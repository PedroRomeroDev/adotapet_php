<?php

    session_start();

    require_once("../conexao.php");

    if(!$_SESSION['email_usuario']){
        Header("Location: login.php");
    }

    if(filter_var($_SESSION['email_usuario'],FILTER_VALIDATE_EMAIL)){
        $email_usuario = $_SESSION['email_usuario'];

        // Select no Banco de Dados
        $SQL = "SELECT * FROM USUARIO WHERE cd_email_usuario = ?";

        // Preparando a Query
        $stmt = $conn->prepare($SQL);

        $stmt->bind_param("s", $email_usuario);

        // Executando Consulta
        $stmt->execute();

        $resposta = $stmt->get_result();

        if($resposta->num_rows > 0) {
            $resposta = $resposta->fetch_assoc();
        }
    }

    if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['acao'] == "salvar_informacoes"){
        $nome = $_POST['nome_usuario'];
        $telefone = $_POST['telefone_usuario'];
        $email = $_POST['email_usuario'];
        $senha = $_POST['senha_usuario'];

        if ($resposta['nm_usuario'] == $nome && $resposta['cd_email_usuario'] == $email && $resposta['cd_telefone_usuario'] == $telefone && $resposta['cd_senha_usuario'] == $senha){
            echo "Voce Nao Alterou Nenhuma Informação";
        } else {
            $SQL = "UPDATE USUARIO SET nm_usuario = ?, cd_email_usuario = ?,
            cd_telefone_usuario = ?,
            cd_senha_usuario = ?
            WHERE id_usuario = ?";

            // Preparando a Query
            $stmt = $conn->prepare($SQL);

            $stmt->bind_param("ssssi", $nome, $email, $telefone, $senha, $resposta['id_usuario']);

            try {

                // Executando Consulta
                $stmt->execute();

                $_SESSION['email_usuario'] = $email;
                $_SESSION['nome_usuario'] = $nome;

                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            } catch (mysqli_sql_exception $e) {
                echo "Erro ao Salvar";
            }
        }
    }
    
    $SQL = "SELECT ds_comentario_avaliacao FROM AVALIACAO WHERE id_usuario_fk = ?";

    $stmt = $conn->prepare($SQL);
    $stmt->bind_param("i", $resposta['id_usuario']);
    $stmt->execute();

    $avaliacao = $stmt->get_result();
    if($avaliacao->num_rows > 0){
        $avaliacao = $avaliacao->fetch_assoc();
        $avaliacao = $avaliacao['ds_comentario_avaliacao'];
    } else {
        $avaliacao = "";
    }

    if($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['acao'] == "enviar_avaliacao"){
        $avaliacao = $_POST['avaliacao'];
        
        $SQL = "INSERT INTO AVALIACAO (ds_comentario_avaliacao, id_usuario_fk) VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE
        ds_comentario_avaliacao = VALUES(ds_comentario_avaliacao),
        dt_avaliacao = CURRENT_TIMESTAMP";

        $stmt = $conn->prepare($SQL);

        $stmt->bind_param("si", $avaliacao, $resposta['id_usuario']);

        $stmt->execute();
    }

    if ($_SERVER['REQUEST_METHOD'] == "POST" && $_POST['acao'] == "excluir_avaliacao"){
        $SQL = "DELETE FROM AVALIACAO WHERE id_usuario_fk =" . $resposta['id_usuario'];
        $stmt = $conn->prepare($SQL);
        $stmt->execute();
        header("Location: " . $_SERVER['PHP_SELF']);
    }
    
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olá <?php echo $_SESSION['nome_usuario']; ?></title>
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/account.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>
    <main class="configurations">
        <div class="title"><a href="../index.php"><i class="bi bi-caret-left-fill"></i></a><h1>Configurações</h1></div>
        <div class="content">
        <section class="account-informations">
        <div class="header-account-informations">
            <h2>Olá, <?php echo strtok($_SESSION['nome_usuario'], " ");?></h2>
            <p>Informações Pessoais</p>
        </div>
            <form action="#" method="POST">
            <ul class="data-account">
                <li><p>Nome Completo</p><input type="text" value='<?php echo $resposta['nm_usuario'];?>' name="nome_usuario"></li>
                <li><p>Telefone</p><input type="text" value='<?php echo $resposta['cd_telefone_usuario'];?>' name="telefone_usuario"></li>
                <li><p>Email</p><input type="email" value='<?php echo $resposta['cd_email_usuario'];?>' name="email_usuario"></li>
                <li><p>Senha</p><input type="password" value='<?php echo $resposta['cd_senha_usuario'];?>' name="senha_usuario"></li>
            </ul>
            <button type="submit" name="acao" value="salvar_informacoes">Salvar</button>
            </form>
        </section>
        <div>
            <section class="extra-section">
            <div class="header-extra-section">
                <h2>Preferências</h2>
                <p>Preferências e Acessibilidade do Usuário</p>
            </div>

            <ul class="data-preferences">
                <li>
                    <label class="switch">
                        <input type="checkbox" id="toggle-modoEscuro">
                        <span class="slider"></span>
                    </label>
                    <p>Modo Escuro</p>
                </li>
                <li>
                    <label class="switch">
                        <input type="checkbox" id="toggle-altoContraste">
                        <span class="slider"></span>
                    </label>
                    <p>Alto Contraste</p>
                </li>
            </ul>
        </section>
        <section class="extra-section">
            <div class="header-extra-section">
                <h2>Minha Avaliação</h2>
                <p>Escreva seu feedback sobre o portal</p>
            </div>
            <form action="#" class="avaliation-form" method="POST"> 
                <textarea rows="2" cols="80" placeholder="Voce não realizou nenhuma avaliação ainda"  maxlength="100" name="avaliacao" id="inputAvaliation"><?php echo $avaliacao; ?></textarea>
                <div class="buttons-avaliation">
                    <button type="submit" name="acao" value="excluir_avaliacao">x</button>
                    <button type="submit" name="acao" value="enviar_avaliacao">Enviar</button>
                </div>
            </form>
        </section>
        <section class="extra-section">
            <div class="header-extra-section">
                <h2>Minha Conta</h2>
                <p>Ações de Conta</p>
            </div>
            <ul class="data-preferences" style="flex-direction: row;">
                <li>
                    <a href="logout.php"><button class="exit-account">Sair da Conta</button></a>
                </li>
                <li>
                    <button class="delete-account" id="delete-account">Excluir Conta</button>
                </li>
            </ul>
        </section>
        </div>
        </div>
    </main>
    <script src="../SCRIPT/account.js"></script>
    <script src="../SCRIPT/modoEscuro.js"></script>
</body>
</html>