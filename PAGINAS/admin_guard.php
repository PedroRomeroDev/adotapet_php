<?php
session_start();

if (($_SESSION['tipo_conta'] ?? '') !== 'admin') {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../conexao.php';
