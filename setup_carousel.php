<?php
include('connect/db.php');

$db = (new connect())->myconnect();

// Clear existing carousel slides
mysqli_query($db, "DELETE FROM carousel_slides");

// Insert 3 beautiful carousel slides like the McLaren cars example
$carousel_slides = [
    [
        'title' => 'Experience Luxury Travel',
        'description' => 'Discover the finest in railway luxury with our premium train services across India',
        'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
        'button_text' => 'Book Premium',
        'button_link' => '#trains',
        'sort_order' => 1
    ],
    [
        'title' => 'Scenic Routes Await',
        'description' => 'Journey through breathtaking landscapes and create memories that last a lifetime',
        'image' => 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
        'button_text' => 'Explore Routes',
        'button_link' => '#services',
        'sort_order' => 2
    ],
    [
        'title' => 'Modern Fleet Excellence',
        'description' => 'Travel in comfort with our state-of-the-art trains equipped with world-class amenities',
        'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80',
        'button_text' => 'View Fleet',
        'button_link' => '#gallery',
        'sort_order' => 3
    ]
];

foreach ($carousel_slides as $slide) {
    $title = mysqli_real_escape_string($db, $slide['title']);
    $description = mysqli_real_escape_string($db, $slide['description']);
    $image = mysqli_real_escape_string($db, $slide['image']);
    $button_text = mysqli_real_escape_string($db, $slide['button_text']);
    $button_link = mysqli_real_escape_string($db, $slide['button_link']);
    $sort_order = $slide['sort_order'];
    
    $query = "INSERT INTO carousel_slides (title, description, image, button_text, button_link, sort_order, status) 
              VALUES ('$title', '$description', '$image', '$button_text', '$button_link', $sort_order, 'active')";
    
    if (mysqli_query($db, $query)) {
        echo "Added carousel slide: " . $slide['title'] . "<br>";
    } else {
        echo "Error adding slide: " . mysqli_error($db) . "<br>";
    }
}

// Add some sample notifications
mysqli_query($db, "DELETE FROM website_notifications");

$notifications = [
    [
        'title' => 'Welcome to IRCTC!',
        'message' => 'Book your train tickets online with ease. New users get 10% off on first booking.',
        'type' => 'info',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+30 days'))
    ],
    [
        'title' => 'System Maintenance Notice',
        'message' => 'Scheduled maintenance tonight from 2:00 AM to 4:00 AM. Services may be temporarily unavailable.',
        'type' => 'warning',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+1 day'))
    ],
    [
        'title' => 'Special Festival Offer',
        'message' => 'Celebrate with 25% off on all AC class bookings. Limited time offer!',
        'type' => 'success',
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d', strtotime('+7 days'))
    ]
];

foreach ($notifications as $notification) {
    $title = mysqli_real_escape_string($db, $notification['title']);
    $message = mysqli_real_escape_string($db, $notification['message']);
    $type = $notification['type'];
    $start_date = $notification['start_date'];
    $end_date = $notification['end_date'];
    
    $query = "INSERT INTO website_notifications (title, message, type, start_date, end_date, created_by, status) 
              VALUES ('$title', '$message', '$type', '$start_date', '$end_date', 'Admin', 'active')";
    
    if (mysqli_query($db, $query)) {
        echo "Added notification: " . $notification['title'] . "<br>";
    } else {
        echo "Error adding notification: " . mysqli_error($db) . "<br>";
    }
}

echo "<br><strong>Setup completed successfully!</strong><br>";
echo "<a href='index/index.php'>View Website</a> | <a href='website_customization.php'>Admin Panel</a>";

mysqli_close($db);
?>
