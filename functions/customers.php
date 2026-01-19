<?php
require_once __DIR__ . '/../config/db.php';

// إضافة عميل جديد
function addCustomer($branch_id, $first_name, $last_name, $mobile, $address, $cash_account, $company) {
    global $conn;
    $sql = "INSERT INTO customers (branch_id, first_name, last_name, mobile, address, cash_account, company) 
            VALUES (:branch_id, :first_name, :last_name, :mobile, :address, :cash_account, :company)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':mobile' => $mobile,
        ':address' => $address,
        ':cash_account' => $cash_account,
        ':company' => $company
    ]);
    return $conn->lastInsertId();
}

// جلب عملاء فرع معين فقط
function getCustomersByBranch($branch_id, $show_archived = 0) {
    global $conn;
    $sql = "SELECT * FROM customers WHERE branch_id = :branch_id AND is_active = :is_active";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':is_active' => $show_archived ? 0 : 1
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// أرشفة عميل (إيقاف تفعيل)
function archiveCustomer($customer_id) {
    global $conn;
    $stmt = $conn->prepare("UPDATE customers SET is_active = 0 WHERE id = :id");
    return $stmt->execute([':id' => $customer_id]);
}