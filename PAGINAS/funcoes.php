<?php
function email_em_uso(mysqli $conn, string $email): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM USUARIO WHERE cd_email_usuario = ?
         UNION
         SELECT 1 FROM ONG WHERE cd_email_ong = ?
         LIMIT 1"
    );
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

function telefone_em_uso(mysqli $conn, string $telefone): bool {
    $stmt = $conn->prepare(
        "SELECT 1 FROM USUARIO WHERE cd_telefone_usuario = ?
         UNION
         SELECT 1 FROM ONG WHERE cd_telefone_ong = ?
         LIMIT 1"
    );
    $stmt->bind_param("ss", $telefone, $telefone);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}