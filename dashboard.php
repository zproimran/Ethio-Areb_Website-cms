<?php
// ethioareb/dashboard.php - CMS Dashboard
require_once 'config/db_config.php';
require_once 'includes/auth.php';

$stats = getDashboardStats();
$conn = getDB();

// Get recent messages
$recentMessages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 5");

// Get recent applications
$recentApps = $conn->query("SELECT * FROM job_applications ORDER BY created_at DESC LIMIT 5");

// Get recent blog posts
$recentPosts = $conn->query("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5");
?>
<?php include 'includes/header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="topbar">
        <div class="flex items-center gap-4">
            <button class="sidebar-toggle text-gray-600 hover:text-primary text-xl" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h2 class="text-xl font-semibold text-dark-slate">Dashboard</h2>
        </div>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-600"><?php echo $_SESSION['admin_name']; ?></span>
            <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center text-primary font-semibold">
                <?php echo substr($_SESSION['admin_name'] ?? 'A', 0, 1); ?>
            </div>
        </div>
    </div>
    
    <div class="content">
        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Services</p>
                        <p class="stat-number text-primary"><?php echo $stats['services']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                        <i class="fas fa-briefcase text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Team Members</p>
                        <p class="stat-number text-secondary"><?php echo $stats['team']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-secondary/10 rounded-xl flex items-center justify-center text-secondary">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Blog Posts</p>
                        <p class="stat-number text-blue-600"><?php echo $stats['posts']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600">
                        <i class="fas fa-newspaper text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Applications</p>
                        <p class="stat-number text-green-600"><?php echo $stats['applications']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center text-green-600">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Second Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Testimonials</p>
                        <p class="stat-number text-yellow-600"><?php echo $stats['testimonials']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center text-yellow-600">
                        <i class="fas fa-star text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Gallery Items</p>
                        <p class="stat-number text-purple-600"><?php echo $stats['gallery']; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600">
                        <i class="fas fa-images text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Messages</p>
                        <p class="stat-number text-red-600"><?php echo $stats['messages']; ?></p>
                        <?php if ($stats['unread'] > 0): ?>
                        <span class="text-xs text-red-500"><?php echo $stats['unread']; ?> unread</span>
                        <?php endif; ?>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-red-600">
                        <i class="fas fa-envelope text-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">Recent Messages</h3>
                <?php if ($recentMessages && $recentMessages->num_rows > 0): ?>
                <div class="space-y-3">
                    <?php while ($msg = $recentMessages->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-medium text-dark-slate"><?php echo $msg['name']; ?></p>
                            <p class="text-sm text-gray-500"><?php echo substr($msg['message'], 0, 50); ?>...</p>
                        </div>
                        <span class="text-xs text-gray-400"><?php echo timeAgo($msg['created_at']); ?></span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">No messages yet</p>
                <?php endif; ?>
                <a href="contact" class="text-primary hover:text-secondary transition text-sm mt-4 inline-block">View all messages →</a>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-dark-slate mb-4">Recent Applications</h3>
                <?php if ($recentApps && $recentApps->num_rows > 0): ?>
                <div class="space-y-3">
                    <?php while ($app = $recentApps->fetch_assoc()): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div>
                            <p class="font-medium text-dark-slate"><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></p>
                            <p class="text-sm text-gray-500"><?php echo $app['position_applied']; ?></p>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full <?php echo $app['status'] == 'new' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                            <?php echo ucfirst($app['status']); ?>
                        </span>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500 text-center py-4">No applications yet</p>
                <?php endif; ?>
                <a href="applications" class="text-primary hover:text-secondary transition text-sm mt-4 inline-block">View all applications →</a>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('open');
}
</script>

<?php include 'includes/footer.php'; ?>