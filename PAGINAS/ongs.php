<?php
    session_start();
    require_once("../conexao.php");

    function h($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    function formatarIdade(int $m): string {
        if ($m < 12) return $m === 1 ? '1 mês' : "$m meses";
        $a = intdiv($m, 12);
        $r = $m % 12;
        $txt = $a === 1 ? '1 ano' : "$a anos";
        if ($r === 0) return $txt;
        return $txt . ' e ' . ($r === 1 ? '1 mês' : "$r meses");
    }
    $SQL_ONGS = "SELECT
        o.cd_ong,
        o.nm_ong,
        o.cd_telefone_ong,
        o.cd_email_ong,
        o.ds_endereco_ong,
        o.nm_cidade_ong,
        o.sg_estado_ong,
        o.dt_aprovacao_ong,
        o.ds_imagem_perfil_ong
    FROM ONG o
    WHERE o.ic_ativo_ong = 1
    ORDER BY o.nm_ong ASC";

    $SQL_PETS = "SELECT
        p.cd_pet,
        p.cd_ong,
        p.nm_pet,
        p.ds_idade_meses_pet,
        p.ds_sexo_pet,
        p.ds_imagem_pet,
        p.dt_cadastro_pet,
        e.nm_especie,
        r.nm_raca
    FROM PET p
    LEFT JOIN ESPECIE e ON e.cd_especie = p.cd_especie_pet
    LEFT JOIN RACA r    ON r.cd_raca    = p.cd_raca_pet
    ORDER BY p.dt_cadastro_pet DESC";

    $ongs = [];
    $resOngs = $conn->query($SQL_ONGS);
    if ($resOngs) {
        while ($o = $resOngs->fetch_assoc()) {
            $id = (int) $o['cd_ong'];
            $ongs[$id] = [
                'id'       => $id,
                'nome'     => $o['nm_ong'],
                'cidade'   => $o['nm_cidade_ong'],
                'uf'       => strtoupper((string) $o['sg_estado_ong']),
                'telefone' => $o['cd_telefone_ong'],
                'email'    => $o['cd_email_ong'],
                'endereco' => $o['ds_endereco_ong'],
                'desde'    => $o['dt_aprovacao_ong'] ? date('d/m/Y', strtotime($o['dt_aprovacao_ong'])) : '',
                'img'      => $o['ds_imagem_perfil_ong'] ? '../' . $o['ds_imagem_perfil_ong'] : '',
                'ini'      => mb_strtoupper(mb_substr($o['nm_ong'], 0, 2)),
                'pets'     => [],
            ];
        }
    }

    $totalPets = 0;
    $resPets = $conn->query($SQL_PETS);
    if ($resPets) {
        while ($p = $resPets->fetch_assoc()) {
            $idOng = (int) $p['cd_ong'];
            if (!isset($ongs[$idOng])) continue; 

            $ongs[$idOng]['pets'][] = [
                'id'      => (int) $p['cd_pet'],
                'nome'    => $p['nm_pet'],
                'img'     => '../' . $p['ds_imagem_pet'],
                'idade'   => formatarIdade((int) $p['ds_idade_meses_pet']),
                'sexo'    => strtoupper(substr((string) $p['ds_sexo_pet'], 0, 1)),
                'especie' => $p['nm_especie'] ?: 'Outros',
                'raca'    => $p['nm_raca'] ?: '',
                'desde'   => date('d/m/Y', strtotime($p['dt_cadastro_pet'])),
            ];
            $totalPets++;
        }
    }

    $estados  = [];
    $especies = [];
    foreach ($ongs as &$o) {
        if ($o['uf'] !== '') $estados[$o['uf']] = true;
        $esp = [];
        foreach ($o['pets'] as $pet) $esp[$pet['especie']] = true;
        $o['especies'] = array_keys($esp);
        foreach ($o['especies'] as $e) $especies[$e] = true;
    }
    unset($o);
    ksort($estados);
    ksort($especies);

    $totalOngs = count($ongs);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AdotaPet - ONGs Parceiras </title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
    <link rel="stylesheet" href="../CSS/ongs.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="shortcut icon" href="/assets/all_images/branco vazado.png">
</head>

<body>
    <?php require_once __DIR__ . '/../navbar.php'; ?>

    <main>
        <section class="ongs-hero">
            <div class="ongs-hero-texto">
                <h1>ONGs parceiras</h1>
                <p>Conheça quem cuida dos animais antes da adoção e veja todos os bichinhos que cada ONG tem para encontrar um lar.</p>
            </div>
            <div class="ongs-hero-numeros">
                <div><strong><?= $totalOngs ?></strong><span><?= $totalOngs === 1 ? 'ONG' : 'ONGs' ?></span></div>
                <div><strong><?= $totalPets ?></strong><span><?= $totalPets === 1 ? 'animal' : 'animais' ?></span></div>
            </div>
        </section>

        <section class="ongs-container">
            <div class="ongs-filtros">
                <form class="search-box" role="search" onsubmit="return false">
                    <input type="search" id="busca-ong" placeholder="Pesquisar ONG ou cidade..." aria-label="Pesquisar ONG">
                    <button type="submit" aria-label="Pesquisar"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>

                <label class="select-box">
                    <i class="fa-solid fa-location-dot"></i>
                    <select id="filtro-estado" aria-label="Filtrar por estado">
                        <option value="">Todos os estados</option>
                        <?php foreach (array_keys($estados) as $uf): ?>
                            <option value="<?= h($uf) ?>"><?= h($uf) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="select-box">
                    <i class="fa-solid fa-paw"></i>
                    <select id="filtro-especie" aria-label="Filtrar por espécie">
                        <option value="">Todas as espécies</option>
                        <?php foreach (array_keys($especies) as $esp): ?>
                            <option value="<?= h(mb_strtolower($esp)) ?>"><?= h($esp) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <p class="ongs-contagem" id="ongs-contagem" aria-live="polite"></p>

            <div class="ongs-grid" id="ongs-grid">
                <?php foreach ($ongs as $o):
                    $qt = count($o['pets']);
                    $local = trim($o['cidade'] . ($o['cidade'] && $o['uf'] ? ' - ' : '') . $o['uf']);
                    $busca = mb_strtolower($o['nome'] . ' ' . $o['cidade']);
                    $espLower = array_map('mb_strtolower', $o['especies']);
                ?>
                <article class="ong-card"
                         data-id="<?= $o['id'] ?>"
                         data-busca="<?= h($busca) ?>"
                         data-uf="<?= h($o['uf']) ?>"
                         data-especies="<?= h(implode('|', $espLower)) ?>">

                    <div class="ong-card-topo">
                        <span class="ong-avatar ong-avatar--lg" data-ini="<?= h($o['ini']) ?>">
                            <?php if ($o['img']): ?>
                                <img src="<?= h($o['img']) ?>" alt="" onerror="this.remove()">
                            <?php endif; ?>
                        </span>
                        <div>
                            <h3><?= h($o['nome']) ?></h3>
                            <?php if ($local): ?>
                                <p class="ong-local"><i class="fa-solid fa-location-dot"></i> <?= h($local) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="ong-qt">
                        <strong><?= $qt ?></strong> <?= $qt === 1 ? 'animal para adoção' : 'animais para adoção' ?>
                    </p>

                    <?php if ($o['especies']): ?>
                        <div class="ong-especies">
                            <?php foreach ($o['especies'] as $esp): ?>
                                <span class="balao"><?= h($esp) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <button type="button" class="btn-ver-mais" data-ong="<?= $o['id'] ?>">Ver animais</button>
                </article>
                <?php endforeach; ?>
            </div>

            <p class="ongs-vazio" id="ongs-vazio" <?= $totalOngs > 0 ? 'hidden' : '' ?>>
                <?= $totalOngs > 0 ? 'Nenhuma ONG encontrada com esses filtros.' : 'Ainda não há ONGs cadastradas.' ?>
            </p>
        </section>
    </main>

    <footer>
        <div class="content-footer">
            <h1>Contatos</h1>
            <p>+55 11 99198-3984</p>
            <p>adotapet@gmail.com</p>
            <p>adotapetportal@hotmail.com</p>
        </div>
        <div class="content-footer">
            <h1>Redes Sociais</h1>
            <li><i class="bi bi-instagram"></i>
                <p>adotapetofc</p>
            </li>
            <li><i class="bi bi-facebook"></i>
                <p>adotapetofc</p>
            </li>
            <li><i class="bi bi-twitter"></i>
                <p>adotapetofc</p>
            </li>
        </div>
        <div class="content-footer">
            <h1>Institucional</h1>
            <a href="#">Termos de Uso</a>
            <a href="/Documentacao.pdf" download="Documentacao.pdf">Documentação</a>
            <a href="#">Conteúdo de Design</a>
        </div>
    </footer>

    <div class="overlay-modal" id="modal">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <button type="button" class="modal-close" aria-label="Fechar">
                <i class="bi bi-x-lg"></i>
            </button>
            <div class="modal-corpo" id="modal-corpo"></div>
        </div>
    </div>

    <script type="application/json" id="dados-ongs"><?=
        json_encode($ongs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
    ?></script>

    <script src="../SCRIPT/headerScript.js"></script>
    <script src="../SCRIPT/ongs.js"></script>
</body>
</html>