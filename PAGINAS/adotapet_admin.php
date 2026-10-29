<?php

// Usuarios
require_once("../conexao.php");

$SQL = "SELECT * FROM USUARIO";
$stmt = $conn->prepare($SQL);

$stmt->execute();
$usuarios = $stmt->get_result();

?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdotaPet - Painel Administrador</title>
    <link rel="stylesheet" href="../CSS/variaveis.css">
    <link rel="stylesheet" href="../CSS/admin.css">
</head>
<body>
    <div class="container">
        <aside>
            <div class="header-aside">
                <img src="../assets/all_images/preto vazado.png" alt="AdotaPet">
            </div>
            <ul class="aside-list">
                <li data-content="usuarios">Usuários</li>
                <li data-content="usuarios-banidos">Usuários banidos</li>
                <li data-content="ongs-aprovadas">ONGs aprovadas</li>
                <li data-content="ongs-esperando">ONGs esperando</li>
            </ul>
        </aside>

        <section class="content" id="usuarios">
            <div class="content-header">
                <h1>Usuários</h1>
                <p>Pessoas cadastradas na plataforma.</p>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Telefone</th>
                            <th>Cidade</th>
                            <th>Endereço</th>
                            <th>Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!$usuarios->num_rows > 0): ?>
                            <tr class="empty-state"><td colspan="7">Nenhum usuário encontrado.</td></tr>
                        <?php endif; ?>

                        <?php if($usuarios->num_rows > 0): ?>
                        <?php foreach($usuarios as $user): ?>
                        <tr>
                            <td><?php echo $user['nm_usuario']; ?></td>
                            <td><?php echo $user['cd_email_usuario']; ?></td>
                            <td><?php echo $user['cd_telefone_usuario']; ?></td>
                            <td><?php echo $user['nm_cidade_usuario']; ?></td>
                            <td><?php echo $user['ds_endereco_usuario']; ?></td>
                            <td><?php echo date( "d/m" ,strtotime($user['dt_cadastro'])); ?></td>
                            <td><div class="actions">
                                <a href="excluir_conta.php?id=<?php echo $user['id_usuario'] ?>"><button class="btn-excluir"><i class="bi bi-trash"></i></button></a>
                                <a href="banir_conta.php?id=<?php echo $user['id_usuario'] ?>"><button class="btn-banir"><i class="bi bi-ban"></i></button></a>
                            </div></td>
                        </tr>
                        <?php endforeach;?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content" id="usuarios-banidos">
            <div class="content-header">
                <h1>Usuários banidos</h1>
                <p>Contas com acesso bloqueado.</p>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Banido em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="empty-state"><td colspan="4">Nenhum usuário banido.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content" id="ongs-aprovadas">
            <div class="content-header">
                <h1>ONGs aprovadas</h1>
                <p>Organizações liberadas para publicar animais.</p>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Cidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="empty-state"><td colspan="4">Nenhuma ONG aprovada.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="content" id="ongs-esperando">
            <div class="content-header">
                <h1>ONGs esperando</h1>
                <p>Cadastros aguardando a sua análise.</p>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CNPJ</th>
                            <th>Cidade</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Exemplo de linha:
                        <tr>
                            <td>ONG Patas Felizes</td>
                            <td>00.000.000/0001-00</td>
                            <td>Praia Grande</td>
                            <td>
                                <button class="btn btn-primary">Aprovar</button>
                                <button class="btn btn-outline">Recusar</button>
                            </td>
                        </tr>
                        -->
                        <tr class="empty-state"><td colspan="4">Nenhuma ONG esperando aprovação.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <script src="../SCRIPT/administrator.js"></script>
</body>
</html>