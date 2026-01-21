<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../modules/UserModule.php';

$data = json_decode(file_get_contents("php://input"), true);
$userMod = new UserModule($pdo);

// في نظام الـ API، يتم إرسال ID المستخدم الحالي من الـ Token المخزن في الفرونت آند
$currentUserId = $data['user_id'] ?? null; 

if ($currentUserId) {
    $result = $userMod->updateProfile($currentUserId, $data);
    
    if ($result) {
        sendResponse(200, "تم تحديث البيانات بنجاح");
    } else {
        sendResponse(500, "فشل تحديث البيانات أو لم يتم إرسال بيانات جديدة");
    }
} else {
    sendResponse(400, "معرف المستخدم مطلوب");
}