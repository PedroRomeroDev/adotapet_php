<?php

    session_start();

    // Importando Conexao PHP
    require_once("../conexao.php");

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $email_usuario    = $_POST["email_usuario"];
        $senha_usuario    = $_POST["senha-usuario"];

        if(empty($email_usuario) || empty($senha_usuario)){
            die("Dados Invalidos");
        }

        if(!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)){
            die("Email Invalido");
        }

        // Select no Banco de Dados
        $SQL = "SELECT * FROM USUARIO WHERE cd_email_usuario = ?";

        // Preparando a Query
        $stmt = $conn->prepare($SQL);

        $stmt->bind_param("s", $email_usuario);

        // Executando Consulta
        $stmt->execute();

        $resposta = $stmt->get_result();

        if($resposta->num_rows > 0){
            $resposta = $resposta->fetch_assoc();
           
            if($resposta['cd_senha_usuario'] == $senha_usuario) {
                 $_SESSION['email_usuario'] = $resposta['cd_email_usuario'];
                 $_SESSION['nome_usuario'] = $resposta['nm_usuario'];
                header('Location: ../index.php');
            } else {
                echo "Senha Incorreta";
            }
        } else {
            echo "Email Nao Cadastrado!";
        }
    }
?>


<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdotaPet - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/login.css?v=<?php echo filemtime(__DIR__ . '/../CSS/login.css'); ?>">
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
</head>

<body>
    <main class="container-form">
        <div class="login" id="login">

            <aside class="first-column">
                <div class="first-column-content">
                    <h2>Olá</h2>
                    <p>Entre na sua conta.</p>
                    <p>Participe da comunidade AdotaPet.</p>
                </div>
            </aside>

            <section class="second-column">
                <form id="form-login" action="#" method="POST">
                    <header class="form-header">
                        <h1>Login</h1>
                        <p>Entre para continuar na sua conta.</p>
                    </header>

                    <div class="campos">
                        <div class="input-container">
                            <label for="email-login">E-mail</label>
                            <input type="email" id="email-login" name="email_usuario" placeholder="seu@email.com"
                                autocomplete="email" required>
                        </div>

                        <div class="input-container">
                            <label for="senha-login">Senha</label>
                            <input type="password" id="senha-login" name="senha-usuario" placeholder="Sua senha"
                                autocomplete="current-password" required>
                            <a class="link-esqueci" href="#">Esqueci minha senha</a>
                        </div>
                    </div>

                    <button type="submit">Entrar</button>

                    <div class="links">
                        <p class="link-cadastro">
                            Não tem uma conta? <a href="cadastro.php">Cadastre-se</a>
                        </p>
                        <p class="link-cadastro">
                            É uma ONG? <a href="cadastro_ong.php">Cadastre sua organização</a>
                        </p>
                    </div>
                </form>
            </section>

        </div>
    </main>
    <script src="/script/loginScript.js"></script>
</body>

</html>