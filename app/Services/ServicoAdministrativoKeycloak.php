<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ServicoAdministrativoKeycloak
{
    public function registrar(string $username, string $email, string $password): void
    {
        $base = rtrim(config('services.keycloak.base_url'), '/');
        $realm = config('services.keycloak.realm');
        $tokenResponse = Http::asForm()->timeout(10)->post($base.'/realms/'.config('services.keycloak.admin_realm', 'master').'/protocol/openid-connect/token', [
            'grant_type'=>'client_credentials', 'client_id'=>config('services.keycloak.client_id'), 'client_secret'=>config('services.keycloak.client_secret'),
        ]);
        $tokenResponse->throw();
        $token = $tokenResponse->json('access_token');
        if (!$token) throw new RuntimeException('Não foi possível autenticar no Keycloak.');
        $create = Http::withToken($token)->timeout(10)->post($base.'/admin/realms/'.$realm.'/users', [
            'username'=>$username, 'email'=>$email, 'enabled'=>true, 'credentials'=>[['type'=>'password','value'=>$password,'temporary'=>false]],
        ]);
        if ($create->status() === 409) throw new RuntimeException('Usuário já existe.');
        if (!$create->successful()) throw new RuntimeException('Erro ao criar usuário no Keycloak: HTTP '.$create->status());
    }
}
