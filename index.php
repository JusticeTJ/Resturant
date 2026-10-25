<?php
require __DIR__ . '/session-config.php';
$userLoggedIn = isset($_SESSION['user_id']);
$userFirstName = $_SESSION['user_first_name'] ?? '';
$userId = (int)($_SESSION['user_id'] ?? 0);
if (empty($_SESSION['order_csrf'])) {
    $_SESSION['order_csrf'] = bin2hex(random_bytes(32));
}
$siteDb = require __DIR__ . '/connection.php';
$menuResult = $siteDb->query('SELECT id, name, description, price, image_url FROM menu_items WHERE is_available = 1 ORDER BY id DESC LIMIT 6');
$featuredMenuItems = $menuResult ? $menuResult->fetch_all(MYSQLI_ASSOC) : [];
$requestedMenuItemId = (int)($_GET['add_item'] ?? 0);
if ($requestedMenuItemId > 0) {
    $requestedMenuStmt = $siteDb->prepare('SELECT id, name, description, price, image_url FROM menu_items WHERE id = ? AND is_available = 1 LIMIT 1');
    $requestedMenuStmt->bind_param('i', $requestedMenuItemId);
    $requestedMenuStmt->execute();
    $requestedMenuItem = $requestedMenuStmt->get_result()->fetch_assoc();
    if ($requestedMenuItem && !array_filter($featuredMenuItems, static fn($item) => (int)$item['id'] === $requestedMenuItemId)) {
        $featuredMenuItems[] = $requestedMenuItem;
    }
}
$customerOrders = [];
if ($userLoggedIn) {
    $customerOrderStmt = $siteDb->prepare('SELECT id, order_number, item_name, total_amount, status, payment_status, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 10');
    $customerOrderStmt->bind_param('i', $userId);
    $customerOrderStmt->execute();
    $customerOrders = $customerOrderStmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$settingResult = $siteDb->query('SELECT setting_key, setting_value FROM restaurant_settings');
$restaurantSettings = [];
if ($settingResult) {
    foreach ($settingResult->fetch_all(MYSQLI_ASSOC) as $setting) {
        $restaurantSettings[$setting['setting_key']] = $setting['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hungry Man's Restaurant | Good Food • Great Vibes</title>
    <link rel="stylesheet" href="style.css">

    <style>
        /* =========================================================
           HUNGRY MAN'S HOMEPAGE REDESIGN
           These rules intentionally tighten the old large gaps.
           ========================================================= */
        :root{
            --gold:#f7b900;
            --gold2:#ffcf33;
            --black:#090909;
            --dark:#111111;
            --dark2:#181818;
            --white:#fff;
            --muted:#c8c8c8;
            --line:rgba(255,255,255,.10);
        }

        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{
            margin:0;
            background:var(--black);
            color:var(--white);
            font-family:Arial, Helvetica, sans-serif;
            overflow-x:hidden;
        }
        img{max-width:100%;display:block}
        a{text-decoration:none}

        /* HEADER */
        header{
            position:fixed;
            inset:0 0 auto 0;
            z-index:1000;
            background:rgba(7,7,7,.92);
            border-bottom:1px solid var(--line);
            backdrop-filter:blur(12px);
        }
        .header-content{
            width:min(1180px,92%);
            min-height:76px;
            margin:auto;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:24px;
        }
        .logo{
            display:flex;
            align-items:center;
            gap:10px;
            color:#fff;
            font-weight:800;
            font-size:18px;
            white-space:nowrap;
        }
        .logo-image{
            width:54px;
            height:54px;
            object-fit:contain;
            border-radius:50%;
        }
        nav{display:flex;align-items:center;gap:26px}
        nav a{
            color:#eee;
            font-size:14px;
            font-weight:700;
            transition:.2s;
        }
        nav a:hover{color:var(--gold)}
        .auth-buttons{display:flex;gap:9px}
        .auth-buttons button{
            border:1px solid var(--gold);
            padding:9px 15px;
            border-radius:7px;
            font-weight:700;
            cursor:pointer;
        }
        .btn-signin{background:transparent;color:var(--gold)}
        .btn-login{background:var(--gold);color:#111}
        .auth-buttons button:hover{transform:translateY(-1px)}
        .hamburger{display:none;flex-direction:column;gap:4px;background:transparent;border:none;cursor:pointer;padding:5px}
        .hamburger span{width:22px;height:2px;background:var(--gold);border-radius:2px;transition:.3s}
        .hamburger span:nth-child(2){width:16px}
        .hamburger.active span:nth-child(1){transform:rotate(45deg) translate(5px,5px)}
        .hamburger.active span:nth-child(2){opacity:0}
        .hamburger.active span:nth-child(3){transform:rotate(-45deg) translate(4px,-4px)}
        .mobile-nav{display:none;position:fixed;top:0;left:0;width:100%;height:100vh;background:rgba(9,9,9,.98);padding:80px 20px 20px;z-index:9999;flex-direction:column;gap:0;transform:translateX(-100%);transition:transform .3s ease}
        .mobile-nav.active{transform:translateX(0)}
        .mobile-nav a{color:#fff;font-size:18px;font-weight:700;padding:15px 0;border-bottom:1px solid rgba(255,255,255,.1);text-decoration:none}
        .mobile-nav a:last-child{border-bottom:none}
        /* HERO SLIDER */
        .hero{
            position:relative;
            min-height:clamp(560px,82vh,760px);
            margin:0;
            overflow:hidden;
            background:#000;
        }
        .hero-track{
            height:100%;
            display:flex;
            transition:transform .7s ease;
        }
        .hero-slide{
            position:relative;
            min-width:100%;
            min-height:clamp(560px,82vh,760px);
            display:flex;
            align-items:center;
            justify-content:center;
            text-align:center;
            background-position:center;
            background-size:cover;
        }
        .hero-slide::before{
            content:"";
            position:absolute;
            inset:0;
            background:linear-gradient(90deg,rgba(0,0,0,.78),rgba(0,0,0,.35),rgba(0,0,0,.72));
        }
        .hero-content{
            position:relative;
            z-index:2;
            width:min(850px,90%);
            padding:100px 0 30px;
        }
        .hero-logo{
            width:170px;
            height:170px;
            object-fit:contain;
            margin:0 auto 12px;
            filter:drop-shadow(0 10px 25px rgba(0,0,0,.6));
        }
        .hero-kicker{
            color:var(--gold2);
            text-transform:uppercase;
            letter-spacing:4px;
            font-weight:800;
            font-size:13px;
            margin:0 0 10px;
        }
        .hero-title{
            margin:0 0 12px;
            font-size:clamp(42px,7vw,82px);
            line-height:.95;
            font-weight:900;
        }
        .hero-title span{color:var(--gold)}
        .hero-subtitle{
            margin:0 auto 24px;
            max-width:650px;
            color:#eee;
            font-size:clamp(16px,2vw,20px);
            line-height:1.5;
        }
        .cta-button{
            border:0;
            background:var(--gold);
            color:#111;
            padding:13px 24px;
            border-radius:7px;
            font-weight:800;
            cursor:pointer;
            box-shadow:0 8px 25px rgba(0,0,0,.3);
        }
        .cta-button:hover{background:var(--gold2);transform:translateY(-2px)}
        .hero-arrow{
            position:absolute;
            top:50%;
            transform:translateY(-50%);
            z-index:5;
            width:44px;
            height:44px;
            border:1px solid rgba(255,255,255,.4);
            border-radius:50%;
            background:rgba(0,0,0,.45);
            color:#fff;
            font-size:25px;
            cursor:pointer;
        }
        .hero-arrow:hover{background:var(--gold);color:#111}
        .hero-prev{left:22px}
        .hero-next{right:22px}
        .hero-dots{
            position:absolute;
            z-index:5;
            bottom:22px;
            left:50%;
            transform:translateX(-50%);
            display:flex;
            gap:8px;
        }
        .hero-dot{
            width:10px;
            height:10px;
            border-radius:50%;
            border:0;
            padding:0;
            background:rgba(255,255,255,.5);
            cursor:pointer;
        }
        .hero-dot.active{background:var(--gold);width:28px;border-radius:10px}

        /* SECTIONS — reduced padding to remove excessive empty space */
        section{
            padding:58px 0;
            margin:0;
        }
        .section-content{
            width:min(1120px,92%);
            margin:0 auto;
        }
        section h2{
            margin:0 0 28px;
            text-align:center;
            font-size:clamp(28px,4vw,42px);
            line-height:1.1;
        }
        section h2::after{
            content:"";
            display:block;
            width:54px;
            height:3px;
            background:var(--gold);
            margin:12px auto 0;
        }

        /* ABOUT */
        .light{background:#f5f1e8;color:#151515}
        .about-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            align-items:center;
            gap:38px;
        }
        .about-text h3{font-size:28px;margin:0 0 12px}
        .about-text p{line-height:1.65;margin:0 0 12px;color:#444}
        .about-image img{
            width:100%;
            height:360px;
            object-fit:cover;
            border-radius:14px;
        }

        /* MENU */
        #menu{background:#0c0c0c}
        .menu-grid{
            display:grid;
            grid-template-columns:repeat(3,1fr);
            gap:18px;
        }
        .menu-card{
            position:relative;
            overflow:hidden;
            border:1px solid var(--line);
            background:var(--dark2);
            border-radius:12px;
            transition:.25s;
        }
        .menu-card:hover{transform:translateY(-5px);border-color:rgba(247,185,0,.55)}
        .menu-card-image{height:210px;overflow:hidden}
        .menu-card-image img{
            width:100%;height:100%;object-fit:cover;
            transition:.35s;
        }
        .menu-card:hover .menu-card-image img{transform:scale(1.05)}
        .menu-card-content{padding:17px}
        .menu-card-content h3{margin:0 0 7px;font-size:20px}
        .menu-card-content p{margin:0;color:#aaa;line-height:1.5;font-size:14px}
        .price{color:var(--gold);font-size:19px;font-weight:800;margin-top:12px}
        .order-now{
            position:absolute;top:14px;right:14px;z-index:3;padding:10px 14px;border:0;border-radius:8px;
            background:var(--gold);color:#17130a;font-weight:900;cursor:pointer;opacity:0;transform:translateY(-5px);
            transition:opacity .18s ease,transform .18s ease;
        }
        .menu-card:hover .order-now,.menu-card:focus-within .order-now{opacity:1;transform:translateY(0)}
        .customer-orders{background:#111;color:#fff}
        .customer-order-list{display:grid;gap:12px}
        .customer-order{display:grid;grid-template-columns:1fr auto;gap:8px 16px;padding:16px;border:1px solid var(--line);border-radius:10px;background:#181818}
        .customer-order strong{color:var(--gold2)}
        .customer-order p{grid-column:1/-1;margin:0;color:#ccc;line-height:1.5}
        .customer-order small{color:#aaa}
        .customer-order-status{display:inline-flex;padding:6px 10px;border-radius:999px;font-size:.76rem;font-weight:800}
        .customer-order-status.status-pending{background:rgba(255,180,84,.12);color:#ffc477}
        .customer-order-status.status-confirmed,.customer-order-status.status-preparing{background:rgba(56,211,159,.12);color:#76e4ba}
        .customer-order-status.status-ready,.customer-order-status.status-delivering{background:rgba(110,140,255,.15);color:#b3c3ff}
        .customer-order-status.status-delivered{background:rgba(56,211,159,.18);color:#a1f0d1}
        .customer-order-status.status-cancelled{background:rgba(255,107,107,.12);color:#ff9a9a}
        .customer-order-pay{justify-self:start;padding:8px 11px;border:1px solid var(--gold);border-radius:8px;background:var(--gold);color:#17130a;font-weight:800;cursor:pointer}
        .cart-button{position:relative;min-width:100px;padding:10px 15px;border:1px solid var(--gold);border-radius:8px;background:var(--gold);color:#16120a;font-weight:900;cursor:pointer}
        .cart-count{display:inline-grid;place-items:center;min-width:22px;height:22px;margin-left:5px;padding:0 5px;border-radius:99px;background:#17130a;color:#fff;font-size:12px}
        .order-toast{margin:0 0 14px;padding:11px 13px;border:1px solid #b73418;border-radius:8px;background:#ffe3d9;color:#701a08;font-weight:800}
        .order-modal-content{width:min(560px,100%)}
        .cart-lines{display:grid;gap:10px;margin:16px 0}
        .cart-line{display:grid;grid-template-columns:1fr auto;gap:8px;padding:12px 0;border-bottom:1px solid rgba(0,0,0,.12)}
        .cart-line-controls{display:flex;align-items:center;gap:8px}
        .cart-quantity-button{width:30px;height:30px;border:1px solid #b68a16;border-radius:6px;background:#fff8df;color:#231b06;font-weight:900;cursor:pointer}
        .cart-total{display:flex;justify-content:space-between;margin:16px 0;font-size:1.1rem;font-weight:900}
        .transfer-details{padding:15px;border:1px solid #e6bd39;border-radius:9px;background:#fff8df;line-height:1.7}
        .transfer-account{font-size:1.45rem;font-weight:900;letter-spacing:.04em}
        .order-modal-actions{display:flex;gap:10px;margin-top:16px}
        .order-modal-actions button{flex:1;min-height:44px;padding:10px;border:0;border-radius:8px;font:inherit;font-weight:900;cursor:pointer}
        .order-primary{background:#f7b900;color:#17130a}
        .order-secondary{background:#e9e2d0;color:#292416}
        .order-confirm-message{margin-top:12px;font-weight:800;line-height:1.45}

        /* WHY US */
        .why-us{background:#151515}
        .features-grid{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:16px;
        }
        .feature{
            text-align:center;
            padding:25px 16px;
            border:1px solid var(--line);
            border-radius:12px;
            background:#101010;
        }
        .feature-icon{font-size:34px;margin-bottom:10px}
        .feature h3{margin:0 0 7px}
        .feature p{margin:0;color:#aaa;line-height:1.5;font-size:14px}

        /* CONTACT */
        .contact-section{background:#f5f1e8;color:#161616}
        .contact-grid{
            display:grid;
            grid-template-columns:.9fr 1.1fr;
            gap:38px;
        }
        .contact-info h3{font-size:27px;margin:0 0 18px}
        .contact-item{margin-bottom:15px;line-height:1.6;color:#444}
        .contact-item strong{display:block;color:#111}
        .contact-form{
            background:#fff;
            padding:24px;
            border-radius:12px;
            box-shadow:0 8px 30px rgba(0,0,0,.08);
        }
        .form-group{margin-bottom:14px}
        .form-group label{display:block;font-weight:700;margin-bottom:6px}
        .form-group input,.form-group textarea{
            width:100%;
            border:1px solid #ddd;
            border-radius:7px;
            padding:11px 12px;
            font:inherit;
        }
        .form-group textarea{min-height:120px;resize:vertical}
        .contact-form .cta-button{width:100%}

        /* FOOTER */
        footer{background:#050505;padding:32px 0;text-align:center}
        .footer-content{width:min(900px,92%);margin:auto}
        .footer-content h3{color:var(--gold);margin:0 0 6px;font-size:25px}
        .footer-content p{color:#999;margin:6px 0}
        .social-links{display:flex;justify-content:center;gap:10px;margin:14px 0}
        .social-links a{
            width:34px;height:34px;display:grid;place-items:center;
            border:1px solid #444;border-radius:50%;color:#fff;
        }

        /* PRELOADER — short and no giant empty wait */
        .preloader{
            position:fixed;inset:0;z-index:9999;
            background:#080808;
            display:flex;flex-direction:column;
            align-items:center;justify-content:center;
            transition:opacity .35s,visibility .35s;
        }
        .preloader.hide{opacity:0;visibility:hidden;pointer-events:none}
        .preloader-logo{width:110px;margin:12px auto}
        .loader-text{color:#aaa}
        .timer{display:none}
        .loader-circle{display:none}

        /* MODALS */
        .modal{
            display:none;position:fixed;inset:0;z-index:3000;
            background:rgba(0,0,0,.78);
            align-items:center;justify-content:center;
            padding:20px;
        }
        .modal.show{display:flex}
        .modal-content{
            width:min(470px,100%);
            max-height:90vh;overflow:auto;
            background:linear-gradient(145deg,#fffdf5,#fff0bf);color:#17130a;
            border:2px solid #ffc928;
            border-radius:14px;padding:25px;
            position:relative;
            box-shadow:0 22px 70px rgba(255,190,0,.28);
        }
        .close{position:absolute;right:18px;top:10px;font-size:28px;cursor:pointer;color:#68531a}
        .modal-header{text-align:center}
        .modal-logo{width:90px;margin:auto}
        .modal-content h2{text-align:center;margin:8px 0;color:#201900}
        .modal-subtitle{text-align:center;color:#574a27}
        .modal-footer,.modal-link{text-align:center;color:#574a27}
        .modal-content a{color:#8a5800;font-weight:700}
        .btn-submit{
            width:100%;padding:12px;border:0;border-radius:7px;
            background:linear-gradient(135deg,#ffdc32,#ff9d00);color:#201600;font-weight:900;cursor:pointer;
            box-shadow:0 6px 18px rgba(235,151,0,.28);
        }
        .checkbox-group{display:flex;gap:8px;align-items:flex-start;margin:10px 0 15px}
        .checkbox-label{font-size:13px;color:#aaa}

        @media(max-width:1200px){
            .stats-grid{grid-template-columns:repeat(2,minmax(180px,1fr))}
            .dashboard-grid{grid-template-columns:1fr}
        }
        @media(max-width:1080px){
            nav{display:none}
            .hamburger{display:flex}
            .mobile-nav{display:flex}
            .menu-grid{grid-template-columns:repeat(2,1fr)}
            .features-grid{grid-template-columns:repeat(2,1fr)}
            .auth-buttons span{display:none}
        }
        @media(max-width:900px){
            .header-content{padding:18px 20px}
            .hero-title{font-size:clamp(32px,6vw,60px)}
            .hero-subtitle{font-size:clamp(14px,2vw,18px)}
            .section-content{width:95%}
        }
        @media(max-width:768px){
            .header-content{min-height:70px;padding:15px 18px}
            .logo span{display:none}
            .logo-image{width:45px;height:45px}
            .auth-buttons{gap:6px}
            .auth-buttons button{padding:7px 12px;font-size:11px}
            .hero{min-height:clamp(450px,70vh,600px)}
            .hero-content{padding:60px 0 20px}
            .hero-logo{width:120px;height:120px}
            .hero-title{font-size:clamp(28px,5vw,48px)}
            .hero-arrow{width:36px;height:36px}
            .hero-prev{left:15px}.hero-next{right:15px}
            .hero-dots{bottom:15px}
            section{padding:50px 0}
            .section-content{width:92%}
            h2{font-size:clamp(24px,5vw,36px)}
            .about-grid,.contact-grid{grid-template-columns:1fr;gap:30px}
            .menu-grid{grid-template-columns:repeat(2,1fr);gap:15px}
            .features-grid{grid-template-columns:repeat(2,1fr);gap:15px}
            .about-image img{height:300px}
            .menu-card-image{height:200px}
            .cta-button{padding:11px 20px;font-size:14px}
            #scrollToTop{bottom:20px;right:20px;width:40px;height:40px;font-size:18px}
        }
        @media(max-width:680px){
            .header-content{min-height:65px;padding:12px 16px}
            .logo-image{width:40px;height:40px}
            .auth-buttons button{padding:6px 10px;font-size:10px}
            .hero{min-height:clamp(400px,65vh,550px)}
            .hero-content{padding:50px 0 15px}
            .hero-logo{width:100px;height:100px}
            .hero-kicker{font-size:11px;letter-spacing:3px}
            .hero-title{font-size:clamp(24px,5vw,40px)}
            .hero-subtitle{font-size:clamp(13px,2vw,16px);max-width:90%}
            .hero-arrow{width:32px;height:32px}
            .hero-prev{left:10px}.hero-next{right:10px}
            .hero-dots{bottom:12px}
            .hero-dot{width:8px;height:8px}
            section{padding:40px 0}
            .section-content{width:90%}
            h2{font-size:clamp(22px,5vw,32px)}
            .about-grid,.contact-grid{grid-template-columns:1fr;gap:20px}
            .menu-grid{grid-template-columns:1fr;gap:20px}
            .features-grid{grid-template-columns:1fr;gap:20px}
            .about-image img{height:250px}
            .menu-card-image{height:220px}
            .order-now{opacity:1;transform:none}
            .order-modal-actions{flex-direction:column}
            .customer-order{grid-template-columns:1fr}
            .modal-content{width:95%;padding:20px}
            .form-group input,.form-group textarea{padding:10px 12px}
            #scrollToTop{bottom:15px;right:15px;width:38px;height:38px;font-size:16px}
        }
        @media(max-width:480px){
            .header-content{min-height:60px;padding:10px 14px}
            .logo-image{width:36px;height:36px}
            .auth-buttons{gap:4px}
            .auth-buttons button{padding:5px 8px;font-size:9px}
            .hero{min-height:clamp(350px,60vh,500px)}
            .hero-content{padding:40px 0 10px}
            .hero-logo{width:80px;height:80px}
            .hero-kicker{font-size:10px;letter-spacing:2px}
            .hero-title{font-size:clamp(20px,4vw,32px)}
            .hero-subtitle{font-size:clamp(12px,2vw,15px);max-width:95%}
            .hero-arrow{width:28px;height:28px}
            .hero-prev{left:8px}.hero-next{right:8px}
            .hero-dots{bottom:10px}
            .hero-dot{width:6px;height:6px}
            section{padding:35px 0}
            .section-content{width:88%}
            h2{font-size:clamp(20px,4vw,28px)}
            .about-text h3{font-size:22px}
            .menu-card{border-radius:12px}
            .menu-card-image{height:180px}
            .menu-card-content{padding:14px}
            .menu-card-content h3{font-size:16px}
            .cta-button{padding:10px 16px;font-size:13px}
            .modal-content{width:94%;padding:18px;margin:10px}
            .modal-logo{width:60px;height:60px}
            .modal-content h2{font-size:20px}
        }
        @media(max-width:360px){
            .header-content{min-height:55px;padding:8px 12px}
            .logo-image{width:32px;height:32px}
            .auth-buttons button{padding:4px 7px;font-size:8px}
            .hero-content{padding:30px 0 8px}
            .hero-logo{width:70px;height:70px}
            .hero-title{font-size:clamp(18px,4vw,28px)}
            .hero-subtitle{font-size:clamp(11px,2vw,14px)}
            section{padding:30px 0}
            .section-content{width:86%}
            h2{font-size:clamp(18px,4vw,24px)}
            .menu-card-content h3{font-size:15px}
            .menu-card-content p{font-size:13px}
            .price{font-size:16px}
            #scrollToTop{bottom:12px;right:12px;width:35px;height:35px;font-size:15px}
        }
        @media (hover:none){
            .order-now{opacity:1;transform:none}
        }
        @media (max-width:768px) and (orientation:landscape){
            .hero{min-height:450px}
            .hero-content{padding:30px 0}
        }
    </style>
</head>

<body>
    <!-- Short preloader -->
    <div class="preloader" id="preloader">
        <div class="loader-text">Welcome to</div>
       <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's" class="preloader-logo">
    </div>

    <!-- HEADER -->
    <header>
        <div class="header-content">
            <a href="index.php" class="logo">
                <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's Logo" class="logo-image">
                <span>Hungry Man's</span>
            </a>

            <nav id="main-nav">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="menu.php">Menu</a>
                <a href="reservations.php">Reservations</a>
                <a href="contact-page.php">Contact</a>
            </nav>

            <!-- Mobile Navigation Menu -->
            <div class="mobile-nav" id="mobileNav">
                <a href="index.php">Home</a>
                <a href="about.php">About</a>
                <a href="menu.php">Menu</a>
                <a href="reservations.php">Reservations</a>
                <a href="contact-page.php">Contact</a>
                <?php if ($userLoggedIn): ?>
                    <a href="#my-orders">Your Orders</a>
                    <a href="auth.php?action=logout">Log Out</a>
                <?php else: ?>
                    <a href="#" onclick="openLogIn()">Log In</a>
                    <a href="#" onclick="openSignIn()">Sign In</a>
                <?php endif; ?>
            </div>

            <div class="auth-buttons">
                <button id="cartOpenButton" class="cart-button" type="button" aria-label="Open shopping cart">Cart <span id="cartCount" class="cart-count">0</span></button>
                <button class="hamburger" id="hamburger" type="button" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <?php if ($userLoggedIn): ?>
                    <span style="color:#fff;align-self:center;">Hi, <?php echo htmlspecialchars($userFirstName); ?></span>
                    <a href="#my-orders" class="btn-login" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 15px;border:1px solid var(--gold);border-radius:7px;font-weight:700;">Your orders</a>
                    <a href="reservations.php" class="btn-login" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 15px;border:1px solid var(--gold);border-radius:7px;font-weight:700;">Reservations</a>
                    <a href="auth.php?action=logout" class="btn-signin" style="display:inline-flex;align-items:center;justify-content:center;padding:9px 15px;border:1px solid var(--gold);border-radius:7px;font-weight:700;">Log Out</a>
                <?php else: ?>
                    <button class="btn-signin" onclick="openSignIn()">Sign In</button>
                    <button class="btn-login" onclick="openLogIn()">Log In</button>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- 3-SLIDE HERO -->
    <section class="hero" id="home">
        <div class="hero-track" id="heroTrack">

            <!-- SLIDE 1: SITE BANNER -->
            <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=1800&h=1000&fit=crop')">
                <div class="hero-content">
                    <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's Restaurant" class="hero-logo">
                    <p class="hero-kicker">Welcome to Hungry Man's</p>
                    <h1 class="hero-title">Good Food <span>•</span> Great Vibes</h1>
                    <p class="hero-subtitle">A place for delicious meals, warm moments and unforgettable dining experiences.</p>
                    <button class="cta-button" onclick="smoothScroll('menu')">Explore Our Menu</button>
                </div>
            </div>

            <!-- SLIDE 2 -->
            <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1544025162-d76694265947?w=1800&h=1000&fit=crop')">
                <div class="hero-content">
                    <p class="hero-kicker">Fresh from the Kitchen</p>
                    <h1 class="hero-title">Taste That <span>Brings You Back</span></h1>
                    <p class="hero-subtitle">Beautifully prepared dishes made for hungry people who love great food.</p>
                    <button class="cta-button" onclick="smoothScroll('menu')">See Featured Dishes</button>
                </div>
            </div>

            <!-- SLIDE 3 -->
            <div class="hero-slide" style="background-image:url('https://images.unsplash.com/photo-1559339352-11d035aa65de?w=1800&h=1000&fit=crop')">
                <div class="hero-content">
                    <p class="hero-kicker">Come Hungry. Leave Happy.</p>
                    <h1 class="hero-title">Your Table <span>Is Waiting</span></h1>
                    <p class="hero-subtitle">Great meals, a welcoming atmosphere and service that makes every visit special.</p>
                    <button class="cta-button" onclick="smoothScroll('contact')">Visit Us</button>
                </div>
            </div>
        </div>

        <button class="hero-arrow hero-prev" id="heroPrev" aria-label="Previous slide">‹</button>
        <button class="hero-arrow hero-next" id="heroNext" aria-label="Next slide">›</button>

        <div class="hero-dots">
            <button class="hero-dot active" data-slide="0" aria-label="Slide 1"></button>
            <button class="hero-dot" data-slide="1" aria-label="Slide 2"></button>
            <button class="hero-dot" data-slide="2" aria-label="Slide 3"></button>
        </div>
    </section>

    <!-- ABOUT -->
    <section class="light" id="about">
        <div class="section-content">
            <h2>Our Story</h2>
            <div class="about-grid">
                <div class="about-text">
                    <h3>Crafted with Passion</h3>
                    <p>Since 2015, Hungry Man's has been delivering exceptional dining experiences with carefully selected ingredients and time-honored recipes.</p>
                    <p>Our chefs combine traditional cooking techniques with modern ideas to make every meal memorable.</p>
                    <p>Join us for breakfast, lunch or dinner and discover your new favorite place to eat.</p>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1495521821757-a1efb6729352?w=800&h=600&fit=crop" alt="Chef preparing fresh food">
                </div>
            </div>
        </div>
    </section>

    <!-- MENU -->
    <section id="menu">
        <div class="section-content">
            <h2>Featured Dishes</h2>
            <div class="menu-grid">
                <?php foreach ($featuredMenuItems as $item): ?>
                    <article class="menu-card" data-menu-id="<?php echo (int)$item['id']; ?>" data-menu-name="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>" data-menu-price="<?php echo (float)$item['price']; ?>">
                        <button class="order-now" type="button" aria-label="Order <?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>">Order now</button>
                        <div class="menu-card-image"><img src="<?php echo htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"></div>
                        <div class="menu-card-content">
                            <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo htmlspecialchars($item['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="price">₦<?php echo number_format((float)$item['price']); ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php if ($userLoggedIn): ?>
        <section class="customer-orders" id="my-orders">
            <div class="section-content">
                <h2>Your orders</h2>
                <?php if (!$customerOrders): ?>
                    <p style="text-align:center;color:#aaa;">Your orders will appear here after checkout.</p>
                <?php else: ?>
                    <div class="customer-order-list">
                        <?php foreach ($customerOrders as $order): ?>
                            <article class="customer-order" data-customer-order-id="<?php echo (int)$order['id']; ?>">
                                <strong><?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="status-pill customer-order-status status-<?php echo strtolower(htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8')); ?>"><?php echo htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <p><?php echo htmlspecialchars($order['item_name'], ENT_QUOTES, 'UTF-8'); ?> · ₦<?php echo number_format((float)$order['total_amount']); ?></p>
                                <small>Payment: <span class="customer-payment-status"><?php echo htmlspecialchars(match ($order['payment_status']) { 'confirmed' => 'Transfer confirmed', 'submitted' => 'Awaiting transfer verification', 'rejected' => 'Transfer not confirmed', default => 'Not sent yet' }, ENT_QUOTES, 'UTF-8'); ?></span></small>
                                <?php if (in_array($order['payment_status'], ['not_sent', 'rejected'], true)): ?>
                                    <button type="button" class="customer-order-pay" data-order-id="<?php echo (int)$order['id']; ?>" data-order-number="<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES, 'UTF-8'); ?>" data-order-total="<?php echo (float)$order['total_amount']; ?>">Pay now</button>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- WHY US -->
    <section class="why-us" id="why-us">
        <div class="section-content">
            <h2>Why Choose Us</h2>
            <div class="features-grid">
                <div class="feature"><div class="feature-icon">👨‍🍳</div><h3>Expert Chefs</h3><p>Skilled chefs focused on flavor and quality.</p></div>
                <div class="feature"><div class="feature-icon">🌱</div><h3>Fresh Ingredients</h3><p>Fresh ingredients selected for every dish.</p></div>
                <div class="feature"><div class="feature-icon">✨</div><h3>Great Service</h3><p>Friendly staff dedicated to your experience.</p></div>
                <div class="feature"><div class="feature-icon">🏆</div><h3>Quality First</h3><p>Food and hospitality you can come back for.</p></div>
            </div>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="contact-section" id="contact">
        <div class="section-content">
            <h2>Get In Touch</h2>
            <div class="contact-grid">
                <div class="contact-info">
                    <h3>Visit Hungry Man's</h3>
                    <div class="contact-item"><strong>Location</strong><?php echo nl2br(htmlspecialchars($restaurantSettings['address'] ?? '123 Gourmet Street, Downtown District, Restaurant Quarter', ENT_QUOTES, 'UTF-8')); ?></div>
                    <div class="contact-item"><strong>Hours</strong><?php echo nl2br(htmlspecialchars($restaurantSettings['opening_hours'] ?? 'Mon - Thu: 11:00 AM - 10:00 PM', ENT_QUOTES, 'UTF-8')); ?></div>
                    <div class="contact-item"><strong>Phone</strong><?php echo htmlspecialchars($restaurantSettings['contact_phone'] ?? '+234 000 000 0000', ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="contact-item"><strong>Email</strong><?php echo htmlspecialchars($restaurantSettings['contact_email'] ?? 'hello@hungrymans.com', ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <form class="contact-form" id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['contact_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group"><label for="name">Your Name</label><input type="text" id="name" name="name" required></div>
                    <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
                    <div class="form-group"><label for="subject">Subject</label><input type="text" id="subject" name="subject" required></div>
                    <div class="form-group"><label for="message">Message</label><textarea id="message" name="message" required></textarea></div>
                    <button type="submit" class="cta-button">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer>
        <div class="footer-content">
            <img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's" style="width:70px;margin:0 auto 8px;">
            <h3>Hungry Man's</h3>
            <p>Good Food • Great Vibes</p>
            <div class="social-links">
                <a href="#" title="Facebook">f</a>
                <a href="#" title="Instagram">@</a>
                <a href="#" title="Twitter">𝕏</a>
            </div>
            <p>&copy; 2026 Hungry Man's. All rights reserved.</p>
        </div>
    </footer>

    <div id="orderModal" class="modal" aria-hidden="true">
        <div class="modal-content order-modal-content">
            <button type="button" class="close" id="closeOrderModal" aria-label="Close order window">&times;</button>
            <h2 id="orderModalTitle">Your cart</h2>
            <p class="modal-subtitle" id="orderModalSubtitle">Review your items before confirming.</p>
            <div id="orderToast" class="order-toast" role="alert" hidden></div>
            <div id="cartView">
                <div id="cartLines" class="cart-lines"></div>
                <div class="cart-total"><span>Total</span><span id="cartTotal">₦0</span></div>
                <button type="button" id="confirmOrderButton" class="btn-submit">Confirm order</button>
            </div>
            <div id="transferView" hidden>
                <p id="createdOrderLabel" style="font-weight:800;"></p>
                <div class="transfer-details">
                    <strong>Bank transfer details</strong><br>
                    Bank: OPay<br>
                    Account name: Sylvia Okhilua<br>
                    Account number: <span class="transfer-account">9165804995</span><br>
                    Amount: <strong id="transferAmount"></strong>
                </div>
                <p id="transferInstructions" class="modal-subtitle">After transferring the exact amount, select “I have sent it”. The restaurant will verify the transfer before confirming your order.</p>
                <div class="order-modal-actions">
                    <button type="button" id="transferSentButton" class="order-primary">I have sent it</button>
                    <button type="button" id="transferNotSentButton" class="order-secondary">Not yet</button>
                </div>
                <p id="transferResponse" class="order-confirm-message" role="status" aria-live="polite"></p>
            </div>
        </div>
    </div>

    <!-- Sign In Modal -->
    <div id="signInModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeSignIn()">&times;</span>
            <div class="modal-header"><img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's" class="modal-logo"></div>
            <p class="auth-message" id="signup-message" role="alert" aria-live="polite"></p>
            <h2>Create Account</h2>
            <p class="modal-subtitle">Join us for exclusive offers and reservations</p>
            <form class="auth-form" id="signInForm">
                <div class="form-group"><label for="signup-first-name">First Name</label><input type="text" id="signup-first-name" name="first_name" autocomplete="given-name" required></div>
                <div class="form-group"><label for="signup-last-name">Last Name</label><input type="text" id="signup-last-name" name="last_name" autocomplete="family-name" required></div>
                <div class="form-group"><label for="signup-email">Email Address</label><input type="email" id="signup-email" name="email" required></div>
                <div class="form-group"><label for="signup-password">Password</label><input type="password" id="signup-password" name="password" autocomplete="new-password" required></div>
                <div class="form-group"><label for="signup-confirm">Confirm Password</label><input type="password" id="signup-confirm" name="confirm_password" autocomplete="new-password" required></div>
                <button type="submit" class="btn-submit">Create Account</button>
            </form>
            <p class="modal-footer">Already have an account? <a href="#" onclick="switchToLogin()">Log In</a></p>
        </div>
    </div>

    <!-- Log In Modal -->
    <div id="logInModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeLogIn()">&times;</span>
            <div class="modal-header"><img src="images/Hungry%20Man%E2%80%99s%20Restaurant%20Logo.png" alt="Hungry Man's" class="modal-logo"></div>
            <h2>Welcome Back</h2>
            <p class="modal-subtitle">Log in to your Hungry Man's account</p>
            <p class="auth-message" id="login-message" role="alert" aria-live="polite"></p>
            <form class="auth-form" id="logInForm">
                <div class="form-group"><label for="login-email">Email Address</label><input type="email" id="login-email" name="email" required></div>
                <div class="form-group"><label for="login-password">Password</label><input type="password" id="login-password" name="password" required></div>
                <div class="checkbox-group"><input type="checkbox" id="remember" name="remember"><label for="remember" class="checkbox-label">Remember me</label></div>
                <button type="submit" class="btn-submit">Log In</button>
            </form>
            <p class="modal-link"><a href="#">Forgot your password?</a></p>
            <p class="modal-footer">Don't have an account? <a href="#" onclick="switchToSignIn()">Create Account</a></p>
        </div>
    </div>

    <script>
        const authReturnTo = new URLSearchParams(window.location.search).get('return') === 'reservations' ? 'reservations.php' : 'index.php';
        const authCsrfToken = <?php echo json_encode($_SESSION['auth_csrf'] ?? ''); ?>;
        const orderCsrfToken = <?php echo json_encode($_SESSION['order_csrf']); ?>;
        const isCustomerSignedIn = <?php echo json_encode($userLoggedIn); ?>;

        /* 3-slide hero */
        const heroTrack = document.getElementById('heroTrack');
        const dots = document.querySelectorAll('.hero-dot');
        let currentSlide = 0;
        let sliderTimer;

        function showSlide(index){
            currentSlide = (index + 3) % 3;
            heroTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
            dots.forEach((dot,i)=>dot.classList.toggle('active',i===currentSlide));
        }
        function nextSlide(){ showSlide(currentSlide + 1); }
        function startSlider(){
            clearInterval(sliderTimer);
            sliderTimer = setInterval(nextSlide,5000);
        }
        document.getElementById('heroNext').addEventListener('click',()=>{nextSlide();startSlider()});
        document.getElementById('heroPrev').addEventListener('click',()=>{showSlide(currentSlide-1);startSlider()});
        dots.forEach(dot=>dot.addEventListener('click',()=>{showSlide(Number(dot.dataset.slide));startSlider()}));
        startSlider();

        /* Short preloader */
        window.addEventListener('load',()=>{
            setTimeout(()=>document.getElementById('preloader')?.classList.add('hide'),450);
        });

        /* Smooth scroll fallback */
        function smoothScroll(id){
            document.getElementById(id)?.scrollIntoView({behavior:'smooth'});
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (new URLSearchParams(window.location.search).get('auth') === 'login') {
                openLogIn();
            }
        });

        /* Hamburger menu toggle */
        const hamburger = document.getElementById('hamburger');
        const mobileNav = document.getElementById('mobileNav');
        if (hamburger && mobileNav) {
            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                mobileNav.classList.toggle('active');
            });
        }

        /* Close mobile nav when clicking a link */
        const mobileNavLinks = mobileNav?.querySelectorAll('a');
        if (mobileNavLinks) {
            mobileNavLinks.forEach(link => {
                link.addEventListener('click', () => {
                    hamburger.classList.remove('active');
                    mobileNav.classList.remove('active');
                });
            });
        }

        /* Scroll to top button */
        const scrollToTopBtn = document.getElementById('scrollToTop');
        if (scrollToTopBtn) {
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) {
                    scrollToTopBtn.style.display = 'flex';
                } else {
                    scrollToTopBtn.style.display = 'none';
                }
            });

            scrollToTopBtn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    </script>

    <style>
        .auth-message {
            display: none;
            padding: 13px 14px;
            margin: 4px 0 12px;
            border: 2px solid #d63b19;
            border-radius: 9px;
            background: #ffe2d8;
            color: #761700;
            font-weight: 800;
            line-height: 1.45;
            box-shadow: 0 4px 14px rgba(214,59,25,.14);
        }
        .auth-message.visible { display: block; }
        .auth-message.success {
            border-color: #148448;
            background: #dcffeb;
            color: #075329;
        }
    </style>

    <style>
        #chatbot {
            position: fixed;
            right: 22px;
            bottom: 22px;
            z-index: 99999;
            font-family: Arial, Helvetica, sans-serif;
        }

        #chatbot-toggle {
            width: 64px;
            height: 64px;
            border: none;
            border-radius: 50%;
            background: #f7b900;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.25);
            transition: transform 0.2s ease;
        }

        #chatbot-toggle:hover {
            transform: translateY(-2px) scale(1.03);
        }

        #chatbot-toggle svg {
            width: 28px;
            height: 28px;
        }

        #chatbot-panel {
            display: none;
            width: min(360px, calc(100vw - 30px));
            margin-bottom: 16px;
            background: #181818;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255,255,255,0.08);
        }

        #chatbot.open #chatbot-panel {
            display: block;
        }

        #chatbot-header {
            background: linear-gradient(135deg, #1f1f1f, #101010);
            padding: 16px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
        }

        .chatbot-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #fff;
            font-size: 15px;
        }

        .chatbot-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #34d399;
            display: inline-block;
            box-shadow: 0 0 12px rgba(52, 211, 153, 0.9);
        }

        .chatbot-sub {
            margin-top: 8px;
            color: #d5d5d5;
            font-size: 12px;
            line-height: 1.4;
        }

        #chatbot-messages {
            background: #f7f7f7;
            padding: 16px;
            max-height: 300px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .msg {
            max-width: 85%;
            padding: 10px 12px;
            border-radius: 12px;
            font-size: 14px;
            line-height: 1.4;
            word-break: break-word;
        }

        .msg.bot {
            background: #e9e9e9;
            color: #111;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .msg.user {
            background: #f7b900;
            color: #111;
            align-self: flex-end;
            border-bottom-right-radius: 4px;
        }

        #chatbot-form {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            padding: 10px 12px;
            border-top: 1px solid rgba(0, 0, 0, 0.08);
        }

        #chatbot-input {
            flex: 1;
            border: 1px solid #ddd;
            border-radius: 999px;
            padding: 10px 14px;
            font-size: 14px;
            outline: none;
        }

        #chatbot-input:focus {
            border-color: #f7b900;
            box-shadow: 0 0 0 3px rgba(247, 185, 0, 0.14);
        }

        #chatbot-send {
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: #f7b900;
            color: #111;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        #chatbot-send svg {
            width: 18px;
            height: 18px;
        }

        .typing-indicator {
            display: flex;
            gap: 6px;
            padding: 10px 12px;
            background: #e9e9e9;
            border-radius: 12px;
            width: fit-content;
            align-self: flex-start;
            border-bottom-left-radius: 4px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #777;
            animation: typing 1s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) { animation-delay: 0.15s; }
        .typing-dot:nth-child(3) { animation-delay: 0.3s; }

        @keyframes typing {
            0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
            40% { transform: scale(1); opacity: 1; }
        }
    </style>

    <?php include 'chatbot.php'; ?>

    <!-- Scroll to Top Button -->
    <button id="scrollToTop" aria-label="Scroll to top" style="display:none;position:fixed;bottom:30px;right:30px;width:45px;height:45px;border-radius:50%;background:var(--gold);color:#111;border:none;cursor:pointer;font-size:20px;font-weight:900;box-shadow:0 4px 15px rgba(247,185,0,0.4);z-index:1000;transition:all 0.3s;">↑</button>

    <!-- Keep your existing site functionality -->
    <script src="script.js" defer></script>
    <script src="orders.js" defer></script>
    <script src="chatbot.js"></script>
</body>
</html>
