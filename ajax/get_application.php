<?php
// ethioareb/ajax/get_application.php - Get application details
require_once '../../config/db_config.php';
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $conn = getDB();
    $stmt = $conn->prepare("SELECT * FROM job_applications WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    header('Content-Type: application/json');
    echo json_encode($data);
}
?>