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
require_once __DIR__ . '/../modules/EnumModule.php';

$enumMod = new EnumModule($pdo);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // استقبال اسم العمود المطلوب (مثلاً: ?column=units)
    $column = $_GET['column'] ?? null;

    if ($column) {
        $values = $enumMod->getColumnValues($column);
        if (isset($values['error'])) {
            sendResponse(400, $values['error']);
        }
        sendResponse(200, "Success", $values);
    } else {
        // إذا لم يتم تحديد عمود، يتم جلب جميع القوائم معاً لتسريع التحميل الأولي للواجهة
        $allEnums = [
            "units" => $enumMod->getColumnValues('units'),
            "payment_methods" => $enumMod->getColumnValues('payment_methods'),
            "currency" => $enumMod->getColumnValues('currency'),
            "permissions" => $enumMod->getColumnValues('permissions')
        ];
        sendResponse(200, "Success", $allEnums);
    }
}