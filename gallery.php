<?php
// ethioareb/gallery.php - Complete Gallery CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// DELETE Gallery Item
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT image FROM gallery_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if ($item && $item['image']) {
        $imagePath = UPLOAD_PATH . 'gallery/' . $item['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM gallery_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Gallery item deleted successfully.';
    } else {
        $error = 'Error deleting gallery item.';
    }
}

// ============================================================
// CREATE/UPDATE Gallery Item
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $type = sanitize($_POST['type']);
    $video_url = sanitize($_POST['video_url']);
    $youtube_id = sanitize($_POST['youtube_id']);
    $vimeo_id = sanitize($_POST['vimeo_id']);
    $order_no = (int)$_POST['order_no'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'gallery/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $allowedTypes = $type == 'video' ? ['mp4', 'webm', 'mov'] : ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $image = uploadFile($_FILES['image'], $uploadDir, $allowedTypes);
        if (!$image) {
            $error = 'Error uploading file.';
        }
    }
    
    if (empty($image) && $id > 0) {
        $stmt = $conn->prepare("SELECT image FROM gallery_items WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['image'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE gallery_items SET 
                category_id = ?, title = ?, description = ?, image = ?, 
                video_url = ?, youtube_id = ?, vimeo_id = ?, type = ?,
                order_no = ?, is_featured = ?, is_active = ?
                WHERE id = ?");
            $stmt->bind_param("issssssssiii", 
                $category_id, $title, $description, $image,
                $video_url, $youtube_id, $vimeo_id, $type,
                $order_no, $is_featured, $is_active, $id);
            
            if ($stmt->execute()) {
                $message = 'Gallery item updated successfully!';
                unset($_GET['edit']);
            } else {
                $error = 'Error updating gallery item.';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO gallery_items 
                (category_id, title, description, image, video_url, youtube_id, vimeo_id, 
                type, order_no, is_featured, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssssiii", 
                $category_id, $title, $description, $image,
                $video_url, $youtube_id, $vimeo_id, $type,
                $order_no, $is_featured, $is_active);
            
            if ($stmt->execute()) {
                $message = 'Gallery item created successfully!';
                $_POST = array();
            } else {
                $error = 'Error creating gallery item.';
            }
        }
    }
}

// ============================================================
// READ Gallery Item for Edit
// ============================================================
$editItem = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM gallery_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editItem = $result->fetch_assoc();
}

// ============================================================
// READ All Gallery Items
// ============================================================
$items = getGalleryItems();
$categories = getGalleryCategories();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Gallery Management</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600"><?php echo $_SESSION['admin_name']; ?></span>
            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold">
                <?php echo substr($_SESSION['admin_name'] ?? 'A', 0, 1); ?>
            </div>
        </div>
    </div>
    
    <div class="content">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-semibold text-dark-slate">Gallery</h2>
                <p class="text-gray-500 text-sm">Manage your gallery items</p>
            </div>
            <button onclick="showAddForm()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Gallery Item
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- CREATE/EDIT FORM -->
        <div id="galleryForm" class="table-container mb-8 <?php echo !$editItem ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <?php echo $editItem ? 'Edit Gallery Item' : 'Add New Gallery Item'; ?>
                </h3>
                <button onclick="hideForm()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editItem['id'] ?? 0; ?>">
                <input type="hidden" name="action" value="save">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editItem['title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($editItem) && $editItem['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Type</label>
                        <select name="type" class="form-control">
                            <option value="image" <?php echo (isset($editItem) && $editItem['type'] == 'image') ? 'selected' : ''; ?>>Image</option>
                            <option value="video" <?php echo (isset($editItem) && $editItem['type'] == 'video') ? 'selected' : ''; ?>>Video</option>
                            <option value="youtube" <?php echo (isset($editItem) && $editItem['type'] == 'youtube') ? 'selected' : ''; ?>>YouTube</option>
                            <option value="vimeo" <?php echo (isset($editItem) && $editItem['type'] == 'vimeo') ? 'selected' : ''; ?>>Vimeo</option>
                        </select>
                    </div>
                    <div>
                        <label>Order Number</label>
                        <input type="number" name="order_no" class="form-control" value="<?php echo $editItem['order_no'] ?? 0; ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label>Description</label>
                        <textarea name="description" rows="2" class="form-control"><?php echo htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>File (Image/Video)</label>
                        <input type="file" name="image" class="form-control" accept="image/*,video/*">
                        <?php if ($editItem && $editItem['image']): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <?php if (in_array($editItem['type'], ['image', 'youtube', 'vimeo'])): ?>
                            <img src="<?php echo UPLOAD_URL . 'gallery/' . $editItem['image']; ?>" alt="" class="w-16 h-16 object-cover rounded">
                            <?php else: ?>
                            <i class="fas fa-video text-2xl text-gray-400"></i>
                            <?php endif; ?>
                            <span class="text-sm text-gray-500">Current: <?php echo $editItem['image']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>Video URL</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($editItem['video_url'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>YouTube ID</label>
                        <input type="text" name="youtube_id" class="form-control" placeholder="YouTube video ID" value="<?php echo htmlspecialchars($editItem['youtube_id'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Vimeo ID</label>
                        <input type="text" name="vimeo_id" class="form-control" placeholder="Vimeo video ID" value="<?php echo htmlspecialchars($editItem['vimeo_id'] ?? ''); ?>">
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" <?php echo (isset($editItem) && $editItem['is_featured']) ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" <?php echo (!isset($editItem) || $editItem['is_active']) ? 'checked' : ''; ?>>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editItem ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- LIST ALL GALLERY ITEMS -->
        <div class="table-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">All Gallery Items (<?php echo count($items); ?>)</h3>
                <div class="flex items-center gap-3">
                    <input type="text" class="form-control w-64 search-input" data-target="#galleryTable" placeholder="Search gallery...">
                    <button onclick="window.location.reload()" class="text-gray-400 hover:text-primary transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($items)): ?>
            <div class="overflow-x-auto">
                <table id="galleryTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="80">Preview</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $index => $item): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($item['image']): ?>
                                    <?php if (in_array($item['type'], ['image', 'youtube', 'vimeo'])): ?>
                                    <img src="<?php echo UPLOAD_URL . 'gallery/' . $item['image']; ?>" alt="" class="w-14 h-14 object-cover rounded">
                                    <?php else: ?>
                                    <div class="w-14 h-14 bg-gray-100 rounded flex items-center justify-center">
                                        <i class="fas fa-video text-2xl text-gray-400"></i>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                <div class="w-14 h-14 bg-gray-100 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-300"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['title'] ?? 'Untitled'); ?></td>
                            <td><?php echo $item['category_name'] ?? 'Uncategorized'; ?></td>
                            <td><span class="badge badge-info"><?php echo ucfirst($item['type']); ?></span></td>
                            <td>
                                <span class="badge <?php echo $item['is_featured'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $item['is_featured'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $item['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $item['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo $item['views'] ?? 0; ?></td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $item['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>gallery" target="_blank" class="text-blue-500 hover:text-blue-700 transition p-1" title="View on Site">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-12">
                <i class="fas fa-images text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No gallery items found. Click "Add Gallery Item" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('galleryForm').classList.remove('hidden');
    document.getElementById('galleryForm').scrollIntoView({ behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('galleryForm').classList.add('hidden');
    window.history.pushState({}, '', window.location.pathname);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>