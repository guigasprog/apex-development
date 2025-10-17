-- =================================================================
-- BANCO DE DADOS DE PERMISSÕES E TENANTS (VERSÃO ATUALIZADA E OTIMIZADA)
-- =================================================================

-- Deleta as tabelas existentes para garantir uma recriação limpa
DROP TABLE IF EXISTS "SystemUser";
DROP TABLE IF EXISTS "tenant_themes";
DROP TABLE IF EXISTS "tenants";

-- Tabela principal para os tenants (lojas/clientes)
CREATE TABLE "tenants" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "nome_loja" TEXT NOT NULL,
  "status" TEXT CHECK( "status" IN ('ativo', 'inativo', 'suspenso') ) NOT NULL DEFAULT 'ativo',
  "url_logo" TEXT NULL,
  "db_connection_name" TEXT NULL,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabela para armazenar as configurações de tema de cada tenant
CREATE TABLE "tenant_themes" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "tenant_id" INTEGER NOT NULL UNIQUE,
  "background_mode" TEXT CHECK( "background_mode" IN ('light', 'dark') ) NOT NULL DEFAULT 'light',
  "primary_color" TEXT NOT NULL DEFAULT 'default',
  "secondary_color" TEXT NOT NULL DEFAULT 'default', -- ADICIONADO
  "font_ui" TEXT CHECK( "font_ui" IN ('Inter', 'Roboto', 'DM Sans') ) NOT NULL DEFAULT 'Inter',
  "has_box_shadow" INTEGER NOT NULL DEFAULT 1, -- 1 para true, 0 para false
  "border_radius_px" INTEGER NOT NULL DEFAULT 8,
  "created_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "updated_at" DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY ("tenant_id") REFERENCES "tenants"("id") ON DELETE CASCADE
);

-- Tabela de usuários do sistema, com vínculo opcional a um tenant
CREATE TABLE "SystemUser" (
  "id" INTEGER PRIMARY KEY NOT NULL,
  "tenant_id" INTEGER NULL,
  "name" TEXT NOT NULL,
  "login" TEXT NOT NULL UNIQUE,
  "password" TEXT NOT NULL,
  "email" TEXT NOT NULL UNIQUE,
  "active" TEXT NOT NULL DEFAULT 'Y', -- ADICIONADO DEFAULT
  FOREIGN KEY ("tenant_id") REFERENCES "tenants"("id") ON DELETE SET NULL ON UPDATE CASCADE
);

ALTER TABLE tenant_themes ADD COLUMN hover_effect TEXT NOT NULL DEFAULT 'default';
ALTER TABLE tenants ADD COLUMN slug TEXT UNIQUE;
-- =================================================================
-- DADOS INICIAIS (SEEDS)
-- =================================================================

-- Inserindo o Super Admin (sem tenant)
INSERT INTO "SystemUser" (id, tenant_id, name, login, password, email, active) VALUES
(1, NULL, 'Super Admin', 'admin', '$2y$10$bLXhe1TLQWx48WNBh0AsxOTpjM9Ls/B4dFlFIp59XL62YWC1vBDpi', 'admin@seusistema.com', 'Y'); -- senha: admin


-- =================================================================
-- TRIGGERS PARA ATUALIZAR 'updated_at'
-- =================================================================


CREATE TRIGGER tenants_updated_at
AFTER UPDATE ON tenants FOR EACH ROW
BEGIN
    UPDATE tenants SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;

CREATE TRIGGER tenant_themes_updated_at
AFTER UPDATE ON tenant_themes FOR EACH ROW
BEGIN
    UPDATE tenant_themes SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
END;