<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/categories.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;

$action = $_GET['action'] ?? '';

if ($action == 'add') {
    $branch_id = $data['branch_id'] ?? null;
    $name = $data['name'] ?? null;

    if (!$branch_id || !$name) {
        echo json_encode(["status" => "error", "message" => "Branch ID and Category Name are required"]);
        exit;
    }

    try {
        $id = addCategory($branch_id, $name, $data['country_of_origin'] ?? null);
        echo json_encode(["status" => "success", "message" => "Category added", "id" => $id]);
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
    $categories = getCategoriesByBranch($branch_id);
    echo json_encode(["status" => "success", "data" => $categories]);
    exit;
}   