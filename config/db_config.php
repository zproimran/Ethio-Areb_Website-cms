<?php
// config/db_config.php
// Database Configuration - Working with InfinityFree

// ============================================================
// DATABASE CONFIGURATION
// ============================================================

// Check if constants are already defined to avoid errors
if (!defined('DB_HOST')) {
    define('DB_HOST', 'sql106.infinityfree.com');
    define('DB_USER', 'if0_42195021');
    define('DB_PASS', 'J5NU347p74Z');
    define('DB_NAME', 'if0_42195021_ethioarebdb');
}

// Site Configuration
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://ethioareb.site.je/');
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', SITE_URL . 'ethioareb/');
}
if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', SITE_URL . 'uploads/');
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/uploads/');
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// DATABASE CONNECTION
// ============================================================

function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }
    return $conn;
}

// ============================================================
// HELPER FUNCTIONS
// ============================================================

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function getSetting($key) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    if (!$stmt) return null;
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return null;
}

function getSettings() {
    $conn = getDB();
    $result = $conn->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    return $settings;
}

function uploadFile($file, $targetDir, $allowedTypes = ['jpg','jpeg','png','gif','svg','webp','pdf','doc','docx']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        return false;
    }
    
    // Create directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $filename = time() . '_' . uniqid() . '.' . $ext;
    $targetPath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }
    return false;
}

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

// ============================================================
// FRONTEND FUNCTIONS
// ============================================================

function getHeroSlides() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY order_no ASC");
    $slides = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $slides[] = $row;
        }
    }
    return $slides;
}

function getServices($limit = null, $category_id = null) {
    $conn = getDB();
    $sql = "SELECT s.*, sc.name as category_name 
            FROM services s 
            LEFT JOIN service_categories sc ON s.category_id = sc.id 
            WHERE s.is_active = 1";
    if ($category_id) {
        $sql .= " AND s.category_id = " . intval($category_id);
    }
    $sql .= " ORDER BY s.order_no ASC, s.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $services = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    return $services;
}

function getServiceBySlug($slug) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT s.*, sc.name as category_name 
                           FROM services s 
                           LEFT JOIN service_categories sc ON s.category_id = sc.id 
                           WHERE s.slug = ? AND s.is_active = 1");
    if (!$stmt) return null;
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getTeamMembers($limit = null) {
    $conn = getDB();
    $sql = "SELECT * FROM team_members WHERE is_active = 1 ORDER BY display_order ASC, order_no ASC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $team = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $team[] = $row;
        }
    }
    return $team;
}

function getTestimonials($limit = null) {
    $conn = getDB();
    $sql = "SELECT * FROM testimonials WHERE is_active = 1 ORDER BY order_no ASC, created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $testimonials = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $testimonials[] = $row;
        }
    }
    return $testimonials;
}

function getGalleryItems($category_id = null, $limit = null) {
    $conn = getDB();
    $sql = "SELECT gi.*, gc.name as category_name 
            FROM gallery_items gi 
            LEFT JOIN gallery_categories gc ON gi.category_id = gc.id 
            WHERE gi.is_active = 1";
    if ($category_id) {
        $sql .= " AND gi.category_id = " . intval($category_id);
    }
    $sql .= " ORDER BY gi.order_no ASC, gi.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $items = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
    return $items;
}

function getBlogPosts($limit = null, $category_id = null) {
    $conn = getDB();
    $sql = "SELECT bp.*, bc.name as category_name 
            FROM blog_posts bp 
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
            WHERE bp.status = 'published'";
    if ($category_id) {
        $sql .= " AND bp.category_id = " . intval($category_id);
    }
    $sql .= " ORDER BY bp.published_at DESC, bp.created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $posts = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    }
    return $posts;
}

function getBlogPost($slug) {
    $conn = getDB();
    $stmt = $conn->prepare("SELECT bp.*, bc.name as category_name 
                           FROM blog_posts bp 
                           LEFT JOIN blog_categories bc ON bp.category_id = bc.id 
                           WHERE bp.slug = ? AND bp.status = 'published'");
    if (!$stmt) return null;
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getFAQs() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM faqs WHERE is_active = 1 ORDER BY order_no ASC");
    $faqs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $faqs[] = $row;
        }
    }
    return $faqs;
}

function getTimeline() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM timeline WHERE is_active = 1 ORDER BY order_no ASC");
    $timeline = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
    }
    return $timeline;
}

function getGalleryCategories() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM gallery_categories WHERE is_active = 1 ORDER BY order_no ASC");
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

function getBlogCategories() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM blog_categories WHERE is_active = 1 ORDER BY order_no ASC");
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

function getServiceCategories() {
    $conn = getDB();
    $result = $conn->query("SELECT * FROM service_categories WHERE is_active = 1 ORDER BY order_no ASC");
    $categories = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    return $categories;
}

function getUnreadCount() {
    $conn = getDB();
    $result = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE is_read = 0");
    if ($result) {
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    return 0;
}

function getDashboardStats() {
    $conn = getDB();
    $stats = [];
    
    $queries = [
        'services' => "SELECT COUNT(*) as count FROM services WHERE is_active = 1",
        'team' => "SELECT COUNT(*) as count FROM team_members WHERE is_active = 1",
        'posts' => "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'",
        'testimonials' => "SELECT COUNT(*) as count FROM testimonials WHERE is_active = 1",
        'gallery' => "SELECT COUNT(*) as count FROM gallery_items WHERE is_active = 1",
        'messages' => "SELECT COUNT(*) as count FROM contact_messages",
        'applications' => "SELECT COUNT(*) as count FROM job_applications"
    ];
    
    foreach ($queries as $key => $sql) {
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats[$key] = $row['count'];
        } else {
            $stats[$key] = 0;
        }
    }
    
    $stats['unread'] = getUnreadCount();
    $stats['new_applications'] = 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM job_applications WHERE status = 'new'");
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['new_applications'] = $row['count'];
    }
    
    return $stats;
}
?>