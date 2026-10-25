<?php
require __DIR__ . '/session-config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?auth=login&return=reservations');
    exit;
}

$conn = require __DIR__ . '/connection.php';
$userStatusStmt = $conn->prepare('SELECT status FROM users WHERE id = ? LIMIT 1');
$sessionUserId = (int)$_SESSION['user_id'];
$userStatusStmt->bind_param('i', $sessionUserId);
$userStatusStmt->execute();
$currentUser = $userStatusStmt->get_result()->fetch_assoc();
if (!$currentUser || $currentUser['status'] !== 'active') {
    unset($_SESSION['user_id'], $_SESSION['user_email'], $_SESSION['user_first_name'], $_SESSION['user_last_name']);
    header('Location: index.php?auth=login&return=reservations&blocked=1');
    exit;
}
$settingResult = $conn->query('SELECT setting_key, setting_value FROM restaurant_settings');
$restaurantSettings = [];
if ($settingResult) {
    foreach ($settingResult->fetch_all(MYSQLI_ASSOC) as $setting) {
        $restaurantSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}
if (empty($_SESSION['reservation_csrf'])) {
    $_SESSION['reservation_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $guests = (int)($_POST['guests'] ?? 0);
    $occasion = $_POST['occasion'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $allowedTimes = ['5:30 PM', '6:00 PM', '7:30 PM', '8:15 PM'];
    $allowedOccasions = ['Casual Dinner', 'Date Night', 'Birthday', 'Business Meal'];
    $validDate = DateTime::createFromFormat('Y-m-d', $date);

    if (!hash_equals($_SESSION['reservation_csrf'], $token)) {
        $_SESSION['reservation_message'] = ['type' => 'error', 'text' => 'Your session expired. Please submit the reservation again.'];
    } elseif ($phone === '' || !$validDate || $validDate->format('Y-m-d') !== $date || $date < date('Y-m-d') || !in_array($time, $allowedTimes, true) || !in_array($guests, [2, 4, 6, 8], true) || !in_array($occasion, $allowedOccasions, true)) {
        $_SESSION['reservation_message'] = ['type' => 'error', 'text' => 'Check the phone, date, time, guest count, and occasion fields.'];
    } else {
        $customerName = trim(($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''));
        $email = $_SESSION['user_email'] ?? '';
        $userId = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare('INSERT INTO reservations (user_id, customer_name, email, phone, reservation_date, reservation_time, guests, occasion, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssiss', $userId, $customerName, $email, $phone, $date, $time, $guests, $occasion, $notes);
        if ($stmt->execute()) {
            $_SESSION['reservation_message'] = ['type' => 'success', 'text' => 'Your reservation request has been sent. We will confirm it soon.'];
        } else {
            $_SESSION['reservation_message'] = ['type' => 'error', 'text' => 'We could not save your reservation. Please try again.'];
        }
    }

    header('Location: reservations.php');
    exit;
}

$reservationMessage = $_SESSION['reservation_message'] ?? null;
unset($_SESSION['reservation_message']);
$customerName = trim(($_SESSION['user_first_name'] ?? '') . ' ' . ($_SESSION['user_last_name'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations | Hungry Man's</title>
    <style>
        :root {
            --bg: #090909;
            --panel: #111111;
            --gold: #f7b900;
            --gold-soft: #ffd867;
            --text: #f4f4f4;
            --muted: #cfcfcf;
            --line: rgba(255,255,255,.08);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #090909; color: var(--text); }
        a { text-decoration: none; color: inherit; }
        img { display: block; max-width: 100%; }
        .container { width: min(1180px, 92%); margin: 0 auto; }
        header { background: rgba(10,10,10,.92); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 20; }
        .header-inner { display: flex; align-items: center; justify-content: space-between; min-height: 76px; gap: 24px; }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; }
        .brand img { width: 52px; height: 52px; border-radius: 50%; }
        nav { display: flex; align-items: center; gap: 24px; flex-wrap: wrap; }
        nav a { color: #efefef; font-weight: 700; font-size: 14px; }
        nav a:hover { color: var(--gold); }
        .nav-btn { padding: 9px 15px; border-radius: 8px; background: var(--gold); color: #111; font-weight: 800; border: 1px solid var(--gold); }
        .hero { background: linear-gradient(120deg, rgba(0,0,0,.8), rgba(0,0,0,.5)), url('https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 100px 0 80px; }
        .hero h1 { text-align: center; font-size: clamp(2.4rem, 5vw, 4.5rem); margin: 0; }
        .hero p { text-align: center; max-width: 740px; margin: 16px auto 0; color: var(--muted); font-size: 1.08rem; line-height: 1.7; }
        section { padding: 80px 0; }
        .layout { display: grid; grid-template-columns: 1.1fr .9fr; gap: 24px; }
        .card {
            border: 1px solid var(--line); border-radius: 20px; background: rgba(255,255,255,.02); padding: 26px; box-shadow: 0 20px 40px rgba(0,0,0,.2);
        }
        h2 { margin: 0 0 16px; font-size: clamp(2rem, 4vw, 3rem); }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px; }
        label { display: block; margin-bottom: 8px; color: var(--muted); font-weight: 700; }
        input, select, textarea { width: 100%; border: 1px solid var(--line); background: rgba(255,255,255,.02); border-radius: 12px; padding: 13px 14px; color: var(--text); font: inherit; }
        textarea { min-height: 120px; resize: vertical; }
        .full { grid-column: 1 / -1; }
        .submit { margin-top: 18px; padding: 14px 26px; border-radius: 12px; border: none; background: linear-gradient(135deg, var(--gold), var(--gold-soft)); color: #111; font-weight: 900; cursor: pointer; }
        .form-message { grid-column:1/-1; padding:12px 14px; border-radius:10px; font-weight:700; }
        .form-message.success { color:#c8f7df; background:rgba(56,211,159,.1); border:1px solid rgba(56,211,159,.25); }
        .form-message.error { color:#ffd5d5; background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.25); }
        .info-list { display: grid; gap: 16px; }
        .info-item { padding: 18px; border: 1px solid var(--line); border-radius: 14px; background: rgba(255,255,255,.02); }
        .info-item strong { display: block; margin-bottom: 6px; color: var(--gold-soft); }
        .info-item span, .info-item p { margin: 0; color: var(--muted); line-height: 1.7; }
        footer { background: #0f0f0f; padding: 28px 0 38px; border-top: 1px solid var(--line); text-align: center; }
        @media (max-width: 820px) { .layout, .form-grid { grid-template-columns: 1fr; } .header-inner { flex-direction: column; padding: 18px 0; } nav { justify-content: center; } }
    </style>
</head>
<body>
    <header>
        <div class="container header-inner">
            <a class="brand" href="index.php">
                <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo">
                <span>Hungry Man's</span>
            </a>
            <nav>
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="menu.php">Menu</a>
                <a href="reservations.php">Reservations</a>
                <a href="contact-page.php">Contact</a>
                <a class="nav-btn" href="admin-dashboard.php">Admin</a>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Reserve your table</h1>
            <p>Whether it’s a quick lunch, a romantic dinner, or a special celebration, we’d love to host you at Hungry Man’s.</p>
        </div>
    </section>

    <section>
        <div class="container layout">
            <div class="card">
                <h2>Book a table</h2>
                <p style="color:var(--muted);">Booking as <?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?></p>
                <form class="form-grid" method="POST" action="reservations.php">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['reservation_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php if ($reservationMessage): ?>
                        <div class="form-message <?php echo htmlspecialchars($reservationMessage['type'], ENT_QUOTES, 'UTF-8'); ?>" role="status"><?php echo htmlspecialchars($reservationMessage['text'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endif; ?>
                    <div>
                        <label for="name">Full name</label>
                        <input id="name" type="text" value="<?php echo htmlspecialchars($customerName, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                    </div>
                    <div>
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" type="tel" placeholder="+234 800 000 0000" required>
                    </div>
                    <div>
                        <label for="date">Date</label>
                        <input id="date" name="date" type="date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div>
                        <label for="guests">Guests</label>
                        <select id="guests" name="guests" required>
                            <option value="2">2 Guests</option>
                            <option value="4">4 Guests</option>
                            <option value="6">6 Guests</option>
                            <option value="8">8 Guests</option>
                        </select>
                    </div>
                    <div>
                        <label for="time">Time</label>
                        <select id="time" name="time" required>
                            <option>5:30 PM</option>
                            <option>6:00 PM</option>
                            <option>7:30 PM</option>
                            <option>8:15 PM</option>
                        </select>
                    </div>
                    <div>
                        <label for="occasion">Occasion</label>
                        <select id="occasion" name="occasion" required>
                            <option>Casual Dinner</option>
                            <option>Date Night</option>
                            <option>Birthday</option>
                            <option>Business Meal</option>
                        </select>
                    </div>
                    <div class="full">
                        <label for="notes">Special requests</label>
                        <textarea id="notes" name="notes" placeholder="Tell us about your preferences..."></textarea>
                    </div>
                    <div class="full">
                        <button type="submit" class="submit">Confirm reservation</button>
                    </div>
                </form>
            </div>

            <aside class="card">
                <h2>Dining details</h2>
                <div class="info-list">
                    <div class="info-item">
                        <strong>Address</strong>
                        <span><?php echo htmlspecialchars($restaurantSettings['address'] ?? '123 Gourmet Street, Downtown District, Restaurant Quarter', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Hours</strong>
                        <p><?php echo nl2br(htmlspecialchars($restaurantSettings['opening_hours'] ?? 'Mon-Thu: 11 AM-10 PM; Fri-Sat: 11 AM-11 PM; Sun: 10 AM-9 PM', ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Contact</strong>
                        <p><?php echo htmlspecialchars($restaurantSettings['contact_phone'] ?? '+234 000 000 0000', ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($restaurantSettings['contact_email'] ?? 'hello@hungrymans.com', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <footer>
        <div class="container">
            <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo" style="width:72px; border-radius:50%; margin:0 auto 12px;">
            <h3>Hungry Man's</h3>
            <p style="margin:0; color:#d4d4d4;">Good Food • Great Vibes</p>
        </div>
    </footer>
</body>
</html>
