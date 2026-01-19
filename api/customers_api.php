<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/customers.php';

// 1. قراءة البيانات (يدعم JSON و Form-Data)
$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;

$action = $_GET['action'] ?? '';

// --- إضافة عميل جديد ---
if ($action == 'add') {
    
    $branch_id    = $data['branch_id'] ?? null;
    $first_name   = $data['first_name'] ?? null;
    $last_name    = $data['last_name'] ?? null;
    $mobile       = $data['mobile'] ?? null;
    $address      = $data['address'] ?? null;
    $cash_account = $data['cash_account'] ?? 'Cash';
    $company      = $data['company'] ?? null;

    if (!$branch_id || !$first_name) {
        echo json_encode(["status" => "error", "message" => "Branch ID and First Name are required"]);
        exit;
    }

    try {
        $id = addCustomer($branch_id, $first_name, $last_name, $mobile, $address, $cash_account, $company);
        echo json_encode(["status" => "success", "message" => "Customer added successfully", "id" => $id]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// --- جلب قائمة العملاء لفرع معين ---
elseif ($action == 'list') {
    $branch_id = $_GET['branch_id'] ?? null;

    if (!$branch_id) {
        echo json_encode(["status" => "error", "message" => "Branch ID is required to list customers"]);
        exit;
    }

    $customers = getCustomersByBranch($branch_id);
    echo json_encode(["status" => "success", "data" => $customers]);
    exit;
}

// --- أرشفة عميل ---
elseif ($action == 'archive') {
    $customer_id = $data['id'] ?? null;
    if (!$customer_id) {
        echo json_encode(["status" => "error", "message" => "Customer ID is required for archiving"]);
        exit;
    }

    archiveCustomer($customer_id);
    echo json_encode(["status" => "success", "message" => "Customer archived successfully"]);
    exit;
}

else {
    echo json_encode(["status" => "error", "message" => "Invalid action"]);
    exit;
}