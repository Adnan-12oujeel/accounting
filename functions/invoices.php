<?php
require_once __DIR__ . '/../config/db.php';

function createInvoice($header, $items) {
    global $conn;
    try {
        $conn->beginTransaction();

        $totalProductiveCapital = 0;
        $needsApproval = false;

        // 1. مراجعة البنود قبل الإدخال لحساب التكلفة والتحقق من السعر
        foreach ($items as $item) {
            $stmtProd = $conn->prepare("SELECT selling_price, productive_capital, stock_quantity FROM products WHERE id = :id");
            $stmtProd->execute([':id' => $item['product_id']]);
            $product = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // حساب إجمالي رأس المال للمنتجات في هذه الفاتورة
                $totalProductiveCapital += ($product['productive_capital'] * $item['count']);

                // التحقق إذا كان السعر المدخل يدوياً يختلف عن سعر البيع الافتراضي
                if (round($item['unit_price'], 2) != round($product['selling_price'], 2)) {
                    $needsApproval = true;
                }
            }
        }

        // 2. تحديد حالة الفاتورة وحساب الربح الصافي
        // إذا كان السعر يدوياً، نغير الحالة إلى Pending
        $currentStatus = $needsApproval ? 'Pending' : $header['invoice_status'];
        
        // الربح الصافي = (صافي قيمة الفاتورة) - (إجمالي رأس مال المنتجات)
        $netProfit = $header['net_amount'] - $totalProductiveCapital;

        // 3. إدخال رأس الفاتورة مع حقل الربح الجديد
        $sqlHeader = "INSERT INTO invoices (branch_id, invoice_type, date, customer_id, payment_method, invoice_status, total_amount, discount, net_amount, net_profit, notes) 
                      VALUES (:branch_id, :type, :date, :customer_id, :payment_method, :status, :total, :discount, :net, :net_profit, :notes)";
        
        $stmtHeader = $conn->prepare($sqlHeader);
        $stmtHeader->execute([
            ':branch_id'      => $header['branch_id'],
            ':type'           => $header['invoice_type'],
            ':date'           => $header['date'],
            ':customer_id'    => $header['customer_id'],
            ':payment_method' => $header['payment_method'],
            ':status'         => $currentStatus,
            ':total'          => $header['total_amount'],
            ':discount'       => $header['discount'],
            ':net'            => $header['net_amount'],
            ':net_profit'     => $netProfit,
            ':notes'          => $header['notes']
        ]);
        
        $invoiceId = $conn->lastInsertId();

        // 4. إدخال بند في جدول الموافقات إذا لزم الأمر
        if ($needsApproval) {
            $sqlApproval = "INSERT INTO price_approvals (invoice_id, status) VALUES (:inv_id, 'pending')";
            $conn->prepare($sqlApproval)->execute([':inv_id' => $invoiceId]);
        }

        // 5. إدخال بنود الفاتورة وتحديث المخزن
        $sqlItem = "INSERT INTO invoice_items (invoice_id, product_id, unit, count, unit_price, total, discount, net_amount, notes) 
                    VALUES (:inv_id, :prod_id, :unit, :count, :price, :total, :discount, :net, :notes)";
        $stmtItem = $conn->prepare($sqlItem);

        foreach ($items as $item) {
            $stmtItem->execute([
                ':inv_id'   => $invoiceId,
                ':prod_id'  => $item['product_id'],
                ':unit'     => $item['unit'],
                ':count'    => $item['count'],
                ':price'    => $item['unit_price'],
                ':total'    => $item['total'],
                ':discount' => $item['discount'],
                ':net'      => $item['net_amount'],
                ':notes'    => $item['notes'] ?? null
            ]);

            if (!$needsApproval) {
                $updateStock = "UPDATE products SET stock_quantity = stock_quantity - :qty WHERE id = :id";
                $conn->prepare($updateStock)->execute([':qty' => $item['count'], ':id' => $item['product_id']]);
            }
        }

        $conn->commit();
        return ['id' => $invoiceId, 'status' => $currentStatus, 'profit' => $netProfit];

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}