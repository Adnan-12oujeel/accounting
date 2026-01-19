<?php
class ActionModel {
    private $conn;
    private $table_name = "actions";

    public function __construct($db) {
        $this->conn = $db;
    }

    // تسجيل عملية جديدة
    public function logAction($branch_id, $user_id, $type, $product_id = null, $details = "") {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET branch_id=:branch_id, user_id=:user_id, action_type=:type, 
                      product_id=:product_id, details=:details";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":type", $type); // (print, add, delete, edit)
        $stmt->bindParam(":product_id", $product_id);
        $stmt->bindParam(":details", $details);

        return $stmt->execute();
    }

    // جلب سجل العمليات لفرع محدد (للـ Admin)
    public function getLogsByBranch($branch_id) {
        $query = "SELECT a.*, u.name as user_name, p.name as product_name 
                  FROM actions a 
                  LEFT JOIN users u ON a.user_id = u.id 
                  LEFT JOIN products p ON a.product_id = p.id 
                  WHERE a.branch_id = :branch_id 
                  ORDER BY a.date_and_time DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}