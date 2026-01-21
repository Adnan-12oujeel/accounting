<?php
class CategoryModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // الدالة الآن تطلب معلومتين فقط: رقم الفرع والاسم
    public function addCategory($branch_id, $name) {
        // جملة الاستعلام (SQL)
        $sql = "INSERT INTO categories (branch_id, name) VALUES (?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$branch_id, $name]);
    }

    // دالة جلب التصنيفات
    public function getCategories($branch_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE branch_id = ? ORDER BY id DESC");
        $stmt->execute([$branch_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}