<?php
require __DIR__ . '/session-config.php';
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['orders' => []]);
    exit;
}

$conn = require __DIR__ . '/connection.php';
$userId = (int)$_SESSION['user_id'];
$stmt = $conn->prepare('SELECT id, order_number, status, payment_status FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10');
$stmt->bind_param('i', $userId);
$stmt->execute();
echo json_encode(['orders' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);