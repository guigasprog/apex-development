# VibeVault - Painel de Controle (Admin)

Este diretório contém o painel administrativo do VibeVault, desenvolvido com **PHP** e **Adianti Framework**.

## Pré-requisitos

- Servidor Web (Apache ou Nginx)
- PHP (v7.4 ou superior)
- Composer
- Banco de dados (Ex: PostgreSQL ou MySQL)

## Instalação

1.  Navegue até o diretório:
    ```bash
    cd control
    ```
2.  Instale as dependências do PHP:
    ```bash
    composer install
    ```

## Configuração

Toda a configuração do painel é feita no arquivo `.ini`.

1.  **Banco de Dados:**

    - Edite o arquivo `control/app/config/application.ini`.
    - Na seção `[database]`, configure as credenciais do seu banco de dados (host, name, user, pass, type, port).

2.  **Permissões:**

    - O framework Adianti precisa de permissão de escrita nos diretórios `tmp` e `app/config`.
    - Execute os seguintes comandos na raiz de `/control`:

    ```bash
    chmod -R 777 tmp
    chmod -R 777 app/config
    ```

3.  **Importação do Banco de Dados:**
    - O painel de controle contém os scripts de criação do banco de dados.
    - Acesse o banco de dados que você acabou de configurar no `application.ini`.
    - Importe os seguintes arquivos SQL (nesta ordem, se possível):
      - `control/app/database/permission.sql`
      - `control/app/database/log.sql`
      - `control/app/database/application.sql`

## Executando (Servidor Web)

1.  Configure um VirtualHost no seu Apache (ou um Server Block no Nginx).
2.  Aponte o `DocumentRoot` (raiz) do seu servidor web para o diretório `control/`.
