<?php
    session_start();
    require_once("../conexao.php");
    require_once("funcoes.php");

    $erro = '';
    $cnpj = $nome_cadastro = $nome_responsavel_cadastro = $telefone_cadastro = '';
    $endereco_ong = $cidade_ong = $estado_ong = $email_cadastro = '';

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $cnpj                          = trim($_POST["cnpj-ong"] ?? '');
        $nome_cadastro                 = trim($_POST["nome-cadastro"] ?? '');
        $nome_responsavel_cadastro     = trim($_POST["nome-responsavel-cadastro"] ?? '');
        $telefone_cadastro             = trim($_POST["telefone-cadastro"] ?? '');
        $endereco_ong                  = trim($_POST["endereco-ong"] ?? '');
        $cidade_ong                    = trim($_POST["cidade-ong"] ?? '');
        $estado_ong                    = trim($_POST["estado-ong"] ?? '');
        $email_cadastro                = trim($_POST["email-cadastro"] ?? '');
        $senha_cadastro                = $_POST["senha-cadastro"] ?? '';

        // "(11) 91234-5678" vira "11912345678"
        $telefone_numeros = preg_replace('/\D/', '', $telefone_cadastro);

        if( empty($cnpj)                      ||
            empty($nome_cadastro)             ||
            empty($nome_responsavel_cadastro) ||
            empty($telefone_cadastro)         ||
            empty($endereco_ong)              ||
            empty($cidade_ong)                ||
            empty($estado_ong)                ||
            empty($email_cadastro)            ||
            empty($senha_cadastro)){
            $erro = 'Preencha todos os campos.';
        } elseif(!filter_var($email_cadastro, FILTER_VALIDATE_EMAIL)){
            $erro = 'Digite um e-mail válido.';
        } elseif(strlen($telefone_numeros) < 10 || strlen($telefone_numeros) > 11){
            $erro = 'Digite um telefone válido com DDD.';
        }elseif(email_em_uso($conn, $email_cadastro)){
            $erro = 'Este e-mail já está cadastrado.';
        } elseif(telefone_em_uso($conn, $telefone_numeros)){
            $erro = 'Este telefone já está cadastrado.';
        } else {
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

            try {
                $stmt = $conn->prepare($SQL);
                $stmt->bind_param("sssssssss",
                    $cnpj,
                    $nome_cadastro,
                    $nome_responsavel_cadastro,
                    $telefone_numeros,
                    $endereco_ong,
                    $cidade_ong,
                    $estado_ong,
                    $email_cadastro,
                    $senha_cadastro
                );
                if ($stmt->execute()) {
                    header('Location: login.php');
                    exit;
                }
                $erro = 'Não foi possível cadastrar a ONG. Confira os dados e tente novamente.';
            } catch (mysqli_sql_exception $e) {
                if ($e->getCode() === 1062) {
                    $msg = $e->getMessage();
                    if (strpos($msg, 'uq_telefone_ong') !== false) {
                        $erro = 'Este telefone já está cadastrado.';
                    } elseif (strpos($msg, 'cd_email_ong') !== false) {
                        $erro = 'Este e-mail já está cadastrado.';
                    } elseif(telefone_em_uso($conn, $telefone_numeros)){
                        $erro = 'Este telefone já está cadastrado.';
                    } else {
                        $erro = 'Este CNPJ já está cadastrado.';
                    }
                } else {
                    $erro = 'Não foi possível cadastrar a ONG. Confira os dados e tente novamente.';
                }
            }
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
    <link rel="stylesheet" href="../CSS/form-feedback.css">
    <link rel="shortcut icon" href="../assets/all_images/vazado.png">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css"></head>

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

                    <?php if ($erro !== ''): ?>
                        <p class="form-feedback" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <div class="form-section">
                        <h3>Dados da instituição</h3>

                        <div class="input-container">
                            <label for="cnpj-ong">CNPJ</label>
                            <input type="text" id="cnpj-ong" name="cnpj-ong" placeholder="Somente números"
                                value="<?= htmlspecialchars($cnpj, ENT_QUOTES, 'UTF-8') ?>"
                                maxlength="14" inputmode="numeric" autocomplete="off" required>
                        </div>

                        <div class="input-container">
                            <label for="nome-cadastro">Nome da instituição</label>
                            <input type="text" id="nome-cadastro" name="nome-cadastro" placeholder="Nome da ONG"
                                value="<?= htmlspecialchars($nome_cadastro, ENT_QUOTES, 'UTF-8') ?>"
                                maxlength="200" autocomplete="organization" required>
                        </div>

                        <div class="input-container full">
                            <label for="nome-responsavel">Nome do responsável</label>
                            <input type="text" id="nome-responsavel" name="nome-responsavel-cadastro"
                                value="<?= htmlspecialchars($nome_responsavel_cadastro, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Nome completo" maxlength="200" autocomplete="name" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Contato e acesso</h3>

                        <div class="input-container">
                            <label for="telefone-cadastro">Telefone</label>
                            <input type="tel" id="telefone-cadastro" name="telefone-cadastro"
                                value="<?= htmlspecialchars($telefone_cadastro, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="(00) 00000-0000" maxlength="20" autocomplete="tel" required>
                        </div>

                        <div class="input-container">
                            <label for="email-cadastro">E-mail da ONG</label>
                            <input type="email" id="email-cadastro" name="email-cadastro"
                                value="<?= htmlspecialchars($email_cadastro, ENT_QUOTES, 'UTF-8') ?>"
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
                                value="<?= htmlspecialchars($endereco_ong, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Rua, número e bairro" autocomplete="street-address" required>
                        </div>

                        <div class="input-container cidade">
                            <label for="cidade-ong">Cidade</label>
                            <input type="text" id="cidade-ong" name="cidade-ong" placeholder="Cidade"
                                value="<?= htmlspecialchars($cidade_ong, ENT_QUOTES, 'UTF-8') ?>"
                                maxlength="100" autocomplete="address-level2" required>
                        </div>

                        <div class="input-container uf">
                            <label for="estado-ong">UF</label>
                            <input type="text" id="estado-ong" name="estado-ong" placeholder="SP" minlength="2"
                                value="<?= htmlspecialchars($estado_ong, ENT_QUOTES, 'UTF-8') ?>"
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
    <script src="../SCRIPT/cadastro.js"></script>
</body>

</html>
