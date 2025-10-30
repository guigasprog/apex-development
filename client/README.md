# VibeVault - Client (Frontend)

Este diretório contém a aplicação frontend do VibeVault, desenvolvida com **Angular**.

## Pré-requisitos

- Node.js (v16 ou superior)
- NPM
- Angular CLI (Instale com `npm install -g @angular/cli`)

## Configuração

Antes de executar, você precisa configurar a URL da API que esta aplicação irá consumir.

1.  Abra o arquivo: `client/src/environments/environment.ts`
2.  Localize a propriedade `apiUrl` (ou similar) e defina a URL onde sua API (`/api`) está sendo executada.

**Exemplo (`environment.ts`):**

```typescript
export const environment = {
  production: false,
  apiUrl: "http://localhost:3000/api", // Certifique-se que esta é a URL correta da sua API
};
```

## Instalação

1.  Navegue até o diretório:
    ```bash
    cd client
    ```
2.  Instale as dependências:
    ```bash
    npm install
    ```

## Executando a Aplicação

1.  Para iniciar o servidor de desenvolvimento (geralmente na porta `4200`):
    ```bash
    ng serve
    ```
    (Ou utilize o script padrão do `package.json`, se houver):
    ```bash
    npm start
    ```
2.  Abra seu navegador em `http://localhost:4200/`.
