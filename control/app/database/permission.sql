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
  "secondary_color" TEXT NOT NULL DEFAULT 'default',
  "font_ui" TEXT NOT NULL DEFAULT 'Inter', -- A RESTRIÇÃO FOI REMOVIDA DESTA LINHA
  "has_box_shadow" INTEGER NOT NULL DEFAULT 1,
  "border_radius_px" INTEGER NOT NULL DEFAULT 8,
  "hover_effect" TEXT NOT NULL DEFAULT 'default',
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



CREATE TABLE "custom_colors" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "type" TEXT CHECK("type" IN ('primary', 'secondary')) NOT NULL,
  "name" TEXT NOT NULL UNIQUE,
  "label" TEXT NOT NULL,
  "hex_light" TEXT NULL, -- ALTERADO: Agora permite nulo
  "hex_dark" TEXT NULL   -- ALTERADO: Agora permite nulo
);

-- Tabela para armazenar as opções de fontes
CREATE TABLE "custom_fonts" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL UNIQUE, -- O valor salvo no BD (ex: 'Satoshi')
  "label" TEXT NOT NULL,      -- O texto no dropdown (ex: 'Satoshi (Moderno)')
  "import_url" TEXT NOT NULL  -- A URL completa do CDN (ex: 'https://api.fontshare.com/...')
);

-- Tabela para armazenar os efeitos de hover customizáveis
CREATE TABLE "custom_hover_effects" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL UNIQUE, -- O valor salvo no BD (ex: 'pulse')
  "label" TEXT NOT NULL,      -- O texto no dropdown (ex: 'Efeito Pulsar')
  "css_code" TEXT NOT NULL    -- O código CSS completo para a classe (ex: '.hover-effect-pulse:hover { transform: scale(1.05); }')
);

-- Populando com alguns dados iniciais para começar

INSERT INTO "custom_colors" (type, name, label, hex_light, hex_dark) VALUES
-- Cores Primárias
('primary', 'primary_default', 'Padrão (Azul)', '#3498db', '#3498db'),
('primary', 'greenlime', 'Verde Lima', '#2ecc71', '#27ae60'),
('primary', 'ruby_red', 'Vermelho Rubi', '#e74c3c', '#c0392b'),
('primary', 'deep_indigo', 'Índigo Profundo', '#4a00e0', '#8e2de2'),
('primary', 'golden_yellow', 'Amarelo Dourado', '#f1c40f', '#f39c12'),
('primary', 'rose_pink', 'Rosa Claro (Apenas Tema Claro)', '#FFC0CB', NULL),
('primary', 'matte_black', 'Preto Fosco (Apenas Tema Claro)', '#1a1a1a', NULL),
('primary', 'ice_white', 'Verde Gelido (Apenas Tema Escuro)', NULL, '#85ffb0'),

-- Cores Secundárias
('secondary', 'secondary_default', 'Padrão (Cinza/Branco)', '#555555', '#f5f5f5'),
('secondary', 'neutral_gray', 'Cinza Neutro', '#808080', '#d3d3d3'),
('secondary', 'sky_blue', 'Azul Céu', '#3498db', '#5dade2');


-- =================================================================
-- 2. FONTES PERSONALIZADAS
-- =================================================================
INSERT INTO "custom_fonts" (name, label, import_url) VALUES
('Inter', 'Inter (Padrão UI)', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap'),
('Roboto', 'Roboto (Clássica)', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap'),
('DM Sans', 'DM Sans (Moderna)', 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;700&display=swap'),
('Hubot Sans', 'Hubot Sans (Tech)', 'https://fonts.googleapis.com/css2?family=Hubot+Sans:wght@400;700&display=swap'),
('Satoshi', 'Satoshi (Minimalista)', 'https://api.fontshare.com/v2/css?f[]=satoshi@400;700&display=swap'),
('Poppins', 'Poppins (Geométrica)', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap'),
('Playfair Display', 'Playfair Display (Serifada)', 'https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap'),
('Source Code Pro', 'Source Code Pro (Monoespaçada)', 'https://fonts.googleapis.com/css2?family=Source+Code+Pro:wght@400;700&display=swap');


-- =================================================================
-- 3. EFEITOS DE HOVER PERSONALIZADOS
-- =================================================================
INSERT INTO "custom_hover_effects" (name, label, css_code) VALUES
('none', 'Nenhum', ''),
('default', 'Padrão (Card amplia, Botão escurece)', '.hover-effect-default-card:hover { transform: scale(1.02); } .hover-effect-default-button:hover { filter: brightness(0.9); }'),
('scale', 'Ampliar Tudo', '.hover-effect-scale:hover { transform: scale(1.05); }'),
('elevate', 'Elevar Tudo', '.hover-effect-elevate:hover { transform: translateY(-5px); }'),
('glow', 'Brilho no Card', '#preview-card.hover-effect-glow:hover { box-shadow: 0 0 20px 0 var(--shadow-color, rgba(0,0,0,0.2)) !important; }'),
('wobble', 'Balançar Suave', '.hover-effect-wobble:hover { transform: rotate(-2deg); }'),
('border_glow', 'Borda Iluminada (Card)', '#preview-card.hover-effect-border_glow:hover { border: 1px solid var(--primary-color, #3498db); box-shadow: 0 0 10px 0 var(--primary-color, #3498db); }');