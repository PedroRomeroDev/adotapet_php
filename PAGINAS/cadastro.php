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
    <link rel="stylesheet" href="../CSS/cadastro.css">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="shortcut icon" href="../assets/all_images/vazado.png">
</head>
<body>
    <div class="container-form">
        <div class="cadastro" id="cadastro">
            <div class="first-column">
                <h2>Bem Vindo</h2>
                <p>Crie sua conta gratuitamente</p>
                <p>Participe da comunidade AdotaPet</p>
            </div>
            <div class="second-column">
                <form id="form-cadastro" method="POST" action="#">
                    <h2>Cadastro</h2>
                    <div class="input-container"><input type="text" placeholder="Nome" id="nome-cadastro" name="nome-cadastro" required></div>
                    <div class="input-container"><input type="tel" placeholder="Telefone" id="telefone-cadastro" name="telefone-cadastro" required></div>
                    <div class="input-container"><input type="email" placeholder="Email" id="email-cadastro" name="email-cadastro" required></div>
                    <div class="input-container"><input type="password" placeholder="Senha" id="senha-cadastro" name="senha-cadastro" required></div>
                    <button type="submit">Cadastrar</button>
                    <p class="link-login">Já tem uma conta? <a href="login.php">Entrar</a></p>
                </form>
            </div>
        </div>
    </div>
    <script src="/script/cadastroScript.js"></script>
</body>
</html>