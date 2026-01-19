<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/branches.php';

// 1. قراءة البيانات (يدعم JSON و Form-Data)
$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;

// 2. تحديد العملية المطلوب تنفيذها
$action = $_GET['action'] ?? '';

// --- عملية إضافة فرع جديد ---
if ($action == 'add') {
    // استخراج القيم من مصفوفة $data التي جهزناها في الأعلى
    $name          = $data['name'] ?? null;
    $address       = $data['address'] ?? null;
    $mobile        = $data['mobile'] ?? null;
    $email         = $data['email'] ?? null;
    $date_of_start = $data['date_of_start'] ?? null;
    $plan          = $data['plan'] ?? null;

    // التحقق من البيانات الأساسية
    if (!$name) {
        echo json_encode(["status" => "error", "message" => "Name is required"]);
        exit;
    }

    try {
        $id = addBranch($name, $address, $mobile, $email, $date_of_start, $plan);
        echo json_encode([
            "status" => "success", 
            "message" => "Branch added successfully", 
            "id" => $id
        ]);
        exit; // إنهاء السكريبت هنا لمنع تكرار الكود
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
} 

// --- عملية جلب قائمة الفروع ---
elseif ($action == 'list') {
    try {
        $branches = getAllBranches();
        echo json_encode(["status" => "success", "data" => $branches]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

// --- في حال لم تطابق الـ action أي مما سبق ---
else {
    echo json_encode(["status" => "error", "message" => "Invalid or missing action"]);
    exit;
}