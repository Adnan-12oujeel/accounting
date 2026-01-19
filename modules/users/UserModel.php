<?php
class UserModel {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. إنشاء مستخدم جديد (Admin أو User عادي)
    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET branch_id=:branch_id, name=:name, email=:email, 
                      password=:password, user_permissions=:perms, is_active=1";
        
        $stmt = $this->conn->prepare($query);

        // تشفير كلمة المرور للأمان
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
        // تحويل المصفوفة إلى JSON لتخزينها في عمود user_permissions
        $permissions = json_encode($data['user_permissions']);

        $stmt->bindParam(":branch_id", $data['branch_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":email", $data['email']);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":perms", $permissions);

        return $stmt->execute();
    }

    // 2. التحقق من تسجيل الدخول
    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // تحديث وقت آخر ظهور
            $this->updateLastLogin($user['id']);
            return $user;
        }
        return false;
    }

    // 3. تحديث وقت آخر دخول
    private function updateLastLogin($id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
    }

    // 4. أرشفة مستخدم (إيقاف الفعالية دون الحذف)
    public function toggleStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET is_active = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }
}