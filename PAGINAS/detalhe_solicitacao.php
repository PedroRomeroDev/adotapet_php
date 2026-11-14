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

// Link do WhatsApp (botão independente, não altera nada no banco)
$linkWhatsapp = null;
if ($telefone !== '') {
    $telefoneWa = strlen($telefone) <= 11 ? '55' . $telefone : $telefone;
    $textoWa = "Olá, {$formulario['nm_usuario']}! Aqui é da {$ong['nm_ong']}. "
             . "Estamos entrando em contato sobre sua solicitação de adoção do(a) {$formulario['nm_pet']}. 🐾";
    $linkWhatsapp = "https://wa.me/" . $telefoneWa . "?text=" . rawurlencode($textoWa);
}

$statusPermitidos = ['PENDENTE', 'APROVADO', 'RECUSADO'];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoStatus = strtoupper(trim($_POST['cd_status'] ?? ''));
    $resposta = trim($_POST['ds_resposta_ong'] ?? '');

    if (!in_array($novoStatus, $statusPermitidos, true)) {
        $erro = 'Status inválido.';
    } else {
        $respostaBanco = $resposta !== '' ? $resposta : null; // mensagem é opcional

        // Sem filtro por PENDENTE: a ONG pode mudar o status quantas vezes quiser.
        // Voltando para PENDENTE, a data de resposta é limpa.
        $stmt = $conn->prepare(
            "UPDATE formulario_adocao
            SET cd_status = ?,
                ds_resposta_ong = ?,
                dt_resposta = CASE WHEN ? = 'PENDENTE' THEN NULL ELSE NOW() END
            WHERE id_usuario_fk = ? AND id_pet_fk = ?"
        );
        $stmt->bind_param("sssii", $novoStatus, $respostaBanco, $novoStatus, $idUsuario, $idPet);
        $stmt->execute();

        $mensagensAviso = [
            'PENDENTE' => 'Solicitação voltou para análise.',
            'APROVADO' => 'Solicitação aprovada.',
            'RECUSADO' => 'Solicitação recusada.',
        ];

        $_SESSION['aviso'] = ['tipo' => 'sucesso', 'mensagem' => $mensagensAviso[$novoStatus], 'secao' => 'solicitacoes'];
        header("Location: " . $paginaConta . "#solicitacoes");
        exit;
    }
}

$statusClasse = strtolower((string) $formulario['cd_status']);
$statusTextos = ['pendente' => 'Em análise', 'aprovado' => 'Aprovada', 'recusado' => 'Recusada'];
$statusTexto = $statusTextos[$statusClasse] ?? $formulario['cd_status'];

// Valor selecionado no <select> (mantém o que foi enviado se houve erro)
$statusAtual = strtoupper((string) ($_POST['cd_status'] ?? $formulario['cd_status']));
$opcoesStatus = ['PENDENTE' => 'Em análise', 'APROVADO' => 'Aprovada', 'RECUSADO' => 'Recusada'];

$fotoUsuario = !empty($formulario['ds_imagem_perfil_usuario']) ? '../' . $formulario['ds_imagem_perfil_usuario'] : null;
$fotoPet = !empty($formulario['ds_imagem_pet']) ? '../' . $formulario['ds_imagem_pet'] : null;
$dataEnvio = date('d/m/Y \à\s H:i', strtotime($formulario['dt_envio']));

// [ícone, rótulo, valor]
$dadosUsuario = [
    ['bi bi-envelope',       'E-mail',          $formulario['cd_email_usuario']],
    ['bi bi-telephone',      'Telefone',        $telefoneExibicao],
    ['bi bi-calendar-heart', 'Nascimento',      $nascimento],
    ['bi bi-buildings',      'Cidade / Estado', $local],
    ['bi bi-geo-alt',        'Endereço',        $formulario['ds_endereco_usuario'] ?: 'Não informado'],
    ['bi bi-calendar-check', 'Membro desde',    date('d/m/Y', strtotime($formulario['dt_cadastro']))],
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
    <link rel="stylesheet" href="../CSS/solicitacao_ong.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../CSS/navbar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <script src="../SCRIPT/preferencias.js"></script>
</head>
<body>
    <?php require_once __DIR__ . '/../navbar.php'; ?>

    <main class="solicitacao">
        <a href="<?php echo htmlspecialchars($paginaConta); ?>#solicitacoes" class="back-link">
            <i class="bi bi-arrow-left"></i>
            <span>Voltar às solicitações</span>
        </a>

        <?php if ($erro !== '') { ?>
            <div class="alert alert-erro" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span><?php echo htmlspecialchars($erro); ?></span>
            </div>
        <?php } ?>

        <!-- Card 1: dados do adotante -->
        <section class="card">
            <header class="card-header card-header-row">
                <div class="solicitation-photo">
                    <?php if ($fotoUsuario) { ?>
                        <img src="<?php echo htmlspecialchars($fotoUsuario); ?>" alt="Foto de <?php echo htmlspecialchars($formulario['nm_usuario']); ?>">
                    <?php } else { ?>
                        <i class="bi bi-person-fill"></i>
                    <?php } ?>
                </div>

                <div class="card-header-info">
                    <h2><?php echo htmlspecialchars($formulario['nm_usuario']); ?></h2>
                    <p>Dados do adotante</p>
                </div>

                <?php if ($linkWhatsapp) { ?>
                    <a href="<?php echo htmlspecialchars($linkWhatsapp); ?>" target="_blank" rel="noopener" class="button button-primary">
                        <i class="bi bi-whatsapp"></i> Chamar no WhatsApp
                    </a>
                <?php } else { ?>
                    <span class="button button-primary button-disabled" title="Adotante sem telefone cadastrado" aria-disabled="true">
                        <i class="bi bi-whatsapp"></i> Sem telefone
                    </span>
                <?php } ?>
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
            <header class="card-header card-header-row">
                <div class="solicitation-photo">
                    <?php if ($fotoPet) { ?>
                        <img src="<?php echo htmlspecialchars($fotoPet); ?>" alt="Foto de <?php echo htmlspecialchars($formulario['nm_pet']); ?>">
                    <?php } else { ?>
                        <i class="fa-solid fa-paw"></i>
                    <?php } ?>
                </div>

                <div class="card-header-info">
                    <h2>Solicitação para <?php echo htmlspecialchars($formulario['nm_pet']); ?></h2>
                    <p>Formulário enviado em <?php echo htmlspecialchars($dataEnvio); ?>.</p>
                </div>

                <span class="solicitation-status status-<?php echo htmlspecialchars($statusClasse); ?>">
                    <?php echo htmlspecialchars($statusTexto); ?>
                </span>
            </header>

            <ul class="preference-list">
                <?php foreach ($respostasFormulario as [$icone, $pergunta, $valor]) { ?>
                    <li class="preference">
                        <div class="preference-icon"><i class="<?php echo htmlspecialchars($icone); ?>"></i></div>
                        <div class="preference-text">
                            <span><?php echo htmlspecialchars($pergunta); ?></span>
                            <strong class="preserve-lines"><?php echo htmlspecialchars((string) $valor); ?></strong>
                        </div>
                    </li>
                <?php } ?>
            </ul>
        </section>

        <!-- Card 3: atendimento -->
        <section class="card">
            <header class="card-header">
                <h2>Atendimento</h2>
                <p>
                    Altere o status quando quiser. A mensagem é opcional.
                    <?php if (!empty($formulario['dt_resposta'])) { ?>
                        Última resposta em <?php echo htmlspecialchars(date('d/m/Y \à\s H:i', strtotime($formulario['dt_resposta']))); ?>.
                    <?php } ?>
                </p>
            </header>

            <form action="" method="POST" class="form-grid">
                <div class="field field-full">
                    <label for="cd_status">Status</label>
                    <select id="cd_status" name="cd_status">
                        <?php foreach ($opcoesStatus as $valor => $texto) { ?>
                            <option value="<?php echo $valor; ?>" <?php echo $statusAtual === $valor ? 'selected' : ''; ?>>
                                <?php echo $texto; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <div class="field field-full">
                    <label for="ds_resposta_ong">Mensagem para o adotante (opcional)</label>
                    <textarea id="ds_resposta_ong" name="ds_resposta_ong" placeholder="Ex.: Podemos combinar uma visita na sexta-feira?"><?php
                        echo htmlspecialchars($_POST['ds_resposta_ong'] ?? ($formulario['ds_resposta_ong'] ?? ''));
                    ?></textarea>
                </div>

                <div class="form-actions field-full">
                    <button type="submit" class="button button-primary">
                        <i class="bi bi-check-lg"></i> Salvar
                    </button>
                </div>
            </form>
        </section>
    </main>

    <script src="../SCRIPT/modoEscuro.js"></script>
</body>
</html>