<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../functions/users.php';

$inputData = json_decode(file_get_contents("php://input"), true);
$data = $inputData ?? $_POST;
$action = $_GET['action'] ?? '';

if ($action == 'update_self') {
    $id = $data['id'] ?? null;
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$id || !$email) {
        echo json_encode(["status" => "error", "message" => "ID and Email are required"]);
        exit;
    }

    try {
        updateCredentials('users', $id, $email, $password);
        echo json_encode(["status" => "success", "message" => "Your profile updated"]);
        exit;
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
}