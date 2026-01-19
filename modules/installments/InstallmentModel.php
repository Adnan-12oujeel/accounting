<?php
class InstallmentModel {
    private $conn;
    private $table_name = "installments";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. إضافة دفعة جديدة لفاتورة معينة
    public function addInstallment($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET invoice_id=:invoice_id, date=:date, paid_amount=:amount, 
                      payment_method=:method, invoice_creator=:creator";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":invoice_id", $data['invoice_id']);
        $stmt->bindParam(":date", $data['date']);
        $stmt->bindParam(":amount", $data['paid_amount']);
        $stmt->bindParam(":method", $data['payment_method']); // تجلب من جدول enums
        $stmt->bindParam(":creator", $data['invoice_creator']);

        return $stmt->execute();
    }

    // 2. جلب جميع دفعات فاتورة محددة
    public function getInvoiceInstallments($invoice_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE invoice_id = :id ORDER BY date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $invoice_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}