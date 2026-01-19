<?php
class ProductModel {
    private $conn;
    private $table_name = "products";

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. إضافة منتج جديد
    public function create($branch_id, $data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET branch_id=:branch_id, category_id=:category_id, unit_id=:unit_id, 
                      name=:name, product_code=:product_code, container_code=:container_code, 
                      weight=:weight, selling_price=:selling_price, productive_capital=:productive_capital, 
                      product_place=:product_place, received_date=:received_date, notes=:notes, is_active=1";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->bindParam(":category_id", $data['category_id']);
        $stmt->bindParam(":unit_id", $data['unit_id']);
        $stmt->bindParam(":name", $data['name']);
        $stmt->bindParam(":product_code", $data['product_code']);
        $stmt->bindParam(":container_code", $data['container_code']);
        $stmt->bindParam(":weight", $data['weight']);
        $stmt->bindParam(":selling_price", $data['selling_price']);
        $stmt->bindParam(":productive_capital", $data['productive_capital']);
        $stmt->bindParam(":product_place", $data['product_place']); // depot or fair
        $stmt->bindParam(":received_date", $data['received_date']);
        $stmt->bindParam(":notes", $data['notes']);

        return $stmt->execute();
    }

    // 2. البحث والفرز المتقدم (Multi-column Search)
    public function searchProducts($branch_id, $filters = []) {
        // الربط مع جدول الأصناف والوحدات لجلب الأسماء بدلاً من الـ ID
        $sql = "SELECT p.*, c.name as category_name, u.unit_name 
                FROM " . $this->table_name . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN units u ON p.unit_id = u.id 
                WHERE p.branch_id = :branch_id AND p.is_active = 1";

        // إضافة شروط البحث بناءً على الحقول الممررة من الفرونت إند
        foreach ($filters as $column => $value) {
            if (!empty($value)) {
                $sql .= " AND p.$column LIKE :$column";
            }
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);

        foreach ($filters as $column => $value) {
            if (!empty($value)) {
                $val = "%$value%";
                $stmt->bindParam(":$column", $val);
            }
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. جلب القيم الفريدة لأي عمود (لتعبئة القوائم المنسدلة في الفرز)
    public function getDistinctValues($column, $branch_id) {
        $query = "SELECT DISTINCT $column FROM " . $this->table_name . " 
                  WHERE branch_id = :branch_id AND $column IS NOT NULL AND $column != ''";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}