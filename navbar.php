<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$baseUrl = '/adotapet_php';
?>
<nav class="nav" id="navBar">
    <div class="logo">
        <a href="<?= $baseUrl ?>/index.php">
            <h1><span>Adota</span>Pet</h1>
        </a>
        <button class="menuBtn" id="menuBtn" type="button" aria-label="Abrir menu">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="content-nav">
        <div class="links">
            <ul>
                <li>
                    <a href="#">Sobre</a>
                    <ul class="dropdown">
                        <li><a href="<?= $baseUrl ?>/PAGINAS/sobre.php">Sobre o Portal</a></li>
                        <li><a href="<?= $baseUrl ?>/PAGINAS/blog.php">Blog</a></li>
                        <li><a href="<?= $baseUrl ?>/PAGINAS/sobre.php#avaliacoes">Avaliações</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Pet's</a>
                    <ul class="dropdown">
                        <li><a href="<?= $baseUrl ?>/PAGINAS/perdidos.php">Pet's Perdidos</a></li>
                        <li><a href="<?= $baseUrl ?>/PAGINAS/perdidos.php#pets-achados">Pet's Achados</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Adoção</a>
                    <ul class="dropdown">
                        <li><a href="<?= $baseUrl ?>/PAGINAS/area_adocao.php">Área de Adoção</a></li>
                        <li><a href="<?= $baseUrl ?>/PAGINAS/ongs.php">ONG's</a></li>
                    </ul>
                </li>
                <li>
                    <?php if (isset($_SESSION['tipo_conta'])): ?>
                        <?php $paginaConta = $_SESSION['tipo_conta'] === 'ong' ? 'account_ong.php' : 'account.php'; ?>
                        <a class="acount" href="<?= $baseUrl ?>/PAGINAS/<?= $paginaConta ?>">
                            <i class="bi bi-person-circle"></i> Conta
                        </a>
                    <?php else: ?>
                        <a class="acount-buton-notLogin" href="<?= $baseUrl ?>/PAGINAS/login.php">Entrar</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>
