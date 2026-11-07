CREATE TABLE IF NOT EXISTS BLOG_POST (
    id_post INT NOT NULL AUTO_INCREMENT,
    nm_titulo VARCHAR(180) NOT NULL,
    ds_resumo VARCHAR(320) NOT NULL,
    ds_conteudo MEDIUMTEXT NOT NULL,
    ds_imagem_capa VARCHAR(255) NULL,
    ic_publicado TINYINT(1) NOT NULL DEFAULT 0,
    dt_criacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dt_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_post),
    INDEX idx_blog_publicado_data (ic_publicado, dt_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
