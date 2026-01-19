<?php
require_once __DIR__ . '/../config/db.php';

function generateBackup() {
    // إعدادات قاعدة البيانات من ملف config
    $host = 'localhost';
    $user = 'root';
    $pass = ''; // افتراضي في XAMPP
    $name = 'accounting';

    // مسار حفظ الملف واسمه (تاريخ ووقت)
    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filePath = __DIR__ . '/../backups/' . $filename;

    // مسار أداة mysqldump في XAMPP على ويندوز
    $mysqldumpPath = 'C:\xampp\mysql\bin\mysqldump.exe';

    // الأمر البرمجي للتصدير
    $command = "\"$mysqldumpPath\" --user=$user --host=$host $name > \"$filePath\"";

    // تنفيذ الأمر
    system($command, $output);

    if ($output === 0) {
        return [
            "status" => "success",
            "file_name" => $filename,
            "download_url" => "http://localhost/accounting/backups/" . $filename
        ];
    } else {
        throw new Exception("فشل إنشاء النسخة الاحتياطية. تأكد من مسار mysqldump.");
    }
}