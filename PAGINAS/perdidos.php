<?php
session_start();

require_once __DIR__ . '/../conexao.php';
require_once __DIR__ . '/perdidos_funcoes.php';

$conn->set_charset('utf8mb4');
$_SESSION['csrf_perdidos'] ??= bin2hex(random_bytes(32));

$tutor = null;

if (($_SESSION['tipo_conta'] ?? '') === 'usuario') {
    $stmt = $conn->prepare(
        'SELECT id_usuario
        FROM usuario
        WHERE cd_email_usuario = ?
            AND COALESCE(ic_banido_usuario, 0) = 0'
    );
    $stmt->bind_param('s', $_SESSION['email_usuario']);
    $stmt->execute();
    $tutor = $stmt->get_result()->fetch_assoc();
}

$erro = '';
$form = array_fill_keys(
    ['nome', 'especie', 'descricao', 'localizacao', 'data_desaparecimento', 'contato'],
    ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foto = null;

    try {
        if (!$tutor) {
            throw new RuntimeException('Entre com uma conta de tutor ativa para publicar.');
        }

        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf_perdidos'], $_POST['csrf'])) {
            throw new RuntimeException('Sua sessão expirou. Atualize a página e tente novamente.');
        }

        if (($_POST['acao'] ?? '') === 'encontrado') {
            $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);

            if (!$id || $id < 1) {
                throw new RuntimeException('Anúncio inválido.');
            }

            $stmt = $conn->prepare(
                'UPDATE pet_perdido
                SET encontrado = 1
                WHERE id = ? AND id_usuario_fk = ? AND encontrado = 0'
            );
            $stmt->bind_param('ii', $id, $tutor['id_usuario']);
            $stmt->execute();

            if (!$stmt->affected_rows) {
                throw new RuntimeException('Não foi possível atualizar esse anúncio.');
            }
        } else {
            $form = perdidoValidar($_POST);
            $arquivo = $_FILES['foto'] ?? [];

            if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($arquivo['size'] ?? 0) > 2 * 1024 * 1024) {
                throw new RuntimeException('Envie uma foto JPG, PNG ou WEBP de até 2 MB.');
            }

            $tipos = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

            if (!isset($tipos[$mime]) || !getimagesize($arquivo['tmp_name'])) {
                throw new RuntimeException('A foto precisa ser uma imagem JPG, PNG ou WEBP válida.');
            }

            $pasta = __DIR__ . '/../uploads/perdidos/';

            if (!is_dir($pasta) && !mkdir($pasta, 0755, true)) {
                throw new RuntimeException('Não foi possível preparar o envio da foto.');
            }

            $imagem = 'uploads/perdidos/' . bin2hex(random_bytes(16)) . '.' . $tipos[$mime];
            $foto = __DIR__ . '/../' . $imagem;

            if (!move_uploaded_file($arquivo['tmp_name'], $foto)) {
                throw new RuntimeException('Não foi possível salvar a foto.');
            }

            $stmt = $conn->prepare(
                'INSERT INTO pet_perdido (
                    id_usuario_fk,
                    nome,
                    especie,
                    descricao,
                    localizacao,
                    data_desaparecimento,
                    contato,
                    imagem
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'isssssss',
                $tutor['id_usuario'],
                $form['nome'],
                $form['especie'],
                $form['descricao'],
                $form['localizacao'],
                $form['data_desaparecimento'],
                $form['contato'],
                $imagem
            );
            $stmt->execute();
        }

        header('Location: perdidos.php?salvo=1');
        exit;
    } catch (Throwable $e) {
        if ($foto && is_file($foto)) {
            unlink($foto);
        }

        $erro = $e instanceof RuntimeException && !($e instanceof mysqli_sql_exception)
            ? $e->getMessage()
            : 'Não foi possível salvar o anúncio. Tente novamente.';

        foreach ($form as $campo => $valor) {
            $form[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
        }
    }
}

$busca = is_string($_GET['busca'] ?? null) ? mb_substr(trim($_GET['busca']), 0, 100) : '';
$termo = '%' . $busca . '%';
$pets = [];
$indisponivel = false;

try {
    $stmt = $conn->prepare(
        'SELECT *
        FROM pet_perdido
        WHERE nome LIKE ? OR localizacao LIKE ?
        ORDER BY criado_em DESC, id DESC'
    );
    $stmt->bind_param('ss', $termo, $termo);
    $stmt->execute();
    $pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    $indisponivel = true;
}

function perdidoEsc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Divulgue um pet perdido e ajude os animais da sua região a voltar para casa.">
    <title>Pets perdidos | AdotaPet</title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/blog.css">
    <link rel="stylesheet" href="../CSS/perdidos.css">
    <script src="../SCRIPT/headerScript.js" defer></script>
    <script src="../SCRIPT/preferencias.js"></script>
</head>
<body>
    <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
    <?php require __DIR__ . '/../navbar.php'; ?>

    <main class="blog-wrap perdidos-wrap" id="conteudo">
        <header class="blog-hero perdidos-hero">
            <div>
                <h1>Pets perdidos.<br>Ajude a trazer de volta.</h1>
                <p class="blog-intro">
                    Uma foto, o último local onde foi visto e um contato podem ajudar seu pet a voltar para casa.
                </p>
                <a class="blog-button" href="#anunciar">Anunciar pet perdido</a>
            </div>
            <div class="perdidos-guide">
                <h2>Viu algum destes pets?</h2>
                <p>Confira as características e o local do desaparecimento. Se tiver uma pista, fale com o tutor pelo contato do anúncio.</p>
                <a href="#anuncios">Consultar anúncios ↓</a>
            </div>
        </header>

        <?php if ($erro): ?>
            <p class="perdidos-message is-error" role="alert"><?= perdidoEsc($erro) ?></p>
        <?php elseif (isset($_GET['salvo'])): ?>
            <p class="perdidos-message" role="status">Anúncio atualizado com sucesso.</p>
        <?php endif; ?>

        <section id="anuncios" class="blog-posts" aria-labelledby="titulo-anuncios">
            <div class="blog-section-heading">
                <h2 id="titulo-anuncios">Procurando por eles</h2>
            </div>

            <form class="perdidos-search" method="get">
                <label for="busca">Buscar por nome, cidade ou bairro</label>
                <div>
                    <input id="busca" name="busca" maxlength="100" value="<?= perdidoEsc($busca) ?>">
                    <button type="submit">Buscar pets</button>
                </div>
            </form>

            <?php if ($indisponivel): ?>
                <p role="alert" class="blog-empty">Os anúncios estão indisponíveis no momento. Tente novamente mais tarde.</p>
            <?php endif; ?>

            <?php foreach ([0 => 'perdidos', 1 => 'achados'] as $encontrado => $grupo): ?>
                <?php $lista = array_filter($pets, fn($pet) => (int) $pet['encontrado'] === $encontrado); ?>

                <?php if ($encontrado): ?>
                    <h2 id="pets-achados">De volta para casa</h2>
                <?php endif; ?>

                <?php if (!$lista && !$indisponivel): ?>
                    <p class="blog-empty">
                        <?= $encontrado ? 'Os reencontros aparecerão aqui.' : 'Nenhum anúncio encontrado. Você pode publicar o primeiro ou tentar outra busca.' ?>
                    </p>
                <?php endif; ?>

                <div class="perdidos-grid">
                    <?php foreach ($lista as $pet): ?>
                        <article class="perdido-card">
                            <img
                                src="../<?= perdidoEsc($pet['imagem']) ?>"
                                alt="Foto de <?= perdidoEsc($pet['nome']) ?>"
                                loading="lazy"
                                width="600"
                                height="450"
                            >
                            <div class="perdido-copy">
                                <span class="perdido-status">
                                    <?= $encontrado ? 'Encontrado' : 'Desaparecido em ' . date('d/m/Y', strtotime($pet['data_desaparecimento'])) ?>
                                </span>
                                <h3><?= perdidoEsc($pet['nome']) ?></h3>
                                <p><strong><?= perdidoEsc($pet['especie']) ?> · <?= perdidoEsc($pet['localizacao']) ?></strong></p>
                                <p class="perdido-description"><?= nl2br(perdidoEsc($pet['descricao'])) ?></p>

                                <?php if (!$encontrado): ?>
                                    <a class="blog-button" href="tel:<?= perdidoEsc($pet['contato']) ?>">Ligar para o tutor</a>
                                    <p>Contato: <?= perdidoEsc($pet['contato']) ?></p>
                                <?php endif; ?>

                                <?php if (!$encontrado && $tutor && (int) $pet['id_usuario_fk'] === (int) $tutor['id_usuario']): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf" value="<?= perdidoEsc($_SESSION['csrf_perdidos']) ?>">
                                        <input type="hidden" name="acao" value="encontrado">
                                        <input type="hidden" name="id" value="<?= (int) $pet['id'] ?>">
                                        <button class="perdidos-secondary">Marcar como encontrado</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <section id="anunciar" class="blog-posts" aria-labelledby="titulo-anunciar">
            <div class="blog-section-heading">
                <h2 id="titulo-anunciar">Anuncie seu pet perdido</h2>
            </div>

            <?php if (!$tutor): ?>
                <div class="blog-empty">
                    <p>Entre com sua conta de tutor para publicar e acompanhar seu anúncio.</p>
                    <a class="blog-button" href="login.php">Entrar na conta</a>
                </div>
            <?php else: ?>
                <form method="post" enctype="multipart/form-data" class="perdidos-form">
                    <input type="hidden" name="csrf" value="<?= perdidoEsc($_SESSION['csrf_perdidos']) ?>">
                    <p class="perdidos-full">Todos os campos são obrigatórios. O telefone será público para que as pessoas possam entrar em contato.</p>

                    <?php foreach (
                        [
                            'nome' => ['Nome do pet', 'text', 80],
                            'especie' => ['Espécie (cão, gato ou outra)', 'text', 30],
                            'localizacao' => ['Último local visto (cidade, bairro e referência)', 'text', 200],
                            'data_desaparecimento' => ['Data do desaparecimento', 'date', 10],
                            'contato' => ['Telefone de contato com DDD', 'tel', 20],
                        ] as $campo => [$label, $tipo, $limite]
                    ): ?>
                        <div>
                            <label for="<?= $campo ?>"><?= $label ?></label>
                            <input
                                required
                                id="<?= $campo ?>"
                                name="<?= $campo ?>"
                                type="<?= $tipo ?>"
                                maxlength="<?= $limite ?>"
                                <?= $tipo === 'date' ? 'max="' . date('Y-m-d') . '"' : '' ?>
                                value="<?= perdidoEsc($form[$campo]) ?>"
                            >
                        </div>
                    <?php endforeach; ?>

                    <div>
                        <label for="foto">Foto do pet (JPG, PNG ou WEBP, até 2 MB)</label>
                        <input required id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="perdidos-full">
                        <label for="descricao">Descrição e características (cor, porte, coleira, comportamento)</label>
                        <textarea required id="descricao" name="descricao" maxlength="3000" rows="5"><?= perdidoEsc($form['descricao']) ?></textarea>
                    </div>
                    <button type="submit">Publicar anúncio</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
