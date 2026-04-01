<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/ProductVariant.php';

class ProductController
{
    private $product;
    private $variant;

    public function __construct($pdo)
    {
        $this->product = new Product($pdo);
        $this->variant = new ProductVariant($pdo);
    }

    public function store($data, $variants)
    {
        $product_id = $this->product->create($data);

        foreach ($variants as $v) {
            $this->variant->create($product_id, $v);
        }
        return $product_id;
    }
}
