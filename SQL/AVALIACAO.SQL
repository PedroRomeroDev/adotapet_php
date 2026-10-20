CREATE TABLE AVALIACAO (
	id_avaliacao int not null auto_increment unique,
    ds_comentario_avaliacao varchar (100) not null,
    dt_avaliacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_usuario_fk int not null unique,
    CONSTRAINT fk_avaliacao_usuario
    FOREIGN KEY (id_usuario_fk)
    REFERENCES usuario(id_usuario)
    ON DELETE CASCADE
);