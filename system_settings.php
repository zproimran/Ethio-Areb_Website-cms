<?php
// ethioareb/system_settings.php - Complete Settings with Hero Slider CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// HERO SLIDER CRUD OPERATIONS
// ============================================================

// Delete Hero Slide
if (isset($_GET['delete_slide']) && is_numeric($_GET['delete_slide'])) {
    $id = (int)$_GET['delete_slide'];
    
    // Get image to delete
    $stmt = $conn->prepare("SELECT image FROM hero_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $slide = $result->fetch_assoc();
    
    if ($slide && $slide['image']) {
        $imagePath = UPLOAD_PATH . 'banners/' . $slide['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM hero_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Hero slide deleted successfully.';
    } else {
        $error = 'Error deleting hero slide.';
    }
}

// Add/Edit Hero Slide
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['slide_action'])) {
    $id = isset($_POST['slide_id']) ? (int)$_POST['slide_id'] : 0;
    $title = sanitize($_POST['title']);
    $subtitle = sanitize($_POST['subtitle']);
    $description = sanitize($_POST['description']);
    $button_text = sanitize($_POST['button_text']);
    $button_url = sanitize($_POST['button_url']);
    $button_type = sanitize($_POST['button_type'] ?? 'primary');
    $order_no = (int)$_POST['order_no'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $background_color = sanitize($_POST['background_color']);
    $text_color = sanitize($_POST['text_color']);
    
    // Handle image upload
    $image = '';
    $uploadDir = UPLOAD_PATH . 'banners/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === UPLOAD_ERR_OK) {
        $image = uploadFile($_FILES['slide_image'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        if (!$image) {
            $error = 'Error uploading image. Please upload JPG, PNG, GIF, WEBP, or SVG files only.';
        }
    }
    
    // If no new image, keep existing
    if (empty($image) && $id > 0) {
        $stmt = $conn->prepare("SELECT image FROM hero_slides WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['image'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            // Update
            $stmt = $conn->prepare("UPDATE hero_slides SET 
                title = ?, subtitle = ?, description = ?, image = ?,
                button_text = ?, button_url = ?, button_type = ?,
                order_no = ?, is_active = ?, background_color = ?, text_color = ?
                WHERE id = ?");
            $stmt->bind_param("ssssssssissi", 
                $title, $subtitle, $description, $image,
                $button_text, $button_url, $button_type,
                $order_no, $is_active, $background_color, $text_color, $id);
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO hero_slides 
                (title, subtitle, description, image, button_text, button_url, 
                button_type, order_no, is_active, background_color, text_color) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssiss", 
                $title, $subtitle, $description, $image, $button_text, $button_url,
                $button_type, $order_no, $is_active, $background_color, $text_color);
        }
        
        if ($stmt->execute()) {
            $message = 'Hero slide saved successfully!';
            // Clear edit mode
            unset($_GET['edit_slide']);
        } else {
            $error = 'Error saving hero slide: ' . $conn->error;
        }
    }
}

// Get slide for edit
$editSlide = null;
if (isset($_GET['edit_slide']) && is_numeric($_GET['edit_slide'])) {
    $id = (int)$_GET['edit_slide'];
    $stmt = $conn->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editSlide = $result->fetch_assoc();
}

// Get all hero slides
$slides = $conn->query("SELECT * FROM hero_slides ORDER BY order_no ASC, created_at DESC");

// ============================================================
// UPDATE SETTINGS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $settings = $_POST['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        $value = sanitize($value);
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->bind_param("ss", $value, $key);
        if (!$stmt->execute()) {
            $error = 'Error updating settings.';
            break;
        }
    }
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'settings/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $logo = uploadFile($_FILES['logo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
        if ($logo) {
            $check = $conn->query("SELECT id FROM settings WHERE setting_key = 'site_logo'");
            if ($check->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_logo'");
            } else {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?)");
            }
            $stmt->bind_param("s", $logo);
            $stmt->execute();
            $message = 'Logo uploaded successfully!';
        }
    }
    
    // Handle favicon upload
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'settings/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $favicon = uploadFile($_FILES['favicon'], $uploadDir, ['ico', 'png', 'jpg', 'jpeg', 'gif']);
        if ($favicon) {
            $check = $conn->query("SELECT id FROM settings WHERE setting_key = 'site_favicon'");
            if ($check->num_rows > 0) {
                $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'site_favicon'");
            } else {
                $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('site_favicon', ?)");
            }
            $stmt->bind_param("s", $favicon);
            $stmt->execute();
            $message = 'Favicon uploaded successfully!';
        }
    }
    
    if (empty($error) && empty($message)) {
        $message = 'Settings updated successfully.';
    }
}

// ============================================================
// Get All Settings
// ============================================================
$settings = getSettings();

// Ensure logo and favicon keys exist
if (!isset($settings['site_logo'])) {
    $settings['site_logo'] = '';
}
if (!isset($settings['site_favicon'])) {
    $settings['site_favicon'] = '';
}

// Upload directory status
$uploadDir = UPLOAD_PATH . 'banners/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
$uploadDirWritable = is_dir($uploadDir) && is_writable($uploadDir);
$uploadDirSettings = UPLOAD_PATH . 'settings/';
if (!is_dir($uploadDirSettings)) {
    mkdir($uploadDirSettings, 0777, true);
}
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Settings</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600"><?php echo $_SESSION['admin_name']; ?></span>
            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold">
                <?php echo substr($_SESSION['admin_name'] ?? 'A', 0, 1); ?>
            </div>
        </div>
    </div>
    
    <div class="content">
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
        </div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <!-- ============================================================
        HERO SLIDER MANAGEMENT
        ============================================================ -->
        <div class="table-container mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <i class="fas fa-sliders-h text-primary mr-2"></i>Hero Slider Management
                </h3>
                <button onclick="showSlideForm()" class="btn-primary text-sm">
                    <i class="fas fa-plus"></i> Add Slide
                </button>
            </div>
            
            <!-- Upload Directory Status 
            <div class="bg-gray-50 rounded-lg p-3 mb-4 text-sm flex flex-wrap items-center gap-4">
                <span><strong>Upload Directory:</strong> <?php echo UPLOAD_PATH . 'banners/'; ?></span>
                <span><strong>Status:</strong> <?php echo $uploadDirWritable ? '✅ Writable' : '❌ Not Writable'; ?></span>
                <?php if (!$uploadDirWritable): ?>
                <span class="text-red-500">⚠️ Please set permissions to 755</span>
                <?php endif; ?>
            </div>
                 -->
            
            <!-- Add/Edit Slide Form -->
            <div id="slideForm" class="mb-6 <?php echo !$editSlide ? 'hidden' : ''; ?>" style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb;">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-dark-slate text-lg">
                        <i class="fas fa-<?php echo $editSlide ? 'edit' : 'plus'; ?> text-primary mr-2"></i>
                        <?php echo $editSlide ? 'Edit Slide' : 'Add New Slide'; ?>
                    </h4>
                    <button onclick="hideSlideForm()" class="text-gray-400 hover:text-gray-600 text-xl">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="slide_id" value="<?php echo $editSlide['id'] ?? 0; ?>">
                    <input type="hidden" name="slide_action" value="save">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Title <span class="text-red-500">*</span></label>
                            <input type="text" name="title" required class="form-control" placeholder="Slide Title" value="<?php echo htmlspecialchars($editSlide['title'] ?? ''); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Subtitle</label>
                            <input type="text" name="subtitle" class="form-control" placeholder="Subtitle / Badge Text" value="<?php echo htmlspecialchars($editSlide['subtitle'] ?? ''); ?>">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Description</label>
                            <textarea name="description" rows="2" class="form-control" placeholder="Slide description..."><?php echo htmlspecialchars($editSlide['description'] ?? ''); ?></textarea>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Button Text</label>
                            <input type="text" name="button_text" class="form-control" placeholder="Learn More" value="<?php echo htmlspecialchars($editSlide['button_text'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Button URL</label>
                            <input type="text" name="button_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($editSlide['button_url'] ?? ''); ?>">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Button Type</label>
                            <select name="button_type" class="form-control">
                                <option value="primary" <?php echo (isset($editSlide) && $editSlide['button_type'] == 'primary') ? 'selected' : ''; ?>>Primary (Teal)</option>
                                <option value="secondary" <?php echo (isset($editSlide) && $editSlide['button_type'] == 'secondary') ? 'selected' : ''; ?>>Secondary (Green)</option>
                                <option value="outline" <?php echo (isset($editSlide) && $editSlide['button_type'] == 'outline') ? 'selected' : ''; ?>>Outline</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Order Number</label>
                            <input type="number" name="order_no" class="form-control" value="<?php echo $editSlide['order_no'] ?? 0; ?>">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Background Color</label>
                            <input type="color" name="background_color" class="form-control" style="padding: 2px; height: 44px;" value="<?php echo $editSlide['background_color'] ?? '#13AABB'; ?>">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Text Color</label>
                            <input type="color" name="text_color" class="form-control" style="padding: 2px; height: 44px;" value="<?php echo $editSlide['text_color'] ?? '#FFFFFF'; ?>">
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Slide Image <span class="text-red-500">*</span></label>
                            <input type="file" name="slide_image" class="form-control" accept="image/*" <?php echo !$editSlide ? 'required' : ''; ?>>
                            <?php if ($editSlide && $editSlide['image']): ?>
                            <div class="mt-3 flex items-center gap-3">
                                <img src="<?php echo UPLOAD_URL . 'banners/' . $editSlide['image']; ?>" alt="Current" class="h-16 w-auto object-cover rounded border">
                                <span class="text-xs text-gray-500">Current: <?php echo $editSlide['image']; ?></span>
                            </div>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">Recommended: 1920x800px. Max 5MB.</p>
                        </div>
                        <div class="flex items-center gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="is_active" <?php echo (!isset($editSlide) || $editSlide['is_active']) ? 'checked' : ''; ?>>
                                <span class="text-sm font-medium">Active</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex space-x-3 pt-2">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> <?php echo $editSlide ? 'Update' : 'Save'; ?>
                        </button>
                        <button type="button" onclick="hideSlideForm()" class="btn-danger">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Slides List -->
            <div class="overflow-x-auto">
                <?php if ($slides && $slides->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="180">Image</th>
                            <th>Title</th>
                            <th>Subtitle</th>
                            <th>Order</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; while ($slide = $slides->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <?php if ($slide['image']): ?>
                                <img src="<?php echo UPLOAD_URL . 'banners/' . $slide['image']; ?>" alt="" class="h-12 w-24 object-cover rounded border">
                                <?php else: ?>
                                <div class="h-12 w-24 bg-gray-200 rounded flex items-center justify-center text-gray-400 text-xs">No Image</div>
                                <?php endif; ?>
                            </td>
                            <td class="font-medium"><?php echo htmlspecialchars($slide['title']); ?></td>
                            <td><?php echo htmlspecialchars($slide['subtitle'] ?? '-'); ?></td>
                            <td><?php echo $slide['order_no']; ?></td>
                            <td>
                                <span class="badge <?php echo $slide['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $slide['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit_slide=<?php echo $slide['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete_slide=<?php echo $slide['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php if ($slide['image']): ?>
                                    <button onclick="previewSlide('<?php echo UPLOAD_URL . 'banners/' . $slide['image']; ?>', '<?php echo htmlspecialchars($slide['title']); ?>')" class="text-blue-500 hover:text-blue-700 transition p-1" title="Preview">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-sliders-h text-3xl mb-2 block"></i>
                    <p>No hero slides found. Click "Add Slide" to create one.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ============================================================
        SETTINGS FORM
        ============================================================ -->
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="save_settings">
            
            <!-- General Settings -->
            <div class="table-container mb-6">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">
                    <i class="fas fa-cog text-primary mr-2"></i>General Settings
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Site Name</label>
                        <input type="text" name="settings[site_name]" class="form-control" value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Site Tagline</label>
                        <input type="text" name="settings[site_tagline]" class="form-control" value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Site Email</label>
                        <input type="email" name="settings[site_email]" class="form-control" value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Support Email</label>
                        <input type="email" name="settings[support_email]" class="form-control" value="<?php echo htmlspecialchars($settings['support_email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Phone Number</label>
                        <input type="text" name="settings[site_phone]" class="form-control" value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Mobile Number</label>
                        <input type="text" name="settings[site_mobile]" class="form-control" value="<?php echo htmlspecialchars($settings['site_mobile'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Address</label>
                        <input type="text" name="settings[site_address]" class="form-control" value="<?php echo htmlspecialchars($settings['site_address'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Timezone</label>
                        <input type="text" name="settings[site_timezone]" class="form-control" value="<?php echo htmlspecialchars($settings['site_timezone'] ?? 'Africa/Addis_Ababa'); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="table-container mb-6">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">
                    <i class="fas fa-share-alt text-primary mr-2"></i>Social Media
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label><i class="fab fa-whatsapp text-green-500 mr-2"></i>WhatsApp URL</label>
                        <input type="url" name="settings[site_whatsapp]" class="form-control" value="<?php echo htmlspecialchars($settings['site_whatsapp'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-telegram text-blue-500 mr-2"></i>Telegram</label>
                        <input type="text" name="settings[site_telegram]" class="form-control" value="<?php echo htmlspecialchars($settings['site_telegram'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-facebook text-blue-600 mr-2"></i>Facebook URL</label>
                        <input type="url" name="settings[site_facebook]" class="form-control" value="<?php echo htmlspecialchars($settings['site_facebook'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-instagram text-pink-500 mr-2"></i>Instagram URL</label>
                        <input type="url" name="settings[site_instagram]" class="form-control" value="<?php echo htmlspecialchars($settings['site_instagram'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-linkedin text-blue-700 mr-2"></i>LinkedIn URL</label>
                        <input type="url" name="settings[site_linkedin]" class="form-control" value="<?php echo htmlspecialchars($settings['site_linkedin'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-twitter text-blue-400 mr-2"></i>Twitter/X URL</label>
                        <input type="url" name="settings[site_twitter]" class="form-control" value="<?php echo htmlspecialchars($settings['site_twitter'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-youtube text-red-600 mr-2"></i>YouTube URL</label>
                        <input type="url" name="settings[site_youtube]" class="form-control" value="<?php echo htmlspecialchars($settings['site_youtube'] ?? ''); ?>">
                    </div>
                    <div>
                        <label><i class="fab fa-tiktok text-black mr-2"></i>TikTok URL</label>
                        <input type="url" name="settings[site_tiktok]" class="form-control" value="<?php echo htmlspecialchars($settings['site_tiktok'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- SEO Settings -->
            <div class="table-container mb-6">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">
                    <i class="fas fa-search text-primary mr-2"></i>SEO Settings
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label>Meta Title</label>
                        <input type="text" name="settings[meta_title]" class="form-control" value="<?php echo htmlspecialchars($settings['meta_title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Meta Description</label>
                        <textarea name="settings[meta_description]" rows="2" class="form-control"><?php echo htmlspecialchars($settings['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Meta Keywords</label>
                        <input type="text" name="settings[meta_keywords]" class="form-control" value="<?php echo htmlspecialchars($settings['meta_keywords'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Content Settings -->
            <div class="table-container mb-6">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">
                    <i class="fas fa-file-alt text-primary mr-2"></i>Content Settings
                </h3>
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label>Welcome Title</label>
                        <input type="text" name="settings[welcome_title]" class="form-control" value="<?php echo htmlspecialchars($settings['welcome_title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Welcome Text</label>
                        <textarea name="settings[welcome_text]" rows="3" class="form-control"><?php echo htmlspecialchars($settings['welcome_text'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Footer Text</label>
                        <input type="text" name="settings[footer_text]" class="form-control" value="<?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            
            <!-- Branding -->
            <div class="table-container mb-6">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">
                    <i class="fas fa-palette text-primary mr-2"></i>Branding
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Logo</label>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <?php if (!empty($settings['site_logo'])): ?>
                        <div class="mt-3 flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img src="<?php echo UPLOAD_URL . 'settings/' . $settings['site_logo']; ?>" alt="Logo" class="h-16 object-contain border rounded">
                            <div>
                                <span class="text-sm text-gray-500">Current: <?php echo $settings['site_logo']; ?></span>
                                <br>
                                <a href="?delete_logo=1" class="text-red-500 text-sm hover:underline delete-btn">Remove Logo</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>Favicon</label>
                        <input type="file" name="favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg,.gif,.svg">
                        <?php if (!empty($settings['site_favicon'])): ?>
                        <div class="mt-3 flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <img src="<?php echo UPLOAD_URL . 'settings/' . $settings['site_favicon']; ?>" alt="Favicon" class="h-10 w-10 object-contain border rounded">
                            <div>
                                <span class="text-sm text-gray-500">Current: <?php echo $settings['site_favicon']; ?></span>
                                <br>
                                <a href="?delete_favicon=1" class="text-red-500 text-sm hover:underline delete-btn">Remove Favicon</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="imageModal" class="fixed inset-0 bg-black/80 z-50 hidden flex items-center justify-center p-4" onclick="closePreview()">
    <div class="relative max-w-4xl w-full" onclick="event.stopPropagation()">
        <button onclick="closePreview()" class="absolute -top-12 right-0 text-white text-3xl hover:text-gray-300 transition">
            <i class="fas fa-times"></i>
        </button>
        <img id="previewImage" src="" alt="Preview" class="w-full h-auto max-h-[80vh] object-contain rounded-lg shadow-2xl">
        <div id="previewCaption" class="text-center text-white text-lg font-semibold mt-4"></div>
    </div>
</div>

<script>
function showSlideForm() {
    document.getElementById('slideForm').classList.remove('hidden');
    document.getElementById('slideForm').scrollIntoView({ behavior: 'smooth' });
}

function hideSlideForm() {
    document.getElementById('slideForm').classList.add('hidden');
    window.history.pushState({}, '', window.location.pathname);
}

function previewSlide(imageUrl, title) {
    document.getElementById('previewImage').src = imageUrl;
    document.getElementById('previewCaption').textContent = title || 'Slide Preview';
    document.getElementById('imageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closePreview() {
    document.getElementById('imageModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePreview();
    }
});

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}

// Delete confirmation
document.querySelectorAll('.delete-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
});

// Show file name when selected
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        const fileName = this.files[0] ? this.files[0].name : 'No file chosen';
        const parent = this.closest('div');
        let nameDisplay = parent.querySelector('.file-name-display');
        if (!nameDisplay) {
            nameDisplay = document.createElement('div');
            nameDisplay.className = 'file-name-display text-sm text-gray-500 mt-1';
            parent.appendChild(nameDisplay);
        }
        nameDisplay.textContent = fileName ? '📎 ' + fileName : '';
    });
});
</script>

<?php include 'includes/footer.php'; ?>