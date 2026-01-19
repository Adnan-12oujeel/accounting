<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/products.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;

$action = $_GET['action'] ?? '';

if ($action == 'add') {
    if (!isset($data['branch_id'], $data['name'], $data['selling_price'], $data['productive_capital'])) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit;
    }

    try {
        $id = addProduct($data);
        echo json_encode(["status" => "success", "message" => "Product added", "id" => $id]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}

elseif ($action == 'list') {
    $branch_id = $_GET['branch_id'] ?? null;
    if (!$branch_id) {
        echo json_encode(["status" => "error", "message" => "Branch ID is required"]);
        exit;
    }
    $products = getProductsByBranch($branch_id);
    echo json_encode(["status" => "success", "data" => $products]);
    exit;
}

if ($action == 'search') {
    $branch_id = $_GET['branch_id'] ?? null;
    
    if (!$branch_id) {
        echo json_encode(["status" => "error", "message" => "Branch ID is required"]);
        exit;
    }

    // استلام الفلاتر من الطلب (مثلاً: name, product_code, product_place)
    $filters = [
        'name'           => $data['name'] ?? null,
        'product_code'   => $data['product_code'] ?? null,
        'product_place'  => $data['product_place'] ?? null,
        'category_id'    => $data['category_id'] ?? null
    ];

    try {
        // تنظيف المصفوفة من القيم الفارغة قبل الإرسال للدالة
        $activeFilters = array_filter($filters, fn($value) => !is_null($value) && $value !== '');
        
        $results = searchProducts($branch_id, $activeFilters);
        echo json_encode(["status" => "success", "count" => count($results), "data" => $results]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}