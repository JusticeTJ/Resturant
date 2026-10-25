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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $token) || $userId < 1 || !in_array($action, ['block', 'unblock'], true)) {
        $_SESSION['customer_flash'] = ['type' => 'error', 'text' => 'Invalid customer update. Please try again.'];
    } else {
        $status = $action === 'block' ? 'blocked' : 'active';
        $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $userId);
        $_SESSION['customer_flash'] = $stmt->execute()
            ? ['type' => 'success', 'text' => 'Customer access updated.']
            : ['type' => 'error', 'text' => 'Could not update this customer.'];
    }
    header('Location: admin-customers.php');
    exit;
}

$result = $conn->query('SELECT id, first_name, last_name, email, status, created_at FROM users ORDER BY created_at DESC');
$allUsers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$validFilter = in_array($statusFilter, ['active', 'blocked'], true) ? $statusFilter : '';
$users = array_values(array_filter($allUsers, static function ($user) use ($search, $validFilter) {
    $matchesSearch = $search === '' || stripos($user['first_name'] . ' ' . $user['last_name'] . ' ' . $user['email'], $search) !== false;
    return $matchesSearch && ($validFilter === '' || $user['status'] === $validFilter);
}));
$activeCount = count(array_filter($allUsers, static fn($user) => $user['status'] === 'active'));
$blockedCount = count($allUsers) - $activeCount;
$flash = $_SESSION['customer_flash'] ?? null;
unset($_SESSION['customer_flash']);
require __DIR__ . '/admin-layout.php';
admin_page_start('Customers', 'customers');
?>
<?php if ($flash): ?><div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<section class="stats">
    <article class="stat"><span>Registered customers</span><strong><?php echo count($allUsers); ?></strong></article>
    <article class="stat"><span>Active accounts</span><strong><?php echo $activeCount; ?></strong></article>
    <article class="stat"><span>Blocked accounts</span><strong><?php echo $blockedCount; ?></strong></article>
</section>
<section class="panel">
    <div class="panel-head"><h2>Customer accounts</h2><span class="sub"><?php echo count($users); ?> shown</span></div>
    <form class="form-grid panel-body" method="GET" action="admin-customers.php">
        <div class="field"><label for="customer-search">Search customers</label><input id="customer-search" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name or email"></div>
        <div class="field"><label for="customer-status">Account status</label><select id="customer-status" name="status"><option value="">All statuses</option><option value="active" <?php echo $validFilter === 'active' ? 'selected' : ''; ?>>Active</option><option value="blocked" <?php echo $validFilter === 'blocked' ? 'selected' : ''; ?>>Blocked</option></select></div>
        <div class="field full form-actions"><button class="button" type="submit">Filter customers</button></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Status</th><th>Access</th></tr></thead>
            <tbody>
                <?php if (!$users): ?><tr><td colspan="5" class="empty">No customer accounts match this view.</td></tr><?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($user['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="status status-<?php echo htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst($user['status']), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><form method="POST" class="form-actions"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>"><button class="button <?php echo $user['status'] === 'active' ? 'secondary' : ''; ?>" type="submit" name="action" value="<?php echo $user['status'] === 'active' ? 'block' : 'unblock'; ?>"><?php echo $user['status'] === 'active' ? 'Block access' : 'Unblock'; ?></button></form></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_page_end(); ?>