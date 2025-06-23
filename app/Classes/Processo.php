<?php
// app/Classes/Processo.php

require_once __DIR__ . '/../Conexao.php';

class Processo {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getInstance()->getPDO();
    }

    /**
     * Insere um novo processo no banco de dados.
     *
     * @param array $dadosProcesso Array associativo com os dados principais do processo.
     * @param array $dadosCliente Array associativo com os dados do cliente.
     * @param array $dadosParteExAdversa Array associativo com os dados da parte ex-adversa.
     * @param array $advogados Array de arrays associativos com os dados dos advogados.
     * @return int|bool O ID do processo recém-criado em caso de sucesso, false caso contrário.
     */
    public function criarProcesso(array $dadosProcesso, array $dadosCliente, array $dadosParteExAdversa, array $advogados): int|bool {
        // Inicia uma transação para garantir a atomicidade das operações
        $this->pdo->beginTransaction();

        try {
            // 1. Inserir Cliente
            $stmtCliente = $this->pdo->prepare("INSERT INTO clientes (nome, email, telefone) VALUES (:nome, :email, :telefone)");
            $stmtCliente->execute([
                ':nome' => $dadosCliente['nome'],
                ':email' => $dadosCliente['email'] ?? null,
                ':telefone' => $dadosCliente['telefone'] ?? null
            ]);
            $clienteId = $this->pdo->lastInsertId();

            // 2. Inserir Parte Ex-Adversa
            $stmtParte = $this->pdo->prepare("INSERT INTO partes_ex_adversas (nome, identidade, cpf, email, endereco) VALUES (:nome, :identidade, :cpf, :email, :endereco)");
            $stmtParte->execute([
                ':nome' => $dadosParteExAdversa['nome'],
                ':identidade' => $dadosParteExAdversa['identidade'] ?? null,
                ':cpf' => $dadosParteExAdversa['cpf'] ?? null,
                ':email' => $dadosParteExAdversa['email'] ?? null,
                ':endereco' => $dadosParteExAdversa['endereco'] ?? null
            ]);
            $parteExAdversaId = $this->pdo->lastInsertId();

            // 3. Inserir Processo
            $stmtProcesso = $this->pdo->prepare("INSERT INTO processos (cliente_id, numero_processo, data_protocolo, vara_tribunal_origem, natureza_acao, parte_ex_adversa_id) VALUES (:cliente_id, :numero_processo, :data_protocolo, :vara_tribunal_origem, :natureza_acao, :parte_ex_adversa_id)");
            $stmtProcesso->execute([
                ':cliente_id' => $clienteId,
                ':numero_processo' => $dadosProcesso['numero_processo'],
                ':data_protocolo' => $dadosProcesso['data_protocolo'],
                ':vara_tribunal_origem' => $dadosProcesso['vara_tribunal_origem'],
                ':natureza_acao' => $dadosProcesso['natureza_acao'],
                ':parte_ex_adversa_id' => $parteExAdversaId
            ]);
            $processoId = $this->pdo->lastInsertId();

            // 4. Inserir Advogados e associá-los ao processo (se houver)
            if (!empty($advogados) && is_array($advogados)) {
                foreach ($advogados as $advogado) {
                    if (empty($advogado['oab'])) { // Pula advogados sem OAB, eles não serão cadastrados/associados
                        continue;
                    }
                    // Verifica se o advogado já existe pela OAB para evitar duplicidade
                    $stmtCheckAdv = $this->pdo->prepare("SELECT id FROM advogados WHERE oab = :oab");
                    $stmtCheckAdv->execute([':oab' => $advogado['oab']]);
                    $advogadoExistente = $stmtCheckAdv->fetch();

                    $advogadoId = null;
                    if ($advogadoExistente) {
                        $advogadoId = $advogadoExistente->id;
                        // Opcional: Atualizar dados do advogado existente se houver campos a serem atualizados
                        $stmtUpdateAdv = $this->pdo->prepare("UPDATE advogados SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id");
                        $stmtUpdateAdv->execute([
                            ':nome' => $advogado['nome'],
                            ':telefone' => $advogado['telefone'] ?? null,
                            ':email' => $advogado['email'] ?? null,
                            ':id' => $advogadoId
                        ]);
                    } else {
                        // Insere o advogado se não existir
                        $stmtAdv = $this->pdo->prepare("INSERT INTO advogados (nome, oab, telefone, email) VALUES (:nome_adv, :oab_adv, :telefone_adv, :email_adv)");
                        $stmtAdv->execute([
                            ':nome_adv' => $advogado['nome'],
                            ':oab_adv' => $advogado['oab'],
                            ':telefone_adv' => $advogado['telefone'] ?? null,
                            ':email_adv' => $advogado['email'] ?? null
                        ]);
                        $advogadoId = $this->pdo->lastInsertId();
                    }

                    // Associa o advogado ao processo
                    $stmtProcAdv = $this->pdo->prepare("INSERT INTO processo_advogado (processo_id, advogado_id) VALUES (:processo_id, :advogado_id)");
                    $stmtProcAdv->execute([
                        ':processo_id' => $processoId,
                        ':advogado_id' => $advogadoId
                    ]);
                }
            }

            $this->pdo->commit(); // Confirma todas as operações
            return $processoId; // Retorna o ID do processo criado
        } catch (PDOException $e) {
            $this->pdo->rollBack(); // Desfaz todas as operações em caso de erro
            error_log("Erro ao criar processo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todos os processos com informações do cliente e parte ex-adversa.
     *
     * @return array Um array de objetos, onde cada objeto representa um processo.
     */
    public function listarProcessos(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    p.id,
                    p.numero_processo,
                    p.data_protocolo,
                    p.natureza_acao,
                    c.nome AS cliente_nome,
                    pea.nome AS parte_ex_adversa_nome
                FROM
                    processos p
                JOIN
                    clientes c ON p.cliente_id = c.id
                JOIN
                    partes_ex_adversas pea ON p.parte_ex_adversa_id = pea.id
                ORDER BY
                    p.data_protocolo DESC, p.id DESC
            ");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao listar processos: " . $e->getMessage());
            return []; // Retorna um array vazio em caso de erro
        }
    }

    /**
     * Obtém os dados de um processo específico pelo ID, incluindo cliente, parte ex-adversa e advogados.
     *
     * @param int $id O ID do processo a ser buscado.
     * @return object|null Um objeto com os dados do processo ou null se não encontrado.
     */
    public function getProcessoById(int $id) {
        try {
            $stmtProcesso = $this->pdo->prepare("
                SELECT
                    p.id AS processo_id,
                    p.numero_processo,
                    p.data_protocolo,
                    p.vara_tribunal_origem,
                    p.natureza_acao,
                    c.id AS cliente_id,
                    c.nome AS cliente_nome,
                    c.email AS cliente_email,
                    c.telefone AS cliente_telefone,
                    pea.id AS parte_ex_adversa_id,
                    pea.nome AS parte_ex_adversa_nome,
                    pea.identidade AS parte_ex_adversa_identidade,
                    pea.cpf AS parte_ex_adversa_cpf,
                    pea.email AS parte_ex_adversa_email,
                    pea.endereco AS parte_ex_adversa_endereco
                FROM
                    processos p
                JOIN
                    clientes c ON p.cliente_id = c.id
                JOIN
                    partes_ex_adversas pea ON p.parte_ex_adversa_id = pea.id
                WHERE
                    p.id = :id
            ");
            $stmtProcesso->execute([':id' => $id]);
            $processo = $stmtProcesso->fetch();

            if ($processo) {
                // Obter advogados associados a este processo
                $stmtAdvogados = $this->pdo->prepare("
                    SELECT
                        a.id AS advogado_id,
                        a.nome AS advogado_nome,
                        a.oab AS advogado_oab,
                        a.telefone AS advogado_telefone,
                        a.email AS advogado_email
                    FROM
                        processo_advogado pa
                    JOIN
                        advogados a ON pa.advogado_id = a.id
                    WHERE
                        pa.processo_id = :processo_id
                ");
                $stmtAdvogados->execute([':processo_id' => $processo->processo_id]);
                $processo->advogados = $stmtAdvogados->fetchAll();
            }

            return $processo;

        } catch (PDOException $e) {
            error_log("Erro ao buscar processo por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Atualiza os dados de um processo existente.
     *
     * @param int $id O ID do processo a ser atualizado.
     * @param array $dadosProcesso Array associativo com os dados principais do processo.
     * @param array $dadosCliente Array associativo com os dados do cliente.
     * @param array $dadosParteExAdversa Array associativo com os dados da parte ex-adversa.
     * @param array $advogados Array de arrays associativos com os dados dos advogados.
     * @return bool True em caso de sucesso, false caso contrário.
     */
    public function atualizarProcesso(int $id, array $dadosProcesso, array $dadosCliente, array $dadosParteExAdversa, array $advogados): bool {
        $this->pdo->beginTransaction();

        try {
            // 1. Obter IDs existentes de cliente e parte ex-adversa do processo
            $stmtGetIds = $this->pdo->prepare("SELECT cliente_id, parte_ex_adversa_id FROM processos WHERE id = :id");
            $stmtGetIds->execute([':id' => $id]);
            $currentIds = $stmtGetIds->fetch();

            if (!$currentIds) {
                $this->pdo->rollBack();
                return false; // Processo não encontrado
            }

            // 2. Atualizar Cliente
            $stmtUpdateCliente = $this->pdo->prepare("UPDATE clientes SET nome = :nome, email = :email, telefone = :telefone WHERE id = :id");
            $stmtUpdateCliente->execute([
                ':nome' => $dadosCliente['nome'], // Usando $dadosCliente aqui
                ':email' => $dadosCliente['email'] ?? null,
                ':telefone' => $dadosCliente['telefone'] ?? null,
                ':id' => $currentIds->cliente_id
            ]);

            // 3. Atualizar Parte Ex-Adversa
            $stmtUpdateParte = $this->pdo->prepare("UPDATE partes_ex_adversas SET nome = :nome, identidade = :identidade, cpf = :cpf, email = :email, endereco = :endereco WHERE id = :id");
            $stmtUpdateParte->execute([
                ':nome' => $dadosParteExAdversa['nome'], // Usando $dadosParteExAdversa aqui
                ':identidade' => $dadosParteExAdversa['identidade'] ?? null,
                ':cpf' => $dadosParteExAdversa['cpf'] ?? null,
                ':email' => $dadosParteExAdversa['email'] ?? null,
                ':endereco' => $dadosParteExAdversa['endereco'] ?? null,
                ':id' => $currentIds->parte_ex_adversa_id
            ]);

            // 4. Atualizar Processo
            $stmtUpdateProcesso = $this->pdo->prepare("UPDATE processos SET numero_processo = :numero_processo, data_protocolo = :data_protocolo, vara_tribunal_origem = :vara_tribunal_origem, natureza_acao = :natureza_acao WHERE id = :id");
            $stmtUpdateProcesso->execute([
                ':numero_processo' => $dadosProcesso['numero_processo'],
                ':data_protocolo' => $dadosProcesso['data_protocolo'],
                ':vara_tribunal_origem' => $dadosProcesso['vara_tribunal_origem'],
                ':natureza_acao' => $dadosProcesso['natureza_acao'],
                ':id' => $id
            ]);

            // 5. Atualizar Advogados
            // Lógica para advogados: deletar antigas associações e inserir/atualizar novas.
            // Isso simplifica o manuseio de advogados que foram adicionados ou removidos.

            // Deleta associações existentes para este processo
            $stmtDeleteAdvAssoc = $this->pdo->prepare("DELETE FROM processo_advogado WHERE processo_id = :processo_id");
            $stmtDeleteAdvAssoc->execute([':processo_id' => $id]);

            if (!empty($advogados) && is_array($advogados)) {
                foreach ($advogados as $advogado) {
                    if (empty($advogado['oab'])) {
                        continue; // Pula advogados sem OAB, eles não serão cadastrados/associados
                    }
                    // Verifica se o advogado já existe pela OAB
                    $stmtCheckAdv = $this->pdo->prepare("SELECT id FROM advogados WHERE oab = :oab");
                    $stmtCheckAdv->execute([':oab' => $advogado['oab']]);
                    $advogadoExistente = $stmtCheckAdv->fetch();

                    $advogadoId = null;
                    if ($advogadoExistente) {
                        $advogadoId = $advogadoExistente->id;
                        // Atualiza os dados do advogado existente, se necessário
                        $stmtUpdateAdv = $this->pdo->prepare("UPDATE advogados SET nome = :nome, telefone = :telefone, email = :email WHERE id = :id");
                        $stmtUpdateAdv->execute([
                            ':nome' => $advogado['nome'],
                            ':telefone' => $advogado['telefone'] ?? null,
                            ':email' => $advogado['email'] ?? null,
                            ':id' => $advogadoId
                        ]);
                    } else {
                        // Insere o advogado se não existir
                        $stmtAdv = $this->pdo->prepare("INSERT INTO advogados (nome, oab, telefone, email) VALUES (:nome_adv, :oab_adv, :telefone_adv, :email_adv)");
                        $stmtAdv->execute([
                            ':nome_adv' => $advogado['nome'],
                            ':oab_adv' => $advogado['oab'],
                            ':telefone_adv' => $advogado['telefone'] ?? null,
                            ':email_adv' => $advogado['email'] ?? null
                        ]);
                        $advogadoId = $this->pdo->lastInsertId();
                    }

                    // Associa o advogado ao processo
                    $stmtProcAdv = $this->pdo->prepare("INSERT INTO processo_advogado (processo_id, advogado_id) VALUES (:processo_id, :advogado_id)");
                    $stmtProcAdv->execute([
                        ':processo_id' => $id,
                        ':advogado_id' => $advogadoId
                    ]);
                }
            }

            $this->pdo->commit(); // Confirma todas as operações
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack(); // Desfaz todas as operações em caso de erro
            error_log("Erro ao atualizar processo: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deleta um processo e suas informações relacionadas.
     * Devido ao ON DELETE CASCADE no DB, ele automaticamente deleta movimentacoes e associações de advogados.
     * Também deleta o cliente e a parte ex-adversa se não estiverem associados a outros processos.
     *
     * @param int $id O ID do processo a ser deletado.
     * @return bool True em caso de sucesso, false caso contrário.
     */
    public function deletarProcesso(int $id): bool {
        $this->pdo->beginTransaction();
        try {
            // Primeiro, obtemos os IDs do cliente e da parte ex-adversa associados a este processo.
            // Precisamos fazer isso ANTES de deletar o processo, pois o CASCADE vai limpar as FKs.
            $stmtGetAssociatedIds = $this->pdo->prepare("SELECT cliente_id, parte_ex_adversa_id FROM processos WHERE id = :id");
            $stmtGetAssociatedIds->execute([':id' => $id]);
            $associatedIds = $stmtGetAssociatedIds->fetch(PDO::FETCH_ASSOC);

            if (!$associatedIds) {
                $this->pdo->rollBack();
                return false; // Processo não encontrado
            }

            // Deleta o processo (e, em cascata, suas movimentações e associações de advogados)
            $stmtDeleteProcesso = $this->pdo->prepare("DELETE FROM processos WHERE id = :id");
            $stmtDeleteProcesso->execute([':id' => $id]);

            // Opcional: Deletar cliente se não estiver mais associado a nenhum processo
            if ($associatedIds['cliente_id']) {
                $stmtCheckCliente = $this->pdo->prepare("SELECT COUNT(*) FROM processos WHERE cliente_id = :cliente_id");
                $stmtCheckCliente->execute([':cliente_id' => $associatedIds['cliente_id']]);
                if ($stmtCheckCliente->fetchColumn() == 0) {
                    $stmtDeleteCliente = $this->pdo->prepare("DELETE FROM clientes WHERE id = :id");
                    $stmtDeleteCliente->execute([':id' => $associatedIds['cliente_id']]);
                }
            }

            // Opcional: Deletar parte ex-adversa se não estiver mais associada a nenhum processo
            if ($associatedIds['parte_ex_adversa_id']) {
                $stmtCheckParte = $this->pdo->prepare("SELECT COUNT(*) FROM processos WHERE parte_ex_adversa_id = :parte_ex_adversa_id");
                $stmtCheckParte->execute([':parte_ex_adversa_id' => $associatedIds['parte_ex_adversa_id']]);
                if ($stmtCheckParte->fetchColumn() == 0) {
                    $stmtDeleteParte = $this->pdo->prepare("DELETE FROM partes_ex_adversas WHERE id = :id");
                    $stmtDeleteParte->execute([':id' => $associatedIds['parte_ex_adversa_id']]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Erro ao deletar processo: " . $e->getMessage());
            return false;
        }
    }
}