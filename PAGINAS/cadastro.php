<?php

    // Importando Conexao PHP
    require_once("../conexao.php");

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $nome_cadastro     = $_POST["nome-cadastro"];
        $telefone_cadastro = $_POST["telefone-cadastro"];
        $email_cadastro    = $_POST["email-cadastro"];
        $senha_cadastro    = $_POST["senha-cadastro"];

        // Verifica se Campos Existem
        if(empty($nome_cadastro) || empty($telefone_cadastro) || empty($email_cadastro) || empty($senha_cadastro)){
            die("Preencha todos os campos.");
        }

        // Filtrando se Email é Valido
        if(!filter_var($email_cadastro, FILTER_VALIDATE_EMAIL)){
            die("Email Invalido");
        }

        // Insert no Banco de Dados
        $SQL  = "INSERT INTO USUARIO (nm_usuario, cd_email_usuario, cd_telefone_usuario, cd_senha_usuario) VALUES (?, ?, ?, ?)";

        // Preparando a Query
        $stmt = $conn->prepare($SQL);

        $stmt->bind_param("ssss", $nome_cadastro, $email_cadastro, $telefone_cadastro, $senha_cadastro);

        // Executando a Query
        if($stmt->execute()){
            header('Location: login.php');
        } else {
            echo $stmt->error;
        }

    }

?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdotaPet - Cadastro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/cadastro.css?v=<?php echo filemtime(__DIR__ . '/../CSS/cadastro.css'); ?>">
    <link rel="shortcut icon" href="../assets/all_images/vazado.png">
</head>

<body>
    <main class="container-form">
        <div class="cadastro" id="cadastro">

            <aside class="first-column">
                <div class="first-column-content">
                    <h2>Bem-vindo</h2>
                    <p>Crie sua conta gratuitamente.</p>
                    <p>Participe da comunidade AdotaPet.</p>
                </div>
            </aside>

            <section class="second-column">
                <form id="form-cadastro" method="POST" action="#">
                    <header class="form-header">
                        <h1>Cadastro</h1>
                        <p>Crie sua conta para adotar e acompanhar seus pets.</p>
                    </header>

                    <div class="form-section">
                        <h3>Seus dados</h3>

                        <div class="input-container full">
                            <label for="nome-cadastro">Nome</label>
                            <input type="text" id="nome-cadastro" name="nome-cadastro" placeholder="Nome completo"
                                maxlength="200" autocomplete="name" required>
                        </div>

                        <div class="input-container">
                            <label for="telefone-cadastro">Telefone</label>
                            <input type="tel" id="telefone-cadastro" name="telefone-cadastro"
                                placeholder="(00) 00000-0000" maxlength="20" autocomplete="tel" required>
                        </div>

                        <div class="input-container">
                            <label for="email-cadastro">E-mail</label>
                            <input type="email" id="email-cadastro" name="email-cadastro"
                                placeholder="seu@email.com" maxlength="200" autocomplete="email" required>
                        </div>

                        <div class="input-container full">
                            <label for="senha-cadastro">Senha</label>
                            <input type="password" id="senha-cadastro" name="senha-cadastro"
                                placeholder="Crie uma senha" autocomplete="new-password" required>
                        </div>
                    </div>

                    <button type="submit">Cadastrar</button>

                    <div class="links">
                        <p class="link-login">
                            Já tem uma conta? <a href="login.php">Entrar</a>
                        </p>
                        <p class="link-login">
                            É uma ONG? <a href="cadastro_ong.php">Cadastre sua organização</a>
                        </p>
                    </div>
                </form>
            </section>

        </div>
    </main>
</body>

</html>