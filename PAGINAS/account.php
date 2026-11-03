<?php

session_start();

require_once("../conexao.php");

if (empty($_SESSION['email_usuario'])) {
    header("Location: login.php");
    exit;
}

function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function avisar($tipo, $mensagem, $secao = 'informacoes')
{
    $_SESSION['aviso'] = ['tipo' => $tipo, 'mensagem' => $mensagem, 'secao' => $secao];
    header("Location: " . $_SERVER['PHP_SELF'] . "#" . $secao);
    exit;
}

function buscarUsuario($conn, $email)
{
    $stmt = $conn->prepare("SELECT * FROM USUARIO WHERE cd_email_usuario = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$usuario = buscarUsuario($conn, $_SESSION['email_usuario']);

if (!$usuario) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$idUsuario = (int) $usuario['id_usuario'];
$estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
$diretorioImagens = __DIR__ . "/../assets/user_images/";

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'salvar_informacoes':
                $nome = trim($_POST['nome_usuario'] ?? '');
                $email = trim($_POST['email_usuario'] ?? '');
                $telefone = preg_replace('/\D/', '', $_POST['telefone_usuario'] ?? '');
                $endereco = trim($_POST['endereco_usuario'] ?? '');
                $cidade = trim($_POST['cidade_usuario'] ?? '');
                $estado = strtoupper(trim($_POST['estado_usuario'] ?? ''));
                $nascimento = trim($_POST['nascimento_usuario'] ?? '');

                if ($nome === '' || mb_strlen($nome) > 200) {
                    avisar('erro', 'Informe um nome válido com até 200 caracteres.');
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 200) {
                    avisar('erro', 'Informe um e-mail válido.');
                }

                if ($telefone !== '' && (strlen($telefone) < 10 || strlen($telefone) > 13)) {
                    avisar('erro', 'Informe um telefone com DDD.');
                }

                if ($estado !== '' && !in_array($estado, $estados, true)) {
                    avisar('erro', 'Selecione um estado válido.');
                }

                if ($nascimento !== '') {
                    $data = DateTime::createFromFormat('Y-m-d', $nascimento);
                    if (!$data || $data->format('Y-m-d') !== $nascimento || $data > new DateTime()) {
                        avisar('erro', 'Informe uma data de nascimento válida.');
                    }
                }

                $telefone = $telefone === '' ? null : $telefone;
                $endereco = $endereco === '' ? null : $endereco;
                $cidade = $cidade === '' ? null : $cidade;
                $estado = $estado === '' ? null : $estado;
                $nascimento = $nascimento === '' ? null : $nascimento;

                $stmt = $conn->prepare(
                    "UPDATE USUARIO SET
                        nm_usuario = ?,
                        cd_email_usuario = ?,
                        cd_telefone_usuario = ?,
                        ds_endereco_usuario = ?,
                        nm_cidade_usuario = ?,
                        sg_estado_usuario = ?,
                        dt_nascimento_usuario = ?
                    WHERE id_usuario = ?"
                );
                $stmt->bind_param("sssssssi", $nome, $email, $telefone, $endereco, $cidade, $estado, $nascimento, $idUsuario);
                $stmt->execute();

                $_SESSION['email_usuario'] = $email;
                $_SESSION['nome_usuario'] = $nome;

                avisar('sucesso', 'Informações atualizadas com sucesso.');
                break;

            case 'alterar_senha':
                $senhaAtual = $_POST['senha_atual'] ?? '';
                $novaSenha = $_POST['nova_senha'] ?? '';
                $confirmacao = $_POST['confirmar_senha'] ?? '';

                if ($senhaAtual !== $usuario['cd_senha_usuario']) {
                    avisar('erro', 'A senha atual está incorreta.', 'seguranca');
                }

                if (strlen($novaSenha) < 6 || strlen($novaSenha) > 50) {
                    avisar('erro', 'A nova senha deve ter entre 6 e 50 caracteres.', 'seguranca');
                }

                if ($novaSenha !== $confirmacao) {
                    avisar('erro', 'A confirmação não corresponde à nova senha.', 'seguranca');
                }

                $stmt = $conn->prepare("UPDATE USUARIO SET cd_senha_usuario = ? WHERE id_usuario = ?");
                $stmt->bind_param("si", $novaSenha, $idUsuario);
                $stmt->execute();

                avisar('sucesso', 'Senha alterada com sucesso.', 'seguranca');
                break;

            case 'salvar_foto':
                $arquivo = $_FILES['foto_perfil'] ?? null;

                if (!$arquivo || $arquivo['error'] !== UPLOAD_ERR_OK) {
                    avisar('erro', 'Não foi possível enviar a imagem. Tente novamente.');
                }

                if ($arquivo['size'] > 2 * 1024 * 1024) {
                    avisar('erro', 'A imagem deve ter no máximo 2 MB.');
                }

                $tiposPermitidos = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $tipo = $finfo->file($arquivo['tmp_name']);

                if (!isset($tiposPermitidos[$tipo])) {
                    avisar('erro', 'Envie uma imagem JPG, PNG ou WEBP.');
                }

                if (!is_dir($diretorioImagens)) {
                    mkdir($diretorioImagens, 0755, true);
                }

                $nomeArquivo = "perfil_" . $idUsuario . "_" . bin2hex(random_bytes(8)) . "." . $tiposPermitidos[$tipo];

                if (!move_uploaded_file($arquivo['tmp_name'], $diretorioImagens . $nomeArquivo)) {
                    avisar('erro', 'Não foi possível salvar a imagem no servidor.');
                }

                if (!empty($usuario['ds_imagem_perfil_usuario'])) {
                    $antiga = $diretorioImagens . basename($usuario['ds_imagem_perfil_usuario']);
                    if (is_file($antiga)) {
                        unlink($antiga);
                    }
                }

                $caminho = "assets/user_images/" . $nomeArquivo;
                $stmt = $conn->prepare("UPDATE USUARIO SET ds_imagem_perfil_usuario = ? WHERE id_usuario = ?");
                $stmt->bind_param("si", $caminho, $idUsuario);
                $stmt->execute();

                avisar('sucesso', 'Foto de perfil atualizada.');
                break;

            case 'remover_foto':
                if (!empty($usuario['ds_imagem_perfil_usuario'])) {
                    $antiga = $diretorioImagens . basename($usuario['ds_imagem_perfil_usuario']);
                    if (is_file($antiga)) {
                        unlink($antiga);
                    }
                }

                $stmt = $conn->prepare("UPDATE USUARIO SET ds_imagem_perfil_usuario = NULL WHERE id_usuario = ?");
                $stmt->bind_param("i", $idUsuario);
                $stmt->execute();

                avisar('sucesso', 'Foto de perfil removida.');
                break;

            case 'enviar_avaliacao':
                $comentario = trim($_POST['avaliacao'] ?? '');

                if ($comentario === '' || mb_strlen($comentario) > 100) {
                    avisar('erro', 'Escreva uma avaliação de até 100 caracteres.', 'avaliacao');
                }

                $stmt = $conn->prepare(
                    "INSERT INTO AVALIACAO (ds_comentario_avaliacao, id_usuario_fk) VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE
                        ds_comentario_avaliacao = VALUES(ds_comentario_avaliacao),
                        dt_avaliacao = CURRENT_TIMESTAMP"
                );
                $stmt->bind_param("si", $comentario, $idUsuario);
                $stmt->execute();

                avisar('sucesso', 'Avaliação enviada. Obrigado pelo feedback!', 'avaliacao');
                break;

            case 'excluir_avaliacao':
                $stmt = $conn->prepare("DELETE FROM AVALIACAO WHERE id_usuario_fk = ?");
                $stmt->bind_param("i", $idUsuario);
                $stmt->execute();

                avisar('sucesso', 'Avaliação excluída.', 'avaliacao');
                break;

            case 'excluir_conta':
                if (($_POST['senha_confirmacao'] ?? '') !== $usuario['cd_senha_usuario']) {
                    avisar('erro', 'Senha incorreta. A conta não foi excluída.', 'conta');
                }

                if (!empty($usuario['ds_imagem_perfil_usuario'])) {
                    $antiga = $diretorioImagens . basename($usuario['ds_imagem_perfil_usuario']);
                    if (is_file($antiga)) {
                        unlink($antiga);
                    }
                }

                $stmt = $conn->prepare("DELETE FROM USUARIO WHERE id_usuario = ?");
                $stmt->bind_param("i", $idUsuario);
                $stmt->execute();

                session_destroy();
                header("Location: login.php");
                exit;
        }
    } catch (mysqli_sql_exception $erro) {
        if ($erro->getCode() === 1062) {
            avisar('erro', 'Este e-mail já está em uso por outra conta.');
        }
    avisar('erro', 'Erro: ' . $erro->getMessage());
}
}

$stmt = $conn->prepare("SELECT ds_comentario_avaliacao FROM AVALIACAO WHERE id_usuario_fk = ?");
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$linhaAvaliacao = $stmt->get_result()->fetch_assoc();
$avaliacao = $linhaAvaliacao['ds_comentario_avaliacao'] ?? '';

$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);

$nomeCompleto = $usuario['nm_usuario'];
$primeiroNome = strtok($nomeCompleto, " ");
$iniciais = mb_strtoupper(mb_substr($primeiroNome, 0, 1));
$partesNome = explode(" ", trim($nomeCompleto));
if (count($partesNome) > 1) {
    $iniciais .= mb_strtoupper(mb_substr(end($partesNome), 0, 1));
}

$fotoPerfil = !empty($usuario['ds_imagem_perfil_usuario']) ? "../" . $usuario['ds_imagem_perfil_usuario'] : null;
// echo "<script>alert(" . $fotoPerfil . ")</script>";
$membroDesde = date("m/Y", strtotime($usuario['dt_cadastro']));

$telefoneExibicao = $usuario['cd_telefone_usuario'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olá, <?php echo e($primeiroNome); ?></title>
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/account.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-content">
            <a href="../index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>
                <span>Voltar ao portal</span>
            </a>
            <span class="topbar-title">Configurações da conta</span>
        </div>
    </header>

    <main class="account">
        <section class="profile-card">
            <div class="profile-cover"></div>

            <div class="profile-body">
                <div class="avatar-wrapper">
                    <div class="avatar">
                        <?php if ($fotoPerfil) { ?>
                            <img src="<?php echo e($fotoPerfil); ?>" alt="Foto de perfil de <?php echo e($primeiroNome); ?>">
                        <?php } else { ?>
                            <span><?php echo e($iniciais); ?></span>
                        <?php } ?>
                    </div>

                    <form action="" method="POST" enctype="multipart/form-data" id="form-foto">
                        <input type="hidden" name="acao" value="salvar_foto">
                        <input type="file" name="foto_perfil" id="foto-perfil" accept="image/jpeg,image/png,image/webp" hidden>
                        <label for="foto-perfil" class="avatar-edit" title="Alterar foto">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                    </form>
                </div>

                <div class="profile-identity">
                    <h1><?php echo e($nomeCompleto); ?></h1>
                    <p><?php echo e($usuario['cd_email_usuario']); ?></p>
                    <span class="profile-badge"><i class="bi bi-calendar-check"></i> Membro desde <?php echo e($membroDesde); ?></span>
                </div>

                <?php if ($fotoPerfil) { ?>
                    <form action="" method="POST" class="profile-actions">
                        <button type="submit" name="acao" value="remover_foto" class="button button-ghost">
                            <i class="bi bi-trash3"></i> Remover foto
                        </button>
                    </form>
                <?php } ?>
            </div>
        </section>

        <?php if ($aviso) { ?>
            <div class="alert alert-<?php echo e($aviso['tipo']); ?>" role="alert">
                <i class="bi <?php echo $aviso['tipo'] === 'sucesso' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?>"></i>
                <span><?php echo e($aviso['mensagem']); ?></span>
                <button type="button" class="alert-close" aria-label="Fechar aviso"><i class="bi bi-x-lg"></i></button>
            </div>
        <?php } ?>

        <div class="account-layout">
            <nav class="account-menu" aria-label="Seções da conta">
                <a href="#informacoes" class="menu-item" data-section="informacoes">
                    <i class="bi bi-person-fill"></i> Informações pessoais
                </a>
                <a href="#seguranca" class="menu-item" data-section="seguranca">
                    <i class="bi bi-shield-lock-fill"></i> Segurança
                </a>
                <a href="#preferencias" class="menu-item" data-section="preferencias">
                    <i class="bi bi-sliders"></i> Preferências
                </a>
                <a href="#avaliacao" class="menu-item" data-section="avaliacao">
                    <i class="bi bi-chat-heart-fill"></i> Minha avaliação
                </a>
                <a href="#conta" class="menu-item" data-section="conta">
                    <i class="bi bi-gear-fill"></i> Conta
                </a>
            </nav>

            <div class="account-sections">
                <section class="card" id="informacoes" data-panel>
                    <header class="card-header">
                        <h2>Informações pessoais</h2>
                        <p>Mantenha seus dados atualizados para um atendimento mais preciso.</p>
                    </header>

                    <form action="" method="POST" class="form-grid">
                        <div class="field field-full">
                            <label for="nome_usuario">Nome completo</label>
                            <div class="input-wrapper">
                                <i class="bi bi-person"></i>
                                <input type="text" id="nome_usuario" name="nome_usuario" maxlength="200" value="<?php echo e($usuario['nm_usuario']); ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="email_usuario">E-mail</label>
                            <div class="input-wrapper">
                                <i class="bi bi-envelope"></i>
                                <input type="email" id="email_usuario" name="email_usuario" maxlength="200" value="<?php echo e($usuario['cd_email_usuario']); ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="telefone_usuario">Telefone</label>
                            <div class="input-wrapper">
                                <i class="bi bi-telephone"></i>
                                <input type="tel" id="telefone_usuario" name="telefone_usuario" maxlength="16" placeholder="(00) 00000-0000" value="<?php echo e($telefoneExibicao); ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="nascimento_usuario">Data de nascimento</label>
                            <div class="input-wrapper">
                                <i class="bi bi-calendar-event"></i>
                                <input type="date" id="nascimento_usuario" name="nascimento_usuario" max="<?php echo date('Y-m-d'); ?>" value="<?php echo e($usuario['dt_nascimento_usuario']); ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="estado_usuario">Estado</label>
                            <div class="input-wrapper">
                                <i class="bi bi-map"></i>
                                <select id="estado_usuario" name="estado_usuario">
                                    <option value="">Selecione</option>
                                    <?php foreach ($estados as $uf) { ?>
                                        <option value="<?php echo e($uf); ?>" <?php echo trim((string) $usuario['sg_estado_usuario']) === $uf ? 'selected' : ''; ?>><?php echo e($uf); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="cidade_usuario">Cidade</label>
                            <div class="input-wrapper">
                                <i class="bi bi-buildings"></i>
                                <input type="text" id="cidade_usuario" name="cidade_usuario" maxlength="100" value="<?php echo e($usuario['nm_cidade_usuario']); ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="endereco_usuario">Endereço</label>
                            <div class="input-wrapper">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text" id="endereco_usuario" name="endereco_usuario" maxlength="200" value="<?php echo e($usuario['ds_endereco_usuario']); ?>">
                            </div>
                        </div>

                        <div class="form-actions field-full">
                            <button type="reset" class="button button-ghost">Descartar</button>
                            <button type="submit" name="acao" value="salvar_informacoes" class="button button-primary">
                                <i class="bi bi-check2"></i> Salvar alterações
                            </button>
                        </div>
                    </form>
                </section>

                <section class="card" id="seguranca" data-panel>
                    <header class="card-header">
                        <h2>Segurança</h2>
                        <p>Altere sua senha para manter sua conta protegida.</p>
                    </header>

                    <form action="" method="POST" class="form-grid">
                        <div class="field field-full">
                            <label for="senha_atual">Senha atual</label>
                            <div class="input-wrapper">
                                <i class="bi bi-key"></i>
                                <input type="password" id="senha_atual" name="senha_atual" maxlength="50" autocomplete="current-password" required>
                                <button type="button" class="toggle-password" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="nova_senha">Nova senha</label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock"></i>
                                <input type="password" id="nova_senha" name="nova_senha" minlength="6" maxlength="50" autocomplete="new-password" required>
                                <button type="button" class="toggle-password" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="confirmar_senha">Confirmar nova senha</label>
                            <div class="input-wrapper">
                                <i class="bi bi-lock-fill"></i>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" minlength="6" maxlength="50" autocomplete="new-password" required>
                                <button type="button" class="toggle-password" aria-label="Mostrar senha"><i class="bi bi-eye"></i></button>
                            </div>
                        </div>

                        <div class="form-actions field-full">
                            <button type="submit" name="acao" value="alterar_senha" class="button button-primary">
                                <i class="bi bi-shield-check"></i> Atualizar senha
                            </button>
                        </div>
                    </form>
                </section>

                <section class="card" id="preferencias" data-panel>
                    <header class="card-header">
                        <h2>Preferências</h2>
                        <p>Ajuste a aparência e a acessibilidade do portal.</p>
                    </header>

                    <ul class="preference-list">
                        <li class="preference">
                            <div class="preference-icon"><i class="bi bi-moon-stars-fill"></i></div>
                            <div class="preference-text">
                                <strong>Modo escuro</strong>
                                <span>Reduz o brilho da tela e o cansaço visual.</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="toggle-modoEscuro">
                                <span class="slider"></span>
                            </label>
                        </li>
                        <li class="preference">
                            <div class="preference-icon"><i class="bi bi-circle-half"></i></div>
                            <div class="preference-text">
                                <strong>Alto contraste</strong>
                                <span>Destaca textos e elementos para facilitar a leitura.</span>
                            </div>
                            <label class="switch">
                                <input type="checkbox" id="toggle-altoContraste">
                                <span class="slider"></span>
                            </label>
                        </li>
                    </ul>
                </section>

                <section class="card" id="avaliacao" data-panel>
                    <header class="card-header">
                        <h2>Minha avaliação</h2>
                        <p>Conte o que você acha do portal. Sua opinião nos ajuda a melhorar.</p>
                    </header>

                    <form action="" method="POST" class="form-grid">
                        <div class="field field-full">
                            <label for="inputAvaliation">Seu comentário</label>
                            <textarea id="inputAvaliation" name="avaliacao" rows="3" maxlength="100" placeholder="Você ainda não fez nenhuma avaliação."><?php echo e($avaliacao); ?></textarea>
                            <span class="counter"><span id="contador-avaliacao"><?php echo mb_strlen($avaliacao); ?></span>/100</span>
                        </div>

                        <div class="form-actions field-full">
                            <?php if ($avaliacao !== '') { ?>
                                <button type="submit" name="acao" value="excluir_avaliacao" class="button button-danger-ghost" formnovalidate>
                                    <i class="bi bi-trash3"></i> Excluir
                                </button>
                            <?php } ?>
                            <button type="submit" name="acao" value="enviar_avaliacao" class="button button-primary">
                                <i class="bi bi-send"></i> <?php echo $avaliacao !== '' ? 'Atualizar avaliação' : 'Enviar avaliação'; ?>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="card" id="conta" data-panel>
                    <header class="card-header">
                        <h2>Conta</h2>
                        <p>Encerre sua sessão ou exclua sua conta definitivamente.</p>
                    </header>

                    <ul class="preference-list">
                        <li class="preference">
                            <div class="preference-icon"><i class="bi bi-box-arrow-right"></i></div>
                            <div class="preference-text">
                                <strong>Sair da conta</strong>
                                <span>Você precisará entrar novamente para acessar seus dados.</span>
                            </div>
                            <a href="logout.php" class="button button-ghost">Sair</a>
                        </li>
                        <li class="preference preference-danger">
                            <div class="preference-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
                            <div class="preference-text">
                                <strong>Excluir conta</strong>
                                <span>Todos os seus dados serão removidos e não poderão ser recuperados.</span>
                            </div>
                            <button type="button" class="button button-danger" id="delete-account">Excluir conta</button>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </main>

    <div class="modal" id="modal-excluir" hidden>
        <div class="modal-backdrop" data-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <div class="modal-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <h3 id="modal-titulo">Excluir sua conta?</h3>
            <p>Esta ação é permanente. Para confirmar, digite sua senha.</p>

            <form action="" method="POST">
                <div class="input-wrapper">
                    <i class="bi bi-lock"></i>
                    <input type="password" name="senha_confirmacao" maxlength="50" placeholder="Sua senha" autocomplete="current-password" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="button button-ghost" data-close>Cancelar</button>
                    <button type="submit" name="acao" value="excluir_conta" class="button button-danger">Excluir definitivamente</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../SCRIPT/account.js"></script>
    <script src="../SCRIPT/modoEscuro.js"></script>
</body>
</html>