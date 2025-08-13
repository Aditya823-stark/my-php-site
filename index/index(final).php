<?php
// Start session for user management
session_start();

// Include database connection
include('../connect/db.php');
include('../connect/fun.php');

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
$notifications = [];

if ($connection_status == "success") {
    // Fetch website customization
    $customization_query = "SELECT * FROM website_customization WHERE id = 1 LIMIT 1";
    $customization_result = mysqli_query($db, $customization_query);
    if ($customization_result && mysqli_num_rows($customization_result) > 0) {
        $customization = mysqli_fetch_assoc($customization_result);
    } else {
        // Default values if no customization found
        $customization = [
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#f97316', 
            'accent_color' => '#059669',
            'site_title' => 'IRCTC Rail Connect',
            'logo_url' => 'assets/logo/favicon.png',
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
    
    // If no carousel slides, use defaults
    if (empty($carousel_slides)) {
        $carousel_slides = [
            [
                'image_url' => 'assets/images/slider/slider1.jpg',
                'title' => 'Discover Amazing Places',
                'description' => 'Experience the beauty of Indian Railways',
                'button_text' => 'Book Now',
                'button_link' => '#booking'
            ],
            [
                'image_url' => 'assets/images/slider/slider2.jpg', 
                'title' => 'Comfortable Journey',
                'description' => 'Travel in comfort with modern amenities',
                'button_text' => 'Explore',
                'button_link' => '#services'
            ]
        ];
    }
    
    // Fetch active notifications for frontend display
    $notification_query = "SELECT * FROM notification_logs WHERE status = 'Sent' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC LIMIT 5";
    $notification_result = mysqli_query($db, $notification_query);
    if ($notification_result) {
        while ($notification = mysqli_fetch_assoc($notification_result)) {
            $notifications[] = $notification;
        }
    }
}
?>
<!doctype html>
<html class="no-js" lang="en">
<head>
    <!-- META DATA -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!--font-family-->
    <link href="https://fonts.googleapis.com/css?family=Rufina:400,700" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet" />
    
    <!-- TITLE OF SITE -->
    <title><?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?></title>
    
    <!-- favicon img -->
    <link rel="shortcut icon" type="image/icon" href="<?php echo htmlspecialchars($customization['logo_url'] ?? 'assets/logo/favicon.png'); ?>"/>
    
    <!--font-awesome.min.css-->
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" />
    
    <!--animate.css-->
    <link rel="stylesheet" href="assets/css/animate.css" />
    
    <!--hover.css-->
    <link rel="stylesheet" href="assets/css/hover-min.css">
    
    <!--datepicker.css-->
    <link rel="stylesheet" href="assets/css/datepicker.css" >
    
    <!--owl.carousel.css-->
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/owl.theme.default.min.css"/>
    
    <!-- range css-->
    <link rel="stylesheet" href="assets/css/jquery-ui.min.css" />
    
    <!--bootstrap.min.css-->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    
    <!-- bootsnav -->
    <link rel="stylesheet" href="assets/css/bootsnav.css"/>
    
    <!--style.css-->
    <link rel="stylesheet" href="assets/css/style.css" />
    
    <!--responsive.css-->
    <link rel="stylesheet" href="assets/css/responsive.css" />
    
    <!-- Custom CSS Variables for Dynamic Theming -->
    <style>
        :root {
            --primary-color: <?php echo $customization['primary_color'] ?? '#1e3a8a'; ?>;
            --secondary-color: <?php echo $customization['secondary_color'] ?? '#f97316'; ?>;
            --accent-color: <?php echo $customization['accent_color'] ?? '#059669'; ?>;
        }
        
        /* IRCTC Top Header Styling */
        .irctc-top-header {
            background: linear-gradient(135deg, var(--primary-color), #1e40af);
            color: white;
            padding: 8px 0;
            font-size: 13px;
            border-bottom: 2px solid var(--secondary-color);
        }
        
        .irctc-top-header span {
            margin-right: 15px;
        }
        
        .irctc-top-header i {
            margin-right: 5px;
            color: var(--secondary-color);
        }
        
        .daily-deals {
            background: var(--secondary-color);
            color: white !important;
            padding: 4px 12px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            animation: pulse 2s infinite;
            transition: all 0.3s ease;
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
        
        /* Navigation Styling */
        .navbar-brand, .logo a {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color) !important;
            text-decoration: none;
        }
        
        .navbar-brand:hover, .logo a:hover {
            color: var(--primary-color) !important;
            text-decoration: none;
        }
        
        .navbar-brand span, .logo a span {
            color: var(--secondary-color);
        }
        
        /* About Us Section */
        .about-us {
            background: linear-gradient(rgba(30, 58, 138, 0.8), rgba(30, 58, 138, 0.8)), 
                        url('assets/images/about/about-us.jpg') center/cover;
            padding: 100px 0;
            color: white;
            text-align: center;
        }
        
        .about-us h2 {
            font-size: 3rem;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .about-view {
            background: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }
        
        .about-view:hover {
            background: #ea580c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(249, 115, 22, 0.4);
        }
        
        /* Travel Box / Booking Section */
        .travel-box {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .single-travel-boxes {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .desc-tabs .nav-tabs {
            border-bottom: 3px solid var(--primary-color);
            background: var(--primary-color);
        }
        
        .desc-tabs .nav-tabs > li > a {
            color: white;
            border: none;
            background: transparent;
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .desc-tabs .nav-tabs > li.active > a,
        .desc-tabs .nav-tabs > li.active > a:hover,
        .desc-tabs .nav-tabs > li.active > a:focus {
            background: var(--secondary-color);
            color: white;
            border: none;
        }
        
        .desc-tabs .nav-tabs > li > a:hover {
            background: rgba(255,255,255,0.1);
            border: none;
        }
        
        .tab-content {
            padding: 30px;
        }
        
        .btn-search {
            background: var(--secondary-color) !important;
            border: none !important;
            color: white !important;
            padding: 12px 25px !important;
            border-radius: 5px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            width: 100% !important;
            height: 45px !important;
        }
        
        .btn-search:hover {
            background: #ea580c !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 5px 15px rgba(249, 115, 22, 0.4) !important;
        }
        
        /* Notification Banner */
        .notification-banner {
            background: linear-gradient(45deg, #059669, #10b981);
            color: white;
            padding: 10px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .notification-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Database Connection Alert */
        .setup-alert {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            text-align: center;
            animation: slideInDown 0.5s ease-out;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body>
    <!--[if lte IE 9]>
    <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="https://browsehappy.com/">upgrade your browser</a> to improve your experience and security.</p>
    <![endif]-->
    
    <?php if ($connection_status == "error"): ?>
    <div class="setup-alert">
        <h4><i class="fa fa-exclamation-triangle"></i> Database Setup Required</h4>
        <p>Please run the setup files to initialize the database tables:</p>
        <p><strong>setup_website_customization.php</strong> and <strong>carousel_management.php</strong></p>
        <p>Error: <?php echo htmlspecialchars($error_message); ?></p>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($notifications)): ?>
    <div class="notification-banner">
        <div class="container">
            <marquee behavior="scroll" direction="left" scrollamount="3">
                <?php foreach ($notifications as $notification): ?>
                    <span style="margin-right: 50px;">
                        <i class="fa fa-bell"></i> <?php echo htmlspecialchars($notification['message']); ?>
                    </span>
                <?php endforeach; ?>
            </marquee>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- IRCTC Top Header -->
    <div class="irctc-top-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <span><i class="fa fa-phone"></i> <?php echo htmlspecialchars($customization['contact_phone'] ?? '139'); ?> (Railway Enquiry)</span>
                    <span style="margin-left: 20px;"><i class="fa fa-envelope"></i> <?php echo htmlspecialchars($customization['contact_email'] ?? 'care@irctc.co.in'); ?></span>
                    <span style="margin-left: 20px;"><i class="fa fa-clock-o"></i> 24x7 Service</span>
                </div>
                <div class="col-md-4 text-right">
                    <a href="#offers" class="daily-deals">
                        <i class="fa fa-star"></i> DAILY DEALS
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- main-menu Start -->
    <header class="top-area">
        <div class="header-area">
            <div class="container">
                <div class="row">
                    <div class="col-sm-2">
                        <div class="logo">
                            <a href="index(final).php" class="navbar-brand">
                                <?php 
                                $site_title = $customization['site_title'] ?? 'IRCTC Rail Connect';
                                $title_parts = explode(' ', $site_title);
                                if (count($title_parts) >= 2) {
                                    echo htmlspecialchars($title_parts[0]) . '<span>' . htmlspecialchars($title_parts[1]) . '</span>';
                                } else {
                                    echo 'IRCTC<span>Rail</span>';
                                }
                                ?>
                            </a>
                        </div><!-- /.logo-->
                    </div><!-- /.col-->
                    <div class="col-sm-10">
                        <div class="main-menu">
                            <!-- Brand and toggle get grouped for better mobile display -->
                            <div class="navbar-header">
                                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                                    <span class="sr-only">Toggle navigation</span>
                                    <span class="icon-bar"></span>
                                    <span class="icon-bar"></span>
                                    <span class="icon-bar"></span>
                                </button><!-- / button-->
                            </div><!-- /.navbar-header-->
                            <div class="collapse navbar-collapse">		  
                                <ul class="nav navbar-nav navbar-right">
                                    <li class="smooth-menu"><a href="#home">Home</a></li>
                                    <li class="smooth-menu"><a href="#booking">Book Tickets</a></li>
                                    <li class="smooth-menu dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Services <b class="caret"></b></a>
                                        <ul class="dropdown-menu">
                                            <li><a href="../view_passenger.php">My Bookings</a></li>
                                            <li><a href="../cancel_ticket.php">Cancel Ticket</a></li>
                                            <li><a href="../train_schedule.php">Train Schedule</a></li>
                                            <li><a href="../gps_tracking.php">Track Train</a></li>
                                            <li><a href="../weather_integration.php">Weather Info</a></li>
                                        </ul>
                                    </li>
                                    <li class="smooth-menu dropdown">
                                        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Help <b class="caret"></b></a>
                                        <ul class="dropdown-menu">
                                            <li><a href="../feedback_system.php">Feedback</a></li>
                                            <li><a href="#contact">Contact Us</a></li>
                                            <li><a href="#faq">FAQ</a></li>
                                        </ul>
                                    </li>
                                    <li class="smooth-menu"><a href="#contact">Contact</a></li>
                                </ul>
                            </div><!-- /.navbar-collapse -->
                        </div><!-- /.main-menu-->
                    </div><!-- /.col-->
                </div><!-- /.row -->
                <div class="home-border"></div><!-- /.home-border-->
            </div><!-- /.container-->
        </div><!-- /.header-area -->
    </header><!-- /.top-area-->
    <!-- main-menu End -->

    <!--about-us start -->
    <section id="home" class="about-us">
        <div class="container">
            <div class="about-us-content">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="single-about-us">
                            <div class="about-us-txt">
                                <h2>
                                    <?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?>
                                </h2>
                                <div class="about-btn">
                                    <button class="about-view" onclick="scrollToBooking()">
                                        Book Train Tickets
                                    </button>
                                </div><!--/.about-btn-->
                            </div><!--/.about-us-txt-->
                        </div><!--/.single-about-us-->
                    </div><!--/.col-->
                </div><!--/.row-->
            </div><!--/.about-us-content-->
        </div><!--/.container-->
    </section><!--/.about-us-->
    <!--about-us end -->

    <!--travel-box start-->
    <section class="travel-box">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <div class="single-travel-boxes">
                        <div id="desc-tabs" class="desc-tabs">
                            <!-- Dynamic Carousel Section -->
                            <div class="carousel-section" style="margin-bottom: 30px;">
                                <div id="heroCarousel" class="carousel slide" data-ride="carousel" data-interval="5000">
                                    <!-- Indicators -->
                                    <ol class="carousel-indicators">
                                        <?php for ($i = 0; $i < count($carousel_slides); $i++): ?>
                                            <li data-target="#heroCarousel" data-slide-to="<?php echo $i; ?>" <?php echo $i === 0 ? 'class="active"' : ''; ?>></li>
                                        <?php endfor; ?>
                                    </ol>
                                    
                                    <!-- Wrapper for slides -->
                                    <div class="carousel-inner" role="listbox">
                                        <?php foreach ($carousel_slides as $index => $slide): ?>
                                            <div class="item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                <div class="carousel-slide" style="
                                                    background: linear-gradient(rgba(30, 58, 138, 0.7), rgba(30, 58, 138, 0.7)), 
                                                                url('<?php echo htmlspecialchars($slide['image_url'] ?? 'assets/images/slider/slider1.jpg'); ?>') center/cover;
                                                    height: 400px;
                                                    display: flex;
                                                    align-items: center;
                                                    justify-content: center;
                                                    color: white;
                                                    text-align: center;
                                                    border-radius: 10px;
                                                    margin-bottom: 20px;
                                                ">
                                                    <div class="carousel-content">
                                                        <h2 style="font-size: 2.5rem; margin-bottom: 15px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                                                            <?php echo htmlspecialchars($slide['title'] ?? 'Welcome to IRCTC'); ?>
                                                        </h2>
                                                        <p style="font-size: 1.2rem; margin-bottom: 20px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">
                                                            <?php echo htmlspecialchars($slide['description'] ?? 'Book your train tickets online'); ?>
                                                        </p>
                                                        <?php if (!empty($slide['button_text']) && !empty($slide['button_link'])): ?>
                                                            <a href="<?php echo htmlspecialchars($slide['button_link']); ?>" 
                                                               class="btn btn-primary btn-lg" 
                                                               style="background: var(--secondary-color); border: none; padding: 12px 30px; border-radius: 25px;">
                                                                <?php echo htmlspecialchars($slide['button_text']); ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <!-- Left and right controls -->
                                    <a class="left carousel-control" href="#heroCarousel" role="button" data-slide="prev">
                                        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                    <a class="right carousel-control" href="#heroCarousel" role="button" data-slide="next">
                                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Tab Navigation for Booking -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active">
                                    <a href="#trains" aria-controls="trains" role="tab" data-toggle="tab">
                                        <i class="fa fa-train"></i> Book Trains
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#tickets" aria-controls="tickets" role="tab" data-toggle="tab">
                                        <i class="fa fa-ticket"></i> My Tickets
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#status" aria-controls="status" role="tab" data-toggle="tab">
                                        <i class="fa fa-search"></i> PNR Status
                                    </a>
                                </li>
                            </ul>
                            
                            <!-- Tab Content -->
                            <div class="tab-content" id="booking">
                                <!-- Train Booking Tab -->
                                <div role="tabpanel" class="tab-pane active" id="trains">
                                    <div class="tab-para">
                                        <div class="row">
                                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                                <div class="travel-form">
                                                    <form action="../seats/form.php" method="POST" id="trainBookingForm">
                                                        <div class="row">
                                                            <div class="col-lg-3 col-md-3 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>From</h2>
                                                                    <div class="travel-select-icon">
                                                                        <select class="form-control" name="from_station" id="fromStation" required>
                                                                            <option value="">Select Origin</option>
                                                                            <option value="NEW DELHI">NEW DELHI (NDLS)</option>
                                                                            <option value="MUMBAI CENTRAL">MUMBAI CENTRAL (BCT)</option>
                                                                            <option value="CHENNAI CENTRAL">CHENNAI CENTRAL (MAS)</option>
                                                                            <option value="KOLKATA">KOLKATA (HWH)</option>
                                                                            <option value="BANGALORE">BANGALORE (SBC)</option>
                                                                            <option value="HYDERABAD">HYDERABAD (SC)</option>
                                                                            <option value="PUNE">PUNE (PUNE)</option>
                                                                            <option value="AHMEDABAD">AHMEDABAD (ADI)</option>
                                                                        </select>
                                                                    </div><!-- /.travel-select-icon -->
                                                                </div><!--/.single-tab-select-box-->
                                                            </div><!--/.col-->
                                                            
                                                            <div class="col-lg-3 col-md-3 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>To</h2>
                                                                    <div class="travel-select-icon">
                                                                        <select class="form-control" name="to_station" id="toStation" required>
                                                                            <option value="">Select Destination</option>
                                                                            <option value="NEW DELHI">NEW DELHI (NDLS)</option>
                                                                            <option value="MUMBAI CENTRAL">MUMBAI CENTRAL (BCT)</option>
                                                                            <option value="CHENNAI CENTRAL">CHENNAI CENTRAL (MAS)</option>
                                                                            <option value="KOLKATA">KOLKATA (HWH)</option>
                                                                            <option value="BANGALORE">BANGALORE (SBC)</option>
                                                                            <option value="HYDERABAD">HYDERABAD (SC)</option>
                                                                            <option value="PUNE">PUNE (PUNE)</option>
                                                                            <option value="AHMEDABAD">AHMEDABAD (ADI)</option>
                                                                        </select>
                                                                    </div><!-- /.travel-select-icon -->
                                                                </div><!--/.single-tab-select-box-->
                                                            </div><!--/.col-->
                                                            
                                                            <div class="col-lg-2 col-md-2 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>Departure</h2>
                                                                    <div class="travel-check-icon">
                                                                        <input type="date" name="journey_date" id="journeyDate" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                                                    </div><!-- /.travel-check-icon -->
                                                                </div><!--/.single-tab-select-box-->
                                                            </div><!--/.col-->
                                                            
                                                            <div class="col-lg-2 col-md-2 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>Class</h2>
                                                                    <div class="travel-select-icon">
                                                                        <select class="form-control" name="class" required>
                                                                            <option value="">Select Class</option>
                                                                            <option value="SL">Sleeper (SL)</option>
                                                                            <option value="3A">AC 3 Tier (3A)</option>
                                                                            <option value="2A">AC 2 Tier (2A)</option>
                                                                            <option value="1A">AC First Class (1A)</option>
                                                                            <option value="CC">Chair Car (CC)</option>
                                                                        </select>
                                                                    </div><!-- /.travel-select-icon -->
                                                                </div><!--/.single-tab-select-box-->
                                                            </div><!--/.col-->
                                                            
                                                            <div class="col-lg-2 col-md-2 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <div class="travel-search-icon">
                                                                        <input type="submit" class="btn-search" value="Search Trains" style="background: var(--secondary-color); border: none; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                                                                    </div><!-- /.travel-search-icon -->
                                                                </div><!--/.single-tab-select-box-->
                                                            </div><!--/.col-->
                                                        </div><!--/.row-->
                                                    </form><!--/.travel-form-->
                                                </div><!--/.travel-form-->
                                            </div><!--/.col-->
                                        </div><!--/.row-->
                                    </div><!--/.tab-para-->
                                </div><!--/.tabpanel-->
                                
                                <!-- My Tickets Tab -->
                                <div role="tabpanel" class="tab-pane" id="tickets">
                                    <div class="tab-para">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="travel-form">
                                                    <form action="../view_passenger.php" method="POST">
                                                        <div class="row">
                                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>Phone Number</h2>
                                                                    <div class="travel-check-icon">
                                                                        <input type="tel" name="phone" class="form-control" placeholder="Enter your phone number" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>Email</h2>
                                                                    <div class="travel-check-icon">
                                                                        <input type="email" name="email" class="form-control" placeholder="Enter your email">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <div class="travel-search-icon">
                                                                        <input type="submit" class="btn-search" value="View My Tickets" style="background: var(--secondary-color); border: none; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- PNR Status Tab -->
                                <div role="tabpanel" class="tab-pane" id="status">
                                    <div class="tab-para">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="travel-form">
                                                    <form action="../seats/view_passenger.php" method="POST">
                                                        <div class="row">
                                                            <div class="col-lg-6 col-md-6 col-sm-8 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <h2>PNR Number</h2>
                                                                    <div class="travel-check-icon">
                                                                        <input type="text" name="pnr" class="form-control" placeholder="Enter 10-digit PNR number" maxlength="10" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-6 col-md-6 col-sm-4 col-xs-12">
                                                                <div class="single-tab-select-box">
                                                                    <div class="travel-search-icon">
                                                                        <input type="submit" class="btn-search" value="Check Status" style="background: var(--secondary-color); border: none; color: white; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div><!--/.tab content-->
                        </div><!--/.desc-tabs-->
                    </div><!--/.single-travel-box-->
                </div><!--/.col-->
            </div><!--/.row-->
        </div><!--/.container-->
    </section><!--/.travel-box-->
    <!--travel-box end-->

    <!--service start-->
    <section id="service" class="service">
        <div class="container">
            <div class="service-details">
                <div class="section-header text-center">
                    <h2>Our Railway Services</h2>
                    <p>Experience the best of Indian Railways with our comprehensive services</p>
                </div><!--/.section-header-->
                <div class="service-content">
                    <div class="row">
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-train" style="color: var(--primary-color);"></i>
                                </div>
                                <h2><a href="../train_schedule.php">Train Schedule</a></h2>
                                <p>
                                    Check real-time train schedules, platform information, and arrival/departure times for all major routes across India.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                        
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-map-marker" style="color: var(--secondary-color);"></i>
                                </div>
                                <h2><a href="../gps_tracking.php">Live Tracking</a></h2>
                                <p>
                                    Track your train in real-time with GPS technology. Get live updates on train location and expected arrival times.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                        
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-credit-card" style="color: var(--accent-color);"></i>
                                </div>
                                <h2><a href="../payment_integration.php">Secure Payment</a></h2>
                                <p>
                                    Multiple payment options including UPI, cards, net banking, and QR code payments with 100% secure transactions.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                        
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-bell" style="color: var(--primary-color);"></i>
                                </div>
                                <h2><a href="../notification_system.php">Smart Notifications</a></h2>
                                <p>
                                    Get instant SMS and email alerts for booking confirmations, train delays, platform changes, and journey reminders.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                        
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-cloud" style="color: var(--secondary-color);"></i>
                                </div>
                                <h2><a href="../weather_integration.php">Weather Updates</a></h2>
                                <p>
                                    Check weather conditions at your destination and along the route to plan your journey better.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                        
                        <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                            <div class="single-service-item">
                                <div class="single-service-icon">
                                    <i class="fa fa-users" style="color: var(--accent-color);"></i>
                                </div>
                                <h2><a href="../passenger_management.php">Passenger Management</a></h2>
                                <p>
                                    Manage multiple passengers, save frequent travelers, and handle group bookings with ease.
                                </p>
                            </div><!--/.single-service-item-->
                        </div><!--/.col-->
                    </div><!--/.row-->
                </div><!--/.service-content-->
            </div><!--/.service-details-->
        </div><!--/.container-->
    </section><!--/.service-->
    <!--service end-->

    <!--statistics start-->
    <section class="statistics" style="background: linear-gradient(135deg, var(--primary-color), #1e40af); color: white; padding: 80px 0;">
        <div class="container">
            <div class="statistics-content">
                <div class="row">
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="single-statistics-box text-center">
                            <div class="statistics-icon">
                                <i class="fa fa-train" style="font-size: 48px; color: var(--secondary-color); margin-bottom: 20px;"></i>
                            </div>
                            <div class="statistics-content">
                                <div class="counter">12,617</div>
                                <h3>Daily Trains</h3>
                            </div><!--/.statistics-content-->
                        </div><!--/.single-statistics-box-->
                    </div><!--/.col-->
                    
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="single-statistics-box text-center">
                            <div class="statistics-icon">
                                <i class="fa fa-users" style="font-size: 48px; color: var(--secondary-color); margin-bottom: 20px;"></i>
                            </div>
                            <div class="statistics-content">
                                <div class="counter">23</div>
                                <h3>Million Passengers Daily</h3>
                            </div><!--/.statistics-content-->
                        </div><!--/.single-statistics-box-->
                    </div><!--/.col-->
                    
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="single-statistics-box text-center">
                            <div class="statistics-icon">
                                <i class="fa fa-map-marker" style="font-size: 48px; color: var(--secondary-color); margin-bottom: 20px;"></i>
                            </div>
                            <div class="statistics-content">
                                <div class="counter">7,349</div>
                                <h3>Railway Stations</h3>
                            </div><!--/.statistics-content-->
                        </div><!--/.single-statistics-box-->
                    </div><!--/.col-->
                    
                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                        <div class="single-statistics-box text-center">
                            <div class="statistics-icon">
                                <i class="fa fa-road" style="font-size: 48px; color: var(--secondary-color); margin-bottom: 20px;"></i>
                            </div>
                            <div class="statistics-content">
                                <div class="counter">68,155</div>
                                <h3>Route Kilometers</h3>
                            </div><!--/.statistics-content-->
                        </div><!--/.single-statistics-box-->
                    </div><!--/.col-->
                </div><!--/.row-->
            </div><!--/.statistics-content-->
        </div><!--/.container-->
    </section><!--/.statistics-->
    <!--statistics end-->

    <!--special-offer start-->
    <section id="offers" class="special-offer">
        <div class="container">
            <div class="special-offer-content">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="single-special-offer">
                            <div class="special-offer-txt">
                                <h2>Special Railway Offers</h2>
                                <h3>Book Now & Save Up to 25%</h3>
                                <p>
                                    Enjoy exclusive discounts on train bookings. Limited time offers on premium classes, 
                                    group bookings, and advance reservations. Don't miss out on these amazing deals!
                                </p>
                                <div class="special-offer-btn">
                                    <a href="#booking" class="btn-special-offer">
                                        Book Now
                                    </a>
                                </div><!--/.special-offer-btn-->
                            </div><!--/.special-offer-txt-->
                        </div><!--/.single-special-offer-->
                    </div><!--/.col-->
                    <div class="col-sm-4">
                        <div class="single-special-offer">
                            <div class="special-offer-img">
                                <img src="assets/images/special-offer/special-offer.jpg" alt="special-offer" style="border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
                            </div><!--/.special-offer-img-->
                        </div><!--/.single-special-offer-->
                    </div><!--/.col-->
                </div><!--/.row-->
            </div><!--/.special-offer-content-->
        </div><!--/.container-->
    </section><!--/.special-offer-->
    <!--special-offer end-->

    <!--footer start-->
    <footer id="contact" class="footer-copyright">
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="single-footer-item">
                            <div class="footer-logo">
                                <a href="index(final).php" class="navbar-brand">
                                    <?php 
                                    $site_title = $customization['site_title'] ?? 'IRCTC Rail Connect';
                                    $title_parts = explode(' ', $site_title);
                                    if (count($title_parts) >= 2) {
                                        echo htmlspecialchars($title_parts[0]) . '<span>' . htmlspecialchars($title_parts[1]) . '</span>';
                                    } else {
                                        echo 'IRCTC<span>Rail</span>';
                                    }
                                    ?>
                                </a>
                                <p style="margin-top: 15px; color: #666;">
                                    Your trusted partner for seamless railway travel across India. 
                                    Book tickets, track trains, and manage your journey with ease.
                                </p>
                            </div><!--/.footer-logo-->
                        </div><!--/.single-footer-item-->
                    </div><!--/.col-->
                    
                    <div class="col-sm-3">
                        <div class="single-footer-item">
                            <h2>Quick Links</h2>
                            <div class="single-footer-txt">
                                <ul>
                                    <li><a href="#booking"><i class="fa fa-angle-double-right"></i> Book Tickets</a></li>
                                    <li><a href="../view_passenger.php"><i class="fa fa-angle-double-right"></i> View Bookings</a></li>
                                    <li><a href="../cancel_ticket.php"><i class="fa fa-angle-double-right"></i> Cancel Ticket</a></li>
                                    <li><a href="../train_schedule.php"><i class="fa fa-angle-double-right"></i> Train Schedule</a></li>
                                    <li><a href="../gps_tracking.php"><i class="fa fa-angle-double-right"></i> Track Train</a></li>
                                </ul>
                            </div><!--/.single-footer-txt-->
                        </div><!--/.single-footer-item-->
                    </div><!--/.col-->
                    
                    <div class="col-sm-3">
                        <div class="single-footer-item">
                            <h2>Services</h2>
                            <div class="single-footer-txt">
                                <ul>
                                    <li><a href="../passenger_management.php"><i class="fa fa-angle-double-right"></i> Passenger Management</a></li>
                                    <li><a href="../payment_integration.php"><i class="fa fa-angle-double-right"></i> Payment Gateway</a></li>
                                    <li><a href="../notification_system.php"><i class="fa fa-angle-double-right"></i> Notifications</a></li>
                                    <li><a href="../weather_integration.php"><i class="fa fa-angle-double-right"></i> Weather Updates</a></li>
                                    <li><a href="../feedback_system.php"><i class="fa fa-angle-double-right"></i> Feedback</a></li>
                                </ul>
                            </div><!--/.single-footer-txt-->
                        </div><!--/.single-footer-item-->
                    </div><!--/.col-->
                    
                    <div class="col-sm-3">
                        <div class="single-footer-item text-center">
                            <h2>Contact Info</h2>
                            <div class="single-footer-txt">
                                <p><i class="fa fa-phone" style="color: var(--secondary-color);"></i> <?php echo htmlspecialchars($customization['contact_phone'] ?? '139'); ?> (Railway Enquiry)</p>
                                <p><i class="fa fa-envelope" style="color: var(--secondary-color);"></i> <?php echo htmlspecialchars($customization['contact_email'] ?? 'care@irctc.co.in'); ?></p>
                                <p><i class="fa fa-clock-o" style="color: var(--secondary-color);"></i> 24x7 Customer Support</p>
                                <div class="footer-social-icon" style="margin-top: 20px;">
                                    <a href="#" style="color: var(--primary-color); margin: 0 10px; font-size: 20px;"><i class="fa fa-facebook"></i></a>
                                    <a href="#" style="color: var(--primary-color); margin: 0 10px; font-size: 20px;"><i class="fa fa-twitter"></i></a>
                                    <a href="#" style="color: var(--primary-color); margin: 0 10px; font-size: 20px;"><i class="fa fa-instagram"></i></a>
                                    <a href="#" style="color: var(--primary-color); margin: 0 10px; font-size: 20px;"><i class="fa fa-youtube"></i></a>
                                </div>
                            </div><!--/.single-footer-txt-->
                        </div><!--/.single-footer-item-->
                    </div><!--/.col-->
                </div><!--/.row-->
                
                <div class="row">
                    <div class="col-sm-12">
                        <div class="footer-copyright-txt text-center" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?>. All rights reserved. | Powered by Indian Railways</p>
                        </div><!--/.footer-copyright-txt-->
                    </div><!--/.col-->
                </div><!--/.row-->
            </div><!--/.footer-content-->
        </div><!--/.container-->
    </footer><!--/.footer-copyright-->
    <!--footer end-->

    <!-- Include all js compiled plugins (below), or include individual files as needed -->
    <script src="assets/js/jquery.js"></script>
    
    <!--modernizr.min.js-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js"></script>
    
    <!--bootstrap.min.js-->
    <script src="assets/js/bootstrap.min.js"></script>
    
    <!-- bootsnav js -->
    <script src="assets/js/bootsnav.js"></script>

    <!--owl.carousel.js-->
    <script src="assets/js/owl.carousel.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    
    <!--Custom JS-->
    <script src="assets/js/custom.js"></script>
    
    <!-- Custom JavaScript for Enhanced Functionality -->
    <script>
        $(document).ready(function() {
            // Set tomorrow's date as default for journey date
            var tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            var tomorrowString = tomorrow.toISOString().split('T')[0];
            $('#journeyDate').val(tomorrowString);
            
            // Form validation for train booking
            $('#trainBookingForm').on('submit', function(e) {
                var fromStation = $('#fromStation').val();
                var toStation = $('#toStation').val();
                
                if (fromStation === toStation && fromStation !== '') {
                    e.preventDefault();
                    alert('Origin and destination stations cannot be the same!');
                    return false;
                }
                
                // Show loading state
                var submitBtn = $(this).find('input[type="submit"]');
                submitBtn.val('Searching...').prop('disabled', true);
                
                // Re-enable after 3 seconds (in case of errors)
                setTimeout(function() {
                    submitBtn.val('Search Trains').prop('disabled', false);
                }, 3000);
            });
            
            // Smooth scrolling for navigation links
            $('a[href^="#"]').on('click', function(event) {
                var target = $(this.getAttribute('href'));
                if (target.length) {
                    event.preventDefault();
                    $('html, body').stop().animate({
                        scrollTop: target.offset().top - 70
                    }, 1000);
                }
            });
            
            // Counter animation for statistics
            function animateCounter() {
                $('.counter').each(function() {
                    var $this = $(this);
                    var countTo = $this.text().replace(/,/g, '');
                    
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 2000,
                        easing: 'linear',
                        step: function() {
                            var num = Math.floor(this.countNum);
                            if (num >= 1000) {
                                num = num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                            $this.text(num);
                        },
                        complete: function() {
                            var finalNum = parseInt(countTo);
                            if (finalNum >= 1000) {
                                finalNum = finalNum.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
                            }
                            $this.text(finalNum);
                        }
                    });
                });
            }
            
            // Trigger counter animation when statistics section is visible
            var statisticsTriggered = false;
            $(window).scroll(function() {
                var statisticsOffset = $('.statistics').offset();
                if (statisticsOffset && $(window).scrollTop() + $(window).height() > statisticsOffset.top + 100) {
                    if (!statisticsTriggered) {
                        animateCounter();
                        statisticsTriggered = true;
                    }
                }
            });
            
            // Enhanced carousel functionality
            $('#heroCarousel').on('slide.bs.carousel', function(e) {
                var $nextSlide = $(e.relatedTarget);
                $nextSlide.find('.carousel-content').addClass('animated fadeInUp');
            });
            
            $('#heroCarousel').on('slid.bs.carousel', function(e) {
                $(this).find('.carousel-content').removeClass('animated fadeInUp');
            });
            
            // Auto-hide notification banner after 10 seconds
            setTimeout(function() {
                $('.notification-banner').fadeOut('slow');
            }, 10000);
            
            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl+Enter to submit active form
                if (e.ctrlKey && e.keyCode === 13) {
                    var activeForm = $('.tab-pane.active form');
                    if (activeForm.length) {
                        activeForm.submit();
                    }
                }
            });
            
            // Enhanced dropdown functionality
            $('.dropdown').hover(function() {
                $(this).addClass('open');
            }, function() {
                $(this).removeClass('open');
            });
            
            // Responsive navigation improvements
            $('.navbar-toggle').click(function() {
                $('.navbar-collapse').slideToggle();
            });
            
            // Form field focus animations
            $('.form-control').focus(function() {
                $(this).parent().addClass('focused');
            }).blur(function() {
                if ($(this).val() === '') {
                    $(this).parent().removeClass('focused');
                }
            });
        });
        
        // Global function for scrolling to booking section
        function scrollToBooking() {
            $('html, body').animate({
                scrollTop: $('#booking').offset().top - 70
            }, 1000);
        }
        
        // Add loading animation for external links
        $('a[href^="../"]').click(function() {
            var link = $(this);
            var originalText = link.text();
            link.html('<i class="fa fa-spinner fa-spin"></i> Loading...');
            
            setTimeout(function() {
                link.text(originalText);
            }, 2000);
        });
    </script>
</body>
</html>
