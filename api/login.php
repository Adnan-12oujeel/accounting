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
require_once __DIR__ . '/../modules/UserModule.php';

$data = json_decode(file_get_contents("php://input"));
$userMod = new UserModule($pdo);

if (!empty($data->email) && !empty($data->password)) {
    $result = $userMod->login($data->email, $data->password);

    if (isset($result['error'])) {
        sendResponse(403, $result['error']);
    } elseif ($result) {
        sendResponse(200, "تم تسجيل الدخول بنجاح", $result);
    } else {
        sendResponse(401, "البيانات غير صحيحة");
    }
} else {
    sendResponse(400, "يرجى إكمال البيانات");
}