<?php

    require_once("../conexao.php");

    if($_GET['id']){
        $SQL = "DELETE FROM ONG WHERE cd_ong = ?";
        $stmt = $conn->prepare($SQL);
        $stmt->bind_param("i", $_GET['id']);
        $stmt->execute();
        header("Location: adotapet_admin.php");
        return;
    }
    header("Location: logout.php");

    

?>