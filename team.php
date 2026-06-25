<?php
// ethioareb/team.php - Complete Team CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// DELETE Team Member
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get photo to delete
    $stmt = $conn->prepare("SELECT photo FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    
    if ($member && $member['photo']) {
        $photoPath = UPLOAD_PATH . 'team/' . $member['photo'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Team member deleted successfully.';
    } else {
        $error = 'Error deleting team member.';
    }
}

// ============================================================
// CREATE/UPDATE Team Member
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $full_name = sanitize($_POST['full_name']);
    $position = sanitize($_POST['position']);
    $department = sanitize($_POST['department']);
    $biography = sanitize($_POST['biography']);
    $short_bio = sanitize($_POST['short_bio']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $linkedin_url = sanitize($_POST['linkedin_url']);
    $facebook_url = sanitize($_POST['facebook_url']);
    $twitter_url = sanitize($_POST['twitter_url']);
    $instagram_url = sanitize($_POST['instagram_url']);
    $order_no = (int)$_POST['order_no'];
    $display_order = (int)$_POST['display_order'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($full_name) || empty($position)) {
        $error = 'Name and position are required.';
    }
    
    $slug = generateUniqueSlug($full_name, 'team_members', $id);
    
    $photo = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'team/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $photo = uploadFile($_FILES['photo'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!$photo) {
            $error = 'Error uploading photo. Please upload JPG, PNG, or GIF files only.';
        }
    }
    
    if (empty($photo) && $id > 0) {
        $stmt = $conn->prepare("SELECT photo FROM team_members WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $photo = $row['photo'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE team_members SET 
                full_name = ?, slug = ?, position = ?, department = ?, 
                biography = ?, short_bio = ?, photo = ?, email = ?, phone = ?,
                linkedin_url = ?, facebook_url = ?, twitter_url = ?, instagram_url = ?,
                order_no = ?, display_order = ?, is_featured = ?, is_active = ?
                WHERE id = ?");
            $stmt->bind_param("sssssssssssssiiii", 
                $full_name, $slug, $position, $department,
                $biography, $short_bio, $photo, $email, $phone,
                $linkedin_url, $facebook_url, $twitter_url, $instagram_url,
                $order_no, $display_order, $is_featured, $is_active, $id);
            
            if ($stmt->execute()) {
                $message = 'Team member updated successfully!';
                unset($_GET['edit']);
            } else {
                $error = 'Error updating team member.';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO team_members 
                (full_name, slug, position, department, biography, short_bio, photo, 
                email, phone, linkedin_url, facebook_url, twitter_url, instagram_url,
                order_no, display_order, is_featured, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssssssssiii", 
                $full_name, $slug, $position, $department, $biography, $short_bio, $photo,
                $email, $phone, $linkedin_url, $facebook_url, $twitter_url, $instagram_url,
                $order_no, $display_order, $is_featured, $is_active);
            
            if ($stmt->execute()) {
                $message = 'Team member created successfully!';
                $_POST = array();
            } else {
                $error = 'Error creating team member.';
            }
        }
    }
}

// ============================================================
// READ Team Member for Edit
// ============================================================
$editMember = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editMember = $result->fetch_assoc();
}

// ============================================================
// READ All Team Members
// ============================================================
$team = getTeamMembers();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Team Management</h2>
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
                <h2 class="text-xl font-semibold text-dark-slate">Team Members</h2>
                <p class="text-gray-500 text-sm">Manage your team members</p>
            </div>
            <button onclick="showAddForm()" class="btn-primary">
                <i class="fas fa-plus"></i> Add Team Member
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- CREATE/EDIT FORM -->
        <div id="teamForm" class="table-container mb-8 <?php echo !$editMember ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <?php echo $editMember ? 'Edit Team Member' : 'Add New Team Member'; ?>
                </h3>
                <button onclick="hideForm()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editMember['id'] ?? 0; ?>">
                <input type="hidden" name="action" value="save">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label>Full Name <span class="text-red-500">*</span></label>
                        <input type="text" name="full_name" required class="form-control" value="<?php echo htmlspecialchars($editMember['full_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Position <span class="text-red-500">*</span></label>
                        <input type="text" name="position" required class="form-control" value="<?php echo htmlspecialchars($editMember['position'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Department</label>
                        <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($editMember['department'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editMember['email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editMember['phone'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Order Number</label>
                        <input type="number" name="order_no" class="form-control" value="<?php echo $editMember['order_no'] ?? 0; ?>">
                    </div>
                    <div>
                        <label>Display Order</label>
                        <input type="number" name="display_order" class="form-control" value="<?php echo $editMember['display_order'] ?? 0; ?>">
                    </div>
                    <div>
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                        <?php if ($editMember && $editMember['photo']): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?php echo UPLOAD_URL . 'team/' . $editMember['photo']; ?>" alt="" class="w-16 h-16 object-cover rounded-full">
                            <span class="text-sm text-gray-500">Current: <?php echo $editMember['photo']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label>LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="form-control" value="<?php echo htmlspecialchars($editMember['linkedin_url'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Facebook URL</label>
                        <input type="url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($editMember['facebook_url'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Twitter URL</label>
                        <input type="url" name="twitter_url" class="form-control" value="<?php echo htmlspecialchars($editMember['twitter_url'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Instagram URL</label>
                        <input type="url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($editMember['instagram_url'] ?? ''); ?>">
                    </div>
                    <div class="md:col-span-2">
                        <label>Short Biography</label>
                        <textarea name="short_bio" rows="2" class="form-control"><?php echo htmlspecialchars($editMember['short_bio'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Full Biography</label>
                        <textarea name="biography" rows="4" class="form-control"><?php echo htmlspecialchars($editMember['biography'] ?? ''); ?></textarea>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" <?php echo (isset($editMember) && $editMember['is_featured']) ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" <?php echo (!isset($editMember) || $editMember['is_active']) ? 'checked' : ''; ?>>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editMember ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- LIST ALL TEAM MEMBERS -->
        <div class="table-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">All Team Members (<?php echo count($team); ?>)</h3>
                <div class="flex items-center gap-3">
                    <input type="text" class="form-control w-64 search-input" data-target="#teamTable" placeholder="Search team...">
                    <button onclick="window.location.reload()" class="text-gray-400 hover:text-primary transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($team)): ?>
            <div class="overflow-x-auto">
                <table id="teamTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="60">Photo</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Featured</th>
                            <th>Status</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($team as $index => $member): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($member['photo']): ?>
                                <img src="<?php echo UPLOAD_URL . 'team/' . $member['photo']; ?>" alt="" class="w-12 h-12 object-cover rounded-full">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-300"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($member['full_name']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo $member['slug']; ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($member['position']); ?></td>
                            <td><?php echo htmlspecialchars($member['department'] ?? '-'); ?></td>
                            <td>
                                <?php if ($member['email']): ?>
                                <a href="mailto:<?php echo $member['email']; ?>" class="text-primary hover:underline text-sm"><?php echo $member['email']; ?></a>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $member['is_featured'] ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo $member['is_featured'] ? 'Yes' : 'No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $member['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $member['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?php echo $member['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $member['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>team" target="_blank" class="text-blue-500 hover:text-blue-700 transition p-1" title="View on Site">
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
                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No team members found. Click "Add Team Member" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('teamForm').classList.remove('hidden');
    document.getElementById('teamForm').scrollIntoView({ behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('teamForm').classList.add('hidden');
    window.history.pushState({}, '', window.location.pathname);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>