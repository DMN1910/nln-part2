<?php
require_once __DIR__ . '/../config/database.php';

class User
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByEmail($email)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function create($name, $email, $password)
    {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, email, password) VALUES (?, ?, ?)"
        );

        return $stmt->execute([$name, $email, $hashed]);
    }
}
