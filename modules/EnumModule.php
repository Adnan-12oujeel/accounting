<?php
class EnumModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // دالة لجلب البيانات من عمود محدد دون تكرار
    public function getColumnValues($columnName) {
        // التحقق من أن العمود المطلوب ضمن القائمة المسموح بها للأمان
        $allowedColumns = ['permissions', 'units', 'payment_methods', 'currency'];
        if (!in_array($columnName, $allowedColumns)) {
            return ["error" => "Invalid column name"];
        }

        // جلب القيم الفريدة وتجاهل القيم الفارغة
        $sql = "SELECT DISTINCT $columnName FROM enums WHERE $columnName IS NOT NULL AND $columnName != ''";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}