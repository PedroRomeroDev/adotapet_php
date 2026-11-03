<?php

date_default_timezone_set('America/Sao_Paulo');
session_start();
require_once("../conexao.php");

/* Proteja o painel assim que souber o nome da sessão do seu login de admin:
if (empty($_SESSION['email_admin'])) { header("Location: login.php"); exit; }
*/

function e($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function consultar($conn, $sql)
{
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function data($v) { return $v ? date("d/m/Y", strtotime($v)) : '-'; }

function telefone($v)
{
    $n = preg_replace('/\D/', '', (string) $v);
    if (strlen($n) === 11) return sprintf('(%s) %s-%s', substr($n, 0, 2), substr($n, 2, 5), substr($n, 7));
    if (strlen($n) === 10) return sprintf('(%s) %s-%s', substr($n, 0, 2), substr($n, 2, 4), substr($n, 6));
    return $n === '' ? '-' : $n;
}

function cnpj($v)
{
    $n = preg_replace('/\D/', '', (string) $v);
    return strlen($n) === 14
        ? sprintf('%s.%s.%s/%s-%s', substr($n, 0, 2), substr($n, 2, 3), substr($n, 5, 3), substr($n, 8, 4), substr($n, 12))
        : ($n === '' ? '-' : $n);
}

function iniciais($nome)
{
    $p = preg_split('/\s+/', trim((string) $nome));
    $i = mb_strtoupper(mb_substr($p[0] ?? '?', 0, 1));
    return count($p) > 1 ? $i . mb_strtoupper(mb_substr(end($p), 0, 1)) : $i;
}

function pessoa($nome, $sub, $img = null)
{
    $avatar = $img ? '<img src="' . e('../' . $img) . '" alt="">' : e(iniciais($nome));
    return '<div class="person"><span class="avatar">' . $avatar . '</span><div><strong>' . e($nome) . '</strong><small>' . e($sub) . '</small></div></div>';
}

function acao($href, $icone, $titulo, $tom, $confirmar = null)
{
    $c = $confirmar ? ' data-confirm="' . e($confirmar) . '"' : '';
    return '<a class="act ' . $tom . '" href="' . e($href) . '" title="' . e($titulo) . '" data-title="' . e($titulo) . '" data-tom="' . $tom . '"' . $c . '><i class="bi bi-' . $icone . '"></i></a>';
}

function secao($id, $titulo, $desc, $icone, $cabecalhos, $linhas, $vazio)
{
    ob_start(); ?>
    <section class="page" id="<?= $id ?>">
        <div class="page-head">
            <div>
                <h1><?= e($titulo) ?> <span class="count"><?= count($linhas) ?></span></h1>
                <p><?= e($desc) ?></p>
            </div>
            <label class="search"><i class="bi bi-search"></i><input type="search" placeholder="Buscar nesta lista..." data-filter="<?= $id ?>"></label>
        </div>
        <div class="panel table-wrap">
            <table id="t-<?= $id ?>">
                <thead><tr><?php foreach ($cabecalhos as $h) { echo '<th>' . e($h) . '</th>'; } ?></tr></thead>
                <tbody>
                <?php if (!$linhas) { ?>
                    <tr class="empty"><td colspan="<?= count($cabecalhos) ?>"><i class="bi bi-<?= $icone ?>"></i><span><?= e($vazio) ?></span></td></tr>
                <?php } else { foreach ($linhas as $l) { echo '<tr><td>' . implode('</td><td>', $l) . '</td></tr>'; } } ?>
                </tbody>
            </table>
            <div class="no-result" hidden><i class="bi bi-search"></i><span>Nenhum resultado para a busca.</span></div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

$usuarios = consultar($conn, "SELECT * FROM USUARIO WHERE COALESCE(ic_banido_usuario, 0) = 0 ORDER BY dt_cadastro DESC");
$banidos = consultar($conn, "SELECT * FROM USUARIO WHERE ic_banido_usuario = 1 ORDER BY dt_cadastro DESC");
$ongsAprovadas = consultar($conn, "SELECT o.*, (SELECT COUNT(*) FROM PET p WHERE p.cd_ong = o.cd_ong) AS total_pets FROM ONG o WHERE o.ic_ativo_ong = 1 ORDER BY o.dt_aprovacao_ong DESC");
$ongsEsperando = consultar($conn, "SELECT * FROM ONG WHERE COALESCE(ic_ativo_ong, 0) = 0 ORDER BY nm_ong");
$totalPets = (int) (consultar($conn, "SELECT COUNT(*) AS t FROM PET")[0]['t'] ?? 0);

$nomeAdmin = $_SESSION['nome_admin'] ?? 'Admin';
$hora = (int) date('G');
$saudacao = $hora < 12 ? 'Bom dia' : ($hora < 18 ? 'Boa tarde' : 'Boa noite');
$dias = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$hoje = ucfirst($dias[date('w')]) . ', ' . date('j') . ' de ' . $meses[date('n') - 1] . ' de ' . date('Y');

$stats = [
    ['usuarios', 'people-fill', count($usuarios), 'Usuários ativos', 'blue'],
    ['usuarios-banidos', 'slash-circle-fill', count($banidos), 'Usuários banidos', 'red'],
    ['ongs-aprovadas', 'patch-check-fill', count($ongsAprovadas), 'ONGs aprovadas', 'green'],
    ['ongs-esperando', 'hourglass-split', count($ongsEsperando), 'ONGs em análise', 'amber'],
    ['ongs-aprovadas', 'balloon-heart-fill', $totalPets, 'Pets cadastrados', 'orange'],
];

$nav = [
    ['visao-geral', 'grid-1x2-fill', 'Visão geral', null],
    ['usuarios', 'people-fill', 'Usuários', count($usuarios)],
    ['usuarios-banidos', 'slash-circle-fill', 'Usuários banidos', count($banidos)],
    ['ongs-aprovadas', 'patch-check-fill', 'ONGs aprovadas', count($ongsAprovadas)],
    ['ongs-esperando', 'hourglass-split', 'ONGs esperando', count($ongsEsperando)],
];

// Linhas das tabelas
$linhasUsuarios = [];
foreach ($usuarios as $u) {
    $linhasUsuarios[] = [
        pessoa($u['nm_usuario'], $u['cd_email_usuario'], $u['ds_imagem_perfil_usuario'] ?? null),
        e(telefone($u['cd_telefone_usuario'])),
        e($u['nm_cidade_usuario'] ?: '-'),
        data($u['dt_cadastro']),
        '<div class="actions">'
            . acao('banir_conta.php?id=' . (int) $u['id_usuario'], 'ban', 'Banir usuário', 'warn', 'Banir ' . $u['nm_usuario'] . '? O acesso à conta será bloqueado.')
            . acao('excluir_conta.php?id=' . (int) $u['id_usuario'], 'trash3', 'Excluir conta', 'bad', 'Excluir a conta de ' . $u['nm_usuario'] . '? Esta ação não pode ser desfeita.')
            . '</div>',
    ];
}

$linhasBanidos = [];
foreach ($banidos as $u) {
    $linhasBanidos[] = [
        pessoa($u['nm_usuario'], $u['cd_email_usuario'], $u['ds_imagem_perfil_usuario'] ?? null),
        e(telefone($u['cd_telefone_usuario'])),
        e($u['nm_cidade_usuario'] ?: '-'),
        data($u['dt_cadastro']),
        '<div class="actions">'
            . acao('desbanir_conta.php?id=' . (int) $u['id_usuario'], 'arrow-counterclockwise', 'Desbanir', 'ok')
            . acao('excluir_conta.php?id=' . (int) $u['id_usuario'], 'trash3', 'Excluir conta', 'bad', 'Excluir a conta de ' . $u['nm_usuario'] . '? Esta ação não pode ser desfeita.')
            . '</div>',
    ];
}

$linhasAprovadas = [];
foreach ($ongsAprovadas as $o) {
    $linhasAprovadas[] = [
        pessoa($o['nm_ong'], $o['cd_email_ong'], $o['ds_imagem_perfil_ong'] ?? null),
        e(cnpj($o['cd_cnpj_ong'])),
        e($o['nm_responsavel_ong']),
        e(trim($o['nm_cidade_ong'] . '/' . $o['sg_estado_ong'], '/')),
        '<span class="badge">' . (int) $o['total_pets'] . '</span>',
        data($o['dt_aprovacao_ong']),
        '<div class="actions">'
            . acao('revogar_ong.php?id=' . (int) $o['cd_ong'], 'x-circle', 'Revogar aprovação', 'warn', 'Revogar a aprovação de ' . $o['nm_ong'] . '? A ONG deixará de poder publicar animais.')
            . acao('excluir_ong.php?id=' . (int) $o['cd_ong'], 'trash3', 'Excluir ONG', 'bad', 'Excluir ' . $o['nm_ong'] . ' e todos os seus pets? Esta ação não pode ser desfeita.')
            . '</div>',
    ];
}

$linhasEsperando = [];
foreach ($ongsEsperando as $o) {
    $linhasEsperando[] = [
        pessoa($o['nm_ong'], $o['cd_email_ong'], $o['ds_imagem_perfil_ong'] ?? null),
        e(cnpj($o['cd_cnpj_ong'])),
        e($o['nm_responsavel_ong']),
        e(telefone($o['cd_telefone_ong'])),
        e(trim($o['nm_cidade_ong'] . '/' . $o['sg_estado_ong'], '/')),
        '<div class="actions">'
            . acao('aprovar_ong.php?id=' . (int) $o['cd_ong'], 'check-lg', 'Aprovar ONG', 'ok')
            . acao('excluir_ong.php?id=' . (int) $o['cd_ong'], 'x-lg', 'Recusar cadastro', 'bad', 'Recusar e excluir o cadastro de ' . $o['nm_ong'] . '?')
            . '</div>',
    ];
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdotaPet - Painel Administrador</title>
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../CSS/admin.css">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <script>
        try {
            var t = localStorage.getItem('admin-tema') || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
</head>
<body>
    <aside id="sidebar">
        <div class="brand"><img src="../assets/all_images/preto vazado.png" alt="AdotaPet"><span>Admin</span></div>
        <div>
            <div class="label">Gerenciar</div>
            <nav>
                <?php foreach ($nav as [$id, $icone, $texto, $total]) { ?>
                    <a href="#<?= $id ?>" data-nav="<?= $id ?>"><i class="bi bi-<?= $icone ?>"></i> <?= $texto ?><?= $total !== null ? '<em>' . $total . '</em>' : '' ?></a>
                <?php } ?>
            </nav>
        </div>
        <nav class="side-foot">
            <a href="../index.php"><i class="bi bi-box-arrow-up-right"></i> Ver portal</a>
        </nav>
    </aside>

    <main>
        <header class="topbar">
            <button class="icon-btn menu-btn" id="menu" aria-label="Abrir menu"><i class="bi bi-list"></i></button>
            <div class="hello">
                <h2><?= $saudacao ?>, <?= e($nomeAdmin) ?>!</h2>
                <p><?= $hoje ?> · <?= count($ongsEsperando) ? 'Você tem <strong>' . count($ongsEsperando) . '</strong> ONG(s) aguardando análise.' : 'Tudo em dia por aqui.' ?></p>
            </div>
            <button class="icon-btn" id="tema" aria-label="Alternar tema"><i class="bi bi-moon-stars-fill"></i></button>
            <div class="me"><span class="avatar"><?= e(iniciais($nomeAdmin)) ?></span><span><?= e($nomeAdmin) ?></span></div>
        </header>

        <section class="page" id="visao-geral">
            <div class="stats">
                <?php foreach ($stats as [$destino, $icone, $valor, $texto, $cor]) { ?>
                    <a href="#<?= $destino ?>" class="panel stat <?= $cor ?>">
                        <span class="ico"><i class="bi bi-<?= $icone ?>"></i></span>
                        <div><strong><?= $valor ?></strong><small><?= $texto ?></small></div>
                    </a>
                <?php } ?>
            </div>

            <div class="grid2">
                <div class="panel box">
                    <div class="box-head"><h3>Aguardando aprovação</h3><a href="#ongs-esperando">Ver todas</a></div>
                    <?php if (!$ongsEsperando) { ?>
                        <p class="row" style="color:var(--muted)">Nenhuma ONG esperando aprovação.</p>
                    <?php } else { foreach (array_slice($ongsEsperando, 0, 5) as $o) { ?>
                        <div class="row">
                            <?= pessoa($o['nm_ong'], $o['nm_cidade_ong'] . '/' . $o['sg_estado_ong'], $o['ds_imagem_perfil_ong'] ?? null) ?>
                            <div class="actions">
                                <?= acao('aprovar_ong.php?id=' . (int) $o['cd_ong'], 'check-lg', 'Aprovar ONG', 'ok') ?>
                                <?= acao('excluir_ong.php?id=' . (int) $o['cd_ong'], 'x-lg', 'Recusar cadastro', 'bad', 'Recusar e excluir o cadastro de ' . $o['nm_ong'] . '?') ?>
                            </div>
                        </div>
                    <?php } } ?>
                </div>

                <div class="panel box">
                    <div class="box-head"><h3>Últimos usuários</h3><a href="#usuarios">Ver todos</a></div>
                    <?php if (!$usuarios) { ?>
                        <p class="row" style="color:var(--muted)">Nenhum usuário cadastrado ainda.</p>
                    <?php } else { foreach (array_slice($usuarios, 0, 5) as $u) { ?>
                        <div class="row">
                            <?= pessoa($u['nm_usuario'], $u['cd_email_usuario'], $u['ds_imagem_perfil_usuario'] ?? null) ?>
                            <time><?= data($u['dt_cadastro']) ?></time>
                        </div>
                    <?php } } ?>
                </div>
            </div>
        </section>

        <?= secao('usuarios', 'Usuários', 'Pessoas cadastradas na plataforma.', 'people', ['Usuário', 'Telefone', 'Cidade', 'Cadastro', 'Ações'], $linhasUsuarios, 'Nenhum usuário encontrado.') ?>
        <?= secao('usuarios-banidos', 'Usuários banidos', 'Contas com acesso bloqueado.', 'shield-check', ['Usuário', 'Telefone', 'Cidade', 'Cadastro', 'Ações'], $linhasBanidos, 'Nenhum usuário banido.') ?>
        <?= secao('ongs-aprovadas', 'ONGs aprovadas', 'Organizações liberadas para publicar animais.', 'patch-check', ['ONG', 'CNPJ', 'Responsável', 'Cidade', 'Pets', 'Aprovada em', 'Ações'], $linhasAprovadas, 'Nenhuma ONG aprovada.') ?>
        <?= secao('ongs-esperando', 'ONGs esperando', 'Cadastros aguardando a sua análise.', 'emoji-smile', ['ONG', 'CNPJ', 'Responsável', 'Telefone', 'Cidade', 'Ações'], $linhasEsperando, 'Nenhuma ONG esperando aprovação.') ?>
    </main>

    <dialog id="confirmar">
        <div class="ico" id="c-ico"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <h3 id="c-titulo"></h3>
        <p id="c-texto"></p>
        <div class="modal-actions">
            <button class="btn" id="c-cancelar">Cancelar</button>
            <a class="btn main" id="c-ok" href="#">Confirmar</a>
        </div>
    </dialog>

    <script>
        (function () {
            var paginas = document.querySelectorAll('.page');
            var links = document.querySelectorAll('[data-nav]');
            var lateral = document.getElementById('sidebar');

            function abrir() {
                var id = location.hash.slice(1);
                if (!document.getElementById(id) || !document.getElementById(id).classList.contains('page')) id = 'visao-geral';
                paginas.forEach(function (p) { p.classList.toggle('show', p.id === id); });
                links.forEach(function (l) { l.classList.toggle('active', l.dataset.nav === id); });
                lateral.classList.remove('open');
                window.scrollTo(0, 0);
            }
            window.addEventListener('hashchange', abrir);
            abrir();

            document.getElementById('menu').onclick = function () { lateral.classList.toggle('open'); };

            // Tema claro/escuro
            var raiz = document.documentElement;
            var iconeTema = document.querySelector('#tema i');
            function pintarTema() { iconeTema.className = 'bi ' + (raiz.dataset.theme === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'); }
            document.getElementById('tema').onclick = function () {
                raiz.dataset.theme = raiz.dataset.theme === 'dark' ? 'light' : 'dark';
                try { localStorage.setItem('admin-tema', raiz.dataset.theme); } catch (e) {}
                pintarTema();
            };
            pintarTema();

            // Busca nas tabelas
            document.querySelectorAll('[data-filter]').forEach(function (campo) {
                campo.addEventListener('input', function () {
                    var termo = campo.value.toLowerCase().trim();
                    var tabela = document.getElementById('t-' + campo.dataset.filter);
                    var visiveis = 0, total = 0;
                    tabela.querySelectorAll('tbody tr:not(.empty)').forEach(function (tr) {
                        var ok = tr.textContent.toLowerCase().indexOf(termo) !== -1;
                        tr.hidden = !ok;
                        total++;
                        if (ok) visiveis++;
                    });
                    tabela.parentElement.querySelector('.no-result').hidden = !(total > 0 && visiveis === 0);
                });
            });

            // Confirmação para ações perigosas
            var dialogo = document.getElementById('confirmar');
            var tons = { bad: ['var(--red)', 'trash3-fill'], warn: ['var(--amber)', 'exclamation-triangle-fill'] };
            document.addEventListener('click', function (ev) {
                var a = ev.target.closest('a[data-confirm]');
                if (!a) return;
                ev.preventDefault();
                var tom = tons[a.dataset.tom] || tons.warn;
                dialogo.style.setProperty('--c', tom[0]);
                document.querySelector('#c-ico i').className = 'bi bi-' + tom[1];
                document.getElementById('c-titulo').textContent = a.dataset.title;
                document.getElementById('c-texto').textContent = a.dataset.confirm;
                document.getElementById('c-ok').href = a.getAttribute('href');
                dialogo.showModal();
            });
            document.getElementById('c-cancelar').onclick = function () { dialogo.close(); };
            dialogo.addEventListener('click', function (ev) { if (ev.target === dialogo) dialogo.close(); });
        })();
    </script>
</body>
</html>