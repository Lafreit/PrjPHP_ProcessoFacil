<?php
// app/Classes/Movimentacao.php

require_once __DIR__ . '/../Conexao.php';

class Movimentacao {
    private $pdo;

    public function __construct() {
        $this->pdo = Conexao::getInstance()->getPDO();
    }

    /**
     * Cria uma nova movimentação para um processo específico.
     *
     * @param int $processoId O ID do processo ao qual a movimentação pertence.
     * @param array $dadosMovimentacao Array associativo com os dados da movimentação.
     * @return bool True em caso de sucesso, false caso contrário.
     */
    public function criarMovimentacao(int $processoId, array $dadosMovimentacao): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO movimentacoes (processo_id, data_movimentacao, descricao, instancia, tipo)
                VALUES (:processo_id, :data_movimentacao, :descricao, :instancia, :tipo)
            ");

            return $stmt->execute([
                ':processo_id' => $processoId,
                ':data_movimentacao' => $dadosMovimentacao['data_movimentacao'],
                ':descricao' => $dadosMovimentacao['descricao'],
                ':instancia' => $dadosMovimentacao['instancia'],
                ':tipo' => $dadosMovimentacao['tipo'] ?? null // Tipo pode ser opcional
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao criar movimentação: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém uma movimentação específica pelo ID.
     *
     * @param int $id O ID da movimentação.
     * @return object|null Objeto com os dados da movimentação ou null se não encontrada.
     */
    public function getMovimentacaoById(int $id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM movimentacoes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Erro ao buscar movimentação por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lista todas as movimentações para um processo específico.
     *
     * @param int $processoId O ID do processo.
     * @return array Um array de objetos, cada um representando uma movimentação.
     */
    public function listarMovimentacoesPorProcesso(int $processoId): array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM movimentacoes WHERE processo_id = :processo_id ORDER BY data_movimentacao DESC");
            $stmt->execute([':processo_id' => $processoId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erro ao listar movimentações por processo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Atualiza uma movimentação existente.
     *
     * @param int $id O ID da movimentação a ser atualizada.
     * @param array $dadosMovimentacao Array associativo com os novos dados da movimentação.
     * @return bool True em caso de sucesso, false caso contrário.
     */
    public function atualizarMovimentacao(int $id, array $dadosMovimentacao): bool {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE movimentacoes
                SET data_movimentacao = :data_movimentacao,
                    descricao = :descricao,
                    instancia = :instancia,
                    tipo = :tipo
                WHERE id = :id
            ");

            return $stmt->execute([
                ':data_movimentacao' => $dadosMovimentacao['data_movimentacao'],
                ':descricao' => $dadosMovimentacao['descricao'],
                ':instancia' => $dadosMovimentacao['instancia'],
                ':tipo' => $dadosMovimentacao['tipo'] ?? null,
                ':id' => $id
            ]);
        } catch (PDOException $e) {
            error_log("Erro ao atualizar movimentação: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Deleta uma movimentação específica.
     *
     * @param int $id O ID da movimentação a ser deletada.
     * @return bool True em caso de sucesso, false caso contrário.
     */
    public function deletarMovimentacao(int $id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM movimentacoes WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Erro ao deletar movimentação: " . $e->getMessage());
            return false;
        }
    }
}
?>