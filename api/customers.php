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

try {
    if ($method === 'GET') {
        // جلب قائمة العملاء
        $search = $_GET['search'] ?? '';
        $sql = "SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["%$search%", "%$search%"]);
        $response = ["status" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    } 
    elseif ($method === 'POST') {
        // إضافة عميل جديد
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!empty($data['name'])) {
            $sql = "INSERT INTO customers (name, company, phone, address, balance) VALUES (?, ?, ?, ?, 0.00)";
            $stmt = $pdo->prepare($sql);
            
            $stmt->execute([
                $data['name'], 
                $data['company'] ?? '', 
                $data['phone'] ?? '', 
                $data['address'] ?? ''
            ]);

            $response = [
                "status" => 201, 
                "message" => "تم إضافة العميل بنجاح", 
                "id" => $pdo->lastInsertId()
            ];
        } else {
            $response = ["status" => 400, "message" => "اسم العميل مطلوب"];
        }
    }
} catch (Exception $e) {
    $response = ["status" => 500, "message" => "خطأ: " . $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);