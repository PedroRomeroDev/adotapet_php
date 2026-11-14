<?php
require_once __DIR__ . '/admin_guard.php';

function voltar($msg, $erro = false) {
    header('Location: admin.php?msg=' . urlencode($msg) . ($erro ? '&erro=1' : '') . '#racas-especies');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) voltar('Raça inválida.', true);

$stmt = $conn->prepare("SELECT COUNT(*) AS t FROM PET WHERE cd_raca_pet = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
if ((int) $stmt->get_result()->fetch_assoc()['t'] > 0) voltar('Não é possível excluir: há pets cadastrados com essa raça.', true);

$stmt = $conn->prepare("DELETE FROM RACA WHERE cd_raca = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
voltar('Raça excluída.');