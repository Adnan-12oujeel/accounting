<?php
class UserModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. تسجيل الدخول (Login)
    public function login($email, $password) {
        // البحث عن المستخدم وتأكد أنه نشط (is_active = 1)
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // التحقق من كلمة المرور المشفرة
        if ($user && password_verify($password, $user['password'])) {
            // حذف كلمة المرور من المصفوفة قبل إرجاعها للأمان
            unset($user['password']);
            return $user;
        }
        return false;
    }

    // 2. إضافة مستخدم جديد (Add User)
    public function addUser($data) {
        // تشفير كلمة المرور وتحويل الصلاحيات إلى JSON
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $permissions = json_encode($data['permissions']); // تأكد أن الفرونت يرسلها كـ Array

        $sql = "INSERT INTO users (branch_id, name, email, password, user_permissions, is_active) 
                VALUES (?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['branch_id'],
            $data['name'],
            $data['email'],
            $hashedPassword,
            $permissions
        ]);
    }

    // 3. جلب مستخدمي فرع معين (للعرض في لوحة التحكم)
    public function getUsersByBranch($branch_id) {
        $sql = "SELECT id, branch_id, name, email, user_permissions, is_active, created_at 
                FROM users 
                WHERE branch_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. جلب بيانات مستخدم واحد (لأغراض التعديل)
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT id, name, email, branch_id, user_permissions, is_active FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 5. تحديث بيانات المستخدم (Update User)
    // تدعم تحديث كلمة المرور فقط إذا تم إرسالها
    public function updateUser($id, $data) {
        $fields = [];
        $params = [];

        if (!empty($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        if (!empty($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (!empty($data['permissions'])) {
            $fields[] = "user_permissions = ?";
            $params[] = json_encode($data['permissions']);
        }
        // تحديث كلمة المرور فقط إذا قام المستخدم بكتابة كلمة مرور جديدة
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) {
            return false; // لا يوجد شيء لتحديثه
        }

        $params[] = $id;
        // مثال للناتج: UPDATE users SET name = ?, email = ? WHERE id = ?
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    // 6. تغيير الحالة (تفعيل / إيقاف)
    public function toggleStatus($id, $status) {
        // status يجب أن يكون 0 أو 1
        $sql = "UPDATE users SET is_active = ? WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$status, $id]);
    }
}