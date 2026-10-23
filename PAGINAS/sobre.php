<?php
    require_once("../conexao.php");
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
        <title> AdotaPet - Área de Adoção </title>
        <link rel="stylesheet" href="../css/variaveis.css">
        <link rel="stylesheet" href="../css/navbar.css">
        <link rel="stylesheet" href="../css/footer.css">
        <link rel="stylesheet" href="../css/sobre.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
        <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
        
    </head>
    <body>
        <nav class="nav" id="navBar">
            <div class="logo">
                <a href="../index.php"> 
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
                        ? "<a class='acount' href='account.php'>Olá, " . strtok($_SESSION['nome_usuario'], " ") . "</a>"
                        : "<a class='acount-buton-notLogin' href='login.php'>Entrar</a>";
                    ?>
                </ul>
            </div>
            </div>
        </nav>

        <main>

    <!-- Hero -->
    <section class="hero" id="sobre-nos">
        <div class="sobre-wrap">
            <div class="hero-texto">
                <h2>Todo pet merece voltar para casa</h2>
                <p>Todos os dias, pets se perdem e famílias ficam sem respostas. Criamos um espaço simples e acessível para mudar essa realidade, conectando quem perdeu, quem encontrou e quem quer ajudar.</p>
            </div>
            <div class="hero-logo">
                <img src="https://imgs.search.brave.com/hNVbpmH1AlZwTvX0sEUbir7hRIYzPxicbKLXYPL2Ipg/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9zdGF0/aWMudmVjdGVlenku/Y29tL3RpL2ZvdG9z/LWdyYXRpcy90Mi8y/NDYzNzUyNS1jYWNo/b3Jyby1kZW50cm8t/cmVhbC12aWRhLWZl/bGl6LW1vbWVudG8t/Y29tLWFuaW1hbC1h/aS1nZW5lcmF0aXZv/LWdyYXRpcy1mb3Rv/LmpwZw" alt="Logo AdotaPet">
            </div>
        </div>
    </section>
    
    <section class="hero" id="sobre-nos">
        <div class="sobre-wrap">
            <div class="hero-texto">
                <h2>Se voce não tem amigos, adote um</h2>
                <p>Muitos animais estão esperando nesse exato momento para serem adotados, sonhando um dia em ter uma casa e receber carinho de seus donos.</p>
            </div>
            <div class="hero-logo">
                <img src="https://imgs.search.brave.com/Ot5x2qsZlQNs8AVg683vrl3VBsSvMWEkn0Q4zre2PJc/rs:fit:860:0:0:0/g:ce/aHR0cHM6Ly9tZWRp/YS5pc3RvY2twaG90/by5jb20vaWQvMTQ1/MTU4NDI4L3B0L2Zv/dG8vYyVDMyVBM28t/ZmVsaXotYS1kYXIt/bWFpcy1jaW5jby5q/cGc_cz02MTJ4NjEy/Jnc9MCZrPTIwJmM9/N0hrdHNOVHdEeGM1/MHp1NW5DdVVvSGNO/UlBDV2t3bFhGOE9I/cThoU3U1dz0" alt="Logo AdotaPet">
            </div>
        </div>
    </section>

    <!-- Como funciona -->
    <section class="passos">
        <div class="sobre-wrap">
            <div class="passos-titulo">
                <h2>Como funciona</h2>
                <p>Divulgue seu animalzinho perdido ou adote um com ONG's autorizadas.</p>
            </div>
            <ol class="passos-lista">
                <li>
                    <h3>Crie sua conta</h3>
                    <p>Crie sua conta gratuitamente para navegar com liberdade.</p>
                </li>
                <li>
                    <h3>Compatilhe</h3>
                    <p>Em caso de pet perdido, crie um anuncio com informações do pet e do sumiço</p>
                </li>
                <li>
                    <h3>Adote</h3>
                    <p>Muitos animais precisam de um lar, faça sua parte e adote um animalzinho!</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Propósito -->
    <section class="proposito">
        <div class="sobre-wrap">
            <div class="proposito-box">
                <div>
                    <h3>Nosso propósito</h3>
                    <p>Às vezes, tudo o que um você precisa é de um animalzinho. Nosso objetivo é ser o ponto de encontro entre pessoas e pets.</p>
                </div>
                <div>
                    <h3>Faça a diferença</h3>
                    <p>Se você encontrou um animal, não ignore. Se perdeu, não desista. Cada compartilhamento pode mudar a vida de um pet e de uma família.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Avaliações -->
    <section class="reviews-container" id="avaliacoes">
        <div class="sobre-wrap">

            <div class="review-title">
                <div class="review-img">
                    <img src="../assets/all_images/reviews.svg" alt="Personagem avaliando o portal">
                </div>
                <h2>Avaliações</h2>
                <p>Conte como foi sua experiência com o portal.</p>
                <button class="btn-avaliar">Criar avaliação <i class="bi bi-plus"></i></button>
            </div>

            <div class="reviews-cards-container">
                <?php foreach($avaliacoes as $a): ?>
                <article class="review-card">
                    <div class="review-card-header">
                        <div class="avatar" aria-hidden="true"><?php echo iniciais($a['nm_usuario']) ?></div>
                        <div class="card-header-text">
                            <p><?php echo $a['nm_usuario'] ?></p>
                        </div>
                    </div>
                    <div class="review-content-card">
                        <p><?php echo $a['ds_comentario_avaliacao'] ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>

        </div>
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
                <li><i class="bi bi-instagram"></i><p>adotapetofc</p></li>
                <li><i class="bi bi-facebook"></i><p>adotapetofc</p></li>
                <li><i class="bi bi-twitter"></i><p>adotapetofc</p></li>
                
            </div>
            <div class="content-footer">
                <h1>Institucional</h1>
                <a href="#" >Termos de Uso</a>
                <a href="/Documentacao.pdf" download="Documentacao.pdf">Documentação</a>
                <a href="#">Conteúdo de Design</a>
            </div>
        </footer>
        <script src="../script/headerScript.js"></script>
    </body>
</html>