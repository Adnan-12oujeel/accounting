<?php
// 1. تفعيل تنظيف المخرجات (هذا هو الحل السحري لمنع ظهور HTML في الـ API)
ob_start(); 
header("Access-Control-Allow-Origin: *"); // اسمح لأي دومين بالاتصال
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // السماح بهذه العمليات
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات Preflight (التي يرسلها المتصفح قبل الـ POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// إخفاء الأخطاء العشوائية
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    // 2. التحقق من الملفات قبل استدعائها
    $paths = [
        'db' => __DIR__ . '/../config/db.php',
        'func' => __DIR__ . '/../includes/functions.php',
        'mod' => __DIR__ . '/../modules/CategoryModule.php'
    ];

    foreach ($paths as $key => $path) {
        if (!file_exists($path)) {
            throw new Exception("الملف غير موجود: $path");
        }
    }

    // استدعاء الملفات
    require_once $paths['db'];
    
    // التحقق من ملف الدوال قبل استدعائه لتجنب المشاكل
    if (file_exists($paths['func'])) {
        require_once $paths['func'];
    }

    require_once $paths['mod'];

    // بدء المعالجة
    $catMod = new CategoryModule($pdo);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // قراءة البيانات
    $input_json = file_get_contents("php://input");
    $data = json_decode($input_json, true);

    // -----------------------------------------------------------
    // معالجة GET
    // -----------------------------------------------------------
    if ($method === 'GET') {
        $branch_id = $_GET['branch_id'] ?? null;
        if ($branch_id) {
            $result = $catMod->getCategories($branch_id);
            $response = ["status" => 200, "message" => "Success", "data" => $result];
        } else {
            $response = ["status" => 400, "message" => "رقم الفرع (branch_id) مطلوب"];
        }
    }

    // -----------------------------------------------------------
    // معالجة POST
    // -----------------------------------------------------------
    elseif ($method === 'POST') {
        if (!empty($data['name']) && !empty($data['branch_id'])) {
            if ($catMod->addCategory($data['branch_id'], $data['name'])) {
                http_response_code(201);
                $response = ["status" => 201, "message" => "تمت الإضافة بنجاح: " . $data['name']];
            } else {
                throw new Exception("فشل الإضافة في قاعدة البيانات");
            }
        } else {
            http_response_code(400);
            $response = ["status" => 400, "message" => "البيانات ناقصة (الاسم أو الفرع)"];
        }
    } else {
        $response = ["status" => 405, "message" => "طريقة الطلب غير مدعومة"];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ["status" => 500, "message" => "خطأ: " . $e->getMessage()];
}

// 3. تنظيف أي شيء تمت طباعته بالخطأ قبل الـ JSON
ob_end_clean(); 

// طباعة الـ JSON النظيف فقط
echo json_encode($response);
exit;