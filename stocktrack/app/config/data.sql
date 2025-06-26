
DROP DATABASE IF EXISTS `vibe_vault`;

CREATE DATABASE `vibe_vault` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `vibe_vault`;

CREATE TABLE `enderecos` (
  `idEndereco` INT AUTO_INCREMENT PRIMARY KEY,
  `logradouro` VARCHAR(100) NULL,
  `numero` VARCHAR(10) NULL,
  `complemento` VARCHAR(50) NULL,
  `bairro` VARCHAR(50) NULL,
  `cidade` VARCHAR(50) NULL,
  `estado` VARCHAR(2) NULL,
  `cep` VARCHAR(9) NULL
) ENGINE=InnoDB;

CREATE TABLE `clientes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `telefone` VARCHAR(20) NULL,
  `cpf` VARCHAR(14) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `endereco_id` INT NULL,
  CONSTRAINT `fk_cliente_endereco`
    FOREIGN KEY (`endereco_id`)
    REFERENCES `enderecos` (`idEndereco`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `categorias` (
  `idCategoria` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `sobre_o_item` TEXT NULL,
  `validade` DATE NULL,
  `preco` DECIMAL(10, 2) NOT NULL,
  `peso_kg` DECIMAL(10, 2) NOT NULL,
  `comprimento_cm` INT NOT NULL,
  `altura_cm` INT NOT NULL,
  `largura_cm` INT NOT NULL,
  `categoria_id` INT NULL,
  CONSTRAINT `fk_produto_categoria`
    FOREIGN KEY (`categoria_id`)
    REFERENCES `categorias` (`idCategoria`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `data_pedido` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `total` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pendente', 'concluído', 'cancelado', 'pagamento efetuado', 'enviado para entrega') DEFAULT 'pendente',
  CONSTRAINT `fk_pedido_cliente`
    FOREIGN KEY (`cliente_id`)
    REFERENCES `clientes` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `estoque` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `quantidade` INT NOT NULL,
  `data_entrada` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_estoque_produto`
    FOREIGN KEY (`produto_id`)
    REFERENCES `produtos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `imagens_produto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `produto_id` INT NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `descricao` VARCHAR(255) NULL,
  CONSTRAINT `fk_imagem_produto`
    FOREIGN KEY (`produto_id`)
    REFERENCES `produtos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `pedido_produto` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `produto_id` INT NOT NULL,
  `quantidade` INT NOT NULL,
  CONSTRAINT `fk_pedprod_pedido`
    FOREIGN KEY (`pedido_id`)
    REFERENCES `pedidos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pedprod_produto`
    FOREIGN KEY (`produto_id`)
    REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;
