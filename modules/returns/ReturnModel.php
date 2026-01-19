<?php
class ReturnModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // إنشاء فاتورة مرتجع مع بنودها
    public function createReturn($return_data, $items) {
        try {
            $this->conn->beginTransaction();

            // 1. إدخال رأس فاتورة المرتجع
            $query = "INSERT INTO invoice_sales_returns 
                      SET main_invoice_id=:main_id, condition_of_goods=:condition, 
                          total_value_of_returns=:total, refund_method=:method, 
                          invoice_creator=:creator";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ":main_id" => $return_data['main_invoice_id'],
                ":condition" => $return_data['condition_of_goods'],
                ":total" => $return_data['total_value_of_returns'],
                ":method" => $return_data['refund_method'],
                ":creator" => $return_data['invoice_creator']
            ]);
            
            $return_id = $this->conn->lastInsertId();

            // 2. إدخال بنود المرتجع
            foreach ($items as $item) {
                $item_query = "INSERT INTO invoice_return_items 
                               SET return_invoice_id=:ret_id, product_id=:prod_id, 
                                   unit=:unit, count=:count, unit_price=:price, 
                                   total=:total, net_amount=:net";
                
                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->execute([
                    ":ret_id" => $return_id,
                    ":prod_id" => $item['product_id'],
                    ":unit" => $item['unit'],
                    ":count" => $item['count'],
                    ":price" => $item['unit_price'],
                    ":total" => $item['total'],
                    ":net" => $item['net_amount']
                ]);
            }

            $this->conn->commit();
            return $return_id;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }
}