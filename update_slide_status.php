<?php
// Set headers for JSON response
header('Content-Type: application/json');

// Include database connection
require_once 'db.php';

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if required parameters are provided
    if (!isset($_POST['id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required parameters');
    }

    $slideId = intval($_POST['id']);
    $status = $_POST['status'] === '1' ? 1 : 0;

    // Validate ID
    if ($slideId <= 0) {
        throw new Exception('Invalid slide ID');
    }

    // Prepare and execute the update query
    $stmt = $db->prepare("UPDATE carousel_slides SET is_active = ? WHERE id = ?");
    $stmt->bind_param('ii', $status, $slideId);
    $result = $stmt->execute();

    if (!$result) {
        throw new Exception('Failed to update slide status');
    }

    // Update successful
    $response['success'] = true;
    $response['message'] = 'Slide status updated successfully';
    
} catch (Exception $e) {
    http_response_code(400);
    $response['message'] = $e->getMessage();
}

// Return JSON response
echo json_encode($response);
?>
