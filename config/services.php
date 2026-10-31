<?php
return [
    'keycloak'=>['base_url'=>env('KEYCLOAK_BASE_URL','http://keycloak:8080'),'issuer'=>env('KEYCLOAK_ISSUER_URI','http://localhost:8080/realms/operix'),'realm'=>env('KEYCLOAK_REALM','operix'),'admin_realm'=>env('KEYCLOAK_ADMIN_REALM','operix'),'client_id'=>env('KEYCLOAK_ADMIN_CLIENT_ID'),'client_secret'=>env('KEYCLOAK_ADMIN_CLIENT_SECRET'),'clientes_permitidos'=>array_filter(array_map('trim', explode(',', (string) env('KEYCLOAK_ALLOWED_CLIENTS', env('KEYCLOAK_ADMIN_CLIENT_ID', 'operix-sso')))))],
    'tokens'=>['duracao_segundos'=>env('TOKEN_DURACAO_SEGUNDOS', 3600)],
];
