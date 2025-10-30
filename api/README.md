# VibeVault - API (Backend)

Este diretório contém a API de backend do VibeVault, desenvolvida com **Node.js** e (provavelmente) **Express**.

## Pré-requisitos

- Node.js (v16 ou superior)
- NPM
- Um banco de dados (Ex: PostgreSQL ou MySQL), que deve ser o mesmo configurado no painel `/control`.

## Configuração

Esta API requer variáveis de ambiente para se conectar ao banco de dados.

1.  Crie um arquivo `.env` na raiz deste diretório (`/api/.env`).
2.  Com base no arquivo `api/src/database/connection.js`, adicione as variáveis necessárias. Elas provavelmente incluem:

```ini
# Configuração do Banco de Dados
DB_HOST=localhost
DB_USER=seu_usuario_db
DB_PASS=sua_senha_db
DB_NAME=vibevault_db
DB_PORT=5432 # (Porta do seu DB)

# Porta da Aplicação
PORT=3000
```

_Nota: Ajuste `DB_NAME`, `DB_USER` e `DB_PASS` para que sejam os mesmos valores usados no arquivo `application.ini` do painel `/control`._

## Instalação

1.  Navegue até o diretório:
    ```bash
    cd api
    ```
2.  Instale as dependências:
    ```bash
    npm install
    ```

## Executando a Aplicação

1.  Para iniciar o servidor (geralmente na porta `3000`):
    ```bash
    npm start
    ```
    (Se não houver script `start`, execute diretamente):
    ```bash
    node server.js
    ```
