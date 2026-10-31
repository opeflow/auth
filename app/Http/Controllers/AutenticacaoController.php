<?php

namespace App\Http\Controllers;

use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\HeaderParameter;
use App\Services\ServicoSistemaAplicacao;
use App\Services\ServicoTokenCentral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

#[Group('Autenticação')]
class AutenticacaoController
{
    /** Cria um usuário dentro do sistema integrado identificado pelos cabeçalhos do cliente. */
    #[HeaderParameter('X-Client-Id', description: 'Identificador público do sistema cadastrado no painel.', type: 'string', required: true)]
    #[HeaderParameter('X-Client-Secret', description: 'Segredo do sistema integrado. Nunca compartilhe este valor.', type: 'string', required: true)]
    public function registrar(Request $request, ServicoSistemaAplicacao $sistemas)
    {
        $data = Validator::make($request->all(), [
            'username'=>['required','string','max:255'], 'email'=>['required','email','max:255'], 'password'=>['required','string','min:8'],
        ])->validate();
        $sistema = $sistemas->autenticarCliente($request);
        $usuario = $sistemas->registrarUsuario($sistema, $data['username'], $data['email'], $data['password']);

        return response()->json(['status'=>'success','message'=>'Usuário criado com sucesso','data'=>null]);
    }

    /** Autentica um usuário no sistema integrado e retorna um token de acesso. */
    #[HeaderParameter('X-Client-Id', description: 'Identificador público do sistema cadastrado no painel.', type: 'string', required: true)]
    #[HeaderParameter('X-Client-Secret', description: 'Segredo do sistema integrado. Nunca compartilhe este valor.', type: 'string', required: true)]
    public function entrar(Request $request, ServicoSistemaAplicacao $sistemas, ServicoTokenCentral $tokens)
    {
        $data = Validator::make($request->all(), [
            'username'=>['required','string','max:255'], 'password'=>['required','string','max:255'],
        ])->validate();
        $sistema = $sistemas->autenticarCliente($request);
        $usuario = $sistemas->autenticarUsuario($sistema, $data['username'], $data['password']);

        abort_unless($usuario, 401, 'Usuário ou senha inválidos.');

        return response()->json(['status'=>'success','message'=>'Autenticação realizada com sucesso','data'=>$tokens->emitir($sistema, $usuario)]);
    }

    public function usuarioAtual(Request $request) { return $this->respostaDeAlegacoes($request, 'Token válido'); }
    public function alegacoes(Request $request) { return $this->respostaDeAlegacoes($request, 'Dados do token'); }

    public function autoridades(Request $request)
    {
        $roles = $request->attributes->get('jwt')->realm_access->roles ?? [];
        return response()->json(['status'=>'success','message'=>'Authorities do usuário','data'=>['authorities'=>array_map(fn ($r) => 'ROLE_'.$r, $roles)]]);
    }

    public function perfil(Request $request)
    {
        $jwt = $request->attributes->get('jwt');
        return response()->json(['status'=>'success','message'=>'Perfil do usuário','data'=>[
            'id'=>$jwt->sub ?? null, 'username'=>$jwt->preferred_username ?? null, 'email'=>$jwt->email ?? null,
            'roles'=>$jwt->realm_access->roles ?? [],
        ]]);
    }

    private function respostaDeAlegacoes(Request $request, string $message)
    {
        $jwt = $request->attributes->get('jwt');
        return response()->json(['status'=>'success','message'=>$message,'data'=>[
            'subject'=>$jwt->sub ?? null, 'issuer'=>$jwt->iss ?? null, 'audience'=>$jwt->aud ?? [],
            'issuedAt'=>isset($jwt->iat) ? gmdate('Y-m-d\\TH:i:s\\Z', $jwt->iat) : null,
            'expiresAt'=>isset($jwt->exp) ? gmdate('Y-m-d\\TH:i:s\\Z', $jwt->exp) : null,
            'preferredUsername'=>$jwt->preferred_username ?? null, 'email'=>$jwt->email ?? null,
            'roles'=>$jwt->realm_access->roles ?? [],
        ]]);
    }
}
