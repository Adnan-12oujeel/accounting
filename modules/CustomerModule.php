<?php
class CustomerModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // جلب العملاء (نشط أو مؤرشف) لفرع معين
    public function getCustomers($branch_id, $active = 1) {
        $sql = "SELECT * FROM customers WHERE branch_id = ? AND is_active = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branch_id, $active]);
        return $stmt->fetchAll();
    }

    // إضافة عميل جديد مع منطق حساب الكاش
    public function addCustomer($data) {
        // إذا كان نوع الحساب Cash، يتم تعيين البيانات الأخرى كـ "---"
        if ($data['cash_account'] === 'Cash') {
            $data['first_name'] = 'Cash';
            $data['last_name']  = 'Customer';
            $data['mobile']     = '---';
            $data['address']    = '---';
            $data['company']    = '---';
        }

        $sql = "INSERT INTO customers (branch_id, first_name, last_name, mobile, address, cash_account, company) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $data['branch_id'], $data['first_name'], $data['last_name'],
            $data['mobile'], $data['address'], $data['cash_account'], $data['company']
        ]);
    }

    // تغيير حالة العميل (أرشفة أو تفعيل)
    public function toggleStatus($id, $status) {
        $sql = "UPDATE customers SET is_active = ? WHERE id = ?";
        return $this->pdo->prepare($sql)->execute([$status, $id]);
    }
}