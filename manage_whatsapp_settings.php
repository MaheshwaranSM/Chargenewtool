<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

include 'templates/header.php'; // Replace with your header file
require 'db.php'; // Your database connection file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $apiUrl = $_POST['api_url'];
    $token = $_POST['token'];

    // Check if settings already exist
    $stmt = $pdo->prepare('SELECT * FROM whatsapp_api_settings WHERE usr_no = ?');
    $stmt->execute([$usr_no]);
    $existingSettings = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingSettings) {
        // Update existing settings
        $stmt = $pdo->prepare('UPDATE whatsapp_api_settings SET api_url = ?, token = ? WHERE usr_no = ?');
        $stmt->execute([$apiUrl, $token, $usr_no]);
    } else {
        // Insert new settings
        $stmt = $pdo->prepare('INSERT INTO whatsapp_api_settings (usr_no, api_url, token) VALUES (?, ?, ?)');
        $stmt->execute([$usr_no, $apiUrl, $token]);
    }

    $_SESSION['settings_message'] = 'Settings saved successfully!';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch existing settings
$stmt = $pdo->prepare('SELECT * FROM whatsapp_api_settings WHERE usr_no = ?');
$stmt->execute([$usr_no]);
$apiSettings = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage WhatsApp API Settings</title>
</head>
<body>
    <h1>Manage WhatsApp API Settings</h1>
    <?php if (isset($_SESSION['settings_message'])): ?>
        <p><?php echo $_SESSION['settings_message']; unset($_SESSION['settings_message']); ?></p>
    <?php endif; ?>
    <form method="post">
        <label for="api_url">API URL:</label>
        <input type="text" id="api_url" name="api_url" value="<?php echo htmlspecialchars($apiSettings['api_url'] ?? ''); ?>" required><br><br>
        <label for="token">Token:</label>
        <input type="text" id="token" name="token" value="<?php echo htmlspecialchars($apiSettings['token'] ?? ''); ?>" required><br><br>
        <button type="submit">Save Settings</button>
    </form>
</body>
</html>
