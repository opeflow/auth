@extends('admin.layout')

@section('titulo', 'Segurança e monitoramento')

@section('conteudo')
<header class="topo" id="visao">
    <div><h1>Central de autenticação</h1><p class="subtitulo">Saúde da plataforma, aplicações conectadas e atividade recente.</p></div>
    <div class="acoes"><button class="botao" type="button" data-alternar-tema>◐ Tema</button><span class="pill ok">● Operacional</span></div>
</header>

<section class="cartoes">
    <article class="cartao"><small>Sistemas cadastrados</small><div class="valor">{{ $total_sistemas }}</div><small>{{ $sistemas_ativos }} ativos</small></article>
    <article class="cartao"><small>Requisições · 24 horas</small><div class="valor">{{ number_format($requisicoes_24h, 0, ',', '.') }}</div><small>Tráfego autenticado pela API</small></article>
    <article class="cartao"><small>Falhas · 24 horas</small><div class="valor" style="color:{{ $falhas_24h > 0 ? 'var(--amarelo)' : 'var(--verde)' }}">{{ number_format($falhas_24h, 0, ',', '.') }}</div><small>Respostas HTTP 4xx e 5xx</small></article>
    <article class="cartao"><small>Falhas de autenticação</small><div class="valor" style="color:{{ $falhas_auth_24h > 0 ? 'var(--amarelo)' : 'var(--verde)' }}">{{ number_format($falhas_auth_24h, 0, ',', '.') }}</div><small>Tokens ou credenciais recusados</small></article>
</section>

@if(session('mensagem'))<div class="aviso">{{ session('mensagem') }}</div>@endif
@if($credenciais = session('credenciais_sistema'))
    <section class="credencial"><strong>Aplicação criada. Copie o segredo agora; ele não será exibido novamente.</strong><p>{{ $credenciais['nome'] }}</p><div>Client ID: <code>{{ $credenciais['client_id'] }}</code></div><div>Client Secret: <code>{{ $credenciais['client_secret'] }}</code></div><div class="muted" style="margin-top:8px">Envie os dois valores em <code>X-Client-Id</code> e <code>X-Client-Secret</code> nas chamadas de cadastro e login.</div></section>
@endif
@if($credenciais = session('credenciais_rotacionadas'))<section class="credencial"><strong>Novo segredo — copie agora; ele não será exibido novamente.</strong><div>Client ID: <code>{{ $credenciais['client_id'] }}</code></div><div>Client Secret: <code>{{ $credenciais['client_secret'] }}</code></div></section>@endif

<section class="bloco" id="sistemas">
    <div class="cabecalho-bloco"><div><h2>Sistemas conectados</h2><div class="muted">Cada aplicação usa uma credencial e uma tabela de usuários separadas.</div></div><span class="pill">{{ $total_sistemas }} total</span></div>
    <form class="form-linha" method="post" action="{{ route('admin.sistemas.criar') }}" style="margin:17px 0 20px">@csrf
        <div class="campo"><label for="nome">Nome da aplicação</label><input id="nome" name="nome" value="{{ old('nome') }}" placeholder="Opeflow Barber" required>@error('nome')<span class="erro-validacao">{{ $message }}</span>@enderror</div>
        <div class="campo"><label for="slug">Identificador</label><input id="slug" name="slug" value="{{ old('slug') }}" placeholder="opeflow-barber" pattern="[A-Za-z0-9_-]+" required>@error('slug')<span class="erro-validacao">{{ $message }}</span>@enderror</div>
        <button class="botao primario" type="submit">＋ Cadastrar sistema</button>
    </form>
    <div class="tabela-wrap"><table><thead><tr><th>Aplicação</th><th>Client ID</th><th>Tabela de usuários</th><th>Estado</th><th>Cadastro público</th><th>Ações</th></tr></thead><tbody>
    @forelse($sistemas as $sistema)
        <tr><td><strong>{{ $sistema->nome }}</strong><div class="muted">{{ $sistema->slug }}</div></td><td><code>{{ $sistema->client_id }}</code></td><td><code>{{ $sistema->tabela_usuarios }}</code></td><td><span class="pill {{ $sistema->habilitado ? 'ok' : 'erro' }}">{{ $sistema->habilitado ? 'Ativo' : 'Desativado' }}</span></td><td><form method="post" action="{{ route('admin.sistemas.cadastro', $sistema->id) }}">@csrf @method('PATCH')<button class="botao" type="submit">{{ $sistema->cadastro_habilitado ? 'Aberto' : 'Fechado' }}</button></form></td><td><div class="acoes"><form method="post" action="{{ route('admin.sistemas.status', $sistema->id) }}">@csrf @method('PATCH')<button class="botao" type="submit">{{ $sistema->habilitado ? 'Bloquear' : 'Reativar' }}</button></form><form method="post" action="{{ route('admin.sistemas.rotacionar-segredo', $sistema->id) }}" onsubmit="return confirm('Rotacionar segredo e revogar todos os tokens emitidos?')">@csrf<button class="botao" type="submit">Rotacionar segredo</button></form></div></td></tr>
    @empty<tr><td colspan="6" class="muted">Nenhuma aplicação cadastrada ainda.</td></tr>@endforelse
    </tbody></table></div>
</section>

<section class="bloco" id="seguranca">
    <div class="cabecalho-bloco"><div><h2>Verificações preventivas</h2><div class="muted">A lista é calculada com as configurações do ambiente atual.</div></div><span class="pill {{ collect($checks)->every(fn ($check) => $check['ok']) ? 'ok' : 'erro' }}">{{ collect($checks)->where('ok', true)->count() }}/{{ count($checks) }} aprovadas</span></div>
    <div class="tabela-wrap"><table><thead><tr><th>Controle</th><th>Estado</th><th>Orientação</th></tr></thead><tbody>@foreach($checks as $check)<tr><td>{{ $check['titulo'] }}</td><td><span class="pill {{ $check['ok'] ? 'ok' : 'erro' }}">{{ $check['ok'] ? 'Aprovado' : 'Revisar' }}</span></td><td class="muted">{{ $check['dica'] }}</td></tr>@endforeach</tbody></table></div>
</section>

<section class="bloco" id="logs">
    <div class="cabecalho-bloco"><div><h2>Logs de segurança</h2><div class="muted">Requisições recentes registradas pela API, sem tokens ou senhas.</div></div><span class="pill">Últimas {{ $logs->count() }}</span></div>
    <div class="tabela-wrap"><table><thead><tr><th>Data e hora</th><th>Evento</th><th>Sistema</th><th>Origem</th><th>Requisição</th><th>Status</th><th>Tempo</th><th>Correlação</th></tr></thead><tbody>
    @forelse($logs as $log)
        <tr><td>{{ \Illuminate\Support\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td><td>{{ $log->event_type }}</td><td>{{ $sistemas->firstWhere('id', $log->system_id)?->nome ?? '—' }}</td><td>{{ $log->client_ip }}</td><td><strong>{{ $log->http_method }}</strong> {{ $log->http_path }}</td><td><span class="pill {{ $log->http_status >= 400 ? 'erro' : 'ok' }}">{{ $log->http_status }}</span></td><td>{{ $log->latency_ms }} ms</td><td><code>{{ $log->correlation_id }}</code></td></tr>
    @empty<tr><td colspan="8" class="muted">Ainda não há requisições registradas.</td></tr>@endforelse
    </tbody></table></div>
    @if($logs->hasPages())<div class="paginacao">{{ $logs->links() }}</div>@endif
</section>
@endsection
