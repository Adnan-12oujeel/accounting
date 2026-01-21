<?php
// api/inventory_audit.php
ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Logger.php';

$method = $_SERVER['REQUEST_METHOD'];
$response = [];

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // التحقق من نوع العملية (create_session أو approve_audit)
        $action = $_GET['action'] ?? 'create';

        if ($action == 'create') {
            // --- إنشاء جلسة جرد وحفظ الفروقات ---
            // البيانات المتوقعة: branch_id, user_id, items: [{product_id, actual_qty}]
            
            $pdo->beginTransaction();
            
            // 1. إنشاء السجل الرئيسي
            $stmt = $pdo->prepare("INSERT INTO inventory_audits (branch_id, creator_id, notes, status) VALUES (?, ?, ?, 'Approved')");
            $stmt->execute([$data['branch_id'], $data['user_id'], $data['notes'] ?? 'جرد مخزني']);
            $audit_id = $pdo->lastInsertId();

            // 2. معالجة المنتجات
            foreach ($data['items'] as $item) {
                // جلب الكمية الحالية من النظام (system_qty)
                $stmtProd = $pdo->prepare("SELECT stock_qty FROM products WHERE id = ?");
                $stmtProd->execute([$item['product_id']]);
                $system_qty = $stmtProd->fetchColumn() ?: 0;
                
                $actual_qty = $item['actual_qty'];
                $difference = $actual_qty - $system_qty;

                // تسجيل بند الجرد
                $stmtItem = $pdo->prepare("INSERT INTO audit_items (audit_id, product_id, system_qty, actual_qty, difference) VALUES (?, ?, ?, ?, ?)");
                $stmtItem->execute([$audit_id, $item['product_id'], $system_qty, $actual_qty, $difference]);

                // 3. تحديث المخزون الرسمي ليطابق الواقع (تصحيح المخزون)
                if ($difference != 0) {
                    $stmtUpdate = $pdo->prepare("UPDATE products SET stock_qty = ? WHERE id = ?");
                    $stmtUpdate->execute([$actual_qty, $item['product_id']]);
                }
            }

            Logger::log($pdo, $data['branch_id'], $data['user_id'], 'audit', 'inventory_audits', $audit_id, "إجراء جرد وتصحيح مخزون");
            
            $pdo->commit();
            $response = ["status" => 201, "message" => "تم حفظ الجرد وتحديث الكميات بنجاح", "audit_id" => $audit_id];
        }

    } elseif ($method === 'GET') {
        // عرض سجلات الجرد السابقة
        $branch_id = $_GET['branch_id'];
        $sql = "SELECT a.*, u.name as creator_name, 
                (SELECT COUNT(*) FROM audit_items WHERE audit_id = a.id) as items_count
                FROM inventory_audits a 
                LEFT JOIN users u ON a.creator_id = u.id
                WHERE a.branch_id = ? ORDER BY a.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        $response = ["status" => 200, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)];
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    $response = ["status" => 500, "message" => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
?>