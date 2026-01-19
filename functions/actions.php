<?php
require_once __DIR__ . '/../config/db.php';

// دالة تسجيل عملية
function logAction($branch_id, $user_id, $action_type, $product_id, $details = "") {
    global $conn;
    
    $sql = "INSERT INTO actions (branch_id, user_id, action_type, product_id, details) 
            VALUES (:branch_id, :user_id, :action_type, :product_id, :details)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id'   => $branch_id,
        ':user_id'     => $user_id,
        ':action_type' => $action_type,
        ':product_id'  => $product_id,
        ':details'     => $details
    ]);
    
    return $conn->lastInsertId();
}

// جلب سجل العمليات لفرع معين مع بيانات المنتج والمستخدم
function getActionsByBranch($branch_id) {
    global $conn;
    $sql = "SELECT a.*, u.name as user_name, p.name as product_name 
            FROM actions a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN products p ON a.product_id = p.id
            WHERE a.branch_id = :branch_id 
            ORDER BY a.date_and_time DESC";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([':branch_id' => $branch_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}