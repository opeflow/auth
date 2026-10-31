# Plano de evolução e revisão de segurança

## Plano A — central multi sistema

1. Registrar aplicações autorizadas no painel e emitir `client_id`/`client_secret`.
2. Criar uma tabela PostgreSQL própria por aplicação, com hash de senha e sem compartilhamento de usuários entre sistemas.
3. Exigir credenciais do sistema no cadastro/login, autenticar o usuário apenas na tabela correspondente e emitir JWT com identificador e audiência do sistema.
4. Manter os endpoints e o formato JSON já existentes; documentar headers, payloads, operações e o guia de integração Keycloak.

**Implementado:** tabelas físicas `usuarios_sistema_<id>`, cadastro e login da API, JWT central assinado com chave derivada da `APP_KEY`, isolamento coberto por testes e compatibilidade de leitura com tokens Keycloak.

## Plano B — painel administrativo

1. Proteger `/admin` com credenciais de ambiente, sessão renovada no login e proteção CSRF.
2. Mostrar métricas, falhas e logs recentes sem armazenar tokens, senhas ou secrets.
3. Permitir cadastrar/desativar sistemas, fechar cadastro por aplicação e rotacionar segredos.
4. Incluir guia de configuração do Keycloak e verificações preventivas em tema escuro responsivo.

**Implementado:** painel Blade com as funções descritas e alternância claro/escuro. `ADMIN_EMAIL` e `ADMIN_PASSWORD` precisam ser configurados antes da publicação.

## Revisão ofensiva local

Os casos foram reproduzidos e cobertos por testes HTTP locais, sem atacar serviços externos:

- Tentativas repetidas de login: limitação a 10 requisições por minuto e endereço IP.
- Token Keycloak válido, mas destinado a outro cliente: bloqueado por validação de `aud`/`azp` contra `KEYCLOAK_ALLOWED_CLIENTS`.
- Token de aplicação após rotação do segredo: revogado imediatamente por `versao_credencial`.
- Aplicação desativada: deixa de autenticar e tokens emitidos deixam de autorizar chamadas.
- Cadastro de usuário desativado: API responde 403 para aquela aplicação.
- Conteúdo HTML em nomes de sistemas: saída escapada pelos templates Blade.
- Senhas/segredos: senha de usuário e secret de cliente persistidos como hashes; payloads e headers de credenciais não são escritos nos logs.
- Chave da aplicação ausente: emissão de JWT central falha fechada em vez de usar uma chave previsível.
- Contexto de imagem Docker: `.env`, `.git` e diretórios temporários excluídos; a imagem do serviço não instala dependências de desenvolvimento.
- Acesso ao painel sem sessão ou com senha incorreta: recusado; tentativas inválidas entram no log de segurança.

O painel verifica `APP_ENV`, `APP_DEBUG`, `APP_KEY`, força das senhas administrativa e PostgreSQL, HTTPS, cookie seguro, cache de limitação e duração do token. Para produção, publique atrás de HTTPS, use `SESSION_SECURE_COOKIE=true`, senha administrativa forte, segredo fora do repositório e Redis compartilhado se houver mais de uma réplica.

## Verificação

```bash
docker compose run --build --rm testes
```
