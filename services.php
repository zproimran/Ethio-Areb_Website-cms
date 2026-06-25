<?php
// ethioareb/services.php - Complete Services CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// DELETE Service
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image to delete
    $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $service = $result->fetch_assoc();
    
    // Delete image file
    if ($service && $service['image']) {
        $imagePath = UPLOAD_PATH . 'services/' . $service['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Service deleted successfully.';
    } else {
        $error = 'Error deleting service.';
    }
}

// ============================================================
// CREATE/UPDATE Service
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name = sanitize($_POST['name']);
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = sanitize($_POST['description']);
    $long_description = sanitize($_POST['long_description']);
    $features = sanitize($_POST['features']);
    $benefits = sanitize($_POST['benefits']);
    $requirements = sanitize($_POST['requirements']);
    $icon = sanitize($_POST['icon']);
    $price = isset($_POST['price']) && $_POST['price'] !== '' ? floatval($_POST['price']) : null;
    $price_currency = sanitize($_POST['price_currency'] ?? 'USD');
    $price_period = sanitize($_POST['price_period'] ?? 'one_time');
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $order_no = (int)$_POST['order_no'];
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    
    // Validate
    if (empty($name)) {
        $error = 'Service name is required.';
    }
    
    // Generate slug
    $slug = createSlug($name);
    if (empty($error)) {
        $slug = generateUniqueSlug($name, 'services', $id);
    }
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'services/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $image = uploadFile($_FILES['image'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        if (!$image) {
            $error = 'Error uploading image. Please upload JPG, PNG, or GIF files only.';
        }
    }
    
    // If no new image, keep existing
    if (empty($image) && $id > 0) {
        $stmt = $conn->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['image'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            // UPDATE
            $stmt = $conn->prepare("UPDATE services SET 
                name = ?, slug = ?, category_id = ?, description = ?, long_description = ?,
                features = ?, benefits = ?, requirements = ?, image = ?, icon = ?,
                price = ?, price_currency = ?, price_period = ?, is_featured = ?,
                is_active = ?, order_no = ?, meta_title = ?, meta_description = ?
                WHERE id = ?");
            $stmt->bind_param("ssisssssssdsssiissi", 
                $name, $slug, $category_id, $description, $long_description,
                $features, $benefits, $requirements, $image, $icon,
                $price, $price_currency, $price_period, $is_featured,
                $is_active, $order_no, $meta_title, $meta_description, $id);
            
            if ($stmt->execute()) {
                $message = 'Service updated successfully!';
                // Clear edit mode
                unset($_GET['edit']);
            } else {
                $error = 'Error updating service: ' . $conn->error;
            }
        } else {
            // INSERT
            $stmt = $conn->prepare("INSERT INTO services 
                (name, slug, category_id, description, long_description, features, benefits, 
                requirements, image, icon, price, price_currency, price_period, 
                is_featured, is_active, order_no, meta_title, meta_description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssisssssssdsssiiss", 
                $name, $slug, $category_id, $description, $long_description, $features, $benefits,
                $requirements, $image, $icon, $price, $price_currency, $price_period,
                $is_featured, $is_active, $order_no, $meta_title, $meta_description);
            
            if ($stmt->execute()) {
                $message = 'Service created successfully!';
                // Clear form
                $_POST = array();
            } else {
                $error = 'Error creating service: ' . $conn->error;
            }
        }
    }
}

// ============================================================
// READ Service for Edit
// ============================================================
$editService = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editService = $result->fetch_assoc();
}

// ============================================================
// READ All Services
// ============================================================
$services = getServices();
$categories = getServiceCategories();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Services Management</h2>
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
                <h2 class="text-xl font-semibold text-dark-slate">Services</h2>
                <p class="text-gray-500 text-sm">Manage your services</p>
            </div>
            <button onclick="showAddForm()" class="btn-primary">
                <i class="fas fa-plus"></i> Add New Service
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- ============================================================
        CREATE/EDIT FORM
        ============================================================ -->
        <div id="serviceForm" class="table-container mb-8 <?php echo !$editService ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <?php echo $editService ? 'Edit Service' : 'Add New Service'; ?>
                </h3>
                <button onclick="hideForm()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editService['id'] ?? 0; ?>">
                <input type="hidden" name="action" value="save">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Service Name <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required class="form-control" value="<?php echo htmlspecialchars($editService['name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($editService) && $editService['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Icon (Font Awesome class)</label>
                        <input type="text" name="icon" class="form-control" placeholder="e.g., briefcase" value="<?php echo htmlspecialchars($editService['icon'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Order Number</label>
                        <input type="number" name="order_no" class="form-control" value="<?php echo $editService['order_no'] ?? 0; ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label>Short Description</label>
                        <textarea name="description" rows="3" class="form-control"><?php echo htmlspecialchars($editService['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Long Description</label>
                        <textarea name="long_description" rows="5" class="form-control"><?php echo htmlspecialchars($editService['long_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Features (one per line)</label>
                        <textarea name="features" rows="3" class="form-control" placeholder="Feature 1&#10;Feature 2"><?php echo htmlspecialchars($editService['features'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Benefits (one per line)</label>
                        <textarea name="benefits" rows="3" class="form-control" placeholder="Benefit 1&#10;Benefit 2"><?php echo htmlspecialchars($editService['benefits'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Requirements (one per line)</label>
                        <textarea name="requirements" rows="3" class="form-control" placeholder="Requirement 1&#10;Requirement 2"><?php echo htmlspecialchars($editService['requirements'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Price (USD)</label>
                        <input type="number" name="price" step="0.01" class="form-control" value="<?php echo $editService['price'] ?? ''; ?>">
                    </div>
                    <div>
                        <label>Price Period</label>
                        <select name="price_period" class="form-control">
                            <option value="one_time" <?php echo (isset($editService) && $editService['price_period'] == 'one_time') ? 'selected' : ''; ?>>One Time</option>
                            <option value="hourly" <?php echo (isset($editService) && $editService['price_period'] == 'hourly') ? 'selected' : ''; ?>>Hourly</option>
                            <option value="daily" <?php echo (isset($editService) && $editService['price_period'] == 'daily') ? 'selected' : ''; ?>>Daily</option>
                            <option value="weekly" <?php echo (isset($editService) && $editService['price_period'] == 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                            <option value="monthly" <?php echo (isset($editService) && $editService['price_period'] == 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                            <option value="yearly" <?php echo (isset($editService) && $editService['price_period'] == 'yearly') ? 'selected' : ''; ?>>Yearly</option>
                        </select>
                    </div>
                    <div>
                        <label>Meta Title (SEO)</label>
                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($editService['meta_title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Meta Description (SEO)</label>
                        <textarea name="meta_description" rows="2" class="form-control"><?php echo htmlspecialchars($editService['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if ($editService && $editService['image']): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?php echo UPLOAD_URL . 'services/' . $editService['image']; ?>" alt="" class="w-16 h-16 object-cover rounded">
                            <span class="text-sm text-gray-500">Current: <?php echo $editService['image']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" <?php echo (isset($editService) && $editService['is_featured']) ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" <?php echo (!isset($editService) || $editService['is_active']) ? 'checked' : ''; ?>>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editService ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- ============================================================
        LIST ALL SERVICES
        ============================================================ -->
        <div class="table-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">All Services (<?php echo count($services); ?>)</h3>
                <div class="flex items-center gap-3">
                    <input type="text" class="form-control w-64 search-input" data-target="#servicesTable" placeholder="Search services...">
                    <button onclick="window.location.reload()" class="text-gray-400 hover:text-primary transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($services)): ?>
            <div class="overflow-x-auto">
                <table id="servicesTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="60">Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $index => $service): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($service['image']): ?>
                                <img src="<?php echo UPLOAD_URL . 'services/' . $service['image']; ?>" alt="" class="w-12 h-12 object-cover rounded">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center">
                                    <i class="fas fa-image text-gray-300"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($service['name']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo $service['slug']; ?></div>
                            </td>
                            <td><?php echo $service['category_name'] ?? 'Uncategorized'; ?></td>
                            <td><?php echo $service['price'] ? '$' . number_format($service['price'], 2) : 'N/A'; ?></td>
                            <td>
                                <span class="badge <?php echo $service['is_featured'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $service['is_featured'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $service['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $service['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo $service['views'] ?? 0; ?></td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?php echo $service['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $service['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>service/<?php echo $service['slug']; ?>" target="_blank" class="text-blue-500 hover:text-blue-700 transition p-1" title="View on Site">
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
                <i class="fas fa-briefcase text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No services found. Click "Add New Service" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('serviceForm').classList.remove('hidden');
    document.getElementById('serviceForm').scrollIntoView({ behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('serviceForm').classList.add('hidden');
    // Remove edit parameter from URL
    window.history.pushState({}, '', window.location.pathname);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>