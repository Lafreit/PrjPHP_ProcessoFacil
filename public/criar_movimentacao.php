<?php
// public/criar_movimentacao.php

session_start();

require_once __DIR__ . '/../app/Classes/Processo.php'; // Para obter dados do processo
require_once __DIR__ . '/../app/Classes/Movimentacao.php';

$processoId = filter_var($_GET['processo_id'] ?? null, FILTER_VALIDATE_INT);
$processo = null;
$mensagem = '';
$old_values = $_POST ?? [];

if ($processoId === false || $processoId <= 0) {
    $mensagem = '<p style="color: red;">ID do processo inválido ou não fornecido.</p>';
} else {
    $processoObj = new Processo();
    $processo = $processoObj->getProcessoById($processoId);

    if (!$processo) {
        $mensagem = '<p style="color: red;">Processo não encontrado para adicionar movimentação.</p>';
        $processoId = null; // Invalida o ID para evitar submissão
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $processoId) {
    // CSRF token validation
    if (
        !isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $mensagem = '<p style="color: red;">Falha de segurança: token CSRF inválido.</p>';
    } else {
        $movimentacaoObj = new Movimentacao();

        $dadosMovimentacao = [
            'data_movimentacao' => trim($_POST['data_movimentacao'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'instancia' => trim($_POST['instancia'] ?? ''),
            'tipo' => trim($_POST['tipo'] ?? ''),
        ];

        $erros = [];
        if (empty($dadosMovimentacao['data_movimentacao'])) $erros[] = "Data da movimentação é obrigatória.";
        if (empty($dadosMovimentacao['descricao'])) $erros[] = "Descrição é obrigatória.";
        if (empty($dadosMovimentacao['instancia'])) $erros[] = "Instância é obrigatória.";

        // Valida se a instância está entre as opções válidas do ENUM
        $instanciasValidas = ['primeiro grau', 'segundo grau', 'especial', 'extraordinaria', 'administrativa'];
        if (!in_array($dadosMovimentacao['instancia'], $instanciasValidas)) {
            $erros[] = "Instância inválida. Selecione uma das opções fornecidas.";
        }

        if (count($erros) > 0) {
            $mensagem = '<p style="color: red;">' . implode('<br>', $erros) . '</p>';
        } else {
            if ($movimentacaoObj->criarMovimentacao($processoId, $dadosMovimentacao)) {
                $mensagem = '<p style="color: green;">Movimentação adicionada com sucesso!</p>';
                // Opcional: Limpar os campos do formulário após o sucesso
                $old_values = [];
            } else {
                $mensagem = '<p style="color: red;">Erro ao adicionar movimentação. Verifique os logs.</p>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Movimentação - ProcessoFacil</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Movimentação ao Processo:</h1>
        <?php if ($processo): ?>
            <p><strong>Número:</strong> <?php echo htmlspecialchars($processo->numero_processo); ?></p>
            <p><strong>Cliente:</strong> <?php echo htmlspecialchars($processo->cliente_nome); ?></p>
            <p><a href="index.php">Voltar para o Painel de Processos</a></p>
            <p><a href="visualizar_processo.php?id=<?php echo htmlspecialchars($processoId); ?>">Ver Detalhes do Processo</a></p>
        <?php endif; ?>

        <?php echo $mensagem; // Exibe mensagens ?>

        <?php
        // Gera um token CSRF se não existir
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        ?>
        <?php if ($processoId && $processo): // Só exibe o formulário se o processo for válido ?>
            <form action="criar_movimentacao.php?processo_id=<?php echo htmlspecialchars($processoId); ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <label for="data_movimentacao">Data da Movimentação:</label>
                <input type="datetime-local" id="data_movimentacao" name="data_movimentacao" value="<?php echo htmlspecialchars($old_values['data_movimentacao'] ?? date('Y-m-d\TH:i')); ?>" required>

                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="5" required><?php echo htmlspecialchars($old_values['descricao'] ?? ''); ?></textarea>

                <label for="instancia">Instância / Tipo:</label>
                <select id="instancia" name="instancia" required>
                    <option value="">Selecione...</option>
                    <option value="primeiro grau" <?php echo (isset($old_values['instancia']) && $old_values['instancia'] == 'primeiro grau') ? 'selected' : ''; ?>>Primeiro Grau</option>
                    <option value="segundo grau" <?php echo (isset($old_values['instancia']) && $old_values['instancia'] == 'segundo grau') ? 'selected' : ''; ?>>Segundo Grau</option>
                    <option value="especial" <?php echo (isset($old_values['instancia']) && $old_values['instancia'] == 'especial') ? 'selected' : ''; ?>>Especial</option>
                    <option value="extraordinaria" <?php echo (isset($old_values['instancia']) && $old_values['instancia'] == 'extraordinaria') ? 'selected' : ''; ?>>Extraordinária</option>
                    <option value="administrativa" <?php echo (isset($old_values['instancia']) && $old_values['instancia'] == 'administrativa') ? 'selected' : ''; ?>>Administrativa</option>
                </select>

                <label for="tipo">Tipo (Ex: Despacho, Sentença, Recurso, Protocolo):</label>
                <input type="text" id="tipo" name="tipo" value="<?php echo htmlspecialchars($old_values['tipo'] ?? ''); ?>">

                <button type="submit">Adicionar Movimentação</button>
            </form>
        <?php else: ?>
            <p>Não é possível adicionar movimentação sem um processo válido.</p>
        <?php endif; ?>
    </div>
</body>
</html>