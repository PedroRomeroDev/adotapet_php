<?php 
session_start();
require_once('../conexao.php');

if (!isset($_SESSION['tipo_conta']) || $_SESSION['tipo_conta'] !== 'usuario') {
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM USUARIO WHERE cd_email_usuario = ?");
$stmt->bind_param("s", $_SESSION['email_usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc() ?: [];

$animal_id = (int)($_GET['cd_pet'] ?? 0);

$SQL = "SELECT * FROM PET WHERE cd_pet = " . $animal_id;
$stmt = $conn->prepare($SQL);
$stmt->execute();
$resultado = $stmt->get_result();
$pet = $resultado->fetch_assoc();

$stmt = $conn->prepare("SELECT 1 FROM formulario_adocao WHERE id_usuario_fk = ? AND id_pet_fk = ?");
$stmt->bind_param("ii", $usuario['id_usuario'], $animal_id);
$stmt->execute();
$stmt->store_result();
$ja_enviou = $stmt->num_rows > 0;
$stmt->close();

// Helper: escapa o valor e evita erro quando o campo vier vazio
function e($v) {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

// Data máxima de nascimento (precisa ter 18 anos ou mais)
$max_nascimento = date('Y-m-d', strtotime('-18 years'));
?>

<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title> AdotaPet - Formulário </title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../CSS/navbar.css">
        <link rel="stylesheet" href="../CSS/formulario_adocao.css">
        <link rel="stylesheet" href="../CSS/variaveis.css">
        <link rel="shortcut icon" href="../assets/all_images/branco vazado.png">
    </head>
    <body>
        <?php require_once __DIR__ . '/../navbar.php'; ?>
        <section class="adoption-form-container">
            <h2>Formulário de Adoção</h2>
            <p class="subtitle">Você esta solicitando a adoção de: <strong> <?php echo $pet['nm_pet']; ?> </strong></p>
            <?php if($ja_enviou): ?>
                <div class="form-feedback">
                    <p>Você já enviou uma solicitação de adoção para este pet.</p>
                </div>
            <?php endif ?>

            <?php if (!$ja_enviou): ?>
                <form action="processa_adocao.php" method="POST" class="adoption-form">
                <input type="hidden" name="animal_id" value="<?php echo $animal_id; ?>">

                <div class="form-column">
                    <div class="form-section">
                        <h3 class="section-title">Dados do tutor</h3>
                        <div class="input-group">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome-tutor"
                                   value="<?php echo e($usuario['nm_usuario'] ?? ''); ?>"
                                   minlength="3" maxlength="120" autocomplete="name" required readonly>
                        </div>

                        <div class="input-group">
                            <label for="data-nascimento">Data de nascimento</label>
                            <input type="date" id="data-nascimento" name="data-nascimento"
                                   value="<?php echo e($usuario['dt_nascimento_usuario'] ?? ''); ?>"
                                   max="<?php echo $max_nascimento; ?>" required>
                        </div>
                        
                        <div class="input-group">
                            <label for="cpf">CPF</label>
                            <input type="text" id="cpf" name="cpf"
                                   data-mask="cpf" placeholder="000.000.000-00" required>
                        </div>

                        <div class="input-group">
                            <label for="rg">RG</label>
                            <input type="text" id="rg" name="rg"
                                   data-mask="rg" placeholder="00.000.000-0">
                        </div>

                        <div class="input-group">
                            <label for="telefone">Numero Telefone</label>
                            <input type="tel" id="telefone" name="telefone"
                                   value="<?php echo e($usuario['cd_telefone_usuario'] ?? ''); ?>"
                                   data-mask="telefone" placeholder="(00) 00000-0000" required readonly>
                        </div>

                        <div class="input-group">
                            <label for="estado">Estado</label>
                            <input type="text" id="estado" name="estado"
                                   value="<?php echo e($usuario['sg_estado_usuario'] ?? ''); ?>"
                                   maxlength="2" pattern="[A-Za-z]{2}" placeholder="UF"
                                   autocomplete="address-level1" required>
                        </div>

                        <div class="input-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade"
                                   value="<?php echo e($usuario['nm_cidade_usuario'] ?? ''); ?>"
                                   maxlength="100" autocomplete="address-level2" required>
                        </div>

                        <div class="input-group">
                            <label for="endereco">Endereço</label>
                            <input type="text" id="endereco" name="endereco"
                                   value="<?php echo e($usuario['ds_endereco_usuario'] ?? ''); ?>"
                                   maxlength="150" autocomplete="street-address" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3 class="section-title">Sobre a casa</h3>
                        
                        <div class="input-group">
                        <label for="moradia">Moradia</label>
                        <select id="moradia" name="moradia" required>
                            <option value="" disabled selected>Selecione</option>
                            <option value="casa">Casa</option>
                            <option value="apartamento">Apartamento</option>
                        </select>
                        </div>
                        
                        <div class="input-group">
                        <label for="seguranca">É telado ou Murado?</label>
                        <select id="seguranca" name="seguranca" required>
                            <option value="" disabled selected>Selecione</option>
                            <option value="sim">Sim</option>
                            <option value="nao">Não</option>
                        </select>
                        </div>
                    </div>

                </div>

                <div class="form-column">
                
                <div class="form-section">
                    <h3 class="section-title">Experiência</h3>
                    
                    <div class="input-group">
                    <label for="teve-pets">Já teve PET'S?</label>
                    <select id="teve-pets" name="teve-pets" required>
                        <option value="" disabled selected>Selecione</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                    </select>
                    </div>
                    
                    <div class="input-group">
                    <label for="outros-animais">Atualmente tem outros animais?</label>
                    <select id="outros-animais" name="outros-animais" required>
                        <option value="" disabled selected>Selecione</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                    </select>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Rotina</h3>
                    
                    <div class="input-group">
                    <label for="tempo-sozinho">Quanto tempo o pet ficará sozinho?</label>
                    <input type="text" id="tempo-sozinho" name="tempo-sozinho"
                           maxlength="60" placeholder="Ex.: 4 horas por dia" required>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="section-title">Intenção</h3>
                    
                    <div class="input-group">
                    <label for="intencao">Porque adotar esse pet em específico?</label>
                    <textarea id="intencao" name="intencao" rows="3" minlength="10" maxlength="1000" required></textarea>
                    </div>
                </div>

                </div>

                <div class="form-submit">
                    <input type="submit" value="Enviar" <?= $ja_enviou ? 'disabled' : '' ?>>
                </div>
            </form>
            <?php endif ?>
        </section>
        <script src="../SCRIPT/mascaras.js"></script>
        <script src="../SCRIPT/headerScript.js"></script>
    </body>
</html>