<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/invoices.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($action == 'create') {
    if (!isset($inputData['header']) || !isset($inputData['items'])) {
        echo json_encode(["status" => "error", "message" => "Invoice header or items missing"]);
        exit;
    }

    try {
        $invoiceId = createInvoice($inputData['header'], $inputData['items']);
        
        // --- تشغيل نظام الإشعارات تلقائياً ---
        require_once __DIR__ . '/../functions/notifications.php';
        notifyUsersOnNewInvoice($invoiceId, $inputData['header']['branch_id']);
        
        echo json_encode(["status" => "success", "message" => "Invoice created and users notified"]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}