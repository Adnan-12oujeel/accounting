<?php
class ReturnModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // دالة لجلب خصم المنتج من الفاتورة الأساسية
    private function getItemDiscountFromOriginal($invoice_id, $product_id) {
        $query = "SELECT discount, unit_price FROM invoice_items 
                  WHERE invoice_id = :invoice_id AND product_id = :product_id 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ":invoice_id" => $invoice_id,
            ":product_id" => $product_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

            // 2. إدخال بنود المرتجع مع جلب الخصم الأصلي
            foreach ($items as $item) {
                // جلب الخصم الأصلي للمنتج من الفاتورة الأساسية
                $originalData = $this->getItemDiscountFromOriginal($return_data['main_invoice_id'], $item['product_id']);
                $originalDiscount = $originalData ? $originalData['discount'] : 0;
                $originalPrice = $originalData ? $originalData['unit_price'] : $item['unit_price'];

                // حساب الصافي للمرتجع بناءً على الخصم الأصلي
                $itemTotal = $item['count'] * $originalPrice;
                $netAmount = $itemTotal - ($originalDiscount * $item['count']);

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
                    ":price" => $originalPrice,
                    ":total" => $itemTotal,
                    ":net" => $netAmount
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