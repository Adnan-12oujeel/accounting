<?php
header("Access-Control-Allow-Origin: *"); // اسمح لأي دومين بالاتصال
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // السماح بهذه العمليات
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات Preflight (التي يرسلها المتصفح قبل الـ POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/BranchModule.php';

$branchMod = new BranchModule($pdo);
$method = $_SERVER['REQUEST_METHOD'];

// تجلب البيانات المرسلة من الفرونت آند
$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'POST') {
    // 1. التحقق من الصلاحية (بفرض إرسال صلاحيات المستخدم الحالي)
    $user_permissions = $data['current_user_permissions'] ?? [];
    
    if (!isset($user_permissions['can_create_branches']) || $user_permissions['can_create_branches'] !== true) {
        sendResponse(403, "عذراً، هذه الصلاحية مخصصة للـ Super Admin فقط");
    }

    // 2. تنفيذ الإضافة
    if (!empty($data['name'])) {
        if ($branchMod->addBranch($data)) {
            sendResponse(201, "تم إنشاء الفرع بنجاح");
        } else {
            sendResponse(500, "فشل إنشاء الفرع");
        }
    } else {
        sendResponse(400, "اسم الفرع مطلوب");
    }
}

if ($method === 'GET') {
    $branches = $branchMod->getAllBranches();
    sendResponse(200, "Success", $branches);
}