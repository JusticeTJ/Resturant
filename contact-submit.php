<?php
require __DIR__ . '/session-config.php';

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$respond = static function (int $status, bool $success, string $message) use ($wantsJson): never {
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    header('Location: contact-page.php?result=' . ($success ? 'sent' : 'error') . '#message-form');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $respond(405, false, 'Please submit the contact form.');
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['contact_csrf'] ?? '', $csrfToken)) {
    $respond(403, false, 'Your form session expired. Please refresh and try again.');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? 'Website message'));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
    $respond(422, false, 'Enter your name, a valid email, a subject, and a message.');
}

$_SESSION['contact_csrf'] = bin2hex(random_bytes(32));

$conn = require __DIR__ . '/connection.php';
$stmt = $conn->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $name, $email, $subject, $message);
if (!$stmt->execute()) {
    $respond(500, false, 'Your message could not be saved. Please try again.');
}

$respond(201, true, 'Thanks for your message. The restaurant team will get back to you.');