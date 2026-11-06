<?php

session_start();

require_once("../conexao.php");

// Mesma verificação da página da conta da ONG
if (empty($_SESSION['email_ong'])) {
    header("Location: login.php");
    exit;
}

// Nome do arquivo da página da conta da ONG (ajuste se for outro)
$paginaConta = "account_ong.php";

// Descobre a ONG logada pelo e-mail da sessão
$stmt = $conn->prepare("SELECT cd_ong, nm_ong FROM ONG WHERE cd_email_ong = ?");
$stmt->bind_param("s", $_SESSION['email_ong']);
$stmt->execute();
$ong = $stmt->get_result()->fetch_assoc();

if (!$ong) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$idOng = (int) $ong['cd_ong'];
$idUsuario = (int) ($_GET['usuario'] ?? 0);
$idPet = (int) ($_GET['pet'] ?? 0);

// Busca o formulário, só se o pet pertencer à ONG logada
$stmt = $conn->prepare(
    "SELECT f.*,
            p.nm_pet,
            p.ds_imagem_pet,
            u.nm_usuario,
            u.cd_email_usuario,
            u.cd_telefone_usuario,
            u.ds_endereco_usuario,
            u.nm_cidade_usuario,
            u.sg_estado_usuario,
            u.dt_nascimento_usuario,
            u.dt_cadastro,
            u.ds_imagem_perfil_usuario
    FROM formulario_adocao f
    JOIN pet p     ON p.cd_pet = f.id_pet_fk
    JOIN usuario u ON u.id_usuario = f.id_usuario_fk
    WHERE f.id_usuario_fk = ?
      AND f.id_pet_fk = ?
      AND p.cd_ong = ?"
);
$stmt->bind_param("iii", $idUsuario, $idPet, $idOng);
$stmt->execute();
$formulario = $stmt->get_result()->fetch_assoc();

if (!$formulario) {
    $_SESSION['aviso'] = ['tipo' => 'erro', 'mensagem' => 'Solicitação não encontrada.', 'secao' => 'solicitacoes'];
    header("Location: " . $paginaConta . "#solicitacoes");
    exit;
}

// Telefone: só os números (usado no WhatsApp) e uma versão formatada para exibir
$telefone = preg_replace('/\D/', '', (string) $formulario['cd_telefone_usuario']);

if (strlen($telefone) === 11) {
    $telefoneExibicao = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
} elseif (strlen($telefone) === 10) {
    $telefoneExibicao = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
} else {
    $telefoneExibicao = $telefone ?: 'Não informado';
}

// Data de nascimento com idade
if (!empty($formulario['dt_nascimento_usuario'])) {
    $idade = floor((time() - strtotime($formulario['dt_nascimento_usuario'])) / 31557600);
    $nascimento = date('d/m/Y', strtotime($formulario['dt_nascimento_usuario'])) . ' (' . $idade . ' anos)';
} else {
    $nascimento = 'Não informado';
}

// Cidade - Estado
$local = implode(' - ', array_filter([$formulario['nm_cidade_usuario'], $formulario['sg_estado_usuario']])) ?: 'Não informado';

$pendente = strtoupper((string) $formulario['cd_status']) === 'PENDENTE';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pendente) {
    $acao = $_POST['acao'] ?? '';
    $resposta = trim($_POST['ds_resposta_ong'] ?? '');

    if ($acao !== 'aprovar' && $acao !== 'recusar') {
        $erro = 'Ação inválida.';
    } elseif ($acao === 'recusar' && $resposta === '') {
        $erro = 'Explique o motivo da recusa para o adotante.';
    } else {
        $novoStatus = $acao === 'aprovar' ? 'APROVADO' : 'RECUSADO';

        // O filtro por PENDENTE evita responder duas vezes à mesma solicitação
        $stmt = $conn->prepare(
            "UPDATE formulario_adocao
            SET cd_status = ?, ds_resposta_ong = ?, dt_resposta = NOW()
            WHERE id_usuario_fk = ? AND id_pet_fk = ? AND UPPER(cd_status) = 'PENDENTE'"
        );
        $stmt->bind_param("ssii", $novoStatus, $resposta, $idUsuario, $idPet);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            $_SESSION['aviso'] = ['tipo' => 'erro', 'mensagem' => 'Esta solicitação já foi respondida.', 'secao' => 'solicitacoes'];
            header("Location: " . $paginaConta . "#solicitacoes");
            exit;
        }

        if ($acao === 'recusar') {
            $_SESSION['aviso'] = ['tipo' => 'sucesso', 'mensagem' => 'Solicitação recusada.', 'secao' => 'solicitacoes'];
            header("Location: " . $paginaConta . "#solicitacoes");
            exit;
        }

        // Aprovada: abre o WhatsApp do adotante com a mensagem pronta
        if ($telefone === '') {
            $_SESSION['aviso'] = ['tipo' => 'erro', 'mensagem' => 'Solicitação aprovada, mas o adotante não tem telefone cadastrado.', 'secao' => 'solicitacoes'];
            header("Location: " . $paginaConta . "#solicitacoes");
            exit;
        }

        if (strlen($telefone) <= 11) {
            $telefone = '55' . $telefone;
        }

        $mensagem = "Olá, {$formulario['nm_usuario']}! Aqui é da {$ong['nm_ong']}. "
                  . "Seu formulário de adoção do(a) {$formulario['nm_pet']} foi aprovado! 🐾";

        if ($resposta !== '') {
            $mensagem .= "\n\n" . $resposta;
        }

        $_SESSION['aviso'] = ['tipo' => 'sucesso', 'mensagem' => 'Solicitação aprovada.', 'secao' => 'solicitacoes'];
        header("Location: https://wa.me/" . $telefone . "?text=" . rawurlencode($mensagem));
        exit;
    }
}

$statusClasse = strtolower((string) $formulario['cd_status']);
$statusTextos = ['pendente' => 'Em análise', 'aprovado' => 'Aprovada', 'recusado' => 'Recusada'];
$statusTexto = $statusTextos[$statusClasse] ?? $formulario['cd_status'];

$fotoUsuario = !empty($formulario['ds_imagem_perfil_usuario']) ? '../' . $formulario['ds_imagem_perfil_usuario'] : null;
$fotoPet = !empty($formulario['ds_imagem_pet']) ? '../' . $formulario['ds_imagem_pet'] : null;
$dataEnvio = date('d/m/Y \à\s H:i', strtotime($formulario['dt_envio']));

// [ícone, rótulo, valor]
$dadosUsuario = [
    ['bi bi-envelope',      'E-mail',          $formulario['cd_email_usuario']],
    ['bi bi-telephone',     'Telefone',        $telefoneExibicao],
    ['bi bi-calendar-heart', 'Nascimento',     $nascimento],
    ['bi bi-buildings',     'Cidade / Estado', $local],
    ['bi bi-geo-alt',       'Endereço',        $formulario['ds_endereco_usuario'] ?: 'Não informado'],
    ['bi bi-calendar-check', 'Membro desde',   date('d/m/Y', strtotime($formulario['dt_cadastro']))],
];

$respostasFormulario = [
    ['bi bi-heart',           'Já teve pet?',                   $formulario['ic_teve_pet_form'] ? 'Sim' : 'Não'],
    ['fa-solid fa-paw',       'Possui outros animais?',         $formulario['ic_outros_animais_form'] ? 'Sim' : 'Não'],
    ['bi bi-hourglass-split', 'Horas por dia sozinho',          (int) $formulario['qt_horas_sozinho'] . ' h'],
    ['bi bi-house-door',      'Tipo de moradia',                $formulario['nm_tipo_moradia']],
    ['bi bi-shield-check',    'Moradia com telas de proteção?', $formulario['ic_protecao'] ? 'Sim' : 'Não'],
    ['bi bi-chat-heart',      'Motivação para adotar',          $formulario['ds_motivacao']],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adoção de <?php echo htmlspecialchars($formulario['nm_pet']); ?></title>
    <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/account_ong.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <div class="topbar-content">
            <a href="<?php echo htmlspecialchars($paginaConta); ?>#solicitacoes" class="back-link">
                <i class="bi bi-arrow-left"></i>
                <span>Voltar às solicitações</span>
            </a>
            <span class="topbar-title">Solicitação de adoção</span>
        </div>
    </header>

    <main class="account" style="max-width: 800px;">
        <?php if ($erro !== '') { ?>
            <div class="alert alert-erro" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo htmlspecialchars($erro); ?></span>
            </div>
        <?php } ?>

        <!-- Card 1: dados do adotante -->
        <section class="card">
            <header class="card-header" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div class="solicitation-photo">
                    <?php if ($fotoUsuario) { ?>
                        <img src="<?php echo htmlspecialchars($fotoUsuario); ?>" alt="Foto de <?php echo htmlspecialchars($formulario['nm_usuario']); ?>">
                    <?php } else { ?>
                        <i class="bi bi-person-fill"></i>
                    <?php } ?>
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <h2><?php echo htmlspecialchars($formulario['nm_usuario']); ?></h2>
                    <p>Dados do adotante</p>
                </div>
            </header>

            <ul class="preference-list">
                <?php foreach ($dadosUsuario as [$icone, $rotulo, $valor]) { ?>
                    <li class="preference">
                        <div class="preference-icon"><i class="<?php echo htmlspecialchars($icone); ?>"></i></div>
                        <div class="preference-text">
                            <span><?php echo htmlspecialchars($rotulo); ?></span>
                            <strong><?php echo htmlspecialchars((string) $valor); ?></strong>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </section>

        <!-- Card 2: formulário preenchido -->
        <section class="card">
            <header class="card-header" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                <div class="solicitation-photo">
                    <?php if ($fotoPet) { ?>
                        <img src="<?php echo htmlspecialchars($fotoPet); ?>" alt="Foto de <?php echo htmlspecialchars($formulario['nm_pet']); ?>">
                    <?php } else { ?>
                        <i class="fa-solid fa-paw"></i>
                    <?php } ?>
                </div>

                <div style="flex: 1; min-width: 200px;">
                    <h2>Solicitação para <?php echo htmlspecialchars($formulario['nm_pet']); ?></h2>
                    <p>Formulário enviado em <?php echo htmlspecialchars($dataEnvio); ?>.</p>
                </div>

                <span class="solicitation-status status-<?php echo htmlspecialchars($statusClasse); ?>">
                    <?php echo htmlspecialchars($statusTexto); ?>
                </span>
            </header>

            <ul class="preference-list">
                <?php foreach ($respostasFormulario as [$icone, $pergunta, $resposta]) { ?>
                    <li class="preference">
                        <div class="preference-icon"><i class="<?php echo htmlspecialchars($icone); ?>"></i></div>
                        <div class="preference-text">
                            <span><?php echo htmlspecialchars($pergunta); ?></span>
                            <strong style="white-space: pre-line;"><?php echo htmlspecialchars((string) $resposta); ?></strong>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </section>

        <!-- Card 3: resposta da ONG -->
        <section class="card">
            <?php if ($pendente) { ?>
                <header class="card-header">
                    <h2>Sua resposta</h2>
                    <p>Se aprovar, o WhatsApp do adotante abre com a mensagem pronta para enviar.</p>
                </header>

                <form action="" method="POST" class="form-grid">
                    <div class="field field-full">
                        <label for="ds_resposta_ong">Mensagem para o adotante</label>
                        <textarea id="ds_resposta_ong" name="ds_resposta_ong" placeholder="Ex.: Podemos combinar uma visita na sexta-feira?"><?php echo htmlspecialchars($_POST['ds_resposta_ong'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions field-full">
                        <button type="submit" name="acao" value="recusar" class="button button-danger-ghost">
                            <i class="bi bi-x-lg"></i> Recusar
                        </button>
                        <button type="submit" name="acao" value="aprovar" class="button button-primary">
                            <i class="bi bi-whatsapp"></i> Aprovar e chamar no WhatsApp
                        </button>
                    </div>
                </form>
            <?php } else { ?>
                <header class="card-header">
                    <h2>Resposta da ONG</h2>
                    <?php if (!empty($formulario['dt_resposta'])) { ?>
                        <p>Respondido em <?php echo htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($formulario['dt_resposta']))); ?>.</p>
                    <?php } ?>
                </header>

                <ul class="preference-list">
                    <li class="preference">
                        <div class="preference-icon"><i class="bi bi-chat-left-text"></i></div>
                        <div class="preference-text">
                            <span>Mensagem enviada ao adotante</span>
                            <strong style="white-space: pre-line;"><?php echo htmlspecialchars($formulario['ds_resposta_ong'] ?: 'Sem mensagem.'); ?></strong>
                        </div>
                    </li>
                </ul>
            <?php } ?>
        </section>
    </main>

    <script src="../SCRIPT/modoEscuro.js"></script>
</body>
</html>