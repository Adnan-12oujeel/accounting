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

if (!isset($_GET['id'])) {
    echo json_encode(["status" => 400, "message" => "رقم الفاتورة مطلوب"]);
    exit;
}

$id = $_GET['id'];
$response = [];

try {
    // 1. جلب رأس الفاتورة مع اسم العميل
    $sql_inv = "SELECT 
                    invoices.*, 
                    customers.name as customer_name,
                    customers.company as customer_company,
                    customers.phone as customer_phone,
                    customers.address as customer_address,
                    payment_methods.name as payment_method_name
                FROM invoices 
                LEFT JOIN customers ON invoices.customer_id = customers.id 
                LEFT JOIN payment_methods ON invoices.payment_method_id = payment_methods.id
                WHERE invoices.id = ?";
    
    $stmt = $pdo->prepare($sql_inv);
    $stmt->execute([$id]);
    $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo json_encode(["status" => 404, "message" => "الفاتورة غير موجودة"]);
        exit;
    }

    // 2. جلب المنتجات (البنود)
    $sql_items = "SELECT 
                    invoice_details.*, 
                    products.name as product_name,
                    units.name as unit_name
                  FROM invoice_details 
                  LEFT JOIN products ON invoice_details.product_id = products.id
                  LEFT JOIN units ON invoice_details.unit_id = units.id
                  WHERE invoice_details.invoice_id = ?";
                  
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$id]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // دمج النتائج
    $invoice['items'] = $items;

    echo json_encode(["status" => 200, "data" => $invoice]);

} catch (PDOException $e) {
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}