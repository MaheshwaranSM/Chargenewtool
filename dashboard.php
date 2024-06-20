<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

include 'templates/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-top: 50px;
        }
        h1 {
            font-size: 28px;
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-control {
            margin-bottom: 15px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0069d9;
            border-color: #0062cc;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .modal-content {
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Dashboard</h1>
        <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
            <div class="mb-3">
                <label for="userListFile" class="form-label">Select User List file to upload:</label>
                <input type="file" class="form-control" id="userListFile" name="userListFile">
            </div>
            <div class="mb-3">
                <label for="rechargeReportFile" class="form-label">Select Recharge Report file to upload:</label>
                <input type="file" class="form-control" id="rechargeReportFile" name="rechargeReportFile">
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Upload Files</button>
            <button type="button" class="btn btn-secondary ms-2" onclick="openReports()">View Reports</button>
        </form>

        <hr>

        <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-success" onclick="openSendNotification()">Send Notification</button>
            <button type="button" class="btn btn-info" onclick="openWhatsAppSettings()">WhatsApp API Settings</button>
        </div>
    </div>

    <!-- Bootstrap JS (optional, only needed if you use Bootstrap's JavaScript features) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Function to open reports in a new tab
        function openReports() {
            window.open('report.php', '_blank');
        }

        // Function to open send notification page in a new tab
        function openSendNotification() {
            window.open('push_notification.php', '_blank');
        }

        // Function to open WhatsApp API settings page in a new tab
        function openWhatsAppSettings() {
            window.open('manage_whatsapp_settings.php', '_blank');
        }

        // Display upload success message
        <?php if (isset($_SESSION['upload_success'])): ?>
            $(document).ready(function(){
                $('#uploadSuccessModal').modal('show');
            });
        <?php unset($_SESSION['upload_success']); ?>
        <?php endif; ?>
    </script>

    <!-- Upload success modal -->
    <div class="modal fade" id="uploadSuccessModal" tabindex="-1" aria-labelledby="uploadSuccessModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadSuccessModalLabel">Upload Complete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Files were uploaded successfully.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
<?php include 'templates/footer.php'; ?>
