<?php
class BranchModel {
    private $conn;
    private $table_name = "branches";

    public function __construct($db) {
        $this->conn = $db;
    }

    // إضافة فرع جديد (Super Admin فقط)
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=:name, address=:address, mobile=:mobile, email=:email, 
                      date_of_start=:date_of_start, plan=:plan, is_active=1";
        
        $stmt = $this->conn->prepare($query);

        // ربط البيانات
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":address", $data['address']);
        $stmt->bindParam(":mobile", $data['mobile']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":date_of_start", $data['date_of_start']);
        $stmt->bindParam(":plan", $data['plan']); // (yearly, 2_years, 4_years)

        return $stmt->execute();
    }

    // جلب الفروع النشطة فقط (للعرض الأساسي)
    public function getActiveBranches() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // جلب الأرشيف (الفروع غير النشطة)
    public function getArchivedBranches() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE is_active = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // أرشفة فرع أو تفعيله
    public function toggleStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET is_active = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}