<?php
// public/deletar_processo.php

session_start();
require_once __DIR__ . '/../app/Classes/Processo.php';

// Only allow POST requests for deletion
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?mensagem=Metodo_nao_permitido');
    exit();
}

// Check CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header('Location: index.php?mensagem=Token_CSRF_invalido');
    exit();
}

$id = isset($_POST['id']) ? $_POST['id'] : null;

if ($id !== null) {
    $processoId = filter_var($id, FILTER_VALIDATE_INT);

    if ($processoId === false || $processoId <= 0) {
        header('Location: index.php?mensagem=ID_invalido');
        exit();
    }

    $processoObj = new Processo();

    if ($processoObj->deletarProcesso($processoId)) {
        header('Location: index.php?mensagem=Processo_deletado_com_sucesso');
    } else {
        header('Location: index.php?mensagem=Erro_ao_deletar_processo');
    }
} else {
    header('Location: index.php?mensagem=ID_nao_fornecido');
}
exit(); // Garante que o script pare de executar após o redirecionamento

?>