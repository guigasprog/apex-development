-- =================================================================
-- BANCO DE DADOS MASTER (CONTROLE DOS TENANTS)
-- =================================================================
DROP DATABASE IF EXISTS `db_master`;
CREATE DATABASE `db_master` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_master`;

CREATE TABLE `tenants` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nome_loja` VARCHAR(255) NOT NULL,
    `schema_name` VARCHAR(100) NOT NULL UNIQUE,
    `status` ENUM('ativo', 'inativo', 'suspenso') NOT NULL DEFAULT 'ativo',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NULL,
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`tenant_id`) REFERENCES `tenants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Usuário Super Admin do sistema
INSERT INTO users (`tenant_id`, `nome`, `email`, `password_hash`)
VALUES (NULL, 'Super Admin', 'admin@seusistema.com', '$2y$10$FqjLJgz3yDKecoW/uEX0W.Az5Rj7TJuXsGqqLJOEjwoksj1jKTYBm');


-- =================================================================
-- TEMPLATE PARA O BANCO DE DADOS DE CADA NOVO CLIENTE (TENANT)
-- =================================================================

CREATE TABLE `enderecos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
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
    `tenant_id` INT UNSIGNED NOT NULL,
    `endereco_id` INT UNSIGNED NULL,
    `nome` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `telefone` VARCHAR(20) NULL,
    `cpf` VARCHAR(14) NOT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `reset_token` VARCHAR(255) NULL,
    `reset_token_expires` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cliente_endereco` FOREIGN KEY (`endereco_id`) REFERENCES `enderecos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `categorias` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `descricao` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `produtos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `categoria_id` INT UNSIGNED NULL,
    `tipo` ENUM('PRODUTO', 'SERVICO') NOT NULL DEFAULT 'PRODUTO',
    `nome` VARCHAR(255) NOT NULL,
    `descricao` TEXT NULL,
    `sobre_o_item` TEXT NULL,
    `preco` DECIMAL(10, 2) NOT NULL,
    `peso_kg` DECIMAL(10, 3) NULL,
    `comprimento_cm` INT UNSIGNED NULL,
    `largura_cm` INT UNSIGNED NULL,
    `altura_cm` INT UNSIGNED NULL,
    `validade` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedidos` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `cliente_id` INT UNSIGNED NOT NULL,
    `total` DECIMAL(10, 2) NOT NULL,
    `status` ENUM('pendente', 'pago', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'pendente',
    `data_pedido` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `estoque` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NOT NULL UNIQUE,
    `quantidade` INT NOT NULL DEFAULT 0,
    `data_entrada` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_estoque_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `imagens_produto` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NOT NULL,
    `image_url` VARCHAR(255) NOT NULL,
    `descricao` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedido_itens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `pedido_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NOT NULL,
    `quantidade` INT UNSIGNED NOT NULL,
    `preco_unitario` DECIMAL(10, 2) NOT NULL,
    CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `produto_interacoes` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `tenant_id` INT UNSIGNED NOT NULL,
    `produto_id` INT UNSIGNED NULL,
    `tipo` ENUM('view', 'search') NOT NULL,
    `texto_busca` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_interacao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =================================================================
-- ÍNDICES PARA OTIMIZAÇÃO DE CONSULTAS (MUITO IMPORTANTE!)
-- =================================================================

-- Índices compostos para garantir performance em ambiente multi-tenant
CREATE INDEX `idx_tenant_id` ON `enderecos`(`tenant_id`);
CREATE UNIQUE INDEX `idx_tenant_email` ON `clientes`(`tenant_id`, `email`);
CREATE UNIQUE INDEX `idx_tenant_cpf` ON `clientes`(`tenant_id`, `cpf`);
CREATE INDEX `idx_tenant_nome` ON `categorias`(`tenant_id`, `nome`);
CREATE INDEX `idx_tenant_nome_produto` ON `produtos`(`tenant_id`, `nome`);
CREATE INDEX `idx_tenant_tipo_produto` ON `produtos`(`tenant_id`, `tipo`);
CREATE INDEX `idx_tenant_cliente` ON `pedidos`(`tenant_id`, `cliente_id`);
CREATE INDEX `idx_tenant_status` ON `pedidos`(`tenant_id`, `status`);
CREATE INDEX `idx_tenant_produto` ON `estoque`(`tenant_id`, `produto_id`);
CREATE INDEX `idx_tenant_produto_imagem` ON `imagens_produto`(`tenant_id`, `produto_id`);
CREATE INDEX `idx_tenant_pedido` ON `pedido_itens`(`tenant_id`, `pedido_id`);
CREATE INDEX `idx_tenant_produto_interacao` ON `produto_interacoes`(`tenant_id`, `produto_id`);