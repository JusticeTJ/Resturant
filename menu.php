<?php
$siteDb = require __DIR__ . '/connection.php';
$menuResult = $siteDb->query('SELECT id, name, description, category, price, image_url FROM menu_items WHERE is_available = 1 ORDER BY id');
$menuItems = $menuResult ? $menuResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu | Hungry Man's</title>
    <style>
        :root {
            --bg: #0a0a0a;
            --panel: #121212;
            --gold: #f7b900;
            --gold-soft: #ffd867;
            --text: #f5f5f5;
            --muted: #d3d3d3;
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
        nav a { color: #f0f0f0; font-weight: 700; font-size: 14px; }
        nav a:hover { color: var(--gold); }
        .nav-btn {
            display: inline-flex; align-items: center; justify-content: center; padding: 9px 15px; border-radius: 8px; font-weight: 800;
            background: var(--gold); color: #111; border: 1px solid var(--gold);
        }
        .page-hero {
            background: linear-gradient(120deg, rgba(0,0,0,.8), rgba(0,0,0,.5)), url('https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; padding: 100px 0 70px;
        }
        .page-hero h1 { text-align: center; font-size: clamp(2.5rem, 5vw, 4.4rem); margin: 0; }
        .page-hero p { text-align: center; color: var(--muted); max-width: 760px; margin: 16px auto 0; font-size: 1.06rem; line-height: 1.7; }
        section { padding: 80px 0; }
        .tab-row { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; margin-bottom: 28px; }
        .tab { padding: 10px 18px; border-radius: 999px; border: 1px solid var(--line); background: rgba(255,255,255,.02); color: var(--muted); font-weight: 700; }
        .tab.active { background: rgba(247,185,0,.1); border-color: rgba(247,185,0,.28); color: var(--gold-soft); }
        .menu-grid { display: grid; grid-template-columns: repeat(3, minmax(240px, 1fr)); gap: 24px; }
        .menu-card { position:relative;
            background: rgba(255,255,255,.02); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,.22);
        }
        .order-now { position:absolute;top:14px;right:14px;z-index:2;padding:10px 14px;border:0;border-radius:8px;background:var(--gold);color:#17130a;font-weight:900;opacity:0;transform:translateY(-5px);transition:.18s; }
        .menu-card:hover .order-now,.menu-card:focus-within .order-now { opacity:1;transform:translateY(0); }
        .menu-card img { height: 220px; width: 100%; object-fit: cover; }
        .menu-card .body { padding: 18px 18px 20px; }
        .menu-card .top { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 8px; }
        .menu-card h3 { margin: 0; font-size: 1.45rem; }
        .price { color: var(--gold-soft); font-size: 1.2rem; font-weight: 900; }
        .menu-card p { color: var(--muted); line-height: 1.7; margin: 0 0 12px; }
        .tag { display: inline-block; padding: 6px 10px; border-radius: 999px; background: rgba(247,185,0,.12); color: var(--gold-soft); font-size: 12px; font-weight: 700; }
        footer { background: #0f0f0f; padding: 28px 0 38px; border-top: 1px solid var(--line); text-align: center; }
        @media (max-width: 860px) { .menu-grid { grid-template-columns: 1fr; } .header-inner { flex-direction: column; padding: 18px 0; } nav { justify-content: center; } }
        @media (hover: none) { .order-now { opacity: 1; transform: none; } }
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

    <section class="page-hero">
        <div class="container">
            <h1>Our signature menu</h1>
            <p>From indulgent mains to fresh favorites, every dish is designed around bold flavor, quality ingredients, and a memorable dining experience.</p>
        </div>
    </section>

    <section>
        <div class="container">
            <div class="tab-row">
                <div class="tab active">All</div>
                <div class="tab">Starters</div>
                <div class="tab">Mains</div>
                <div class="tab">Desserts</div>
                <div class="tab">Drinks</div>
            </div>

            <div class="menu-grid">
                <?php foreach ($menuItems as $item): ?>
                    <article class="menu-card">
                        <a class="order-now" href="index.php?add_item=<?php echo (int)$item['id']; ?>#menu">Order now</a>
                        <img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="body">
                            <div class="top"><h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3><span class="price">₦<?php echo number_format((float)$item['price']); ?></span></div>
                            <p><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="tag"><?php echo htmlspecialchars($item['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </article>
                <?php endforeach; ?>
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
