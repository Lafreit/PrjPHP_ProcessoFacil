<?php
// public/criar_processo.php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/Conexao.php';
require_once __DIR__ . '/../app/Classes/Processo.php';
require_once __DIR__ . '/../app/Classes/Advogado.php';
require_once __DIR__ . '/../app/Classes/Cliente.php';
require_once __DIR__ . '/../app/Classes/ParteExAdversa.php';
require_once __DIR__ . '/../app/Classes/Movimentacao.php';

$processoObj = new Processo();
$mensagem = '';
$old_values = []; // Inicializa um array para armazenar valores antigos se houver erro

// Verifica se há mensagens na URL (após redirecionamento de outras páginas)
if (isset($_GET['mensagem'])) {
    $feedback = $_GET['mensagem'];
    switch ($feedback) {
        case 'Processo_criado_com_sucesso':
            $mensagem = '<div class="alert alert-success" role="alert">Processo criado com sucesso!</div>';
            break;
        case 'Erro_ao_criar_processo':
            $mensagem = '<div class="alert alert-danger" role="alert">Erro ao criar processo. Por favor, tente novamente.</div>';
            break;
    }
}

// Se o formulário foi submetido (POST), processa a criação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $mensagem = '<div class="alert alert-danger" role="alert">Falha na validação do token CSRF. Por favor, recarregue a página e tente novamente.</div>';
    } else { // Início do else do CSRF
        // Coleta os dados do formulário e armazena em old_values para repopular se houver erro
        $old_values = $_POST;

        $dadosProcesso = [
            'numero_processo' => trim($_POST['numero_processo'] ?? ''),
            'data_protocolo' => trim($_POST['data_protocolo'] ?? ''),
            'vara_tribunal_origem' => trim($_POST['vara_tribunal_origem'] ?? ''),
            'natureza_acao' => trim($_POST['natureza_acao'] ?? ''),
        ];

        $dadosCliente = [
            'nome' => trim($_POST['cliente_nome'] ?? ''),
            'email' => trim($_POST['cliente_email'] ?? ''),
            'telefone' => trim($_POST['cliente_telefone'] ?? ''),
        ];

        $dadosParteExAdversa = [
            'nome' => trim($_POST['parte_ex_adversa_nome'] ?? ''),
            'identidade' => trim($_POST['parte_ex_adversa_identidade'] ?? ''),
            'cpf' => trim($_POST['parte_ex_adversa_cpf'] ?? ''),
            'email' => trim($_POST['parte_ex_adversa_email'] ?? ''),
            'endereco' => trim($_POST['parte_ex_adversa_endereco'] ?? ''),
        ];

        $advogados = [];
        if (isset($_POST['advogado_nome']) && is_array($_POST['advogado_nome'])) {
            foreach ($_POST['advogado_nome'] as $key => $nome) {
                // Apenas adiciona advogado se o nome ou OAB não estiverem vazios
                if (!empty(trim($nome)) || !empty(trim($_POST['advogado_oab'][$key] ?? ''))) {
                    $advogados[] = [
                        'nome' => trim($nome),
                        'oab' => trim($_POST['advogado_oab'][$key] ?? ''),
                        'telefone' => trim($_POST['advogado_telefone'][$key] ?? ''),
                        'email' => trim($_POST['advogado_email'][$key] ?? ''),
                    ];
                }
            }
        }

        // Validação básica
        $erros = [];
        if (empty($dadosProcesso['numero_processo'])) $erros[] = "Número do processo é obrigatório.";
        if (empty($dadosProcesso['data_protocolo'])) $erros[] = "Data do protocolo é obrigatória.";
        if (empty($dadosProcesso['vara_tribunal_origem'])) $erros[] = "Vara/Tribunal de origem é obrigatória.";
        if (empty($dadosProcesso['natureza_acao'])) $erros[] = "Natureza da ação é obrigatória.";
        if (empty($dadosCliente['nome'])) $erros[] = "Nome do cliente é obrigatório.";
        if (empty($dadosParteExAdversa['nome'])) $erros[] = "Nome da parte ex-adversa é obrigatório.";

        // Validação para pelo menos um advogado
        if (empty($advogados)) {
            $erros[] = "Pelo menos um advogado é obrigatório.";
        } else {
            foreach ($advogados as $adv) {
                if (empty($adv['nome'])) $erros[] = "Nome do advogado é obrigatório.";
                if (empty($adv['oab'])) $erros[] = "OAB do advogado é obrigatória.";
            }
        }

        if (count($erros) > 0) {
            $mensagem = '<div class="alert alert-danger" role="alert">' . implode('<br>', $erros) . '</div>';
        } else {
            // Tenta criar o processo
            $novoProcessoId = $processoObj->criarProcesso($dadosProcesso, $dadosCliente, $dadosParteExAdversa, $advogados);

            if ($novoProcessoId) {
                // Redireciona para o painel com mensagem de sucesso
                header('Location: index.php?mensagem=Processo_criado_com_sucesso');
                exit();
            } else {
                $mensagem = '<div class="alert alert-danger" role="alert">Erro ao criar processo. Verifique os logs para mais detalhes.</div>';
            }
        }
    }
}

// Gera um token CSRF se não existir
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Inicia as variáveis para o formulário no modo "criação" (vazias ou com valores antigos em caso de erro)
$old_values['numero_processo'] = $old_values['numero_processo'] ?? '';
$old_values['data_protocolo'] = $old_values['data_protocolo'] ?? '';
$old_values['vara_tribunal_origem'] = $old_values['vara_tribunal_origem'] ?? '';
$old_values['natureza_acao'] = $old_values['natureza_acao'] ?? '';

$old_values['cliente_nome'] = $old_values['cliente_nome'] ?? '';
$old_values['cliente_email'] = $old_values['cliente_email'] ?? '';
$old_values['cliente_telefone'] = $old_values['cliente_telefone'] ?? '';

$old_values['parte_ex_adversa_nome'] = $old_values['parte_ex_adversa_nome'] ?? '';
$old_values['parte_ex_adversa_identidade'] = $old_values['parte_ex_adversa_identidade'] ?? '';
$old_values['parte_ex_adversa_cpf'] = $old_values['parte_ex_adversa_cpf'] ?? '';
$old_values['parte_ex_adversa_email'] = $old_values['parte_ex_adversa_email'] ?? '';
$old_values['parte_ex_adversa_endereco'] = $old_values['parte_ex_adversa_endereco'] ?? '';


// Advogados para pré-preenchimento
$advogados_para_exibir = [];
if (isset($old_values['advogado_nome']) && is_array($old_values['advogado_nome'])) {
    foreach ($old_values['advogado_nome'] as $key => $nome) {
        $advogados_para_exibir[] = (object)[
            'advogado_nome' => $nome,
            'advogado_oab' => $old_values['advogado_oab'][$key] ?? '',
            'advogado_telefone' => $old_values['advogado_telefone'][$key] ?? '',
            'advogado_email' => $old_values['advogado_email'][$key] ?? ''
        ];
    }
}
// Se não há dados antigos (primeira vez acessando a página), adicione um advogado vazio para o formulário
if (empty($advogados_para_exibir)) {
    $advogados_para_exibir[] = (object)['advogado_nome' => '', 'advogado_oab' => '', 'advogado_telefone' => '', 'advogado_email' => ''];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Processo - ProcessoFacil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4 text-center">Criar Novo Processo</h1>
        <p class="mb-4"><a href="index.php" class="btn btn-secondary">Voltar para o Painel</a></p>

        <?php if (!empty($mensagem)): ?>
            <?php echo $mensagem; ?>
        <?php endif; ?>

        <form action="criar_processo.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <h2 class="mt-4 mb-3">Dados do Processo</h2>
            <div class="mb-3">
                <label for="numero_processo" class="form-label">Número do Processo:</label>
                <input type="text" class="form-control" id="numero_processo" name="numero_processo" value="<?php echo htmlspecialchars($old_values['numero_processo']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="data_protocolo" class="form-label">Data do Protocolo:</label>
                <input type="date" class="form-control" id="data_protocolo" name="data_protocolo" value="<?php echo htmlspecialchars($old_values['data_protocolo']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="vara_tribunal_origem" class="form-label">Vara / Tribunal de Origem:</label>
                <input type="text" class="form-control" id="vara_tribunal_origem" name="vara_tribunal_origem" value="<?php echo htmlspecialchars($old_values['vara_tribunal_origem']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="natureza_acao" class="form-label">Natureza da Ação:</label>
                <input type="text" class="form-control" id="natureza_acao" name="natureza_acao" value="<?php echo htmlspecialchars($old_values['natureza_acao']); ?>" required>
            </div>

            <hr>

            <h2 class="mt-4 mb-3">Dados do Cliente</h2>
            <div class="mb-3">
                <label for="cliente_nome" class="form-label">Nome do Cliente:</label>
                <input type="text" class="form-control" id="cliente_nome" name="cliente_nome" value="<?php echo htmlspecialchars($old_values['cliente_nome']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="cliente_email" class="form-label">E-mail do Cliente:</label>
                <input type="email" class="form-control" id="cliente_email" name="cliente_email" value="<?php echo htmlspecialchars($old_values['cliente_email']); ?>">
            </div>

            <div class="mb-3">
                <label for="cliente_telefone" class="form-label">Telefone do Cliente:</label>
                <input type="text" class="form-control" id="cliente_telefone" name="cliente_telefone" value="<?php echo htmlspecialchars($old_values['cliente_telefone']); ?>">
            </div>

            <hr>

            <h2 class="mt-4 mb-3">Dados da Parte Ex-Adversa</h2>
            <div class="mb-3">
                <label for="parte_ex_adversa_nome" class="form-label">Nome da Parte Ex-Adversa:</label>
                <input type="text" class="form-control" id="parte_ex_adversa_nome" name="parte_ex_adversa_nome" value="<?php echo htmlspecialchars($old_values['parte_ex_adversa_nome']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="parte_ex_adversa_identidade" class="form-label">Identidade:</label>
                <input type="text" class="form-control" id="parte_ex_adversa_identidade" name="parte_ex_adversa_identidade" value="<?php echo htmlspecialchars($old_values['parte_ex_adversa_identidade']); ?>">
            </div>

            <div class="mb-3">
                <label for="parte_ex_adversa_cpf" class="form-label">CPF:</label>
                <input type="text" class="form-control" id="parte_ex_adversa_cpf" name="parte_ex_adversa_cpf" value="<?php echo htmlspecialchars($old_values['parte_ex_adversa_cpf']); ?>">
            </div>

            <div class="mb-3">
                <label for="parte_ex_adversa_email" class="form-label">E-mail:</label>
                <input type="email" class="form-control" id="parte_ex_adversa_email" name="parte_ex_adversa_email" value="<?php echo htmlspecialchars($old_values['parte_ex_adversa_email']); ?>">
            </div>

            <div class="mb-3">
                <label for="parte_ex_adversa_endereco" class="form-label">Endereço:</label>
                <textarea class="form-control" id="parte_ex_adversa_endereco" name="parte_ex_adversa_endereco" rows="3"><?php echo htmlspecialchars($old_values['parte_ex_adversa_endereco']); ?></textarea>
            </div>

            <div id="advogados-container">
                <h2 class="mt-4 mb-3">Advogados Envolvidos</h2>
                <?php
                foreach ($advogados_para_exibir as $key => $advogado):
                ?>
                    <div class="card p-3 mb-3 advogado-group">
                        <h4 class="mb-3">Advogado <?php echo $key + 1; ?></h4>
                        <div class="mb-3">
                            <label for="advogado_nome_<?php echo $key + 1; ?>" class="form-label">Nome:</label>
                            <input type="text" class="form-control" id="advogado_nome_<?php echo $key + 1; ?>" name="advogado_nome[]" value="<?php echo htmlspecialchars($advogado->advogado_nome ?? ''); ?>" <?php echo $key == 0 ? 'required' : ''; ?> aria-label="Nome do Advogado <?php echo $key + 1; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="advogado_oab_<?php echo $key + 1; ?>" class="form-label">OAB:</label>
                            <input type="text" class="form-control" id="advogado_oab_<?php echo $key + 1; ?>" name="advogado_oab[]" value="<?php echo htmlspecialchars($advogado->advogado_oab ?? ''); ?>" <?php echo $key == 0 ? 'required' : ''; ?> aria-label="OAB do Advogado <?php echo $key + 1; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="advogado_telefone_<?php echo $key + 1; ?>" class="form-label">Telefone:</label>
                            <input type="text" class="form-control" id="advogado_telefone_<?php echo $key + 1; ?>" name="advogado_telefone[]" value="<?php echo htmlspecialchars($advogado->advogado_telefone ?? ''); ?>" aria-label="Telefone do Advogado <?php echo $key + 1; ?>">
                        </div>
                        <div class="mb-3">
                            <label for="advogado_email_<?php echo $key + 1; ?>" class="form-label">E-mail:</label>
                            <input type="email" class="form-control" id="advogado_email_<?php echo $key + 1; ?>" name="advogado_email[]" value="<?php echo htmlspecialchars($advogado->advogado_email ?? ''); ?>" aria-label="E-mail do Advogado <?php echo $key + 1; ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" id="add-advogado" class="btn btn-secondary mb-4">Adicionar Mais Advogado</button>

            <button type="submit" class="btn btn-success w-100">Criar Processo</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let advogadoCount = document.querySelectorAll('.advogado-group').length;
            const advogadosContainer = document.getElementById('advogados-container');
            const addAdvogadoBtn = document.getElementById('add-advogado');

            addAdvogadoBtn.addEventListener('click', function() {
                advogadoCount++;
                const newAdvogadoDiv = document.createElement('div');
                newAdvogadoDiv.classList.add('card', 'p-3', 'mb-3', 'advogado-group');
                newAdvogadoDiv.innerHTML = `
                    <h4 class="mb-3">Advogado ${advogadoCount}</h4>
                    <div class="mb-3">
                        <label for="advogado_nome_${advogadoCount}" class="form-label">Nome:</label>
                        <input type="text" class="form-control" id="advogado_nome_${advogadoCount}" name="advogado_nome[]" required aria-label="Nome do Advogado ${advogadoCount}">
                    </div>
                    <div class="mb-3">
                        <label for="advogado_oab_${advogadoCount}" class="form-label">OAB:</label>
                        <input type="text" class="form-control" id="advogado_oab_${advogadoCount}" name="advogado_oab[]" required aria-label="OAB do Advogado ${advogadoCount}">
                    </div>
                    <div class="mb-3">
                        <label for="advogado_telefone_${advogadoCount}" class="form-label">Telefone:</label>
                        <input type="text" class="form-control" id="advogado_telefone_${advogadoCount}" name="advogado_telefone[]" aria-label="Telefone do Advogado ${advogadoCount}">
                    </div>
                    <div class="mb-3">
                        <label for="advogado_email_${advogadoCount}" class="form-label">E-mail:</label>
                        <input type="email" class="form-control" id="advogado_email_${advogadoCount}" name="advogado_email[]" aria-label="E-mail do Advogado ${advogadoCount}">
                    </div>
                `;
                advogadosContainer.appendChild(newAdvogadoDiv);
            });
        });
    </script>
</body>
</html>