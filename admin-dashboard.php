<?php
require __DIR__ . '/session-config.php';
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
$userConn = $conn;
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_action'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $action = $_POST['user_action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (hash_equals($_SESSION['admin_csrf'], $token) && $userId > 0 && in_array($action, ['block', 'unblock'], true)) {
        $status = ($action === 'block') ? 'blocked' : 'active';
        $stmt = $conn->prepare('UPDATE users SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $userId);
        $_SESSION['user_status_message'] = $stmt->execute()
            ? (($action === 'block') ? 'User blocked successfully.' : 'User unblocked successfully.')
            : 'Could not update this user.';
    } else {
        $_SESSION['user_status_message'] = 'Invalid user access request. Please try again.';
    }

    header('Location: admin-dashboard.php');
    exit;
}

$orderStatsResult = $conn->query("SELECT COUNT(*) AS total, COALESCE(SUM(CASE WHEN payment_status = 'confirmed' THEN total_amount ELSE 0 END), 0) AS sales FROM orders WHERE DATE(created_at) = CURDATE()");
$orderStats = $orderStatsResult ? $orderStatsResult->fetch_assoc() : ['total' => 0, 'sales' => 0];
$reservationStatsResult = $conn->query('SELECT COUNT(*) AS total FROM reservations');
$reservationCount = $reservationStatsResult ? (int)$reservationStatsResult->fetch_assoc()['total'] : 0;
$messageStatsResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages WHERE status = 'New'");
$newMessageCount = $messageStatsResult ? (int)$messageStatsResult->fetch_assoc()['total'] : 0;
$stats = [
    ['label' => 'Gross Sales Today', 'value' => '₦' . number_format((float)$orderStats['sales']), 'delta' => 'Today', 'trend' => 'up', 'icon' => '₦'],
    ['label' => 'Orders Today', 'value' => (string)$orderStats['total'], 'delta' => 'Today', 'trend' => 'up', 'icon' => '◫'],
    ['label' => 'Reservations', 'value' => (string)$reservationCount, 'delta' => 'All requests', 'trend' => 'up', 'icon' => '☰'],
    ['label' => 'New Messages', 'value' => (string)$newMessageCount, 'delta' => 'Unread', 'trend' => 'up', 'icon' => '✉'],
];

$orderResult = $conn->query("SELECT order_number AS id, customer_name AS customer, item_name AS items, CONCAT('₦', FORMAT(total_amount, 0)) AS total, status FROM orders ORDER BY created_at DESC LIMIT 5");
$orders = $orderResult ? $orderResult->fetch_all(MYSQLI_ASSOC) : [];

$reservationResult = $conn->query("SELECT customer_name AS name, CONCAT(DATE_FORMAT(reservation_date, '%a, %b %e'), ', ', reservation_time) AS date, CONCAT(guests, ' Guests') AS party, occasion AS `table`, status FROM reservations ORDER BY created_at DESC LIMIT 4");
$reservations = $reservationResult ? $reservationResult->fetch_all(MYSQLI_ASSOC) : [];

$menuPerformance = [
    ['name' => 'Prime Ribeye', 'sales' => '48 sold', 'revenue' => '₦1,920,000'],
    ['name' => 'Lobster Pasta', 'sales' => '36 sold', 'revenue' => '₦1,620,000'],
    ['name' => 'Grilled Salmon', 'sales' => '29 sold', 'revenue' => '₦1,160,000'],
    ['name' => 'Chicken Shawarma', 'sales' => '41 sold', 'revenue' => '₦1,050,000'],
    ['name' => 'Truffle Fries', 'sales' => '53 sold', 'revenue' => '₦795,000'],
];

$messageResult = $conn->query("SELECT name, LEFT(message, 160) AS text, DATE_FORMAT(created_at, '%b %e, %l:%i %p') AS time FROM contact_messages ORDER BY created_at DESC LIMIT 3");
$messages = $messageResult ? $messageResult->fetch_all(MYSQLI_ASSOC) : [];

$staff = [
    ['name' => 'Alicia', 'role' => 'Shift Manager', 'status' => 'On duty'],
    ['name' => 'Marcus', 'role' => 'Chef', 'status' => 'In kitchen'],
    ['name' => 'Jules', 'role' => 'Server', 'status' => 'Available'],
    ['name' => 'Rina', 'role' => 'Host', 'status' => 'Seated guests'],
];

$salesBars = [72, 58, 83, 64, 91, 76, 88];
$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

$userResult = $conn->query('SELECT id, first_name, last_name, email, status FROM users ORDER BY created_at DESC LIMIT 8');
$users = $userResult ? $userResult->fetch_all(MYSQLI_ASSOC) : [];
$userStatusMessage = $_SESSION['user_status_message'] ?? '';
unset($_SESSION['user_status_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hungry Man's | Admin Dashboard</title>
    <style>
        :root {
            --bg: #0b0b0c;
            --panel: #121214;
            --panel-alt: #17181a;
            --panel-soft: #1d1f22;
            --card: #18191b;
            --border: rgba(255,255,255,0.08);
            --muted: #a8adb7;
            --text: #f5f5f5;
            --gold: #f7b900;
            --gold-soft: #ffd96a;
            --green: #38d39f;
            --red: #ff6b6b;
            --amber: #ffb454;
            --shadow: 0 20px 45px rgba(0,0,0,0.35);
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, #090909 0%, #101214 100%);
            color: var(--text);
        }

        body { min-height: 100vh; }
        a { color: inherit; text-decoration: none; }

        .admin-shell { display: flex; min-height: 100vh; }

        .sidebar {
            width: 260px; background: rgba(15, 16, 18, 0.96); border-right: 1px solid var(--border);
            padding: 28px 18px; position: sticky; top: 0; height: 100vh;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 900; letter-spacing: 0.04em; margin-bottom: 34px; padding: 8px 10px; }
        .brand-mark { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg, var(--gold), #f8d76d); display: grid; place-items: center; color: #111; font-size: 1.2rem; font-weight: 900; }
        .brand-text { font-size: 0.98rem; }
        .nav { display: flex; flex-direction: column; gap: 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 12px; border: 1px solid transparent; padding: 12px 12px; border-radius: 12px; color: var(--muted); font-weight: 700; transition: 0.2s ease;
        }
        .nav-item:hover, .nav-item.active { background: rgba(247, 185, 0, 0.12); color: var(--text); border-color: rgba(247, 185, 0, 0.22); }
        .nav-icon { width: 28px; height: 28px; border-radius: 8px; display: grid; place-items: center; font-size: 0.9rem; background: rgba(255,255,255,0.04); color: var(--gold-soft); }

        .sidebar-footer { margin-top: 42px; border: 1px solid var(--border); border-radius: 16px; background: rgba(255,255,255,0.02); padding: 18px 14px; }
        .sidebar-footer small { display: block; color: var(--muted); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.12em; }
        .mini-progress { height: 8px; background: rgba(255,255,255,0.08); border-radius: 999px; overflow: hidden; margin-bottom: 10px; }
        .mini-progress span { display: block; width: 76%; height: 100%; background: linear-gradient(90deg, var(--gold), var(--gold-soft)); border-radius: inherit; }

        .main { flex: 1; min-width: 0; padding: 26px 28px 40px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 26px; }
        .page-title h1 { margin: 0; font-size: clamp(1.8rem, 2vw, 2.5rem); }
        .page-title p { margin: 6px 0 0; color: var(--muted); }
        .topbar-actions { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .chip, .action-btn { border: 1px solid var(--border); background: rgba(255,255,255,0.02); color: var(--text); padding: 10px 14px; border-radius: 12px; font-weight: 700; }
        .action-btn.primary { background: linear-gradient(135deg, var(--gold), #f4c846); color: #121212; border-color: transparent; box-shadow: 0 14px 30px rgba(247, 185, 0, 0.2); }

        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 18px; margin-bottom: 24px; }
        .stat-card { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border: 1px solid var(--border); border-radius: 18px; padding: 18px 18px 16px; box-shadow: var(--shadow); }
        .stat-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .icon-badge { width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; background: rgba(247, 185, 0, 0.12); color: var(--gold-soft); font-weight: 700; }
        .delta { padding: 5px 8px; border-radius: 999px; font-size: 0.72rem; font-weight: 800; }
        .delta.up { background: rgba(56, 211, 159, 0.12); color: var(--green); }
        .delta.down { background: rgba(255, 107, 107, 0.12); color: var(--red); }
        .stat-card .label { color: var(--muted); font-size: 0.82rem; letter-spacing: 0.05em; text-transform: uppercase; }
        .stat-card .value { margin-top: 10px; font-weight: 900; font-size: clamp(1.7rem, 2vw, 2.3rem); }

        .dashboard-grid { display: grid; grid-template-columns: 1.8fr 1fr; gap: 22px; }
        .panel { background: rgba(20, 22, 24, 0.96); border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--shadow); overflow: hidden; }
        .panel-header { padding: 18px 20px 14px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; gap: 12px; }
        .panel-header h3 { margin: 0; font-size: 1.05rem; }
        .panel-header .small-link { color: var(--gold-soft); font-weight: 700; font-size: 0.78rem; }
        .chart-panel { padding: 20px; }
        .chart-bars { height: 220px; display: flex; align-items: end; gap: 12px; padding-top: 10px; }
        .bar-column { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 8px; height: 100%; justify-content: end; }
        .bar { width: 100%; max-width: 36px; border-radius: 12px 12px 0 0; background: linear-gradient(180deg, var(--gold-soft), var(--gold)); box-shadow: inset 0 -8px 18px rgba(17, 17, 17, 0.2); }
        .bar-label { color: var(--muted); font-size: 0.72rem; font-weight: 700; }
        .panel-body { padding: 18px 20px 20px; }

        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--border); font-size: 0.92rem; }
        .table th { color: var(--muted); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; }

        .status-pill { display: inline-flex; padding: 6px 10px; border-radius: 999px; font-weight: 800; font-size: 0.72rem; border: 1px solid transparent; }
        .status-ready { background: rgba(56, 211, 159, 0.09); color: var(--green); border-color: rgba(56, 211, 159, 0.2); }
        .status-preparing { background: rgba(255, 180, 84, 0.09); color: var(--amber); border-color: rgba(255, 180, 84, 0.2); }
        .status-delivered { background: rgba(110, 140, 255, 0.12); color: #9bb5ff; border-color: rgba(110, 140, 255, 0.2); }
        .status-pending { background: rgba(255, 107, 107, 0.09); color: var(--red); border-color: rgba(255, 107, 107, 0.2); }
        .status-confirmed { background: rgba(56, 211, 159, 0.09); color: var(--green); border-color: rgba(56, 211, 159, 0.2); }
        .status-awaiting { background: rgba(255, 180, 84, 0.09); color: var(--amber); border-color: rgba(255, 180, 84, 0.2); }
        .status-delivering { background: rgba(110, 140, 255, 0.12); color: #9bb5ff; border-color: rgba(110, 140, 255, 0.2); }

        .stack { display: grid; gap: 22px; }
        .list { display: grid; gap: 14px; }
        .list-item { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 12px 14px; background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 14px; }
        .list-meta strong { display: block; margin-bottom: 4px; font-size: 0.96rem; }
        .list-meta span, .list-meta small { color: var(--muted); }
        .list-price { font-weight: 900; color: var(--gold-soft); }

        .message { border: 1px solid var(--border); border-radius: 14px; background: rgba(255,255,255,0.02); padding: 12px 14px; }
        .message strong { display: block; margin-bottom: 6px; }
        .message p { margin: 0; color: var(--muted); line-height: 1.5; }
        .message time { display: block; margin-top: 8px; color: #d6ba5c; font-size: 0.75rem; }
        .staff-grid { display: grid; grid-template-columns: repeat(2, minmax(120px, 1fr)); gap: 12px; }
        .staff-card { padding: 14px 12px; border: 1px solid var(--border); border-radius: 14px; background: rgba(255,255,255,0.02); }
        .staff-card .avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, rgba(247,185,0,0.25), rgba(247,185,0,0.6)); display: grid; place-items: center; font-weight: 900; color: #111; margin-bottom: 10px; }
        .staff-card h4 { margin: 0 0 4px; font-size: 0.95rem; }
        .staff-card p { margin: 0; font-size: 0.76rem; color: var(--muted); }

        .user-badge {
            display: inline-flex; align-items: center; gap: 8px; padding: 9px 12px; border-radius: 999px; background: rgba(247,185,0,0.08); border: 1px solid rgba(247,185,0,0.18); color: var(--gold-soft); font-weight: 800; margin-top: 10px;
        }

        .user-actions {
            display: flex; align-items: center; gap: 8px; justify-content: flex-end; flex-wrap: wrap;
        }

        .user-action-btn {
            padding: 8px 12px; border: 1px solid var(--border); background: rgba(255,255,255,0.02); color: var(--text);
            border-radius: 10px; cursor: pointer; font-weight: 700;
        }

        .user-action-btn.block {
            background: rgba(255, 107, 107, 0.08); color: #ffb0b0; border-color: rgba(255,107,107,0.18);
        }

        .user-action-btn.unblock {
            background: rgba(56, 211, 159, 0.08); color: #c8f7df; border-color: rgba(56,211,159,0.18);
        }

        .status-badge {
            display: inline-flex; padding: 6px 10px; border-radius: 999px; font-size: 0.72rem; font-weight: 800;
        }

        .status-active {
            background: rgba(56, 211, 159, 0.1); color: var(--green); border: 1px solid rgba(56, 211, 159, 0.2);
        }

        .status-blocked {
            background: rgba(255, 107, 107, 0.1); color: var(--red); border: 1px solid rgba(255, 107, 107, 0.2);
        }

        .success-message {
            margin: 0 0 18px; padding: 12px 14px; border-radius: 12px; background: rgba(56, 211, 159, 0.08);
            border: 1px solid rgba(56, 211, 159, 0.2); color: #d3ffe8; font-weight: 700;
        }

        .admin-toast {
            position: fixed; right: 22px; bottom: 22px; z-index: 10000; width: min(380px, calc(100vw - 28px));
            padding: 16px 18px; border: 1px solid rgba(247,185,0,.45); border-left: 5px solid var(--gold);
            border-radius: 12px; background: #1a1914; color: var(--text); box-shadow: 0 18px 45px rgba(0,0,0,.45);
            opacity: 0; transform: translateY(12px); pointer-events: none; transition: opacity .2s, transform .2s;
        }
        .admin-toast.visible { opacity: 1; transform: translateY(0); }
        .admin-toast strong { display:block; margin-bottom:5px; color:var(--gold-soft); }

        @media (max-width: 1080px) {
            .stats-grid { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
            .dashboard-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 760px) {
            .admin-shell { display: block; }
            .sidebar { position: static; width: 100%; height: auto; border-right: none; border-bottom: 1px solid var(--border); }
            .main { padding: 20px 16px 30px; }
            .topbar { flex-direction: column; align-items: flex-start; }
            .stats-grid { grid-template-columns: 1fr; }
            .table th:nth-child(2), .table td:nth-child(2), .table th:nth-child(3), .table td:nth-child(3) { display: none; }
        }
    </style>
</head>
<body>
    <div id="adminToast" class="admin-toast" role="status" aria-live="polite"></div>
    <div class="admin-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">H</div>
                <div class="brand-text">Hungry Man's</div>
            </div>

            <nav class="nav">
                <a href="admin-dashboard.php" class="nav-item active"><span class="nav-icon">◉</span>Overview</a>
                <a href="admin-orders.php" class="nav-item"><span class="nav-icon">▣</span>Orders</a>
                <a href="admin-menu.php" class="nav-item"><span class="nav-icon">☰</span>Menu</a>
                <a href="admin-reservations.php" class="nav-item"><span class="nav-icon">✦</span>Reservations</a>
                <a href="admin-customers.php" class="nav-item"><span class="nav-icon">👥</span>Customers</a>
                <a href="admin-messages.php" class="nav-item"><span class="nav-icon">✉</span>Messages</a>
                <a href="admin-settings.php" class="nav-item"><span class="nav-icon">⚙</span>Settings</a>
            </nav>

            <div class="sidebar-footer">
                <small>Monthly target</small>
                <div class="mini-progress"><span></span></div>
                <strong>76% complete</strong>
            </div>
        </aside>

        <main class="main">
            <div class="topbar">
                <div class="page-title">
                    <h1>Admin Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($adminName); ?> · <?php echo date('l, F j'); ?></p>
                    <div class="user-badge">Signed in as <?php echo htmlspecialchars($adminName); ?></div>
                </div>

                <div class="topbar-actions">
                    <button class="chip">This Week</button>
                    <button class="action-btn">Export</button>
                    <a href="admin-menu.php" class="action-btn primary" style="display:inline-block;">+ Add Item</a>
                    <a href="admin-logout.php" class="action-btn" style="display:inline-block;">Logout</a>
                </div>
            </div>

            <section class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <article class="stat-card">
                        <div class="stat-head">
                            <span class="label"><?php echo htmlspecialchars($stat['label']); ?></span>
                            <span class="icon-badge"><?php echo htmlspecialchars($stat['icon']); ?></span>
                        </div>
                        <div class="delta <?php echo $stat['trend'] === 'up' ? 'up' : 'down'; ?>"><?php echo htmlspecialchars($stat['delta']); ?></div>
                        <div class="value"><?php echo htmlspecialchars($stat['value']); ?></div>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="dashboard-grid">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Sales overview</h3>
                        <a href="#" class="small-link">View report</a>
                    </div>
                    <div class="chart-panel">
                        <div class="chart-bars" aria-label="Weekly sales chart">
                            <?php foreach ($days as $index => $day): ?>
                                <div class="bar-column">
                                    <div class="bar" style="height: <?php echo $salesBars[$index]; ?>%;"></div>
                                    <div class="bar-label"><?php echo htmlspecialchars($day); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="stack">
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Reservation status</h3>
                            <a href="admin-reservations.php" class="small-link">Manage</a>
                        </div>
                        <div class="panel-body">
                            <div class="list">
                                <?php foreach ($reservations as $booking): ?>
                                    <div class="list-item">
                                        <div class="list-meta">
                                            <strong><?php echo htmlspecialchars($booking['name']); ?></strong>
                                            <small><?php echo htmlspecialchars($booking['date']); ?></small>
                                        </div>
                                        <span class="status-pill status-<?php echo strtolower(str_replace(' ', '-', $booking['status'])); ?>"><?php echo htmlspecialchars($booking['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h3>Team availability</h3>
                            <a href="#" class="small-link">Roster</a>
                        </div>
                        <div class="panel-body">
                            <div class="staff-grid">
                                <?php foreach ($staff as $person): ?>
                                    <div class="staff-card">
                                        <div class="avatar"><?php echo strtoupper(substr($person['name'], 0, 1)); ?></div>
                                        <h4><?php echo htmlspecialchars($person['name']); ?></h4>
                                        <p><?php echo htmlspecialchars($person['role']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="dashboard-grid" style="margin-top:22px;">
                <div class="panel">
                    <div class="panel-header">
                        <h3>Recent orders</h3>
                        <a href="admin-orders.php" class="small-link">See all</a>
                    </div>
                    <div class="panel-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Customer</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['customer']); ?></td>
                                        <td><?php echo htmlspecialchars($order['items']); ?></td>
                                        <td><?php echo htmlspecialchars($order['total']); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="stack">
                    <div class="panel">
                        <div class="panel-header">
                            <h3>Top sellers</h3>
                            <a href="admin-menu.php" class="small-link">Update menu</a>
                        </div>
                        <div class="panel-body">
                            <div class="list">
                                <?php foreach ($menuPerformance as $item): ?>
                                    <div class="list-item">
                                        <div class="list-meta">
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <span><?php echo htmlspecialchars($item['sales']); ?></span>
                                        </div>
                                        <div class="list-price"><?php echo htmlspecialchars($item['revenue']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header">
                            <h3>Recent inquiries</h3>
                            <a href="admin-messages.php" class="small-link">Inbox</a>
                        </div>
                        <div class="panel-body">
                            <div class="list">
                                <?php if (!$messages): ?><p style="color:var(--muted);">No messages received yet.</p><?php endif; ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message">
                                        <strong><?php echo htmlspecialchars($message['name']); ?></strong>
                                        <p><?php echo htmlspecialchars($message['text']); ?></p>
                                        <time><?php echo htmlspecialchars($message['time']); ?></time>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="panel" id="users" style="margin-top:22px;">
                <div class="panel-header">
                    <h3>User Access Control</h3>
                    <a href="admin-customers.php" class="small-link">Manage users</a>
                </div>
                <div class="panel-body">
                    <?php if ($userStatusMessage !== ''): ?>
                        <div class="success-message"><?php echo htmlspecialchars($userStatusMessage); ?></div>
                    <?php endif; ?>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo ($user['status'] === 'active') ? 'status-active' : 'status-blocked'; ?>"><?php echo htmlspecialchars(ucfirst($user['status'])); ?></span>
                                    </td>
                                    <td>
                                        <div class="user-actions">
                                            <?php if ($user['status'] === 'active'): ?>
                                                <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <button type="submit" name="user_action" value="block" class="user-action-btn block">Block</button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                                    <button type="submit" name="user_action" value="unblock" class="user-action-btn unblock">Unblock</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
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
        const adminToast = document.getElementById('adminToast');
        const adminCsrfToken = <?php echo json_encode($_SESSION['admin_csrf']); ?>;
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
                    const messages = data.notifications.map(notification => notification.message).join(' ');
                    const heading = data.notifications.some(notification => notification.type === 'payment_submitted') ? 'Transfer awaiting verification' : 'New account notification';
                    adminToast.replaceChildren();
                    const title = document.createElement('strong');
                    title.textContent = heading;
                    adminToast.append(title);
                    adminToast.append(document.createTextNode(messages));
                    adminToast.classList.add('visible');
                    clearTimeout(adminToastTimer);
                    adminToastTimer = setTimeout(() => adminToast.classList.remove('visible'), 7000);
                }
            } catch (error) {
                // Keep the dashboard usable if the notification endpoint is unavailable.
            }
        }
        checkAdminNotifications();
        setInterval(checkAdminNotifications, 12000);
    </script>
</body>
</html>
