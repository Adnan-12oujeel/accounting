<?php
header("Access-Control-Allow-Origin: *"); // اسمح لأي دومين بالاتصال
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // السماح بهذه العمليات
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// معالجة طلبات Preflight (التي يرسلها المتصفح قبل الـ POST)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../modules/ActionModule.php';

$actionMod = new ActionModule($pdo);
$method = $_SERVER['REQUEST_METHOD'];
$branch_id = $_GET['branch_id'] ?? null;

if (!$branch_id) sendResponse(400, "Branch ID required");

if ($method === 'GET') {
    $logs = $actionMod->getLogs($branch_id);
    sendResponse(200, "Success", $logs);
}