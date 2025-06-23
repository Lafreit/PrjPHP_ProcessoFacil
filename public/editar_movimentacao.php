<?php
// public/editar_movimentacao.php

require_once __DIR__ . '/../app/Classes/Movimentacao.php';
require_once __DIR__ . '/../app/Classes/Processo.php'; // Para exibir dados do processo

// Inicia a sessão para CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$movimentacaoObj = new Movimentacao();
$processoObj = new Processo();
$mensagem = '';
$movimentacaoDados = null;
$processoDados = null;

$movimentacaoId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$processoId = filter_var($_GET['processo_id'] ?? null, FILTER_VALIDATE_INT);

// Validação inicial dos IDs
if ($movimentacaoId === false || $movimentacaoId <= 0 || $processoId === false || $processoId <= 0) {
    $mensagem = '<p style="color: red;">ID de movimentação ou processo inválido.</p>';
    // Redireciona de volta para a lista principal se os IDs forem inválidos
    header('Location: index.php');
    exit();
}

// Busca os dados do processo pai para exibição
$processoDados = $processoObj->getProcessoById($processoId);
if (!$processoDados) {
    $mensagem = '<p style="color: red;">Processo associado não encontrado.</p>';
    header('Location: index.php');
    exit();
}

// Se o formulário foi submetido (POST), processa a atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validação do CSRF token
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $mensagem = '<p style="color: red;">Falha de segurança: token CSRF inválido.</p>';
        $movimentacaoDados = null;
    } else {
        $movimentacaoId = (int)$_POST['movimentacao_id']; // Garante que o ID é um inteiro

        $dadosMovimentacao = [
            'data_movimentacao' => trim($_POST['data_movimentacao'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'instancia' => trim($_POST['instancia'] ?? ''),
            'tipo' => trim($_POST['tipo'] ?? ''),
        ];

        // Validação básica
        $erros = [];
        if (empty($dadosMovimentacao['data_movimentacao'])) $erros[] = "Data da movimentação é obrigatória.";
        if (empty($dadosMovimentacao['descricao'])) $erros[] = "Descrição é obrigatória.";
        if (empty($dadosMovimentacao['instancia'])) $erros[] = "Instância é obrigatória.";

        $instanciasValidas = ['primeiro grau', 'segundo grau', 'especial', 'extraordinaria', 'administrativa'];
        if (!in_array($dadosMovimentacao['instancia'], $instanciasValidas)) {
            $erros[] = "Instância inválida. Selecione uma das opções fornecidas.";
        }

        if (count($erros) > 0) {
            $mensagem = '<p style="color: red;">' . implode('<br>', $erros) . '</p>';
            // Para manter os dados preenchidos no formulário após o erro
            $movimentacaoDados = (object) array_merge((array)$movimentacaoDados, $dadosMovimentacao);
        } else {
            if ($movimentacaoObj->atualizarMovimentacao($movimentacaoId, $dadosMovimentacao)) {
                $mensagem = '<p style="color: green;">Movimentação atualizada com sucesso!</p>';
                // Recarrega os dados da movimentação para exibir as últimas atualizações
                $movimentacaoDados = $movimentacaoObj->getMovimentacaoById($movimentacaoId);
            } else {
                $mensagem = '<p style="color: red;">Erro ao atualizar movimentação. Verifique os logs para mais detalhes.</p>';
                $movimentacaoDados = (object) array_merge((array)$movimentacaoDados, $dadosMovimentacao);
            }
        }
    }
} else {
    // Se não for POST, busca os dados para pré-preencher o formulário
    $movimentacaoDados = $movimentacaoObj->getMovimentacaoById($movimentacaoId);

    if (!$movimentacaoDados || $movimentacaoDados->processo_id != $processoId) {
        $mensagem = '<p style="color: red;">Movimentação não encontrada ou não pertence a este processo.</p>';
        header('Location: visualizar_processo.php?id=' . htmlspecialchars($processoId));
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Movimentação - ProcessoFacil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Movimentação</h1>
        <?php if ($processoDados): ?>
            <p><strong>Processo:</strong> <?php echo htmlspecialchars($processoDados->numero_processo); ?></p>
            <p><a href="visualizar_processo.php?id=<?php echo htmlspecialchars($processoId); ?>">Voltar para Detalhes do Processo</a></p>
        <?php endif; ?>

        <?php echo $mensagem; // Exibe mensagens de feedback ?>

        <?php
            // Gera o token CSRF se não existir
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        ?>
        <?php if ($movimentacaoDados): ?>
            <form action="editar_movimentacao.php?id=<?php echo htmlspecialchars($movimentacaoId); ?>&processo_id=<?php echo htmlspecialchars($processoId); ?>" method="POST">
                <input type="hidden" name="movimentacao_id" value="<?php echo htmlspecialchars($movimentacaoDados->id); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

                <label for="data_movimentacao">Data da Movimentação:</label>
                <input type="datetime-local" id="data_movimentacao" name="data_movimentacao" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($movimentacaoDados->data_movimentacao))); ?>" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($movimentacaoDados->descricao); ?></textarea>

                <label for="instancia">Instância / Tipo:</label>
                <select id="instancia" name="instancia" required>
                    <option value="">Selecione...</option>
                    <option value="primeiro grau" <?php echo (isset($movimentacaoDados->instancia) && $movimentacaoDados->instancia == 'primeiro grau') ? 'selected' : ''; ?>>Primeiro Grau</option>
                    <option value="segundo grau" <?php echo (isset($movimentacaoDados->instancia) && $movimentacaoDados->instancia == 'segundo grau') ? 'selected' : ''; ?>>Segundo Grau</option>
                    <option value="especial" <?php echo (isset($movimentacaoDados->instancia) && $movimentacaoDados->instancia == 'especial') ? 'selected' : ''; ?>>Especial</option>
                    <option value="extraordinaria" <?php echo (isset($movimentacaoDados->instancia) && $movimentacaoDados->instancia == 'extraordinaria') ? 'selected' : ''; ?>>Extraordinária</option>
                    <option value="administrativa" <?php echo (isset($movimentacaoDados->instancia) && $movimentacaoDados->instancia == 'administrativa') ? 'selected' : ''; ?>>Administrativa</option>
                </select>

                <label for="tipo">Tipo (Ex: Despacho, Sentença, Recurso, Protocolo):</label>
                <input type="text" id="tipo" name="tipo" value="<?php echo htmlspecialchars($movimentacaoDados->tipo ?? ''); ?>">

                <button type="submit">Atualizar Movimentação</button>
            </form>
        <?php else: ?>
            <p>Movimentação não encontrada para edição.</p>
        <?php endif; ?>
    </div>
</body>
</html>