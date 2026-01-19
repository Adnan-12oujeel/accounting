<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/installments.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;
$action = $_GET['action'] ?? '';

if ($action == 'add') {
    try {
        $id = addInstallment(
            $data['invoice_id'],
            $data['date'],
            $data['paid_amount'],
            $data['payment_method'],
            $data['invoice_creator']
        );
        echo json_encode(["status" => "success", "message" => "Installment added", "id" => $id]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}