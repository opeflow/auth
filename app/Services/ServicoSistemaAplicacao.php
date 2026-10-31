<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServicoSistemaAplicacao
{
    public function criar(string $nome, string $slug): array
    {
        $clientId = Str::lower(Str::random(12));
        $clientSecret = Str::random(48);

        $sistema = DB::transaction(function () use ($nome, $slug, $clientId, $clientSecret): object {
            $id = DB::table('sistemas')->insertGetId([
                'nome' => $nome,
                'slug' => $slug,
                'client_id' => $clientId,
                'client_secret_hash' => Hash::make($clientSecret),
                'tabela_usuarios' => 'usuarios_sistema_pendente',
                'habilitado' => true,
                'cadastro_habilitado' => true,
                'versao_credencial' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tabela = $this->nomeDaTabela($id);
            DB::table('sistemas')->where('id', $id)->update(['tabela_usuarios' => $tabela]);
            $this->criarTabelaDeUsuarios($tabela);

            return DB::table('sistemas')->find($id);
        });

        return [
            'sistema' => $sistema,
            'client_secret' => $clientSecret,
        ];
    }

    public function autenticarCliente(Request $request): object
    {
        $clientId = $request->header('X-Client-Id');
        $clientSecret = $request->header('X-Client-Secret');
        $sistema = $clientId ? DB::table('sistemas')->where('client_id', $clientId)->first() : null;
        if ($sistema) {
            $request->attributes->set('sistema', $sistema);
        }

        if (!$sistema || !$sistema->habilitado || !$clientSecret || !Hash::check($clientSecret, $sistema->client_secret_hash)) {
            abort(401, 'Credenciais do sistema inválidas ou inativas.');
        }

        return $sistema;
    }

    public function rotacionarSegredo(int $id): array
    {
        $sistema = DB::table('sistemas')->find($id);
        abort_unless($sistema, 404);
        $novoSegredo = Str::random(48);
        DB::table('sistemas')->where('id', $id)->update([
            'client_secret_hash' => Hash::make($novoSegredo),
            'versao_credencial' => $sistema->versao_credencial + 1,
            'updated_at' => now(),
        ]);

        return ['client_id' => $sistema->client_id, 'client_secret' => $novoSegredo];
    }

    public function registrarUsuario(object $sistema, string $username, string $email, string $password): object
    {
        abort_unless($sistema->cadastro_habilitado, 403, 'O cadastro está desabilitado para este sistema.');
        $tabela = $this->validarTabela($sistema->tabela_usuarios);
        $existe = DB::table($tabela)->where('username', $username)->orWhere('email', $email)->exists();

        if ($existe) {
            throw ValidationException::withMessages(['username' => 'Já existe um usuário com esse nome ou e-mail neste sistema.']);
        }

        $id = DB::table($tabela)->insertGetId([
            'username' => $username,
            'email' => $email,
            'password_hash' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table($tabela)->find($id);
    }

    public function autenticarUsuario(object $sistema, string $username, string $password): ?object
    {
        $tabela = $this->validarTabela($sistema->tabela_usuarios);
        $usuario = DB::table($tabela)->where('username', $username)->orWhere('email', $username)->first();

        return $usuario && Hash::check($password, $usuario->password_hash) ? $usuario : null;
    }

    private function criarTabelaDeUsuarios(string $tabela): void
    {
        Schema::create($tabela, function ($colunas): void {
            $colunas->id();
            $colunas->string('username');
            $colunas->string('email');
            $colunas->string('password_hash');
            $colunas->jsonb('dados')->nullable();
            $colunas->timestampsTz();
            $colunas->unique('username');
            $colunas->unique('email');
        });
    }

    private function nomeDaTabela(int $id): string
    {
        return 'usuarios_sistema_'.$id;
    }

    private function validarTabela(string $tabela): string
    {
        abort_unless(preg_match('/^usuarios_sistema_[0-9]+$/', $tabela) === 1 && Schema::hasTable($tabela), 500, 'Tabela de usuários do sistema não está provisionada.');

        return $tabela;
    }
}
