<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/actions.php';

$action = $_GET['action'] ?? '';
$branch_id = $_GET['branch_id'] ?? null;

if (!$branch_id) {
    echo json_encode(["status" => "error", "message" => "Branch ID is required"]);
    exit;
}

if ($action == 'list') {
    try {
        $logs = getActionsByBranch($branch_id);
        echo json_encode(["status" => "success", "data" => $logs]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}