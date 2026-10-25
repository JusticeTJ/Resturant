<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Hungry Man's</title>
    <style>
        :root {
            --bg: #0a0a0a;
            --panel: #121212;
            --panel-2: #191919;
            --gold: #f7b900;
            --gold-soft: #ffd867;
            --text: #f4f4f4;
            --muted: #d7d7d7;
            --soft: #f1eadb;
            --line: rgba(255,255,255,.08);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #090909;
            color: var(--text);
        }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }
        .container { width: min(1180px, 92%); margin: 0 auto; }
        header {
            position: sticky; top: 0; z-index: 20;
            background: rgba(10,10,10,.92); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--line);
        }
        .header-inner {
            display: flex; align-items: center; justify-content: space-between; gap: 24px;
            min-height: 76px;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; }
        .brand img { width: 52px; height: 52px; border-radius: 50%; }
        nav { display: flex; align-items: center; gap: 26px; flex-wrap: wrap; }
        nav a { color: #ececec; font-weight: 700; font-size: 14px; }
        nav a:hover { color: var(--gold); }
        .nav-btn {
            display: inline-flex; align-items: center; justify-content: center;
            padding: 10px 16px; border-radius: 8px; font-weight: 800;
            border: 1px solid var(--gold); background: transparent; color: var(--gold);
        }
        .nav-btn.primary {
            background: var(--gold); color: #111; border-color: var(--gold);
        }
        .hero {
            background: linear-gradient(120deg, rgba(0,0,0,.75), rgba(0,0,0,.45)),
                        url('https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            padding: 100px 0 80px;
        }
        .hero-inner { text-align: center; }
        .eyebrow { color: var(--gold-soft); text-transform: uppercase; letter-spacing: 0.18em; font-size: 12px; font-weight: 800; }
        h1 { font-size: clamp(2.5rem, 5vw, 5rem); margin: 14px 0 18px; line-height: 1.02; }
        .hero p { max-width: 750px; margin: 0 auto; color: var(--muted); font-size: 1.08rem; line-height: 1.7; }
        section { padding: 82px 0; }
        .two-col { display: grid; grid-template-columns: 1.1fr .9fr; gap: 36px; align-items: center; }
        .card {
            background: linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,.01));
            border: 1px solid var(--line); border-radius: 24px; padding: 28px; box-shadow: 0 22px 50px rgba(0,0,0,.25);
        }
        h2 { font-size: clamp(2rem, 4vw, 3.2rem); margin: 0 0 18px; }
        p { color: var(--muted); line-height: 1.8; }
        .story-list { display: grid; gap: 16px; margin-top: 20px; }
        .story-item {
            display: flex; gap: 14px; align-items: flex-start; padding: 14px 16px; border-radius: 14px; background: rgba(255,255,255,.02); border: 1px solid var(--line);
        }
        .story-item .num {
            width: 30px; height: 30px; border-radius: 50%; background: rgba(247,185,0,.18); display: grid; place-items: center; color: var(--gold-soft); font-weight: 800;
        }
        .image-wrap { border-radius: 24px; overflow: hidden; border: 1px solid var(--line); }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .feature-card { padding: 26px 20px; border-radius: 18px; border: 1px solid var(--line); background: rgba(255,255,255,.02); }
        .feature-card .icon { font-size: 2rem; margin-bottom: 10px; }
        .feature-card h3 { margin: 0 0 10px; }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
        .stat { padding: 22px 18px; text-align: center; border: 1px solid var(--line); border-radius: 18px; background: rgba(255,255,255,.02); }
        .stat strong { display: block; font-size: 2rem; color: var(--gold-soft); }
        .stat span { color: var(--muted); }
        footer {
            background: #0f0f0f; border-top: 1px solid var(--line); padding: 28px 0 40px; text-align: center;
        }
        .socials { display: flex; justify-content: center; gap: 8px; margin: 18px 0 10px; }
        .socials a {
            width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center;
            border: 1px solid var(--line); background: rgba(255,255,255,.02); color: var(--text);
        }
        @media (max-width: 840px) {
            .two-col, .grid-3, .stats { grid-template-columns: 1fr; }
            .header-inner { flex-direction: column; padding: 18px 0; }
            nav { justify-content: center; }
        }
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
                <a class="nav-btn primary" href="admin-dashboard.php">Admin</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-inner">
                <div class="eyebrow">Our story</div>
                <h1>Flavors born from passion.</h1>
                <p>Hungry Man's began with one simple idea: serve generous portions, honest ingredients, and a warm atmosphere where everyone feels at home.</p>
            </div>
        </section>

        <section>
            <div class="container two-col">
                <div>
                    <div class="eyebrow" style="margin-bottom:12px;">Since 2015</div>
                    <h2>Crafted to bring people together.</h2>
                    <p>From our first neighborhood dinners to our now lively downtown dining room, we have always focused on memorable food and exceptional hospitality. Every plate is designed to spark conversation and comfort.</p>
                    <div class="story-list">
                        <div class="story-item">
                            <div class="num">1</div>
                            <div>
                                <strong>Local sourcing</strong><br>
                                <span>We prioritize fresh ingredients from trusted local farms and suppliers.</span>
                            </div>
                        </div>
                        <div class="story-item">
                            <div class="num">2</div>
                            <div>
                                <strong>Chef-led kitchen</strong><br>
                                <span>Our culinary team blends classic methods with modern flavor profiles.</span>
                            </div>
                        </div>
                        <div class="story-item">
                            <div class="num">3</div>
                            <div>
                                <strong>Warm hospitality</strong><br>
                                <span>Service is personal, welcoming, and built around your experience.</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="image-wrap">
                    <img src="https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=1000&q=80" alt="Chef preparing food" style="height:100%;min-height:480px;object-fit:cover;">
                </div>
            </div>
        </section>

        <section style="background: rgba(255,255,255,.02);">
            <div class="container">
                <div class="eyebrow" style="text-align:center; margin-bottom:12px;">What makes us different</div>
                <h2 style="text-align:center;">The Hungry Man's experience</h2>
                <div class="grid-3" style="margin-top:28px;">
                    <div class="feature-card">
                        <div class="icon">👨‍🍳</div>
                        <h3>Expert chefs</h3>
                        <p>Every dish is prepared with technique, balance, and a love for comfort food done right.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🌿</div>
                        <h3>Fresh ingredients</h3>
                        <p>We select premium produce, meats, and pantry essentials that keep every plate vibrant.</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎉</div>
                        <h3>Memorable nights</h3>
                        <p>From casual bites to date-night dinners, our atmosphere makes every visit special.</p>
                    </div>
                </div>
            </div>
        </section>

        <section>
            <div class="container">
                <div class="stats">
                    <div class="stat">
                        <strong>9+</strong>
                        <span>Years serving the city</span>
                    </div>
                    <div class="stat">
                        <strong>12k</strong>
                        <span>Happy guests served</span>
                    </div>
                    <div class="stat">
                        <strong>42</strong>
                        <span>Signature dishes</span>
                    </div>
                    <div class="stat">
                        <strong>4.9</strong>
                        <span>Average guest rating</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's logo" style="width:72px; margin:0 auto 12px; border-radius:50%;">
            <h3>Hungry Man's</h3>
            <p style="margin:0; color:var(--muted);">Good Food • Great Vibes</p>
            <div class="socials">
                <a href="#">f</a>
                <a href="#">◎</a>
                <a href="#">x</a>
            </div>
            <p style="margin:0; color:var(--muted);">&copy; 2026 Hungry Man's. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
