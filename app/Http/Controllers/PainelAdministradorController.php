<?php

namespace App\Http\Controllers;

use App\Services\ServicoSistemaAplicacao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PainelAdministradorController
{
    public function login()
    {
        return view('admin.login');
    }

    public function autenticar(Request $request)
    {
        $dados = $request->validate(['email' => ['required', 'email'], 'senha' => ['required', 'string']]);
        $emailCorreto = (string) config('painel.email');
        $senhaCorreta = (string) config('painel.senha');

        $emailValido = $emailCorreto !== '' && hash_equals($emailCorreto, $dados['email']);
        $senhaValida = $senhaCorreta !== '' && hash_equals($senhaCorreta, $dados['senha']);
        if (!$emailValido || !$senhaValida) {
            $this->registrarEventoAdministrativo($request, 'security_admin_login_failure', 401);
            throw ValidationException::withMessages(['email' => 'Credenciais administrativas inválidas.']);
        }

        $request->session()->regenerate();
        $request->session()->put('administrador_autenticado', true);
        $this->registrarEventoAdministrativo($request, 'admin_login_success', 200);

        return redirect()->route('admin.inicio');
    }

    public function sair(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    public function guiaKeycloak()
    {
        return view('admin.guia-keycloak');
    }

    public function inicio()
    {
        $ultimas24h = now()->subDay();
        $checks = [
            ['titulo' => 'Modo de produção', 'ok' => config('app.env') === 'production', 'dica' => 'Defina APP_ENV=production no ambiente de implantação.'],
            ['titulo' => 'Modo de depuração desligado', 'ok' => !config('app.debug'), 'dica' => 'Defina APP_DEBUG=false para não expor detalhes internos.'],
            ['titulo' => 'Chave da aplicação configurada', 'ok' => \App\Services\ChaveJwtCentral::obter() !== null, 'dica' => 'Gere uma APP_KEY aleatória e mantenha-a protegida.'],
            ['titulo' => 'Senha administrativa forte', 'ok' => strlen((string) config('painel.senha')) >= 16, 'dica' => 'Use ADMIN_PASSWORD com pelo menos 16 caracteres.'],
            ['titulo' => 'Senha do PostgreSQL', 'ok' => strlen((string) config('database.connections.pgsql.password')) >= 20 && !in_array((string) config('database.connections.pgsql.password'), ['root', 'password'], true), 'dica' => 'Troque a senha de desenvolvimento por uma credencial exclusiva e longa.'],
            ['titulo' => 'URL pública com HTTPS', 'ok' => str_starts_with((string) config('app.url'), 'https://'), 'dica' => 'Use HTTPS na URL pública e termine TLS no proxy reverso.'],
            ['titulo' => 'Cookie de sessão seguro', 'ok' => (bool) config('session.secure'), 'dica' => 'Defina SESSION_SECURE_COOKIE=true quando o painel estiver publicado por HTTPS.'],
            ['titulo' => 'Cache para limitação de tentativas', 'ok' => config('cache.default') !== 'database', 'dica' => 'Use CACHE_STORE=file ou Redis com a limitação por banco configurada.'],
            ['titulo' => 'Validade curta dos tokens', 'ok' => (int) config('services.tokens.duracao_segundos', 3600) <= 3600, 'dica' => 'Mantenha TOKEN_DURACAO_SEGUNDOS em até 3600.'],
        ];
        $dados = [
            'total_sistemas' => DB::table('sistemas')->count(),
            'sistemas_ativos' => DB::table('sistemas')->where('habilitado', true)->count(),
            'requisicoes_24h' => DB::table('log_event')->where('created_at', '>=', $ultimas24h)->count(),
            'falhas_24h' => DB::table('log_event')->where('created_at', '>=', $ultimas24h)->where('http_status', '>=', 400)->count(),
            'falhas_auth_24h' => DB::table('log_event')->where('created_at', '>=', $ultimas24h)->whereIn('event_type', ['security_auth_failure', 'security_admin_login_failure'])->count(),
            'logs' => DB::table('log_event')->orderByDesc('created_at')->paginate(15),
            'sistemas' => DB::table('sistemas')->orderBy('nome')->get(),
            'checks' => $checks,
        ];

        return view('admin.inicio', $dados);
    }

    public function cadastrarSistema(Request $request, ServicoSistemaAplicacao $sistemas)
    {
        $request->merge(['slug' => Str::lower((string) $request->input('slug'))]);
        $dados = $request->validate([
            'nome' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'alpha_dash:ascii', 'max:80', 'unique:sistemas,slug'],
        ]);

        $credenciais = $sistemas->criar($dados['nome'], Str::lower($dados['slug']));
        $request->session()->flash('credenciais_sistema', [
            'nome' => $credenciais['sistema']->nome,
            'client_id' => $credenciais['sistema']->client_id,
            'client_secret' => $credenciais['client_secret'],
        ]);

        return redirect()->route('admin.inicio')->withFragment('sistemas');
    }

    public function alternarSistema(int $id)
    {
        $sistema = DB::table('sistemas')->find($id);
        abort_unless($sistema, 404);
        DB::table('sistemas')->where('id', $id)->update(['habilitado' => !$sistema->habilitado, 'updated_at' => now()]);

        return redirect()->route('admin.inicio')->withFragment('sistemas')->with('mensagem', 'Status do sistema atualizado.');
    }

    public function alternarCadastro(int $id)
    {
        $sistema = DB::table('sistemas')->find($id);
        abort_unless($sistema, 404);
        DB::table('sistemas')->where('id', $id)->update(['cadastro_habilitado' => !$sistema->cadastro_habilitado, 'updated_at' => now()]);

        return redirect()->route('admin.inicio')->withFragment('sistemas')->with('mensagem', 'Configuração de cadastro atualizada.');
    }

    public function rotacionarSegredo(int $id, Request $request, ServicoSistemaAplicacao $sistemas)
    {
        $credenciais = $sistemas->rotacionarSegredo($id);
        $request->session()->flash('credenciais_rotacionadas', $credenciais);

        return redirect()->route('admin.inicio')->withFragment('sistemas')->with('mensagem', 'Segredo rotacionado. Tokens anteriores foram revogados. Copie o novo segredo agora.');
    }

    private function registrarEventoAdministrativo(Request $request, string $tipo, int $status): void
    {
        try {
            DB::table('log_event')->insert([
                'created_at' => now(),
                'correlation_id' => (string) Str::uuid(),
                'event_type' => $tipo,
                'level' => $status >= 400 ? 'WARN' : 'INFO',
                'client_ip' => $request->ip(),
                'http_method' => $request->method(),
                'http_path' => '/admin/login',
                'http_status' => $status,
                'latency_ms' => 0,
                'details_json' => json_encode([], JSON_THROW_ON_ERROR),
            ]);
        } catch (\Throwable) {
            // O painel permanece acessível mesmo se a auditoria estiver indisponível.
        }
    }
}
