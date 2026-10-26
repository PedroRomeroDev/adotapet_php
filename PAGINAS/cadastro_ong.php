<?php

    // Importando Conexao PHP
    require_once("../conexao.php");

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $cnpj                          = $_POST["cnpj-ong"];
        $nome_cadastro                 = $_POST["nome-cadastro"];
        $nome_responsavel_cadastro     = $_POST["nome-responsavel-cadastro"];
        $telefone_cadastro             = $_POST["telefone-cadastro"];
        $endereco_ong                  = $_POST["endereco-ong"];
        $cidade_ong                    = $_POST["cidade-ong"];
        $estado_ong                    = $_POST["estado-ong"];
        $email_cadastro                = $_POST["email-cadastro"];
        $senha_cadastro                = $_POST["senha-cadastro"];

        // Verifica se Campos Existem
        if( empty($cnpj)                      ||
            empty($nome_cadastro)             ||
            empty($nome_responsavel_cadastro) || 
            empty($telefone_cadastro)         || 
            empty($endereco_ong)              || 
            empty($cidade_ong)                || 
            empty($estado_ong)                || 
            empty($email_cadastro)            || 
            empty($senha_cadastro)){
            die("Preencha todos os campos.");
        }

        // Filtrando se Email é Valido
        if(!filter_var($email_cadastro, FILTER_VALIDATE_EMAIL)){
            die("Email Invalido");
        }

        // Insert no Banco de Dados
        $SQL  = "INSERT INTO ONG ( 
        cd_cnpj_ong, 
        nm_ong, 
        nm_responsavel_ong, 
        cd_telefone_ong, 
        ds_endereco_ong, 
        nm_cidade_ong, 
        sg_estado_ong,
        cd_email_ong, 
        cd_senha_ong
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        // Preparando a Query
        $stmt = $conn->prepare($SQL);

        $stmt->bind_param("sssssssss", 
        $cnpj, 
        $nome_cadastro, 
        $nome_responsavel_cadastro, 
        $telefone_cadastro,
        $endereco_ong,
        $cidade_ong,
        $estado_ong,
        $email_cadastro,
        $senha_cadastro
        );

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
    <title>AdotaPet - Cadastro ONG</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/cadastro_ong.css">
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
                <form id="form-cadastro" method="POST" action="">
                    <header class="form-header">
                        <h1>Cadastro ONG</h1>
                        <p>Preencha os dados da sua instituição para começar.</p>
                    </header>

                    <div class="form-section">
                        <h3>Dados da instituição</h3>

                        <div class="input-container">
                            <label for="cnpj-ong">CNPJ</label>
                            <input type="text" id="cnpj-ong" name="cnpj-ong" placeholder="Somente números"
                                maxlength="14" inputmode="numeric" autocomplete="off" required>
                        </div>

                        <div class="input-container">
                            <label for="nome-cadastro">Nome da instituição</label>
                            <input type="text" id="nome-cadastro" name="nome-cadastro" placeholder="Nome da ONG"
                                maxlength="200" autocomplete="organization" required>
                        </div>

                        <div class="input-container full">
                            <label for="nome-responsavel">Nome do responsável</label>
                            <input type="text" id="nome-responsavel" name="nome-responsavel-cadastro"
                                placeholder="Nome completo" maxlength="200" autocomplete="name" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Contato e acesso</h3>

                        <div class="input-container">
                            <label for="telefone-cadastro">Telefone</label>
                            <input type="tel" id="telefone-cadastro" name="telefone-cadastro"
                                placeholder="(00) 00000-0000" maxlength="20" autocomplete="tel" required>
                        </div>

                        <div class="input-container">
                            <label for="email-cadastro">E-mail da ONG</label>
                            <input type="email" id="email-cadastro" name="email-cadastro"
                                placeholder="contato@ong.org" maxlength="200" autocomplete="email" required>
                        </div>

                        <div class="input-container full">
                            <label for="senha-cadastro">Senha</label>
                            <input type="password" id="senha-cadastro" name="senha-cadastro"
                                placeholder="Crie uma senha" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="form-section localizacao">
                        <h3>Localização</h3>

                        <div class="input-container full">
                            <label for="endereco-ong">Endereço completo</label>
                            <input type="text" id="endereco-ong" name="endereco-ong"
                                placeholder="Rua, número e bairro" autocomplete="street-address" required>
                        </div>

                        <div class="input-container cidade">
                            <label for="cidade-ong">Cidade</label>
                            <input type="text" id="cidade-ong" name="cidade-ong" placeholder="Cidade"
                                maxlength="100" autocomplete="address-level2" required>
                        </div>

                        <div class="input-container uf">
                            <label for="estado-ong">UF</label>
                            <input type="text" id="estado-ong" name="estado-ong" placeholder="SP" minlength="2"
                                maxlength="2" pattern="[A-Za-z]{2}" title="Informe a sigla do estado, por exemplo, SP."
                                autocomplete="address-level1" required>
                        </div>
                    </div>

                    <button type="submit">Cadastrar ONG</button>

                    <p class="link-login">
                        Já tem uma conta? <a href="login.php">Entrar</a>
                    </p>
                </form>
            </section>

        </div>
    </main>
</body>

</html>