<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/returns.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$action = $_GET['action'] ?? '';

if ($action == 'create') {
    if (!isset($inputData['header']) || !isset($inputData['items'])) {
        echo json_encode(["status" => "error", "message" => "Return header or items missing"]);
        exit;
    }

    try {
        $returnId = createReturnInvoice($inputData['header'], $inputData['items']);
        echo json_encode(["status" => "success", "message" => "Return Invoice #$returnId created successfully"]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}