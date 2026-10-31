# <img width="50" height="50" alt="f9dd8bfa-5d56-4511-aef8-73031ee9558c" src="https://github.com/user-attachments/assets/8d07d11f-79a3-4390-b73c-b4aa2deec9b4" /> Operix Auth

Central de autenticação multi sistema construída com Laravel 12 e PHP 8.3. A API cria sistemas isolados, mantém os usuários em tabelas PostgreSQL próprias e emite JWTs vinculados ao sistema. Tokens emitidos pelo Keycloak também continuam aceitos nas rotas protegidas.

## Requisitos

- Docker e Docker Compose
- Cliente confidencial no Keycloak com permissão para criar usuários no realm configurado

## Configuração e execução

Copie `.env.example` para `.env`, gere `APP_KEY` com `docker compose run --rm app php artisan key:generate --show` e configure credenciais do banco e `ADMIN_PASSWORD` com um valor forte. `ADMIN_EMAIL` e `ADMIN_PASSWORD` protegem o painel administrativo. Use `SESSION_SECURE_COOKIE=true` quando publicar por HTTPS. Cache e sessões usam arquivos por padrão e a limitação de tentativas usa esse cache; para múltiplas réplicas, configure Redis. Dentro da rede Docker, o serviço usa `http://keycloak:8080`; o issuer dos tokens Keycloak deve corresponder exatamente à configuração `KEYCLOAK_ISSUER_URI`.

```bash
cp .env.example .env
docker compose up --build -d
docker compose exec app php artisan migrate
```

A API fica em `http://localhost:8081`, Keycloak em `http://localhost:8080` e PostgreSQL em `localhost:5432`. O realm de `realm-export.json` é importado pelo Keycloak. As chamadas da API usam as credenciais de sistema geradas no painel. Os valores `KEYCLOAK_ADMIN_CLIENT_ID` e `KEYCLOAK_ADMIN_CLIENT_SECRET` são mantidos para integrações administrativas opcionais e validação de tokens emitidos pelo Keycloak.

O painel administrativo fica em `http://localhost:8081/admin`. Ele apresenta sistemas conectados, métricas de requisições e falhas, logs recentes, controles para ativar/desativar aplicações e o [guia de integração do Keycloak](./resources/views/admin/guia-keycloak.blade.php).

## Endpoints

| Método | Caminho | Acesso | Descrição |
| --- | --- | --- | --- |
| POST | `/api/v1/auth/register` | Público | Cria usuário e senha no Keycloak |
| GET | `/api/v1/auth/me` | JWT | Subject, issuer, audience, datas, email e roles |
| GET | `/api/v1/auth/claims` | JWT | Subconjunto selecionado de claims validadas |
| GET | `/api/v1/auth/authorities` | JWT | Roles convertidas em authorities `ROLE_*` |
| GET | `/api/v1/user/profile` | JWT | Identidade básica e roles |
| GET | `/up` | Público | Health check do Laravel |

Cada aplicação cadastrada no painel recebe `X-Client-Id` e `X-Client-Secret`, exibidos uma única vez. Guarde-os como segredos no backend do sistema integrado. O identificador gera uma tabela independente chamada `usuarios_sistema_<id>`.

Cadastro: `POST /api/v1/auth/register` com JSON `{"username":"user1","email":"user1@example.com","password":"SenhaSegura123"}` e os dois cabeçalhos de cliente. Login: `POST /api/v1/auth/login` com `{"username":"user1","password":"SenhaSegura123"}` e os mesmos cabeçalhos. A resposta de login inclui `access_token`, expiração, identidade pública do usuário e sistema. Envie o token como `Authorization: Bearer <access_token>` para consultar `/api/v1/auth/me`, `/api/v1/auth/claims`, `/api/v1/auth/authorities` e `/api/v1/user/profile`.

## Plano e isolamento multi sistema

1. **Registro de aplicações e dados isolados:** o painel grava cada sistema em `sistemas`, gera credenciais aleatórias e cria uma tabela física separada para os usuários daquela aplicação. O segredo de cliente é armazenado somente como hash. O middleware valida as credenciais em cada operação de cadastro/login; cada login consulta exclusivamente a tabela daquele sistema e emite um token com `system_id` e `system`.
2. **Painel e monitoramento:** a área `/admin` exige as credenciais administrativas do ambiente, mostra métricas de uso/falhas e logs de auditoria, permite cadastrar e desativar sistemas, fechar o cadastro público por aplicação e rotacionar segredos (revogando tokens ativos). A página de segurança verifica modo debug, chave, força da senha administrativa, HTTPS, cookie de sessão e validade dos tokens. A interface usa tema escuro por padrão e permite alternar para claro.

Para habilitar o painel, configure `ADMIN_EMAIL` e `ADMIN_PASSWORD` no `.env`. Para configurar um cliente OIDC/conta de serviço no Keycloak, siga **Guia do Keycloak** no menu lateral. Consulte o guia oficial do [Keycloak Server Administration](https://www.keycloak.org/docs/25.0.6/server_admin/) para detalhes de contas de serviço e permissões.

O plano das duas mudanças e a revisão de segurança realizada estão em [docs/PLANO_E_REVISAO_DE_SEGURANCA.md](./docs/PLANO_E_REVISAO_DE_SEGURANCA.md).

Rotas privadas exigem `Authorization: Bearer <access_token>` emitido pelo realm configurado. As respostas seguem `{ "status", "message", "data" }`. Validação retorna HTTP 422 e conflito de cadastro HTTP 409.

## Testes

```bash
docker compose run --rm testes
```

Os testes de feature cobrem validação, isolamento dos usuários entre sistemas, emissão e validação dos JWTs e proteção do painel administrativo.


## Banco

`php artisan migrate` cria `log_event`, tabela de eventos de auditoria, além da tabela de controle de migrations. Nunca registre senhas ou tokens nos eventos.
