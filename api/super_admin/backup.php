<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../modules/BackupModule.php';

// ملاحظة: يجب التحقق هنا من الـ Token للتأكد أن المستخدم Super Admin
$backupMod = new BackupModule($pdo);

try {
    $backupFile = $backupMod->generateBackup();
    
    // إرسال الإيميل (يفضل استخدام PHPMailer في بيئة الإنتاج)
    $to = "super_admin@example.com"; 
    $subject = "نسخة احتياطية لقاعدة البيانات - " . date('Y-m-d');
    $downloadUrl = "https://yourdomain.com/backups/" . $backupFile['file_name'];
    
    $message = "تم إنشاء نسخة احتياطية بنجاح.\nرابط التحميل: " . $downloadUrl;
    $headers = "From: system@yourdomain.com";

    if (mail($to, $subject, $message, $headers)) {
        sendResponse(200, "تم إنشاء النسخة الاحتياطية وإرسال الرابط للإيميل", ["url" => $downloadUrl]);
    } else {
        sendResponse(200, "تم إنشاء الملف ولكن فشل إرسال الإيميل", ["url" => $downloadUrl]);
    }
} catch (Exception $e) {
    sendResponse(500, "خطأ في النظام: " . $e->getMessage());
}