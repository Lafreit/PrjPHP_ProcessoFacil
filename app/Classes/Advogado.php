<?php
// app/Classes/Advogado.php

class Advogado {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getInstance()->getPDO();
    }
}
?>