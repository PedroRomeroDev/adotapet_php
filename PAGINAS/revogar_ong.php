<?php
    require_once("../conexao.php");

    $id = $_GET['id'];
    $SQL = "UPDATE ONG SET ic_ativo_ong = 0, dt_aprovacao_ong = null WHERE cd_ong = ?";
    $stmt = $conn->prepare($SQL);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
?>