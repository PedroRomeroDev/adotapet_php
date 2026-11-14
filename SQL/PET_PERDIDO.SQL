CREATE TABLE IF NOT EXISTS pet_perdido (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario_fk INT NOT NULL,
    nome VARCHAR(80) NOT NULL,
    especie VARCHAR(30) NOT NULL,
    descricao TEXT NOT NULL,
    localizacao VARCHAR(200) NOT NULL,
    data_desaparecimento DATE NOT NULL,
    contato VARCHAR(20) NOT NULL,
    imagem VARCHAR(255) NOT NULL,
    encontrado TINYINT(1) NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_perdido_usuario FOREIGN KEY (id_usuario_fk) REFERENCES usuario(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
