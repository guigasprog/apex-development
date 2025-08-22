/*
=================================================================
SCRIPT PARA O BANCO DE DADOS MASTER (db_master)
Este banco gerencia os tenants e os usuários do sistema.
=================================================================
*/

-- Use um nome de banco de dados específico para o sistema master
CREATE DATABASE IF NOT EXISTS `db_master` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_master`;

-- Tabela para gerenciar os clientes (tenants) do seu SaaS
CREATE TABLE tenants (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nome_loja` VARCHAR(255) NOT NULL COMMENT 'Nome fantasia da loja do tenant',
    `schema_name` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome do banco de dados (schema) exclusivo deste tenant',
    `status` ENUM('ativo', 'inativo', 'suspenso') NOT NULL DEFAULT 'ativo',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela para gerenciar os usuários que podem logar no sistema
CREATE TABLE users (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NULL COMMENT 'Se NULO, é um Super Admin do sistema. Se preenchido, pertence a um tenant.',
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Inserindo o Super Admin do sistema (senha: admin123)
-- Lembre-se de usar um gerador de hash para senhas reais em produção.
INSERT INTO users (`tenant_id`, `nome`, `email`, `password_hash`)
VALUES (NULL, 'Super Admin', 'admin@seusistema.com', '$2y$10$FqjLJgz3yDKecoW/uEX0W.Az5Rj7TJuXsGqqLJOEjwoksj1jKTYBm');

/*
=================================================================
SCRIPT TEMPLATE PARA O SCHEMA DE CADA NOVO TENANT
Sua aplicação deve executar este script para criar o banco de cada cliente.
=================================================================
*/

CREATE TABLE `enderecos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `logradouro` VARCHAR(255) NULL,
  `numero` VARCHAR(20) NULL,
  `complemento` VARCHAR(100) NULL,
  `bairro` VARCHAR(100) NULL,
  `cidade` VARCHAR(100) NULL,
  `estado` CHAR(2) NULL,
  `cep` VARCHAR(9) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `clientes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `endereco_id` INT UNSIGNED NULL,
  `nome` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `telefone` VARCHAR(20) NULL,
  `cpf` VARCHAR(14) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `reset_token` VARCHAR(255) NULL,
  `reset_token_expires` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_cliente_endereco`
    FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `categorias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `produtos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT UNSIGNED NULL,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `sobre_o_item` TEXT NULL,
  `preco` DECIMAL(10, 2) NOT NULL,
  `peso_kg` DECIMAL(10, 3) NOT NULL DEFAULT 0.300,
  `comprimento_cm` INT UNSIGNED NOT NULL DEFAULT 16,
  `altura_cm` INT UNSIGNED NOT NULL DEFAULT 2,
  `largura_cm` INT UNSIGNED NOT NULL DEFAULT 11,
  `validade` DATE NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_produto_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedidos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT UNSIGNED NOT NULL,
  `total` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pendente', 'pago', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_pedido_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `estoque` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT UNSIGNED NOT NULL UNIQUE COMMENT 'Cada produto só pode ter uma entrada no estoque. Use transações para atualizar.',
  `quantidade` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT `fk_estoque_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `imagens_produto` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `descricao` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT `fk_imagem_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedido_itens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT UNSIGNED NOT NULL,
  `produto_id` INT UNSIGNED NOT NULL,
  `quantidade` INT UNSIGNED NOT NULL,
  `preco_unitario` DECIMAL(10, 2) NOT NULL COMMENT 'Preço do produto no momento da compra',

  CONSTRAINT `fk_item_pedido`
    FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_item_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `produto_interacoes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT UNSIGNED NULL,
  `tipo` ENUM('view', 'search') NOT NULL,
  `texto_busca` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  CONSTRAINT `fk_interacao_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

/*
=================================================================
CRIAÇÃO DE ÍNDICES PARA MELHORAR A PERFORMANCE DAS CONSULTAS
=================================================================
*/

-- Índices para pesquisas e filtros comuns
CREATE INDEX idx_clientes_email ON clientes(email);
CREATE INDEX idx_clientes_cpf ON clientes(cpf);
CREATE INDEX idx_produtos_nome ON produtos(nome);
CREATE INDEX idx_pedidos_status ON pedidos(status);
CREATE INDEX idx_pedidos_cliente_id ON pedidos(cliente_id);