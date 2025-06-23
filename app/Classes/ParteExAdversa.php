<?php
// app/Classes/ParteExAdversa.php

class ParteExAdversa {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getInstance()->getPDO();
    }

    // Métodos para Parte Ex-Adversa
}
?>