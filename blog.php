<?php
// ethioareb/blog.php - Complete Blog CRUD
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$conn = getDB();
$message = '';
$error = '';

// ============================================================
// DELETE Blog Post
// ============================================================
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    
    if ($post && $post['featured_image']) {
        $imagePath = UPLOAD_PATH . 'blog/' . $post['featured_image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = 'Blog post deleted successfully.';
    } else {
        $error = 'Error deleting blog post.';
    }
}

// ============================================================
// CREATE/UPDATE Blog Post
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = sanitize($_POST['title']);
    $category_id = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $excerpt = sanitize($_POST['excerpt']);
    $content = $_POST['content'];
    $author_name = sanitize($_POST['author_name'] ?? $_SESSION['admin_name']);
    $status = sanitize($_POST['status']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $meta_title = sanitize($_POST['meta_title']);
    $meta_description = sanitize($_POST['meta_description']);
    $meta_keywords = sanitize($_POST['meta_keywords']);
    
    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    }
    
    $slug = generateUniqueSlug($title, 'blog_posts', $id);
    $published_at = $status == 'published' ? date('Y-m-d H:i:s') : null;
    
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = UPLOAD_PATH . 'blog/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $image = uploadFile($_FILES['image'], $uploadDir, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        if (!$image) {
            $error = 'Error uploading image.';
        }
    }
    
    if (empty($image) && $id > 0) {
        $stmt = $conn->prepare("SELECT featured_image FROM blog_posts WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $image = $row['featured_image'] ?? '';
    }
    
    if (empty($error)) {
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE blog_posts SET 
                title = ?, slug = ?, category_id = ?, excerpt = ?, content = ?,
                featured_image = ?, author_name = ?, status = ?, published_at = COALESCE(?, published_at),
                is_featured = ?, is_urgent = ?, meta_title = ?, meta_description = ?, meta_keywords = ?
                WHERE id = ?");
            $stmt->bind_param("ssissssssiiisssi", 
                $title, $slug, $category_id, $excerpt, $content,
                $image, $author_name, $status, $published_at,
                $is_featured, $is_urgent, $meta_title, $meta_description, $meta_keywords, $id);
            
            if ($stmt->execute()) {
                $message = 'Blog post updated successfully!';
                unset($_GET['edit']);
            } else {
                $error = 'Error updating blog post.';
            }
        } else {
            $stmt = $conn->prepare("INSERT INTO blog_posts 
                (title, slug, category_id, excerpt, content, featured_image, 
                author_name, status, published_at, is_featured, is_urgent,
                meta_title, meta_description, meta_keywords) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissssssiiiss", 
                $title, $slug, $category_id, $excerpt, $content, $image,
                $author_name, $status, $published_at, $is_featured, $is_urgent,
                $meta_title, $meta_description, $meta_keywords);
            
            if ($stmt->execute()) {
                $message = 'Blog post created successfully!';
                $_POST = array();
            } else {
                $error = 'Error creating blog post.';
            }
        }
    }
}

// ============================================================
// READ Blog Post for Edit
// ============================================================
$editPost = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $editPost = $result->fetch_assoc();
}

// ============================================================
// READ All Blog Posts
// ============================================================
$posts = getBlogPosts();
$categories = getBlogCategories();
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Blog Management</h2>
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
                <h2 class="text-xl font-semibold text-dark-slate">Blog Posts</h2>
                <p class="text-gray-500 text-sm">Manage your blog posts</p>
            </div>
            <button onclick="showAddForm()" class="btn-primary">
                <i class="fas fa-plus"></i> New Post
            </button>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- CREATE/EDIT FORM -->
        <div id="postForm" class="table-container mb-8 <?php echo !$editPost ? 'hidden' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">
                    <?php echo $editPost ? 'Edit Post' : 'New Post'; ?>
                </h3>
                <button onclick="hideForm()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $editPost['id'] ?? 0; ?>">
                <input type="hidden" name="action" value="save">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label>Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required class="form-control" value="<?php echo htmlspecialchars($editPost['title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($editPost) && $editPost['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Author</label>
                        <input type="text" name="author_name" class="form-control" value="<?php echo htmlspecialchars($editPost['author_name'] ?? $_SESSION['admin_name']); ?>">
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="draft" <?php echo (isset($editPost) && $editPost['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending" <?php echo (isset($editPost) && $editPost['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="published" <?php echo (isset($editPost) && $editPost['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
                            <option value="archived" <?php echo (isset($editPost) && $editPost['status'] == 'archived') ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <div>
                        <label>Meta Title (SEO)</label>
                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($editPost['meta_title'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Meta Description (SEO)</label>
                        <textarea name="meta_description" rows="2" class="form-control"><?php echo htmlspecialchars($editPost['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div>
                        <label>Meta Keywords (SEO)</label>
                        <input type="text" name="meta_keywords" class="form-control" value="<?php echo htmlspecialchars($editPost['meta_keywords'] ?? ''); ?>">
                    </div>
                    <div>
                        <label>Featured Image</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <?php if ($editPost && $editPost['featured_image']): ?>
                        <div class="mt-2 flex items-center gap-3">
                            <img src="<?php echo UPLOAD_URL . 'blog/' . $editPost['featured_image']; ?>" alt="" class="w-16 h-16 object-cover rounded">
                            <span class="text-sm text-gray-500">Current: <?php echo $editPost['featured_image']; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_featured" <?php echo (isset($editPost) && $editPost['is_featured']) ? 'checked' : ''; ?>>
                            <span>Featured</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_urgent" <?php echo (isset($editPost) && $editPost['is_urgent']) ? 'checked' : ''; ?>>
                            <span>Urgent</span>
                        </label>
                    </div>
                    <div class="md:col-span-2">
                        <label>Excerpt (Short Description)</label>
                        <textarea name="excerpt" rows="2" class="form-control"><?php echo htmlspecialchars($editPost['excerpt'] ?? ''); ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label>Content <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="12" required class="form-control"><?php echo htmlspecialchars($editPost['content'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div class="flex space-x-3 mt-4">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> <?php echo $editPost ? 'Update' : 'Save'; ?>
                    </button>
                    <button type="button" onclick="hideForm()" class="btn-danger">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
        
        <!-- LIST ALL BLOG POSTS -->
        <div class="table-container">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-dark-slate">All Posts (<?php echo count($posts); ?>)</h3>
                <div class="flex items-center gap-3">
                    <input type="text" class="form-control w-64 search-input" data-target="#blogTable" placeholder="Search posts...">
                    <button onclick="window.location.reload()" class="text-gray-400 hover:text-primary transition">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            
            <?php if (!empty($posts)): ?>
            <div class="overflow-x-auto">
                <table id="blogTable">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th width="80">Image</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Likes</th>
                            <th>Date</th>
                            <th width="150">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $index => $post): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if ($post['featured_image']): ?>
                                <img src="<?php echo UPLOAD_URL . 'blog/' . $post['featured_image']; ?>" alt="" class="w-12 h-12 object-cover rounded">
                                <?php else: ?>
                                <div class="w-12 h-12 bg-gray-100 rounded flex items-center justify-center">
                                    <i class="fas fa-newspaper text-gray-300"></i>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="font-medium"><?php echo htmlspecialchars($post['title']); ?></div>
                                <div class="text-xs text-gray-400"><?php echo $post['slug']; ?></div>
                            </td>
                            <td><?php echo $post['category_name'] ?? 'Uncategorized'; ?></td>
                            <td>
                                <span class="badge <?php echo $post['status'] == 'published' ? 'badge-success' : ($post['status'] == 'draft' ? 'badge-warning' : 'badge-danger'); ?>">
                                    <?php echo ucfirst($post['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $post['views'] ?? 0; ?></td>
                            <td><?php echo $post['likes'] ?? 0; ?></td>
                            <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="?edit=<?php echo $post['id']; ?>" class="text-primary hover:text-secondary transition p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $post['id']; ?>" class="text-red-500 hover:text-red-700 transition delete-btn p-1" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <a href="<?php echo SITE_URL; ?>blog/<?php echo $post['slug']; ?>" target="_blank" class="text-blue-500 hover:text-blue-700 transition p-1" title="View on Site">
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
                <i class="fas fa-newspaper text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">No blog posts found. Click "New Post" to create one.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showAddForm() {
    document.getElementById('postForm').classList.remove('hidden');
    document.getElementById('postForm').scrollIntoView({ behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('postForm').classList.add('hidden');
    window.history.pushState({}, '', window.location.pathname);
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>