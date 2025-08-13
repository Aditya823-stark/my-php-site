<?php
class fun {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // ✅ Get route details for selected from-to-train combination
    public function get_route($from, $to, $train_id) {
        $from = (int)$from;
        $to = (int)$to;
        $train_id = (int)$train_id;

        $sql = "SELECT r.* FROM routes r
                JOIN train_routes tr ON tr.route_id = r.id
                WHERE r.from_station_id = $from
                  AND r.to_station_id = $to
                  AND tr.train_id = $train_id
                LIMIT 1";

        $res = mysqli_query($this->db, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    // ✅ Get station name by ID
    public function get_station_name($id) {
        $id = (int)$id;
        $res = mysqli_query($this->db, "SELECT name FROM stations WHERE id = $id");
        if ($row = mysqli_fetch_assoc($res)) {
            return $row['name'];
        }
        return "Unknown Station";
    }

    // ✅ Get train name by ID
    public function get_train_name($id) {
        $id = (int)$id;
        $res = mysqli_query($this->db, "SELECT name FROM trains WHERE id = $id");
        if ($row = mysqli_fetch_assoc($res)) {
            return $row['name'];
        }
        return "Unknown Train";
    }

    // ✅ Get all stations for dropdown
    public function get_all_stations() {
        $res = mysqli_query($this->db, "SELECT id, name FROM stations ORDER BY name ASC");
        $stations = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $stations[] = $row;
        }
        return $stations;
    }

    // ✅ Get all trains for dropdown
    public function get_all_trains() {
        $res = mysqli_query($this->db, "SELECT id, name FROM trains ORDER BY name ASC");
        $trains = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $trains[] = $row;
        }
        return $trains;
    }

    // ✅ Insert a new passenger record
   public function add_passenger($data) {
    // First, check if the train exists in the 'trains' table
    $checkTrainQuery = "SELECT id FROM trains WHERE id = ?";
    $stmtCheck = $this->db->prepare($checkTrainQuery);
    $stmtCheck->bind_param("i", $data['train_id']);
    $stmtCheck->execute();
    $stmtCheck->store_result();

    // If no rows are found, the train_id is invalid
    if ($stmtCheck->num_rows === 0) {
        echo "Invalid train ID!";
        return false;  // Return false if the train doesn't exist
    }

    // Ensure distance is not null - set default value if missing
    if (!isset($data['distance']) || $data['distance'] === null || $data['distance'] === '') {
        $data['distance'] = 0; // Default distance
    }

    // Ensure fare is not null - set default value if missing
    if (!isset($data['fare']) || $data['fare'] === null || $data['fare'] === '') {
        $data['fare'] = 0; // Default fare
    }

    // Proceed with the insert if the train_id is valid
    $sql = "INSERT INTO passengers
            (train_id, name, age, gender, email, phone, password, from_station_id, to_station_id, class_type, journey_date, fare, distance)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
        echo "Failed to prepare statement: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "isisssssissdd",
        $data['train_id'],
        $data['name'],
        $data['age'],
        $data['gender'],
        $data['email'],
        $data['phone'],
        $data['password'],
        $data['from_station_id'],
        $data['to_station_id'],
        $data['class_type'],
        $data['journey_date'],
        $data['fare'],
        $data['distance']
    );

    if ($stmt->execute()) {
        return $this->db->insert_id;
    } else {
        echo "Failed to execute statement: " . $stmt->error;
        return false;
    }
}

    // ✅ Insert a new passenger record with seat number
    public function add_passenger_with_seat($data) {
        // First, check if the train exists in the 'trains' table
        $checkTrainQuery = "SELECT id FROM trains WHERE id = ?";
        $stmtCheck = $this->db->prepare($checkTrainQuery);
        $stmtCheck->bind_param("i", $data['train_id']);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        // If no rows are found, the train_id is invalid
        if ($stmtCheck->num_rows === 0) {
            echo "Invalid train ID!";
            return false;  // Return false if the train doesn't exist
        }

        // Ensure distance is not null - set default value if missing
        if (!isset($data['distance']) || $data['distance'] === null || $data['distance'] === '') {
            $data['distance'] = 0; // Default distance
        }

        // Ensure fare is not null - set default value if missing
        if (!isset($data['fare']) || $data['fare'] === null || $data['fare'] === '') {
            $data['fare'] = 0; // Default fare
        }

        // Proceed with the insert if the train_id is valid
        $sql = "INSERT INTO passengers
                (train_id, name, age, gender, email, phone, password, from_station_id, to_station_id, class_type, journey_date, fare, distance, seat_no, payment_status, departure_time, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            echo "Failed to prepare statement: " . $this->db->error;
            return false;
        }

        $payment_status = ($data['payment_mode'] === 'Online') ? 'Paid' : 'Pending';
        $departure_time = $data['departure_time'] ?? '08:00';

        // Debug: Check if data is properly passed
        error_log("Passenger data: " . print_r($data, true));

        $stmt->bind_param(
            "isisssssissddsss",
            $data['train_id'],
            $data['name'],
            $data['age'],
            $data['gender'],
            $data['email'],
            $data['phone'],
            $data['password'],
            $data['from_station_id'],
            $data['to_station_id'],
            $data['class_type'],
            $data['journey_date'],
            $data['fare'],
            $data['distance'],
            $data['seat_no'],
            $payment_status,
            $departure_time
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        } else {
            echo "Failed to execute statement: " . $stmt->error;
            return false;
        }
    }

    

    // ✅ Fetch latest passenger record (for ticket view)
    public function get_last_passenger() {
        $sql = "SELECT * FROM passengers ORDER BY id DESC LIMIT 1";
        $res = mysqli_query($this->db, $sql);
        if ($res && mysqli_num_rows($res) > 0) {
            return mysqli_fetch_assoc($res);
        }
        return null;
    }

    // ✅ Get all route details with train and station names
    public function get_all_routes_with_train() {
        $sql = "SELECT r.*, s1.name as from_station, s2.name as to_station, t.name as train_name
                FROM routes r
                JOIN stations s1 ON r.from_station_id = s1.id
                JOIN stations s2 ON r.to_station_id = s2.id
                JOIN train_routes tr ON tr.route_id = r.id
                JOIN trains t ON tr.train_id = t.id";
        $res = mysqli_query($this->db, $sql);
        $routes = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $routes[] = $row;
        }
        return $routes;
    }

    // ✅ Get full train details by ID (optional utility)
    public function get_train_by_id($id) {
        $id = (int)$id;
        $res = mysqli_query($this->db, "SELECT * FROM trains WHERE id = $id");
        if ($row = mysqli_fetch_assoc($res)) {
            return $row;
        }
        return null;
    }

//     public function get_train_by_id($id) {
//     $id = (int)$id;
//     $res = mysqli_query($this->db, "SELECT * FROM trains WHERE id = $id");
//     return mysqli_fetch_assoc($res);
// }


    // Get route details for selected from-to-train combination


    // Get station name by ID


    // Get train name by ID


    // Get all stations

    // Get all trains

    // Insert a new passenger


    // Get latest passenger


    // Get all routes with train and station info


    // Get full train info by ID
 public function get_station_by_id($id) {
    $id = (int)$id;
    $sql = "SELECT * FROM stations WHERE id = $id";
    $res = mysqli_query($this->db, $sql);
    if (mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

public function station_exists($station_name) {
    $station_name = mysqli_real_escape_string($this->db, $station_name);
    $res = mysqli_query($this->db, "SELECT id FROM stations WHERE name = '$station_name'");
    return mysqli_num_rows($res) > 0;
}

public function train_exists($train_name) {
    $train_name = mysqli_real_escape_string($this->db, $train_name);
    $res = mysqli_query($this->db, "SELECT id FROM trains WHERE name = '$train_name'");
    return mysqli_num_rows($res) > 0;
}

public function get_route_by_id($route_id) {
    $route_id = (int)$route_id;
    $sql = "SELECT * FROM routes WHERE id = $route_id";
    $res = mysqli_query($this->db, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

public function get_passenger_by_id($passenger_id) {
    $passenger_id = (int)$passenger_id;
    $sql = "SELECT * FROM passengers WHERE id = $passenger_id";
    $res = mysqli_query($this->db, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

public function format_rupees($amount) {
    return "₹" . number_format($amount, 2);
}

public function get_cancelled_passengers() {
    $sql = "SELECT * FROM passengers WHERE status = 'Cancelled'";
    $res = mysqli_query($this->db, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $data[] = $row;
    }
    return $data;
}

public function delete_route($route_id) {
    $route_id = (int)$route_id;
    return mysqli_query($this->db, "DELETE FROM routes WHERE id = $route_id");
}

public function total_passenger_count() {
    $res = mysqli_query($this->db, "SELECT COUNT(*) as total FROM passengers");
    $row = mysqli_fetch_assoc($res);
    return $row['total'];
}

// ✅ Get all passengers with train and station names
public function get_all_passengers() {
    $sql = "SELECT p.*, 
                   t.name as train_name, 
                   s1.name as from_station, 
                   s2.name as to_station 
            FROM passengers p
            LEFT JOIN trains t ON p.train_id = t.id
            LEFT JOIN stations s1 ON p.from_station_id = s1.id
            LEFT JOIN stations s2 ON p.to_station_id = s2.id
            ORDER BY p.id DESC";
    
    $res = mysqli_query($this->db, $sql);
    $passengers = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $passengers[] = $row;
    }
    return $passengers;
}

public function delete_passenger($id) {
    $id = intval($id);
    $query = "DELETE FROM passengers WHERE id = $id";
    return mysqli_query($this->db, $query);
}

public function get_available_seats($train_id, $journey_date, $class_type) {
    $train_id = (int)$train_id;
    $journey_date = mysqli_real_escape_string($this->db, $journey_date);
    $class_type = mysqli_real_escape_string($this->db, $class_type);

    $seats = [];
    $sql = "SELECT seat_no FROM seats 
            WHERE train_id = $train_id AND class_type = '$class_type' 
            AND seat_no NOT IN (
                SELECT seat_no FROM passengers 
                WHERE train_id = $train_id 
                AND journey_date = '$journey_date'
                AND class_type = '$class_type'
            )";

    $res = mysqli_query($this->db, $sql);
    while ($row = mysqli_fetch_assoc($res)) {
        $seats[] = $row['seat_no'];
    }
    return $seats;
}


public function assign_random_seat($train_id, $journey_date, $class_type) {
    $available = $this->get_available_seats($train_id, $journey_date, $class_type);
    if (!empty($available)) {
        return $available[array_rand($available)];
    }
    return null; // No seat available
}

public function add_seat($train_id, $seat_no, $class_type) {
    $train_id = (int)$train_id;
    $seat_no = mysqli_real_escape_string($this->db, $seat_no);
    $class_type = mysqli_real_escape_string($this->db, $class_type);

    $sql = "INSERT INTO seats (train_id, seat_no, class_type) 
            VALUES ($train_id, '$seat_no', '$class_type')";
    return mysqli_query($this->db, $sql);
}

public function get_passenger_seat($passenger_id) {
    $passenger_id = (int)$passenger_id;
    $res = mysqli_query($this->db, "SELECT seat_no FROM passengers WHERE id = $passenger_id");
    if ($row = mysqli_fetch_assoc($res)) {
        return $row['seat_no'];
    }
    return null;
}

public function total_available_seats($train_id, $journey_date, $class_type) {
    $available = $this->get_available_seats($train_id, $journey_date, $class_type);
    return count($available);
}

    public function get_stations() {
        $result = mysqli_query($this->db, "SELECT * FROM stations");
        $stations = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stations[] = $row;
        }
        return $stations;
    }

    // ✅ Fetch all trains
    public function get_trains() {
        $result = mysqli_query($this->db, "SELECT * FROM trains");
        $trains = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $trains[] = $row;
        }
        return $trains;
    }

    public function is_seat_available($train_id, $class_type, $seat_no, $journey_date) {
    $train_id = (int)$train_id;
    $seat_no = mysqli_real_escape_string($this->db, $seat_no);
    $class_type = mysqli_real_escape_string($this->db, $class_type);
    $journey_date = mysqli_real_escape_string($this->db, $journey_date);

    $sql = "SELECT COUNT(*) as count FROM passengers
            WHERE train_id = $train_id
              AND class_type = '$class_type'
              AND seat_no = '$seat_no'
              AND journey_date = '$journey_date'";

    $res = mysqli_query($this->db, $sql);
    $row = mysqli_fetch_assoc($res);
    return $row['count'] == 0; // true if not booked
}

public function getAvailableSeatsByClass($train_id, $route_id, $journey_date, $class) {
    $booked = $this->getBookedSeats($train_id, $route_id, $journey_date, $class);
    $allSeats = $this->getTotalSeatsForClass($class); // A, B, S, etc.
    return array_diff($allSeats, $booked);
}

public function getTotalSeatsForClass($class) {
    switch (strtoupper($class)) {
        case 'A':
            return range(1, 18);  // 3 coaches × 6 seats
        case 'S':
            return range(1, 72);  // 9 coaches × 8 seats
        case 'B':
            return range(1, 36);  // 6 coaches × 6 seats
        default:
            return range(1, 50);  // fallback
    }
}

public function seatIsAvailable($train_id, $route_id, $journey_date, $seat_no, $class) {
    $booked = $this->getBookedSeats($train_id, $route_id, $journey_date, $class);
    return !in_array($seat_no, $booked);
}

public function getFare($route_id) {
    $q = mysqli_query($this->db, "SELECT fare FROM routes WHERE id = $route_id");
    $r = mysqli_fetch_assoc($q);
    return $r ? $r['fare'] : 0;
}

public function isEmailValid($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

public function generateBookingID() {
    return strtoupper("BK" . uniqid(rand(1000, 9999)));
}

public function getStationName($station_id) {
    $q = mysqli_query($this->db, "SELECT name FROM stations WHERE id = $station_id");
    $r = mysqli_fetch_assoc($q);
    return $r ? $r['name'] : 'Unknown Station';
}

public function getDistance($from_station_id, $to_station_id) {
    $query = "SELECT distance FROM routes 
              WHERE from_station_id = $from_station_id 
              AND to_station_id = $to_station_id LIMIT 1";
    $result = mysqli_query($this->db, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['distance'];
    }
    return 0; // default if not found
}


public function getTrainName($train_id) {
    $q = mysqli_query($this->db, "SELECT name FROM trains WHERE id = $train_id");
    $row = mysqli_fetch_assoc($q);
    return $row ? $row['name'] : "Unknown";
}

public function get_trains_between($from_station_id, $to_station_id) {
    $trains = [];

    $query = "
        SELECT 
            t.id AS train_id, 
            t.name AS train_name, 
            r.id AS route_id, 
            r.distance, 
            r.fare 
        FROM train_routes tr
        JOIN routes r ON tr.route_id = r.id
        JOIN trains t ON tr.train_id = t.id
        WHERE r.from_station_id = '$from_station_id' 
          AND r.to_station_id = '$to_station_id'
    ";

    $result = mysqli_query($this->db, $query);  // using $this->db instead of $this->con

    while ($row = mysqli_fetch_assoc($result)) {
        $trains[] = $row;
    }

    return $trains;
}













}
?>
