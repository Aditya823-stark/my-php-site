<?php
// Start session for user management
session_start();

// Include database connection
include('connect/db.php');
include('connect/fun.php');

// Initialize database connection
try {
    $db = (new connect())->myconnect();
    $fun = new fun($db);
    $connection_status = "success";
} catch (Exception $e) {
    $connection_status = "error";
    $error_message = $e->getMessage();
}

// Fetch website customization settings
$customization = [];
$carousel_slides = [];
$active_alerts = [];

if ($connection_status == "success") {
    // Fetch website customization
    $customization_query = "SELECT * FROM website_customization WHERE id = 1 LIMIT 1";
    $customization_result = mysqli_query($db, $customization_query);
    if ($customization_result && mysqli_num_rows($customization_result) > 0) {
        $customization = mysqli_fetch_assoc($customization_result);
    } else {
        // Default IRCTC values
        $customization = [
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#f97316', 
            'accent_color' => '#059669',
            'site_title' => 'IRCTC Rail Connect',
            'logo_url' => 'IRCTC-logo1.png',
            'hero_image_url' => 'assets/images/slider/slider1.jpg',
            'contact_phone' => '139',
            'contact_email' => 'care@irctc.co.in'
        ];
    }
    
    // Fetch carousel slides
    $carousel_query = "SELECT * FROM carousel_slides ORDER BY sort_order ASC";
    $carousel_result = mysqli_query($db, $carousel_query);
    if ($carousel_result) {
        while ($slide = mysqli_fetch_assoc($carousel_result)) {
            $carousel_slides[] = $slide;
        }
    }
    
    // Fetch active alerts
    $current_time = date('Y-m-d H:i:s');
    $alerts_query = "SELECT * FROM train_alerts 
                    WHERE is_active = 1 
                    AND start_date <= '$current_time' 
                    AND end_date >= '$current_time' 
                    ORDER BY created_at DESC";
    $alerts_result = mysqli_query($db, $alerts_query);
    if ($alerts_result) {
        while ($row = mysqli_fetch_assoc($alerts_result)) {
            $active_alerts[] = $row;
        }
    }
    
    // If no carousel slides, use defaults
    if (empty($carousel_slides)) {
        $carousel_slides = [
            [
                'image_url' => 'assets/images/train1.jpg',
                'title' => 'Second slide',
                'description' => 'Some representative placeholder content for the second slide.',
                'button_text' => 'Book Now',
                'button_link' => '#booking'
            ],
            [
                'image_url' => 'assets/images/train2.jpg', 
                'title' => 'Third slide',
                'description' => 'Some representative placeholder content for the third slide.',
                'button_text' => 'Explore',
                'button_link' => '#services'
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <style>
        /* Services Section */
        .services-section {
            padding: 60px 0;
            background-color: #f8f9fa;
        }
        
        .services-section .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .services-section h2 {
            font-size: 2.2rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 15px;
        }
        
        .services-section .lead {
            color: #666;
            font-size: 1.25rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 40px;
        }
        
        .services-section .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            margin: 0 -10px;
        }
        
        .services-section [class*="col-"] {
            padding: 0 10px;
            margin-bottom: 20px;
        }
        
        .service-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
            transition: transform 0.3s ease;
        }
        
        .service-link:hover {
            transform: translateY(-5px);
            text-decoration: none;
        }
        
        .service-card {
            background: white;
            border-radius: 10px;
            padding: 25px 15px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .service-card:hover {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .service-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            color: white;
            font-size: 24px;
        }
        
        .service-card h5 {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        
        /* Alert Ticker Styles */
        .alert-ticker {
            background-color: #f8f9fa;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
            margin-bottom: 0;
        }
        
        .ticker-header {
            background-color: #1e3a8a;
            color: white;
            padding: 6px 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .ticker-header i {
            color: #ffc107;
        }
        
        .alert-marquee {
            background-color: #f8f9fa;
            padding: 10px 0;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .marquee-content {
            display: flex;
            white-space: nowrap;
            padding-left: 100%;
            animation: scroll 20s linear infinite;
        }
        
        .marquee-container {
            display: flex;
            white-space: nowrap;
        }
        
        .alert-item {
            display: inline-flex;
            align-items: center;
            padding: 5px 15px;
            margin-right: 20px;
            background-color: white;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-100%); }
        }
        
        .alert-marquee:hover .marquee-content {
            animation-play-state: paused;
        }
        
        .ticker-item {
            display: inline-flex;
            margin: 0 15px;
            padding: 5px 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            white-space: nowrap;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex-shrink: 0;
        }
        
        .ticker-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .ticker-item .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        @keyframes ticker {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        
        /* Pause animation on hover */
        .ticker-track:hover {
            animation-play-state: paused;
        }
        
        /* Alert type specific styles */
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-delay {
            background-color: #fff3e6;
            border-color: #ffe0b3;
            color: #663300;
        }
        
        .alert-cancellation {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-diversion {
            background-color: #e6e6ff;
            border-color: #ccccff;
            color: #333399;
        }
        
        /* Badge colors for alert types */
        .bg-delay {
            background-color: #ff9800 !important;
        }
        
        .bg-cancellation {
            background-color: #dc3545 !important;
        }
        
        .bg-diversion {
            background-color: #6f42c1 !important;
        }
        
        /* Base Styles */
        :root {
            --primary-color: <?php echo $customization['primary_color'] ?? '#1e3a8a'; ?>;
            --secondary-color: <?php echo $customization['secondary_color'] ?? '#f97316'; ?>;
            --accent-color: <?php echo $customization['accent_color'] ?? '#059669'; ?>;
            --irctc-blue: var(--primary-color);
            --irctc-orange: var(--secondary-color);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            padding-top: 140px !important;
        }
        
        /* Top Header */
        .irctc-top-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 8px 0;
            font-size: 13px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .irctc-top-header .container-fluid {
            padding: 0 20px;
        }
        
        .irctc-top-header .left-section {
            display: flex;
            align-items: center;
        }
        
        .irctc-top-header .logo-section {
            display: flex;
            align-items: center;
            margin-right: 30px;
        }
        
        .irctc-top-header .logo-section img {
            height: 60px;
            width: auto;
            transition: transform 0.3s ease;
        }
        
        .irctc-top-header .logo-section img:hover {
            transform: scale(1.05);
        }
        
        .irctc-top-header .nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .irctc-top-header .nav-buttons .btn {
            padding: 6px 15px;
            font-size: 12px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .irctc-top-header .nav-buttons .btn-primary {
            background: var(--irctc-orange);
            border-color: var(--irctc-orange);
            color: white;
        }
        
        .irctc-top-header .nav-buttons .btn-primary:hover {
            background: #ea580c;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .irctc-top-header .nav-buttons .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        /* Dropdown Menu Styling */
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 10px 0;
            min-width: 280px;
            margin-top: 10px;
            animation: slideIn 0.2s ease-out;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .dropdown-item {
            padding: 8px 20px;
            transition: all 0.2s ease;
            font-size: 13px;
            color: #333;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #f8f9fa;
            color: var(--primary-color);
            transform: translateX(5px);
            padding-left: 25px;
        }
        
        .dropdown-divider {
            margin: 8px 0;
            border-top: 1px solid rgba(0,0,0,0.05);
        }
        
        .dropdown-header {
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            padding: 5px 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .dropdown-item.unread {
            background-color: #f8f9fa;
            border-left: 3px solid var(--primary-color);
        }
        
        .dropdown-item .badge {
            font-size: 10px;
            padding: 3px 6px;
            margin-left: 5px;
        }
        
        .irctc-top-header .right-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .daily-deals {
            background: var(--irctc-orange);
            color: white !important;
            padding: 6px 15px 6px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            animation: pulse 2s infinite;
            transition: all 0.3s ease;
            position: relative;
            display: inline-flex;
            align-items: center;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -5px;
            background-color: #ff4d4d;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 1.5s infinite;
            border: 2px solid #fff;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .daily-deals:hover {
            background: #ea580c;
            transform: scale(1.05);
            text-decoration: none;
            color: white !important;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(249, 115, 22, 0); }
            100% { box-shadow: 0 0 0 0 rgba(249, 115, 22, 0); }
        }
        
        .date-time, .font-controls, .hindi-text {
            font-size: 13px;
            color: rgba(255,255,255,0.9);
            transition: all 0.3s ease;
        }
        
        .hindi-text {
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        
        .hindi-text:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .font-controls a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            margin: 0 2px;
            padding: 2px 6px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .font-controls a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        /* Main Navigation */
        .main-navbar {
            background: linear-gradient(135deg, #ffffff, #f8fafc);
            padding: 0;
            position: fixed;
            top: 76px;
            left: 0;
            right: 0;
            z-index: 1040;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-bottom: 3px solid var(--irctc-orange);
        }
        
        .main-navbar .container-fluid {
            padding: 0 20px;
        }
        
        .main-navbar .navbar-nav {
            width: 100%;
            justify-content: space-between;
            align-items: center;
        }
        
        .main-navbar .nav-link {
            color: #374151;
            font-weight: 500;
            padding: 15px 20px;
            text-decoration: none;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .main-navbar .nav-link:hover {
            color: var(--irctc-blue);
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.1));
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.2);
        }
        
        .main-navbar .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 3px;
            background: var(--irctc-orange);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        
        .main-navbar .nav-link:hover::after {
            width: 80%;
        }
        
        .main-navbar .nav-link.active {
            color: var(--irctc-blue);
            background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.1));
            font-weight: 600;
        }
        
        .main-navbar .nav-link.active::after {
            width: 80%;
        }
        
        .main-navbar .nav-link.trains {
            background: var(--irctc-orange);
            color: white;
            font-weight: 600;
        }
        
        .main-navbar .nav-link.trains:hover {
            background: #ea580c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.3);
        }
        
        .home-icon {
            background: var(--irctc-blue);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .home-icon:hover {
            background: #1e40af;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        /* Carousel Styling */
        .carousel {
            margin-top: 0;
            height: 75vh;
            overflow: hidden;
        }
        
        .carousel-item {
            height: 75vh;
            background: #666;
            color: white;
            position: relative;
        }
        
        .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.4);
            z-index: 1;
        }
        
        .carousel-caption {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 2;
            text-align: center;
            width: 80%;
        }
        
        .carousel-caption h5 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .carousel-caption p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            transition: all 0.3s ease;
        }
        
        .carousel-control-prev:hover,
        .carousel-control-next:hover {
            background: rgba(0,0,0,0.1);
        }
        
        /* Modern Rectangular Carousel Indicators */
        .carousel-indicators {
            position: absolute;
            right: 0;
            bottom: 25px;
            left: 0;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            margin: 0 15% 1.5rem;
            list-style: none;
            gap: 10px;
        }
        
        .carousel-indicators [data-bs-target] {
            box-sizing: border-box;
            flex: 0 0 40px;
            height: 4px;
            padding: 0;
            margin: 0;
            text-indent: -999px;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.4);
            border: none;
            border-radius: 2px;
            opacity: 0.7;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
        }
        
        .carousel-indicators [data-bs-target]:hover {
            background-color: var(--secondary-color);
            opacity: 1;
            transform: scaleY(1.5);
        }
        
        .carousel-indicators .active {
            flex: 0 0 60px;
            height: 4px;
            background-color: var(--primary-color);
            border: none;
            transform: none;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
            opacity: 1;
        }
        
        /* Services Section */
        .services-section {
            padding: 80px 0;
            background: #f8fafc;
        }
        
        .services-section h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #1f2937;
            font-weight: 700;
            font-size: 2.5rem;
        }
        
        .services-section p {
            text-align: center;
            margin-bottom: 60px;
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .service-card {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 30px;
            border: 2px solid transparent;
        }
        
        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border-color: var(--irctc-orange);
        }
        
        .service-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border: 3px solid #e5e7eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        
        .service-card:hover .service-icon {
            border-color: var(--irctc-orange);
            color: var(--irctc-orange);
            transform: scale(1.1);
        }
        
        .service-card h5 {
            font-weight: 600;
            margin-bottom: 15px;
            color: #1f2937;
        }
        
        .service-card p {
            color: #6b7280;
            margin-bottom: 0;
            text-align: center;
        }
        
        /* Footer Styling */
        .footer-section {
            background: linear-gradient(135deg, #4c1d95, #5b21b6);
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        
        .footer-top {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .footer-top h6 {
            color: white;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .social-icons {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
            font-size: 18px;
        }
        
        .social-icon.facebook { background: #3b5998; }
        .social-icon.whatsapp { background: #25d366; }
        .social-icon.youtube { background: #ff0000; }
        .social-icon.instagram { background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); }
        .social-icon.linkedin { background: #0077b5; }
        .social-icon.telegram { background: #0088cc; }
        .social-icon.pinterest { background: #bd081c; }
        .social-icon.snapchat { background: #fffc00; color: #333; }
        .social-icon.twitter { background: #1da1f2; }
        
        .social-icon:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            color: white;
        }
        
        .social-icon.snapchat:hover {
            color: #333;
        }
        
        .footer-content {
            margin-bottom: 30px;
        }
        
        .footer-column h6 {
            color: white;
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .footer-column ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-column ul li {
            margin-bottom: 8px;
        }
        
        .footer-column ul li a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 12px;
            transition: all 0.3s ease;
        }
        
        .footer-column ul li a:hover {
            color: var(--irctc-orange);
            padding-left: 5px;
        }
        
        .footer-payment {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding-top: 20px;
        }
        
        .payment-logos {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .payment-logo {
            height: 30px;
            width: auto;
            background: white;
            padding: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .payment-logo:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .footer-copyright {
            text-align: right;
        }
        
        .footer-copyright p {
            margin: 0;
            font-size: 11px;
            color: rgba(255,255,255,0.7);
            line-height: 1.4;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .irctc-top-header .logo-section img {
                height: 50px;
            }
            
            .carousel-caption h5 {
                font-size: 2.5rem;
            }
        }
        
        @media (max-width: 992px) {
            body {
                padding-top: 120px !important;
            }
            
            .irctc-top-header .logo-section img {
                height: 45px;
            }
            
            .main-navbar .nav-link {
                padding: 12px 15px;
            }
            
            .carousel-caption h5 {
                font-size: 2rem;
            }
            
            .carousel-caption p {
                font-size: 1rem;
            }
        }
        
        @media (max-width: 768px) {
            body {
                padding-top: 100px !important;
            }
            
            .irctc-top-header {
                padding: 5px 0;
            }
            
            .irctc-top-header .logo-section img {
                height: 40px;
            }
            
            .main-navbar .nav-link {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .carousel-caption h5 {
                font-size: 1.5rem;
            }
            
            .carousel-caption p {
                font-size: 0.9rem;
            }
            
            .service-card {
                padding: 30px 15px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding-top: 90px !important;
            }
            
            .irctc-top-header .container-fluid {
                padding: 0 10px;
            }
            
            .main-navbar .container-fluid {
                padding: 0 10px;
            }
            
            .main-navbar .nav-link {
                padding: 8px 10px;
                font-size: 12px;
            }
            
            .carousel-caption {
                width: 90%;
            }
            
            .carousel-caption h5 {
                font-size: 1.2rem;
            }
            
            .carousel-caption p {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Language Data (Hidden) -->
    <div id="language-data" 
         data-en='{
            // Navigation
            "login":"LOGIN", "register":"REGISTER", "agentLogin":"AGENT LOGIN", "contactUs":"CONTACT US",
            "haveAccount":"Have an account?", "loginHere":"Login here",
            
            // Services Section
            "findService":"Have you not found the right one?",
            "findSuitable":"Find a service suitable for you here.",
            "flightTickets":"Flight Tickets",
            "hotelBooking":"Hotel Booking",
            "railDrishti":"Rail Drishti",
            "eCatering":"E-Catering",
            "busTickets":"Bus Tickets",
            "holidayPackages":"Holiday Packages",
            "touristTrain":"Tourist Train",
            "hillRailways":"Hill Railways",
            "charterTrain":"Charter Train",
            "gallery":"Gallery",
            
            // Why Choose Section
            "whyChoose":"Why Choose IRCTC?",
            "experienceTravel":"Experience seamless rail travel with India's largest e-ticketing platform",
            "secureBooking":"Secure Booking",
            "secureDesc":"Your transactions are safe and secure with our advanced encryption technology.",
            "customerSupport":"24/7 Support",
            "supportDesc":"Round-the-clock assistance for all your travel needs and queries.",
            "easyCancellation":"Easy Cancellation",
            "cancellationDesc":"Simple and hassle-free cancellation process with quick refunds.",
            
            // Footer
            "bookTrain":"Book Train Tickets",
            "forAgents":"For Newly Migrated Agents",
            "aboutUs":"About us"
         }'
         data-hi='{
            // Navigation
            "login":"लॉगिन", "register":"पंजीकरण", "agentLogin":"एजेंट लॉगिन", "contactUs":"संपर्क करें",
            "haveAccount":"क्या आपके पास खाता है?", "loginHere":"यहां लॉगिन करें",
            
            // Services Section
            "findService":"क्या आपको सही सेवा नहीं मिली?",
            "findSuitable":"यहां अपने लिए उपयुक्त सेवा खोजें।",
            "flightTickets":"फ्लाइट टिकट",
            "hotelBooking":"होटल बुकिंग",
            "railDrishti":"रेल दृष्टि",
            "eCatering":"ई-केटरिंग",
            "busTickets":"बस टिकट",
            "holidayPackages":"छुट्टी के पैकेज",
            "touristTrain":"पर्यटक ट्रेन",
            "hillRailways":"पहाड़ी रेलवे",
            "charterTrain":"चार्टर ट्रेन",
            "gallery":"गैलरी",
            
            // Why Choose Section
            "whyChoose":"आईआरसीटीसी क्यों चुनें?",
            "experienceTravel":"भारत के सबसे बड़े ई-टिकटिंग प्लेटफॉर्म पर निर्बल रेल यात्रा का अनुभव करें",
            "secureBooking":"सुरक्षित बुकिंग",
            "secureDesc":"हमारी उन्नत एन्क्रिप्शन तकनीक के साथ आपके लेन-देन सुरक्षित हैं।",
            "customerSupport":"24/7 ग्राहक सहायता",
            "supportDesc":"आपकी सभी यात्रा आवश्यकताओं और प्रश्नों के लिए चौबीसों घंटे सहायता।",
            "easyCancellation":"आसान रद्दीकरण",
            "cancellationDesc":"सरल और परेशानी मुक्त रद्दीकरण प्रक्रिया के साथ त्वरित धनवापसी।",
            
            // Footer
            "bookTrain":"ट्रेन टिकट बुक करें",
            "forAgents":"नव स्थानांतरित एजेंटों के लिए",
            "aboutUs":"हमारे बारे में"
         }'>
    </div>

    <!-- Top Header -->
    <div class="irctc-top-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div class="left-section">
                    <div class="logo-section">
                        <img src="<?php echo htmlspecialchars($customization['logo_url'] ?? 'IRCTC-logo1.png'); ?>" alt="IRCTC Logo">
                    </div>
                    <div class="nav-buttons">
                        <a href="login.php" class="btn btn-primary btn-sm" data-i18n="login">LOGIN</a>
                        <a href="register.php" class="btn btn-outline-light btn-sm" data-i18n="register">REGISTER</a>
                        <a href="agent-login.php" class="btn btn-outline-light btn-sm" data-i18n="agentLogin">AGENT LOGIN</a>
                        <a href="contact.php" class="btn btn-outline-light btn-sm" data-i18n="contactUs">CONTACT US</a>
                        <a href="help-support.php" class="btn btn-outline-light btn-sm">HELP & SUPPORT</a>
                        <a href="daily-deals.php" class="daily-deals">DAILY DEALS
                            <span class="notification-badge">3</span>
                        </a>
                        <div class="dropdown d-inline-block">
                            <a href="#" class="btn btn-outline-light btn-sm position-relative" id="alertsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell me-1"></i> ALERTS
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    5+
                                    <span class="visually-hidden">unread alerts</span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start" aria-labelledby="alertsDropdown">
                                <li><h6 class="dropdown-header" data-i18n="howTo">How To</h6></li>
                                <li>
                                    <a class="dropdown-item unread" href="#">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2 text-primary">
                                                <i class="fas fa-ticket-alt"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small">Your booking #12345 is confirmed</div>
                                                <div class="text-muted small">2 min ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item unread" href="#">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2 text-warning">
                                                <i class="fas fa-percentage"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small">Special offer: 10% off on next booking</div>
                                                <div class="text-muted small">1 hour ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="#">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0 me-2 text-info">
                                                <i class="fas fa-train"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="small">Train #12345 schedule updated</div>
                                                <div class="text-muted small">5 hours ago</div>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-center text-primary" href="#">
                                        <i class="fas fa-bell me-1"></i> View all notifications
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="right-section">
                    <div class="date-time">11-Aug-2025 [18:11:21]</div>
                    <div class="font-controls">
                        <a href="#" title="Decrease font size">A-</a>
                        <a href="#" title="Normal font size">A</a>
                        <a href="#" title="Increase font size">A+</a>
                    </div>
                    <div class="hindi-text language-toggle" title="Switch to Hindi" data-lang="en">हिंदी</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg main-navbar">
        <div class="container-fluid">
            <div class="navbar-nav d-flex w-100">
                <a class="nav-link home-icon" href="#" title="Home">
                    <i class="fas fa-home"></i>
                </a>
                <a class="nav-link active" href="#">IRCTC EXCLUSIVE</a>
                <a class="nav-link trains" href="#">TRAINS</a>
                <a class="nav-link" href="#">LOYALTY</a>
                <a class="nav-link" href="#">IRCTC eWallet</a>
                <a class="nav-link" href="#">BUSES</a>
                <a class="nav-link" href="#">FLIGHTS</a>
                <a class="nav-link" href="#">HOTELS</a>
                <a class="nav-link" href="#">HOLIDAYS</a>
                <a class="nav-link" href="#">MEALS</a>
                <a class="nav-link" href="#">PROMOTIONS</a>
                <a class="nav-link" href="#">MORE</a>
            </div>
        </div>
    </nav>
    
    <!-- Train Alerts Marquee -->
    <?php 
    // Process alerts only once at the top
    $has_active_alerts = false;
    $alert_messages = [];
    $unique_alerts = []; // To track unique alerts
    
    if (!empty($active_alerts)) {
        $current_time = date('Y-m-d H:i:s');
        $current_timestamp = strtotime($current_time);
        
        foreach ($active_alerts as $alert) {
            // Skip if alert is not active or not within date range
            $start_date = strtotime($alert['start_date']);
            $end_date = strtotime($alert['end_date']);
            
            // Create a unique key for each alert to prevent duplicates
            $alert_key = md5($alert['title'] . $alert['message']);
            
            if ($alert['is_active'] && $current_timestamp >= $start_date && $current_timestamp <= $end_date && !isset($unique_alerts[$alert_key])) {
                $has_active_alerts = true;
                $alert_type = $alert['alert_type'];
                $badge_class = "badge bg-{$alert_type} me-2";
                $alert_messages[] = [
                    'type' => $alert_type,
                    'title' => htmlspecialchars($alert['title']),
                    'message' => htmlspecialchars($alert['message']),
                    'badge_class' => $badge_class
                ];
                $unique_alerts[$alert_key] = true; // Mark this alert as processed
            }
        }
    }
    ?>
    
    <div class="container-fluid px-0">
        <div class="alert-ticker">
            <div class="alert-marquee">
                <div class="marquee-content">
                    <?php if ($has_active_alerts): ?>
                        <?php foreach ($alert_messages as $alert): ?>
                            <span class="alert-item">
                                <span class="<?php echo $alert['badge_class']; ?>"><?php echo strtoupper($alert['type']); ?></span>
                                <strong><?php echo $alert['title']; ?>:</strong>
                                <span class="ms-2"><?php echo $alert['message']; ?></span>
                                <span class="mx-3">•</span>
                            </span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span class="alert-item">
                            <span class="badge bg-info me-2">INFO</span>
                            <strong>No active alerts at this time.</strong>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Alert Banner -->
    <?php if (!empty($customization['system_alert'])): ?>
    <div class="alert alert-warning alert-dismissible fade show rounded-0 mb-0" role="alert" style="border-left: 5px solid var(--primary-color);">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <div class="flex-grow-1">
                    <?php echo htmlspecialchars($customization['system_alert']); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Carousel -->
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <?php foreach ($carousel_slides as $index => $slide): ?>
                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>" 
                        <?php echo $index === 0 ? 'class="active"' : ''; ?>></button>
            <?php endforeach; ?>
        </div>
        
        <div class="carousel-inner">
            <?php foreach ($carousel_slides as $index => $slide): ?>
                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                     style="background-image: url('<?php echo htmlspecialchars($slide['image_url'] ?? 'https://via.placeholder.com/1920x800/666666/ffffff?text=Slide+' . ($index + 1)); ?>'); background-size: cover; background-position: center;">
                    <div class="carousel-caption">
                        <h5 data-i18n="busTickets">Bus Tickets</h5><?php echo htmlspecialchars($slide['title'] ?? 'Second slide'); ?></h5>
                        <p><?php echo htmlspecialchars($slide['description'] ?? 'Some representative placeholder content for the second slide.'); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>

    <!-- Services Section -->
    <section class="services-section">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="mb-3" data-i18n="findService">Have you not found the right one?</h2>
                <p class="lead" data-i18n="findSuitable">Find a service suitable for you here.</p>
            </div>
            
            <div class="row">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctc.co.in/nget/train-search" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-train"></i>
                            </div>
                            <h5 data-i18n="bookTrain">Book Train Tickets</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctc.co.in/nget/train-search" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-plane"></i>
                            </div>
                            <h5 data-i18n="flightTickets">Flight Tickets</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctctourism.com/" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-umbrella-beach"></i>
                            </div>
                            <h5 data-i18n="holidayPackages">Holiday Packages</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.bus.irctc.co.in/home" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-bus"></i>
                            </div>
                            <h5 data-i18n="busTickets">Bus Tickets</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctctourism.com/hotels" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <h5 data-i18n="hotelBooking">Hotel Booking</h5>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://raildrishti.cris.org.in/" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 data-i18n="railDrishti">Rail Drishti</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.ecatering.irctc.co.in/" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-utensils"></i>
                            </div>
                            <h5 data-i18n="eCatering">E-Catering</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctctourism.com/tourpackages" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-subway"></i>
                            </div>
                            <h5 data-i18n="touristTrain">Tourist Train</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctctourism.com/hillrailways" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-mountain"></i>
                            </div>
                            <h5 data-i18n="hillRailways">Hill Railways</h5>
                        </div>
                    </a>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                    <a href="https://www.irctctourism.com/gallery" class="service-link" target="_blank" rel="noopener noreferrer">
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-images"></i>
                            </div>
                            <h5 data-i18n="gallery">Gallery</h5>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Train Animation Section -->
    <div class="train-journey-container" style="background: linear-gradient(135deg, #1a237e 0%, #0d47a1 100%); padding: 80px 0; position: relative; overflow: hidden;">
        <div class="container position-relative" style="z-index: 2;">
            <div class="text-center mb-5">
                <h2 class="mb-3 text-white" style="font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.3);">Experience the Magic of Indian Railways</h2>
                <p class="lead text-light" style="font-size: 1.25rem; opacity: 0.9;">Journey through India's breathtaking landscapes with comfort and style</p>
            </div>
            
            <!-- Train Journey Canvas -->
            <div class="train-journey" style="position: relative; height: 400px; border-radius: 12px; overflow: hidden; box-shadow: 0 15px 50px rgba(0,0,0,0.3);">
                <!-- Sky with Gradient -->
                <div class="sky" style="position: absolute; top: 0; left: 0; width: 100%; height: 70%; background: linear-gradient(to bottom, #1e88e5, #64b5f6);">
                    <!-- Sun -->
                    <div class="sun" style="position: absolute; top: 15%; right: 15%; width: 60px; height: 60px; background: #ffeb3b; border-radius: 50%; box-shadow: 0 0 40px #ffeb3b, 0 0 60px #ffeb3b, 0 0 80px #ffeb3b;"></div>
                </div>
                
                <!-- Mountains -->
                <div class="mountains" style="position: absolute; bottom: 30%; left: 0; width: 100%; height: 150px;">
                    <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 100%; background: url('data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'100%\' height=\'100%\' viewBox=\'0 0 1000 150\'><path d=\'M0,150 L0,50 C100,100 200,50 300,80 C400,110 500,70 600,90 C700,110 800,70 900,100 L1000,70 L1000,150 Z\' fill=\'%232c3e50\' fill-opacity=\'0.8\'/><path d=\'M0,120 L0,70 C150,40 250,90 350,60 C450,30 550,80 650,50 C750,20 850,70 1000,30 L1000,120 Z\' fill=\'%23344a5e\' fill-opacity=\'0.7\'/></svg>') no-repeat bottom center; background-size: 100% 100%;"></div>
                </div>
                
                <!-- Ground -->
                <div class="ground" style="position: absolute; bottom: 0; left: 0; width: 100%; height: 30%; background: linear-gradient(to bottom, #2e7d32, #1b5e20);"></div>
                
                <!-- Train Tracks -->
                <div class="tracks" style="position: absolute; bottom: 15%; left: 0; width: 100%; height: 30px; background: #3e2723; transform: perspective(100px) rotateX(30deg);">
                    <!-- Railway sleepers -->
                    <div style="position: absolute; top: 50%; left: 0; width: 100%; height: 4px; background: repeating-linear-gradient(90deg, #5d4037, #5d4037 10px, #3e2723 10px, #3e2723 20px);"></div>
                    
                    <!-- Rails -->
                    <div style="position: absolute; top: 40%; left: 25%; width: 50%; height: 10px; background: #212121; border-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                        <div style="position: absolute; top: 2px; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #757575, #e0e0e0, #757575);"></div>
                    </div>
                    
                    <div style="position: absolute; top: 40%; right: 25%; width: 50%; height: 10px; background: #212121; border-radius: 2px; box-shadow: 0 2px 5px rgba(0,0,0,0.3);">
                        <div style="position: absolute; top: 2px; left: 0; width: 100%; height: 3px; background: linear-gradient(90deg, #757575, #e0e0e0, #757575);"></div>
                    </div>
                </div>
                
                <!-- Train -->
                <div class="train" style="position: absolute; bottom: 25%; left: -400px; width: 350px; height: 100px; animation: moveTrain 20s linear infinite;">
                    <!-- Engine -->
                    <div style="position: absolute; width: 120px; height: 70px; background: #e53935; border-radius: 10px 20px 0 0; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">
                        <!-- Windows -->
                        <div style="position: absolute; top: 15px; right: 15px; width: 30px; height: 25px; background: #bbdefb; border-radius: 3px; border: 2px solid #0d47a1;"></div>
                        <div style="position: absolute; top: 15px; right: 55px; width: 30px; height: 25px; background: #bbdefb; border-radius: 3px; border: 2px solid #0d47a1;"></div>
                        
                        <!-- Smokestack -->
                        <div style="position: absolute; top: -20px; left: 20px; width: 20px; height: 25px; background: #424242; border-radius: 3px 3px 0 0;"></div>
                        
                        <!-- Headlight -->
                        <div style="position: absolute; top: 20px; right: 5px; width: 15px; height: 15px; background: #ffeb3b; border-radius: 50%; box-shadow: 0 0 10px 3px #ffeb3b; animation: lightPulse 1s infinite alternate;"></div>
                        
                        <!-- Wheels -->
                        <div style="position: absolute; bottom: -10px; left: 15px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                        <div style="position: absolute; bottom: -10px; left: 65px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                    </div>
                    
                    <!-- Coaches -->
                    <div style="position: absolute; left: 120px; top: 10px; display: flex; gap: 5px;">
                        <div style="width: 70px; height: 60px; background: #1976d2; border-radius: 5px; position: relative; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                            <div style="position: absolute; top: 10px; left: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; top: 10px; right: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: 10px; left: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: 10px; right: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: -10px; left: 10px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                            <div style="position: absolute; bottom: -10px; right: 10px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                        </div>
                        
                        <div style="width: 70px; height: 60px; background: #1976d2; border-radius: 5px; position: relative; box-shadow: 0 3px 10px rgba(0,0,0,0.2);">
                            <div style="position: absolute; top: 10px; left: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; top: 10px; right: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: 10px; left: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: 10px; right: 10px; width: 20px; height: 15px; background: #bbdefb; border: 1px solid #0d47a1; border-radius: 2px;"></div>
                            <div style="position: absolute; bottom: -10px; left: 10px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                            <div style="position: absolute; bottom: -10px; right: 10px; width: 25px; height: 25px; background: #212121; border-radius: 50%; border: 3px solid #424242;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Smoke Effect -->
                <div id="smoke-container" style="position: absolute; top: 0; left: 0; width: 100%; height: 150px; pointer-events: none;"></div>
            </div>
            
            <!-- Journey Info Cards -->
            <div class="row mt-5">
                <div class="col-md-4 mb-4">
                    <div class="journey-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 10px; padding: 20px; color: white; text-align: center; height: 100%; transition: transform 0.3s ease;">
                        <i class="fas fa-train" style="font-size: 2.5rem; margin-bottom: 15px; color: #ffeb3b;"></i>
                        <h4 style="font-weight: 600;">Comfortable Travel</h4>
                        <p>Experience the most comfortable train journeys with modern amenities and spacious seating.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="journey-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 10px; padding: 20px; color: white; text-align: center; height: 100%; transition: transform 0.3s ease;">
                        <i class="fas fa-mountain" style="font-size: 2.5rem; margin-bottom: 15px; color: #4caf50;"></i>
                        <h4 style="font-weight: 600;">Scenic Routes</h4>
                        <p>Discover India's breathtaking landscapes through our extensive rail network.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="journey-card" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 10px; padding: 20px; color: white; text-align: center; height: 100%; transition: transform 0.3s ease;">
                        <i class="fas fa-utensils" style="font-size: 2.5rem; margin-bottom: 15px; color: #ff7043;"></i>
                        <h4 style="font-weight: 600;">On-board Dining</h4>
                        <p>Enjoy delicious meals with our premium catering services during your journey.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        @keyframes moveTrain {
            0% { transform: translateX(-400px); }
            100% { transform: translateX(calc(100vw + 400px)); }
        }
        
        @keyframes lightPulse {
            from { box-shadow: 0 0 10px 3px #ffeb3b; }
            to { box-shadow: 0 0 20px 6px #ffeb3b; }
        }
        
        .journey-card:hover {
            transform: translateY(-10px) !important;
            background: rgba(255,255,255,0.15) !important;
        }
        
        /* Add some 3D perspective to the train */
        .train {
            transform-style: preserve-3d;
            transform: perspective(1000px) rotateY(0deg);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .train-journey {
                height: 300px !important;
            }
            .journey-card {
                margin-bottom: 15px !important;
            }
        }
        </style>
        
        <script>
        // Dynamic smoke effect
        document.addEventListener('DOMContentLoaded', function() {
            const smokeContainer = document.getElementById('smoke-container');
            const train = document.querySelector('.train');
            
            function createSmoke() {
                const smoke = document.createElement('div');
                smoke.style.position = 'absolute';
                smoke.style.left = '40px';
                smoke.style.top = '30px';
                smoke.style.width = '8px';
                smoke.style.height = '8px';
                smoke.style.background = 'rgba(255,255,255,0.8)';
                smoke.style.borderRadius = '50%';
                smoke.style.filter = 'blur(3px)';
                smoke.style.animation = 'smokeRise 3s ease-out forwards';
                smokeContainer.appendChild(smoke);
                
                // Remove smoke element after animation
                setTimeout(() => {
                    smoke.remove();
                }, 3000);
            }
            
            // Create smoke periodically
            setInterval(createSmoke, 300);
            
            // Add mouse move effect to train
            document.addEventListener('mousemove', (e) => {
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                train.style.transform = `translateX(${x * 10 - 5}px) translateY(${y * 5 - 2.5}px)`;
            });
        });
        
        // Add CSS for smoke animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes smokeRise {
                0% { 
                    transform: translate(0, 0) scale(1); 
                    opacity: 0.8; 
                }
                50% { 
                    opacity: 0.5; 
                }
                100% { 
                    transform: translate(-20px, -100px) scale(3); 
                    opacity: 0; 
                }
            }
        `;
        document.head.appendChild(style);
        </script>
    </div>
    <div class="train-animation-container" style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); padding: 60px 0; overflow: hidden;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3" style="color: #1e3a8a; font-weight: 700;">Experience the Journey</h2>
                <p class="lead" style="color: #4b5563; font-size: 1.25rem;">Witness the beauty of Indian Railways in motion</p>
            </div>
            
            <div class="train-track" style="position: relative; height: 200px; background: #e5e7eb; border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                <!-- Sky -->
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 60%; background: linear-gradient(to bottom, #87CEEB, #E0F7FA);"></div>
                
                <!-- Ground -->
                <div style="position: absolute; bottom: 0; left: 0; width: 100%; height: 40%; background: #4b7b3a;"></div>
                
                <!-- Mountains -->
                <div style="position: absolute; bottom: 40%; left: 0; width: 100%; height: 100px;">
                    <div style="position: absolute; bottom: 0; left: 20%; width: 0; height: 0; border-left: 80px solid transparent; border-right: 80px solid transparent; border-bottom: 100px solid #2c3e50; opacity: 0.8;"></div>
                    <div style="position: absolute; bottom: 0; left: 30%; width: 0; height: 0; border-left: 60px solid transparent; border-right: 60px solid transparent; border-bottom: 80px solid #34495e; opacity: 0.7;"></div>
                    <div style="position: absolute; bottom: 0; right: 25%; width: 0; height: 0; border-left: 100px solid transparent; border-right: 100px solid transparent; border-bottom: 120px solid #2c3e50; opacity: 0.8;"></div>
                </div>
                
                <!-- Trees -->
                <div style="position: absolute; bottom: 40%; left: 10%; width: 10px; height: 40px; background: #8b4513;"></div>
                <div style="position: absolute; bottom: 40%; left: 10%; width: 30px; height: 15px; background: #27ae60; border-radius: 50%; bottom: 55%; left: 5%;"></div>
                
                <div style="position: absolute; bottom: 40%; left: 30%; width: 10px; height: 30px; background: #8b4513;"></div>
                <div style="position: absolute; bottom: 40%; left: 30%; width: 25px; height: 12px; background: #27ae60; border-radius: 50%; bottom: 52%; left: 27.5%;"></div>
                
                <div style="position: absolute; bottom: 40%; right: 20%; width: 10px; height: 35px; background: #8b4513;"></div>
                <div style="position: absolute; bottom: 40%; right: 20%; width: 35px; height: 18px; background: #27ae60; border-radius: 50%; bottom: 55%; right: 12.5%;"></div>
                
                <!-- Train -->
                <div class="train" style="position: absolute; bottom: 40%; left: -200px; width: 200px; height: 80px; animation: moveTrain 15s linear infinite;">
                    <!-- Engine -->
                    <div style="position: absolute; width: 80px; height: 50px; background: #e74c3c; border-radius: 10px 20px 0 0;">
                        <div style="position: absolute; width: 30px; height: 20px; background: #3498db; border-radius: 3px; right: 10px; top: 10px;"></div>
                        <div style="position: absolute; width: 15px; height: 30px; background: #7f8c8d; left: 10px; top: -30px; border-radius: 3px 3px 0 0;"></div>
                        <div style="position: absolute; width: 15px; height: 15px; background: #f1c40f; border-radius: 50%; right: 10px; top: 10px; box-shadow: 0 0 10px 3px #f1c40f; animation: lightPulse 1s infinite alternate;"></div>
                    </div>
                    
                    <!-- Coaches -->
                    <div style="position: absolute; left: 80px; top: 10px; display: flex; gap: 5px;">
                        <div style="width: 50px; height: 45px; background: #3498db; border-radius: 5px; position: relative;">
                            <div style="position: absolute; width: 20px; height: 15px; background: #87ceeb; border-radius: 2px; left: 15px; top: 10px;"></div>
                        </div>
                        <div style="width: 50px; height: 45px; background: #3498db; border-radius: 5px; position: relative;">
                            <div style="position: absolute; width: 20px; height: 15px; background: #87ceeb; border-radius: 2px; left: 15px; top: 10px;"></div>
                        </div>
                    </div>
                    
                    <!-- Wheels -->
                    <div style="position: absolute; bottom: -10px; left: 10px; width: 20px; height: 20px; background: #2c3e50; border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -10px; left: 50px; width: 20px; height: 20px; background: #2c3e50; border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -10px; left: 100px; width: 20px; height: 20px; background: #2c3e50; border-radius: 50%;"></div>
                    <div style="position: absolute; bottom: -10px; left: 140px; width: 20px; height: 20px; background: #2c3e50; border-radius: 50%;"></div>
                </div>
                
                <!-- Train Tracks -->
                <div style="position: absolute; bottom: 30%; left: 0; width: 100%; height: 10px; background: #333; display: flex; justify-content: space-between; padding: 0 20px;">
                    <div style="width: 10px; height: 100%; background: #7f8c8d; position: relative; overflow: hidden;">
                        <div style="position: absolute; width: 200%; height: 3px; background: #95a5a6; top: 3px; left: -50%; animation: moveTrack 1s linear infinite;"></div>
                    </div>
                    <div style="width: 10px; height: 100%; background: #7f8c8d; position: relative; overflow: hidden;">
                        <div style="position: absolute; width: 200%; height: 3px; background: #95a5a6; top: 3px; left: -50%; animation: moveTrack 1s linear infinite 0.5s;"></div>
                    </div>
                    <div style="width: 10px; height: 100%; background: #7f8c8d; position: relative; overflow: hidden;">
                        <div style="position: absolute; width: 200%; height: 3px; background: #95a5a6; top: 3px; left: -50%; animation: moveTrack 1s linear infinite 0.2s;"></div>
                    </div>
                </div>
                
                <!-- Smoke -->
                <div class="smoke" style="position: absolute; top: 10%; left: 30px; width: 10px; height: 10px; background: rgba(255,255,255,0.8); border-radius: 50%; animation: smoke 3s linear infinite;"></div>
                <div class="smoke" style="position: absolute; top: 5%; left: 40px; width: 15px; height: 15px; background: rgba(255,255,255,0.6); border-radius: 50%; animation: smoke 4s linear infinite 0.5s;"></div>
                <div class="smoke" style="position: absolute; top: 0%; left: 50px; width: 20px; height: 20px; background: rgba(255,255,255,0.4); border-radius: 50%; animation: smoke 5s linear infinite 1s;"></div>
            </div>
        </div>
        
        <style>
        @keyframes moveTrain {
            0% { transform: translateX(-200px); }
            100% { transform: translateX(calc(100vw + 200px)); }
        }
        
        @keyframes moveTrack {
            0% { transform: translateX(0); }
            100% { transform: translateX(10px); }
        }
        
        @keyframes smoke {
            0% { 
                transform: translateY(0) scale(1); 
                opacity: 0.8; 
            }
            50% { 
                opacity: 0.6; 
            }
            100% { 
                transform: translateY(-100px) scale(3); 
                opacity: 0; 
            }
        }
        
        @keyframes lightPulse {
            from { box-shadow: 0 0 10px 3px #f1c40f; }
            to { box-shadow: 0 0 20px 6px #f1c40f; }
        }
        </style>
    </div>

    <!-- Promotional Banner -->
    <div class="container my-5">
        <div class="promo-banner" style="display: flex; justify-content: center; align-items: center; width: 100%; border: 1px solid #e0e0e0; padding: 15px; border-radius: 10px; box-shadow: rgba(50, 50, 93, 0.1) 0px 13px 27px -5px, rgba(0, 0, 0, 0.1) 0px 8px 16px -8px; background: #fff;">
            <div class="promo-content" style="text-align: center; width: 100%;">
                <h3 style="color: #1e3a8a; margin-bottom: 15px;">IRCTC eCatering - Food on Train</h3>
                <p style="color: #555; margin-bottom: 20px;">Enjoy delicious food delivered to your seat during train journey</p>
                <a href="https://www.ecatering.irctc.co.in/" class="btn btn-primary" style="background: #1e3a8a; border: none; padding: 8px 25px; border-radius: 5px; color: white; text-decoration: none; font-weight: 500;">Order Now</a>
            </div>
        </div>
    </div>

    <!-- Why Choose Us Section -->
    <section class="why-choose-us py-5" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="mb-3" data-i18n="whyChoose">Why Choose IRCTC?</h2>
                <p class="lead" style="max-width: 700px; margin: 0 auto;" data-i18n="experienceTravel">Experience seamless rail travel with India's largest e-ticketing platform</p>
            </div>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-shield-alt fa-3x" style="color: #1e3a8a;"></i>
                            </div>
                            <h5 class="card-title mb-3" data-i18n="hillRailways">Hill Railways</h5>
                            <p class="card-text text-muted" data-i18n="secureDesc">Your transactions are safe and secure with our advanced encryption technology.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-headset fa-3x" style="color: #1e3a8a;"></i>
                            </div>
                            <h5 class="card-title mb-3" data-i18n="hillRailways">Hill Railways</h5>
                            <p class="card-text text-muted" data-i18n="supportDesc">Round-the-clock assistance for all your travel needs and queries.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-ticket-alt fa-3x" style="color: #1e3a8a;"></i>
                            </div>
                            <h5 class="card-title mb-3" data-i18n="easyCancellation">Easy Cancellation</h5>
                            <p class="card-text text-muted" data-i18n="cancellationDesc">Simple and hassle-free cancellation process with quick refunds.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="footer-section">
        <div class="container">
            <div class="footer-top">
                <div class="social-section">
                    <h6>Get Connected with us on social networks</h6>
                    <div class="social-icons">
                        <a href="#" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon whatsapp"><i class="fab fa-whatsapp"></i></a>
                        <a href="#" class="social-icon youtube"><i class="fab fa-youtube"></i></a>
                        <a href="#" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon linkedin"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="social-icon telegram"><i class="fab fa-telegram-plane"></i></a>
                        <a href="#" class="social-icon pinterest"><i class="fab fa-pinterest"></i></a>
                        <a href="#" class="social-icon snapchat"><i class="fab fa-snapchat-ghost"></i></a>
                        <a href="#" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-content">
                <div class="row">
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="footer-column">
                            <h6 data-i18n="bookTrain">Book Train Tickets</h6>
                            <ul>
                                <li><a href="#">General Information</a></li>
                                <li><a href="#">Important Information</a></li>
                                <li><a href="#">Agents</a></li>
                                <li><a href="#">Enquiries</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="footer-column">
                            <h6 data-i18n="hotelBooking">Hotel Booking</h6>
                            <ul>
                                <li><a href="#">IRCTC Official App</a></li>
                                <li><a href="#">Advertise with us</a></li>
                                <li><a href="#">Refund Rules</a></li>
                                <li><a href="#">Person With Disability Facilities</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="footer-column">
                            <h6 data-i18n="securePayments">Secure Payments</h6>
                            <p data-i18n="secureDesc">100% secure payment options</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="footer-column">
                            <h6 data-i18n="forAgents">For Newly Migrated Agents</h6>
                            <ul>
                                <li><a href="#">Help & Support</a></li>
                                <li><a href="#">Mobile Zone</a></li>
                                <li><a href="#">Policies</a></li>
                                <li><a href="#">Ask Disha ChatBot</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <div class="footer-column">
                            <h6 data-i18n="aboutUs">About us</h6>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-payment">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="payment-logos">
                            <img src="https://via.placeholder.com/60x40/ffffff/cccccc?text=VeriSign" alt="VeriSign" class="payment-logo">
                            <img src="https://via.placeholder.com/80x40/ffffff/cccccc?text=MasterCard" alt="MasterCard" class="payment-logo">
                            <img src="https://via.placeholder.com/60x40/ffffff/cccccc?text=AmEx" alt="American Express" class="payment-logo">
                            <img src="https://via.placeholder.com/60x40/ffffff/cccccc?text=SafeKey" alt="SafeKey" class="payment-logo">
                            <img src="https://via.placeholder.com/80x40/ffffff/cccccc?text=Verified" alt="Verified by Visa" class="payment-logo">
                            <img src="https://via.placeholder.com/60x40/ffffff/cccccc?text=RuPay" alt="RuPay" class="payment-logo">
                            <img src="https://via.placeholder.com/80x40/ffffff/cccccc?text=IRCTC.com" alt="IRCTC" class="payment-logo">
                            <img src="https://via.placeholder.com/60x40/ffffff/cccccc?text=CRIS" alt="CRIS" class="payment-logo">
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="footer-copyright">
                            <h5 data-i18n="eCatering">E-Catering</h5>
                            <p>Copyright A 2025 - www.irctc.co.in. All Rights Reserved.</p>
                            <p>Designed and Hosted By Centre for Railway Information Systems (CRIS), An ISO 27001:2013 Certified Organisation.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Service Unavailable Modal -->
    <div class="modal fade" id="serviceModal" tabindex="-1" aria-labelledby="serviceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="serviceModalLabel">Service Unavailable</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h4 class="mb-3"><span id="serviceName"></span> is currently unavailable</h4>
                    <p>We're working on bringing this service to you as soon as possible. Please check back later.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Service modal handler
    document.addEventListener('DOMContentLoaded', function() {
        var serviceModal = document.getElementById('serviceModal');
        if (serviceModal) {
            serviceModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var serviceName = button.getAttribute('data-service');
                var modalTitle = serviceModal.querySelector('.modal-title');
                var serviceNameSpan = serviceModal.querySelector('#serviceName');
                
                modalTitle.textContent = serviceName + ' Unavailable';
                serviceNameSpan.textContent = serviceName;
            });
        }
    });
    </script>
    
    <script>
    // Pause ticker animation on hover
    document.addEventListener('DOMContentLoaded', function() {
        const tickerTracks = document.querySelectorAll('.ticker-track');
        
        tickerTracks.forEach(track => {
            // Pause animation on hover
            track.addEventListener('mouseenter', function() {
                this.style.animationPlayState = 'paused';
            });
            
            // Resume animation when mouse leaves
            track.addEventListener('mouseleave', function() {
                this.style.animationPlayState = 'running';
            });
            
            // Clone ticker items only once for infinite scrolling effect
            if (!track.hasAttribute('data-cloned')) {
                const tickerItems = track.querySelectorAll('.ticker-item');
                const tickerItemsArray = Array.from(tickerItems);
                
                // Clone each item once and add to the end
                tickerItemsArray.forEach(item => {
                    const clone = item.cloneNode(true);
                    track.appendChild(clone);
                });
                
                // Mark as cloned to prevent duplicate cloning
                track.setAttribute('data-cloned', 'true');
                
                // Restart animation to ensure smooth transition
                track.style.animation = 'none';
                track.offsetHeight; // Trigger reflow
                track.style.animation = null;
            }
        });
        
        // Handle alert dismissal
        const alertDismissButtons = document.querySelectorAll('.alert-ticker .btn-close');
        alertDismissButtons.forEach(button => {
            button.addEventListener('click', function() {
                const alertTicker = this.closest('.alert-ticker');
                if (alertTicker) {
                    alertTicker.style.display = 'none';
                    // You could also make an AJAX call here to mark the alert as read in the database
                }
            });
        });
    });
    </script>
    
    <!-- Custom JavaScript for Alerts and Notifications -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle alert dismissal
        document.querySelectorAll('.alert-dismissible .btn-close').forEach(function(closeBtn) {
            closeBtn.addEventListener('click', function() {
                this.closest('.alert').style.opacity = '0';
                setTimeout(() => {
                    this.closest('.alert').remove();
                }, 300);
            });
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            document.querySelectorAll('.alert:not(.alert-dismissible)').forEach(function(alert) {
                alert.style.opacity = '0';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            });
        }, 5000);
        
        // Mark all as read functionality
        const markAllReadBtn = document.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.dropdown-item.unread').forEach(function(item) {
                    item.classList.remove('unread');
                });
                // Update notification count
                const badge = document.querySelector('#alertsDropdown .badge');
                if (badge) {
                    badge.textContent = '0';
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-secondary');
                }
            });
        }
        
        // Update time every minute
        function updateDateTime() {
            const now = new Date();
            const options = { 
                day: '2-digit', 
                month: 'short', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.querySelector('.date-time').textContent = now.toLocaleDateString('en-IN', options);
        }
        
        // Initial call
        updateDateTime();
        
        // Update every minute
        setInterval(updateDateTime, 60000);
    });
    </script>
    
    <script>
    // Language Toggle Functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Language data
        const translations = {
            en: {
                login: 'LOGIN',
                register: 'REGISTER',
                agentLogin: 'AGENT LOGIN',
                contactUs: 'CONTACT US',
                haveAccount: 'Have an account?',
                loginHere: 'Login here',
                findService: 'Have you not found the right one?',
                findSuitable: 'Find a service suitable for you here.',
                flightTickets: 'Flight Tickets',
                hotelBooking: 'Hotel Booking',
                railDrishti: 'Rail Drishti',
                eCatering: 'E-Catering',
                busTickets: 'Bus Tickets',
                holidayPackages: 'Holiday Packages',
                touristTrain: 'Tourist Train',
                hillRailways: 'Hill Railways',
                charterTrain: 'Charter Train',
                gallery: 'Gallery',
                whyChoose: 'Why Choose IRCTC?',
                experienceTravel: 'Experience seamless rail travel with India\'s largest e-ticketing platform',
                secureBooking: 'Secure Booking',
                secureDesc: 'Your transactions are safe and secure with our advanced encryption technology.',
                customerSupport: '24/7 Support',
                supportDesc: 'Round-the-clock assistance for all your travel needs and queries.',
                easyCancellation: 'Easy Cancellation',
                cancellationDesc: 'Simple and hassle-free cancellation process with quick refunds.',
                bookTrain: 'Book Train Tickets',
                forAgents: 'For Newly Migrated Agents',
                aboutUs: 'About us',
                howTo: 'How To'
            },
            hi: {
                login: 'लॉगिन',
                register: 'पंजीकरण',
                agentLogin: 'एजेंट लॉगिन',
                contactUs: 'संपर्क करें',
                haveAccount: 'क्या आपके पास खाता है?',
                loginHere: 'यहां लॉगिन करें',
                findService: 'क्या आपको सही सेवा नहीं मिली?',
                findSuitable: 'यहां अपने लिए उपयुक्त सेवा खोजें।',
                flightTickets: 'फ्लाइट टिकट',
                hotelBooking: 'होटल बुकिंग',
                railDrishti: 'रेल दृष्टि',
                eCatering: 'ई-केटरिंग',
                busTickets: 'बस टिकट',
                holidayPackages: 'छुट्टी के पैकेज',
                touristTrain: 'पर्यटक ट्रेन',
                hillRailways: 'पहाड़ी रेलवे',
                charterTrain: 'चार्टर ट्रेन',
                gallery: 'गैलरी',
                whyChoose: 'आईआरसीटीसी क्यों चुनें?',
                experienceTravel: 'भारत के सबसे बड़े ई-टिकटिंग प्लेटफॉर्म पर निर्बल रेल यात्रा का अनुभव करें',
                secureBooking: 'सुरक्षित बुकिंग',
                secureDesc: 'हमारी उन्नत एन्क्रिप्शन तकनीक के साथ आपके लेन-देन सुरक्षित हैं।',
                customerSupport: '24/7 ग्राहक सहायता',
                supportDesc: 'आपकी सभी यात्रा आवश्यकताओं और प्रश्नों के लिए चौबीसों घंटे सहायता।',
                easyCancellation: 'आसान रद्दीकरण',
                cancellationDesc: 'सरल और परेशानी मुक्त रद्दीकरण प्रक्रिया के साथ त्वरित धनवापसी।',
                bookTrain: 'ट्रेन टिकट बुक करें',
                forAgents: 'नव स्थानांतरित एजेंटों के लिए',
                aboutUs: 'हमारे बारे में',
                howTo: 'कैसे करें'
            }
        };

        // Initialize language
        function initLanguage() {
            try {
                // Get saved language or default to English
                let currentLang = localStorage.getItem('language') || 'en';
                
                // Set initial language
                updateLanguage(currentLang);
                
                // Set up toggle button
                const toggleBtn = document.querySelector('.hindi-text');
                if (toggleBtn) {
                    // Update button text based on current language
                    toggleBtn.textContent = currentLang === 'hi' ? 'English' : 'हिंदी';
                    toggleBtn.setAttribute('data-lang', currentLang);
                    toggleBtn.title = currentLang === 'hi' ? 'Switch to English' : 'हिंदी में बदलें';
                    
                    // Remove any existing click handlers to prevent duplicates
                    toggleBtn.replaceWith(toggleBtn.cloneNode(true));
                    const newToggleBtn = document.querySelector('.hindi-text');
                    
                    // Add click event
                    newToggleBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        const currentLang = this.getAttribute('data-lang');
                        const newLang = currentLang === 'en' ? 'hi' : 'en';
                        updateLanguage(newLang);
                        return false;
                    });
                }
            } catch (error) {
                console.error('Error initializing language:', error);
            }
        }
        
        // Update page content based on selected language
        function updateLanguage(lang) {
            try {
                // Update localStorage
                localStorage.setItem('language', lang);
                
                // Get translations for selected language
                const t = translations[lang] || translations.en;
                
                // Update all elements with data-i18n attribute
                document.querySelectorAll('[data-i18n]').forEach(el => {
                    const key = el.getAttribute('data-i18n');
                    if (t[key] !== undefined) {
                        if (el.tagName === 'INPUT' && el.getAttribute('type') === 'text') {
                            el.placeholder = t[key];
                        } else {
                            el.textContent = t[key];
                        }
                    } else {
                        console.warn('No translation found for key:', key);
                    }
                });
                
                // Update toggle button
                const toggleBtn = document.querySelector('.hindi-text');
                if (toggleBtn) {
                    toggleBtn.textContent = lang === 'hi' ? 'English' : 'हिंदी';
                    toggleBtn.setAttribute('data-lang', lang);
                    toggleBtn.title = lang === 'hi' ? 'Switch to English' : 'हिंदी में बदलें';
                }
                
                // Update HTML lang attribute
                document.documentElement.lang = lang;
                
                console.log('Language updated to:', lang);
            } catch (error) {
                console.error('Error updating language:', error);
            }
        }
        
        // Initialize language functionality
        initLanguage();
        
        // Make updateLanguage available globally
        window.updateLanguage = updateLanguage;
    })();

        // Initialize marquee with better performance
        document.addEventListener('DOMContentLoaded', function() {
            const marquee = document.querySelector('.marquee-content');
            if (!marquee) return;

            // Get all alert items
            const alertItems = marquee.querySelectorAll('.alert-item');
            if (alertItems.length === 0) return;

            // Create a container for the alerts
            const container = document.createElement('div');
            container.className = 'marquee-container';
            
            // Add each alert once to the container
            alertItems.forEach(item => {
                container.appendChild(item);
            });
            
            // Clear marquee and add the container
            marquee.innerHTML = '';
            marquee.appendChild(container);
            
            // Set animation duration based on content width and configured speed
            const contentWidth = container.scrollWidth;
            const viewportWidth = window.innerWidth;
            const baseDuration = Math.max(10, Math.min(60, Math.ceil((contentWidth + viewportWidth) / 50)));
            
            // Get configured speed (default to 50% if not set)
            const marqueeSpeed = <?php echo isset($customization['marquee_speed']) ? (int)$customization['marquee_speed'] : 50; ?>;
            
            // Calculate duration based on speed (inverse relationship - higher speed = shorter duration)
            const speedFactor = (100 - marqueeSpeed + 10) / 100; // Convert 0-100 to 1.1-0.1 multiplier
            const duration = Math.max(5, Math.min(120, baseDuration * speedFactor));
            
            // Set animation
            marquee.style.animationDuration = `${duration}s`;
            
            // Pause on hover
            marquee.parentElement.addEventListener('mouseenter', () => {
                marquee.style.animationPlayState = 'paused';
            });
            
            marquee.parentElement.addEventListener('mouseleave', () => {
                marquee.style.animationPlayState = 'running';
            });
        });
        
        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const dateTimeString = now.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            }) + ' [' + now.toLocaleTimeString('en-GB', {
                hour12: false
            }) + ']';
            
            const dateTimeElement = document.querySelector('.date-time');
            if (dateTimeElement) {
                dateTimeElement.textContent = dateTimeString;
            }
        }
        
        // Update time every second
        setInterval(updateDateTime, 1000);
        updateDateTime();
        
        // Font size controls
        document.querySelectorAll('.font-controls a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const body = document.body;
                const currentSize = parseFloat(window.getComputedStyle(body).fontSize);
                
                if (this.textContent === 'A+') {
                    body.style.fontSize = (currentSize + 1) + 'px';
                } else if (this.textContent === 'A-') {
                    body.style.fontSize = (currentSize - 1) + 'px';
                } else {
                    body.style.fontSize = '16px';
                }
            });
        });
    </script>
</body>
</html>
