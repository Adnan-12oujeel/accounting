<?php
require_once __DIR__ . '/../config/db.php';

function addInstallment($invoice_id, $date, $amount, $method, $creator) {
    global $conn;
    
    $sql = "INSERT INTO installments (invoice_id, date, paid_amount, payment_method, invoice_creator) 
            VALUES (:inv_id, :date, :amount, :method, :creator)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':inv_id'  => $invoice_id,
        ':date'    => $date,
        ':amount'  => $amount,
        ':method'  => $method,
        ':creator' => $creator
    ]);
    
    return $conn->lastInsertId();
}

// دالة لجلب جميع دفعات فاتورة معينة
function getInvoiceInstallments($invoice_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM installments WHERE invoice_id = :inv_id");
    $stmt->execute([':inv_id' => $invoice_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}