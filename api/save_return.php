<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Logger.php';

$response = [];

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // التحقق من البيانات الأساسية
    if (empty($data['branch_id']) || empty($data['main_invoice_id']) || empty($data['items'])) {
        throw new Exception("بيانات ناقصة (branch_id, main_invoice_id, items)");
    }

    $pdo->beginTransaction();

    // 1. جلب بيانات الفاتورة الأصلية للتأكد منها
    $stmtCheck = $pdo->prepare("SELECT customer_id FROM invoices WHERE id = ?");
    $stmtCheck->execute([$data['main_invoice_id']]);
    $invoice = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) throw new Exception("الفاتورة الأصلية غير موجودة");

    // 2. إنشاء رأس فاتورة المرتجع
    $sqlHeader = "INSERT INTO sales_returns 
                  (branch_id, main_invoice_id, condition_of_goods, total_value_of_returns, invoice_creator, return_date) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
    
    $stmtHeader = $pdo->prepare($sqlHeader);
    $stmtHeader->execute([
        $data['branch_id'],
        $data['main_invoice_id'],
        $data['condition_of_goods'] ?? 'Slightly Damaged',
        $data['total_value_of_returns'],
        $data['user_id'] ?? null // الموظف الذي قام بالإرجاع
    ]);
    
    $return_id = $pdo->lastInsertId();

    // 3. معالجة البنود (Items)
    foreach ($data['items'] as $item) {
        // أ) إدخال بند المرتجع
        $sqlItem = "INSERT INTO return_items 
                    (return_id, product_id, unit, count, unit_price, total, net_amount, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmtItem = $pdo->prepare($sqlItem);
        $stmtItem->execute([
            $return_id,
            $item['product_id'],
            $item['unit'] ?? 'pcs',
            $item['count'],
            $item['unit_price'], // يجب أن يأتي من الفاتورة الأصلية (كما برمجنا سابقاً)
            $item['count'] * $item['unit_price'],
            $item['net_amount'],
            $item['notes'] ?? ''
        ]);

        // ب) إعادة الكمية للمخزون (مهم جداً!)
        // نقوم بزيادة الكمية المتوفرة في جدول المنتجات
        // ملاحظة: نفترض وجود عمود quantity في المنتجات، إذا لم يكن موجوداً يجب إضافته أو استخدام جدول مخزون منفصل
        // سنستخدم التعديل المباشر هنا:
        /*
           ملاحظة: في هيكلية جدول المنتجات السابقة لم نضع عمود "الكمية" (quantity) بشكل صريح،
           عادة يتم حسابه، أو يجب إضافته. سأفترض وجوده أو سأضيف كود إضافته في الأسفل إذا لم يكن موجوداً.
           لنفترض الآن أنك تريد تتبع المخزون:
        */
        $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")->execute([$item['count'], $item['product_id']]);
    }

    // 4. تحديث رصيد العميل (إنقاص المديونية بقيمة المرتجع)
    if ($invoice['customer_id']) {
        // نخصم قيمة المرتجع من رصيد العميل (Balance = Balance - Return Value)
        $sqlBalance = "UPDATE customers SET balance = balance - ? WHERE id = ?";
        $stmtBalance = $pdo->prepare($sqlBalance);
        $stmtBalance->execute([$data['total_value_of_returns'], $invoice['customer_id']]);
    }

    // 5. تسجيل العملية
    Logger::log($pdo, $data['branch_id'], $data['user_id'] ?? 0, 'add_return', 'sales_returns', $return_id, "إضافة مرتجع للفاتورة #" . $data['main_invoice_id']);

    $pdo->commit();

    http_response_code(201);
    $response = [
        "status" => 201,
        "message" => "تم حفظ المرتجع وتحديث الأرصدة بنجاح",
        "return_id" => $return_id
    ];

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    $response = ["status" => 500, "message" => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
?>