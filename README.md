# VibeVault

Bem-vindo ao VibeVault. Este repositório contém os três componentes principais do projeto:

1.  **Client (`/client`):** A aplicação frontend (loja virtual) desenvolvida em Angular.
2.  **API (`/api`):** O serviço de backend (Node.js/Express) que atende o `client`.
3.  **Control (`/control`):** O painel administrativo (PHP/Adianti) para gerenciamento da plataforma.

## Estrutura do Projeto

```

/
├── api/ \# Backend (Node.js) para a loja
├── client/ \# Frontend (Angular) para a loja
└── control/ \# Painel de Administração (PHP/Adianti)

```

## Configuração

Cada parte do projeto (client, api, control) deve ser configurada e executada de forma independente. Siga as instruções específicas em cada diretório:

- **Para configurar o Frontend (Loja):**

  - Leia o [Guia de Configuração do Client](./client/README.md)

- **Para configurar o Backend (API):**

  - Leia o [Guia de Configuração da API](./api/README.md)

- **Para configurar o Painel Administrativo:**
  - Leia o [Guia de Configuração do Painel de Controle](./control/README.md)

### Ordem de Instalação Recomendada

1.  Configure e inicialize o **Painel de Controle (`/control`)** primeiro, pois ele contém os scripts de banco de dados (`.sql`).
2.  Configure e execute a **API (`/api`)**, conectando-a ao mesmo banco de dados.
3.  Configure e execute o **Client (`/client`)**, apontando-o para a URL da API.
