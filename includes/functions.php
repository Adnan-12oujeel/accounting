<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

function sendResponse($status, $message, $data = null) {
    http_response_code($status);
    echo json_encode([
        "status"  => $status,
        "message" => $message,
        "data"    => $data
    ]);
    exit();
}

function notifyUsersOfNewInvoice($pdo, $branch_id, $invoice_data) {
    // جلب إيميلات المستخدمين النشطين في هذا الفرع
    $stmt = $pdo->prepare("SELECT email, user_permissions FROM users WHERE branch_id = ? AND is_active = 1");
    $stmt->execute([$branch_id]);
    $users = $stmt->fetchAll();

    foreach ($users as $user) {
        $permissions = json_decode($user['user_permissions'], true);
        // التحقق من صلاحية رؤية الفواتير (مثال: 'view_invoices')
        if (in_array('view_invoices', $permissions)) {
            $to = $user['email'];
            $subject = "إشعار فاتورة جديدة - فرع " . $branch_id;
            $msg = "تمت إضافة فاتورة جديدة بقيمة: " . $invoice_data['net_amount'];
            mail($to, $subject, $msg); // يفضل استخدام PHPMailer لاحقاً
        }
    }
}