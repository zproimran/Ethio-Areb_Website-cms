<?php
// ethioareb/sidebar.php - CMS Sidebar
$current_page = basename($_SERVER['PHP_SELF']);
$current_page = str_replace('.php', '', $current_page);
if ($current_page == 'index') $current_page = 'dashboard';
if ($current_page == 'system_settings') $current_page = 'settings';
$unread = getUnreadCount();
?>
<aside class="sidebar" id="sidebar">
    <div class="brand">
        <h1>Ethio <span>Areb</span></h1>
        <p class="text-gray-400 text-xs mt-1">CMS Panel</p>
    </div>
    
    <nav class="mt-6">
        <a href="dashboard" class="nav-link <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>
        <a href="services" class="nav-link <?php echo $current_page == 'services' ? 'active' : ''; ?>">
            <i class="fas fa-briefcase"></i> Services
        </a>
        <a href="team" class="nav-link <?php echo $current_page == 'team' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Team
        </a>
        <a href="gallery" class="nav-link <?php echo $current_page == 'gallery' ? 'active' : ''; ?>">
            <i class="fas fa-images"></i> Gallery
        </a>
        <a href="testimonials" class="nav-link <?php echo $current_page == 'testimonials' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Testimonials
        </a>
        <a href="blog" class="nav-link <?php echo $current_page == 'blog' ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i> Blog
        </a>
        <a href="contact" class="nav-link <?php echo $current_page == 'contact' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Messages
            <?php if ($unread > 0): ?>
            <span class="badge badge-red"><?php echo $unread; ?></span>
            <?php endif; ?>
        </a>
        <a href="applications" class="nav-link <?php echo $current_page == 'applications' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Applications
        </a>
        <a href="settings" class="nav-link <?php echo $current_page == 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="logout" class="nav-link text-red-400 hover:text-red-300">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </nav>
</aside>

<style>
.sidebar {
    width: 260px;
    background: #1F2937;
    min-height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    z-index: 100;
    transition: transform 0.3s ease;
    overflow-y: auto;
}
.sidebar .brand {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.sidebar .brand h1 {
    color: white;
    font-size: 1.3rem;
    font-family: 'Poppins', sans-serif;
}
.sidebar .brand span { color: #D4AF37; }
.sidebar .brand p { color: #9CA3AF; }

.sidebar .nav-link {
    color: #9CA3AF;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    text-decoration: none;
    font-size: 0.9rem;
}
.sidebar .nav-link:hover,
.sidebar .nav-link.active {
    color: white;
    background: rgba(255,255,255,0.05);
    border-left-color: #D4AF37;
}
.sidebar .nav-link i { width: 20px; }
.sidebar .badge {
    background: #EF4444;
    color: white;
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 10px;
    margin-left: auto;
}
.sidebar .badge-red { background: #EF4444; }

.main-content {
    margin-left: 260px;
    min-height: 100vh;
    background: #F9FAFB;
}
.main-content .topbar {
    background: white;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #E5E7EB;
    position: sticky;
    top: 0;
    z-index: 50;
}
.main-content .content { padding: 2rem; }

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .main-content { margin-left: 0; }
    .sidebar-toggle { display: block !important; }
}
.sidebar-toggle { display: none; }
</style>