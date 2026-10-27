<?php
    require_once("conexao.php");
    session_start();

    function iniciais($nome) {
    $partes = preg_split('/\s+/', trim($nome));
    $primeira = mb_substr($partes[0], 0, 1);
    $ultima = count($partes) > 1 ? mb_substr(end($partes), 0, 1) : '';
    return mb_strtoupper($primeira . $ultima);
    }

    $SQL = "SELECT a.id_avaliacao, a.ds_comentario_avaliacao, u.nm_usuario 
    FROM AVALIACAO AS a 
    JOIN USUARIO u ON a.id_usuario_fk = u.id_usuario
    ORDER BY a.dt_avaliacao DESC
    LIMIT 4";

    $stmt = $conn->prepare($SQL);
    $stmt->execute();
    $avaliacoes = $stmt->get_result();
    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="shortcut icon" href="assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="CSS/index.css">
    <link rel="stylesheet" href="CSS/variaveis.css">
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/hero.css">
    <link rel="stylesheet" href="CSS/footer.css">
    <title>AdotaPet - Welcome</title>
</head>

<body>
    <nav class="nav" id="navBar">
        <div class="logo">
            <a href="#"> 
                <h1><span>Adota</span>Pet</h1>
            </a>
            <button class="menuBtn" id="menuBtn"><i class="bi bi-list"></i></button>
        </div>
        <div class="content-nav">
            <div class="links">
            <ul>
                <li>
                    <a href="/pages/sobre.html">Sobre</a>
                    <ul class="dropdown">
                        <li><a href="PAGINAS/sobre.php">Sobre o Portal</a></li>
                        <li><a href="/pages/blog.html">Blog</a></li>
                        <li><a href="/pages/sobre.html#avaliacoes">Avaliações</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Pet's</a>
                    <ul class="dropdown">
                        <li><a href="/pages/perdidos.html">Pet's Perdidos</a></li>
                        <li><a href="/pages/perdidos.html#pets-achados">Pet's Achados</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#">Adoção</a>
                    <ul class="dropdown">
                        <li><a href="PAGINAS/area_adocao.html">Área de Adoção</a></li>
                        <li><a href="/pages/ongs.html">ONG's</a></li>
                    </ul>
                </li>
                <?php
                    if (isset($_SESSION['tipo_conta'])) {
                        $primeiroNome = htmlspecialchars(strtok($_SESSION['nome_usuario'], " "));

                        if ($_SESSION['tipo_conta'] === 'ong') {
                            echo "<a class='acount' href='PAGINAS/account_ong.php'>Olá, " . $primeiroNome . "</a>";
                        } else {
                            echo "<a class='acount' href='PAGINAS/account.php'>Olá, " . $primeiroNome . "</a>";
                        }
                    } else {
                        echo "<a class='acount-buton-notLogin' href='PAGINAS/login.php'>Entrar</a>";
                    }
                ?>
            </ul>
        </div>
        </div>
    </nav>

    <main>
        <div class="hero">
            <img src="assets/all_images/homePage.png">
            <div class="content-hero">
                <h1><span>Adota</span>Pet</h1>
                <p>Mude a Vida do Seu Novo Melhor Amigo!</p>
            </div>
        </div>

        <div class="avaliations">
            <?php  foreach($avaliacoes as $a): ?>
            <div class="card-avaliation">
                <div class="card-header-avaliation">
                    <div class="card-avatar"><p><?php echo iniciais($a['nm_usuario']) ?></p></div>
                    <P><?php echo $a['nm_usuario'] ?></P>
                </div>
                <div class="content-card-avaliation">
                    <p><?php echo $a['ds_comentario_avaliacao']; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
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
            <li><i class="bi bi-instagram"></i><p>adotapetofc</p></li>
            <li><i class="bi bi-facebook"></i><p>adotapetofc</p></li>
            <li><i class="bi bi-twitter"></i><p>adotapetofc</p></li>
            
        </div>
        <div class="content-footer">
            <h1>Institucional</h1>
            <a href="#" >Termos de Uso</a>
            <a href="#">Documentação</a>
            <a href="#">Conteúdo de Design</a>
        </div>
    </footer>
    <script src="script/headerScript.js"></script>
</body>

</html>