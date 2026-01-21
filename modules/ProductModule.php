<?php
class ProductModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // إضافة منتج (تستقبل مصفوفة $data)
    public function addProduct($data) {
        $sql = "INSERT INTO products (branch_id, category_id, name, description, price, cost, stock_quantity, alert_quantity, product_code, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            $data['branch_id'], 
            $data['category_id'] ?? null, // يقبل القيمة الفارغة
            $data['name'], 
            $data['description'] ?? '', 
            $data['price'], 
            $data['cost'] ?? 0, 
            $data['stock_quantity'] ?? 0, 
            $data['alert_quantity'] ?? 5, 
            $data['product_code'] ?? null
        ]);
    }

    // جلب المنتجات
    public function getProducts($branch_id, $search = null, $category_id = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.branch_id = ?";
        
        $params = [$branch_id];

        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.product_code LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}