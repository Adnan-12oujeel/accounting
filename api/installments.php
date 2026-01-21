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
require_once __DIR__ . '/../modules/InstallmentModule.php';

$instMod = new InstallmentModule($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $result = $instMod->addInstallment($data);
    is_bool($result) ? sendResponse(201, "تم تسجيل الدفعة") : sendResponse(500, $result['error']);
}

if ($method === 'GET') {
    $invoice_id = $_GET['invoice_id'];
    sendResponse(200, "Success", $instMod->getInvoiceInstallments($invoice_id));
}