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

try {
    // استعلام ذكي يدمج جدول الفواتير مع جدول العملاء لجلب الاسم
    $sql = "SELECT 
                invoices.*, 
                customers.name as customer_name,
                customers.customer_type
            FROM invoices 
            LEFT JOIN customers ON invoices.customer_id = customers.id 
            ORDER BY invoices.id DESC";

    $stmt = $pdo->query($sql);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => 200, "data" => $invoices]);

} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}