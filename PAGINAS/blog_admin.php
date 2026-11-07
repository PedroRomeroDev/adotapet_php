<?php
require_once __DIR__ . '/admin_guard.php';

function blogAdminEsc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$erro = '';
$editorId = 0;
$form = ['titulo' => '', 'resumo' => '', 'conteudo' => '', 'imagem' => '', 'publicado' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = filter_input(INPUT_POST, 'id_post', FILTER_VALIDATE_INT) ?: 0;

    if ($acao === 'excluir' && $id > 0) {
        try {
            $stmt = $conn->prepare('SELECT ds_imagem_capa FROM BLOG_POST WHERE id_post = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $imagemAntiga = $stmt->get_result()->fetch_column();

            $stmt = $conn->prepare('DELETE FROM BLOG_POST WHERE id_post = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $caminhoImagemAntiga = __DIR__ . '/../uploads/blog/' . basename((string) $imagemAntiga);
            if ($imagemAntiga && is_file($caminhoImagemAntiga)) {
                unlink($caminhoImagemAntiga);
            }
            header('Location: blog_admin.php?status=excluido#posts');
            exit;
        } catch (mysqli_sql_exception $e) {
            $erro = 'Não foi possível excluir a publicação. Tente novamente.';
        }
    } elseif ($acao === 'salvar') {
        $editorId = $id;
        $form = [
            'titulo' => trim($_POST['titulo'] ?? ''),
            'resumo' => trim($_POST['resumo'] ?? ''),
            'conteudo' => trim($_POST['conteudo'] ?? ''),
            'imagem' => '',
            'publicado' => isset($_POST['publicado']),
        ];

        if ($form['titulo'] === '' || $form['resumo'] === '' || $form['conteudo'] === '') {
            $erro = 'Preencha o título, o resumo e o conteúdo.';
        } elseif (mb_strlen($form['titulo']) > 180 || mb_strlen($form['resumo']) > 320) {
            $erro = 'O título deve ter até 180 caracteres e o resumo até 320.';
        } elseif (!in_array($_FILES['imagem_capa']['error'] ?? UPLOAD_ERR_NO_FILE, [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE], true)) {
            $erro = 'Não foi possível enviar a imagem. Tente novamente.';
        } elseif (($_FILES['imagem_capa']['size'] ?? 0) > 2 * 1024 * 1024) {
            $erro = 'A imagem deve ter no máximo 2 MB.';
        } else {
            $publicado = $form['publicado'] ? 1 : 0;
            $titulo = $form['titulo'];
            $resumo = $form['resumo'];
            $conteudo = $form['conteudo'];
            $imagemAnterior = '';
            if ($editorId > 0) {
                $stmt = $conn->prepare('SELECT ds_imagem_capa FROM BLOG_POST WHERE id_post = ?');
                $stmt->bind_param('i', $editorId);
                $stmt->execute();
                $imagemAnterior = $stmt->get_result()->fetch_column() ?: '';
            }
            $imagem = $imagemAnterior;
            $form['imagem'] = $imagemAnterior;
            $arquivoNovo = null;

            if (($_FILES['imagem_capa']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['imagem_capa']['tmp_name']);
                if (!isset($tiposPermitidos[$tipo]) || !getimagesize($_FILES['imagem_capa']['tmp_name'])) {
                    $erro = 'Envie uma imagem JPG, PNG ou WEBP válida.';
                } else {
                    $diretorio = __DIR__ . '/../uploads/blog/';
                    if (!is_dir($diretorio)) {
                        mkdir($diretorio, 0755, true);
                    }
                    $nomeArquivo = bin2hex(random_bytes(12)) . '.' . $tiposPermitidos[$tipo];
                    $arquivoNovo = $diretorio . $nomeArquivo;
                    if (move_uploaded_file($_FILES['imagem_capa']['tmp_name'], $arquivoNovo)) {
                        $imagem = 'uploads/blog/' . $nomeArquivo;
                        $form['imagem'] = $imagem;
                    } else {
                        $erro = 'Não foi possível salvar a imagem no servidor.';
                    }
                }
            }

            if ($erro === '') {
                try {
                    if ($editorId > 0) {
                        $stmt = $conn->prepare('UPDATE BLOG_POST SET nm_titulo = ?, ds_resumo = ?, ds_conteudo = ?, ds_imagem_capa = ?, ic_publicado = ? WHERE id_post = ?');
                        $stmt->bind_param('ssssii', $titulo, $resumo, $conteudo, $imagem, $publicado, $editorId);
                    } else {
                        $stmt = $conn->prepare('INSERT INTO BLOG_POST (nm_titulo, ds_resumo, ds_conteudo, ds_imagem_capa, ic_publicado) VALUES (?, ?, ?, ?, ?)');
                        $stmt->bind_param('ssssi', $titulo, $resumo, $conteudo, $imagem, $publicado);
                    }

                    if ($stmt->execute()) {
                        $caminhoImagemAnterior = __DIR__ . '/../uploads/blog/' . basename($imagemAnterior);
                        if ($arquivoNovo && $imagemAnterior && is_file($caminhoImagemAnterior)) {
                            unlink($caminhoImagemAnterior);
                        }
                        header('Location: blog_admin.php?status=salvo#posts');
                        exit;
                    }
                    $erro = 'Não foi possível salvar a publicação. Tente novamente.';
                } catch (mysqli_sql_exception $e) {
                    $erro = 'Não foi possível salvar a publicação. Tente novamente.';
                }
            }

            if ($erro !== '' && $arquivoNovo && is_file($arquivoNovo)) {
                unlink($arquivoNovo);
            }
        }
    }
} elseif (isset($_GET['editar'])) {
    $editorId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT) ?: 0;
    if ($editorId > 0) {
        $stmt = $conn->prepare('SELECT nm_titulo, ds_resumo, ds_conteudo, ds_imagem_capa, ic_publicado FROM BLOG_POST WHERE id_post = ?');
        $stmt->bind_param('i', $editorId);
        $stmt->execute();
        $postEditando = $stmt->get_result()->fetch_assoc();
        if ($postEditando) {
            $form = [
                'titulo' => $postEditando['nm_titulo'],
                'resumo' => $postEditando['ds_resumo'],
                'conteudo' => $postEditando['ds_conteudo'],
                'imagem' => $postEditando['ds_imagem_capa'] ?? '',
                'publicado' => (bool) $postEditando['ic_publicado'],
            ];
        } else {
            $erro = 'Essa publicação não foi encontrada.';
            $editorId = 0;
        }
    }
}

$posts = $conn->query('SELECT id_post, nm_titulo, ds_resumo, ds_imagem_capa, ic_publicado, dt_atualizacao FROM BLOG_POST ORDER BY dt_atualizacao DESC')->fetch_all(MYSQLI_ASSOC);
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar blog | AdotaPet Admin</title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/blog-admin.css">
</head>
<body>
    <main class="blog-admin-wrap">
        <header class="blog-admin-header">
            <div>
                <a class="back-dashboard" href="adotapet_admin.php">← Painel admin</a>
                <p class="admin-kicker">AdotaPet · conteúdo</p>
                <h1>Gerenciar publicações</h1>
                <p class="admin-lead">Crie, revise e publique as histórias do blog.</p>
            </div>
            <div class="admin-header-actions">
                <a class="public-link" href="blog.php" target="_blank" rel="noopener">Ver blog ↗</a>
                <a class="logout-link" href="logout.php">Sair</a>
            </div>
        </header>

        <?php if ($erro !== ''): ?>
            <p class="admin-message is-error" role="alert"><?= blogAdminEsc($erro) ?></p>
        <?php elseif ($status === 'salvo'): ?>
            <p class="admin-message" role="status">Publicação salva.</p>
        <?php elseif ($status === 'excluido'): ?>
            <p class="admin-message" role="status">Publicação excluída.</p>
        <?php endif; ?>

        <section class="editor-panel" id="editor" aria-labelledby="editor-title">
            <div class="panel-heading">
                <div>
                    <p class="admin-kicker"><?= $editorId ? 'Edição' : 'Nova história' ?></p>
                    <h2 id="editor-title"><?= $editorId ? 'Editar publicação' : 'Escrever publicação' ?></h2>
                </div>
                <?php if ($editorId): ?><a class="cancel-edit" href="blog_admin.php#editor">Cancelar edição</a><?php endif; ?>
            </div>

            <form method="post" action="blog_admin.php#editor" class="post-form" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="salvar">
                <input type="hidden" name="id_post" value="<?= $editorId ?>">
                <label for="titulo">Título</label>
                <input id="titulo" name="titulo" maxlength="180" value="<?= blogAdminEsc($form['titulo']) ?>" required>
                <label for="resumo">Resumo</label>
                <textarea id="resumo" name="resumo" rows="2" maxlength="320" required><?= blogAdminEsc($form['resumo']) ?></textarea>
                <label for="imagem-capa">Imagem de capa <span>(JPG, PNG ou WEBP · até 2 MB)</span></label>
                <input id="imagem-capa" name="imagem_capa" type="file" accept="image/jpeg,image/png,image/webp">
                <?php if ($form['imagem'] !== ''): ?>
                    <img class="cover-preview" src="../<?= blogAdminEsc($form['imagem']) ?>" alt="Capa atual da publicação">
                <?php endif; ?>
                <label for="conteudo">Conteúdo</label>
                <textarea id="conteudo" name="conteudo" rows="10" required><?= blogAdminEsc($form['conteudo']) ?></textarea>
                <label class="publish-toggle">
                    <input type="checkbox" name="publicado" value="1" <?= $form['publicado'] ? 'checked' : '' ?>>
                    <span>Publicar no blog</span>
                </label>
                <button class="save-post" type="submit"><?= $editorId ? 'Salvar alterações' : 'Salvar publicação' ?></button>
            </form>
        </section>

        <section class="posts-panel" id="posts" aria-labelledby="posts-title">
            <div class="panel-heading">
                <div>
                    <p class="admin-kicker">Biblioteca do blog</p>
                    <h2 id="posts-title">Publicações <span><?= count($posts) ?></span></h2>
                </div>
            </div>

            <?php if (!$posts): ?>
                <p class="posts-empty">Ainda não há publicações. Escreva a primeira acima.</p>
            <?php else: ?>
                <div class="posts-list">
                    <?php foreach ($posts as $post): ?>
                        <article class="post-row">
                            <div class="post-row-copy">
                                <?php if (!empty($post['ds_imagem_capa'])): ?>
                                    <img class="post-row-cover" src="../<?= blogAdminEsc($post['ds_imagem_capa']) ?>" alt="">
                                <?php endif; ?>
                                <div class="post-meta">
                                    <span class="post-status <?= $post['ic_publicado'] ? 'is-published' : '' ?>"><?= $post['ic_publicado'] ? 'Publicado' : 'Rascunho' ?></span>
                                    <time><?= date('d/m/Y', strtotime($post['dt_atualizacao'])) ?></time>
                                </div>
                                <h3><?= blogAdminEsc($post['nm_titulo']) ?></h3>
                                <p><?= blogAdminEsc($post['ds_resumo']) ?></p>
                            </div>
                            <div class="post-actions">
                                <a class="edit-post" href="blog_admin.php?editar=<?= (int) $post['id_post'] ?>#editor">Editar</a>
                                <form method="post" action="blog_admin.php#posts" onsubmit="return confirm('Excluir esta publicação?')">
                                    <input type="hidden" name="acao" value="excluir">
                                    <input type="hidden" name="id_post" value="<?= (int) $post['id_post'] ?>">
                                    <button class="delete-post" type="submit">Excluir</button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
