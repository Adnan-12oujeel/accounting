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
    // 1. عدد المنتجات
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $response['products_count'] = $stmt->fetchColumn();

    // 2. عدد العملاء
    $stmt = $pdo->query("SELECT COUNT(*) FROM customers");
    $response['customers_count'] = $stmt->fetchColumn();

    // 3. مبيعات اليوم (sales_invoice فقط)
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT SUM(net_amount) FROM invoices WHERE invoice_type = 'sales_invoice' AND DATE(date) = ?");
    $stmt->execute([$today]);
    $response['today_sales'] = $stmt->fetchColumn() ?: 0;

    // 4. عدد الفواتير اليوم
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE DATE(date) = ?");
    $stmt->execute([$today]);
    $response['today_invoices'] = $stmt->fetchColumn();

    echo json_encode(["status" => 200, "data" => $response]);

} catch (Exception $e) {
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}