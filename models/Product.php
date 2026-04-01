<?php
require_once __DIR__ . '/../config/database.php';

class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        $sql = "
            SELECT p.*, b.name AS brand, c.name AS category
            FROM products p
            JOIN brands b ON p.brand_id = b.id
            JOIN categories c ON p.category_id = c.id
            ORDER BY p.id DESC
        ";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO products (name, brand_id, category_id, description)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([
            $data['name'],
            $data['brand_id'],
            $data['category_id'],
            $data['description']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
