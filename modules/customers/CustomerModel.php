<?php
class CustomerModel {
    private $conn;
    private $table_name = "customers";

    public function __construct($db) {
        $this->conn = $db;
    }

    // إضافة عميل جديد مع معالجة حساب الـ Cash
    public function create($branch_id, $data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET branch_id=:branch_id, first_name=:first_name, last_name=:last_name, 
                      mobile=:mobile, address=:address, cash_account=:cash_account, 
                      company=:company, is_active=1";
        
        $stmt = $this->conn->prepare($query);

        // منطق حساب الـ Cash: إذا كان العميل Cash، يتم تصفير البيانات الأخرى
        if ($data['cash_account'] == 'Cash') {
            $firstName = "---";
            $lastName = "---";
            $mobile = "---";
            $address = "---";
            $company = "---";
        } else {
            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            $mobile = $data['mobile'];
            $address = $data['address'];
            $company = $data['company'];
        }

        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->bindParam(":first_name", $firstName);
        $stmt->bindParam(":last_name", $lastName);
        $stmt->bindParam(":mobile", $mobile);
        $stmt->bindParam(":address", $address);
        $stmt->bindParam(":cash_account", $data['cash_account']);
        $stmt->bindParam(":company", $company);

        return $stmt->execute();
    }

    // جلب العملاء النشطين لفرع محدد
    public function getActive($branch_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE branch_id = :branch_id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // جلب العملاء المؤرشفين (غير النشطين)
    public function getArchived($branch_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE branch_id = :branch_id AND is_active = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // أرشفة عميل
    public function archiveCustomer($id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}