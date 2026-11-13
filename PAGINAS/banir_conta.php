<?php
    require_once("../conexao.php");
    $idUsuario = (int) $_GET['id'];

    $conn->begin_transaction();

    try {
        // 2. Apaga o usuário
        $stmt = $conn->prepare("UPDATE USUARIO SET ic_banido_usuario = 1 WHERE id_usuario = ?");
        $stmt->bind_param("i", $idUsuario);
        $stmt->execute();

        $conn->commit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        die("Erro ao excluir usuário: " . $e->getMessage());
    }

    header("Location: adotapet_admin.php");
    exit;
?>