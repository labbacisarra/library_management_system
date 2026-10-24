-- À exécuter UNE SEULE FOIS dans phpMyAdmin (onglet SQL) sur la base "bibliotheque".
CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type VARCHAR(40) NOT NULL,
  fichier VARCHAR(100) NOT NULL,
  nom_original VARCHAR(255) NOT NULL,
  mime VARCHAR(60) NOT NULL,
  date_ajout DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
