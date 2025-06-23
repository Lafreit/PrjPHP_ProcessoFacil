<?php
// app/Conexao.php

class Conexao {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $config = require __DIR__ . '/Configs/db.php';

        $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        } catch (PDOException $e) {
            error_log("Erro de conexão com o banco de dados: " . $e->getMessage());
            die("Erro interno no servidor. Por favor, tente novamente mais tarde.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Conexao();
        }
        return self::$instance;
    }

    public function getPDO() {
        return $this->pdo;
    }
}
?>