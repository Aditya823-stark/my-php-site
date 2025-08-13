<?php
// Test file for payment_qr.php functionality
// This simulates the data that would come from select_seats.php

// Simulate POST data that would come from the seat selection form
$_POST = [
    'name' => 'John Doe',
    'age' => '30',
    'gender' => 'Male',
    'email' => 'john.doe@example.com',
    'phone' => '9876543210',
    'password' => 'test123',
    'from_station_id' => '1',
    'to_station_id' => '2',
    'train_id' => '1',
    'class_type' => 'AC Tier 2',
    'journey_date' => '2025-08-15',
    'payment_mode' => 'Online',
    'departure_time' => '08:00 AM',
    'selected_seats' => '1,2'
];

echo "<h2>Testing Payment QR Generation</h2>";
echo "<p><strong>Test Data:</strong></p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
echo "<hr>";

// Include the payment_qr.php file
include('payment_qr.php');
?>