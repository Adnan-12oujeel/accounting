<?php
class BranchModule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addBranch($data) {
        $sql = "INSERT INTO branches (name, address, phone) VALUES (?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$data['name'], $data['address'], $data['phone']]);
    }

    // جلب جميع الفروع المسجلة
    public function getAllBranches() {
        $stmt = $this->pdo->query("SELECT * FROM branches");
        return $stmt->fetchAll();
    }
}