<?php
require_once __DIR__ . '/../config/database.php';

class Category
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function all()
    {
        return $this->pdo->query("SELECT * FROM categories")->fetchAll();
    }
}
