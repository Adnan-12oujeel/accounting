<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

// استلام ومعالجة بيانات JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "الحقول مطلوبة"]);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            echo json_encode([
                "status" => "success",
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['name']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "بيانات الدخول غير صحيحة"]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "خطأ فني: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "طلب غير معروف"]);
}