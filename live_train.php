<?php
// Function to fetch data from NTES public API
function getLiveTrainStatus($trainNumber) {
    $url = "https://enquiry.indianrail.gov.in/ntes/NTES?action=getTrainRunningStatus&trainNo={$trainNumber}";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            "User-Agent: Mozilla/5.0"
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        return null;
    }

    // NTES sometimes returns JSON inside a callback function → clean it
    $response = trim($response);
    $response = preg_replace('/^\w+\((.*)\)$/', '$1', $response);

    $data = json_decode($response, true);

    return $data;
}

// Example: Train number 12627
$trainNo = $_GET['train'] ?? '12627';
$data = getLiveTrainStatus($trainNo);

if (!$data) {
    echo "<h3>Unable to fetch live train status. Please try again.</h3>";
    exit;
}

echo "<h2>Live Train Status for {$trainNo}</h2>";
echo "<pre>";
print_r($data);
echo "</pre>";
?>
