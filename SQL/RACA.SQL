CREATE TABLE RACA (
cd_raca INT AUTO_INCREMENT PRIMARY KEY,
nm_raca VARCHAR (100) NOT NULL,
cd_especie INT REFERENCES especie(cd_especie),

unique(nm_raca, cd_especie)
);