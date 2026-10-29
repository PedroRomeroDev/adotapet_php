<?php

    require_once("../conexao.php");
    session_start();

    if($_GET['id']){
        $SQL = "DELETE FROM usuario WHERE id_usuario = ?";
        $stmt = $conn->prepare($SQL);
        $stmt->bind_param("s", $_GET['id']);
        $stmt->execute();
        header("Location: adotapet_admin.php");
        return;
    }

    $email = $_SESSION['email_usuario'];
    
    // Comando SQL
    $SQL = "DELETE FROM usuario WHERE cd_email_usuario = ?";
    $stmt = $conn->prepare($SQL);
    $stmt->bind_param("s", $email);
    $stmt->execute();

    header("Location: logout.php");

    

?>