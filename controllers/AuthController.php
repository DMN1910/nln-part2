<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/config.php';

class AuthController
{
    private $userModel;

    public function __construct($pdo)
    {
        $this->userModel = new User($pdo);
    }

    public function register($name, $email, $password)
    {
        if ($this->userModel->findByEmail($email)) {
            return "Email đã tồn tại";
        }

        $this->userModel->create($name, $email, $password);
        return true;
    }

    public function login($email, $password)
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return "Email hoặc mật khẩu không đúng";
        }

        $_SESSION['user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role']
        ];

        return true;
    }
}
