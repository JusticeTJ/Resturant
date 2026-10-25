<?php
// ===== Hungry Man's Simple Chatbot - No Database Dependency =====

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// CORS headers for production
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Please send a chat message.']);
    exit;
}

// Get request data
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$message = trim($data['message'] ?? '');

if ($message === '') {
    echo json_encode(['reply' => 'Please type a message.']);
    exit;
}

// Simple response logic
$messageLower = strtolower($message);

if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/', $messageLower)) {
    $reply = "Hi! Welcome to Hungry Man's. How can I help you?";
}
elseif (preg_match('/\b(menu|food|dish|dishes|meal|meals|eat|price|prices)\b/', $messageLower)) {
    $reply = "Our menu includes Prime Ribeye (₦42,000), Grilled Salmon (₦31,000), Lobster Pasta (₦35,000), Chicken Shawarma (₦22,000), Truffle Fries (₦12,000), and Chocolate Tart (₦14,000).";
}
elseif (preg_match('/\b(hour|hours|open|opening|close|closing|time)\b/', $messageLower)) {
    $reply = "Our hours are: Mon-Thu: 11 AM-10 PM, Fri-Sat: 11 AM-11 PM, Sun: 10 AM-9 PM.";
}
elseif (preg_match('/\b(reserve|reservation|reservations|book|booking|table)\b/', $messageLower)) {
    $reply = "You can make a reservation from the Reservations section of our website.";
}
elseif (preg_match('/\b(location|where|address|located|direction|directions)\b/', $messageLower)) {
    $reply = "We're located at 123 Gourmet Street, Downtown District, Restaurant Quarter.";
}
elseif (preg_match('/\b(contact|phone|call|number)\b/', $messageLower)) {
    $reply = "Contact us at +234 000 000 0000 or hello@hungrymans.com";
}
elseif (preg_match('/\b(order|delivery|takeaway|take out)\b/', $messageLower)) {
    $reply = "You can place orders from our menu. Sign in to your account to track your orders.";
}
elseif (preg_match('/\b(payment|pay|transfer|card|cash)\b/', $messageLower)) {
    $reply = "We accept bank transfers for online orders. Cash and card payments available at the restaurant.";
}
elseif (preg_match('/\b(about|story|who are you|tell me about)\b/', $messageLower)) {
    $reply = "Hungry Man's is a premium restaurant dedicated to exceptional dining experiences with good food and great vibes!";
}
else {
    $reply = "I can help with menu, reservations, hours, location, contact, orders, or payment info. What would you like to know?";
}

// Log for debugging (comment out in production if needed)
error_log("Chatbot request: " . $message . " - Reply: " . $reply);

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
