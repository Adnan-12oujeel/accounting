<?php
// إعدادات الـ CORS (مهمة جداً لزميلك في الفرونت إند)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات الـ OPTION (Pre-flight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/InvoiceModule.php';

$response = [];

try {
    // استقبال البيانات JSON
    $data = json_decode(file_get_contents("php://input"), true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // التحقق من صحة البيانات الأساسية
        if (empty($data['branch_id']) || empty($data['items']) || empty($data['invoice_type'])) {
            throw new Exception("بيانات ناقصة: تأكد من branch_id, items, invoice_type");
        }

        // استدعاء الموديول للمعالجة
        $invMod = new InvoiceModule($pdo);
        $invoice_id = $invMod->createInvoice($data);

        // الرد بنجاح
        http_response_code(201);
        $response = [
            "status" => 201,
            "message" => "تم حفظ الفاتورة بنجاح",
            "invoice_id" => $invoice_id,
            "type" => $data['invoice_type']
        ];
    } else {
        throw new Exception("Method Not Allowed");
    }

} catch (Exception $e) {
    // الرد بخطأ
    http_response_code(500); // أو 400 حسب الخطأ
    $response = [
        "status" => 500,
        "message" => $e->getMessage()
    ];
}

echo json_encode($response);