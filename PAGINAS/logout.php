<?php
session_start();
$tipoConta = $_SESSION['tipo_conta'] ?? '';
session_unset();
session_destroy();

header($tipoConta === 'admin' ? 'Location: login.php' : 'Location: ../index.php');
exit;
?>
