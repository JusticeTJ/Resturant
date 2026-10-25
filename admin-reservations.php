<?php
require __DIR__ . '/session-config.php';
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin-login.php');
    exit;
}

$conn = require __DIR__ . '/connection.php';
$statuses = ['Pending', 'Confirmed', 'Cancelled', 'Completed'];
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $reservationId = (int)($_POST['reservation_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if (!hash_equals($_SESSION['admin_csrf'], $token) || $reservationId < 1 || !in_array($status, $statuses, true)) {
        $_SESSION['admin_reservation_flash'] = ['type' => 'error', 'text' => 'Invalid reservation update. Please try again.'];
    } else {
        $stmt = $conn->prepare('UPDATE reservations SET status = ? WHERE id = ?');
        $stmt->bind_param('si', $status, $reservationId);
        $_SESSION['admin_reservation_flash'] = $stmt->execute()
            ? ['type' => 'success', 'text' => 'Reservation status updated.']
            : ['type' => 'error', 'text' => 'Could not update this reservation.'];
    }
    header('Location: admin-reservations.php');
    exit;
}

$result = $conn->query('SELECT * FROM reservations ORDER BY reservation_date DESC, reservation_time DESC');
$allReservations = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$validFilter = in_array($statusFilter, $statuses, true) ? $statusFilter : '';
$reservations = array_values(array_filter($allReservations, static function ($reservation) use ($search, $validFilter) {
    $matchesSearch = $search === '' || stripos($reservation['customer_name'] . ' ' . $reservation['email'] . ' ' . $reservation['phone'], $search) !== false;
    return $matchesSearch && ($validFilter === '' || $reservation['status'] === $validFilter);
}));
$pending = count(array_filter($allReservations, static fn($reservation) => $reservation['status'] === 'Pending'));
$confirmed = count(array_filter($allReservations, static fn($reservation) => $reservation['status'] === 'Confirmed'));
$flash = $_SESSION['admin_reservation_flash'] ?? null;
unset($_SESSION['admin_reservation_flash']);
require __DIR__ . '/admin-layout.php';
admin_page_start('Reservations', 'reservations');
?>
<?php if ($flash): ?><div class="flash <?php echo htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($flash['text'], ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
<section class="stats">
    <article class="stat"><span>All bookings</span><strong><?php echo count($allReservations); ?></strong></article>
    <article class="stat"><span>Awaiting review</span><strong><?php echo $pending; ?></strong></article>
    <article class="stat"><span>Confirmed</span><strong><?php echo $confirmed; ?></strong></article>
</section>
<section class="panel">
    <div class="panel-head"><h2>Reservation requests</h2><span class="sub"><?php echo count($reservations); ?> shown</span></div>
    <form class="form-grid panel-body" method="GET" action="admin-reservations.php">
        <div class="field"><label for="reservation-search">Search bookings</label><input id="reservation-search" type="search" name="q" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name, email, or phone"></div>
        <div class="field"><label for="reservation-status">Status</label><select id="reservation-status" name="status"><option value="">All statuses</option><?php foreach ($statuses as $option): ?><option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $validFilter === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
        <div class="field full form-actions"><button class="button" type="submit">Filter</button></div>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Customer</th><th>Date & time</th><th>Guests</th><th>Occasion</th><th>Phone</th><th>Requests</th><th>Status</th><th>Update</th></tr></thead>
            <tbody>
                <?php if (!$reservations): ?><tr><td colspan="8" class="empty">No reservation requests match this view.</td></tr><?php endif; ?>
                <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($reservation['customer_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br><span class="sub"><?php echo htmlspecialchars($reservation['email'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($reservation['reservation_date'])) . ' · ' . $reservation['reservation_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo (int)$reservation['guests']; ?></td>
                        <td><?php echo htmlspecialchars($reservation['occasion'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reservation['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($reservation['notes'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><span class="status status-<?php echo strtolower(htmlspecialchars($reservation['status'], ENT_QUOTES, 'UTF-8')); ?>"><?php echo htmlspecialchars($reservation['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><form method="POST" class="form-actions"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="reservation_id" value="<?php echo (int)$reservation['id']; ?>"><select name="status" aria-label="New reservation status"><?php foreach ($statuses as $option): ?><option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $reservation['status'] === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><button class="button" type="submit">Save</button></form></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php admin_page_end(); ?>