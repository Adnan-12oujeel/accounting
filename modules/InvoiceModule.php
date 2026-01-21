<?php
class InvoiceModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createInvoice($data) {
        try {
            $this->pdo->beginTransaction();

            $type = $data['invoice_type'];

            // 1. تحديد التأثير على المخزون (زيادة أم نقص)
            // sales_invoice: نقص (-1)
            // bought_invoice: زيادة (+1)
            // sales_return_invoice: زيادة (+1) -> العميل رجع بضاعة
            // bought_return_invoice: نقص (-1) -> رجعنا بضاعة للمورد
            
            $stock_factor = 0;
            if ($type == 'sales_invoice' || $type == 'bought_return_invoice') {
                $stock_factor = -1; 
            } elseif ($type == 'bought_invoice' || $type == 'sales_return_invoice') {
                $stock_factor = 1;
            }

            // 2. حفظ رأس الفاتورة
            $sql = "INSERT INTO invoices (
                branch_id, invoice_type, date, customer_id, payment_method_id, 
                invoice_status, total_amount, discount, net_amount, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['branch_id'],
                $type,
                date('Y-m-d H:i:s'),
                $data['customer_id'] ?? null,
                $data['payment_method_id'] ?? null,
                $data['invoice_status'] ?? 'Paid',
                $data['total_amount'],
                $data['discount'],
                $data['net_amount'],
                $data['notes'] ?? ''
            ]);
            $invoice_id = $this->pdo->lastInsertId();

            // 3. حفظ البنود وتحديث المخزون
            $sql_items = "INSERT INTO invoice_details (
                invoice_id, product_id, unit_id, quantity, price, 
                item_discount, total, net_amount, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_item = $this->pdo->prepare($sql_items);
            
            // استعلام تحديث المخزون (ديناميكي حسب العامل + أو -)
            $operator = ($stock_factor >= 0) ? '+' : '-';
            $stmt_stock = $this->pdo->prepare("UPDATE products SET stock_quantity = stock_quantity $operator ? WHERE id = ?");

            foreach ($data['items'] as $item) {
                $stmt_item->execute([
                    $invoice_id,
                    $item['product_id'],
                    $item['unit_id'],
                    $item['count'],
                    $item['unit_price'],
                    $item['discount'],
                    $item['total'],
                    $item['net_amount'],
                    $item['notes'] ?? ''
                ]);

                // تحديث المخزون فقط إذا لم تكن فاتورة مصروفات
                if ($type !== 'expense_invoice') {
                    $stmt_stock->execute([$item['count'], $item['product_id']]);
                }
            }

            $this->pdo->commit();
            return $invoice_id;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}