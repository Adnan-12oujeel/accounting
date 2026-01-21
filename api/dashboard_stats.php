<?php
// api/dashboard_stats.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../config/db.php';

// استقبال رقم الفرع (مهم جداً)
$branch_id = $_GET['branch_id'] ?? null;

if (!$branch_id) {
    echo json_encode(["status" => 400, "message" => "Branch ID required"]);
    exit;
}

$response = [];

try {
    // 1. الإحصائيات العلوية (خاصة بالفرع)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE branch_id = ? AND active = 1");
    $stmt->execute([$branch_id]);
    $response['stats']['products_count'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE branch_id = ? AND active = 1");
    $stmt->execute([$branch_id]);
    $response['stats']['customers_count'] = $stmt->fetchColumn();

    // مبيعات اليوم للفرع
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT SUM(net_amount) FROM invoices WHERE branch_id = ? AND invoice_type = 'sales_invoice' AND DATE(date) = ?");
    $stmt->execute([$branch_id, $today]);
    $response['stats']['today_sales'] = $stmt->fetchColumn() ?: 0;

    // 2. آخر 5 عمليات في هذا الفرع
    $sql_recent = "SELECT i.id, i.invoice_type, i.net_amount, i.date, c.first_name, c.last_name
                   FROM invoices i
                   LEFT JOIN customers c ON i.customer_id = c.id
                   WHERE i.branch_id = ?
                   ORDER BY i.id DESC LIMIT 5";

    $stmt = $pdo->prepare($sql_recent);
    $stmt->execute([$branch_id]);
    $response['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["status" => 200, "data" => $response]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}
?>