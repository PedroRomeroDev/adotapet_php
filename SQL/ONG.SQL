CREATE TABLE ONG (
	cd_ong INT AUTO_INCREMENT PRIMARY KEY,
    cd_cnpj_ong CHAR(14) UNIQUE NOT NULL,
	nm_ong VARCHAR(200) NOT NULL,
    nm_responsavel_ong VARCHAR(200),
    cd_telefone_ong VARCHAR(20),
    cd_email_ong VARCHAR(200) NOT NULL,
    ds_endereco_ong VARCHAR(200) NOT NULL,
    nm_cidade_ong VARCHAR(100) NOT NULL, 
    sg_estado_ong CHAR(2) NOT NULL,
    dt_aprovacao_ong DATE,
    ic_ativo_ong boolean DEFAULT FALSE
);