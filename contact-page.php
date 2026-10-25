<?php
require __DIR__ . '/session-config.php';

$contactResult = $_GET['result'] ?? '';
$siteDb = require __DIR__ . '/connection.php';
$settingResult = $siteDb->query('SELECT setting_key, setting_value FROM restaurant_settings');
$restaurantSettings = [];
if ($settingResult) {
    foreach ($settingResult->fetch_all(MYSQLI_ASSOC) as $setting) {
        $restaurantSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}
$contactCsrfToken = $_SESSION['contact_csrf'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Hungry Man's</title>
    <style>
        :root {
            --bg: #090909;
            --panel: #111111;
            --gold: #f7b900;
            --gold-soft: #ffd867;
            --text: #f4f4f4;
            --muted: #d7d7d7;
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
        .hero { background: linear-gradient(120deg, rgba(0,0,0,.8), rgba(0,0,0,.5)), url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 100px 0 80px; }
        .hero h1 { text-align: center; font-size: clamp(2.4rem, 5vw, 4.5rem); margin: 0; }
        .hero p { text-align: center; max-width: 740px; margin: 16px auto 0; color: var(--muted); font-size: 1.08rem; line-height: 1.7; }
        section { padding: 80px 0; }
        .card-grid { display: grid; grid-template-columns: .95fr 1.05fr; gap: 24px; }
        .card {
            border: 1px solid var(--line); background: rgba(255,255,255,.02); border-radius: 20px; padding: 28px; box-shadow: 0 20px 40px rgba(0,0,0,.2);
        }
        .info-list { display: grid; gap: 18px; }
        .info-item { border: 1px solid var(--line); border-radius: 14px; padding: 18px; background: rgba(255,255,255,.02); }
        .info-item strong { display: block; margin-bottom: 6px; color: var(--gold-soft); }
        .info-item p, .info-item span { margin: 0; color: var(--muted); line-height: 1.7; }
        form { display: grid; gap: 16px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; color: var(--muted); }
        input, textarea { width: 100%; background: rgba(255,255,255,.02); border: 1px solid var(--line); border-radius: 12px; padding: 13px 14px; color: var(--text); font: inherit; }
        textarea { min-height: 140px; resize: vertical; }
        .submit { padding: 14px 22px; border: none; border-radius: 12px; background: linear-gradient(135deg, var(--gold), var(--gold-soft)); color: #111; font-weight: 900; cursor: pointer; }
        .form-notice { margin:0 0 16px; padding:12px 14px; border-radius:10px; font-weight:700; }
        .form-notice.sent { color:#c8f7df; background:rgba(56,211,159,.1); border:1px solid rgba(56,211,159,.25); }
        .form-notice.error { color:#ffd5d5; background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.25); }
        footer { background: #0f0f0f; padding: 28px 0 38px; border-top: 1px solid var(--line); text-align: center; }
        @media (max-width: 820px) { .card-grid { grid-template-columns: 1fr; } .header-inner { flex-direction: column; padding: 18px 0; } nav { justify-content: center; } }
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
            <h1>We'd love to hear from you</h1>
            <p>Whether you have a question, want to book a special event, or just want to say hello, our team is here to help.</p>
        </div>
    </section>

    <section>
        <div class="container card-grid">
            <aside class="card">
                <h2>Contact details</h2>
                <div class="info-list">
                    <div class="info-item">
                        <strong>Location</strong>
                        <span><?php echo htmlspecialchars($restaurantSettings['address'] ?? '123 Gourmet Street, Downtown District, Restaurant Quarter', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-item">
                        <strong>Phone</strong>
                        <p><?php echo htmlspecialchars($restaurantSettings['contact_phone'] ?? '+234 000 000 0000', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Email</strong>
                        <p><?php echo htmlspecialchars($restaurantSettings['contact_email'] ?? 'hello@hungrymans.com', ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                    <div class="info-item">
                        <strong>Hours</strong>
                        <p><?php echo nl2br(htmlspecialchars($restaurantSettings['opening_hours'] ?? 'Mon-Thu: 11 AM-10 PM; Fri-Sat: 11 AM-11 PM; Sun: 10 AM-9 PM', ENT_QUOTES, 'UTF-8')); ?></p>
                    </div>
                </div>
            </aside>

            <div class="card">
                <h2>Send a message</h2>
                <?php if ($contactResult === 'sent'): ?>
                    <p class="form-notice sent" role="status">Thanks for your message. The restaurant team will get back to you.</p>
                <?php elseif ($contactResult === 'error'): ?>
                    <p class="form-notice error" role="alert">Please check your details and try sending your message again.</p>
                <?php endif; ?>
                <form method="POST" action="contact-submit.php" id="message-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($contactCsrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div>
                        <label for="contact-name">Your Name</label>
                        <input id="contact-name" name="name" type="text" placeholder="Enter your name" required>
                    </div>
                    <div>
                        <label for="contact-email">Email Address</label>
                        <input id="contact-email" name="email" type="email" placeholder="Enter your email" required>
                    </div>
                    <div>
                        <label for="contact-subject">Subject</label>
                        <input id="contact-subject" name="subject" type="text" placeholder="How can we help?" required>
                    </div>
                    <div>
                        <label for="contact-message">Message</label>
                        <textarea id="contact-message" name="message" placeholder="Write your message here..." required></textarea>
                    </div>
                    <button type="submit" class="submit">Send message</button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo" style="width:72px; border-radius:50%; margin:0 auto 12px;">
            <h3>Hungry Man's</h3>
            <p style="margin:0; color:#d7d7d7;">Good Food • Great Vibes</p>
        </div>
    </footer>
</body>
</html>
