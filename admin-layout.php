<?php
function admin_page_start(string $title, string $active): void
{
    $adminName = htmlspecialchars($_SESSION['admin_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | Hungry Man's Admin</title>
        <style>
            :root { --panel:#141618; --line:rgba(255,255,255,.09); --muted:#a8adb7; --text:#f5f5f5; --gold:#f7b900; --gold-soft:#ffd96a; --green:#38d39f; --red:#ff6b6b; --amber:#ffb454; }
            * { box-sizing:border-box; }
            body { margin:0; min-height:100vh; color:var(--text); background:linear-gradient(180deg,#090909,#101214); font-family:Arial,Helvetica,sans-serif; }
            a { color:inherit; text-decoration:none; }
            .admin-shell { display:flex; min-height:100vh; }
            .sidebar { width:250px; flex:none; padding:25px 17px; background:rgba(15,16,18,.96); border-right:1px solid var(--line); }
            .brand { display:flex; align-items:center; gap:12px; margin:0 0 30px; padding:8px 10px; font-weight:900; }
            .brand-mark { display:grid; place-items:center; width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,var(--gold),#f8d76d); color:#111; font-size:1.2rem; }
            .nav { display:grid; gap:7px; }
            .nav-item { display:flex; align-items:center; gap:11px; padding:11px 12px; border:1px solid transparent; border-radius:10px; color:var(--muted); font-weight:700; }
            .nav-item:hover,.nav-item.active { border-color:rgba(247,185,0,.22); background:rgba(247,185,0,.12); color:var(--text); }
            .nav-icon { display:grid; place-items:center; width:28px; height:28px; border-radius:8px; background:rgba(255,255,255,.05); color:var(--gold-soft); }
            .main { flex:1; min-width:0; padding:28px clamp(16px,3vw,40px) 42px; }
            .topbar { display:flex; align-items:flex-start; justify-content:space-between; gap:18px; margin-bottom:22px; }
            h1 { margin:0; font-size:clamp(1.8rem,3vw,2.4rem); }
            .sub { margin:7px 0 0; color:var(--muted); }
            .logout { padding:10px 14px; border:1px solid var(--line); border-radius:9px; font-weight:700; }
            .panel { margin-bottom:18px; overflow:hidden; border:1px solid var(--line); border-radius:13px; background:rgba(20,22,24,.96); }
            .panel-head { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:16px 19px; border-bottom:1px solid var(--line); }
            .panel-head h2 { margin:0; font-size:1.05rem; }
            .panel-body { padding:18px 19px; }
            .form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
            .field { display:grid; gap:7px; min-width:0; }
            .field.full { grid-column:1/-1; }
            label { color:var(--muted); font-size:.84rem; font-weight:700; }
            input,select,textarea { width:100%; min-height:40px; padding:9px 11px; border:1px solid var(--line); border-radius:8px; background:#101214; color:var(--text); font:inherit; }
            textarea { min-height:80px; resize:vertical; }
            input:focus,select:focus,textarea:focus { outline:2px solid rgba(247,185,0,.28); border-color:var(--gold); }
            .button { min-height:40px; padding:9px 14px; border:0; border-radius:8px; background:var(--gold); color:#17130a; font-weight:800; cursor:pointer; }
            .button:hover { background:var(--gold-soft); }
            .button.secondary { border:1px solid var(--line); background:rgba(255,255,255,.04); color:var(--text); }
            .form-actions { display:flex; align-items:center; gap:9px; }
            .flash { margin-bottom:16px; padding:11px 13px; border-radius:8px; font-weight:700; }
            .flash.success { color:#c8f7df; background:rgba(56,211,159,.1); border:1px solid rgba(56,211,159,.25); }
            .flash.error { color:#ffd5d5; background:rgba(255,107,107,.1); border:1px solid rgba(255,107,107,.25); }
            .table-wrap { overflow-x:auto; }
            table { width:100%; border-collapse:collapse; min-width:740px; }
            th,td { padding:12px 13px; border-top:1px solid var(--line); text-align:left; vertical-align:middle; }
            th { color:var(--muted); font-size:.72rem; text-transform:uppercase; letter-spacing:.07em; }
            td { font-size:.9rem; }
            .status { display:inline-flex; padding:6px 9px; border-radius:999px; font-size:.72rem; font-weight:800; white-space:nowrap; }
            .status-new,.status-pending { color:var(--amber); background:rgba(255,180,84,.1); }
            .status-confirmed,.status-active,.status-read,.status-completed { color:var(--green); background:rgba(56,211,159,.1); }
            .status-cancelled,.status-blocked { color:var(--red); background:rgba(255,107,107,.1); }
            .status-preparing { color:var(--amber); background:rgba(255,180,84,.1); }
            .status-ready,.status-delivered { color:var(--green); background:rgba(56,211,159,.1); }
            .empty { padding:28px 14px; color:var(--muted); text-align:center; }
            .stats { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:13px; margin-bottom:18px; }
            .stat { padding:16px; border:1px solid var(--line); border-radius:11px; background:rgba(255,255,255,.025); }
            .stat span { display:block; color:var(--muted); font-size:.74rem; font-weight:700; text-transform:uppercase; }
            .stat strong { display:block; margin-top:8px; font-size:1.4rem; }
            .admin-toast { position:fixed; right:22px; bottom:22px; z-index:10000; width:min(380px,calc(100vw - 28px)); padding:15px 17px; border:1px solid rgba(247,185,0,.45); border-left:5px solid var(--gold); border-radius:11px; background:#1a1914; box-shadow:0 18px 45px rgba(0,0,0,.45); opacity:0; transform:translateY(12px); pointer-events:none; transition:opacity .2s,transform .2s; }
            .admin-toast.visible { opacity:1; transform:translateY(0); }
            .admin-toast strong { display:block; margin-bottom:5px; color:var(--gold-soft); }
            @media(max-width:850px) { .sidebar { width:210px; } }
            @media(max-width:650px) { .admin-shell { display:block; } .sidebar { width:100%; padding:14px; border-right:0; border-bottom:1px solid var(--line); } .brand { margin-bottom:12px; } .nav { grid-template-columns:repeat(2,minmax(0,1fr)); } .main { padding:20px 14px 30px; } .stats { grid-template-columns:1fr; } .form-grid { grid-template-columns:1fr; } .field.full { grid-column:auto; } }
        </style>
    </head>
    <body>
        <div id="adminToast" class="admin-toast" role="status" aria-live="polite"></div>
        <div class="admin-shell">
            <aside class="sidebar">
                <div class="brand"><span class="brand-mark">H</span><span>Hungry Man's</span></div>
                <nav class="nav" aria-label="Admin navigation">
                    <a class="nav-item <?php echo $active === 'overview' ? 'active' : ''; ?>" href="admin-dashboard.php"><span class="nav-icon">◉</span>Overview</a>
                    <a class="nav-item <?php echo $active === 'orders' ? 'active' : ''; ?>" href="admin-orders.php"><span class="nav-icon">▣</span>Orders</a>
                    <a class="nav-item <?php echo $active === 'menu' ? 'active' : ''; ?>" href="admin-menu.php"><span class="nav-icon">☰</span>Menu</a>
                    <a class="nav-item <?php echo $active === 'reservations' ? 'active' : ''; ?>" href="admin-reservations.php"><span class="nav-icon">✦</span>Reservations</a>
                    <a class="nav-item <?php echo $active === 'customers' ? 'active' : ''; ?>" href="admin-customers.php"><span class="nav-icon">👥</span>Customers</a>
                    <a class="nav-item <?php echo $active === 'messages' ? 'active' : ''; ?>" href="admin-messages.php"><span class="nav-icon">✉</span>Messages</a>
                    <a class="nav-item <?php echo $active === 'settings' ? 'active' : ''; ?>" href="admin-settings.php"><span class="nav-icon">⚙</span>Settings</a>
                </nav>
            </aside>
            <main class="main">
                <div class="topbar">
                    <div><h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1><p class="sub">Signed in as <?php echo $adminName; ?></p></div>
                    <a class="logout" href="admin-logout.php">Log out</a>
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
    <?php
}

function admin_page_end(): void
{
    ?>
            </main>
        </div>
    </body>
    </html>
    <?php
}