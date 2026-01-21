<?php
ob_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/Logger.php'; // استدعاء المسجل

$response = [];

try {
    // استقبال البيانات
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        throw new Exception("البريد الإلكتروني وكلمة المرور مطلوبان");
    }

    $email = $data['email'];
    $password = $data['password'];

    // 1. البحث عن المستخدم (يجب أن يكون فعالاً والفرع فعالاً أيضاً)
    $sql = "SELECT u.*, b.name as branch_name, b.active as branch_active 
            FROM users u 
            JOIN branches b ON u.branch_id = b.id 
            WHERE u.email = ? AND u.active = 1";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. التحقق من المستخدم وكلمة المرور
    if (!$user || !password_verify($password, $user['password'])) {
        // تسجيل محاولة فاشلة (اختياري)
        // Logger::log($pdo, 1, null, 'login_fail', 'users', 0, "محاولة دخول فاشلة للإيميل: $email");
        
        http_response_code(401); // Unauthorized
        throw new Exception("بيانات الدخول غير صحيحة");
    }

    // 3. التحقق من حالة الفرع
    if ($user['branch_active'] == 0) {
        http_response_code(403); // Forbidden
        throw new Exception("عذراً، هذا الفرع غير نشط حالياً. يرجى مراجعة الإدارة.");
    }

    // 4. تسجيل الدخول بنجاح
    // تحديث تاريخ آخر دخول
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    // تسجيل العملية في السجل
    Logger::log($pdo, $user['branch_id'], $user['id'], 'login', 'users', $user['id'], 'تم تسجيل الدخول بنجاح');

    // 5. تجهيز الرد (لا ترسل كلمة المرور أبداً)
    unset($user['password']); 
    
    // تحويل الصلاحيات من JSON String إلى Array (إذا كانت مخزنة كنص)
    $permissions = json_decode($user['user_permissions'], true); 
    if (!$permissions) $permissions = []; // مصفوفة فارغة إذا لم يوجد صلاحيات

    http_response_code(200);
    $response = [
        "status" => 200,
        "message" => "تم تسجيل الدخول بنجاح",
        "data" => [
            "user_id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "branch_id" => $user['branch_id'],
            "branch_name" => $user['branch_name'],
            "permissions" => $permissions,
            // "token" => "..." // مستقبلاً يمكن إضافة JWT هنا
        ]
    ];

} catch (Exception $e) {
    if (http_response_code() == 200) http_response_code(400); // Bad Request إذا لم يحدد كود سابق
    $response = [
        "status" => http_response_code(),
        "message" => $e->getMessage()
    ];
}

ob_end_clean();
echo json_encode($response);
?>