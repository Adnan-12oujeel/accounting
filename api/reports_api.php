<?php
header("Content-Type: application/json");
// السماح لـ React بالوصول للبيانات (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

require_once __DIR__ . '/../functions/reports.php';

$action = $_GET['action'] ?? '';
$branch_id = $_GET['branch_id'] ?? null;

if (!$branch_id) {
    echo json_encode(["status" => "error", "message" => "Branch ID is required"]);
    exit;
}

// 1. جلب التقرير المالي كبيانات JSON للوحة التحكم (Dashboard)
if ($action == 'profit_report') {
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;

    try {
        $report = getBranchProfitReport($branch_id, $start, $end);
        echo json_encode([
            "status" => "success",
            "timestamp" => date('Y-m-d H:i:s'),
            "branch_id" => $branch_id,
            "currency" => "USD", 
            "data" => $report
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// 2. خيار جديد: تصدير التقرير بصيغة PDF
if ($action == 'export_pdf') {
    $start = $_GET['start_date'] ?? null;
    $end = $_GET['end_date'] ?? null;

    try {
        $report = getBranchProfitReport($branch_id, $start, $end);
        
        // هنا يتم استدعاء مكتبة Dompdf (تأكد من تنصيبها عبر Composer)
        // سيقوم هذا الجزء بتحويل البيانات إلى جدول HTML ثم إلى PDF
        require_once __DIR__ . '/../vendor/autoload.php';
        $dompdf = new \Dompdf\Dompdf();
        
        // بناء محتوى HTML بسيط للتقرير
        $html = "<h3>تقرير الأرباح والمبيعات - فرع رقم $branch_id</h3>";
        $html .= "<p>الفترة: " . ($start ?? 'الكل') . " إلى " . ($end ?? 'الكل') . "</p>";
        $html .= "<table border='1' width='100%' style='border-collapse: collapse; text-align: center;'>
                    <thead>
                        <tr style='background: #f2f2f2;'>
                            <th>الوقت</th>
                            <th>رقم الفاتورة</th>
                            <th>المبيعات</th>
                            <th>المرتجعات</th>
                            <th>صافي الربح</th>
                        </tr>
                    </thead>
                    <tbody>";
        
        foreach ($report['details'] as $row) {
            $html .= "<tr>
                        <td>{$row['entry_time']}</td>
                        <td>#{$row['invoice_id']}</td>
                        <td>{$row['total_sold']}</td>
                        <td>{$row['returned_value']}</td>
                        <td>{$row['actual_net_profit']}</td>
                      </tr>";
        }
        
        $html .= "</tbody></table>";
        $html .= "<h4>إجمالي الأرباح النهائية: " . $report['summary']['final_net_profit'] . "</h4>";

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // إرسال الملف للمتصفح كتحميل تلقائي
        header("Content-Type: application/pdf");
        header("Content-Disposition: attachment; filename=Profit_Report_$branch_id.pdf");
        echo $dompdf->output();
        exit;

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "فشل توليد ملف PDF: " . $e->getMessage()]);
        exit;
    }
}

echo json_encode(["status" => "error", "message" => "Invalid action"]);