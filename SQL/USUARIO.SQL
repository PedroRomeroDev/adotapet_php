CREATE TABLE USUARIO (
    id_usuario            INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    nm_usuario            VARCHAR(200) NOT NULL,
    cd_email_usuario      VARCHAR(200) NOT NULL UNIQUE,
    cd_telefone_usuario   VARCHAR(13),
    cd_senha_usuario      VARCHAR(50),
    ds_endereco_usuario   VARCHAR(200),
    nm_cidade_usuario     VARCHAR(100),
    sg_estado_usuario     CHAR(2),
    dt_nascimento_usuario DATE,
    dt_cadastro           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);