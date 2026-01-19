<?php
require_once __DIR__ . '/../config/db.php';

// إضافة منتج جديد
function addProduct($data) {
    global $conn;
    $sql = "INSERT INTO products (branch_id, category_id, unit_id, name, product_code, container_code, weight, selling_price, productive_capital, product_place, received_date, notes) 
            VALUES (:branch_id, :category_id, :unit_id, :name, :product_code, :container_code, :weight, :selling_price, :productive_capital, :product_place, :received_date, :notes)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id'         => $data['branch_id'],
        ':category_id'       => $data['category_id'],
        ':unit_id'           => $data['unit_id'],
        ':name'              => $data['name'],
        ':product_code'      => $data['product_code'] ?? null,
        ':container_code'    => $data['container_code'] ?? null,
        ':weight'            => $data['weight'] ?? null,
        ':selling_price'     => $data['selling_price'],
        ':productive_capital'=> $data['productive_capital'],
        ':product_place'     => $data['product_place'] ?? 'depot',
        ':received_date'     => $data['received_date'] ?? null,
        ':notes'             => $data['notes'] ?? null
    ]);
    return $conn->lastInsertId();
}

// جلب منتجات فرع معين مع اسم الصنف والوحدة
function getProductsByBranch($branch_id, $show_archived = 0) {
    global $conn;
    $sql = "SELECT p.*, c.name as category_name, u.unit_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.branch_id = :branch_id AND p.is_active = :is_active";
            
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id' => $branch_id,
        ':is_active'  => $show_archived ? 0 : 1
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchProducts($branch_id, $filters = []) {
    global $conn;

    // الاستعلام الأساسي مع الربط بجداول الأصناف والوحدات
    $sql = "SELECT p.*, c.name as category_name, u.unit_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.branch_id = :branch_id AND p.is_active = 1";

    $params = [':branch_id' => $branch_id];

    // بناء جملة الـ WHERE ديناميكياً بناءً على الفلاتر
    if (!empty($filters)) {
        foreach ($filters as $column => $value) {
            if ($value !== null && $value !== '') {
                // استخدام LIKE للبحث الجزئي في النصوص
                $sql .= " AND p.$column LIKE :$column";
                $params[":$column"] = "%$value%";
            }
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}