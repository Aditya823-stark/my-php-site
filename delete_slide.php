<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if ID is provided
    if (!isset($_POST['id'])) {
        throw new Exception('Missing slide ID');
    }

    $slideId = intval($_POST['id']);

    // Validate ID
    if ($slideId <= 0) {
        throw new Exception('Invalid slide ID');
    }

    // First, get the image path if it's a local file
    $stmt = $db->prepare("SELECT image_url FROM carousel_slides WHERE id = ?");
    $stmt->bind_param('i', $slideId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Slide not found');
    }
    
    $slide = $result->fetch_assoc();
    $imagePath = $slide['image_url'];

    // Delete the database record
    $stmt = $db->prepare("DELETE FROM carousel_slides WHERE id = ?");
    $stmt->bind_param('i', $slideId);
    $result = $stmt->execute();

    if (!$result) {
        throw new Exception('Failed to delete slide from database');
    }

    // If the image is a local file and exists, delete it
    if (!empty($imagePath) && strpos($imagePath, 'http') !== 0 && file_exists($imagePath)) {
        @unlink($imagePath);
    }

    // Deletion successful
    $response['success'] = true;
    $response['message'] = 'Slide deleted successfully';
    
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
