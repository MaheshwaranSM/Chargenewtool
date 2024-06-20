<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

include 'templates/header.php'; // Replace with your header file
require 'db.php'; // Your database connection file

// Include PhpSpreadsheet classes
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Initialize variables
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Fetch reports if both from and to dates are provided
$reportData = [];
if (!empty($fromDate) && !empty($toDate)) {
    // Convert dates to SQL format
    $fromDateSQL = date('Y-m-d H:i:s', strtotime($fromDate));
    $toDateSQL = date('Y-m-d', strtotime($toDate)) . ' 23:59:59'; // End of selected day

    // Fetch report data for each report type
    $reportData = [
        'top_retailers' => fetchTopRetailersReport($conn, $fromDateSQL, $toDateSQL),
        'top_distributors' => fetchTopDistributorsReport($conn, $fromDateSQL, $toDateSQL),
        'operator_sales' => fetchOperatorSalesReport($conn, $fromDateSQL, $toDateSQL)
    ];

    // If download is requested for any report type
    if (isset($_GET['download']) && $_GET['download'] == 1 && isset($_GET['report_type'])) {
        $reportType = $_GET['report_type'];
        if (isset($reportData[$reportType])) {
            generateExcel($reportData[$reportType], $reportType);
        }
    }
}

// Function to fetch operator sales report
function fetchOperatorSalesReport($conn, $fromDateSQL, $toDateSQL) {
    $sql = "SELECT 
                operator,
                SUM(CASE WHEN status = 'Success' THEN amount ELSE 0 END) AS Success,
                SUM(CASE WHEN status = 'Failure' THEN amount ELSE 0 END) AS Failure,
                SUM(amount) AS Total
            FROM recharge_reports
            WHERE status IN ('Success', 'Failure')
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') >= ?
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') < ?
            GROUP BY operator
            ORDER BY Success DESC";

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

// Function to fetch top distributors report
function fetchTopDistributorsReport($conn, $fromDateSQL, $toDateSQL) {
    $sql = "SELECT 
                COALESCE(parent_name, 'API') AS parent_name,
                SUM(CASE WHEN status = 'Success' THEN amount ELSE 0 END) AS Total_Success,
                SUM(CASE WHEN status = 'Failure' THEN amount ELSE 0 END) AS Total_Failure,
                SUM(amount) AS Total
            FROM recharge_reports
            WHERE status IN ('Success', 'Failure')
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') >= ?
            AND STR_TO_DATE(recharge_date, '%d-%m-%Y %h:%i %p') < ?
            GROUP BY parent_name
            ORDER BY Total_Success DESC";

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

// Function to generate Excel file
function generateExcel($data, $reportType) {
    // Create new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set headers based on report type
    switch ($reportType) {
        case 'top_retailers':
            $sheet->setCellValue('A1', 'Firm Name');
            $sheet->setCellValue('B1', 'Total Success');
            $sheet->setCellValue('C1', 'Total Failure');
            $sheet->setCellValue('D1', 'Total');
            break;
        case 'top_distributors':
            $sheet->setCellValue('A1', 'Parent Name');
            $sheet->setCellValue('B1', 'Total Success');
            $sheet->setCellValue('C1', 'Total Failure');
            $sheet->setCellValue('D1', 'Total');
            break;
        case 'operator_sales':
            $sheet->setCellValue('A1', 'Operator');
            $sheet->setCellValue('B1', 'Success');
            $sheet->setCellValue('C1', 'Failure');
            $sheet->setCellValue('D1', 'Total');
            break;
        default:
            return;
    }

    // Populate data
    $row = 2;
    foreach ($data as $report) {
        switch ($reportType) {
            case 'top_retailers':
                $sheet->setCellValue('A'.$row, $report['firm_name']);
                $sheet->setCellValue('B'.$row, $report['Total_Success']);
                $sheet->setCellValue('C'.$row, $report['Total_Failure']);
                $sheet->setCellValue('D'.$row, $report['Total']);
                break;
            case 'top_distributors':
                $sheet->setCellValue('A'.$row, htmlspecialchars($report['parent_name']));
                $sheet->setCellValue('B'.$row, $report['Total_Success']);
                $sheet->setCellValue('C'.$row, $report['Total_Failure']);
                $sheet->setCellValue('D'.$row, $report['Total']);
                break;
            case 'operator_sales':
                $sheet->setCellValue('A'.$row, $report['operator']);
                $sheet->setCellValue('B'.$row, $report['Success']);
                $sheet->setCellValue('C'.$row, $report['Failure']);
                $sheet->setCellValue('D'.$row, $report['Total']);
                break;
            default:
                continue;
        }
        $row++;
    }

    // Redirect output to a client’s web browser (Xlsx)
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="'. $reportType .'_report.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generation</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Generate Reports</h1>

        <form method="GET" action="">
            <div class="row mb-4">
                <div class="col-md-5">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>" required>
                </div>
                <div class="col-md-5">
    <label for="to_date" class="form-label">To Date</label>
    <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>" required>
</div>
<div class="col-md-2 align-self-end">
    <button type="submit" class="btn btn-primary">Generate</button>
</div>
</div>
</form>

<!-- Report data sections -->
<?php foreach ($reportData as $type => $data) : ?>
    <?php if (!empty($data)) : ?>
        <div id="<?php echo $type; ?>-report" class="mb-5">
            <h2 class="mt-4"><?php echo ucfirst(str_replace('_', ' ', $type)); ?> Report</h2>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <?php foreach (array_keys($data[0]) as $key) : ?>
                            <th><?php echo htmlspecialchars($key); ?></th>
                        <?php endforeach; ?>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $row) : ?>
                        <tr>
                            <?php foreach ($row as $cell) : ?>
                                <td><?php echo htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                            <td>
                                <a href="?download=1&report_type=<?php echo urlencode($type); ?>&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>" class="btn btn-sm btn-success">Download Excel</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endforeach; ?>
</div>

<!-- Bootstrap JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include 'templates/footer.php'; ?>

