<?php
    session_start();
    require_once("../conexao.php");

    // Escapa texto para HTML
    function h($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    // 14 -> "1 ano e 2 meses"
    function formatarIdade(int $m): string {
        if ($m < 12) return $m === 1 ? '1 mês' : "$m meses";
        $a = intdiv($m, 12);
        $r = $m % 12;
        $txt = $a === 1 ? '1 ano' : "$a anos";
        if ($r === 0) return $txt;
        return $txt . ' e ' . ($r === 1 ? '1 mês' : "$r meses");
    }

    $SQL = "SELECT 
    p.cd_pet,
    p.dt_cadastro_pet, 
    p.nm_pet, 
    p.ds_idade_meses_pet, 
    p.ds_sexo_pet, 
    p.cd_especie_pet, 
    p.cd_raca_pet, 
    p.cd_ong, 
    p.ds_imagem_pet, 
    o.nm_ong, 
    o.ds_imagem_perfil_ong 
    FROM PET p
    JOIN ONG o on p.cd_ong = o.cd_ong";

    $response = $conn->query($SQL);

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> AdotaPet - Área de Adoção </title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="../CSS/footer.css">
    <link rel="stylesheet" href="../CSS/area_adocao.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">

</head>

<body>
    <?php require_once __DIR__ . '/../navbar.php'; ?>

    <main>
        <section class="first-container">
            <div class="adopteds-container">
                <h2> Adotados </h2>
                <div class="row-div">
                    <img src="../assets/all_images/dog_adoptions.svg" alt="Ilustração Cachorro">
                    <h3> <span id="today-counter">4</span> hoje</h3>
                </div>
                <div class="balloon-adopteds">
                    <p > <span id="adoptions-counter">1.654</span> adoções desde o início </p>
                </div>
            </div>

            <div class="how-works-container">
                <h2> Como funciona? </h2>
                <p> Confira como funciona o processo de adoção em nossos artigos e entenda cada etapa dessa jornada
                    especial. Adotar é oferecer amor, cuidado e uma nova chance para um animal que precisa de um lar. Em
                    troca, você ganha companhia, carinho e momentos inesquecíveis ao lado de um amigo fiel. Compartilhe
                    sua vida com um melhor amigo e transforme duas vidas ao mesmo tempo. </p>
                <button onclick="baixarArquivo()" class="btn-conferir"> Conferir </button>
            </div>
        </section>

        <section class="pets-container" id="para-adocao">
            <div class="pets-header">
                <h2>Para adoção <i class="fa-solid fa-paw"></i></h2>

                <form class="search-box" role="search" onsubmit="return false">
                    <input type="search" id="busca-animal" placeholder="Pesquisar..." aria-label="Pesquisar animais">
                    <button type="submit" aria-label="Pesquisar"><i class="fa-solid fa-magnifying-glass"></i></button>
                </form>
            </div>

            <div class="pets-grid" id="pets-grid">
                <?php if ($response && $response->num_rows > 0): ?>
                    <?php foreach ($response as $resp):
                        $id       = (int) $resp['cd_pet'];
                        $nome     = h($resp['nm_pet']);
                        $img      = '../' . h($resp['ds_imagem_pet']);
                        $ong      = h($resp['nm_ong']);
                        $imgOng   = '../' . h($resp['ds_imagem_perfil_ong']);
                        $iniciais = h(mb_strtoupper(mb_substr($resp['nm_ong'], 0, 2)));
                        $desde    = date('d/m/Y', strtotime($resp['dt_cadastro_pet']));
                        $idade    = formatarIdade((int) $resp['ds_idade_meses_pet']);
                        $sexo     = strtoupper(substr((string) $resp['ds_sexo_pet'], 0, 1));
                    ?>
                    <article class="pet-card">
                        <div class="pet-top">
                            <img class="pet-photo" src="<?= $img ?>" alt="Foto do <?= $nome ?>">
                            <h3 class="pet-name"><?= $nome ?></h3>
                        </div>

                        <p class="pet-since">Animal disponível desde <?= $desde ?></p>

                        <div class="pet-ong">
                            <span class="ong-avatar" data-ini="<?= $iniciais ?>">
                                <img src="<?= $imgOng ?>" alt="" onerror="this.remove()">
                            </span>
                            <span><?= $ong ?></span>
                        </div>

                        <button type="button" class="btn-ver-mais">Ver mais</button>

                        <!-- Conteúdo do modal deste pet (o JS copia para o modal ao clicar em "Ver mais") -->
                        <template class="pet-detalhes">
                            <div class="animal-img-container">
                                <img src="<?= $img ?>" alt="Foto do <?= $nome ?>">
                            </div>

                            <div class="about-animal">
                                <div class="modal-ong">
                                    <span class="ong-avatar" data-ini="<?= $iniciais ?>">
                                        <img src="<?= $imgOng ?>" alt="" onerror="this.remove()">
                                    </span>
                                    <div>
                                        <small>Cuidado por</small>
                                        <strong><?= $ong ?></strong>
                                    </div>
                                </div>

                                <h1 id="modal-titulo"><?= $nome ?></h1>

                                <div class="container-balloons">
                                    <?php if ($sexo === 'F'): ?>
                                        <span class="balao"><i class="fa-solid fa-venus"></i> Fêmea</span>
                                    <?php elseif ($sexo === 'M'): ?>
                                        <span class="balao"><i class="fa-solid fa-mars"></i> Macho</span>
                                    <?php endif; ?>
                                    <span class="balao"><i class="fa-solid fa-hourglass-half"></i> <?= $idade ?></span>
                                </div>

                                <p class="modal-desde">
                                    <i class="fa-regular fa-calendar"></i> Disponível desde <?= $desde ?>
                                </p>

                                <a class="btn-adotar" href="formulario_adocao.php?cd_pet=<?= $id ?>">
                                    <i class="fa-solid fa-heart"></i> Quero adotar!
                                </a>
                            </div>
                        </template>
                    </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <p class="pets-vazio" id="pets-vazio" hidden>Nenhum animal encontrado para essa busca.</p>
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

    <script src="../SCRIPT/headerScript.js"></script>
    <script src="../SCRIPT/baixarArquivo.js"></script>
    <script src="../SCRIPT/areaAdocao.js"></script>
</body>
</html>