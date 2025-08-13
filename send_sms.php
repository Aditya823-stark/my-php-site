<?php
// Target phone number
$phone_number = "8010143603"; // Replace with real number

// Message content
$message = "Hello Ajinkya! Your railway ticket is booked successfully. Train: Express 12123, From: Pune To: Mumbai.";

// Prepare data
$fields = array(
    "sender_id" => "FSTSMS",
    "message" => $message,
    "language" => "english",
    "route" => "p",
    "numbers" => $phone_number,
);

// Initialize cURL
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "7XL8jiaUoKOh0SmZz6Dd12NTtHE4Pkg3rAIeMwsvCuyf9nQblJgq1Y2w5x6cPeEMtJXh37inUOmQb8Rj",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($fields),
    CURLOPT_HTTPHEADER => array(
        "authorization: YOUR_API_KEY",  // ✅ Replace with your actual API key
        "accept: */*",
        "cache-control: no-cache",
        "content-type: application/json"
    ),
));

// Send request
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

// Display result
if ($err) {
    echo "cURL Error #:" . $err;
} else {
    echo "SMS Sent Successfully. API Response: <br>";
    echo $response;
}
?>
