<?php
namespace Tests\Feature;

use App\Services\ServicoSistemaAplicacao;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_cadastro_exige_campos_validos(): void
    {
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable()
            ->assertJsonPath('status', 'error')->assertJsonStructure(['data'=>['username','email','password']]);
    }
    public function test_cadastro_e_login_emitem_token_vinculado_ao_sistema(): void
    {
        $credenciais = app(ServicoSistemaAplicacao::class)->criar('Opeflow Barber', 'opeflow-barber');
        $cabecalhos = [
            'X-Client-Id' => $credenciais['sistema']->client_id,
            'X-Client-Secret' => $credenciais['client_secret'],
        ];

        $this->postJson('/api/v1/auth/register', ['username'=>'user1','email'=>'user@example.com','password'=>'Password123'])
            ->assertUnauthorized();

        $this->postJson('/api/v1/auth/register', ['username'=>'user1','email'=>'user@example.com','password'=>'Password123'], $cabecalhos)
            ->assertOk()->assertJsonPath('status','success')->assertJsonPath('data',null);

        $login = $this->postJson('/api/v1/auth/login', ['username'=>'user1','password'=>'Password123'], $cabecalhos)
            ->assertOk()->assertJsonPath('data.user.email','user@example.com')
            ->assertJsonPath('data.system.slug','opeflow-barber')->assertJsonPath('data.token_type','Bearer');

        $token = $login->json('data.access_token');
        $this->withToken($token)->getJson('/api/v1/user/profile')
            ->assertOk()->assertJsonPath('data.username','user1');
    }

    public function test_usuarios_de_sistemas_diferentes_ficam_isolados(): void
    {
        $sistemas = app(ServicoSistemaAplicacao::class);
        $barbearia = $sistemas->criar('Opeflow Barber', 'opeflow-barber');
        $servicos = $sistemas->criar('Opeflow Service', 'opeflow-service');
        $sistemas->registrarUsuario($barbearia['sistema'], 'compartilhado', 'barber@example.com', 'Password123');
        $sistemas->registrarUsuario($servicos['sistema'], 'compartilhado', 'service@example.com', 'Password123');

        $respostaBarbearia = $this->postJson('/api/v1/auth/login', ['username'=>'compartilhado','password'=>'Password123'], [
            'X-Client-Id' => $barbearia['sistema']->client_id,
            'X-Client-Secret' => $barbearia['client_secret'],
        ])->assertOk();
        $this->assertSame('barber@example.com', $respostaBarbearia->json('data.user.email'));

        $this->postJson('/api/v1/auth/login', ['username'=>'compartilhado','password'=>'Password123'], [
            'X-Client-Id' => $servicos['sistema']->client_id,
            'X-Client-Secret' => $servicos['client_secret'],
        ])->assertOk()->assertJsonPath('data.user.email','service@example.com');
    }

    public function test_rotacionar_credencial_revoga_segredo_e_tokens_existentes(): void
    {
        $sistemas = app(ServicoSistemaAplicacao::class);
        $credenciais = $sistemas->criar('Opeflow Barber', 'opeflow-barber');
        $sistemas->registrarUsuario($credenciais['sistema'], 'user1', 'user@example.com', 'Password123');
        $token = $this->postJson('/api/v1/auth/login', ['username'=>'user1','password'=>'Password123'], [
            'X-Client-Id' => $credenciais['sistema']->client_id,
            'X-Client-Secret' => $credenciais['client_secret'],
        ])->assertOk()->json('data.access_token');

        $rotacionadas = $sistemas->rotacionarSegredo($credenciais['sistema']->id);
        $this->assertNotSame($credenciais['client_secret'], $rotacionadas['client_secret']);
        $this->assertNotSame($credenciais['client_secret'], DB::table('sistemas')->value('client_secret_hash'));
        $this->withToken($token)->getJson('/api/v1/user/profile')->assertUnauthorized();
        $this->postJson('/api/v1/auth/login', ['username'=>'user1','password'=>'Password123'], [
            'X-Client-Id' => $credenciais['sistema']->client_id,
            'X-Client-Secret' => $credenciais['client_secret'],
        ])->assertUnauthorized();
    }

    public function test_cadastro_por_sistema_pode_ser_desativado(): void
    {
        $sistema = app(ServicoSistemaAplicacao::class)->criar('Opeflow Barber', 'opeflow-barber');
        DB::table('sistemas')->where('id', $sistema['sistema']->id)->update(['cadastro_habilitado' => false]);

        $this->postJson('/api/v1/auth/register', ['username'=>'user1','email'=>'user@example.com','password'=>'Password123'], [
            'X-Client-Id' => $sistema['sistema']->client_id,
            'X-Client-Secret' => $sistema['client_secret'],
        ])->assertForbidden();
    }

    public function test_login_tem_limite_de_tentativas_por_ip(): void
    {
        for ($tentativa = 0; $tentativa < 10; $tentativa++) {
            $this->postJson('/api/v1/auth/login', ['username'=>'nao-existe','password'=>'senha-incorreta'], [
                'X-Client-Id' => 'cliente-invalido', 'X-Client-Secret' => 'segredo-invalido',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['username'=>'nao-existe','password'=>'senha-incorreta'], [
            'X-Client-Id' => 'cliente-invalido', 'X-Client-Secret' => 'segredo-invalido',
        ])->assertStatus(429);
    }

    public function test_token_keycloak_com_audiencia_de_outro_cliente_e_recusado(): void
    {
        config([
            'services.keycloak.base_url' => 'https://keycloak-teste.invalid',
            'services.keycloak.issuer' => 'https://keycloak-teste.invalid/realms/operix',
            'services.keycloak.realm' => 'operix',
            'services.keycloak.clientes_permitidos' => ['operix-sso'],
        ]);
        $parChaves = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        openssl_pkey_export($parChaves, $chavePrivada);
        $detalhes = openssl_pkey_get_details($parChaves)['rsa'];
        $base64url = fn (string $valor): string => rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
        $jwks = ['keys' => [[
            'kty' => 'RSA', 'use' => 'sig', 'alg' => 'RS256', 'kid' => 'chave-teste',
            'n' => $base64url($detalhes['n']), 'e' => $base64url($detalhes['e']),
        ]]];
        Http::fake([
            'https://keycloak-teste.invalid/realms/operix/.well-known/openid-configuration' => Http::response([
                'jwks_uri' => 'https://keycloak-teste.invalid/realms/operix/protocol/openid-connect/certs',
            ]),
            'https://keycloak-teste.invalid/realms/operix/protocol/openid-connect/certs' => Http::response($jwks),
        ]);
        Cache::forget('keycloak.jwks');
        $claims = ['iss'=>'https://keycloak-teste.invalid/realms/operix','aud'=>'outro-cliente','azp'=>'outro-cliente','sub'=>'usuario-123','exp'=>time()+300];
        $token = JWT::encode($claims, $chavePrivada, 'RS256', 'chave-teste');

        $this->withToken($token)->getJson('/api/v1/user/profile')->assertUnauthorized();

        $claims['aud'] = 'operix-sso';
        $claims['azp'] = 'operix-sso';
        $tokenPermitido = JWT::encode($claims, $chavePrivada, 'RS256', 'chave-teste');
        $this->withToken($tokenPermitido)->getJson('/api/v1/user/profile')->assertOk()->assertJsonPath('data.id', 'usuario-123');
    }

    public function test_jwt_central_nao_usa_chave_vazia_quando_app_key_falta(): void
    {
        config(['app.key' => '']);
        Http::fake();
        $tokenForjado = JWT::encode([
            'iss' => rtrim(config('app.url'), '/'),
            'aud' => 'cliente-forjado',
            'sub' => '1',
            'system_id' => 1,
            'credential_version' => 1,
            'exp' => time() + 300,
        ], hash('sha256', '', true), 'HS256');

        $this->withToken($tokenForjado)->getJson('/api/v1/user/profile')->assertUnauthorized();
    }
    public function test_perfil_protegido_recusa_token_ausente(): void
    {
        $this->getJson('/api/v1/user/profile')->assertUnauthorized()->assertJsonPath('status','error');
    }

    public function test_painel_exige_login_administrativo_e_exibe_guia(): void
    {
        config(['painel.email' => 'admin@example.com', 'painel.senha' => 'senha-painel-teste']);
        DB::table('administradores')->insert([
            'nome' => 'Admin Teste', 'email' => 'admin@example.com', 'senha_hash' => Hash::make('senha-painel-teste'),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/admin')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['email'=>'admin@example.com','senha'=>'senha-errada'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('log_event', ['event_type'=>'security_admin_login_failure','http_status'=>401]);
        $this->post('/admin/login', ['email'=>'admin@example.com','senha'=>'senha-painel-teste'])->assertRedirect(route('admin.inicio'));
        $this->get('/admin')->assertOk()->assertSee('Sistemas conectados')->assertSee('Verificações preventivas');
        $this->get('/admin/guia-keycloak')->assertOk()->assertSee('Service accounts roles');
    }

    public function test_seeder_cria_administrador_com_senha_em_hash_e_pode_ser_reexecutado(): void
    {
        config(['painel.nome' => 'Admin Seed', 'painel.email' => 'ADMIN@example.com', 'painel.senha' => 'senha-forte-de-teste']);

        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdministradorSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => \Database\Seeders\AdministradorSeeder::class, '--force' => true]);

        $this->assertDatabaseCount('administradores', 1);
        $administrador = DB::table('administradores')->first();
        $this->assertSame('admin@example.com', $administrador->email);
        $this->assertNotSame('senha-forte-de-teste', $administrador->senha_hash);
        $this->assertTrue(Hash::check('senha-forte-de-teste', $administrador->senha_hash));
    }

    public function test_nome_de_sistema_nao_e_renderizado_como_html(): void
    {
        config(['painel.email' => 'admin@example.com', 'painel.senha' => 'senha-painel-teste']);
        DB::table('administradores')->insert([
            'nome' => 'Admin Teste', 'email' => 'admin@example.com', 'senha_hash' => Hash::make('senha-painel-teste'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->post('/admin/login', ['email'=>'admin@example.com','senha'=>'senha-painel-teste']);
        $this->post('/admin/sistemas', ['nome'=>'<script>alert(1)</script>','slug'=>'app-seguro'])->assertRedirect();

        $this->get('/admin')->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }
}
