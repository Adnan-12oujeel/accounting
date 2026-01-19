<?php
class InvoiceModel {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. إنشاء فاتورة جديدة (مبيع/شراء/مصروف)
    public function createInvoice($branch_id, $invoice_data, $items) {
        try {
            $this->conn->beginTransaction(); // بدء العملية لضمان سلامة البيانات

            // إدخال رأس الفاتورة في جدول invoices
            $query = "INSERT INTO invoices 
                      SET branch_id=:branch_id, invoice_type=:type, date=:date, 
                          customer_id=:customer_id, payment_method=:method, 
                          invoice_status=:status, total_amount=:total, 
                          discount=:discount, net_amount=:net, notes=:notes";
            
            $stmt = $this->conn->prepare($query);
            
            $net_amount = $invoice_data['total'] - $invoice_data['discount'];

            $stmt->bindParam(":branch_id", $branch_id);
            $stmt->bindParam(":type", $invoice_data['invoice_type']);
            $stmt->bindParam(":date", $invoice_data['date']);
            $stmt->bindParam(":customer_id", $invoice_data['customer_id']);
            $stmt->bindParam(":method", $invoice_data['payment_method']);
            $stmt->bindParam(":status", $invoice_data['invoice_status']);
            $stmt->bindParam(":total", $invoice_data['total']);
            $stmt->bindParam(":discount", $invoice_data['discount']);
            $stmt->bindParam(":net", $net_amount);
            $stmt->bindParam(":notes", $invoice_data['notes']);
            
            $stmt->execute();
            $invoice_id = $this->conn->lastInsertId();

            // إدخال بنود الفاتورة في جدول invoice_items
            foreach ($items as $item) {
                $item_query = "INSERT INTO invoice_items 
                               SET invoice_id=:inv_id, product_id=:prod_id, unit=:unit, 
                                   count=:count, unit_price=:price, total=:total, 
                                   discount=:discount, net_amount=:net";
                
                $item_stmt = $this->conn->prepare($item_query);
                $item_stmt->execute([
                    ":inv_id" => $invoice_id,
                    ":prod_id" => $item['product_id'],
                    ":unit" => $item['unit'],
                    ":count" => $item['count'],
                    ":price" => $item['unit_price'],
                    ":total" => $item['total'],
                    ":discount" => $item['discount'],
                    ":net" => $item['net_amount']
                ]);
            }

            $this->conn->commit();
            return $invoice_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            return false;
        }
    }

    // 2. جلب بيانات فاتورة مع تفاصيل العميل (لتنسيق رأس الفاتورة)
    public function getFullInvoice($invoice_id) {
        $query = "SELECT i.*, c.company, c.mobile, c.address 
                  FROM invoices i 
                  LEFT JOIN customers c ON i.customer_id = c.id 
                  WHERE i.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $invoice_id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}