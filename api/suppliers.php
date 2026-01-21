<?php
ob_start();
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

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

if ($method === 'GET') {
    // جلب الموردين
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC");
    $response = ["status" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
} 
elseif ($method === 'POST') {
    // إضافة مورد جديد
    $data = json_decode(file_get_contents("php://input"), true);
    if (!empty($data['name'])) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (name, phone) VALUES (?, ?)");
        $stmt->execute([$data['name'], $data['phone'] ?? '']);
        $response = ["status" => 201, "message" => "تم حفظ المورد", "id" => $pdo->lastInsertId()];
    } else {
        $response = ["status" => 400, "message" => "الاسم مطلوب"];
    }
}

ob_end_clean();
echo json_encode($response);