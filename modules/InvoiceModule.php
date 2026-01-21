<?php

require_once __DIR__ . '/../includes/Logger.php';

class InvoiceModule {
    
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * إنشاء فاتورة جديدة (مبيعات، مشتريات، مرتجعات)
     * مع تحديث المخزون ورصيد العميل
     */
    public function createInvoice($data) {
        try {
            $this->pdo->beginTransaction();

            $type = $data['invoice_type'];
            $branch_id = $data['branch_id'];
            $user_id = $data['user_id'] ?? null; // المستخدم الذي أنشأ الفاتورة

            // 1. تحديد عامل التأثير على المخزون (زيادة أم نقص)
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

            // 2. حفظ رأس الفاتورة (Invoices Table)
            $sql = "INSERT INTO invoices (
                branch_id, invoice_type, date, customer_id, origin_invoice_id, payment_method, 
                invoice_status, total_amount, discount, net_amount, notes, creator_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $branch_id,
                $type,
                date('Y-m-d H:i:s'),
                $data['customer_id'] ?? null,
                $data['origin_invoice_id'] ?? null, // للفواتير المرتجعة
                $data['payment_method'] ?? 'Cash',
                $data['invoice_status'] ?? 'Paid',
                $data['total_amount'],
                $data['discount'] ?? 0,
                $data['net_amount'],
                $data['notes'] ?? '',
                $user_id
            ]);
            
            $invoice_id = $this->pdo->lastInsertId();

            // 3. حفظ البنود وتحديث المخزون (Invoice Items)
            $sql_items = "INSERT INTO invoice_items (
                invoice_id, product_id, unit, count, unit_price, 
                discount, total, net_amount, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt_item = $this->pdo->prepare($sql_items);
            
            // تحضير استعلام تحديث المخزون
            // نستخدم stock_qty الذي أضفناه مؤخراً
            $operator = ($stock_factor >= 0) ? '+' : '-';
            $sql_stock = "UPDATE products SET stock_qty = stock_qty $operator ? WHERE id = ?";
            $stmt_stock = $this->pdo->prepare($sql_stock);

            foreach ($data['items'] as $item) {
                // أ) إدراج البند
                $stmt_item->execute([
                    $invoice_id,
                    $item['product_id'],
                    $item['unit'] ?? 'pcs',     // اسم الوحدة (string) أو رقمها حسب تصميمك
                    $item['count'],             // الكمية
                    $item['unit_price'],
                    $item['discount'] ?? 0,
                    $item['total'],             // السعر * الكمية
                    $item['net_amount'],        // الصافي
                    $item['notes'] ?? ''
                ]);

                // ب) تحديث المخزون (إلا إذا كانت فاتورة خدمات/مصاريف)
                if ($type !== 'expense_invoice' && $stock_factor != 0) {
                    $stmt_stock->execute([$item['count'], $item['product_id']]);
                }
            }

            // 4. تحديث رصيد العميل (Customer Balance)
            // إذا كانت الفاتورة آجلة (Unpaid) أو جزئية (Installments)، يجب تسجيل الدين
            // مبيعات = زيادة الدين (+)
            // مرتجع مبيعات = إنقاص الدين (-)
            if (!empty($data['customer_id'])) {
                $balance_change = 0;
                
                // حساب المبلغ المتبقي (غير المدفوع)
                $paid_amount = $data['paid_amount'] ?? 0; // المبلغ المدفوع فوراً
                $remaining = $data['net_amount'] - $paid_amount;

                if ($remaining > 0) {
                    if ($type == 'sales_invoice') {
                        $balance_change = $remaining; // عليه دين لنا
                    } elseif ($type == 'sales_return_invoice') {
                        $balance_change = -1 * $data['net_amount']; // ننقص الدين (أو نرجعه رصيد له)
                    } elseif ($type == 'bought_invoice') {
                        $balance_change = -1 * $remaining; // علينا دين للمورد
                    }
                }

                if ($balance_change != 0) {
                    // نفترض وجود عمود balance في جدول العملاء
                    // إذا لم يكن موجوداً، يمكنك إضافته: ALTER TABLE customers ADD COLUMN balance DECIMAL(10,2) DEFAULT 0;
                    $upd_cust = $this->pdo->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
                    $upd_cust->execute([$balance_change, $data['customer_id']]);
                }
            }

            // 5. تسجيل العملية في السجل
            Logger::log($this->pdo, $branch_id, $user_id, 'add', 'invoices', $invoice_id, "إضافة فاتورة نوع: $type");

            $this->pdo->commit();
            return $invoice_id;

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // إعادة رمي الخطأ ليظهر في الـ API
            throw $e;
        }
    }
}
?>