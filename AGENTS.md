# Orientações para agentes

## Projeto
- API de autenticação em Laravel 12 / PHP 8.3.
- Execute aplicação, PostgreSQL e Keycloak via Docker Compose; não reintroduza Java.
- Preserve contratos JSON em `/api/v1/auth/*` e `/api/v1/user/profile`.
- Cada sistema integrado possui credenciais próprias e uma tabela de usuários isolada; nunca consulte usuários sem restringir ao sistema autenticado.
- O painel administrativo fica em `/admin`; credenciais vêm de `ADMIN_EMAIL` e `ADMIN_PASSWORD`.

## Desenvolvimento
- Controllers em `app/Http/Controllers`, integrações em `app/Services` e autenticação JWT em `app/Http/Middleware`.
- Segredos vêm do ambiente; não versione `.env`.
- Alterações no banco devem ser migrations Laravel compatíveis com PostgreSQL.
- Não exponha tokens, senhas ou claims não selecionadas.

## Verificação
- Testes: `docker compose run --rm testes`.
- Stack: `docker compose up --build`; API na porta 8081.
- Atualize testes e documentação em mudanças de autenticação ou persistência.
- Não registre tokens, segredos de cliente, senhas nem conteúdo bruto de cabeçalhos nos logs.
