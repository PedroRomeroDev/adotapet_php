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
    <link rel="stylesheet" href="../CSS/login.css">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="shortcut icon" href="../assets/all_images/vazado.png">
</head>
<body>
    <div class="container-form">
        <div class="login" id="login">
            <div class="first-column">
                <h2>Olá</h2>
                <p>Entre na sua conta</p>
                <p>Participe da comunidade AdotaPet</p>
            </div>
            <div class="second-column">
                <form id="form-login" action="#" method="POST">
                    <h2>Login</h2>
                    <div class="input-container"><input type="email" placeholder="Email" id="email-login" name="email_usuario" required></div>
                    <div class="input-container"><input type="password" placeholder="Senha" id="senha-login" name="senha-usuario" required></div>
                    <a href="#">Esqueci Minha Senha</a>
                    <button type="submit" >Entrar</button>
                    <div class="links">
                        <p class="link-cadastro">Não tem uma conta? <a href="cadastro.php">Cadastre-se</a></p>
                        <p class="link-cadastro">É uma ONG? <a href="cadastro_ong.php">Cadastre Sua Organização</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="/script/loginScript.js"></script>
</body>
</html>