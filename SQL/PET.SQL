CREATE TABLE PET (
    cd_pet INT AUTO_INCREMENT PRIMARY KEY,
    nm_pet VARCHAR(200) NOT NULL,
    ds_idade_meses_pet INT,
    ds_sexo_pet ENUM('Macho', 'Fêmea') NOT NULL,
    cd_especie_pet INT,
    cd_raca_pet INT,
    cd_ong INT NOT NULL,

    FOREIGN KEY (cd_especie_pet)
        REFERENCES ESPECIE(cd_especie),

    FOREIGN KEY (cd_raca_pet)
        REFERENCES RACA(cd_raca),

    FOREIGN KEY (cd_ong)
        REFERENCES ONG(cd_ong),

    CHECK (ds_idade_meses_pet >= 0)
);