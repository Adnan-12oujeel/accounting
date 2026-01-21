<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/UserModule.php';

$userMod = new UserModule($pdo);
$method = $_SERVER['REQUEST_METHOD'];

$data = json_decode(file_get_contents("php://input"), true);

// =================================================================
// 1. إضافة مستخدم جديد (POST)
// =================================================================
if ($method === 'POST') {
    // التحقق من البيانات الأساسية
    if (empty($data['name']) || empty($data['email']) || empty($data['password']) || empty($data['branch_id'])) {
        sendResponse(400, "جميع الحقول مطلوبة (الاسم، البريد، كلمة المرور، الفرع)");
    }

    // محاكاة التحقق من الصلاحيات (لاحقاً سيتم استخراجها من الـ Token)
    // نفترض أن الفرونت آند يرسل دور المستخدم الحالي للتحقق
    $currentUserRole = $data['current_user_role'] ?? 'user'; 
    $currentUserBranch = $data['current_user_branch_id'] ?? null;

    // قاعدة: الأدمن العادي لا يمكنه إضافة مستخدم لفرع غير فرعه
    if ($currentUserRole === 'admin' && $data['branch_id'] != $currentUserBranch) {
        sendResponse(403, "غير مصرح لك بإضافة مستخدمين لفرع آخر");
    }

    // قاعدة: الموظف العادي لا يمكنه الإضافة
    if ($currentUserRole !== 'super_admin' && $currentUserRole !== 'admin') {
        sendResponse(403, "ليس لديك صلاحية لإضافة مستخدمين");
    }

    // محاولة الإضافة
    try {
        if ($userMod->addUser($data)) {
            sendResponse(201, "تم إضافة المستخدم بنجاح");
        } else {
            sendResponse(500, "حدث خطأ أثناء الإضافة");
        }
    } catch (PDOException $e) {
        // التعامل مع تكرار البريد الإلكتروني
        if ($e->getCode() == 23000) {
            sendResponse(409, "البريد الإلكتروني مسجل مسبقاً");
        } else {
            sendResponse(500, $e->getMessage());
        }
    }
}

// =================================================================
// 2. جلب المستخدمين (GET)
// =================================================================
if ($method === 'GET') {
    // إذا تم طلب مستخدم محدد بالـ ID
    if (isset($_GET['id'])) {
        $user = $userMod->getUserById($_GET['id']);
        if ($user) {
            sendResponse(200, "Success", $user);
        } else {
            sendResponse(404, "المستخدم غير موجود");
        }
    } 
    // جلب كل مستخدمي فرع معين
    elseif (isset($_GET['branch_id'])) {
        $users = $userMod->getUsersByBranch($_GET['branch_id']);
        sendResponse(200, "Success", $users);
    } 
    else {
        sendResponse(400, "يرجى تحديد رقم الفرع (branch_id) أو رقم المستخدم (id)");
    }
}

// =================================================================
// 3. تعديل بيانات مستخدم (PUT)
// =================================================================
if ($method === 'PUT') {
    // نتوقع أن يكون الرابط: api/users.php?id=1
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        sendResponse(400, "معرف المستخدم (ID) مطلوب للتعديل");
    }

    // حالة 1: تفعيل / إيقاف المستخدم (Toggle Status)
    if (isset($data['is_active'])) {
        if ($userMod->toggleStatus($id, $data['is_active'])) {
            sendResponse(200, "تم تحديث حالة المستخدم بنجاح");
        } else {
            sendResponse(500, "فشل تحديث الحالة");
        }
    } 
    // حالة 2: تحديث البيانات (الاسم، البريد، كلمة المرور)
    else {
        if ($userMod->updateUser($id, $data)) {
            sendResponse(200, "تم تحديث بيانات المستخدم بنجاح");
        } else {
            sendResponse(200, "لم يتم إجراء تغييرات (البيانات متطابقة أو خطأ في التنفيذ)");
        }
    }
}

// إذا لم تكن الطريقة مدعومة
if ($method === 'DELETE') {
    sendResponse(405, "الحذف النهائي غير مسموح، يرجى استخدام التعديل لإيقاف التفعيل");
}