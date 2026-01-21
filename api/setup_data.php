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

$response = [];

try {
    // 1. الوحدات
    $stmt = $pdo->query("SELECT * FROM units");
    $response['units'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. طرق الدفع المفعلة فقط
    $stmt = $pdo->query("SELECT * FROM payment_methods WHERE is_active = 1");
    $response['payment_methods'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. العملاء (مع بياناتهم التفصيلية)
    $stmt = $pdo->query("SELECT * FROM customers");
    $response['customers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => 200, "data" => $response]);

} catch (Exception $e) {
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}