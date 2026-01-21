<?php
class ActionModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // دالة تسجيل عملية جديدة
    public function logAction($branch_id, $user_id, $action_type, $product_id, $details = null) {
        $sql = "INSERT INTO actions_log (branch_id, user_id, action_type, product_id, action_details, date_and_time) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$branch_id, $user_id, $action_type, $product_id, $details]);
    }

    // جلب سجل العمليات لفرع معين مع بيانات المنتج والمستخدم
    public function getLogs($branch_id) {
        $sql = "SELECT a.*, u.name as user_name, p.name as product_name 
                FROM actions_log a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN products p ON a.product_id = p.id
                WHERE a.branch_id = ?
                ORDER BY a.date_and_time DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        return $stmt->fetchAll();
    }
}