<?php
    session_start();
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
                echo isset($_SESSION['email_usuario'])
                    ? "<a class='acount' href='PAGINAS/account.php'>Olá, " . strtok($_SESSION['nome_usuario'], " ") . "</a>"
                    : "<a class='acount-buton-notLogin' href='PAGINAS/login.php'>Entrar</a>";
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
            <div class="card-avaliation">
                <div class="card-header-avaliation">
                    <img src="https://preview.redd.it/need-someone-to-help-me-with-freaky-vini-jr-photo-i-will-v0-zar03vm82wkd1.png?width=484&format=png&auto=webp&s=eee4664f17822596b7d1e419e3d01d63926dee6a">
                    <P>Zé Felipe</P>
                </div>
                <div class="content-card-avaliation">
                    <p>Achei Meu Gato Através Deste Portal! Obrigado! 😺</p>
                </div>
            </div>

            <div class="card-avaliation">
                <div class="card-header-avaliation">
                    <img src="https://s2.glbimg.com/c1UZ516mc6YGgcsWOWZGdrKvAqI=/e.glbimg.com/og/ed/f/original/2018/10/22/mulher-confronta-negro.jpg">
                    <P>Guilherme Campos</P>
                </div>
                <div class="content-card-avaliation">
                    <p>Achei Meu Salsicha, Ele está a salvo! Obrigadoooo! 🌭</p>
                </div>
            </div>

            <div class="card-avaliation">
                <div class="card-header-avaliation">
                    <img src="https://www.famousbirthdays.com/faces/douglassola-image.jpg">
                    <P>Luan Henrique</P>
                </div>
                <div class="content-card-avaliation">
                    <p>Achei Meu Frango Dinei Através Deste Portal! Vivaa 🐔</p>
                </div>
            </div>

            <div class="card-avaliation">
                <div class="card-header-avaliation">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQh088n5LmT_RsqEyD52tpdrYC_yb7kbNSujA&s">
                    <P>Victor Luiz</P>
                </div>
                <div class="content-card-avaliation">
                    <p>Meu Cachorro Voltou, Obrigado! 🐶</p>
                </div>
            </div>
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