<?php
// includes/Logger.php

class Logger {
    /**
     * تسجيل عملية في قاعدة البيانات
     * @param PDO $pdo اتصال قاعدة البيانات
     * @param int $branch_id رقم الفرع
     * @param int $user_id رقم المستخدم (يمكن أن يكون null إذا لم يسجل دخول بعد)
     * @param string $action نوع العملية (add, edit, delete, print, login)
     * @param string $table اسم الجدول المتأثر (products, invoices...)
     * @param int $record_id رقم السجل المتأثر (مثلاً رقم الفاتورة)
     * @param string $desc وصف إضافي
     */
    public static function log($pdo, $branch_id, $user_id, $action, $table, $record_id = 0, $desc = '') {
        try {
            $sql = "INSERT INTO actions_log 
                    (branch_id, user_id, action_type, table_name, record_id, description) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $branch_id, 
                $user_id, 
                $action, 
                $table, 
                $record_id, 
                $desc
            ]);
        } catch (Exception $e) {
            // لا نريد إيقاف النظام إذا فشل السجل، فقط نتجاهل الخطأ أو نسجله في ملف نصي
            // error_log("Logger Error: " . $e->getMessage());
        }
    }
}
?>