<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host     = "localhost";
$db_name  = "accounting";
$username = "root";
$password = ""; 

try {
    // تم التعديل لـ utf8mb4 لدعم أفضل للبيانات
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $exception) {
    // إرجاع الخطأ بصيغة JSON ليفهمها الفرونت إند
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Connection failed: " . $exception->getMessage()]);
    exit;
}