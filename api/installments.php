<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

require_once __DIR__ . '/../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

if ($method === 'POST') {
    // استلام دفعة جديدة
    $data = json_decode(file_get_contents("php://input"), true);
    
    // التحقق من البيانات
    if (empty($data['invoice_id']) || empty($data['amount'])) {
        http_response_code(400);
        echo json_encode(["status" => 400, "message" => "البيانات ناقصة (invoice_id, amount)"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. جلب بيانات الفاتورة الحالية للتأكد
        $stmtInv = $pdo->prepare("SELECT id, customer_id, net_amount, paid_amount, invoice_type FROM invoices WHERE id = ?");
        $stmtInv->execute([$data['invoice_id']]);
        $invoice = $stmtInv->fetch(PDO::FETCH_ASSOC);

        if (!$invoice) throw new Exception("الفاتورة غير موجودة");

        // التحقق من أن المبلغ لا يتجاوز المتبقي
        $remaining = $invoice['net_amount'] - $invoice['paid_amount'];
        if ($data['amount'] > $remaining) {
            // ملاحظة: يمكن السماح بذلك وتحويله لرصيد دائن، لكن سنمنعه للتبسيط حالياً
            throw new Exception("المبلغ المدفوع أكبر من المبلغ المتبقي للفاتورة ($remaining)");
        }

        // 2. تسجيل الدفعة
        $sqlIns = "INSERT INTO installments (invoice_id, customer_id, amount, payment_method_id, date, notes) VALUES (?, ?, ?, ?, ?, ?)";
        $stmtIns = $pdo->prepare($sqlIns);
        $stmtIns->execute([
            $data['invoice_id'],
            $invoice['customer_id'],
            $data['amount'],
            $data['payment_method_id'] ?? 1, // 1 = Cash افتراضياً
            $data['date'] ?? date('Y-m-d'),
            $data['notes'] ?? ''
        ]);

        // 3. تحديث الفاتورة (زيادة المدفوع + تغيير الحالة إذا اكتملت)
        $new_paid = $invoice['paid_amount'] + $data['amount'];
        $new_status = ($new_paid >= $invoice['net_amount']) ? 'Paid' : 'Installments'; // Installments = جاري السداد
        
        $stmtUpd = $pdo->prepare("UPDATE invoices SET paid_amount = ?, invoice_status = ? WHERE id = ?");
        $stmtUpd->execute([$new_paid, $new_status, $data['invoice_id']]);

        // 4. تحديث رصيد العميل
        // إذا كانت فاتورة مبيعات: السداد ينقص دين العميل (-)
        // إذا كانت فاتورة مشتريات: السداد ينقص ديننا للمورد (-)
        if ($invoice['customer_id']) {
            $balance_effect = -1 * abs($data['amount']); // السداد دائماً ينقص الرصيد المستحق
            
            // في حالة الموردين (فاتورة مشتريات)، نحن نسدد لهم، فالدين ينقص أيضاً
            // في حالة العملاء، هم يسددون لنا، فالدين ينقص أيضاً
            // لذا التأثير دائماً بالسالب على قيمة "الدين"
            
            $stmtCust = $pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
            $stmtCust->execute([$balance_effect, $invoice['customer_id']]);
        }

        $pdo->commit();
        echo json_encode(["status" => 201, "message" => "تم تسجيل الدفعة بنجاح", "new_status" => $new_status]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["status" => 500, "message" => $e->getMessage()]);
    }
}
elseif ($method === 'GET') {
    // جلب سجل دفعات فاتورة معينة
    if (!isset($_GET['invoice_id'])) {
        echo json_encode(["status" => 400, "message" => "invoice_id مطلوب"]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT i.*, p.name as method_name 
                           FROM installments i 
                           LEFT JOIN payment_methods p ON i.payment_method_id = p.id 
                           WHERE invoice_id = ? ORDER BY id DESC");
    $stmt->execute([$_GET['invoice_id']]);
    echo json_encode(["status" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}