-- Criar o banco de dados
CREATE DATABASE IF NOT EXISTS ProcessoFacil;
USE ProcessoFacil;

-- Tabela para os Clientes (separamos para reuso e organização)
CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    telefone VARCHAR(20)
    -- Adicionar outros campos do cliente se necessário (ex: CPF, endereço)
);

-- Tabela para as Partes Ex-Adversas
CREATE TABLE IF NOT EXISTS partes_ex_adversas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    identidade VARCHAR(50),
    cpf VARCHAR(14) UNIQUE,
    email VARCHAR(255),
    endereco TEXT
);

-- Tabela para os Advogados
CREATE TABLE IF NOT EXISTS advogados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    oab VARCHAR(50) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    email VARCHAR(255)
);

-- Tabela Principal de Processos
CREATE TABLE IF NOT EXISTS processos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL, -- Chave estrangeira para clientes
    numero_processo VARCHAR(100) NOT NULL UNIQUE,
    data_protocolo DATE NOT NULL,
    vara_tribunal_origem VARCHAR(255) NOT NULL,
    natureza_acao VARCHAR(255) NOT NULL,
    parte_ex_adversa_id INT NOT NULL, -- Chave estrangeira para partes_ex_adversas
    -- foreign key
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (parte_ex_adversa_id) REFERENCES partes_ex_adversas(id) ON DELETE CASCADE
);

-- Tabela para associar Advogados aos Processos (Muitos para Muitos)
CREATE TABLE IF NOT EXISTS processo_advogado (
    processo_id INT NOT NULL,
    advogado_id INT NOT NULL,
    PRIMARY KEY (processo_id, advogado_id),
    FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE CASCADE,
    FOREIGN KEY (advogado_id) REFERENCES advogados(id) ON DELETE CASCADE
);

-- Tabela para Movimentações (Judiciais e Administrativas)
CREATE TABLE IF NOT EXISTS movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    processo_id INT NOT NULL,
    data_movimentacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    descricao TEXT NOT NULL,
    instancia ENUM('primeiro grau', 'segundo grau', 'especial', 'extraordinaria', 'administrativa') NOT NULL,
    tipo VARCHAR(100), -- Ex: "Despacho", "Sentença", "Protocolo", "Recurso", etc.
    FOREIGN KEY (processo_id) REFERENCES processos(id) ON DELETE CASCADE
);