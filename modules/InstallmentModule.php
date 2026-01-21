<?php
class InstallmentModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // إضافة دفعة جديدة وتحديث حالة الفاتورة
    public function addInstallment($data) {
        try {
            $this->pdo->beginTransaction();

            // 1. تسجيل الدفعة
            $sql = "INSERT INTO installments (invoice_id, paid_amount, payment_method, invoice_creator, date) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['invoice_id'], $data['paid_amount'], 
                $data['payment_method'], $data['invoice_creator']
            ]);

            // 2. تحديث حالة الفاتورة (اختياري: يمكن حساب المجموع لتغيير الحالة لـ Paid)
            $updateSql = "UPDATE invoices SET invoice_status = 'Installments' WHERE id = ?";
            $this->pdo->prepare($updateSql)->execute([$data['invoice_id']]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    public function getInvoiceInstallments($invoice_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM installments WHERE invoice_id = ? ORDER BY date DESC");
        $stmt->execute([$invoice_id]);
        return $stmt->fetchAll();
    }
}