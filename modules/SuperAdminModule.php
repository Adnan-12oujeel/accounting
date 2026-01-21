<?php
class SuperAdminModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // إنشاء فرع جديد وحساب Admin خاص به
    public function createBranchAndAdmin($branchData, $adminData) {
        try {
            $this->pdo->beginTransaction();

            // 1. إنشاء الفرع
            $sqlBranch = "INSERT INTO branches (name, address, mobile, email, plan, date_of_start) VALUES (?, ?, ?, ?, ?, ?)";
            $stmtB = $this->pdo->prepare($sqlBranch);
            $stmtB->execute([
                $branchData['name'], $branchData['address'], $branchData['mobile'],
                $branchData['email'], $branchData['plan'], $branchData['date_of_start']
            ]);
            $branchId = $this->pdo->lastInsertId();

            // 2. إنشاء حساب الـ Admin لهذا الفرع
            $sqlAdmin = "INSERT INTO users (branch_id, name, email, password, user_permissions) VALUES (?, ?, ?, ?, ?)";
            $stmtA = $this->pdo->prepare($sqlAdmin);
            $hashedPassword = password_hash($adminData['password'], PASSWORD_DEFAULT);
            $stmtA->execute([
                $branchId, $adminData['name'], $adminData['email'], 
                $hashedPassword, json_encode($adminData['permissions'])
            ]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }
}