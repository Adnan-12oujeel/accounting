<?php
// api/reports.php
ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

$branch_id = $_GET['branch_id'] ?? null;
$type = $_GET['type'] ?? 'invoices_report'; // نوع التقرير

if (!$branch_id) {
    echo json_encode(["status" => 400, "message" => "Branch ID required"]);
    exit;
}

$response = [];

try {
    if ($type == 'invoices_report') {
        // --- تقرير جرد الفواتير ---
        // الفلاتر الاختيارية
        $start_date = $_GET['start_date'] ?? date('Y-m-01'); // من بداية الشهر افتراضياً
        $end_date = $_GET['end_date'] ?? date('Y-m-d');     // إلى اليوم
        $user_id = $_GET['user_id'] ?? null;                // لموظف معين
        $inv_type = $_GET['invoice_type'] ?? null;          // نوع الفاتورة (مبيع/شراء)

        // بناء الاستعلام ديناميكياً
        $sql = "SELECT * FROM invoices WHERE branch_id = ? AND DATE(date) BETWEEN ? AND ?";
        $params = [$branch_id, $start_date, $end_date];

        if ($user_id) {
            $sql .= " AND creator_id = ?";
            $params[] = $user_id;
        }
        if ($inv_type) {
            $sql .= " AND invoice_type = ?";
            $params[] = $inv_type;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // حساب الإجماليات (Jard)
        $totals = [
            'count' => count($invoices),
            'total_sales' => 0,
            'total_cash' => 0,
            'total_unpaid' => 0
        ];

        foreach ($invoices as $inv) {
            $totals['total_sales'] += $inv['net_amount'];
            if ($inv['invoice_status'] == 'Paid') $totals['total_cash'] += $inv['net_amount'];
            if ($inv['invoice_status'] == 'Unpaid') $totals['total_unpaid'] += $inv['net_amount'];
        }

        $response = [
            "status" => 200,
            "period" => ["from" => $start_date, "to" => $end_date],
            "totals" => $totals,
            "data" => $invoices
        ];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ["status" => 500, "message" => $e->getMessage()];
}

ob_end_clean();
echo json_encode($response);
?>