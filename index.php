<?php
// index.php - الموجه الرئيسي للنظام

// 1. إعدادات CORS (ممتازة كما كتبتها)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. استقبال الرابط من ملف .htaccess
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// إذا كان الرابط فارغاً (الصفحة الرئيسية)، اعرض رسالة ترحيب
if (empty($url)) {
    echo json_encode([
        "status" => 200,
        "message" => "Welcome to Accounting API System V1",
        "backend_dev" => "Online"
    ]);
    exit();
}

// تقسيم الرابط (للمستقبل: قد تحتاج الجزء الثاني للـ ID)
$urlParts = explode('/', $url);
$endpoint = $urlParts[0]; // مثلاً: products

// 3. التحقق والتضمين
$file = __DIR__ . "/api/" . $endpoint . ".php";

if (file_exists($file)) {
    require_once $file;
} else {
    http_response_code(404);
    echo json_encode([
        "status" => 404,
        "message" => "Endpoint not found: " . $endpoint
    ]);
}