<?php

    require_once("../conexao.php");

    session_start();

    $email = $_SESSION['email_usuario'];
    
    // Comando SQL
    $SQL = "DELETE FROM usuario WHERE cd_email_usuario = ?";

    $stmt = $conn->prepare($SQL);

    $stmt->bind_param("s", $email);

    $stmt->execute();

    header("Location: logout.php");

    

?>