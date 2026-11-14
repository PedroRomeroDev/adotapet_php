<?php

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/admin_guard.php';

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

// ---------- Ações de espécies e raças (tratadas aqui mesmo, sem arquivos extras) ----------
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_cadastros'])) $_SESSION['csrf_cadastros'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_cadastros'];
$urlPainel = strtok($_SERVER['REQUEST_URI'], '?');

function voltar_cadastros($msg, $erro = false)
{
    global $urlPainel;
    $_SESSION['flash_cadastros'] = ['msg' => $msg, 'erro' => $erro];
    header('Location: ' . $urlPainel . '#racas-especies');
    exit;
}

function contar($conn, $sql, $tipos, ...$params)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    return (int) $stmt->get_result()->fetch_row()[0];
}

$acaoCad = $_POST['acao'] ?? $_GET['acao'] ?? '';
if (in_array($acaoCad, ['salvar_especie', 'salvar_raca', 'excluir_especie', 'excluir_raca'], true)) {
    if (!hash_equals($csrf, (string) ($_POST['csrf'] ?? $_GET['csrf'] ?? ''))) {
        voltar_cadastros('Sessão expirada. Tente novamente.', true);
    }

    try {
        $id = (int) ($_GET['id'] ?? 0);

        if ($acaoCad === 'salvar_especie') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') voltar_cadastros('Requisição inválida.', true);
            $nome = trim(preg_replace('/\s+/', ' ', (string) ($_POST['nm_especie'] ?? '')));
            if ($nome === '') voltar_cadastros('Informe o nome da espécie.', true);
            if (mb_strlen($nome) > 50) voltar_cadastros('O nome da espécie deve ter até 50 caracteres.', true);
            if (contar($conn, "SELECT COUNT(*) FROM ESPECIE WHERE LOWER(nm_especie) = LOWER(?)", 's', $nome)) {
                voltar_cadastros('Essa espécie já está cadastrada.', true);
            }
            $stmt = $conn->prepare("INSERT INTO ESPECIE (nm_especie) VALUES (?)");
            $stmt->bind_param('s', $nome);
            $stmt->execute();
            voltar_cadastros('Espécie cadastrada com sucesso.');
        }

        if ($acaoCad === 'salvar_raca') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') voltar_cadastros('Requisição inválida.', true);
            $nome = trim(preg_replace('/\s+/', ' ', (string) ($_POST['nm_raca'] ?? '')));
            $especie = (int) ($_POST['cd_especie'] ?? 0);
            if ($nome === '' || $especie <= 0) voltar_cadastros('Preencha a espécie e o nome da raça.', true);
            if (mb_strlen($nome) > 100) voltar_cadastros('O nome da raça deve ter até 100 caracteres.', true);
            if (!contar($conn, "SELECT COUNT(*) FROM ESPECIE WHERE cd_especie = ?", 'i', $especie)) {
                voltar_cadastros('Espécie não encontrada.', true);
            }
            if (contar($conn, "SELECT COUNT(*) FROM RACA WHERE cd_especie = ? AND LOWER(nm_raca) = LOWER(?)", 'is', $especie, $nome)) {
                voltar_cadastros('Essa raça já existe para a espécie escolhida.', true);
            }
            $stmt = $conn->prepare("INSERT INTO RACA (nm_raca, cd_especie) VALUES (?, ?)");
            $stmt->bind_param('si', $nome, $especie);
            $stmt->execute();
            voltar_cadastros('Raça cadastrada com sucesso.');
        }

        if ($acaoCad === 'excluir_especie') {
            if ($id <= 0) voltar_cadastros('Espécie inválida.', true);
            if (contar($conn, "SELECT COUNT(*) FROM RACA WHERE cd_especie = ?", 'i', $id)) {
                voltar_cadastros('Não é possível excluir: há raças vinculadas a essa espécie.', true);
            }
            if (contar($conn, "SELECT COUNT(*) FROM PET WHERE cd_especie_pet = ?", 'i', $id)) {
                voltar_cadastros('Não é possível excluir: há pets cadastrados com essa espécie.', true);
            }
            $stmt = $conn->prepare("DELETE FROM ESPECIE WHERE cd_especie = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            voltar_cadastros('Espécie excluída.');
        }

        if ($acaoCad === 'excluir_raca') {
            if ($id <= 0) voltar_cadastros('Raça inválida.', true);
            if (contar($conn, "SELECT COUNT(*) FROM PET WHERE cd_raca_pet = ?", 'i', $id)) {
                voltar_cadastros('Não é possível excluir: há pets cadastrados com essa raça.', true);
            }
            $stmt = $conn->prepare("DELETE FROM RACA WHERE cd_raca = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            voltar_cadastros('Raça excluída.');
        }
    } catch (Throwable $ex) {
        error_log('[admin] ' . $ex->getMessage());
        voltar_cadastros('Não foi possível concluir a ação. Tente novamente.', true);
    }
}

$usuarios = consultar($conn, "SELECT * FROM USUARIO WHERE COALESCE(ic_banido_usuario, 0) = 0 ORDER BY dt_cadastro DESC");
$banidos = consultar($conn, "SELECT * FROM USUARIO WHERE ic_banido_usuario = 1 ORDER BY dt_cadastro DESC");
$ongsAprovadas = consultar($conn, "SELECT o.*, (SELECT COUNT(*) FROM PET p WHERE p.cd_ong = o.cd_ong) AS total_pets FROM ONG o WHERE o.ic_ativo_ong = 1 ORDER BY o.dt_aprovacao_ong DESC");
$ongsEsperando = consultar($conn, "SELECT * FROM ONG WHERE COALESCE(ic_ativo_ong, 0) = 0 ORDER BY nm_ong");
$totalPets = (int) (consultar($conn, "SELECT COUNT(*) AS t FROM PET")[0]['t'] ?? 0);

// Espécies e raças
$especies = consultar($conn, "SELECT s.*,
    (SELECT COUNT(*) FROM RACA r WHERE r.cd_especie = s.cd_especie) AS total_racas,
    (SELECT COUNT(*) FROM PET p WHERE p.cd_especie_pet = s.cd_especie) AS total_pets
    FROM ESPECIE s ORDER BY s.nm_especie");
$racas = consultar($conn, "SELECT r.*, s.nm_especie,
    (SELECT COUNT(*) FROM PET p WHERE p.cd_raca_pet = r.cd_raca) AS total_pets
    FROM RACA r LEFT JOIN ESPECIE s ON s.cd_especie = r.cd_especie
    ORDER BY s.nm_especie, r.nm_raca");

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
    ['racas-especies', 'tags-fill', count($racas), 'Raças cadastradas', 'blue'],
];

$nav = [
    ['visao-geral', 'grid-1x2-fill', 'Visão geral', null],
    ['usuarios', 'people-fill', 'Usuários', count($usuarios)],
    ['usuarios-banidos', 'slash-circle-fill', 'Usuários banidos', count($banidos)],
    ['ongs-aprovadas', 'patch-check-fill', 'ONGs aprovadas', count($ongsAprovadas)],
    ['ongs-esperando', 'hourglass-split', 'ONGs esperando', count($ongsEsperando)],
    ['racas-especies', 'tags-fill', 'Raças e espécies', count($especies) + count($racas)],
];

// Mensagem de retorno das ações de raça/espécie
$flash = $_SESSION['flash_cadastros'] ?? null;
unset($_SESSION['flash_cadastros']);
$flashMsg = (string) ($flash['msg'] ?? '');
$flashErro = !empty($flash['erro']);

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

$linhasEspecies = [];
foreach ($especies as $s) {
    $linhasEspecies[] = [
        '<strong class="nome">' . e($s['nm_especie']) . '</strong>',
        '<span class="badge">' . (int) $s['total_racas'] . '</span>',
        '<span class="badge">' . (int) $s['total_pets'] . '</span>',
        '<div class="actions">'
            . acao('?acao=excluir_especie&id=' . (int) $s['cd_especie'] . '&csrf=' . $csrf, 'trash3', 'Excluir espécie', 'bad', 'Excluir a espécie ' . $s['nm_especie'] . '? Só é possível se não houver raças nem pets vinculados.')
            . '</div>',
    ];
}

$linhasRacas = [];
foreach ($racas as $r) {
    $linhasRacas[] = [
        '<strong class="nome">' . e($r['nm_raca']) . '</strong>',
        e($r['nm_especie'] ?: '-'),
        '<span class="badge">' . (int) $r['total_pets'] . '</span>',
        '<div class="actions">'
            . acao('?acao=excluir_raca&id=' . (int) $r['cd_raca'] . '&csrf=' . $csrf, 'trash3', 'Excluir raça', 'bad', 'Excluir a raça ' . $r['nm_raca'] . '? Só é possível se nenhum pet a utilizar.')
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
    <style>
        /* Seção Raças e espécies (pode ser movido para admin.css) */
        .duo {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr);
            gap: 16px;
            align-items: start;
        }

        .cad-panel .box-head {
            flex-wrap: wrap;
            gap: 10px;
            margin: 0;
            padding: 18px 20px 14px;
        }

        .cad-panel .box-head h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filtros {
            display: flex;
            gap: 8px;
        }

        .cad-panel .search {
            min-width: 0;
            width: 190px;
            height: 36px;
        }

        .filtro {
            height: 36px;
            padding: 0 10px;
            border: 1px solid var(--a-linha);
            border-radius: var(--a-raio-sm);
            background: var(--a-card);
            color: var(--a-texto);
            font: inherit;
            font-size: .88rem;
            cursor: pointer;
        }

        .filtro:focus {
            outline: 0;
            border-color: var(--cor-primaria);
            box-shadow: var(--a-foco);
        }

        .cad {
            display: flex;
            gap: 8px;
            padding: 0 20px 16px;
            border-bottom: 1px solid var(--a-linha);
        }

        .cad input,
        .cad select {
            flex: 1;
            min-width: 0;
            height: 40px;
            padding: 0 12px;
            border: 1px solid var(--a-linha);
            border-radius: var(--a-raio-sm);
            background: var(--a-fundo);
            color: var(--a-texto);
            font: inherit;
            font-size: .9rem;
            transition: border-color .15s, box-shadow .15s;
        }

        .cad input::placeholder {
            color: var(--a-suave);
        }

        .cad input:focus,
        .cad select:focus {
            outline: 0;
            border-color: var(--cor-primaria);
            box-shadow: var(--a-foco);
        }

        .cad select {
            flex: 0 1 190px;
        }

        .cad .btn {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 40px;
            padding: 0 16px;
        }

        .cad .btn:disabled {
            opacity: .5;
            cursor: not-allowed;
        }

        .nome {
            font-weight: 500;
        }

        .flash {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding: 12px 16px;
            border-left: 4px solid var(--c);
            font-size: .9rem;
        }

        .flash i {
            color: var(--c);
        }

        .flash.ok  { --c: var(--a-verde); }
        .flash.bad { --c: var(--a-vermelho); }

        @media (max-width: 900px) {
            .duo { grid-template-columns: minmax(0, 1fr); }
            .cad { flex-wrap: wrap; }
            .cad select, .cad input { flex: 1 1 100%; }
            .cad .btn { flex: 1 1 100%; justify-content: center; }
            .filtros { width: 100%; flex-wrap: wrap; }
            .filtro, .cad-panel .search { flex: 1 1 100%; width: 100%; }
        }
    </style>
    <script>
        // Mesma chave usada pelo preferencias.js ('modoEscuro'), aplicada cedo para evitar "piscada"
        try {
            document.documentElement.dataset.theme = localStorage.getItem('modoEscuro') === 'true' ? 'dark' : 'light';
        } catch (e) {}
    </script>
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="../SCRIPT/preferencias.js"></script>
</head>
<body>
    <aside id="sidebar">
        <a href="../index.php"><div class="brand"><img src="../assets/all_images/preto vazado.png" alt="AdotaPet"><span>Admin</span></div></a>
        <div>
            <div class="label">Gerenciar</div>
            <nav>
                <?php foreach ($nav as [$id, $icone, $texto, $total]) { ?>
                    <a href="#<?= $id ?>" data-nav="<?= $id ?>"><i class="bi bi-<?= $icone ?>"></i> <?= $texto ?><?= $total !== null ? '<em>' . $total . '</em>' : '' ?></a>
                <?php } ?>
                <a href="blog_admin.php"><i class="bi bi-journal-text"></i> Gerenciar blog</a>
            </nav>
        </div>
        <nav class="side-foot">
            <a href="../index.php"><i class="bi bi-box-arrow-up-right"></i> Ver portal</a>
            <a href="logout.php"><i class="bi bi-box-arrow-left"></i> Sair do admin</a>
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

        <section class="page" id="racas-especies">
            <div class="page-head">
                <div>
                    <h1>Raças e espécies <span class="count"><?= count($racas) ?></span></h1>
                    <p>Cadastros usados pelas ONGs ao publicar animais para adoção.</p>
                </div>
            </div>

            <?php if ($flashMsg !== '') { ?>
                <div class="panel flash <?= $flashErro ? 'bad' : 'ok' ?>" id="flash">
                    <i class="bi bi-<?= $flashErro ? 'exclamation-circle-fill' : 'check-circle-fill' ?>"></i>
                    <span><?= e($flashMsg) ?></span>
                </div>
            <?php } ?>

            <div class="duo">
                <!-- Espécies -->
                <div class="panel cad-panel">
                    <div class="box-head">
                        <h3>Espécies <span class="count"><?= count($especies) ?></span></h3>
                    </div>
                    <form class="cad" method="post" action="<?= e($urlPainel) ?>">
                        <input type="hidden" name="acao" value="salvar_especie">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <input type="text" name="nm_especie" placeholder="Nova espécie (ex.: Cachorro)" aria-label="Nome da espécie" maxlength="50" required>
                        <button class="btn main" type="submit"><i class="bi bi-plus-lg"></i> Adicionar</button>
                    </form>
                    <div class="table-wrap">
                        <table id="t-lista-especies">
                            <thead><tr><th>Espécie</th><th>Raças</th><th>Pets</th><th>Ações</th></tr></thead>
                            <tbody>
                            <?php if (!$linhasEspecies) { ?>
                                <tr class="empty"><td colspan="4"><i class="bi bi-tags"></i><span>Nenhuma espécie cadastrada.</span></td></tr>
                            <?php } else { foreach ($linhasEspecies as $l) { echo '<tr><td>' . implode('</td><td>', $l) . '</td></tr>'; } } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Raças -->
                <div class="panel cad-panel">
                    <div class="box-head">
                        <h3>Raças <span class="count"><?= count($racas) ?></span></h3>
                        <div class="filtros">
                            <select class="filtro" data-filter-especie="lista-racas" aria-label="Filtrar por espécie">
                                <option value="">Todas as espécies</option>
                                <?php foreach ($especies as $s) { ?>
                                    <option value="<?= e($s['nm_especie']) ?>"><?= e($s['nm_especie']) ?></option>
                                <?php } ?>
                            </select>
                            <label class="search"><i class="bi bi-search"></i><input type="search" placeholder="Buscar raça..." data-filter="lista-racas"></label>
                        </div>
                    </div>
                    <form class="cad" method="post" action="<?= e($urlPainel) ?>">
                        <input type="hidden" name="acao" value="salvar_raca">
                        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                        <select name="cd_especie" aria-label="Espécie" required <?= $especies ? '' : 'disabled' ?>>
                            <option value=""><?= $especies ? 'Espécie' : 'Cadastre uma espécie antes' ?></option>
                            <?php foreach ($especies as $s) { ?>
                                <option value="<?= (int) $s['cd_especie'] ?>"><?= e($s['nm_especie']) ?></option>
                            <?php } ?>
                        </select>
                        <input type="text" name="nm_raca" placeholder="Nova raça (ex.: Labrador)" aria-label="Nome da raça" maxlength="100" required <?= $especies ? '' : 'disabled' ?>>
                        <button class="btn main" type="submit" <?= $especies ? '' : 'disabled' ?>><i class="bi bi-plus-lg"></i> Adicionar</button>
                    </form>
                    <div class="table-wrap">
                        <table id="t-lista-racas">
                            <thead><tr><th>Raça</th><th>Espécie</th><th>Pets</th><th>Ações</th></tr></thead>
                            <tbody>
                            <?php if (!$linhasRacas) { ?>
                                <tr class="empty"><td colspan="4"><i class="bi bi-tags"></i><span>Nenhuma raça cadastrada.</span></td></tr>
                            <?php } else { foreach ($linhasRacas as $l) { echo '<tr><td>' . implode('</td><td>', $l) . '</td></tr>'; } } ?>
                            </tbody>
                        </table>
                        <div class="no-result" hidden><i class="bi bi-search"></i><span>Nenhum resultado para a busca.</span></div>
                    </div>
                </div>
            </div>
        </section>
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

            // Tema claro/escuro (usa a mesma chave 'modoEscuro' do preferencias.js)
            var raiz = document.documentElement;
            var botaoTema = document.getElementById('tema');
            function pintarTema() {
                var escuro = raiz.dataset.theme === 'dark';
                botaoTema.querySelector('i').className = 'bi ' + (escuro ? 'bi-sun-fill' : 'bi-moon-stars-fill');
                botaoTema.setAttribute('aria-pressed', String(escuro));
                botaoTema.title = escuro ? 'Modo claro' : 'Modo escuro';
            }
            botaoTema.onclick = function () {
                var escuro = raiz.dataset.theme !== 'dark';
                raiz.dataset.theme = escuro ? 'dark' : 'light';
                document.body.classList.toggle('dark', escuro);
                try { localStorage.setItem('modoEscuro', String(escuro)); } catch (e) {}
            };
            // Atualiza o ícone sempre que o tema mudar (inclusive por outra aba, via preferencias.js)
            new MutationObserver(pintarTema).observe(raiz, { attributes: true, attributeFilter: ['data-theme'] });
            pintarTema();

            // Busca nas tabelas (e filtro por espécie, quando existir)
            function filtrar(nome) {
                var campo = document.querySelector('[data-filter="' + nome + '"]');
                var seletor = document.querySelector('[data-filter-especie="' + nome + '"]');
                var tabela = document.getElementById('t-' + nome);
                var termo = campo.value.toLowerCase().trim();
                var especie = seletor ? seletor.value.toLowerCase() : '';
                var visiveis = 0, total = 0;
                tabela.querySelectorAll('tbody tr:not(.empty)').forEach(function (tr) {
                    var ok = tr.textContent.toLowerCase().indexOf(termo) !== -1;
                    if (ok && especie) ok = tr.cells[1].textContent.trim().toLowerCase() === especie;
                    tr.hidden = !ok;
                    total++;
                    if (ok) visiveis++;
                });
                tabela.parentElement.querySelector('.no-result').hidden = !(total > 0 && visiveis === 0);
            }
            document.querySelectorAll('[data-filter]').forEach(function (campo) {
                campo.addEventListener('input', function () { filtrar(campo.dataset.filter); });
            });
            document.querySelectorAll('[data-filter-especie]').forEach(function (sel) {
                sel.addEventListener('change', function () { filtrar(sel.dataset.filterEspecie); });
            });

            // Limpa a mensagem da URL e some com o aviso depois de alguns segundos
            var flash = document.getElementById('flash');
            if (flash) {
                setTimeout(function () { flash.hidden = true; }, 5000);
            }

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