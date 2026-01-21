<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

// تأكد من حماية هذا الملف (مثلاً التحقق من التوكن أو كلمة مرور خاصة)
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['trigger'])) {
    
    // إعدادات الاتصال (يجب أن تتطابق مع config/db.php)
    $host = 'localhost';
    $dbname = 'accounting'; // اسم قاعدة البيانات
    $user = 'root';
    $pass = ''; // كلمة مرور قاعدة البيانات
    
    // مسار حفظ الملفات
    $backupDir = __DIR__ . '/../backups/';
    if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);
    
    $fileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filePath = $backupDir . $fileName;
    
    // أمر التصدير (mysqldump)
    // ملاحظة: يجب أن يكون mysqldump مضافاً لمتغيرات البيئة في السيرفر أو تحديد مساره الكامل
    // في XAMPP/Laragon غالباً يعمل مباشرة
    $command = "mysqldump --user={$user} --password={$pass} --host={$host} {$dbname} > {$filePath} 2>&1";
    
    system($command, $output);
    
    if (file_exists($filePath) && filesize($filePath) > 0) {
        $downloadLink = "http://" . $_SERVER['HTTP_HOST'] . "/accounting/backups/" . $fileName;
        
        // إرسال الإيميل (اختياري)
        $adminEmail = $_POST['email'] ?? 'admin@system.com';
        $subject = "نسخة احتياطية للنظام: " . date('Y-m-d');
        $message = "تم إنشاء نسخة احتياطية بنجاح.\nرابط التحميل: " . $downloadLink;
        $headers = "From: system@accounting.com";
        
        // mail($adminEmail, $subject, $message, $headers); // مفعلة في السيرفر الحقيقي
        
        echo json_encode([
            "status" => 200, 
            "message" => "تم إنشاء النسخة الاحتياطية بنجاح", 
            "link" => $downloadLink
        ]);
    } else {
        echo json_encode([
            "status" => 500, 
            "message" => "فشل إنشاء ملف النسخة الاحتياطية. تأكد من إعدادات mysqldump",
            "debug" => $command
        ]);
    }
}