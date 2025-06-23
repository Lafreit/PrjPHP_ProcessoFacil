<?php
// public/index.php

require_once __DIR__ . '/../app/Classes/Processo.php';

$processoObj = new Processo();
$processos = [];
$mensagem = '';

// Verifica se há mensagens na URL (após redirecionamento de outras páginas)
if (isset($_GET['mensagem'])) {
    $feedback = $_GET['mensagem'];
    switch ($feedback) {
        case 'Processo_deletado_com_sucesso':
            $mensagem = '<p class="feedback success">Processo deletado com sucesso!</p>';
            break;
        case 'Erro_ao_deletar_processo':
            $mensagem = '<p class="feedback error">Erro ao deletar processo. Por favor, tente novamente.</p>';
            break;
        case 'ID_invalido':
            $mensagem = '<p class="feedback error">Erro: ID do processo inválido.</p>';
            break;
        case 'ID_nao_fornecido':
            $mensagem = '<p class="feedback error">Erro: ID do processo não fornecido.</p>';
            break;
        // case 'Processo_criado_com_sucesso':
        //     $mensagem = '<p style="color: green; text-align: center;">Processo criado com sucesso!</p>';
        //     break;
        // case 'Processo_atualizado_com_sucesso':
        //     $mensagem = '<p style="color: green; text-align: center;">Processo atualizado com sucesso!</p>';
        //     break;
    }
}

try {
    $processos = $processoObj->listarProcessos();
} catch (Exception $e) {
    echo '<p class="feedback error">Erro ao carregar a lista de processos: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Processos - ProcessoFacil</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .feedback {
            text-align: center;
            margin: 10px 0;
            padding: 8px 12px;
            border-radius: 4px;
            font-weight: bold;
        }
        .feedback.success {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .feedback.error {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Painel de Processos</h1>
        <p><a href="criar_processo.php" class="button">Adicionar Novo Processo</a></p>

        <?php echo $mensagem; // Exibe mensagens de feedback ?>

        <?php if (count($processos) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Número do Processo</th>
                        <th>Data Protocolo</th>
                        <th>Natureza da Ação</th>
                        <th>Cliente</th>
                        <th>Parte Ex-Adversa</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processos as $processo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($processo->id); ?></td>
                            <td><?php echo htmlspecialchars($processo->numero_processo); ?></td>
                            <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($processo->data_protocolo))); ?></td>
                            <td><?php echo htmlspecialchars($processo->natureza_acao); ?></td>
                            <td><?php echo htmlspecialchars($processo->cliente_nome); ?></td>
                            <td><?php echo htmlspecialchars($processo->parte_ex_adversa_nome); ?></td>
                            <td>
                                <a href="editar_processo.php?id=<?php echo htmlspecialchars($processo->id); ?>">Editar</a> |
                                <form action="deletar_processo.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar este processo e todas as suas informações relacionadas? Esta ação é irreversível!');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($processo->id); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <button type="submit" style="background:none;border:none;color:#007bff;cursor:pointer;padding:0;">Deletar</button>
                                </form>
                                <br>
                                <a href="visualizar_processo.php?id=<?php echo htmlspecialchars($processo->id); ?>">Ver Detalhes/Mov.</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nenhum processo cadastrado ainda.</p>
        <?php endif; ?>
    </div>
</body>
</html>