<?php
require_once __DIR__ . '/../config/db.php';



// إضافة مستخدم جديد
function addUser($branch_id, $name, $email, $password, $permissions) {
    global $conn;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (branch_id, name, email, password, user_permissions) 
            VALUES (:branch_id, :name, :email, :password, :permissions)";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':branch_id'   => $branch_id,
        ':name'        => $name,
        ':email'       => $email,
        ':password'    => $hashedPassword,
        ':permissions' => json_encode($permissions) // تحويل المصفوفة لـ JSON
    ]);
    return $conn->lastInsertId();
}

// جلب مستخدمي الفرع
function getUsersByBranch($branch_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT id, name, email, user_permissions, is_active FROM users WHERE branch_id = :branch_id");
    $stmt->execute([':branch_id' => $branch_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateCredentials($table, $id, $new_email, $new_password = null) {
    global $conn;
    
    $sql = "UPDATE $table SET email = :email";
    $params = [':email' => $new_email, ':id' => $id];

    if ($new_password) {
        $sql .= ", password = :password";
        $params[':password'] = password_hash($new_password, PASSWORD_DEFAULT);
    }

    $sql .= " WHERE id = :id";
    
    $stmt = $conn->prepare($sql);
    return $stmt->execute($params);
}