<?php
// ethioareb/includes/auth.php - Authentication Check
if (!isset($_SESSION['admin_id'])) {
    header('Location: index');
    exit();
}

// Check if user is active
$conn = getDB();
$stmt = $conn->prepare("SELECT is_active, role FROM admins WHERE id = ?");
$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || $user['is_active'] != 1) {
    session_destroy();
    header('Location: index');
    exit();
}

// Store role in session for permission checks
$_SESSION['admin_role'] = $user['role'];
?>