<!doctype html>
<html lang="pt-BR" data-tema="escuro">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Painel') · Opeflow Auth</title>
    <style>
        :root{color-scheme:dark;--fundo:#090d18;--painel:#111827;--painel2:#151e30;--borda:#243047;--texto:#e7edf7;--suave:#8e9bb2;--destaque:#8b9cff;--verde:#57d6a2;--vermelho:#ff7185;--amarelo:#f6c56a}
        [data-tema="claro"]{color-scheme:light;--fundo:#f3f5fb;--painel:#fff;--painel2:#f7f8fc;--borda:#dfe4ef;--texto:#172033;--suave:#68758c;--destaque:#4c5dd3;--verde:#087a52;--vermelho:#c5344a;--amarelo:#9a6700}
        *{box-sizing:border-box}body{margin:0;background:var(--fundo);color:var(--texto);font:14px/1.55 Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}a{color:inherit;text-decoration:none}button,input{font:inherit}.layout{min-height:100vh;display:grid;grid-template-columns:250px minmax(0,1fr)}aside{position:sticky;top:0;height:100vh;padding:26px 18px;background:var(--painel);border-right:1px solid var(--borda);display:flex;flex-direction:column}.marca{font-size:17px;font-weight:750;letter-spacing:-.4px;padding:0 12px 30px;display:flex;align-items:center;gap:9px}.marca img{width:29px;height:29px}.marca strong{color:var(--destaque)}.marca span{color:var(--destaque)}.rotulo{padding:0 12px;color:var(--suave);font-size:10px;letter-spacing:1.3px;text-transform:uppercase;margin:12px 0 8px}.nav{display:grid;gap:5px}.nav a,.sair{padding:10px 12px;border-radius:9px;color:var(--suave);display:flex;gap:10px;align-items:center}.nav a:hover,.nav a.ativo,.sair:hover{background:var(--painel2);color:var(--texto)}.nav .icone{width:17px;text-align:center;color:var(--destaque)}.rodape{margin-top:auto}.conteudo{padding:30px clamp(20px,4vw,54px);max-width:1500px;width:100%;margin:auto}.topo{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:28px}.topo h1{font-size:25px;letter-spacing:-.7px;margin:0}.subtitulo{color:var(--suave);margin:5px 0 0}.acoes{display:flex;gap:9px;align-items:center}.botao{border:1px solid var(--borda);background:var(--painel2);color:var(--texto);padding:9px 13px;border-radius:9px;cursor:pointer}.botao.primario{background:#596be5;border-color:#596be5;color:white;font-weight:650}.botao:hover{filter:brightness(1.12)}.cartoes{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:22px 0}.cartao,.bloco{border:1px solid var(--borda);background:var(--painel);border-radius:14px;padding:18px}.cartao small{color:var(--suave)}.valor{font-size:28px;font-weight:700;letter-spacing:-1px;margin-top:8px}.bloco{margin:16px 0}.cabecalho-bloco{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}.cabecalho-bloco h2{font-size:15px;margin:0}.muted{color:var(--suave)}.tabela-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;white-space:nowrap}th{text-align:left;color:var(--suave);font-weight:550;font-size:11px;letter-spacing:.45px;text-transform:uppercase;padding:11px;border-bottom:1px solid var(--borda)}td{padding:12px 11px;border-bottom:1px solid var(--borda);font-size:13px}tr:last-child td{border:0}.pill{display:inline-flex;padding:3px 8px;border-radius:99px;font-size:11px;background:var(--painel2);color:var(--suave)}.pill.ok{color:var(--verde);background:color-mix(in srgb,var(--verde) 12%,transparent)}.pill.erro{color:var(--vermelho);background:color-mix(in srgb,var(--vermelho) 12%,transparent)}.form-linha{display:flex;gap:10px;align-items:end;flex-wrap:wrap}.campo{display:grid;gap:6px;min-width:180px;flex:1}.campo label{font-size:12px;color:var(--suave)}.campo input{width:100%;padding:10px 11px;border:1px solid var(--borda);border-radius:8px;background:var(--fundo);color:var(--texto);outline:none}.campo input:focus{border-color:var(--destaque)}.credencial{background:color-mix(in srgb,var(--amarelo) 10%,var(--painel));border:1px solid color-mix(in srgb,var(--amarelo) 30%,var(--borda));border-radius:10px;padding:14px;margin-bottom:14px;overflow-wrap:anywhere}.credencial code{font-size:12px}.aviso{padding:10px 12px;border:1px solid var(--borda);border-radius:9px;color:var(--suave);margin:12px 0}.paginacao{padding-top:14px}.paginacao nav{display:flex;justify-content:space-between}.passos{counter-reset:passo;display:grid;gap:12px}.passo{counter-increment:passo;display:grid;grid-template-columns:36px 1fr;gap:13px;padding:15px;border:1px solid var(--borda);border-radius:11px;background:var(--painel2)}.passo:before{content:counter(passo,decimal-leading-zero);color:var(--destaque);font-weight:750;font-variant-numeric:tabular-nums}.passo h3{font-size:14px;margin:0 0 4px}.passo p{margin:0;color:var(--suave)}.passo code{color:var(--texto)}.login-wrap{min-height:100vh;display:grid;place-items:center;padding:20px}.login{width:min(420px,100%);background:var(--painel);border:1px solid var(--borda);border-radius:17px;padding:30px}.erro-validacao{font-size:12px;color:var(--vermelho)}.paginas{display:flex;gap:6px;align-items:center;color:var(--suave)}@media(max-width:960px){.layout{grid-template-columns:205px 1fr}.cartoes{grid-template-columns:repeat(2,1fr)}}@media(max-width:700px){.layout{display:block}aside{position:static;height:auto;padding:12px;display:block;border-right:0;border-bottom:1px solid var(--borda)}.marca{padding:8px 10px 12px}.nav{display:flex;overflow:auto}.nav a{white-space:nowrap}.rotulo,.rodape{display:none}.conteudo{padding:22px 15px}.cartoes{gap:9px}.cartao{padding:13px}.valor{font-size:23px}.topo{align-items:flex-start}.topo h1{font-size:22px}}
    </style>
</head>
<body>
<div class="layout">
    <aside>
        <a class="marca" href="{{ route('admin.inicio') }}"><img src="{{ asset('images/opeflow-auth.svg') }}" alt=""><span><strong>opeflow</strong> auth</span></a>
        <div class="rotulo">Monitoramento</div>
        <nav class="nav">
            <a href="{{ route('admin.inicio') }}#visao"><span class="icone">◫</span> Visão geral</a>
            <a href="{{ route('admin.inicio') }}#sistemas"><span class="icone">▦</span> Sistemas</a>
            <a href="{{ route('admin.inicio') }}#logs"><span class="icone">≋</span> Logs de segurança</a>
            <a href="{{ route('admin.inicio') }}#seguranca"><span class="icone">⛨</span> Verificações</a>
            <a href="{{ route('admin.guia-keycloak') }}" class="{{ request()->routeIs('admin.guia-keycloak') ? 'ativo' : '' }}"><span class="icone">◇</span> Guia do Keycloak</a>
        </nav>
        <div class="rodape">
            <div class="rotulo">Administração</div>
            <form method="post" action="{{ route('admin.sair') }}">@csrf<button class="sair" type="submit" style="width:100%;border:0;background:transparent;cursor:pointer;text-align:left">↪ &nbsp; Encerrar sessão</button></form>
        </div>
    </aside>
    <main class="conteudo">
        @yield('conteudo')
    </main>
</div>
<script>
    const raiz=document.documentElement;const salvo=localStorage.getItem('tema-operix');if(salvo)raiz.dataset.tema=salvo;
    document.querySelectorAll('[data-alternar-tema]').forEach(b=>b.addEventListener('click',()=>{const tema=raiz.dataset.tema==='escuro'?'claro':'escuro';raiz.dataset.tema=tema;localStorage.setItem('tema-operix',tema)}));
</script>
</body>
</html>
