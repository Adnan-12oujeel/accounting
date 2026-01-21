<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

$branch_id = $_GET['branch_id'] ?? 1; // الافتراضي 1
$action = $_GET['action'] ?? '';

if ($action == 'distinct_values') {
    // جلب قيم فريدة لعمود معين (مثل product_place أو unit)
    $table = $_GET['table'] ?? 'products';
    $column = $_GET['column'] ?? 'product_place';
    
    // قائمة الجداول والأعمدة المسموح بها (حماية أمنية)
    $allowed = [
        'products' => ['product_place', 'default_unit', 'container_code'],
        'customers' => ['city', 'customer_type'],
        'system_settings' => ['value'] // للقوائم العامة
    ];
    
    if (isset($allowed[$table]) && in_array($column, $allowed[$table])) {
        $sql = "SELECT DISTINCT $column FROM $table WHERE branch_id = ? AND active = 1 AND $column IS NOT NULL AND $column != ''";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        
        echo json_encode(["status" => 200, "data" => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
    } else {
        echo json_encode(["status" => 403, "message" => "غير مسموح الوصول لهذا العمود"]);
    }
}