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

// Include database connection
require 'db.php';

// Include PhpSpreadsheet classes
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Initialize variables
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$topRetailersReport = [];

// Function to fetch top retailers report
function fetchTopRetailersReport($conn, $fromDateSQL, $toDateSQL) {
    $sql = "SELECT 
                firm_name,
                SUM(CASE WHEN status = 'Success' THEN amount ELSE 0 END) AS Total_Success,
                SUM(CASE WHEN status = 'Failure' THEN amount ELSE 0 END) AS Total_Failure,
                SUM(amount) AS Total
            FROM recharge_reports
            WHERE status IN ('Success', 'Failure')
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') >= ?
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') < ?
            GROUP BY firm_name
            ORDER BY Total DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $fromDateSQL, $toDateSQL);
    $stmt->execute();
    $result = $stmt->get_result();

    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }

    $stmt->close();
    return $reports;
}

// Fetch reports if both from and to dates are provided
if (!empty($fromDate) && !empty($toDate)) {
    // Convert dates to SQL format
    $fromDateSQL = date('Y-m-d H:i:s', strtotime($fromDate));
    $toDateSQL = date('Y-m-d', strtotime($toDate)) . ' 23:59:59'; // End of selected day
    $topRetailersReport = fetchTopRetailersReport($conn, $fromDateSQL, $toDateSQL);
}

// Close database connection
$conn->close();

// Function to generate Excel file
function generateExcel($data) {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers
    $sheet->setCellValue('A1', 'Firm Name');
    $sheet->setCellValue('B1', 'Total Success');
    $sheet->setCellValue('C1', 'Total Failure');
    $sheet->setCellValue('D1', 'Total');

    // Populate data
    $row = 2;
    foreach ($data as $report) {
        $sheet->setCellValue('A'.$row, $report['firm_name']);
        $sheet->setCellValue('B'.$row, $report['Total_Success']);
        $sheet->setCellValue('C'.$row, $report['Total_Failure']);
        $sheet->setCellValue('D'.$row, $report['Total']);
        $row++;
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="top_retailers_report.xlsx"');
    header('Cache-Control: max-age=0');

    // Write to Excel file
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// Check if download request is initiated
if (isset($_GET['download']) && $_GET['download'] == 1) {
    generateExcel($topRetailersReport);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Retailers Report</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            font-size: 28px;
            color: #343a40;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .table {
            width: 100%;
            margin-bottom: 1rem;
            color: #212529;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 0.75rem;
            vertical-align: top;
            border-top: 1px solid #dee2e6;
        }
        .table thead th {
            vertical-align: bottom;
            border-bottom: 2px solid #dee2e6;
        }
        .table tbody + tbody {
            border-top: 2px solid #dee2e6;
        }
        /* Add more styles as needed */
    </style>
</head>
<body>
    <div class="container">
        <h1>Top Retailers Report</h1>

        <!-- Date range selection form -->
        <form action="report.php" method="get">
            <div class="form-group">
                <label for="fromDate">From Date:</label>
                <input type="datetime-local" id="fromDate" name="from_date" value="<?php echo htmlspecialchars($fromDate); ?>" class="form-control">
            </div>
            <div class="form-group">
                <label for="toDate">To Date:</label>
                <input type="datetime-local" id="toDate" name="to_date" value="<?php echo htmlspecialchars($toDate); ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Fetch Reports</button>
            <?php if (!empty($topRetailersReport)) : ?>
                <a href="?download=1&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>" class="btn btn-success">Download Excel</a>
            <?php endif; ?>
        </form>

        <!-- Display top retailers report if available -->
        <?php if (!empty($topRetailersReport)) : ?>
            <h2>Top Retailers Report</h2>
            <table class="table">
                <thead>
                    <tr>
                        <th>Firm Name</th>
                        <th>Total Success</th>
                        <th>Total Failure</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topRetailersReport as $report) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($report['firm_name']); ?></td>
                            <td><?php echo number_format($report['Total_Success'], 2); ?></td>
                            <td><?php echo number_format($report['Total_Failure'], 2); ?></td>
                            <td><?php echo number_format($report['Total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif (!empty($fromDate) && !empty($toDate)) : ?>
            <p>No records found within the selected date range.</p>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS (optional, only needed if you use Bootstrap's JavaScript features) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'templates/footer.php'; ?>
