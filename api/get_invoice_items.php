<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../config/db.php';

$response = [];

// التحقق من وجود رقم الفاتورة في الرابط
if (!isset($_GET['invoice_id'])) {
    http_response_code(400);
    echo json_encode(["status" => 400, "message" => "رقم الفاتورة مطلوب (invoice_id)"]);
    exit;
}

$invoice_id = $_GET['invoice_id'];

try {
    // 1. التأكد أن الفاتورة موجودة وهي فاتورة مبيعات
    $stmtCheck = $pdo->prepare("SELECT id, invoice_type, customer_id, discount as header_discount FROM invoices WHERE id = ?");
    $stmtCheck->execute([$invoice_id]);
    $invoice = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        throw new Exception("الفاتورة غير موجودة");
    }
    // يمكن تفعيل هذا الشرط إذا أردت حصر المرتجعات لفواتير المبيعات فقط
    // if ($invoice['invoice_type'] !== 'sales_invoice') { throw new Exception("هذه ليست فاتورة مبيعات"); }

    // 2. جلب التفاصيل (المنتجات) بالأسعار والخصومات التاريخية المسجلة
    $sql = "SELECT 
                d.product_id,
                p.name as product_name,
                d.unit_id,
                u.name as unit_name,
                d.quantity as original_quantity, -- الكمية التي تم شراؤها
                d.price as unit_price,           -- السعر وقت البيع
                d.item_discount,                 -- الخصم وقت البيع (هذا ما تريده)
                d.net_amount
            FROM invoice_details d
            LEFT JOIN products p ON d.product_id = p.id
            LEFT JOIN units u ON d.unit_id = u.id
            WHERE d.invoice_id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invoice_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // إضافة بيانات إضافية مفيدة للفرونت
    $response = [
        "status" => 200,
        "origin_invoice" => [
            "id" => $invoice['id'],
            "customer_id" => $invoice['customer_id'],
            "global_discount" => $invoice['header_discount'] // خصم الفاتورة العام إن وجد
        ],
        "items" => $items
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(404);
    echo json_encode(["status" => 404, "message" => $e->getMessage()]);
}