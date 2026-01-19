<?php
require_once __DIR__ . '/../config/db.php';

// إضافة صنف جديد
function addCategory($branch_id, $name, $country_of_origin) {
    global $conn;
    $sql = "INSERT INTO categories (branch_id, name, country_of_origin) 
            VALUES (:branch_id, :name, :country_of_origin)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':name' => $name,
        ':country_of_origin' => $country_of_origin
    ]);
    return $conn->lastInsertId();
}

// جلب أصناف فرع معين
function getCategoriesByBranch($branch_id, $show_archived = 0) {
    global $conn;
    $sql = "SELECT * FROM categories WHERE branch_id = :branch_id AND is_active = :is_active";
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':is_active' => $show_archived ? 0 : 1
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}