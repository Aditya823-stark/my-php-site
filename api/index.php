<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

include('../connect/db.php');
include('../connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create API users table
$create_api_users = "CREATE TABLE IF NOT EXISTS api_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NULL,
    app_name VARCHAR(100) NOT NULL,
    permissions JSON,
    rate_limit INT DEFAULT 1000,
    requests_made INT DEFAULT 0,
    last_request TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES passengers(id)
)";
mysqli_query($db, $create_api_users);

// Create API logs table
$create_api_logs = "CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(255),
    endpoint VARCHAR(255) NOT NULL,
    method VARCHAR(10) NOT NULL,
    request_data JSON,
    response_code INT NOT NULL,
    response_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    execution_time DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_api_logs);

// API Router
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = str_replace('/RAILWAY/kaiadmin-lite-1.2.0/admin/api', '', $path);
$path_parts = explode('/', trim($path, '/'));

// Authentication middleware
function authenticate() {
    global $db;
    
    $headers = getallheaders();
    $api_key = $headers['Authorization'] ?? $headers['authorization'] ?? $_GET['api_key'] ?? null;
    
    if (!$api_key) {
        return false;
    }
    
    $api_key = str_replace('Bearer ', '', $api_key);
    
    $stmt = mysqli_prepare($db, "SELECT * FROM api_users WHERE api_key = ? AND is_active = 1");
    mysqli_stmt_bind_param($stmt, "s", $api_key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($user = mysqli_fetch_assoc($result)) {
        // Update request count
        mysqli_query($db, "UPDATE api_users SET requests_made = requests_made + 1, last_request = NOW() WHERE api_key = '$api_key'");
        return $user;
    }
    
    return false;
}

// Log API request
function logRequest($endpoint, $method, $request_data, $response_code, $response_data, $execution_time, $api_key = null) {
    global $db;
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $request_json = json_encode($request_data);
    $response_json = json_encode($response_data);
    
    $stmt = mysqli_prepare($db, "INSERT INTO api_logs (api_key, endpoint, method, request_data, response_code, response_data, ip_address, user_agent, execution_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssisssd", $api_key, $endpoint, $method, $request_json, $response_code, $response_json, $ip, $user_agent, $execution_time);
    mysqli_stmt_execute($stmt);
}

// Response helper
function sendResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Start timing
$start_time = microtime(true);

// Route handling
try {
    switch ($path_parts[0]) {
        case 'auth':
            handleAuth($path_parts, $request_method);
            break;
        case 'trains':
            handleTrains($path_parts, $request_method);
            break;
        case 'stations':
            handleStations($path_parts, $request_method);
            break;
        case 'bookings':
            handleBookings($path_parts, $request_method);
            break;
        case 'schedules':
            handleSchedules($path_parts, $request_method);
            break;
        case 'passengers':
            handlePassengers($path_parts, $request_method);
            break;
        case 'feedback':
            handleFeedback($path_parts, $request_method);
            break;
        default:
            sendResponse(['error' => 'Endpoint not found'], 404);
    }
} catch (Exception $e) {
    $execution_time = microtime(true) - $start_time;
    logRequest($path, $request_method, $_REQUEST, 500, ['error' => $e->getMessage()], $execution_time);
    sendResponse(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
}

// Auth endpoints
function handleAuth($path_parts, $method) {
    global $db, $fun;
    
    if ($method === 'POST' && isset($path_parts[1]) && $path_parts[1] === 'login') {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            sendResponse(['error' => 'Email and password required'], 400);
        }
        
        $stmt = mysqli_prepare($db, "SELECT * FROM passengers WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                // Generate API key
                $api_key = 'mob_' . bin2hex(random_bytes(32));
                
                $stmt = mysqli_prepare($db, "INSERT INTO api_users (api_key, user_id, app_name, permissions) VALUES (?, ?, 'Mobile App', ?)");
                $permissions = json_encode(['bookings' => ['create', 'view'], 'trains' => ['view'], 'feedback' => ['create']]);
                mysqli_stmt_bind_param($stmt, "sis", $api_key, $user['id'], $permissions);
                mysqli_stmt_execute($stmt);
                
                sendResponse([
                    'success' => true,
                    'api_key' => $api_key,
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ]
                ]);
            }
        }
        
        sendResponse(['error' => 'Invalid credentials'], 401);
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Trains endpoints
function handleTrains($path_parts, $method) {
    global $db, $fun;
    
    if ($method === 'GET') {
        $trains = $fun->get_all_trains();
        sendResponse(['success' => true, 'data' => $trains]);
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Stations endpoints
function handleStations($path_parts, $method) {
    global $db;
    
    if ($method === 'GET') {
        $result = mysqli_query($db, "SELECT * FROM stations ORDER BY name");
        $stations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stations[] = $row;
        }
        sendResponse(['success' => true, 'data' => $stations]);
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Bookings endpoints
function handleBookings($path_parts, $method) {
    global $db, $fun;
    
    $user = authenticate();
    if (!$user) {
        sendResponse(['error' => 'Authentication required'], 401);
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['train_id', 'name', 'age', 'gender', 'from_station_id', 'to_station_id', 'class_type', 'journey_date'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                sendResponse(['error' => "Field $field is required"], 400);
            }
        }
        
        $booking_data = [
            'train_id' => (int)$input['train_id'],
            'name' => $input['name'],
            'age' => (int)$input['age'],
            'gender' => $input['gender'],
            'email' => $input['email'] ?? '',
            'phone' => $input['phone'] ?? '',
            'password' => password_hash($input['phone'] ?? 'default', PASSWORD_DEFAULT),
            'from_station_id' => (int)$input['from_station_id'],
            'to_station_id' => (int)$input['to_station_id'],
            'class_type' => $input['class_type'],
            'journey_date' => $input['journey_date'],
            'fare' => (float)($input['fare'] ?? 0),
            'distance' => (float)($input['distance'] ?? 0)
        ];
        
        $passenger_id = $fun->add_passenger($booking_data);
        
        if ($passenger_id) {
            sendResponse([
                'success' => true,
                'booking_id' => $passenger_id,
                'message' => 'Booking created successfully'
            ]);
        } else {
            sendResponse(['error' => 'Failed to create booking'], 500);
        }
    }
    
    if ($method === 'GET') {
        if (isset($path_parts[1])) {
            // Get specific booking
            $booking_id = (int)$path_parts[1];
            $stmt = mysqli_prepare($db, "SELECT p.*, t.name as train_name, fs.name as from_station, ts.name as to_station FROM passengers p LEFT JOIN trains t ON p.train_id = t.id LEFT JOIN stations fs ON p.from_station_id = fs.id LEFT JOIN stations ts ON p.to_station_id = ts.id WHERE p.id = ?");
            mysqli_stmt_bind_param($stmt, "i", $booking_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($booking = mysqli_fetch_assoc($result)) {
                sendResponse(['success' => true, 'data' => $booking]);
            } else {
                sendResponse(['error' => 'Booking not found'], 404);
            }
        } else {
            // Get user's bookings
            $stmt = mysqli_prepare($db, "SELECT p.*, t.name as train_name, fs.name as from_station, ts.name as to_station FROM passengers p LEFT JOIN trains t ON p.train_id = t.id LEFT JOIN stations fs ON p.from_station_id = fs.id LEFT JOIN stations ts ON p.to_station_id = ts.id WHERE p.email = (SELECT email FROM passengers WHERE id = ?) ORDER BY p.created_at DESC");
            mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            $bookings = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $bookings[] = $row;
            }
            
            sendResponse(['success' => true, 'data' => $bookings]);
        }
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Schedules endpoints
function handleSchedules($path_parts, $method) {
    global $db;
    
    if ($method === 'GET') {
        $from_station = $_GET['from_station'] ?? '';
        $to_station = $_GET['to_station'] ?? '';
        $journey_date = $_GET['journey_date'] ?? '';
        
        $query = "SELECT t.*, fs.name as from_station, ts.name as to_station, 
                  COUNT(p.id) as booked_seats
                  FROM trains t 
                  LEFT JOIN stations fs ON t.from_station_id = fs.id 
                  LEFT JOIN stations ts ON t.to_station_id = ts.id 
                  LEFT JOIN passengers p ON t.id = p.train_id AND p.journey_date = ?
                  WHERE 1=1";
        
        $params = [$journey_date];
        $types = "s";
        
        if ($from_station) {
            $query .= " AND fs.name LIKE ?";
            $params[] = "%$from_station%";
            $types .= "s";
        }
        
        if ($to_station) {
            $query .= " AND ts.name LIKE ?";
            $params[] = "%$to_station%";
            $types .= "s";
        }
        
        $query .= " GROUP BY t.id ORDER BY t.departure_time";
        
        $stmt = mysqli_prepare($db, $query);
        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $schedules = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $schedules[] = $row;
        }
        
        sendResponse(['success' => true, 'data' => $schedules]);
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Passengers endpoints
function handlePassengers($path_parts, $method) {
    $user = authenticate();
    if (!$user) {
        sendResponse(['error' => 'Authentication required'], 401);
    }
    
    if ($method === 'GET' && isset($path_parts[1]) && $path_parts[1] === 'profile') {
        global $db;
        $stmt = mysqli_prepare($db, "SELECT id, name, email, phone, age, gender FROM passengers WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($profile = mysqli_fetch_assoc($result)) {
            sendResponse(['success' => true, 'data' => $profile]);
        } else {
            sendResponse(['error' => 'Profile not found'], 404);
        }
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Feedback endpoints
function handleFeedback($path_parts, $method) {
    global $db;
    
    $user = authenticate();
    if (!$user) {
        sendResponse(['error' => 'Authentication required'], 401);
    }
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required_fields = ['train_id', 'feedback_type', 'subject', 'message'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                sendResponse(['error' => "Field $field is required"], 400);
            }
        }
        
        $stmt = mysqli_prepare($db, "INSERT INTO passenger_feedback (passenger_id, train_id, feedback_type, rating, subject, message, category) VALUES (?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iisisss", 
            $user['user_id'],
            $input['train_id'],
            $input['feedback_type'],
            $input['rating'] ?? null,
            $input['subject'],
            $input['message'],
            $input['category'] ?? 'Other'
        );
        
        if (mysqli_stmt_execute($stmt)) {
            sendResponse(['success' => true, 'message' => 'Feedback submitted successfully']);
        } else {
            sendResponse(['error' => 'Failed to submit feedback'], 500);
        }
    }
    
    sendResponse(['error' => 'Method not allowed'], 405);
}

// Log the request
$execution_time = microtime(true) - $start_time;
$api_key = authenticate() ? authenticate()['api_key'] : null;
logRequest($path, $request_method, $_REQUEST, http_response_code(), [], $execution_time, $api_key);
?>
