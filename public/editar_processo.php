<?php
// public/editar_processo.php

session_start();

require_once __DIR__ . '/../app/Classes/Processo.php';

$processoObj = new Processo();
$mensagem = '';
$processoDados = null; // Para armazenar os dados do processo a ser editado
$processoId = $_GET['id'] ?? null; // Obtém o ID do processo da URL

if (!filter_var($processoId, FILTER_VALIDATE_INT) || $processoId <= 0) {
    $mensagem = '<p style="color: red;">ID do processo inválido.</p>';
    // Redirecionar de volta para a lista se o ID for inválido
    header('Location: index.php');
    exit();
}

// Se o formulário foi submetido (POST), processa a atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica o token CSRF
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $mensagem = '<p style="color: red;">Falha de verificação CSRF. Por favor, recarregue a página e tente novamente.</p>';
    } else {
        $processoId = (int)$_POST['processo_id']; // Garante que o ID é um inteiro
        $dadosProcesso = [
        'numero_processo' => trim($_POST['numero_processo'] ?? ''),
        'data_protocolo' => trim($_POST['data_protocolo'] ?? ''),
        'vara_tribunal_origem' => trim($_POST['vara_tribunal_origem'] ?? ''),
        'natureza_acao' => trim($_POST['natureza_acao'] ?? ''),
        'cliente_nome' => trim($_POST['cliente_nome'] ?? ''),
        'cliente_email' => trim($_POST['cliente_email'] ?? ''),
        'cliente_telefone' => trim($_POST['cliente_telefone'] ?? ''),
        'parte_ex_adversa_nome' => trim($_POST['parte_ex_adversa_nome'] ?? ''),
        'parte_ex_adversa_identidade' => trim($_POST['parte_ex_adversa_identidade'] ?? ''),
        'parte_ex_adversa_cpf' => trim($_POST['parte_ex_adversa_cpf'] ?? ''),
        'parte_ex_adversa_email' => trim($_POST['parte_ex_adversa_email'] ?? ''),
        'parte_ex_adversa_endereco' => trim($_POST['parte_ex_adversa_endereco'] ?? ''),
    ];
    }
    $advogados = [];
    if (isset($_POST['advogado_nome']) && is_array($_POST['advogado_nome'])) {
        foreach ($_POST['advogado_nome'] as $key => $nome_adv) {
            // Apenas adiciona se tiver nome e OAB preenchidos
            if (!empty(trim($nome_adv)) && !empty(trim($_POST['advogado_oab'][$key] ?? ''))) {
                $advogados[] = [
                    'nome' => trim($nome_adv),
                    'oab' => trim($_POST['advogado_oab'][$key]),
                    'telefone' => trim($_POST['advogado_telefone'][$key] ?? ''),
                    'email' => trim($_POST['advogado_email'][$key] ?? '')
                ];
            }
        }
    }
    $dadosProcesso['advogados'] = $advogados;

    // Validação básica (reutiliza parte da lógica de criar_processo.php)
    $erros = [];
    if (empty($dadosProcesso['numero_processo'])) $erros[] = "Número do processo é obrigatório.";
    if (empty($dadosProcesso['data_protocolo'])) $erros[] = "Data do protocolo é obrigatória.";
    if (empty($dadosProcesso['vara_tribunal_origem'])) $erros[] = "Vara/Tribunal de origem é obrigatória.";
    if (empty($dadosProcesso['natureza_acao'])) $erros[] = "Natureza da ação é obrigatória.";
    if (empty($dadosProcesso['cliente_nome'])) $erros[] = "Nome do cliente é obrigatório.";
    if (empty($dadosProcesso['parte_ex_adversa_nome'])) $erros[] = "Nome da parte ex-adversa é obrigatório.";
    if (!empty($dadosProcesso['cliente_email']) && !filter_var($dadosProcesso['cliente_email'], FILTER_VALIDATE_EMAIL)) $erros[] = "E-mail do cliente inválido.";
    if (!empty($dadosProcesso['parte_ex_adversa_email']) && !filter_var($dadosProcesso['parte_ex_adversa_email'], FILTER_VALIDATE_EMAIL)) $erros[] = "E-mail da parte ex-adversa inválido.";

    foreach ($dadosProcesso['advogados'] as $key => $adv) {
        if (empty($adv['nome'])) $erros[] = "Nome do advogado #" . ($key + 1) . " é obrigatório.";
        if (empty($adv['oab'])) $erros[] = "OAB do advogado #" . ($key + 1) . " é obrigatória.";
        if (!empty($adv['email']) && !filter_var($adv['email'], FILTER_VALIDATE_EMAIL)) $erros[] = "E-mail do advogado #" . ($key + 1) . " inválido.";
    }

    if (count($erros) > 0) {
        $mensagem = '<p style="color: red;">' . implode('<br>', $erros) . '</p>';
        // Para manter os dados preenchidos no formulário após o erro
        $processoDados = (object) array_merge((array)($processoDados ?? []), $dadosProcesso); // Mescla com os dados do POST
    } else {
        if ($processoObj->atualizarProcesso($processoId, $dadosProcesso)) {
            $mensagem = '<p style="color: green;">Processo atualizado com sucesso!</p>';
            // Recarrega os dados do processo para exibir as últimas atualizações
            $processoDados = $processoObj->getProcessoById($processoId);
        } else {
            $mensagem = '<p style="color: red;">Erro ao atualizar processo. Verifique os logs para mais detalhes.</p>';
            // Mantém os dados submetidos no formulário em caso de falha de DB
            $processoDados = (object) array_merge((array)$processoDados, $dadosProcesso);
        }
    }
} else {
    // Se não for POST, busca os dados para pré-preencher o formulário
    $processoDados = $processoObj->getProcessoById($processoId);

    if (!$processoDados) {
        $mensagem = '<p style="color: red;">Processo não encontrado.</p>';
        // Redirecionar se o processo não for encontrado
        header('Location: index.php');
        exit();
    }
}

// Gera um novo token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Processo - ProcessoFacil</title>
    <link rel="stylesheet" href="css/style.css">
    <script>
        // Função JavaScript para adicionar campos de advogado dinamicamente
        document.addEventListener('DOMContentLoaded', function() {
            let advogadoCount = <?php echo count($processoDados->advogados ?? []) > 0 ? count($processoDados->advogados) : 1; ?>;
            const advogadosContainer = document.getElementById('advogados-container');
            const addAdvogadoBtn = document.getElementById('add-advogado');

            addAdvogadoBtn.addEventListener('click', function() {
                advogadoCount++;
                const newAdvogadoDiv = document.createElement('div');
                newAdvogadoDiv.classList.add('advogado-group');
                newAdvogadoDiv.innerHTML = `
                    <h3>Advogado ${advogadoCount}</h3>
                    <label for="advogado_nome_${advogadoCount}">Nome:</label>
                    <input type="text" id="advogado_nome_${advogadoCount}" name="advogado_nome[]" required aria-label="Nome do Advogado ${advogadoCount}">

                    <label for="advogado_oab_${advogadoCount}">OAB:</label>
                    <input type="text" id="advogado_oab_${advogadoCount}" name="advogado_oab[]" required aria-label="OAB do Advogado ${advogadoCount}">

                    <label for="advogado_telefone_${advogadoCount}">Telefone:</label>
                    <input type="text" id="advogado_telefone_${advogadoCount}" name="advogado_telefone[]" aria-label="Telefone do Advogado ${advogadoCount}">

                    <label for="advogado_email_${advogadoCount}">E-mail:</label>
                    <input type="email" id="advogado_email_${advogadoCount}" name="advogado_email[]" aria-label="E-mail do Advogado ${advogadoCount}">
                `;
                advogadosContainer.appendChild(newAdvogadoDiv);
            });
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>Editar Processo (ID: <?php echo htmlspecialchars($processoDados->processo_id ?? ''); ?>)</h1>
        <p><a href="index.php">Voltar para o Painel de Processos</a></p>

        <?php echo $mensagem; // Exibe mensagens de feedback ?>

        <form action="editar_processo.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="processo_id" value="<?php echo htmlspecialchars($processoDados->processo_id ?? ''); ?>">

            <h2>Dados do Processo</h2>
            <label for="numero_processo">Número do Processo:</label>
            <input type="text" id="numero_processo" name="numero_processo" value="<?php echo htmlspecialchars($processoDados->numero_processo ?? ''); ?>" required>

            <label for="data_protocolo">Data do Protocolo:</label>
            <input type="date" id="data_protocolo" name="data_protocolo" value="<?php echo htmlspecialchars($processoDados->data_protocolo ?? ''); ?>" required>

            <label for="vara_tribunal_origem">Vara / Tribunal de Origem:</label>
            <input type="text" id="vara_tribunal_origem" name="vara_tribunal_origem" value="<?php echo htmlspecialchars($processoDados->vara_tribunal_origem ?? ''); ?>" required>

            <label for="natureza_acao">Natureza da Ação:</label>
            <input type="text" id="natureza_acao" name="natureza_acao" value="<?php echo htmlspecialchars($processoDados->natureza_acao ?? ''); ?>" required>

            <!-- ============================== -->

            <h2>Dados do Cliente</h2>
            <label for="cliente_nome">Nome do Cliente:</label>
            <input type="text" id="cliente_nome" name="cliente_nome" value="<?php echo htmlspecialchars($processoDados->cliente_nome ?? ''); ?>" required>

            <label for="cliente_email">E-mail do Cliente:</label>
            <input type="email" id="cliente_email" name="cliente_email" value="<?php echo htmlspecialchars($processoDados->cliente_email ?? ''); ?>">

            <label for="cliente_telefone">Telefone do Cliente:</label>
            <input type="text" id="cliente_telefone" name="cliente_telefone" value="<?php echo htmlspecialchars($processoDados->cliente_telefone ?? ''); ?>">

            <!-- ============================== -->

            <h2>Dados da Parte Ex-Adversa</h2>
            <label for="parte_ex_adversa_nome">Nome da Parte Ex-Adversa:</label>
            <input type="text" id="parte_ex_adversa_nome" name="parte_ex_adversa_nome" value="<?php echo htmlspecialchars($processoDados->parte_ex_adversa_nome ?? ''); ?>" required>

            <label for="parte_ex_adversa_identidade">Identidade:</label>
            <input type="text" id="parte_ex_adversa_identidade" name="parte_ex_adversa_identidade" value="<?php echo htmlspecialchars($processoDados->parte_ex_adversa_identidade ?? ''); ?>">

            <label for="parte_ex_adversa_cpf">CPF:</label>
            <input type="text" id="parte_ex_adversa_cpf" name="parte_ex_adversa_cpf" value="<?php echo htmlspecialchars($processoDados->parte_ex_adversa_cpf ?? ''); ?>">

            <label for="parte_ex_adversa_email">E-mail:</label>
            <input type="email" id="parte_ex_adversa_email" name="parte_ex_adversa_email" value="<?php echo htmlspecialchars($processoDados->parte_ex_adversa_email ?? ''); ?>">

            <label for="parte_ex_adversa_endereco">Endereço:</label>
            <textarea id="parte_ex_adversa_endereco" name="parte_ex_adversa_endereco"><?php echo htmlspecialchars($processoDados->parte_ex_adversa_endereco ?? ''); ?></textarea>

            <!-- ============================== -->

            <h2>Advogados Envolvidos</h2>
            <div id="advogados-container">
                <?php if (!empty($processoDados->advogados)): ?>
                    <?php foreach ($processoDados->advogados as $key => $advogado): ?>
                        <div class="advogado-group">
                            <h3>Advogado <?php echo $key + 1; ?></h3>
                            <label for="advogado_nome_<?php echo $key + 1; ?>">Nome:</label>
                            <input type="text" id="advogado_nome_<?php echo $key + 1; ?>" name="advogado_nome[]" value="<?php echo htmlspecialchars($advogado->advogado_nome ?? ''); ?>" required>

                            <label for="advogado_oab_<?php echo $key + 1; ?>">OAB:</label>
                            <input type="text" id="advogado_oab_<?php echo $key + 1; ?>" name="advogado_oab[]" value="<?php echo htmlspecialchars($advogado->advogado_oab ?? ''); ?>" required>

                            <label for="advogado_telefone_<?php echo $key + 1; ?>">Telefone:</label>
                            <input type="text" id="advogado_telefone_<?php echo $key + 1; ?>" name="advogado_telefone[]" value="<?php echo htmlspecialchars($advogado->advogado_telefone ?? ''); ?>">

                            <label for="advogado_email_<?php echo $key + 1; ?>">E-mail:</label>
                            <input type="email" id="advogado_email_<?php echo $key + 1; ?>" name="advogado_email[]" value="<?php echo htmlspecialchars($advogado->advogado_email ?? ''); ?>">
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="advogado-group">
                        <h3>Advogado 1</h3>
                        <label for="advogado_nome_1">Nome:</label>
                        <input type="text" id="advogado_nome_1" name="advogado_nome[]">

                        <label for="advogado_oab_1">OAB:</label>
                        <input type="text" id="advogado_oab_1" name="advogado_oab[]">

                        <label for="advogado_telefone_1">Telefone:</label>
                        <input type="text" id="advogado_telefone_1" name="advogado_telefone[]">

                        <label for="advogado_email_1">E-mail:</label>
                        <input type="email" id="advogado_email_1" name="advogado_email[]">
                    </div>
                <?php endif; ?>
            </div>
            <button type="button" id="add-advogado">Adicionar Mais Advogado</button>

            <!-- ============================== -->

            <button type="submit">Atualizar Processo</button>
        </form>
    </div>
</body>
</html>