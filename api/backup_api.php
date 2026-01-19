<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/backup.php';

// في المستقبل، سنضيف هنا شرط التحقق من أن المستخدم هو Super Admin
$action = $_GET['action'] ?? '';

if ($action == 'run_manual') {
    try {
        $result = generateBackup();
        
        // هنا يتم استدعاء دالة إرسال الإيميل (سنقوم ببرمجتها في موديول الإشعارات)
        // sendBackupEmail($result['download_url']); 

        echo json_encode($result);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
} else {
    echo json_encode(["status" => "error", "message" => "عملية غير مسموح بها"]);
    exit;
}