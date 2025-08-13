<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

include('../connect/db.php');

try {
    $db = (new connect())->myconnect();
    
    // Get active notifications that are within their date range
    $query = "SELECT id, title, message, type, created_at, start_date, end_date 
              FROM website_notifications 
              WHERE status = 'active' 
              AND (start_date IS NULL OR start_date <= CURDATE()) 
              AND (end_date IS NULL OR end_date >= CURDATE()) 
              ORDER BY created_at DESC 
              LIMIT 10";
    
    $result = mysqli_query($db, $query);
    $notifications = [];
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $notifications[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'created_at' => date('M d, Y', strtotime($row['created_at'])),
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date']
            ];
        }
    }
    
    echo json_encode($notifications);
    
} catch (Exception $e) {
    // Return empty array on error
    echo json_encode([]);
}

mysqli_close($db);
?>
