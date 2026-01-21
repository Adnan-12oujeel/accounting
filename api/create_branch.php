<?php
// api/create_branch.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once __DIR__ . '/../config/db.php';

// يمكن إضافة حماية هنا للتأكد أن الطالب هو Super Admin

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['name']) || empty($data['admin_email']) || empty($data['admin_password'])) {
        throw new Exception("بيانات ناقصة (اسم الفرع، إيميل المدير، كلمة السر)");
    }

    $pdo->beginTransaction();

    // 1. إنشاء الفرع
    $stmt = $pdo->prepare("INSERT INTO branches (name, address, mobile, email, date_of_start, plan, active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([
        $data['name'], 
        $data['address'] ?? '', 
        $data['mobile'] ?? '', 
        $data['email'] ?? '', 
        $data['date_of_start'] ?? date('Y-m-d'),
        $data['plan'] ?? 'yearly'
    ]);
    $branch_id = $pdo->lastInsertId();

    // 2. إنشاء مستخدم الأدمن لهذا الفرع
    $hashed_pass = password_hash($data['admin_password'], PASSWORD_DEFAULT);
    $permissions = json_encode(["all_access"]); // صلاحيات كاملة

    $stmtUser = $pdo->prepare("INSERT INTO users (branch_id, name, email, password, user_permissions, active) VALUES (?, ?, ?, ?, ?, 1)");
    $stmtUser->execute([
        $branch_id,
        "مدير " . $data['name'],
        $data['admin_email'],
        $hashed_pass,
        $permissions
    ]);

    $pdo->commit();

    echo json_encode([
        "status" => 201, 
        "message" => "تم إنشاء الفرع والمستخدم بنجاح", 
        "branch_id" => $branch_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => 500, "message" => $e->getMessage()]);
}
?>