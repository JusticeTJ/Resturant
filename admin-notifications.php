<?php
require __DIR__ . '/session-config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['notifications' => []]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['admin_csrf'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['notifications' => []]);
    exit;
}

$conn = require __DIR__ . '/connection.php';
$result = $conn->query("SELECT id, type, message, created_at FROM admin_notifications WHERE is_read = 0 ORDER BY id ASC LIMIT 10");
$notifications = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

if ($notifications) {
    $lastId = (int)end($notifications)['id'];
    $stmt = $conn->prepare('UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0 AND id <= ?');
    $stmt->bind_param('i', $lastId);
    $stmt->execute();
}

echo json_encode(['notifications' => $notifications], JSON_UNESCAPED_UNICODE);