<?php
// 1. تفعيل الحماية لتنظيف أي نصوص تظهر بالخطأ
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

error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    // 2. التحقق من المسارات واستدعاء الملفات
    $paths = [
        'db' => __DIR__ . '/../config/db.php',
        'func' => __DIR__ . '/../includes/functions.php',
        'mod' => __DIR__ . '/../modules/ProductModule.php'
    ];

    foreach ($paths as $path) {
        if (!file_exists($path)) throw new Exception("الملف غير موجود: $path");
    }

    require_once $paths['db'];
    require_once $paths['func'];
    require_once $paths['mod'];

    $prodMod = new ProductModule($pdo);
    $method = $_SERVER['REQUEST_METHOD'];

    // 3. قراءة البيانات (JSON) - هذا هو السطر الأهم
    $input_json = file_get_contents("php://input");
    $data = json_decode($input_json, true);

    // ==========================================
    // معالجة طلبات GET (جلب المنتجات)
    // ==========================================
    if ($method === 'GET') {
        $branch_id = $_GET['branch_id'] ?? null;
        $search = $_GET['search'] ?? null;
        $cat_id = $_GET['category_id'] ?? null;

        if ($branch_id) {
            $products = $prodMod->getProducts($branch_id, $search, $cat_id);
            $response = ["status" => 200, "message" => "Success", "data" => $products];
        } else {
            http_response_code(400);
            $response = ["status" => 400, "message" => "رقم الفرع مطلوب"];
        }
    }

    // ==========================================
    // معالجة طلبات POST (إضافة منتج)
    // ==========================================
    elseif ($method === 'POST') {
        // التحقق من البيانات الأساسية
        if (!empty($data['name']) && !empty($data['price']) && !empty($data['branch_id'])) {
            
            // محاولة الإضافة
            if ($prodMod->addProduct($data)) {
                http_response_code(201);
                $response = ["status" => 201, "message" => "تم إضافة المنتج بنجاح: " . $data['name']];
            } else {
                throw new Exception("فشل إضافة المنتج في قاعدة البيانات");
            }

        } else {
            // إذا كانت البيانات ناقصة، نرسل رسالة توضح السبب
            http_response_code(400);
            $response = [
                "status" => 400, 
                "message" => "بيانات ناقصة (الاسم، السعر، أو الفرع). تأكد من إرسال JSON صحيح."
            ];
        }
    } else {
        http_response_code(405);
        $response = ["status" => 405, "message" => "Method Not Allowed"];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ["status" => 500, "message" => "خطأ في السيرفر: " . $e->getMessage()];
}

// 4. تنظيف وإرسال الرد النظيف
ob_end_clean();
echo json_encode($response);
exit;