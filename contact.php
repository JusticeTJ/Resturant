<?php
// contact.php - Handle contact form submissions
// Configure your email and database settings here

// Allow cross-origin if needed
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $name = isset($_POST['name']) ? sanitize_input($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_input($_POST['email']) : '';
    $message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit;
    }
    
    // Prepare email
    $to = 'hello@hungrymans.com'; // Change this to your email
    $subject = 'New Contact Form Submission from ' . $name;
    
    $email_body = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
    </head>
    <body>
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        <p><strong>Message:</strong></p>
        <p>$message</p>
        <hr>
        <p><small>Submitted on: " . date('Y-m-d H:i:s') . "</small></p>
    </body>
    </html>
    ";
    
    // Email headers
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . $email . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    // Send email
    if (mail($to, $subject, $email_body, $headers)) {
        
        // Optional: Save to database
        // save_to_database($name, $email, $message);
        
        // Optional: Send confirmation email to user
        // send_confirmation_email($email, $name);
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been received. We\'ll get back to you soon.'
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'errors' => ['Failed to send message. Please try again later.']
        ]);
    }
    exit;
}

// If not POST request
http_response_code(405);
echo json_encode([
    'success' => false,
    'errors' => ['Method not allowed']
]);
exit;

// Sanitize input function
function sanitize_input($input) {
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Optional: Save to database function
function save_to_database($name, $email, $message) {
    // Configure your database connection here
    // $conn = new mysqli('localhost', 'user', 'password', 'database');
    // 
    // if ($conn->connect_error) {
    //     die('Database connection failed: ' . $conn->connect_error);
    // }
    // 
    // $stmt = $conn->prepare('INSERT INTO contacts (name, email, message, created_at) VALUES (?, ?, ?, NOW())');
    // $stmt->bind_param('sss', $name, $email, $message);
    // return $stmt->execute();
}

// Optional: Send confirmation email to user
function send_confirmation_email($email, $name) {
    $subject = 'We received your message - Hungry Man\'s';
    $message = "
    <html>
    <head>
        <title>Confirmation</title>
    </head>
    <body>
        <h2>Thank you, $name!</h2>
        <p>We have received your message and will get back to you as soon as possible.</p>
        <p>Best regards,<br>The Hungry Man's Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: hello@hungrymans.com\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>
