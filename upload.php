<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'vendor/autoload.php';
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: index.php');
    exit;
}

require 'db.php'; // Assuming db.php contains your database connection code

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

function uploadUserList($filePath, $conn) {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    foreach ($data as $key => $row) {
        if ($key === 0) continue; // Skip header row

        // Convert each element to string
        $userId = (string) $row[0];

        // Check if user_id already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // User already exists, skip this row or handle accordingly
            continue;
        }

        $personName = (string) $row[1];
        $firmName = (string) $row[2];
        $usertype = (string) $row[3];
        $email = (string) $row[4];
        $mobile = (string) $row[5];
        $whatsappNo = (string) $row[6];
        $mainBalance = (string) $row[7];
        $utilityBalance = (string) $row[8];
        $aepsBalance = (string) $row[9];
        $totalBalance = (string) $row[10];
        $parentId = (string) $row[11];
        $parentName = (string) $row[12];
        $parentType = (string) $row[13];
        $topParentId = (string) $row[14];
        $topParentName = (string) $row[15];
        $topParentType = (string) $row[16];
        $status = (string) $row[17];
        $kycStatus = (string) $row[18];
        $registeredOn = (string) $row[19];
        $remark = (string) $row[20];
        $address = (string) $row[21];
        $city = (string) $row[22];
        $state = (string) $row[23];
        $pinCode = (string) $row[24];
        $panNo = (string) $row[25];
        $adhaarNo = (string) $row[26];
        $tsmName = (string) $row[27];
        $lockedAmountRecharge = (string) $row[28];
        $lockedAmountAeps = (string) $row[29];

        // Prepare and execute the SQL insert statement
        $stmt = $conn->prepare("INSERT INTO users 
            (user_id, person_name, firm_name, usertype, email, mobile, whatsapp_no, main_balance, utility_balance, aeps_balance, total_balance, 
            parent_id, parent_name, parent_type, top_parent_id, top_parent_name, top_parent_type, status, kyc_status, registered_on, 
            remark, address, city, state, pin_code, pan_no, adhaar_no, tsm_name, locked_amount_recharge, locked_amount_aeps) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "ssssssssssssssssssssssssssss",
            $userId, $personName, $firmName, $usertype, $email, $mobile, $whatsappNo, $mainBalance, $utilityBalance, $aepsBalance, $totalBalance,
            $parentId, $parentName, $parentType, $topParentId, $topParentName, $topParentType, $status, $kycStatus, $registeredOn,
            $remark, $address, $city, $state, $pinCode, $panNo, $adhaarNo, $tsmName, $lockedAmountRecharge, $lockedAmountAeps
        );

        $stmt->execute();
    }
}

function uploadRechargeReport($filePath, $conn) {
    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    $spreadsheet = $reader->load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $data = $sheet->toArray();

    foreach ($data as $key => $row) {
        if ($key < 4) continue; // Skip header rows

        // Convert each element to string
        $rechargeId = (string) $row[0];

        // Check if recharge_id already exists
        $stmt = $conn->prepare("SELECT recharge_id FROM recharge_reports WHERE recharge_id = ?");
        $stmt->bind_param("s", $rechargeId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Recharge id already exists, skip this row or handle accordingly
            continue;
        }

        $rechargeDate = (string) $row[1];
        $updatedDate = (string) $row[2];
        $userId = (string) $row[3];
        $firmName = (string) $row[4];
        $parentId = (string) $row[5];
        $parentName = (string) $row[6];
        $operator = (string) $row[7];
        $serviceType = (string) $row[8];
        $circle = (string) $row[9];
        $number = (string) $row[10];
        $amount = (string) $row[11];
        $margin = (string) $row[12];
        $status = (string) $row[13];
        $clientRefId = (string) $row[14];
        $operatorId = (string) $row[15];
        $afterBalance = (string) $row[16];
        $api = (string) $row[17];
        $providerId = (string) $row[18];
        $apiBalance = (string) $row[19];
        $mode = (string) $row[20];
        $extraParams = (string) $row[21];

        // Prepare and execute the SQL insert statement
        $stmt = $conn->prepare("INSERT INTO recharge_reports 
            (recharge_id, recharge_date, updated_date, user_id, firm_name, parent_id, parent_name, operator, service_type, circle, 
            number, amount, margin, status, client_ref_id, operator_id, after_balance, api, provider_id, api_balance, mode, extra_params) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "ssssssssssssssssssssss",
            $rechargeId, $rechargeDate, $updatedDate, $userId, $firmName, $parentId, $parentName, $operator, $serviceType, $circle,
            $number, $amount, $margin, $status, $clientRefId, $operatorId, $afterBalance, $api, $providerId, $apiBalance, $mode, $extraParams
        );

        $stmt->execute();
    }
}

if (isset($_FILES['userListFile']) && $_FILES['userListFile']['error'] == 0) {
    uploadUserList($_FILES['userListFile']['tmp_name'], $conn);
}

if (isset($_FILES['rechargeReportFile']) && $_FILES['rechargeReportFile']['error'] == 0) {
    uploadRechargeReport($_FILES['rechargeReportFile']['tmp_name'], $conn);
}

header('Location: dashboard.php');
?>
