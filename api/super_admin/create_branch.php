<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../modules/SuperAdminModule.php';

// التحقق من أن الطلب POST
$data = json_decode(file_get_contents("php://input"), true);
$superMod = new SuperAdminModule($pdo);

if (!empty($data['branch']) && !empty($data['admin'])) {
    $result = $superMod->createBranchAndAdmin($data['branch'], $data['admin']);
    
    if ($result === true) {
        sendResponse(201, "تم إنشاء الفرع وحساب المسؤول بنجاح");
    } else {
        sendResponse(500, "فشل الإنشاء: " . $result['error']);
    }
} else {
    sendResponse(400, "بيانات غير مكتملة");
}
