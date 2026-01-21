<?php
// 1. تفعيل Output Buffering لمنع أي مسافات أو أخطاء PHP من إفساد الـ JSON
ob_start();

// إعدادات الـ CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات الـ OPTION (Pre-flight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// استدعاء ملفات الاتصال والموديول
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../modules/InvoiceModule.php';

// =========================================================
// دالة مساعدة: إرسال إشعار عبر البريد الإلكتروني
// =========================================================
function sendInvoiceNotification($pdo, $branch_id, $invoice_id, $total, $type, $creator_name = 'System') {
    // 1. جلب إيميلات المستخدمين في نفس الفرع الذين لديهم صلاحية
    // (نفترض هنا أننا نرسل للكل في الفرع للتبسيط، أو يمكن تخصيصها حسب الصلاحيات)
    $stmt = $pdo->prepare("SELECT email FROM users WHERE branch_id = ? AND active = 1");
    $stmt->execute([$branch_id]);
    $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($emails)) return;

    // 2. تجهيز محتوى الرسالة
    $typeName = ($type == 'sales_invoice') ? 'مبيعات' : (($type == 'bought_invoice') ? 'مشتريات' : 'مرتجع');
    
    $subject = "فاتورة جديدة #$invoice_id ($typeName)";
    $message = "
        مرحباً،
        تم تسجيل عملية جديدة في النظام.
        --------------------------------
        رقم الفاتورة: #$invoice_id
        النوع: $typeName
        القيمة الإجمالية: $total
        المستخدم: $creator_name
        --------------------------------
        يرجى مراجعة النظام للتفاصيل.
    ";
    
    // 3. الإرسال (تأكد من إعدادات SMTP في السيرفر لتعمل دالة mail)
    $headers = "From: no-reply@accounting-system.com" . "\r\n" .
               "Content-Type: text/plain; charset=UTF-8";

    foreach ($emails as $email) {
        // نستخدم @ لمنع ظهور أخطاء إذا فشل الإرسال حتى لا يوقف السكربت
        @mail($email, $subject, $message, $headers);
    }
}
// =========================================================

$response = [];

try {
    // استقبال البيانات JSON
    $input_json = file_get_contents("php://input");
    $data = json_decode($input_json, true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // التحقق من صحة البيانات الأساسية
        if (empty($data['branch_id']) || empty($data['items']) || empty($data['invoice_type'])) {
            throw new Exception("بيانات ناقصة: تأكد من إرسال branch_id, items, invoice_type");
        }

        // استدعاء الموديول للمعالجة
        $invMod = new InvoiceModule($pdo);
        
        // إنشاء الفاتورة والحصول على الـ ID
        $invoice_id = $invMod->createInvoice($data);

        // ---------------------------------------------------
        // إرسال الإشعار بعد نجاح الحفظ
        // ---------------------------------------------------
        try {
            // نتحقق إذا تم إرسال اسم المستخدم (اختياري)
            $creator = $data['creator_name'] ?? 'مستخدم'; 
            sendInvoiceNotification(
                $pdo, 
                $data['branch_id'], 
                $invoice_id, 
                $data['total_amount'] ?? 0, 
                $data['invoice_type'],
                $creator
            );
        } catch (Exception $e) {
            // نتجاهل أخطاء الإيميل حتى لا نظهر للمستخدم أن الفاتورة فشلت وهي قد نجحت فعلاً
            // error_log($e->getMessage());
        }
        // ---------------------------------------------------

        // الرد بنجاح
        http_response_code(201);
        $response = [
            "status" => 201,
            "message" => "تم حفظ الفاتورة وإرسال الإشعارات بنجاح",
            "invoice_id" => $invoice_id,
            "type" => $data['invoice_type']
        ];

    } else {
        http_response_code(405);
        throw new Exception("Method Not Allowed");
    }

} catch (Exception $e) {
    // الرد بخطأ
    http_response_code(500);
    $response = [
        "status" => 500,
        "message" => $e->getMessage()
    ];
}

// تنظيف المخرجات وإرسال JSON النهائي
ob_end_clean();
echo json_encode($response);
exit;
?>