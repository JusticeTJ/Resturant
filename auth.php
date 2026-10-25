<?php
require __DIR__ . '/session-config.php';
header('Content-Type: application/json; charset=utf-8');

function respond(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'logout') {
    unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_first_name'], $_SESSION['user_last_name']);
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['success' => false, 'message' => 'Method not allowed.']);
}

if (empty($_SESSION['auth_csrf'])) {
    $_SESSION['auth_csrf'] = bin2hex(random_bytes(32));
}

$action = $_POST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';
if ($action !== '' && !hash_equals($_SESSION['auth_csrf'], $csrfToken)) {
    respond(419, ['success' => false, 'message' => 'Your session expired. Please refresh the page and try again.']);
}
$returnTo = ($_POST['return_to'] ?? '') === 'reservations.php' ? 'reservations.php' : 'index.php';
$conn = require __DIR__ . '/connection.php';

if ($action === 'signup') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['success' => false, 'message' => 'Enter your first name, last name, and a valid email address.']);
    }

    $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->bind_param('s', $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        respond(409, ['success' => false, 'message' => 'That email address is already in use.']);
    }

    if (strlen($password) < 8) {
        respond(422, ['success' => false, 'message' => 'Password is too weak. Use at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character.']);
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
        respond(422, ['success' => false, 'message' => 'Password is too weak. Use at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character.']);
    }
    if ($password !== $confirmPassword) {
        respond(422, ['success' => false, 'message' => 'Passwords do not match.']);
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $insert = $conn->prepare('INSERT INTO users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)');
    $insert->bind_param('ssss', $firstName, $lastName, $email, $passwordHash);
    if (!$insert->execute()) {
        respond(500, ['success' => false, 'message' => 'Account creation failed. Please try again.']);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $conn->insert_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_first_name'] = $firstName;
    $_SESSION['user_last_name'] = $lastName;

    $notificationMessage = $firstName . ' ' . $lastName . ' created an account.';
    $notification = $conn->prepare("INSERT INTO admin_notifications (type, message) VALUES ('account_created', ?)");
    $notification->bind_param('s', $notificationMessage);
    $notification->execute();
    respond(201, ['success' => true, 'message' => 'Account created successfully.', 'redirect' => $returnTo]);
}

if ($action === 'login') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT id, first_name, last_name, email, password, status FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password'])) {
        respond(401, ['success' => false, 'message' => 'Email or password is incorrect.']);
    }
    if ($user['status'] === 'blocked') {
        respond(403, ['success' => false, 'message' => 'This account has been blocked. Please contact the restaurant.']);
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_first_name'] = $user['first_name'];
    $_SESSION['user_last_name'] = $user['last_name'];
    respond(200, ['success' => true, 'message' => 'Signed in successfully.', 'redirect' => $returnTo]);
}

respond(400, ['success' => false, 'message' => 'Unknown authentication request.']);
