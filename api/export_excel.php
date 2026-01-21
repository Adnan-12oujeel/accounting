<?php
// api/export_excel.php

// 1. إعدادات الرؤوس لإجبار المتصفح على التحميل كملف Excel/CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=report_' . date('Y-m-d') . '.csv');

require_once __DIR__ . '/../config/db.php';

// فتح مجرى الإخراج (Output Stream) للكتابة مباشرة للملف
$output = fopen('php://output', 'w');

// 🔥 هام جداً: إضافة BOM (Byte Order Mark) لكي يقرأ Excel اللغة العربية بشكل صحيح
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// استقبال الفلاتر (نفس فلاتر التقرير السابق)
$branch_id = $_GET['branch_id'] ?? null;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$type = $_GET['type'] ?? 'invoices'; // invoices OR products

if (!$branch_id) {
    die("Error: Branch ID is required");
}

try {
    if ($type == 'invoices') {
        // --- 1. تصدير الفواتير ---
        
        // كتابة عناوين الأعمدة (Header Row)
        fputcsv($output, ['رقم الفاتورة', 'النوع', 'التاريخ', 'اسم العميل', 'طريقة الدفع', 'الحالة', 'الإجمالي', 'الخصم', 'الصافي', 'الموظف']);

        // الاستعلام
        $sql = "SELECT i.*, c.first_name, c.last_name, c.company, u.name as user_name 
                FROM invoices i
                LEFT JOIN customers c ON i.customer_id = c.id
                LEFT JOIN users u ON i.creator_id = u.id
                WHERE i.branch_id = ? AND DATE(i.date) BETWEEN ? AND ?
                ORDER BY i.id DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id, $start_date, $end_date]);
        
        // كتابة البيانات سطراً بسطر
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // ترجمة نوع الفاتورة للعربية
            $inv_type_ar = '';
            if($row['invoice_type'] == 'sales_invoice') $inv_type_ar = 'مبيعات';
            elseif($row['invoice_type'] == 'bought_invoice') $inv_type_ar = 'مشتريات';
            elseif($row['invoice_type'] == 'sales_return_invoice') $inv_type_ar = 'مرتجع مبيعات';
            else $inv_type_ar = $row['invoice_type'];

            // اسم العميل (الشركة أو الاسم الشخصي)
            $client_name = $row['company'] ? $row['company'] : ($row['first_name'] . ' ' . $row['last_name']);

            fputcsv($output, [
                $row['id'],
                $inv_type_ar,
                $row['date'],
                $client_name,
                $row['payment_method'],
                $row['invoice_status'],
                $row['total_amount'],
                $row['discount'],
                $row['net_amount'],
                $row['user_name']
            ]);
        }
    } 
    
    elseif ($type == 'products') {
        // --- 2. تصدير جرد المنتجات ---
        
        fputcsv($output, ['المعرف', 'اسم المنتج', 'الكود', 'السعر', 'الكمية في المخزن', 'المكان']);

        $stmt = $pdo->prepare("SELECT * FROM products WHERE branch_id = ? AND active = 1");
        $stmt->execute([$branch_id]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($output, [
                $row['id'],
                $row['name'],
                $row['product_code'],
                $row['selling_price'],
                $row['stock_qty'], // العمود الجديد الذي أضفناه
                $row['product_place']
            ]);
        }
    }

} catch (Exception $e) {
    // في حال الخطأ نكتبه داخل الملف
    fputcsv($output, ['Error', $e->getMessage()]);
}

// إغلاق الملف
fclose($output);
exit;
?>