<?php
// جلب ملف الاتصال
require_once __DIR__ . '/../config/db.php';

// دالة لإضافة فرع جديد
function addBranch($name, $address, $mobile, $email, $date_of_start, $plan) {
    global $conn;
    $sql = "INSERT INTO branches (name, address, mobile, email, date_of_start, plan) 
            VALUES (:name, :address, :mobile, :email, :date_of_start, :plan)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':address' => $address,
        ':mobile' => $mobile,
        ':email' => $email,
        ':date_of_start' => $date_of_start,
        ':plan' => $plan
    ]);
    
    return $conn->lastInsertId();
}

// دالة لجلب كل الفروع النشطة
function getAllBranches() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM branches WHERE is_active = 1");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>