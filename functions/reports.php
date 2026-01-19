<?php
require_once __DIR__ . '/../config/db.php';

function getBranchProfitReport($branch_id, $start_date = null, $end_date = null) {
    global $conn;

    // 1. استعلام جلب الفواتير مرتبة زمنياً مع طرح قيمة المرتجعات من الأرباح
    $sql = "SELECT 
                i.id as invoice_id,
                i.created_at as entry_time,
                i.invoice_status,
                i.net_amount as total_sold,
                i.discount as total_discount,
                i.net_profit as initial_profit,
                -- جلب إجمالي المرتجعات المرتبطة بهذه الفاتورة (إن وجدت)
                COALESCE((SELECT SUM(total_value_of_returns) FROM invoice_sales_returns WHERE main_invoice_id = i.id), 0) as returned_value,
                -- الربح الفعلي = الربح المخزن - (نسبة الربح من المرتجع)
                -- للتبسيط: سنقوم بخصم الربح الإجمالي بناءً على حالة الفاتورة
                (i.net_profit - COALESCE((SELECT SUM(total_value_of_returns) FROM invoice_sales_returns WHERE main_invoice_id = i.id), 0)) as actual_net_profit
            FROM invoices i
            WHERE i.branch_id = :branch_id 
            AND i.invoice_type = 'sales_invoice'
            AND i.invoice_status != 'Canceled'";

    // إضافة فلتر التاريخ
    if ($start_date && $end_date) {
        $sql .= " AND DATE(i.created_at) BETWEEN :start AND :end";
    }

    // الترتيب حسب وقت الإدخال (التسلسل الزمني) كما طلبت
    $sql .= " ORDER BY i.created_at ASC";

    $stmt = $conn->prepare($sql);
    $params = [':branch_id' => $branch_id];
    if ($start_date && $end_date) {
        $params[':start'] = $start_date;
        $params[':end'] = $end_date;
    }

    $stmt->execute($params);
    $details = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. حساب المجاميع النهائية للتقرير
    $totalSales = 0;
    $totalProfit = 0;
    $totalReturns = 0;

    foreach ($details as $row) {
        $totalSales += $row['total_sold'];
        $totalProfit += $row['actual_net_profit'];
        $totalReturns += $row['returned_value'];
    }

    return [
        "report_period" => [
            "start" => $start_date,
            "end" => $end_date
        ],
        "summary" => [
            "total_sales" => $totalSales,
            "total_returns" => $totalReturns,
            "final_net_profit" => $totalProfit // صافي الربح الحقيقي بعد المرتجعات
        ],
        "details" => $details // قائمة الفواتير مرتبة زمنياً
    ];
}