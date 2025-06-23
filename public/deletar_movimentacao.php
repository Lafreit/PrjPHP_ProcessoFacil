<?php
// public/deletar_movimentacao.php

require_once __DIR__ . '/../app/Classes/Movimentacao.php';
session_start();

// CSRF token validation
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    header('Location: index.php?message=invalid_request');
    exit();
}

$movementId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$processId = filter_var($_POST['processo_id'] ?? null, FILTER_VALIDATE_INT); // For redirecting back

if ($movementId === false || $movementId <= 0 || $processId === false || $processId <= 0) {
    // Redirect to process details page or index with error message
    header('Location: ' . ($processId ? 'visualizar_processo.php?id=' . htmlspecialchars($processId) . '&message=invalid_movement_id' : 'index.php?message=invalid_id'));
    exit();
}

$movementObj = new Movimentacao();

if ($movementObj->deletarMovimentacao($movementId)) {
    header('Location: visualizar_processo.php?id=' . htmlspecialchars($processId) . '&message=movement_deleted_successfully');
} else {
    header('Location: visualizar_processo.php?id=' . htmlspecialchars($processId) . '&message=error_deleting_movement');
}
exit();
?>