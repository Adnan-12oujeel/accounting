<?php
class SystemServices {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. نظام النسخ الاحتياطي (Backup)
    public function generateBackup($db_config) {
        $filename = "backup_" . date("Y-m-d_H-i-s") . ".sql";
        $path = "../../backups/" . $filename;
        
        // تنفيذ أمر تصدير قاعدة البيانات (يعتمد على إعدادات السيرفر في Hostinger)
        $command = "mysqldump -h {$db_config['host']} -u {$db_config['user']} -p'{$db_config['pass']}' {$db_config['name']} > $path";
        
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            $download_link = "https://yourdomain.com/backups/" . $filename;
            $this->sendBackupEmail($download_link);
            return true;
        }
        return false;
    }

    // 2. إرسال إشعارات الفواتير للمستخدمين
    public function notifyUsersOfInvoice($branch_id, $invoice_details) {
        // جلب إيميلات جميع المستخدمين في هذا الفرع
        $query = "SELECT email FROM users WHERE branch_id = :branch_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($emails as $email) {
            // منطق إرسال الإيميل (باستخدام PHPMailer أو دالة mail)
            $subject = "فاتورة جديدة: " . $invoice_details['type'];
            $message = "تمت إضافة فاتورة جديدة بقيمة: " . $invoice_details['net_amount'];
            mail($email, $subject, $message);
        }
    }

    private function sendBackupEmail($link) {
        $to = "super_admin@system.com"; // إيميل السوبر أدمن
        $subject = "نسخة احتياطية جاهزة للتحميل";
        $message = "يمكنك تحميل ملف الـ SQL من الرابط التالي: \n" . $link;
        mail($to, $subject, $message);
    }
}