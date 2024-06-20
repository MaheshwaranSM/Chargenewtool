<?php
require 'vendor/autoload.php'; // Assuming you use Composer for dependencies
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use GuzzleHttp\Client;

// Function to send WhatsApp message
function sendWhatsAppMessage($userPhone, $message, $mediaUrl = null, $apiUrl, $token) {
    $client = new Client();
    $url = $apiUrl . '?receiver=' . urlencode($userPhone) . '&msgtext=' . urlencode($message) . '&token=' . urlencode($token);
    if ($mediaUrl) {
        $url .= '&mediaurl=' . urlencode($mediaUrl);
    }
    $response = $client->get($url);
    return json_decode($response->getBody(), true);
}

session_start();
$usr_no = $_SESSION['usr_no']; // Assuming user is logged in and usr_no is stored in session

// Fetch API settings from database
$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'your_user', 'your_password');
$stmt = $pdo->prepare('SELECT * FROM whatsapp_api_settings WHERE usr_no = ?');
$stmt->execute([$usr_no]);
$apiSettings = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userPhone = $_POST['user_phone'];
    $message = $_POST['message'];
    $mediaUrl = null;

    if (!empty($_FILES['media']['tmp_name'])) {
        // Handle media upload
        $mediaPath = 'uploads/' . basename($_FILES['media']['name']);
        move_uploaded_file($_FILES['media']['tmp_name'], $mediaPath);
        $mediaUrl = 'https://yourdomain.com/' . $mediaPath; // Adjust to your actual media URL
    }

    $response = sendWhatsAppMessage($userPhone, $message, $mediaUrl, $apiSettings['api_url'], $apiSettings['token']);

    // Save notification details to database
    $stmt = $pdo->prepare('INSERT INTO notifications (usr_no, user_phone, message, media_url, status, response) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$usr_no, $userPhone, $message, $mediaUrl, $response['success'] ? 'sent' : 'failed', json_encode($response)]);

    $_SESSION['notification_response'] = $response;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Send WhatsApp Notification</title>
</head>
<body>
    <h1>Send WhatsApp Notification</h1>
    <form method="post" enctype="multipart/form-data">
        <label for="user_phone">User Phone Number:</label>
        <input type="text" id="user_phone" name="user_phone" required><br><br>

        <label for="message">Message:</label>
        <textarea id="message" name="message" required></textarea><br><br>

        <label for="media">Media (optional):</label>
        <input type="file" id="media" name="media"><br><br>

        <label>Balance: </label>
        <span id="balance">Check balance using API or other method</span><br><br>

        <button type="submit">Send</button>
    </form>

    <?php if (isset($_SESSION['notification_response'])): ?>
        <h2>Response:</h2>
        <pre><?php echo json_encode($_SESSION['notification_response'], JSON_PRETTY_PRINT); ?></pre>
        <?php unset($_SESSION['notification_response']); ?>
    <?php endif; ?>

    <h2>Download Responses</h2>
    <form method="get" action="download_responses.php">
        <label for="from_date">From Date:</label>
        <input type="date" id="from_date" name="from_date" required><br><br>
        <label for="to_date">To Date:</label>
        <input type="date" id="to_date" name="to_date" required><br><br>
        <button type="submit">Download Report</button>
    </form>
</body>
</html>
