<?php
class ReportModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // 1. تقرير جرد المخزون (الكميات المتاحة)
    public function getInventoryReport($branch_id) {
        $sql = "SELECT p.id, p.name, p.product_code, p.product_place,
                (SELECT SUM(count) FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.id WHERE ii.product_id = p.id AND i.invoice_type = 'bought_invoice') as total_bought,
                (SELECT SUM(count) FROM invoice_items ii JOIN invoices i ON ii.invoice_id = i.id WHERE ii.product_id = p.id AND i.invoice_type = 'sales_invoice') as total_sold
                FROM products p
                WHERE p.branch_id = ? AND p.is_active = 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branch_id]);
        return $stmt->fetchAll();
    }

    // 2. كشف حساب عميل (إجمالي المطلوبات والمدفوعات)
    public function getCustomerStatement($branch_id, $customer_id) {
        // مجموع الفواتير
        $sqlInvoices = "SELECT SUM(net_amount) as total_invoices FROM invoices WHERE branch_id = ? AND customer_id = ?";
        $stmt1 = $this->pdo->prepare($sqlInvoices);
        $stmt1->execute([$branch_id, $customer_id]);
        $totalInvoices = $stmt1->fetchColumn() ?: 0;

        // مجموع الدفعات
        $sqlPayments = "SELECT SUM(paid_amount) as total_paid FROM installments WHERE invoice_id IN (SELECT id FROM invoices WHERE customer_id = ?)";
        $stmt2 = $this->pdo->prepare($sqlPayments);
        $stmt2->execute([$customer_id]);
        $totalPaid = $stmt2->fetchColumn() ?: 0;

        return [
            'total_amount' => $totalInvoices,
            'paid_amount'  => $totalPaid,
            'remaining'    => $totalInvoices - $totalPaid
        ];
    }

    // 3. الملخص المالي للفرع (الأرباح والمبيعات)
    public function getFinancialSummary($branch_id, $start_date, $end_date) {
        $sql = "SELECT 
                SUM(CASE WHEN invoice_type = 'sales_invoice' THEN net_amount ELSE 0 END) as total_sales,
                SUM(CASE WHEN invoice_type = 'bought_invoice' THEN net_amount ELSE 0 END) as total_purchases,
                SUM(CASE WHEN invoice_type = 'expense_invoice' THEN net_amount ELSE 0 END) as total_expenses
                FROM invoices 
                WHERE branch_id = ? AND date BETWEEN ? AND ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$branch_id, $start_date, $end_date]);
        return $stmt->fetch();
    }
}