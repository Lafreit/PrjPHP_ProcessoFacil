<?php
// public/visualizar_processo.php

session_start();

require_once __DIR__ . '/../app/Classes/Processo.php';
require_once __DIR__ . '/../app/Classes/Movimentacao.php';

$processoId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$processo = null;
$movimentacoes = [];
$mensagem = '';

// Verifica se há mensagens na URL (após redirecionamento)
if (isset($_GET['mensagem'])) {
    $feedback = $_GET['mensagem'];
    switch ($feedback) {
        case 'Movimentacao_deletada_com_sucesso':
            $mensagem = '<p class="feedback success">Movimentação deletada com sucesso!</p>';
            break;
        case 'Erro_ao_deletar_movimentacao':
            $mensagem = '<p class="feedback error">Erro ao deletar movimentação. Por favor, tente novamente.</p>';
            break;
        case 'Movimentacao_adicionada_com_sucesso':
            $mensagem = '<p class="feedback success">Movimentação adicionada com sucesso!</p>';
            break;
        case 'Movimentacao_atualizada_com_sucesso':
            $mensagem = '<p class="feedback success">Movimentação atualizada com sucesso!</p>';
            break;
        case 'ID_movimentacao_invalido':
            $mensagem = '<p class="feedback error">Erro: ID da movimentação inválido.</p>';
            break;
    }
}

if ($processoId === false || $processoId <= 0) {
    $mensagem = '<p class="feedback error">ID do processo inválido ou não fornecido.</p>';
} else {
    $processoObj = new Processo();
    $movimentacaoObj = new Movimentacao();

    $processo = $processoObj->getProcessoById($processoId);

    if (!$processo) {
        $mensagem = '<p class="feedback error">Processo não encontrado.</p>';
    } else {
        $movimentacoes = $movimentacaoObj->listarMovimentacoesPorProcesso($processoId);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Processo - ProcessoFacil</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .movimentacao-item {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        .movimentacao-item h3 {
            margin-top: 0;
            color: #333;
        }
        .movimentacao-item p {
            margin: 5px 0;
            text-align: left; /* Ajuste para texto dentro da movimentação */
        }
        .movimentacao-item .acoes {
            text-align: right;
            margin-top: 10px;
        }
        .movimentacao-item .acoes a {
            margin-left: 10px;
        }
        .feedback {
            text-align: center;
            padding: 10px;
            border-radius: 4px;
            margin: 10px auto;
            width: fit-content;
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
        <h1>Detalhes do Processo</h1>
        <p><a href="index.php">Voltar para o Painel de Processos</a></p>

        <?php echo $mensagem; // Exibe mensagens ?>

        <?php if ($processo): ?>
            <div class="processo-details">
                <h2>Dados do Processo (ID: <?php echo htmlspecialchars($processo->processo_id); ?>)</h2>
                <p><strong>Número do Processo:</strong> <?php echo htmlspecialchars($processo->numero_processo); ?></p>
                <p><strong>Data do Protocolo:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($processo->data_protocolo))); ?></p>
                <p><strong>Vara / Tribunal de Origem:</strong> <?php echo htmlspecialchars($processo->vara_tribunal_origem); ?></p>
                <p><strong>Natureza da Ação:</strong> <?php echo htmlspecialchars($processo->natureza_acao); ?></p>

                <h3>Cliente</h3>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($processo->cliente_nome); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($processo->cliente_email ?? 'N/A'); ?></p>
                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($processo->cliente_telefone ?? 'N/A'); ?></p>

                <h3>Parte Ex-Adversa</h3>
                <p><strong>Nome:</strong> <?php echo htmlspecialchars($processo->parte_ex_adversa_nome); ?></p>
                <p><strong>Identidade:</strong> <?php echo htmlspecialchars($processo->parte_ex_adversa_identidade ?? 'N/A'); ?></p>
                <p><strong>CPF:</strong> <?php echo htmlspecialchars($processo->parte_ex_adversa_cpf ?? 'N/A'); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($processo->parte_ex_adversa_email ?? 'N/A'); ?></p>
                <p><strong>Endereço:</strong> <?php echo nl2br(htmlspecialchars($processo->parte_ex_adversa_endereco ?? 'N/A')); ?></p>

                <h3>Advogados Envolvidos</h3>
                <?php if (!empty($processo->advogados)): ?>
                    <ul>
                        <?php foreach ($processo->advogados as $advogado): ?>
                            <li>
                                <?php echo htmlspecialchars($advogado->advogado_nome); ?> (OAB: <?php echo htmlspecialchars($advogado->advogado_oab); ?>)
                                <?php echo !empty($advogado->advogado_email) ? ' - ' . htmlspecialchars($advogado->advogado_email) : ''; ?>
                                <?php echo !empty($advogado->advogado_telefone) ? ' - Tel: ' . htmlspecialchars($advogado->advogado_telefone) : ''; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Nenhum advogado associado a este processo.</p>
                <?php endif; ?>

                <hr>

                <h2>Movimentações do Processo</h2>
                <p><a href="criar_movimentacao.php?processo_id=<?php echo htmlspecialchars($processo->processo_id); ?>" class="button">Adicionar Nova Movimentação</a></p>

                <?php if (!empty($movimentacoes)): ?>
                    <?php foreach ($movimentacoes as $mov): ?>
                        <div class="movimentacao-item">
                            <h3><?php echo htmlspecialchars($mov->instancia); ?> - <?php echo htmlspecialchars($mov->tipo ?? 'Movimentação Geral'); ?></h3>
                            <p><strong>Data:</strong> <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($mov->data_movimentacao))); ?></p>
                            <p><strong>Descrição:</strong> <?php echo nl2br(htmlspecialchars($mov->descricao)); ?></p>
                            <div class="acoes">
                                <a href="editar_movimentacao.php?id=<?php echo htmlspecialchars($mov->id); ?>&processo_id=<?php echo htmlspecialchars($processo->processo_id); ?>">Editar</a> |
                                <form action="deletar_movimentacao.php" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja deletar esta movimentação?');">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($mov->id); ?>">
                                    <input type="hidden" name="processo_id" value="<?php echo htmlspecialchars($processo->processo_id); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
                                    <button type="submit" style="background:none;border:none;color:#007bff;text-decoration:underline;cursor:pointer;padding:0;">Deletar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Nenhuma movimentação registrada para este processo.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>O processo solicitado não pôde ser encontrado.</p>
        <?php endif; ?>
    </div>
</body>
</html>