<?php
require __DIR__ . '/session-config.php';
$errors = [];
$success = '';

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $csrfToken)) {
        $errors[] = 'Your session expired. Please try again.';
    } else {
        $conn = require __DIR__ . '/connection.php';

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($firstName === '') $errors[] = 'First name is required.';
        if ($lastName === '') $errors[] = 'Last name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password is too weak. Use at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character.';
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password is too weak. Use at least 8 characters with an uppercase letter, a lowercase letter, a number, and a special character.';
        }
        if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $check = $conn->prepare('SELECT id FROM admin_users WHERE email = ? LIMIT 1');
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = 'This email is already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('INSERT INTO admin_users (first_name, last_name, email, password) VALUES (?, ?, ?, ?)');
                $stmt->bind_param('ssss', $firstName, $lastName, $email, $hash);

                if ($stmt->execute()) {
                    session_regenerate_id(true);
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $conn->insert_id;
                    $_SESSION['admin_name'] = $firstName . ' ' . $lastName;
                    $_SESSION['admin_email'] = $email;
                    header('Location: admin-dashboard.php');
                    exit;
                } else {
                    $errors[] = 'Unable to create admin account. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Signup | Hungry Man's</title>
    <style>
        :root {
            --bg: #0b0b0d;
            --panel: #121314;
            --gold: #f7b900;
            --gold-soft: #ffd867;
            --text: #f5f5f5;
            --muted: #d0d0d0;
            --line: rgba(255,255,255,.08);
            --danger: #ff6b6b;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            background: linear-gradient(135deg, #090909, #141414 55%, #0b0b0b); color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }
        .wrap { width: min(540px, 92%); }
        .card {
            background: rgba(18,19,20,.96); border: 1px solid var(--line); border-radius: 22px;
            box-shadow: 0 28px 65px rgba(0,0,0,.42); padding: 30px 28px;
        }
        .brand { text-align: center; margin-bottom: 18px; }
        .brand img { width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 10px; }
        h1 { margin: 0; text-align: center; font-size: 2rem; }
        .sub { text-align: center; color: var(--muted); margin: 8px 0 22px; }
        .alert {
            background: rgba(255, 107, 107, 0.08); border: 1px solid rgba(255, 107, 107, 0.25);
            border-radius: 12px; padding: 12px 13px; margin-bottom: 18px; color: #ffd5d5;
        }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        label { display: block; margin: 0 0 8px; font-weight: 700; color: var(--muted); }
        input {
            width: 100%; border: 1px solid var(--line); background: rgba(255,255,255,.02); padding: 13px 14px;
            border-radius: 12px; color: var(--text); font-size: 1rem;
        }
        input:focus { outline: 2px solid rgba(247, 185, 0, 0.35); border-color: var(--gold); }
        .full { grid-column: 1 / -1; }
        button {
            width: 100%; margin-top: 18px; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            color: #111; font-weight: 900; font-size: 1rem; padding: 14px 16px; cursor: pointer;
        }
        .meta { text-align: center; margin-top: 18px; color: var(--muted); }
        .meta a { color: var(--gold-soft); text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="brand">
                <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo">
            </div>
            <h1>Create Admin</h1>
            <p class="sub">Register a new Hungry Man's admin account</p>

            <?php if (!empty($errors)): ?>
                <div class="alert"><?php echo htmlspecialchars($errors[0]); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <div class="grid">
                    <div>
                        <label for="first_name">First Name</label>
                        <input id="first_name" name="first_name" type="text" placeholder="First name" required>
                    </div>
                    <div>
                        <label for="last_name">Last Name</label>
                        <input id="last_name" name="last_name" type="text" placeholder="Last name" required>
                    </div>
                    <div class="full">
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" placeholder="admin@email.com" required>
                    </div>
                    <div>
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" placeholder="Password" required>
                    </div>
                    <div>
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" placeholder="Confirm password" required>
                    </div>
                </div>
                <button type="submit">Create Admin Account</button>
            </form>

            <div class="meta"><a href="admin-login.php">Already have an account? Login</a></div>
        </div>
    </div>
</body>
</html>
