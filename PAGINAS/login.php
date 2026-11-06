<?php
    session_start();
    require_once("../conexao.php");

    $erro = '';
    $email = '';

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $email = trim($_POST["email_usuario"] ?? '');
        $senha = $_POST["senha-usuario"] ?? '';

        if($email === '' || $senha === ''){
            $erro = 'Informe seu e-mail e sua senha.';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
            $erro = 'Digite um e-mail válido.';
        } else {
            $stmt = $conn->prepare("SELECT * FROM USUARIO WHERE cd_email_usuario = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();

            if($res->num_rows > 0){
                $user = $res->fetch_assoc();

                if($user['cd_senha_usuario'] == $senha){
                    session_regenerate_id(true);
                    $_SESSION['tipo_conta']    = 'usuario';
                    $_SESSION['email_usuario'] = $user['cd_email_usuario'];
                    $_SESSION['nome_usuario']  = $user['nm_usuario'];
                    header('Location: ../index.php');
                    exit;
                }
            } else {
                $stmt = $conn->prepare("SELECT * FROM ONG WHERE cd_email_ong = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res = $stmt->get_result();

                if($res->num_rows > 0){
                    $ong = $res->fetch_assoc();

                    if($ong['cd_senha_ong'] == $senha){
                        session_regenerate_id(true);
                        $_SESSION['tipo_conta']   = 'ong';
                        $_SESSION['cnpj_ong']     = $ong['cd_cnpj_ong'];
                        $_SESSION['email_ong']    = $ong['cd_email_ong'];
                        $_SESSION['nome_ong']     = $ong['nm_ong'];
                        $_SESSION['nome_usuario'] = $ong['nm_ong'];
                        header('Location: ../index.php');
                        exit;
                    }
                }
            }
            $erro = 'E-mail ou senha incorretos.';
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
    <link rel="stylesheet" href="../CSS/form-feedback.css">
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

                    <?php if ($erro !== ''): ?>
                        <p class="form-feedback" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <div class="campos">
                        <div class="input-container">
                            <label for="email-login">E-mail</label>
                            <input type="email" id="email-login" name="email_usuario" placeholder="seu@email.com"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
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
