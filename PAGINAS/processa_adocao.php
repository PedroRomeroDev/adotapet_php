<?php
session_start();
require_once("../conexao.php");

if (empty($_SESSION['email_usuario'])) {
    header("Location: login.php");
    exit;
}

$stmt = $conn->prepare("SELECT id_usuario FROM USUARIO WHERE cd_email_usuario = ?");
$stmt->bind_param("s", $_SESSION['email_usuario']);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $id_usuario     = (int)$usuario['id_usuario'];
    $id_pet         = (int)($_POST['animal_id'] ?? 0);

    $teve_pets      = (($_POST['teve-pets'] ?? '') === 'sim') ? 1 : 0;
    $outros_animais = (($_POST['outros-animais'] ?? '') === 'sim') ? 1 : 0;
    $protecao       = (($_POST['seguranca'] ?? '') === 'sim') ? 1 : 0;

    $horas_sozinho  = (int)preg_replace('/\D/', '', $_POST['tempo-sozinho'] ?? '');

    $moradia        = trim($_POST['moradia'] ?? '');
    $motivacao      = trim($_POST['intencao'] ?? '');

    $SQL = "INSERT INTO formulario_adocao
            (id_usuario_fk, id_pet_fk, ic_teve_pet_form, ic_outros_animais_form,
             qt_horas_sozinho, nm_tipo_moradia, ic_protecao, ds_motivacao)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($SQL);
    $stmt->bind_param("iiiiisis",
        $id_usuario, $id_pet, $teve_pets, $outros_animais,
        $horas_sozinho, $moradia, $protecao, $motivacao
    );

    if ($stmt->execute()) {
        header("Location: area_adocao.php");
        exit;
    } else {
        echo "Erro ao enviar: " . $stmt->error;
    }
}
?>