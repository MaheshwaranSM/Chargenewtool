<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
$usr_no = $_SESSION['usr_no']; // Assuming user is logged in and usr_no is stored in session

$fromDate = $_GET['from_date'];
$toDate = $_GET['to_date'];

$pdo = new PDO('mysql:host=localhost;dbname=your_db', 'your_user', 'your_password');
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE usr_no = ? AND created_at BETWEEN ? AND ?');
$stmt->execute([$usr_no, $fromDate, $toDate]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'ID');
$sheet->setCellValue('B1', 'User Phone');
$sheet->setCellValue('C1', 'Message');
$sheet->setCellValue('D1', 'Media URL');
$sheet->setCellValue('E1', 'Status');
$sheet->setCellValue('F1', 'Response');
$sheet->setCellValue('G1', 'Created At');

$row = 2;
foreach ($notifications as $notification) {
    $sheet->setCellValue('A' . $row, $notification['id']);
    $sheet->setCellValue('B' . $row, $notification['user_phone']);
    $sheet->setCellValue('C' . $row, $notification['message']);
    $sheet->setCellValue('D' . $row, $notification['media_url']);
    $sheet->setCellValue('E' . $row, $notification['status']);
    $sheet->setCellValue('F' . $row, $notification['response']);
    $sheet->setCellValue('G' . $row, $notification['created_at']);
    $row++;
}

$writer = new Xlsx($spreadsheet);
$filename = 'notifications_' . date('Y-m-d_H-i-s') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$writer->save('php://output');
exit;
?>
