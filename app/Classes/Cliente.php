<?php
// app/Classes/Cliente.php

class Cliente {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getInstance()->getPDO();
    }

    // Métodos para Cliente
}
?>