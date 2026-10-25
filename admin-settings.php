<?php
require __DIR__ . '/session-config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}
$adminId = (int)($_SESSION['admin_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    $flash = ['type' => 'error', 'text' => 'Your session expired. Please try again.'];

    if (hash_equals($_SESSION['admin_csrf'], $token) && $action === 'profile') {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($adminId > 0 && $firstName !== '' && $lastName !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = $conn->prepare('UPDATE admin_users SET first_name = ?, last_name = ?, email = ? WHERE id = ?');
            $stmt->bind_param('sssi', $firstName, $lastName, $email, $adminId);
            if ($stmt->execute()) {
                $_SESSION['admin_name'] = $firstName . ' ' . $lastName;
                $_SESSION['admin_email'] = $email;
                $flash = ['type' => 'success', 'text' => 'Admin profile updated.'];
            } else {
                $flash = ['type' => 'error', 'text' => 'Could not save this profile. The email may already belong to another admin.'];
            }
        } else {
            $flash = ['type' => 'error', 'text' => 'Enter your first name, last name, and a valid email address.'];
        }
    } elseif (hash_equals($_SESSION['admin_csrf'], $token) && $action === 'restaurant') {
        $settings = [
            'restaurant_name' => trim($_POST['restaurant_name'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? ''),
            'contact_phone' => trim($_POST['contact_phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'opening_hours' => trim($_POST['opening_hours'] ?? ''),
        ];
        if ($settings['restaurant_name'] === '' || !filter_var($settings['contact_email'], FILTER_VALIDATE_EMAIL) || $settings['contact_phone'] === '' || $settings['address'] === '' || $settings['opening_hours'] === '') {
            $flash = ['type' => 'error', 'text' => 'Complete all restaurant details and enter a valid contact email.'];
        } else {
            $stmt = $conn->prepare('INSERT INTO restaurant_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
            foreach ($settings as $key => $value) {
                $stmt->bind_param('ss', $key, $value);
                $stmt->execute();
            }
            $flash = ['type' => 'success', 'text' => 'Restaurant settings saved.'];
        }
    } elseif (hash_equals($_SESSION['admin_csrf'], $token) && $action === 'password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $stmt = $conn->prepare('SELECT password FROM admin_users WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        if (!$admin || !password_verify($currentPassword, $admin['password'])) {
            $flash = ['type' => 'error', 'text' => 'Current password is incorrect.'];
        } elseif (strlen($newPassword) < 10 || !preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^a-zA-Z0-9]/', $newPassword)) {
            $flash = ['type' => 'error', 'text' => 'New password must be 10+ characters with uppercase, lowercase, number, and symbol.'];
        } elseif ($newPassword !== $confirmPassword) {
            $flash = ['type' => 'error', 'text' => 'New password and confirmation do not match.'];
        } else {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $update = $conn->prepare('UPDATE admin_users SET password = ? WHERE id = ?');
            $update->bind_param('si', $hash, $adminId);
            if ($update->execute()) {
                $flash = ['type' => 'success', 'text' => 'Admin password changed.'];
            }
        }
    }

    $_SESSION['settings_flash'] = $flash;
    header('Location: admin-settings.php');
    exit;
}

$profileStmt = $conn->prepare('SELECT first_name, last_name, email FROM admin_users WHERE id = ? LIMIT 1');
$profileStmt->bind_param('i', $adminId);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc() ?: ['first_name' => '', 'last_name' => '', 'email' => ''];
$settingResult = $conn->query('SELECT setting_key, setting_value FROM restaurant_settings');
$settings = [];
if ($settingResult) {
    foreach ($settingResult->fetch_all(MYSQLI_ASSOC) as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
}
$flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);
require __DIR__ . '/admin-layout.php';
admin_page_start('Settings', 'settings');
?>
<?php if ($flash): ?><div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

<section class="panel">
    <div class="panel-head"><h2>Admin profile</h2><span class="sub">Used for dashboard access</span></div>
    <div class="panel-body">
        <form class="form-grid" method="POST" action="admin-settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="profile">
            <div class="field"><label for="admin-first">First name</label><input id="admin-first" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="admin-last">Last name</label><input id="admin-last" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field full"><label for="admin-email">Email</label><input id="admin-email" name="email" type="email" value="<?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field full form-actions"><button class="button" type="submit">Save profile</button></div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Restaurant details</h2><span class="sub">Contact information and opening hours</span></div>
    <div class="panel-body">
        <form class="form-grid" method="POST" action="admin-settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="restaurant">
            <div class="field"><label for="restaurant-name">Restaurant name</label><input id="restaurant-name" name="restaurant_name" value="<?php echo htmlspecialchars($settings['restaurant_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="restaurant-email">Contact email</label><input id="restaurant-email" name="contact_email" type="email" value="<?php echo htmlspecialchars($settings['contact_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="restaurant-phone">Contact phone</label><input id="restaurant-phone" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field"><label for="restaurant-address">Address</label><input id="restaurant-address" name="address" value="<?php echo htmlspecialchars($settings['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required></div>
            <div class="field full"><label for="restaurant-hours">Opening hours</label><textarea id="restaurant-hours" name="opening_hours" required><?php echo htmlspecialchars($settings['opening_hours'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea></div>
            <div class="field full form-actions"><button class="button" type="submit">Save restaurant details</button></div>
        </form>
    </div>
</section>

<section class="panel">
    <div class="panel-head"><h2>Change admin password</h2><span class="sub">Your current password is required</span></div>
    <div class="panel-body">
        <form class="form-grid" method="POST" action="admin-settings.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="password">
            <div class="field full"><label for="current-password">Current password</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="field"><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" required></div>
            <div class="field"><label for="confirm-password">Confirm new password</label><input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" required></div>
            <div class="field full form-actions"><button class="button" type="submit">Change password</button></div>
        </form>
    </div>
</section>
<?php admin_page_end(); ?>