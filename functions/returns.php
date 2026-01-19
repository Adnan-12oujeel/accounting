<?php
require_once __DIR__ . '/../config/db.php';

function createReturnInvoice($header, $items) {
    global $conn;
    try {
        $conn->beginTransaction();

        $mainInvoiceId = $header['main_invoice_id'];
        $totalCalculatedReturn = 0;

        $sqlCheck = "SELECT invoice_status, net_amount FROM invoices WHERE id = :main_id";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->execute([':main_id' => $mainInvoiceId]);
        $originalInvoice = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$originalInvoice) {
            throw new Exception("الفاتورة الأصلية غير موجودة.");
        }

        // 2. إدخال بنود المرتجع مع التحقق من السعر الأصلي بعد الخصم
        $sqlItem = "INSERT INTO invoice_return_items (return_invoice_id, product_id, unit, count, unit_price, total, discount, net_amount, notes) 
                    VALUES (:ret_id, :prod_id, :unit, :count, :price, :total, :discount, :net, :notes)";
        $stmtItem = $conn->prepare($sqlItem);

        // سنقوم أولاً بإنشاء رأس الفاتورة بقيمة 0 ثم تحديثها لاحقاً بالمجموع الحقيقي
        $sqlHeader = "INSERT INTO invoice_sales_returns (main_invoice_id, condition_of_goods, total_value_of_returns, invoice_creator, refund_method) 
                      VALUES (:main_inv_id, :condition, 0, :creator, :method)";
        
        // تحديد طريقة الاسترداد بناءً على حالة الفاتورة
        $refundMethod = ($originalInvoice['invoice_status'] == 'Installments') ? 'installment_deduction' : 'cash';

        $stmtHeader = $conn->prepare($sqlHeader);
        $stmtHeader->execute([
            ':main_inv_id' => $mainInvoiceId,
            ':condition'   => $header['condition_of_goods'],
            ':creator'     => $header['invoice_creator'],
            ':method'      => $refundMethod
        ]);
        
        $returnId = $conn->lastInsertId();

        foreach ($items as $item) {
            // استخراج السعر النهائي للقطعة من الفاتورة الأصلية (بعد الخصم)
            $sqlOrigItem = "SELECT net_amount, count FROM invoice_items WHERE invoice_id = :inv_id AND product_id = :prod_id";
            $stmtOrigItem = $conn->prepare($sqlOrigItem);
            $stmtOrigItem->execute([':inv_id' => $mainInvoiceId, ':prod_id' => $item['product_id']]);
            $origItem = $stmtOrigItem->fetch(PDO::FETCH_ASSOC);

            if (!$origItem) {
                throw new Exception("المنتج المرتجع غير موجود في الفاتورة الأصلية.");
            }

            // السعر الفعلي الذي دفعه الزبون للقطعة الواحدة = صافي القيمة / الكمية
            $actualUnitPrice = $origItem['net_amount'] / $origItem['count'];
            $itemTotalReturn = $actualUnitPrice * $item['count'];
            $totalCalculatedReturn += $itemTotalReturn;

            $stmtItem->execute([
                ':ret_id'   => $returnId,
                ':prod_id'  => $item['product_id'],
                ':unit'     => $item['unit'],
                ':count'    => $item['count'],
                ':price'    => $actualUnitPrice, // السعر الإجباري من الفاتورة الأصلية
                ':total'    => $itemTotalReturn,
                ':discount' => 0, // الخصم تم حسابه مسبقاً في السعر
                ':net'      => $itemTotalReturn,
                ':notes'    => $item['notes'] ?? null
            ]);

            // تحديث كمية المخزن (إعادة المنتج للمخزن)
            $sqlStock = "UPDATE products SET stock_quantity = stock_quantity + :qty WHERE id = :prod_id";
            $stmtStock = $conn->prepare($sqlStock);
            $stmtStock->execute([':qty' => $item['count'], ':prod_id' => $item['product_id']]);
        }

        // 3. تحديث إجمالي قيمة المرتجع في رأس الفاتورة
        $sqlUpdateHeader = "UPDATE invoice_sales_returns SET total_value_of_returns = :total WHERE id = :ret_id";
        $conn->prepare($sqlUpdateHeader)->execute([':total' => $totalCalculatedReturn, ':ret_id' => $returnId]);

        // 4. تسوية المبالغ مالياً (خصم من الأقساط أو إثبات خروج نقدية)
        if ($refundMethod == 'installment_deduction') {
            // ملاحظة: هنا يجب أن يكون لديك حقل 'remaining_debt' أو ما يعادله في جدول الأقساط لتخفيضه
            // إذا لم يتوفر الحقل، يمكن إضافة سجل مدين في جدول الحسابات للزبون
            $sqlInstallment = "UPDATE invoices SET net_amount = net_amount - :refund WHERE id = :inv_id";
            $conn->prepare($sqlInstallment)->execute([':refund' => $totalCalculatedReturn, ':inv_id' => $mainInvoiceId]);
        }

        $conn->commit();
        return $returnId;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}