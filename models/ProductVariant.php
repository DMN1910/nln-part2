<?php
require_once __DIR__ . '/../config/database.php';

class ProductVariant {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function create($product_id, $variant) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO product_variants
            (product_id, `condition`, cost_price, sell_price, stock)
            VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $product_id,
            $variant['condition'],
            $variant['cost_price'],
            $variant['sell_price'],
            $variant['stock']
        ]);
    }

    public function byProduct($product_id) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM product_variants WHERE product_id = ?"
        );
        $stmt->execute([$product_id]);
        return $stmt->fetchAll();
    }
}
