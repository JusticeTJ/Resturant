<?php
require __DIR__ . '/session-config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
$statuses = ['Pending', 'Confirmed', 'Preparing', 'Ready', 'Delivering', 'Delivered', 'Cancelled'];

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $orderId = (int)($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $paymentAction = $_POST['payment_action'] ?? '';

    if (!hash_equals($_SESSION['admin_csrf'], $token)) {
        $_SESSION['order_message'] = ['type' => 'error', 'text' => 'Your session expired. Please try the update again.'];
    } elseif ($orderId > 0 && in_array($paymentAction, ['confirm', 'reject'], true)) {
        $paymentStatus = $paymentAction === 'confirm' ? 'confirmed' : 'rejected';
        $nextStatus = $paymentAction === 'confirm' ? 'Confirmed' : 'Pending';
        $stmt = $conn->prepare("UPDATE orders SET payment_status = ?, status = ? WHERE id = ? AND payment_status IN ('submitted', 'rejected')");
        $stmt->bind_param('ssi', $paymentStatus, $nextStatus, $orderId);
        if ($stmt->execute() && $stmt->affected_rows === 1) {
            $_SESSION['order_message'] = ['type' => 'success', 'text' => $paymentAction === 'confirm' ? 'Transfer confirmed. The order can now be prepared.' : 'Transfer marked as not confirmed. You can verify it later if it arrives.'];
        } else {
            $_SESSION['order_message'] = ['type' => 'error', 'text' => 'Could not update this transfer.'];
        }
    } elseif ($orderId < 1 || !in_array($status, $statuses, true)) {
        $_SESSION['order_message'] = ['type' => 'error', 'text' => 'Choose a valid order status.'];
    } else {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND (payment_status = 'confirmed' OR customer_id IS NULL)");
        $stmt->bind_param('si', $status, $orderId);
        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            $_SESSION['order_message'] = ['type' => 'success', 'text' => 'Order status updated.'];
        } else {
            $_SESSION['order_message'] = ['type' => 'error', 'text' => 'Confirm the transfer before updating fulfillment status.'];
        }
    }

    header('Location: admin-orders.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$validStatusFilter = in_array($statusFilter, $statuses, true) ? $statusFilter : '';
$allResult = $conn->query('SELECT id, order_number, customer_id, customer_name, item_name, total_amount, status, payment_status, payment_submitted_at, created_at FROM orders ORDER BY created_at DESC, id DESC');
$allOrders = $allResult ? $allResult->fetch_all(MYSQLI_ASSOC) : [];
$orders = array_values(array_filter($allOrders, static function ($order) use ($search, $validStatusFilter) {
    $matchesSearch = $search === '' || stripos($order['order_number'] . ' ' . $order['customer_name'] . ' ' . $order['item_name'], $search) !== false;
    $matchesStatus = $validStatusFilter === '' || $order['status'] === $validStatusFilter;
    return $matchesSearch && $matchesStatus;
}));
$paidOrders = array_filter($allOrders, static fn($order) => $order['payment_status'] === 'confirmed');
$totalRevenue = array_sum(array_column($paidOrders, 'total_amount'));
$activeOrders = count(array_filter($allOrders, static fn($order) => in_array($order['status'], ['Confirmed', 'Preparing', 'Ready', 'Delivering'], true)));
$deliveredOrders = count(array_filter($allOrders, static fn($order) => $order['status'] === 'Delivered'));
$flash = $_SESSION['order_message'] ?? null;
unset($_SESSION['order_message']);
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders | Hungry Man's Admin</title>
    <style>
        :root { --bg:#0b0b0c; --panel:#141618; --border:rgba(255,255,255,.09); --muted:#a8adb7; --text:#f5f5f5; --gold:#f7b900; --gold-soft:#ffd96a; --green:#38d39f; --red:#ff6b6b; --amber:#ffb454; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; color:var(--text); background:linear-gradient(180deg,#090909,#101214); font-family:Arial,Helvetica,sans-serif; }
        a { color:inherit; text-decoration:none; }
        .shell { display:flex; min-height:100vh; }
        .sidebar { width:250px; flex:none; padding:26px 18px; background:rgba(15,16,18,.96); border-right:1px solid var(--border); }
        .brand { display:flex; align-items:center; gap:12px; margin:0 0 32px; padding:8px 10px; font-weight:900; }
        .brand-mark { display:grid; place-items:center; width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,var(--gold),#f8d76d); color:#111; font-size:1.2rem; }
        .nav { display:grid; gap:8px; }
        .nav a { display:flex; align-items:center; gap:12px; padding:12px; border:1px solid transparent; border-radius:10px; color:var(--muted); font-weight:700; }
        .nav a:hover,.nav a.active { border-color:rgba(247,185,0,.22); background:rgba(247,185,0,.12); color:var(--text); }
        .nav-icon { display:grid; place-items:center; width:28px; height:28px; border-radius:8px; background:rgba(255,255,255,.05); color:var(--gold-soft); }
        main { flex:1; min-width:0; padding:30px clamp(18px,3vw,42px); }
        .topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; margin-bottom:25px; }
        h1 { margin:0; font-size:clamp(1.8rem,3vw,2.4rem); }
        .sub { margin:7px 0 0; color:var(--muted); }
        .logout { padding:10px 14px; border:1px solid var(--border); border-radius:9px; color:var(--text); font-weight:700; }
        .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; margin-bottom:20px; }
        .stat { padding:17px 18px; border:1px solid var(--border); border-radius:12px; background:rgba(255,255,255,.025); }
        .stat span { display:block; color:var(--muted); font-size:.76rem; font-weight:700; text-transform:uppercase; }
        .stat strong { display:block; margin-top:9px; font-size:1.45rem; }
        .panel { overflow:hidden; border:1px solid var(--border); border-radius:13px; background:rgba(20,22,24,.96); }
        .panel-head { display:flex; align-items:center; justify-content:space-between; gap:15px; padding:17px 20px; border-bottom:1px solid var(--border); }
        .panel-head h2 { margin:0; font-size:1.05rem; }
        .filters { display:flex; flex-wrap:wrap; gap:9px; padding:16px 20px; }
        .filters input,.filters select,.status-select { min-height:40px; padding:8px 11px; border:1px solid var(--border); border-radius:8px; background:#101214; color:var(--text); font:inherit; }
        .filters input { flex:1 1 230px; }
        .filters select { min-width:145px; }
        .button { min-height:40px; padding:9px 14px; border:0; border-radius:8px; background:var(--gold); color:#14100a; font-weight:800; cursor:pointer; }
        .button:hover { background:var(--gold-soft); }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:850px; }
        th,td { padding:13px 15px; border-top:1px solid var(--border); text-align:left; vertical-align:middle; }
        th { color:var(--muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.08em; }
        td { font-size:.9rem; }
        .order-number { color:var(--gold-soft); font-weight:800; }
        .amount { white-space:nowrap; font-weight:800; }
        .status-form { display:flex; gap:7px; align-items:center; }
        .status-select { min-width:130px; }
        .status { display:inline-flex; padding:6px 9px; border-radius:999px; font-size:.72rem; font-weight:800; }
        .status-pending { color:var(--red); background:rgba(255,107,107,.1); }
        .status-preparing { color:var(--amber); background:rgba(255,180,84,.1); }
        .status-ready,.status-delivered { color:var(--green); background:rgba(56,211,159,.1); }
        .status-confirmed { color:var(--green); background:rgba(56,211,159,.1); }
        .status-delivering { color:#9bb5ff; background:rgba(110,140,255,.12); }
        .status-cancelled { color:#d0d3d9; background:rgba(208,211,217,.1); }
        .status-not_sent { color:#d0d3d9; background:rgba(208,211,217,.1); }
        .status-submitted { color:var(--amber); background:rgba(255,180,84,.1); }
        .status-rejected { color:var(--red); background:rgba(255,107,107,.1); }
        .flash { margin:16px 20px 0; padding:11px 13px; border-radius:8px; font-weight:700; }
        .flash.success { color:#c8f7df; background:rgba(56,211,159,.1); border:1px solid rgba(56,211,159,.25); }
        .flash.error { color:#ffd5d5; background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.25); }
        .empty { padding:28px 15px; color:var(--muted); text-align:center; }
        .admin-toast { position:fixed; right:22px; bottom:22px; z-index:10000; width:min(380px,calc(100vw - 28px)); padding:15px 17px; border:1px solid rgba(247,185,0,.45); border-left:5px solid var(--gold); border-radius:11px; background:#1a1914; box-shadow:0 18px 45px rgba(0,0,0,.45); opacity:0; transform:translateY(12px); pointer-events:none; transition:opacity .2s,transform .2s; }
        .admin-toast.visible { opacity:1; transform:translateY(0); }
        .admin-toast strong { display:block; margin-bottom:5px; color:var(--gold-soft); }
        @media(max-width:850px) { .sidebar { width:205px; } .stats { grid-template-columns:1fr; } }
        @media(max-width:650px) { .shell { display:block; } .sidebar { width:100%; padding:14px; border-right:0; border-bottom:1px solid var(--border); } .brand { margin-bottom:12px; } .nav { grid-template-columns:repeat(2,minmax(0,1fr)); } main { padding:22px 14px; } .topbar { align-items:center; } .topbar h1 { font-size:1.6rem; } }
    </style>
</head>
<body>
    <div id="adminToast" class="admin-toast" role="status" aria-live="polite"></div>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand"><span class="brand-mark">H</span><span>Hungry Man's</span></div>
            <nav class="nav" aria-label="Admin navigation">
                <a href="admin-dashboard.php"><span class="nav-icon">◉</span>Overview</a>
                <a href="admin-orders.php" class="active"><span class="nav-icon">▣</span>Orders</a>
                <a href="admin-menu.php"><span class="nav-icon">☰</span>Menu</a>
                <a href="admin-reservations.php"><span class="nav-icon">✦</span>Reservations</a>
                <a href="admin-customers.php"><span class="nav-icon">👥</span>Customers</a>
                <a href="admin-messages.php"><span class="nav-icon">✉</span>Messages</a>
                <a href="admin-settings.php"><span class="nav-icon">⚙</span>Settings</a>
            </nav>
        </aside>
        <main>
            <div class="topbar">
                <div><h1>Orders</h1><p class="sub">Manage order progress, <?php echo htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8'); ?>.</p></div>
                <a class="logout" href="admin-logout.php">Log out</a>
            </div>

            <section class="stats" aria-label="Order summary">
                <article class="stat"><span>Total orders</span><strong><?php echo count($allOrders); ?></strong></article>
                <article class="stat"><span>In progress</span><strong><?php echo $activeOrders; ?></strong></article>
                <article class="stat"><span>Total order value</span><strong>₦<?php echo number_format((float)$totalRevenue); ?></strong></article>
            </section>

            <section class="panel">
                <div class="panel-head"><h2>Order list</h2><span class="sub"><?php echo count($orders); ?> shown · <?php echo $deliveredOrders; ?> delivered</span></div>
                <?php if ($flash): ?>
                    <div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <form class="filters" method="GET" action="admin-orders.php">
                    <input type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search order, customer, or item" aria-label="Search orders">
                    <select name="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <?php foreach ($statuses as $option): ?>
                            <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $validStatusFilter === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="button" type="submit">Filter orders</button>
                </form>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Order</th><th>Customer</th><th>Item</th><th>Total</th><th>Placed</th><th>Payment</th><th>Fulfillment</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (!$orders): ?>
                                <tr><td colspan="8" class="empty">No orders match these filters.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td class="order-number"><?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($order['item_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="amount">₦<?php echo number_format((float)$order['total_amount']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($order['created_at'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="status status-<?php echo htmlspecialchars($order['payment_status'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $order['payment_status'])), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($order['payment_submitted_at']): ?><br><small class="sub">Claimed <?php echo htmlspecialchars(date('M j, g:i A', strtotime($order['payment_submitted_at'])), ENT_QUOTES, 'UTF-8'); ?></small><?php endif; ?>
                                    </td>
                                    <td><span class="status status-<?php echo strtolower(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td>
                                        <form class="status-form" method="POST" action="admin-orders.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="order_id" value="<?php echo (int)$order['id']; ?>">
                                            <select class="status-select" name="status" aria-label="New status for <?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($order['payment_status'] !== 'confirmed' && $order['customer_id'] !== null) ? 'disabled' : ''; ?>>
                                                <?php foreach ($statuses as $option): ?>
                                                    <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $order['status'] === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($order['payment_status'] === 'confirmed' || $order['customer_id'] === null): ?>
                                                <button class="button" type="submit">Save</button>
                                            <?php elseif ($order['payment_status'] === 'submitted'): ?>
                                                <button class="button" type="submit" name="payment_action" value="confirm">Yes</button>
                                                <button class="button" type="submit" name="payment_action" value="reject">No</button>
                                            <?php elseif ($order['payment_status'] === 'rejected'): ?>
                                                <button class="button" type="submit" name="payment_action" value="confirm">Confirm delayed transfer</button>
                                            <?php else: ?>
                                                <span class="sub">Waiting for customer</span>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
    <script>
        const adminCsrfToken = <?php echo json_encode($_SESSION['admin_csrf']); ?>;
        const adminToast = document.getElementById('adminToast');
        let adminToastTimer;
        async function checkAdminNotifications() {
            try {
                const response = await fetch('admin-notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ csrf_token: adminCsrfToken }),
                    cache: 'no-store'
                });
                const data = await response.json();
                if (data.notifications && data.notifications.length) {
                    adminToast.replaceChildren();
                    const heading = document.createElement('strong');
                    heading.textContent = data.notifications.some(item => item.type === 'payment_submitted') ? 'Transfer awaiting verification' : 'New account notification';
                    adminToast.append(heading, document.createTextNode(data.notifications.map(item => item.message).join(' ')));
                    adminToast.classList.add('visible');
                    clearTimeout(adminToastTimer);
                    adminToastTimer = setTimeout(() => adminToast.classList.remove('visible'), 7000);
                }
            } catch (error) {
            }
        }
        checkAdminNotifications();
        setInterval(checkAdminNotifications, 12000);
    </script>
</body>
</html>