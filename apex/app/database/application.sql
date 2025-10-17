-- =================================================================
-- TEMPLATE PARA O BANCO DE DADOS DE CADA NOVO CLIENTE (TENANT) (VERSÃO SQLITE)
-- =================================================================

CREATE TABLE "enderecos" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "logradouro" TEXT NULL,
  "numero" TEXT NULL,
  "complemento" TEXT NULL,
  "bairro" TEXT NULL,
  "cidade" TEXT NULL,
  "estado" TEXT NULL,
  "cep" TEXT NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "clientes" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "endereco_id" INTEGER NULL,
  "nome" TEXT NOT NULL,
  "email" TEXT NOT NULL,
  "telefone" TEXT NULL,
  "cpf" TEXT NOT NULL,
  "password_hash" TEXT NOT NULL,
  "reset_token" TEXT NULL,
  "reset_token_expires" DATETIME NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("endereco_id") REFERENCES "enderecos" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE "categorias" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nome" TEXT NOT NULL,
  "descricao" TEXT NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE "produtos" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "categoria_id" INTEGER NULL,
  "tipo" TEXT CHECK( "tipo" IN ('PRODUTO', 'SERVICO') ) NOT NULL DEFAULT 'PRODUTO', -- ALTERAÇÃO: ENUM para TEXT+CHECK
  "nome" TEXT NOT NULL,
  "descricao" TEXT NULL,
  "sobre_o_item" TEXT NULL,
  "preco" NUMERIC NOT NULL,
  "peso_kg" NUMERIC NULL,
  "comprimento_cm" INTEGER NULL,
  "largura_cm" INTEGER NULL,
  "altura_cm" INTEGER NULL,
  "validade" DATETIME NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("categoria_id") REFERENCES "categorias" ("id") ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE "pedidos" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "cliente_id" INTEGER NOT NULL,
  "total" NUMERIC NOT NULL,
  "status" TEXT CHECK( "status" IN ('pendente', 'pago', 'enviado', 'entregue', 'cancelado') ) NOT NULL DEFAULT 'pendente', -- ALTERAÇÃO: ENUM para TEXT+CHECK
  "data_pedido" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("cliente_id") REFERENCES "clientes" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE "estoque" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "produto_id" INTEGER NOT NULL UNIQUE,
  "quantidade" INTEGER NOT NULL DEFAULT 0,
  "data_entrada" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("produto_id") REFERENCES "produtos" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE "imagens_produto" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "produto_id" INTEGER NOT NULL,
  "image_url" TEXT NOT NULL,
  "descricao" TEXT NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("produto_id") REFERENCES "produtos" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE "pedido_itens" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "pedido_id" INTEGER NOT NULL,
  "produto_id" INTEGER NOT NULL,
  "quantidade" INTEGER NOT NULL,
  "preco_unitario" NUMERIC NOT NULL,
  FOREIGN KEY ("pedido_id") REFERENCES "pedidos" ("id") ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY ("produto_id") REFERENCES "produtos" ("id") ON DELETE RESTRICT ON UPDATE CASCADE
);

CREATE TABLE "produto_interacoes" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "produto_id" INTEGER NULL,
  "tipo" TEXT CHECK( "tipo" IN ('view', 'search') ) NOT NULL, -- ALTERAÇÃO: ENUM para TEXT+CHECK
  "texto_busca" TEXT NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("produto_id") REFERENCES "produtos" ("id") ON DELETE CASCADE ON UPDATE CASCADE
);

-- Índices otimizados
CREATE UNIQUE INDEX "idx_email_cliente" ON "clientes"("email");
CREATE UNIQUE INDEX "idx_cpf_cliente" ON "clientes"("cpf");
CREATE INDEX "idx_nome_categoria" ON "categorias"("nome");
CREATE INDEX "idx_nome_produto" ON "produtos"("nome");
CREATE INDEX "idx_tipo_produto" ON "produtos"("tipo");
CREATE INDEX "idx_cliente_pedido" ON "pedidos"("cliente_id");
CREATE INDEX "idx_status_pedido" ON "pedidos"("status");
CREATE INDEX "idx_produto_imagem" ON "imagens_produto"("produto_id");
CREATE INDEX "idx_pedido_item" ON "pedido_itens"("pedido_id");
CREATE INDEX "idx_produto_interacao" ON "produto_interacoes"("produto_id");

-- ALTERAÇÃO: Triggers para simular o ON UPDATE CURRENT_TIMESTAMP
CREATE TRIGGER "trigger_enderecos_updated_at" AFTER UPDATE ON "enderecos"
BEGIN
    UPDATE "enderecos" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;

CREATE TRIGGER "trigger_clientes_updated_at" AFTER UPDATE ON "clientes"
BEGIN
    UPDATE "clientes" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;

CREATE TRIGGER "trigger_categorias_updated_at" AFTER UPDATE ON "categorias"
BEGIN
    UPDATE "categorias" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;

CREATE TRIGGER "trigger_produtos_updated_at" AFTER UPDATE ON "produtos"
BEGIN
    UPDATE "produtos" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;

CREATE TRIGGER "trigger_pedidos_updated_at" AFTER UPDATE ON "pedidos"
BEGIN
    UPDATE "pedidos" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;

CREATE TRIGGER "trigger_estoque_updated_at" AFTER UPDATE ON "estoque"
BEGIN
    UPDATE "estoque" SET "updated_at" = CURRENT_TIMESTAMP WHERE "id" = OLD.id;
END;