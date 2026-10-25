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
        $identifier = trim($_POST['identifier'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($identifier === '' || $password === '') {
            $errors[] = 'Please enter your email or first name and password.';
        } else {
            $normalizedIdentifier = strtolower($identifier);
            $stmt = $conn->prepare("SELECT id, first_name, last_name, email, password FROM admin_users WHERE email = ? OR first_name = ? LIMIT 1");
            $stmt->bind_param('ss', $normalizedIdentifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                $_SESSION['admin_email'] = $admin['email'];
                header('Location: admin-dashboard.php');
                exit;
            } else {
                $errors[] = 'Invalid email/first name or password.';
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
    <title>Admin Login | Hungry Man's</title>
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
            --success: #4cd964;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            background: linear-gradient(135deg, #090909, #151515 55%, #0b0b0b); color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
        }
        .wrap { width: min(460px, 92%); }
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
        .success {
            background: rgba(76, 217, 100, 0.08); border-color: rgba(76, 217, 100, 0.25); color: #d5ffd8;
        }
        label { display: block; margin: 14px 0 8px; font-weight: 700; color: var(--muted); }
        input {
            width: 100%; border: 1px solid var(--line); background: rgba(255,255,255,.02); padding: 14px 14px;
            border-radius: 12px; color: var(--text); font-size: 1rem;
        }
        input:focus { outline: 2px solid rgba(247, 185, 0, 0.35); border-color: var(--gold); }
        button {
            width: 100%; margin-top: 22px; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--gold), var(--gold-soft));
            color: #111; font-weight: 900; font-size: 1rem; padding: 14px 16px; cursor: pointer;
        }
        .meta { text-align: center; margin-top: 18px; color: var(--muted); }
        .meta a { color: var(--gold-soft); text-decoration: none; }
        .link-row { display: flex; justify-content: space-between; margin-top: 16px; }
        .link-row a { color: var(--gold-soft); text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="brand">
                <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo">
            </div>
            <h1>Admin Login</h1>
            <p class="sub">Access the Hungry Man's management dashboard</p>

            <?php if (!empty($errors)): ?>
                <div class="alert"><?php echo htmlspecialchars($errors[0]); ?></div>
            <?php endif; ?>

            <?php if ($success !== ''): ?>
                <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                <label for="identifier">Email or First Name</label>
                <input id="identifier" name="identifier" type="text" placeholder="Enter your email or first name" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Enter your password" required>

                <button type="submit">Login</button>
            </form>

            <div class="link-row">
                <a href="index.php">Back to website</a>
                <a href="admin-signup.php">Create account</a>
            </div>
        </div>
    </div>
</body>
</html>
