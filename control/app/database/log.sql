-- =================================================================
-- BANCO DE DADOS DE LOGS CENTRALIZADOS (VERSÃO SQLITE - CORRIGIDA)
-- =================================================================

CREATE TABLE "access_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "sessionid" TEXT,
  "login" TEXT,
  "login_time" DATETIME,      -- CORRIGIDO: de 'access_time' para 'login_time'
  "logout_time" DATETIME,     -- ADICIONADO: para registrar o logout
  "impersonated" TEXT,
  "access_ip" TEXT,           -- RENOMEADO: de 'ip_address' para o padrão 'access_ip'
  "login_year" TEXT,          -- RENOMEADO: de 'year' para o padrão 'login_year'
  "login_month" TEXT,         -- RENOMEADO: de 'month' para o padrão 'login_month'
  "login_day" TEXT,           -- RENOMEADO: de 'day' para o padrão 'login_day'
  "impersonated_by" TEXT      -- ADICIONADO: para log de personificação
);

CREATE TABLE "change_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "logdate" DATETIME,
  "login" TEXT,
  "tablename" TEXT,
  "primarykey" TEXT,
  "pkvalue" TEXT,
  "operation" TEXT,
  "columnname" TEXT,
  "oldvalue" TEXT,
  "newvalue" TEXT,
  "access_ip" TEXT,
  "transaction_id" TEXT,
  "log_trace" TEXT,
  "session_id" TEXT,
  "class_name" TEXT,
  "php_sapi" TEXT,
  "request_id" TEXT
);

CREATE TABLE "sql_log" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "logdate" DATETIME,
  "login" TEXT,
  "database_name" TEXT,
  "sql_command" TEXT,
  "statement_type" TEXT,
  "access_ip" TEXT,
  "transaction_id" TEXT,
  "log_trace" TEXT,
  "session_id" TEXT,
  "class_name" TEXT,
  "php_sapi" TEXT,
  "request_id" TEXT
);

CREATE TABLE "system_request_log" (
    "id" INTEGER PRIMARY KEY AUTOINCREMENT,
    "endpoint" TEXT,
    "logdate" TEXT,
    "log_year" TEXT,
    "log_month" TEXT,
    "log_day" TEXT,
    "session_id" TEXT,
    "login" TEXT,
    "access_ip" TEXT,
    "class_name" TEXT,
    "class_method" TEXT,
    "http_host" TEXT,
    "server_port" TEXT,
    "request_uri" TEXT,
    "request_method" TEXT,
    "query_string" TEXT,
    "request_headers" TEXT,
    "request_body" TEXT,
    "request_duration" INTEGER
);