<?php

session_start();

require_once("../conexao.php");


if (empty($_SESSION['email_ong'])) {
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

function buscarOng($conn, $email)
{
    $stmt = $conn->prepare("SELECT * FROM ONG WHERE cd_email_ong = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function formatarIdade($meses)
{
    $meses = (int) $meses;
    $anos = intdiv($meses, 12);
    $resto = $meses % 12;
    $partes = [];

    if ($anos > 0) {
        $partes[] = $anos . ($anos === 1 ? ' ano' : ' anos');
    }
    if ($resto > 0 || $anos === 0) {
        $partes[] = $resto . ($resto === 1 ? ' mês' : ' meses');
    }

    return implode(' e ', $partes);
}

function removerImagem($diretorio, $caminho)
{
    if (!empty($caminho)) {
        $arquivo = $diretorio . basename($caminho);
        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }
}

function salvarImagemPet($arquivo, $diretorio, $prefixo)
{
    // Retorna [nome_do_arquivo, mensagem_de_erro]
    if (!$arquivo || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Não foi possível enviar a imagem do pet. Tente novamente.'];
    }

    if ($arquivo['size'] > 2 * 1024 * 1024) {
        return [null, 'A imagem do pet deve ter no máximo 2 MB.'];
    }

    $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);

    if (!isset($tiposPermitidos[$tipo])) {
        return [null, 'Envie uma imagem JPG, PNG ou WEBP para o pet.'];
    }

    if (!is_dir($diretorio)) {
        mkdir($diretorio, 0755, true);
    }

    $nomeArquivo = $prefixo . bin2hex(random_bytes(8)) . "." . $tiposPermitidos[$tipo];

    if (!move_uploaded_file($arquivo['tmp_name'], $diretorio . $nomeArquivo)) {
        return [null, 'Não foi possível salvar a imagem do pet no servidor.'];
    }

    return [$nomeArquivo, null];
}

$ong = buscarOng($conn, $_SESSION['email_ong']);

if (!$ong) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$idOng = (int) $ong['cd_ong'];
$estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
$diretorioImagens = __DIR__ . "/../assets/ong_images/";
$diretorioPets = __DIR__ . "/../assets/pet_images/";

// Valores do ENUM de sexo do pet (lidos direto do banco, então funciona com qualquer valor que você tenha definido)
$sexos = [];
$colunaSexo = $conn->query("SHOW COLUMNS FROM PET LIKE 'ds_sexo_pet'")->fetch_assoc();
if ($colunaSexo && preg_match_all("/'([^']*)'/", $colunaSexo['Type'], $correspondencias)) {
    $sexos = $correspondencias[1];
}

// Espécies e raças para o formulário de pets
$especies = $conn->query("SELECT cd_especie, nm_especie FROM ESPECIE ORDER BY nm_especie")->fetch_all(MYSQLI_ASSOC);
$racas = $conn->query("SELECT cd_raca, nm_raca, cd_especie FROM RACA ORDER BY nm_raca")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $acao = $_POST['acao'] ?? '';

    try {
        switch ($acao) {
            case 'salvar_informacoes':
                $nome = trim($_POST['nome_ong'] ?? '');
                $responsavel = trim($_POST['responsavel_ong'] ?? '');
                $email = trim($_POST['email_ong'] ?? '');
                $telefone = preg_replace('/\D/', '', $_POST['telefone_ong'] ?? '');
                $endereco = trim($_POST['endereco_ong'] ?? '');
                $cidade = trim($_POST['cidade_ong'] ?? '');
                $estado = strtoupper(trim($_POST['estado_ong'] ?? ''));

                if ($nome === '' || mb_strlen($nome) > 200) {
                    avisar('erro', 'Informe um nome válido com até 200 caracteres.');
                }

                if ($responsavel === '' || mb_strlen($responsavel) > 200) {
                    avisar('erro', 'Informe o nome do responsável com até 200 caracteres.');
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

                $telefone = $telefone === '' ? null : $telefone;
                $endereco = $endereco === '' ? null : $endereco;
                $cidade = $cidade === '' ? null : $cidade;
                $estado = $estado === '' ? null : $estado;

                $stmt = $conn->prepare(
                    "UPDATE ONG SET
                        nm_ong = ?,
                        nm_responsavel_ong = ?,
                        cd_email_ong = ?,
                        cd_telefone_ong = ?,
                        ds_endereco_ong = ?,
                        nm_cidade_ong = ?,
                        sg_estado_ong = ?
                    WHERE cd_ong = ?"
                );
                $stmt->bind_param("sssssssi", $nome, $responsavel, $email, $telefone, $endereco, $cidade, $estado, $idOng);
                $stmt->execute();

                $_SESSION['email_ong'] = $email;
                $_SESSION['nome_ong'] = $nome;

                avisar('sucesso', 'Informações atualizadas com sucesso.');
                break;

            case 'cadastrar_pet':
                $nomePet = trim($_POST['nome_pet'] ?? '');
                $idadeMeses = $_POST['idade_meses_pet'] ?? '';
                $sexoPet = $_POST['sexo_pet'] ?? '';
                $especiePet = (int) ($_POST['especie_pet'] ?? 0);
                $racaPet = (int) ($_POST['raca_pet'] ?? 0);

                if ($nomePet === '' || mb_strlen($nomePet) > 200) {
                    avisar('erro', 'Informe o nome do pet com até 200 caracteres.', 'pets');
                }

                if (!ctype_digit((string) $idadeMeses) || (int) $idadeMeses > 600) {
                    avisar('erro', 'Informe a idade do pet em meses (0 a 600).', 'pets');
                }
                $idadeMeses = (int) $idadeMeses;

                if (!in_array($sexoPet, $sexos, true)) {
                    avisar('erro', 'Selecione o sexo do pet.', 'pets');
                }

                $stmt = $conn->prepare("SELECT cd_especie FROM ESPECIE WHERE cd_especie = ?");
                $stmt->bind_param("i", $especiePet);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) {
                    avisar('erro', 'Selecione a espécie do pet.', 'pets');
                }

                // Raça é opcional, mas precisa pertencer à espécie escolhida
                if ($racaPet > 0) {
                    $stmt = $conn->prepare("SELECT cd_raca FROM RACA WHERE cd_raca = ? AND cd_especie = ?");
                    $stmt->bind_param("ii", $racaPet, $especiePet);
                    $stmt->execute();
                    if (!$stmt->get_result()->fetch_assoc()) {
                        avisar('erro', 'A raça selecionada não pertence à espécie escolhida.', 'pets');
                    }
                } else {
                    $racaPet = null;
                }

                // Imagem do pet (opcional)
                [$nomeImagemPet, $erroImagem] = salvarImagemPet($_FILES['imagem_pet'] ?? null, $diretorioPets, "pet_" . $idOng . "_");

                if ($erroImagem) {
                    avisar('erro', $erroImagem, 'pets');
                }

                $caminhoImagemPet = $nomeImagemPet ? "assets/pet_images/" . $nomeImagemPet : null;

                try {
                    $stmt = $conn->prepare(
                        "INSERT INTO PET (nm_pet, ds_idade_meses_pet, ds_sexo_pet, cd_especie_pet, cd_raca_pet, cd_ong, ds_imagem_pet)
                        VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->bind_param("sisiiis", $nomePet, $idadeMeses, $sexoPet, $especiePet, $racaPet, $idOng, $caminhoImagemPet);
                    $stmt->execute();
                } catch (mysqli_sql_exception $erroPet) {
                    // Se o cadastro falhar, não deixa a imagem órfã na pasta
                    removerImagem($diretorioPets, $caminhoImagemPet);
                    throw $erroPet;
                }

                avisar('sucesso', 'Pet cadastrado com sucesso.', 'pets');
                break;

            case 'excluir_pet':
                $idPet = (int) ($_POST['cd_pet'] ?? 0);

                $stmt = $conn->prepare("SELECT ds_imagem_pet FROM PET WHERE cd_pet = ? AND cd_ong = ?");
                $stmt->bind_param("ii", $idPet, $idOng);
                $stmt->execute();
                $petExcluido = $stmt->get_result()->fetch_assoc();

                // O "AND cd_ong" garante que a ONG só exclua os próprios pets
                $stmt = $conn->prepare("DELETE FROM PET WHERE cd_pet = ? AND cd_ong = ?");
                $stmt->bind_param("ii", $idPet, $idOng);
                $stmt->execute();

                if ($petExcluido) {
                    removerImagem($diretorioPets, $petExcluido['ds_imagem_pet']);
                }

                avisar('sucesso', 'Pet removido.', 'pets');
                break;

            case 'alterar_senha':
                $senhaAtual = $_POST['senha_atual'] ?? '';
                $novaSenha = $_POST['nova_senha'] ?? '';
                $confirmacao = $_POST['confirmar_senha'] ?? '';

                if ($senhaAtual !== $ong['cd_senha_ong']) {
                    avisar('erro', 'A senha atual está incorreta.', 'seguranca');
                }

                if (strlen($novaSenha) < 6 || strlen($novaSenha) > 50) {
                    avisar('erro', 'A nova senha deve ter entre 6 e 50 caracteres.', 'seguranca');
                }

                if ($novaSenha !== $confirmacao) {
                    avisar('erro', 'A confirmação não corresponde à nova senha.', 'seguranca');
                }

                $stmt = $conn->prepare("UPDATE ONG SET cd_senha_ong = ? WHERE cd_ong = ?");
                $stmt->bind_param("si", $novaSenha, $idOng);
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

                $nomeArquivo = "ong_" . $idOng . "_" . bin2hex(random_bytes(8)) . "." . $tiposPermitidos[$tipo];

                if (!move_uploaded_file($arquivo['tmp_name'], $diretorioImagens . $nomeArquivo)) {
                    avisar('erro', 'Não foi possível salvar a imagem no servidor.');
                }

                removerImagem($diretorioImagens, $ong['ds_imagem_perfil_ong']);

                $caminho = "assets/ong_images/" . $nomeArquivo;
                $stmt = $conn->prepare("UPDATE ONG SET ds_imagem_perfil_ong = ? WHERE cd_ong = ?");
                $stmt->bind_param("si", $caminho, $idOng);
                $stmt->execute();

                avisar('sucesso', 'Foto de perfil atualizada.');
                break;

            case 'remover_foto':
                removerImagem($diretorioImagens, $ong['ds_imagem_perfil_ong']);

                $stmt = $conn->prepare("UPDATE ONG SET ds_imagem_perfil_ong = NULL WHERE cd_ong = ?");
                $stmt->bind_param("i", $idOng);
                $stmt->execute();

                avisar('sucesso', 'Foto de perfil removida.');
                break;

            case 'excluir_conta':
                if (($_POST['senha_confirmacao'] ?? '') !== $ong['cd_senha_ong']) {
                    avisar('erro', 'Senha incorreta. A conta não foi excluída.', 'conta');
                }

                removerImagem($diretorioImagens, $ong['ds_imagem_perfil_ong']);

                // Remove as imagens dos pets da ONG
                $stmt = $conn->prepare("SELECT ds_imagem_pet FROM PET WHERE cd_ong = ?");
                $stmt->bind_param("i", $idOng);
                $stmt->execute();
                foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $petConta) {
                    removerImagem($diretorioPets, $petConta['ds_imagem_pet']);
                }

                // Os pets precisam ser removidos antes, por causa da chave estrangeira
                $stmt = $conn->prepare("DELETE FROM PET WHERE cd_ong = ?");
                $stmt->bind_param("i", $idOng);
                $stmt->execute();

                $stmt = $conn->prepare("DELETE FROM ONG WHERE cd_ong = ?");
                $stmt->bind_param("i", $idOng);
                $stmt->execute();

                session_destroy();
                header("Location: login.php");
                exit;
        }
    } catch (mysqli_sql_exception $erro) {
        if ($erro->getCode() === 1062) {
            avisar('erro', 'Este e-mail já está em uso por outra conta.');
        }
        avisar('erro', 'Ocorreu um erro ao salvar. Tente novamente em instantes.');
    }
}

// Pets cadastrados por esta ONG
$stmt = $conn->prepare(
    "SELECT p.cd_pet, p.nm_pet, p.ds_idade_meses_pet, p.ds_sexo_pet, p.ds_imagem_pet, e.nm_especie, r.nm_raca
    FROM PET p
    LEFT JOIN ESPECIE e ON e.cd_especie = p.cd_especie_pet
    LEFT JOIN RACA r ON r.cd_raca = p.cd_raca_pet
    WHERE p.cd_ong = ?
    ORDER BY p.nm_pet"
);
$stmt->bind_param("i", $idOng);
$stmt->execute();
$pets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$aviso = $_SESSION['aviso'] ?? null;
unset($_SESSION['aviso']);

$nomeCompleto = $ong['nm_ong'];
$primeiroNome = strtok($nomeCompleto, " ");
$iniciais = mb_strtoupper(mb_substr($primeiroNome, 0, 1));
$partesNome = explode(" ", trim($nomeCompleto));
if (count($partesNome) > 1) {
    $iniciais .= mb_strtoupper(mb_substr(end($partesNome), 0, 1));
}

$fotoPerfil = !empty($ong['ds_imagem_perfil_ong']) ? "../" . $ong['ds_imagem_perfil_ong'] : null;
$aprovadaDesde = !empty($ong['dt_aprovacao_ong']) ? date("m/Y", strtotime($ong['dt_aprovacao_ong'])) : null;

$telefoneExibicao = $ong['cd_telefone_ong'] ?? '';

// CNPJ formatado para exibição
$cnpj = preg_replace('/\D/', '', (string) $ong['cd_cnpj_ong']);
$cnpjExibicao = strlen($cnpj) === 14
    ? substr($cnpj, 0, 2) . '.' . substr($cnpj, 2, 3) . '.' . substr($cnpj, 5, 3) . '/' . substr($cnpj, 8, 4) . '-' . substr($cnpj, 12, 2)
    : $cnpj;

    $SolicitacoesQuery = "
                    SELECT
                        f.id_usuario_fk,
                        f.id_pet_fk,
                        u.nm_usuario,
                        p.nm_pet,
                        p.ds_imagem_pet,
                        f.dt_envio,
                        f.cd_status
                    FROM formulario_adocao f
                    JOIN pet p     ON p.cd_pet = f.id_pet_fk
                    JOIN usuario u ON u.id_usuario = f.id_usuario_fk
                    WHERE p.cd_ong = ?
                    ORDER BY f.dt_envio DESC; 
                    ";
    $stmt = $conn->prepare($SolicitacoesQuery);
    $stmt->bind_param("i", $idOng);
    $stmt->execute();
    $solicitacoes = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Olá, <?php echo e($primeiroNome); ?></title>
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/account_ong.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">    <script src="../SCRIPT/preferencias.js"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../navbar.php'; ?>
    <!-- <header class="topbar">
        <div class="topbar-content">
            <a href="../index.php" class="back-link">
                <i class="bi bi-arrow-left"></i>
                <span>Voltar ao portal</span>
            </a>
            <span class="topbar-title">Configurações da conta</span>
        </div>
    </header> -->

    <main class="account">
        <section class="profile-card">
            <div class="profile-cover"></div>

            <div class="profile-body">
                <div class="avatar-wrapper">
                    <div class="avatar">
                        <?php if ($fotoPerfil) { ?>
                            <img src="<?php echo e($fotoPerfil); ?>" alt="Logo da ONG <?php echo e($nomeCompleto); ?>">
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
                    <p><?php echo e($ong['cd_email_ong']); ?></p>
                    <span class="profile-badge">
                        <i class="bi bi-patch-check-fill"></i>
                        <?php echo $aprovadaDesde ? 'ONG aprovada desde ' . e($aprovadaDesde) : 'ONG em análise'; ?>
                    </span>
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
                    <i class="bi bi-building-fill"></i> Informações da ONG
                </a>
                <a href="#pets" class="menu-item" data-section="pets">
                    <i class="fa-solid fa-paw"></i> Pets
                </a>
                <a href="#solicitacoes" class="menu-item" data-section="solicitacoes">
                    <i class="fa-solid fa-folder-open"></i> Solicitacoes
                </a>
                <a href="#seguranca" class="menu-item" data-section="seguranca">
                    <i class="bi bi-shield-lock-fill"></i> Segurança
                </a>
                <a href="#preferencias" class="menu-item" data-section="preferencias">
                    <i class="bi bi-sliders"></i> Preferências
                </a>
                <a href="#conta" class="menu-item" data-section="conta">
                    <i class="bi bi-gear-fill"></i> Conta
                </a>
            </nav>

            <div class="account-sections">
                <section class="card" id="informacoes" data-panel>
                    <header class="card-header">
                        <h2>Informações da ONG</h2>
                        <p>Mantenha os dados da sua organização atualizados para que os adotantes possam entrar em contato.</p>
                    </header>

                    <form action="" method="POST" class="form-grid">
                        <div class="field field-full">
                            <label for="nome_ong">Nome da ONG</label>
                            <div class="input-wrapper">
                                <i class="bi bi-building"></i>
                                <input type="text" id="nome_ong" name="nome_ong" maxlength="200" value="<?php echo e($ong['nm_ong']); ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="cnpj_ong">CNPJ</label>
                            <div class="input-wrapper">
                                <i class="bi bi-card-text"></i>
                                <input type="text" id="cnpj_ong" value="<?php echo e($cnpjExibicao); ?>" readonly disabled>
                            </div>
                        </div>

                        <div class="field">
                            <label for="responsavel_ong">Responsável</label>
                            <div class="input-wrapper">
                                <i class="bi bi-person"></i>
                                <input type="text" id="responsavel_ong" name="responsavel_ong" maxlength="200" value="<?php echo e($ong['nm_responsavel_ong']); ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="email_ong">E-mail</label>
                            <div class="input-wrapper">
                                <i class="bi bi-envelope"></i>
                                <input type="email" id="email_ong" name="email_ong" maxlength="200" value="<?php echo e($ong['cd_email_ong']); ?>" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="telefone_ong">Telefone</label>
                            <div class="input-wrapper">
                                <i class="bi bi-telephone"></i>
                                <input type="tel" id="telefone_ong" name="telefone_ong" maxlength="20" placeholder="(00) 00000-0000" value="<?php echo e($telefoneExibicao); ?>">
                            </div>
                        </div>

                        <div class="field">
                            <label for="estado_ong">Estado</label>
                            <div class="input-wrapper">
                                <i class="bi bi-map"></i>
                                <select id="estado_ong" name="estado_ong">
                                    <option value="">Selecione</option>
                                    <?php foreach ($estados as $uf) { ?>
                                        <option value="<?php echo e($uf); ?>" <?php echo trim((string) $ong['sg_estado_ong']) === $uf ? 'selected' : ''; ?>><?php echo e($uf); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="cidade_ong">Cidade</label>
                            <div class="input-wrapper">
                                <i class="bi bi-buildings"></i>
                                <input type="text" id="cidade_ong" name="cidade_ong" maxlength="100" value="<?php echo e($ong['nm_cidade_ong']); ?>">
                            </div>
                        </div>

                        <div class="field field-full">
                            <label for="endereco_ong">Endereço</label>
                            <div class="input-wrapper">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text" id="endereco_ong" name="endereco_ong" maxlength="200" value="<?php echo e($ong['ds_endereco_ong']); ?>">
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

                <section class="card" id="pets" data-panel>
                    <?php if($ong['ic_ativo_ong']): ?>
                    <header class="card-header">
                        <h2>Pets</h2>
                        <p>Cadastre os animais disponíveis para adoção na sua ONG.</p>
                    </header>

                    <form action="" method="POST" enctype="multipart/form-data" class="form-grid">
                        <div class="field field-full">
                            <label for="nome_pet">Nome do pet</label>
                            <div class="input-wrapper">
                                <i class="bi bi-tag"></i>
                                <input type="text" id="nome_pet" name="nome_pet" maxlength="200" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="idade_meses_pet">Idade (em meses)</label>
                            <div class="input-wrapper">
                                <i class="bi bi-hourglass-split"></i>
                                <input type="number" id="idade_meses_pet" name="idade_meses_pet" min="0" max="600" required>
                            </div>
                        </div>

                        <div class="field">
                            <label for="sexo_pet">Sexo</label>
                            <div class="input-wrapper">
                                <i class="bi bi-gender-ambiguous"></i>
                                <select id="sexo_pet" name="sexo_pet" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($sexos as $sexo) { ?>
                                        <option value="<?php echo e($sexo); ?>"><?php echo e($sexo); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="especie_pet">Espécie</label>
                            <div class="input-wrapper">
                                <i class="bi bi-diagram-3"></i>
                                <select id="especie_pet" name="especie_pet" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($especies as $especie) { ?>
                                        <option value="<?php echo (int) $especie['cd_especie']; ?>"><?php echo e($especie['nm_especie']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="raca_pet">Raça</label>
                            <div class="input-wrapper">
                                <i class="bi bi-stars"></i>
                                <select id="raca_pet" name="raca_pet">
                                    <option value="">Sem raça definida</option>
                                    <?php foreach ($racas as $raca) { ?>
                                        <option value="<?php echo (int) $raca['cd_raca']; ?>" data-especie="<?php echo (int) $raca['cd_especie']; ?>" hidden disabled><?php echo e($raca['nm_raca']); ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="field field-full">
                            <label for="imagem_pet">Foto do pet (opcional)</label>

                            <input type="file" id="imagem_pet" name="imagem_pet" class="dropzone-input" accept="image/jpeg,image/png,image/webp">

                            <label for="imagem_pet" class="dropzone" id="dropzone">
                                <span class="dropzone-empty">
                                    <span class="dropzone-icon"><i class="bi bi-cloud-arrow-up-fill"></i></span>
                                    <span class="dropzone-text">
                                        <strong>Clique para escolher</strong> ou arraste a foto até aqui
                                    </span>
                                    <small>JPG, PNG ou WEBP • até 2 MB</small>
                                </span>

                                <span class="dropzone-preview" hidden>
                                    <img src="" alt="Pré-visualização da foto do pet" id="dropzone-img">
                                    <span class="dropzone-info">
                                        <strong id="dropzone-nome"></strong>
                                        <small id="dropzone-tamanho"></small>
                                    </span>
                                    <button type="button" class="dropzone-remove" id="dropzone-remover" aria-label="Remover foto selecionada">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </span>
                            </label>
                        </div>

                        <div class="form-actions field-full">
                            <button type="reset" class="button button-ghost">Limpar</button>
                            <button type="submit" name="acao" value="cadastrar_pet" class="button button-primary">
                                <i class="bi bi-plus-lg"></i> Cadastrar pet
                            </button>
                        </div>
                    </form>

                    <header class="card-header">
                        <h2>Pets cadastrados (<?php echo count($pets); ?>)</h2>
                    </header>

                    <?php if (empty($pets)) { ?>
                        <p>Você ainda não cadastrou nenhum pet.</p>
                    <?php } else { ?>
                        <ul class="preference-list">
                            <?php foreach ($pets as $pet) { ?>
                                <li class="preference">
                                    <div class="preference-icon" <?php echo !empty($pet['ds_imagem_pet']) ? 'style="overflow:hidden;padding:0;"' : ''; ?>>
                                        <?php if (!empty($pet['ds_imagem_pet'])) { ?>
                                            <img src="<?php echo e('../' . $pet['ds_imagem_pet']); ?>" alt="Foto de <?php echo e($pet['nm_pet']); ?>" style="width:100%;height:100%;object-fit:cover;display:block;">
                                        <?php } else { ?>
                                            <?php echo iconePet(); ?>
                                        <?php } ?>
                                    </div>
                                    <div class="preference-text">
                                        <strong><?php echo e($pet['nm_pet']); ?></strong>
                                        <span>
                                            <?php
                                            $detalhes = [
                                                $pet['nm_especie'] ?? null,
                                                $pet['nm_raca'] ?? null,
                                                $pet['ds_sexo_pet'],
                                                formatarIdade($pet['ds_idade_meses_pet'])
                                            ];
                                            echo e(implode(' • ', array_filter($detalhes)));
                                            ?>
                                        </span>
                                    </div>
                                    <form action="" method="POST" onsubmit="return confirm('Remover o pet <?php echo e(addslashes($pet['nm_pet'])); ?>?');">
                                        <input type="hidden" name="cd_pet" value="<?php echo (int) $pet['cd_pet']; ?>">
                                        <button type="submit" name="acao" value="excluir_pet" class="button button-danger-ghost" aria-label="Remover <?php echo e($pet['nm_pet']); ?>">
                                            <i class="bi bi-trash3"></i> Remover
                                        </button>
                                    </form>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                    <?php else: ?>
                        <p class="aprovation-message"><i class="bi bi-alarm"></i>Aguarde sua aprovação em nosso portal!
                        </p>
                    <?php endif ?>
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

                <section class="card" id="conta" data-panel>
                    <header class="card-header">
                        <h2>Conta</h2>
                        <p>Encerre sua sessão ou exclua a conta da ONG definitivamente.</p>
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
                                <span>Todos os dados da ONG e os pets cadastrados serão removidos e não poderão ser recuperados.</span>
                            </div>
                            <button type="button" class="button button-danger" id="delete-account">Excluir conta</button>
                        </li>
                    </ul>
                </section>
                
                <section class="card" id="solicitacoes" data-panel>
                    <header class="card-header">
                        <h2>Solicitações de Adoção</h2>
                        <p>Administre as solicitações de adoção</p>
                    </header>
                    <?php if ($solicitacoes->num_rows == 0): ?>
    <p class="solicitations-empty">Nenhuma solicitação de adoção por enquanto.</p>
    <?php else: ?>
    <ul class="solicitations-list">
        <?php foreach ($solicitacoes as $s): ?>
            <?php
                $dataEnvio = date('d/m/Y', strtotime($s['dt_envio']));
                $fotoSolicitacao = !empty($s['ds_imagem_pet']) ? '../' . $s['ds_imagem_pet'] : null;

                // Ajuste para os valores reais do seu cd_status
                $statusMap = [
                    'pendente'  => ['pendente', 'Em análise'],
                    'aprovado'  => ['aprovado', 'Aprovada'],
                    'recusado'  => ['recusado', 'Recusada'],
                ];
                $statusChave = strtolower((string) $s['cd_status']);
                [$statusClasse, $statusTexto] = $statusMap[$statusChave] ?? ['pendente', e($s['cd_status'])];
            ?>
            <li class="solicitation">
                <div class="solicitation-photo">
                    <?php if ($fotoSolicitacao): ?>
                        <img src="<?php echo e($fotoSolicitacao); ?>" alt="Foto de <?php echo e($s['nm_pet']); ?>">
                    <?php else: ?>
                        <i class="fa-solid fa-paw"></i>
                    <?php endif; ?>
                </div>

                <div class="solicitation-info">
                    <h3>Solicitação para <?php echo e($s['nm_pet']); ?></h3>
                    <div class="solicitation-meta">
                        <span><i class="bi bi-person"></i> <?php echo e($s['nm_usuario']); ?></span>
                        <span><i class="bi bi-calendar3"></i> <?php echo e($dataEnvio); ?></span>
                    </div>
                </div>

                <span class="solicitation-status status-<?php echo e($statusClasse); ?>">
                    <?php echo e($statusTexto); ?>
                </span>

                <a href="detalhe_solicitacao.php?usuario=<?php echo (int) $s['id_usuario_fk']; ?>&pet=<?php echo (int) $s['id_pet_fk']; ?>"
                class="solicitation-action" aria-label="Ver solicitação de <?php echo e($s['nm_usuario']); ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
                </section>
            </div>
        </div>
    </main>

    <div class="modal" id="modal-excluir" hidden>
        <div class="modal-backdrop" data-close></div>
        <div class="modal-dialog" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
            <div class="modal-icon"><i class="bi bi-exclamation-octagon-fill"></i></div>
            <h3 id="modal-titulo">Excluir a conta da ONG?</h3>
            <p>Esta ação é permanente e remove também todos os pets cadastrados. Para confirmar, digite sua senha.</p>

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

    <script>
        // Mostra no select de raça apenas as raças da espécie escolhida
        (function () {
            var especie = document.getElementById('especie_pet');
            var raca = document.getElementById('raca_pet');

            function filtrarRacas() {
                var escolhida = especie.value;
                raca.value = '';

                Array.prototype.forEach.call(raca.options, function (opcao) {
                    if (!opcao.dataset.especie) return;
                    var visivel = opcao.dataset.especie === escolhida;
                    opcao.hidden = !visivel;
                    opcao.disabled = !visivel;
                });
            }

            especie.addEventListener('change', filtrarRacas);
            especie.form.addEventListener('reset', function () {
                setTimeout(filtrarRacas, 0);
            });
            filtrarRacas();
        })();
    </script>
    <script>
        // Área de upload da foto do pet: pré-visualização, arrastar e soltar
        (function () {
            var input = document.getElementById('imagem_pet');
            var zona = document.getElementById('dropzone');
            var vazio = zona.querySelector('.dropzone-empty');
            var previa = zona.querySelector('.dropzone-preview');
            var img = document.getElementById('dropzone-img');
            var nome = document.getElementById('dropzone-nome');
            var tamanho = document.getElementById('dropzone-tamanho');
            var remover = document.getElementById('dropzone-remover');
            var limite = 2 * 1024 * 1024;
            var urlAtual = null;

            function formatar(bytes) {
                return bytes < 1024 * 1024
                    ? Math.round(bytes / 1024) + ' KB'
                    : (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            }

            function limpar() {
                if (urlAtual) URL.revokeObjectURL(urlAtual);
                urlAtual = null;
                input.value = '';
                img.removeAttribute('src');
                previa.hidden = true;
                vazio.hidden = false;
                zona.classList.remove('has-file');
            }

            function mostrar() {
                var arquivo = input.files && input.files[0];
                if (!arquivo) return limpar();

                if (!/^image\/(jpeg|png|webp)$/.test(arquivo.type)) {
                    alert('Envie uma imagem JPG, PNG ou WEBP.');
                    return limpar();
                }

                if (arquivo.size > limite) {
                    alert('A imagem deve ter no máximo 2 MB.');
                    return limpar();
                }

                if (urlAtual) URL.revokeObjectURL(urlAtual);
                urlAtual = URL.createObjectURL(arquivo);
                img.src = urlAtual;
                nome.textContent = arquivo.name;
                tamanho.textContent = formatar(arquivo.size);
                vazio.hidden = true;
                previa.hidden = false;
                zona.classList.add('has-file');
            }

            input.addEventListener('change', mostrar);

            remover.addEventListener('click', function (evento) {
                evento.preventDefault();
                evento.stopPropagation();
                limpar();
            });

            ['dragenter', 'dragover'].forEach(function (nomeEvento) {
                zona.addEventListener(nomeEvento, function (evento) {
                    evento.preventDefault();
                    zona.classList.add('is-dragover');
                });
            });

            ['dragleave', 'drop'].forEach(function (nomeEvento) {
                zona.addEventListener(nomeEvento, function (evento) {
                    evento.preventDefault();
                    zona.classList.remove('is-dragover');
                });
            });

            zona.addEventListener('drop', function (evento) {
                if (evento.dataTransfer.files.length) {
                    input.files = evento.dataTransfer.files;
                    mostrar();
                }
            });

            input.form.addEventListener('reset', limpar);
        })();
    </script>
    <script src="../SCRIPT/account.js"></script>
    <script src="../SCRIPT/modoEscuro.js"></script>
</body>
</html>