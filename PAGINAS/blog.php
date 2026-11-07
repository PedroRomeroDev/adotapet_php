<?php
require_once __DIR__ . '/../conexao.php';

function blogEsc($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

$post = null;
$posts = [];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    $stmt = $conn->prepare('SELECT id_post, nm_titulo, ds_resumo, ds_conteudo, ds_imagem_capa, dt_criacao FROM BLOG_POST WHERE id_post = ? AND ic_publicado = 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    if (!$post) {
        http_response_code(404);
    }
} else {
    $result = $conn->query('SELECT id_post, nm_titulo, ds_resumo, ds_conteudo, ds_imagem_capa, dt_criacao FROM BLOG_POST WHERE ic_publicado = 1 ORDER BY dt_criacao DESC');
    $posts = $result->fetch_all(MYSQLI_ASSOC);
}

function blogData($valor): string
{
    return date('d/m/Y', strtotime($valor));
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? blogEsc($post['nm_titulo']) . ' | Blog AdotaPet' : 'Blog | AdotaPet' ?></title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/blog.css">
</head>
<body>
    <?php require_once __DIR__ . '/../navbar.php'; ?>
    <main class="blog-wrap">
        <?php if ($post): ?>
            <article class="blog-article">
                <a class="blog-back" href="blog.php">← Voltar ao blog</a>
                <?php if (!empty($post['ds_imagem_capa'])): ?>
                    <img class="blog-cover blog-cover-article" src="../<?= blogEsc($post['ds_imagem_capa']) ?>" alt="Capa: <?= blogEsc($post['nm_titulo']) ?>">
                <?php else: ?>
                <div class="blog-art blog-art-article" aria-hidden="true">
                    <svg viewBox="0 0 160 140" role="img">
                        <path d="M80 66c-13 0-29 14-29 29 0 12 10 18 29 18s29-6 29-18C109 80 93 66 80 66Z" fill="currentColor"/>
                        <ellipse cx="42" cy="52" rx="12" ry="17" transform="rotate(-28 42 52)" fill="currentColor"/>
                        <ellipse cx="68" cy="31" rx="12" ry="17" transform="rotate(-8 68 31)" fill="currentColor"/>
                        <ellipse cx="96" cy="31" rx="12" ry="17" transform="rotate(8 96 31)" fill="currentColor"/>
                        <ellipse cx="120" cy="52" rx="12" ry="17" transform="rotate(28 120 52)" fill="currentColor"/>
                    </svg>
                </div>
                <?php endif; ?>
                <p class="blog-date"><?= blogData($post['dt_criacao']) ?></p>
                <h1><?= blogEsc($post['nm_titulo']) ?></h1>
                <p class="blog-deck"><?= blogEsc($post['ds_resumo']) ?></p>
                <div class="blog-copy"><?= nl2br(blogEsc($post['ds_conteudo'])) ?></div>
            </article>
        <?php elseif ($id): ?>
            <section class="blog-empty">
                <h1>Não encontramos esse artigo.</h1>
                <p>Ele pode ter sido removido ou ainda não foi publicado.</p>
                <a class="blog-button" href="blog.php">Ver publicações</a>
            </section>
        <?php else: ?>
            <header class="blog-hero">
                <div>
                    <h1>Cuidado, companhia<br>e novas histórias.</h1>
                    <p class="blog-intro">Ideias simples para cuidar melhor, receber um novo amigo e fazer a diferença na vida dos pets.</p>
                </div>
                <div class="blog-art" aria-hidden="true">
                    <svg viewBox="0 0 220 190" role="img">
                        <path d="M110 87c-18 0-40 20-40 41 0 16 14 25 40 25s40-9 40-25c0-21-22-41-40-41Z" fill="currentColor"/>
                        <ellipse cx="57" cy="67" rx="16" ry="23" transform="rotate(-28 57 67)" fill="currentColor"/>
                        <ellipse cx="92" cy="38" rx="16" ry="23" transform="rotate(-8 92 38)" fill="currentColor"/>
                        <ellipse cx="128" cy="38" rx="16" ry="23" transform="rotate(8 128 38)" fill="currentColor"/>
                        <ellipse cx="163" cy="67" rx="16" ry="23" transform="rotate(28 163 67)" fill="currentColor"/>
                    </svg>
                    <span>um carinho<br>por vez</span>
                </div>
            </header>

            <section class="blog-posts" aria-labelledby="blog-posts-title">
                <div class="blog-section-heading">
                    <div>
                        <h2 id="blog-posts-title">Últimas publicações</h2>
                    </div>
                    <span><?= count($posts) ?> <?= count($posts) === 1 ? 'artigo' : 'artigos' ?></span>
                </div>

                <?php if (!$posts): ?>
                    <div class="blog-empty">
                        <h3>As primeiras histórias estão a caminho.</h3>
                        <p>Em breve, você encontra por aqui dicas e histórias da comunidade AdotaPet.</p>
                    </div>
                <?php else: ?>
                    <div class="blog-list">
                        <?php foreach ($posts as $item): ?>
                            <article class="blog-item">
                                <?php if (!empty($item['ds_imagem_capa'])): ?>
                                    <img class="blog-cover blog-item-cover" src="../<?= blogEsc($item['ds_imagem_capa']) ?>" alt="">
                                <?php else: ?>
                                <div class="blog-item-mark" aria-hidden="true">
                                    <svg viewBox="0 0 80 70">
                                        <path d="M40 33c-7 0-14 8-14 15 0 6 5 9 14 9s14-3 14-9c0-7-7-15-14-15Z" fill="currentColor"/>
                                        <ellipse cx="17" cy="25" rx="6" ry="9" transform="rotate(-25 17 25)" fill="currentColor"/>
                                        <ellipse cx="32" cy="12" rx="6" ry="9" transform="rotate(-8 32 12)" fill="currentColor"/>
                                        <ellipse cx="48" cy="12" rx="6" ry="9" transform="rotate(8 48 12)" fill="currentColor"/>
                                        <ellipse cx="63" cy="25" rx="6" ry="9" transform="rotate(25 63 25)" fill="currentColor"/>
                                    </svg>
                                </div>
                                <?php endif; ?>
                                <div class="blog-item-copy">
                                    <p class="blog-date"><?= blogData($item['dt_criacao']) ?></p>
                                    <h3><a href="blog.php?id=<?= (int) $item['id_post'] ?>"><?= blogEsc($item['nm_titulo']) ?></a></h3>
                                    <p><?= blogEsc($item['ds_resumo']) ?></p>
                                    <a class="blog-read" href="blog.php?id=<?= (int) $item['id_post'] ?>">Ler artigo <span aria-hidden="true">→</span></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
