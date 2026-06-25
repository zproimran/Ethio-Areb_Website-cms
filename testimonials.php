<?php
// ethioareb/testimonials.php - Complete Testimonials CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// DELETE Testimonial
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT photo FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $testimonial = $result->fetch_assoc();
    
    if ($testimonial && $testimonial['photo']) {
        $photoPath = UPLOAD_PATH . 'testimonials/' . $testimonial['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Testimonial deleted successfully.';
    } else {
        $error = 'Error deleting testimonial.';
    }
}

// ============================================================
// CREATE/UPDATE Testimonial
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $customer_name = sanitize($_POST['customer_name']);
    $company = sanitize($_POST['company']);
    $position = sanitize($_POST['position']);
    $testimonial = sanitize($_POST['testimonial']);
    $rating = (int)$_POST['rating'];
    $video_url = sanitize($_POST['video_url']);
    $order_no = (int)$_POST['order_no'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($customer_name) || empty($testimonial)) {
        $error = 'Customer name and testimonial are required.';
    }
    
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'testimonials/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $photo = uploadFile($_FILES['photo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!$photo) {
            $error = 'Error uploading photo.';
        }
    }
    
    if (empty($photo) && $id > 0) {
        $stmt = $conn->prepare("SELECT photo FROM testimonials WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $photo = $row['photo'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE testimonials SET 
                customer_name = ?, company = ?, position = ?, testimonial = ?, 
                photo = ?, rating = ?, video_url = ?, order_no = ?, 
                is_featured = ?, is_active = ?
                WHERE id = ?");
            $stmt->bind_param("sssssisiiii", 
                $customer_name, $company, $position, $testimonial,
                $photo, $rating, $video_url, $order_no,
                $is_featured, $is_active, $id);
            
            if ($stmt->execute()) {
                $message = 'Testimonial updated successfully!';
                unset($_GET['edit']);
            } else {
                $error = 'Error updating testimonial.';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO testimonials 
                (customer_name, company, position, testimonial, photo, rating, 
                video_url, order_no, is_featured, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssisiii", 
                $customer_name, $company, $position, $testimonial, $photo,
                $rating, $video_url, $order_no, $is_featured, $is_active);
            
            if ($stmt->execute()) {
                $message = 'Testimonial created successfully!';
                $_POST = array();
            } else {
                $error = 'Error creating testimonial.';
            }
        }
    }
}

// ============================================================
// READ Testimonial for Edit
// ============================================================
$editTestimonial = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editTestimonial = $result->fetch_assoc();
}

// ============================================================
// READ All Testimonials
// ============================================================
$testimonials = getTestimonials();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Testimonials Management</h2>
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
                <h2 class="text-xl font-semibold text-dark-slate">Testimonials</h2>
                <p class="text-gray-500 text-sm">Manage client testimonials</p>
            </div>
            <button onclick="showAddForm()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Testimonial
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- CREATE/EDIT FORM -->
        <div id="testimonialForm" class="table-container mb-8 <?php echo !$editTestimonial ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <?php echo $editTestimonial ? 'Edit Testimonial' : 'Add New Testimonial'; ?>
                </h3>
                <button onclick="hideForm()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editTestimonial['id'] ?? 0; ?>">
                <input type="hidden" name="action" value="save">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Customer Name <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required class="form-control" value="<?php echo htmlspecialchars($editTestimonial['customer_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Company</label>
                        <input type="text" name="company" class="form-control" value="<?php echo htmlspecialchars($editTestimonial['company'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Position</label>
                        <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($editTestimonial['position'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Rating (1-5)</label>
                        <select name="rating" class="form-control">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($editTestimonial) && $editTestimonial['rating'] == $i) ? 'selected' : ($i == 5 ? 'selected' : ''); ?>>
                                <?php echo $i; ?> Stars
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div>
                        <label>Order Number</label>
                        <input type="number" name="order_no" class="form-control" value="<?php echo $editTestimonial['order_no'] ?? 0; ?>">
                    </div>
                    <div>
                        <label>Video URL (optional)</label>
                        <input type="url" name="video_url" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($editTestimonial['video_url'] ?? ''); ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label>Testimonial <span class="text-red-500">*</span></label>
                        <textarea name="testimonial" rows="4" required class="form-control"><?php echo htmlspecialchars($editTestimonial['testimonial'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <?php if ($editTestimonial && $editTestimonial['photo']): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?php echo UPLOAD_URL . 'testimonials/' . $editTestimonial['photo']; ?>" alt="" class="w-12 h-12 object-cover rounded-full">
                            <span class="text-sm text-gray-500">Current: <?php echo $editTestimonial['photo']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" <?php echo (isset($editTestimonial) && $editTestimonial['is_featured']) ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" <?php echo (!isset($editTestimonial) || $editTestimonial['is_active']) ? 'checked' : ''; ?>>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editTestimonial ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- LIST ALL TESTIMONIALS -->
        <div class="table-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">All Testimonials (<?php echo count($testimonials); ?>)</h3>
                <div class="flex items-center gap-3">
                    <input type="text" class="form-control w-64 search-input" data-target="#testimonialTable" placeholder="Search testimonials...">
                    <button onclick="window.location.reload()" class="text-gray-400 hover:text-primary transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($testimonials)): ?>
            <div class="overflow-x-auto">
                <table id="testimonialTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="60">Photo</th>
                            <th>Customer</th>
                            <th>Company</th>
                            <th>Rating</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testimonials as $index => $testimonial): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($testimonial['photo']): ?>
                                <img src="<?php echo UPLOAD_URL . 'testimonials/' . $testimonial['photo']; ?>" alt="" class="w-12 h-12 object-cover rounded-full">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-300"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($testimonial['customer_name']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($testimonial['position'] ?? ''); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($testimonial['company'] ?? 'N/A'); ?></td>
                            <td>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?php echo $i <= $testimonial['rating'] ? 'text-secondary' : 'text-gray-300'; ?> text-sm"></i>
                                <?php endfor; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $testimonial['is_featured'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $testimonial['is_featured'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $testimonial['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $testimonial['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?php echo $testimonial['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $testimonial['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>testimonials" target="_blank" class="text-blue-500 hover:text-blue-700 transition p-1" title="View on Site">
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
                <i class="fas fa-star text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No testimonials found. Click "Add Testimonial" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('testimonialForm').classList.remove('hidden');
    document.getElementById('testimonialForm').scrollIntoView({ behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('testimonialForm').classList.add('hidden');
    window.history.pushState({}, '', window.location.pathname);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>