<?php
class ReturnModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // إنشاء فاتورة مرتجع
    public function createReturn($branch_id, $header, $items) {
        try {
            $this->pdo->beginTransaction();

            // 1. رأس فاتورة المرتجع
            $sqlH = "INSERT INTO invoice_sales_returns (branch_id, main_invoice_id, main_invoice_date, condition_of_goods, total_value_of_returns, invoice_creator) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmtH = $this->pdo->prepare($sqlH);
            $stmtH->execute([
                $branch_id, $header['main_invoice_id'], $header['main_invoice_date'],
                $header['condition_of_goods'], $header['total_value_of_returns'], $header['invoice_creator']
            ]);
            $return_id = $this->pdo->lastInsertId();

            // 2. بنود المرتجع (الخصم للقراءة فقط من الفاتورة الأصلية)
            $sqlI = "INSERT INTO invoice_return_items (return_invoice_id, product_id, unit, count, unit_price, total, discount, net_amount, notes) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtI = $this->pdo->prepare($sqlI);

            foreach ($items as $item) {
                $stmtI->execute([
                    $return_id, $item['product_id'], $item['unit'], $item['count'],
                    $item['unit_price'], $item['total'], $item['original_discount'], $item['net_amount'], $item['notes']
                ]);
            }

            $this->pdo->commit();
            return $return_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }
}