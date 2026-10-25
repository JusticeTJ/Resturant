<?php
require __DIR__ . '/session-config.php';
header('Content-Type: application/json; charset=utf-8');

function order_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    order_response(405, ['success' => false, 'message' => 'Orders must be submitted with POST.']);
}
if (empty($_SESSION['user_id'])) {
    order_response(401, ['success' => false, 'message' => 'Sign in before placing an order.']);
}
if (empty($_SESSION['order_csrf']) || !hash_equals($_SESSION['order_csrf'], $_POST['csrf_token'] ?? '')) {
    order_response(403, ['success' => false, 'message' => 'Your session expired. Refresh and try again.']);
}

$conn = require __DIR__ . '/connection.php';
$userId = (int)$_SESSION['user_id'];
$userStmt = $conn->prepare("SELECT first_name, last_name, email, status FROM users WHERE id = ? LIMIT 1");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$customer = $userStmt->get_result()->fetch_assoc();
if (!$customer || $customer['status'] !== 'active') {
    order_response(403, ['success' => false, 'message' => 'This account cannot place orders.']);
}
$action = $_POST['action'] ?? '';

if ($action === 'create_order') {
    $requestedItems = json_decode($_POST['items'] ?? '', true);
    if (!is_array($requestedItems) || count($requestedItems) < 1 || count($requestedItems) > 20) {
        order_response(422, ['success' => false, 'message' => 'Your cart is empty or has too many items.']);
    }

    $quantities = [];
    foreach ($requestedItems as $item) {
        $itemId = (int)($item['id'] ?? 0);
        $quantity = (int)($item['quantity'] ?? 0);
        if ($itemId < 1 || $quantity < 1 || $quantity > 20) {
            order_response(422, ['success' => false, 'message' => 'One or more cart quantities are invalid.']);
        }
        $quantities[$itemId] = ($quantities[$itemId] ?? 0) + $quantity;
        if ($quantities[$itemId] > 20) {
            order_response(422, ['success' => false, 'message' => 'An item quantity cannot exceed 20.']);
        }
    }

    $menuStmt = $conn->prepare('SELECT id, name, price FROM menu_items WHERE id = ? AND is_available = 1 LIMIT 1');
    $lineItems = [];
    $total = 0;
    foreach ($quantities as $itemId => $quantity) {
        $menuStmt->bind_param('i', $itemId);
        $menuStmt->execute();
        $menuItem = $menuStmt->get_result()->fetch_assoc();
        if (!$menuItem) {
            order_response(422, ['success' => false, 'message' => 'A menu item in your cart is no longer available. Refresh the menu and try again.']);
        }
        $unitPrice = (float)$menuItem['price'];
        $total += $unitPrice * $quantity;
        $lineItems[] = [
            'id' => (int)$menuItem['id'],
            'name' => $menuItem['name'],
            'price' => $unitPrice,
            'quantity' => $quantity,
        ];
    }

    $customerName = $customer['first_name'] . ' ' . $customer['last_name'];
    $summaryParts = array_map(static fn($item) => $item['quantity'] . ' x ' . $item['name'], $lineItems);
    $itemSummary = substr(implode(', ', $summaryParts), 0, 250);
    $orderNumber = 'HM-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $conn->begin_transaction();
    try {
        $insertOrder = $conn->prepare("INSERT INTO orders (order_number, customer_id, customer_name, item_name, total_amount, status, payment_status) VALUES (?, ?, ?, ?, ?, 'Pending', 'not_sent')");
        $insertOrder->bind_param('sissd', $orderNumber, $userId, $customerName, $itemSummary, $total);
        $insertOrder->execute();
        $orderId = (int)$conn->insert_id;

        $insertLine = $conn->prepare('INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, quantity) VALUES (?, ?, ?, ?, ?)');
        foreach ($lineItems as $item) {
            $insertLine->bind_param('iisdi', $orderId, $item['id'], $item['name'], $item['price'], $item['quantity']);
            $insertLine->execute();
        }
        $conn->commit();
    } catch (Throwable $error) {
        $conn->rollback();
        order_response(500, ['success' => false, 'message' => 'The order could not be saved. Please try again.']);
    }

    order_response(201, ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber, 'total' => $total, 'message' => 'Order placed. Transfer to the account shown to continue.']);
}

if ($action === 'transfer_sent') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $orderStmt = $conn->prepare('SELECT order_number, payment_status FROM orders WHERE id = ? AND customer_id = ? LIMIT 1');
    $orderStmt->bind_param('ii', $orderId, $userId);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    if (!$order) {
        order_response(404, ['success' => false, 'message' => 'Order not found.']);
    }
    if ($order['payment_status'] === 'confirmed') {
        order_response(409, ['success' => false, 'message' => 'This transfer has already been confirmed.']);
    }
    if ($order['payment_status'] === 'submitted') {
        order_response(200, ['success' => true, 'message' => 'Your transfer is already waiting for admin review.']);
    }

    $update = $conn->prepare("UPDATE orders SET payment_status = 'submitted', payment_submitted_at = NOW() WHERE id = ? AND customer_id = ? AND payment_status IN ('not_sent', 'rejected')");
    $update->bind_param('ii', $orderId, $userId);
    $update->execute();
    if ($update->affected_rows !== 1) {
        order_response(409, ['success' => false, 'message' => 'This order is not ready for transfer review.']);
    }

    $notificationText = 'Customer marked transfer sent for order ' . $order['order_number'] . '. Confirm or reject the payment.';
    $notification = $conn->prepare("INSERT INTO admin_notifications (type, message) VALUES ('payment_submitted', ?)");
    $notification->bind_param('s', $notificationText);
    $notification->execute();
    order_response(200, ['success' => true, 'message' => 'Transfer marked as sent. It will show as confirmed after admin verification.']);
}

order_response(400, ['success' => false, 'message' => 'Unknown order action.']);