<?php
// 1. إعدادات الوصول (CORS) واستجابة JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// التعامل مع طلبات OPTIONS (Pre-flight) للمتصفحات
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. تضمين جميع الملفات الأساسية والموديلات
require_once 'config/database.php';
require_once 'core/SystemServices.php';
require_once 'modules/branches/BranchModel.php';
require_once 'modules/customers/CustomerModel.php';
require_once 'modules/products/ProductModel.php';
require_once 'modules/invoices/InvoiceModel.php';
require_once 'modules/installments/InstallmentModel.php';
require_once 'modules/returns/ReturnModel.php';
require_once 'modules/users/UserModel.php';
require_once 'modules/actions/ActionModel.php';

// 3. تهيئة الاتصال والموديلات
$database = new Database();
$db = $database->getConnection();

$branchModel      = new BranchModel($db);
$customerModel    = new CustomerModel($db);
$productModel     = new ProductModel($db);
$invoiceModel     = new InvoiceModel($db);
$installmentModel = new InstallmentModel($db);
$returnModel      = new ReturnModel($db);
$userModel        = new UserModel($db);
$actionModel      = new ActionModel($db);
$systemServices   = new SystemServices($db);

// 4. تحديد الموديول والأكشن من الرابط
$module = $_GET['module'] ?? '';
$action = $_GET['action'] ?? '';

// استقبال بيانات JSON من الـ Body
$inputData = json_decode(file_get_contents("php://input"), true);

// 5. نظام التوجيه (Routing System)
switch ($module) {
    case 'branches':
        if ($action == 'list') echo json_encode($branchModel->getActiveBranches());
        if ($action == 'archived') echo json_encode($branchModel->getArchivedBranches());
        if ($action == 'create') echo json_encode(["success" => $branchModel->create($inputData)]);
        if ($action == 'toggle') echo json_encode(["success" => $branchModel->toggleStatus($inputData['id'], $inputData['status'])]);
        break;

    case 'customers':
        $branch_id = $_GET['branch_id'] ?? 0;
        if ($action == 'list') echo json_encode($customerModel->getActive($branch_id));
        if ($action == 'archived') echo json_encode($customerModel->getArchived($branch_id));
        if ($action == 'create') echo json_encode(["success" => $customerModel->create($branch_id, $inputData)]);
        if ($action == 'archive') echo json_encode(["success" => $customerModel->archiveCustomer($inputData['id'])]);
        break;

    case 'products':
        $branch_id = $_GET['branch_id'] ?? 0;
        if ($action == 'search') echo json_encode($productModel->searchProducts($branch_id, $_POST));
        if ($action == 'create') echo json_encode(["success" => $productModel->create($branch_id, $inputData)]);
        if ($action == 'distinct_values') echo json_encode($productModel->getDistinctValues($_GET['column'], $branch_id));
        break;

    case 'invoices':
        if ($action == 'create') {
            $result = $invoiceModel->createInvoice($inputData['branch_id'], $inputData['invoice'], $inputData['items']);
            if($result) $systemServices->notifyUsersOfInvoice($inputData['branch_id'], $inputData['invoice']);
            echo json_encode(["success" => (bool)$result, "invoice_id" => $result]);
        }
        if ($action == 'get') echo json_encode($invoiceModel->getFullInvoice($_GET['id']));
        break;

    case 'installments':
        if ($action == 'add') echo json_encode(["success" => $installmentModel->addInstallment($inputData)]);
        if ($action == 'list') echo json_encode($installmentModel->getInvoiceInstallments($_GET['invoice_id']));
        break;

    case 'returns':
        if ($action == 'create') {
            $result = $returnModel->createReturn($inputData['return_info'], $inputData['items']);
            if ($result) {
                $actionModel->logAction($inputData['branch_id'], $inputData['user_id'], 'add', null, "مرتجع للفاتورة: " . $inputData['return_info']['main_invoice_id']);
            }
            echo json_encode(["success" => (bool)$result, "return_id" => $result]);
        }
        break;

    case 'users':
        if ($action == 'login') echo json_encode($userModel->login($inputData['email'], $inputData['password']));
        if ($action == 'create') echo json_encode(["success" => $userModel->create($inputData)]);
        break;

    case 'actions':
        if ($action == 'list') echo json_encode($actionModel->getLogsByBranch($_GET['branch_id']));
        break;

    case 'backup':
        // تحديث البيانات بناءً على استضافة Hostinger
        $db_config = ['host'=>'localhost', 'user'=>'root', 'pass'=>'', 'name'=>'accounting'];
        echo json_encode(["success" => $systemServices->generateBackup($db_config)]);
        break;

    default:
        http_response_code(404);
        echo json_encode(["message" => "الموديول أو الأكشن غير موجود"]);
        break;
}
=======
header("location: api/");
>>>>>>> 7a8dfef0717481d879ca2de28acc64de62e514e7
