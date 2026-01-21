<?php
header("Access-Control-Allow-Origin: *"); // اسمح لأي دومين بالاتصال
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // السماح بهذه العمليات
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات Preflight (التي يرسلها المتصفح قبل الـ POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/ReportModule.php';

$reportMod = new ReportModule($pdo);
$branch_id = $_GET['branch_id'] ?? null;
$type = $_GET['type'] ?? null; // نوع التقرير

if (!$branch_id) sendResponse(400, "Branch ID required");

switch($type) {
    case 'inventory':
        sendResponse(200, "Inventory Data", $reportMod->getInventoryReport($branch_id));
        break;

    case 'customer':
        $customer_id = $_GET['customer_id'] ?? null;
        if (!$customer_id) sendResponse(400, "Customer ID required");
        sendResponse(200, "Customer Ledger", $reportMod->getCustomerStatement($branch_id, $customer_id));
        break;

    case 'summary':
        $start = $_GET['start'] ?? date('Y-m-01'); // بداية الشهر الحالي
        $end = $_GET['end'] ?? date('Y-m-d');
        sendResponse(200, "Financial Summary", $reportMod->getFinancialSummary($branch_id, $start, $end));
        break;

    default:
        sendResponse(400, "Invalid Report Type");
}