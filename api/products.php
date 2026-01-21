<?php
// 1. تفعيل الحماية لتنظيف أي نصوص تظهر بالخطأ (Start Output Buffering)
ob_start();

// إعدادات الـ CORS والرؤوس
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات Preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// إخفاء أخطاء PHP العادية عن المخرجات لتجنب تشوه الـ JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = [];

try {
    // 2. التحقق من المسارات واستدعاء الملفات الضرورية
    $paths = [
        'db' => __DIR__ . '/../config/db.php',
        'mod' => __DIR__ . '/../modules/ProductModule.php'
    ];

    foreach ($paths as $path) {
        if (!file_exists($path)) throw new Exception("الملف غير موجود: $path");
    }

    require_once $paths['db'];
    // نتأكد من وجود الكلاس قبل استدعائه، وإذا لم يكن موجوداً نعتمد على الـ PDO المباشر في هذا الملف
    if (file_exists($paths['mod'])) {
        require_once $paths['mod'];
        $prodMod = new ProductModule($pdo);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    
    // قراءة البيانات القادمة (JSON)
    $input_json = file_get_contents("php://input");
    $data = json_decode($input_json, true);

    // ==========================================
    // معالجة طلبات GET (جلب وبحث المنتجات)
    // ==========================================
    if ($method === 'GET') {
        $branch_id = $_GET['branch_id'] ?? null;
        $action = $_GET['action'] ?? null; // لتحديد نوع العملية (search أو list)

        if (!$branch_id) {
            throw new Exception("رقم الفرع (branch_id) مطلوب");
        }

        // --- أ: البحث المتقدم (Advanced Search) ---
        if ($action === 'search') {
            $term = $_GET['term'] ?? '';
            $searchTerm = "%$term%";

            // البحث في الاسم، كود المنتج، كود الحاوية، ومكان المنتج
            $sql = "SELECT * FROM products 
                    WHERE branch_id = ? 
                    AND active = 1 
                    AND (
                        name LIKE ? 
                        OR product_code LIKE ? 
                        OR container_code LIKE ? 
                        OR product_place LIKE ?
                    )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$branch_id, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = ["status" => 200, "message" => "تم البحث بنجاح", "data" => $products];
        }
        
        // --- ب: الجلب العادي (مع فلترة بسيطة اختيارية) ---
        else {
            // يمكن استخدام الموديول هنا أو استعلام مباشر
            // سنستخدم استعلام مباشر لضمان العمل مع التغييرات الجديدة
            $category_id = $_GET['category_id'] ?? null;
            
            $sql = "SELECT * FROM products WHERE branch_id = ? AND active = 1";
            $params = [$branch_id];

            if ($category_id) {
                $sql .= " AND category_id = ?";
                $params[] = $category_id;
            }

            $sql .= " ORDER BY id DESC"; // الأحدث أولاً

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = ["status" => 200, "message" => "Success", "data" => $products];
        }
    }

    // ==========================================
    // معالجة طلبات POST (إضافة منتج جديد)
    // ==========================================
    elseif ($method === 'POST') {
        // التحقق من الحقول الإلزامية حسب قاعدة البيانات الجديدة
        if (
            !empty($data['name']) && 
            !empty($data['branch_id']) && 
            (!empty($data['selling_price']) || !empty($data['price'])) // قبول price أو selling_price
        ) {
            
            // تجهيز البيانات
            $selling_price = $data['selling_price'] ?? $data['price']; // دعم التسميتين
            
            $sql = "INSERT INTO products (
                branch_id, category_id, name, weight, selling_price, 
                default_unit, productive_capital, product_code, 
                container_code, received_date, product_place, active, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute([
                $data['branch_id'],
                $data['category_id'] ?? null,
                $data['name'],
                $data['weight'] ?? 0,
                $selling_price,
                $data['default_unit'] ?? 'pcs',
                $data['productive_capital'] ?? 0,
                $data['product_code'] ?? null,
                $data['container_code'] ?? null,
                $data['received_date'] ?? date('Y-m-d'),
                $data['product_place'] ?? 'depot',
                1, // active default
                $data['notes'] ?? ''
            ]);

            if ($result) {
                http_response_code(201);
                $response = [
                    "status" => 201, 
                    "message" => "تم إضافة المنتج بنجاح", 
                    "product_id" => $pdo->lastInsertId()
                ];
            } else {
                throw new Exception("فشل تنفيذ الاستعلام في قاعدة البيانات");
            }

        } else {
            http_response_code(400);
            $response = [
                "status" => 400, 
                "message" => "بيانات ناقصة. تأكد من (name, branch_id, selling_price)."
            ];
        }
    } 
    
    // ==========================================
    // طرق غير مسموحة
    // ==========================================
    else {
        http_response_code(405);
        $response = ["status" => 405, "message" => "Method Not Allowed"];
    }

} catch (Exception $e) {
    http_response_code(500);
    $response = ["status" => 500, "message" => "خطأ في النظام: " . $e->getMessage()];
}

// 4. تنظيف وإرسال الرد النظيف
ob_end_clean();
echo json_encode($response);
exit;
?>