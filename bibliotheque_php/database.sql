-- IMPORTANT : si vous aviez déjà importé l'ancienne version, supprimez d'abord
-- la base "bibliotheque" dans phpMyAdmin, puis importez ce fichier.
-- (Sur un hébergement : supprimez les 2 lignes CREATE DATABASE et USE.)

CREATE DATABASE IF NOT EXISTS bibliotheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bibliotheque;

CREATE TABLE IF NOT EXISTS livres (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titre VARCHAR(200) NOT NULL,
  auteur VARCHAR(150) NOT NULL,
  isbn VARCHAR(30) DEFAULT NULL,
  categorie VARCHAR(100) DEFAULT NULL,
  quantite INT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS adherents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) DEFAULT NULL,
  telephone VARCHAR(30) DEFAULT NULL,
  date_inscription DATE NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS emprunts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  livre_id INT NOT NULL,
  adherent_id INT NOT NULL,
  date_emprunt DATE NOT NULL,
  date_retour_prevue DATE NOT NULL,
  date_retour_effective DATE DEFAULT NULL,
  FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE RESTRICT,
  FOREIGN KEY (adherent_id) REFERENCES adherents(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Comptes de connexion (admin = bibliothécaire, user = adhérent)
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  adherent_id INT DEFAULT NULL,
  FOREIGN KEY (adherent_id) REFERENCES adherents(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------- Données de démonstration ----------------
INSERT INTO livres (titre, auteur, isbn, categorie, quantite) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupéry', '9782070612758', 'Roman', 3),
('L/'Étranger', 'Albert Camus', '9782070360024', 'Roman', 2),
('Clean Code', 'Robert C. Martin', '9780132350884', 'Informatique', 2),
('Les Misérables', 'Victor Hugo', '9782253096344', 'Classique', 1);

INSERT INTO adherents (nom, prenom, email, telephone, date_inscription) VALUES
('Benali', 'Sarra', 'sarra@example.com', '0555000001', CURDATE()),
('Haddad', 'Karim', 'karim@example.com', '0555000002', CURDATE());

-- Comptes de test :
--   admin  : admin@biblio.com  / admin123
--   user   : sarra@example.com / user123
--   user   : karim@example.com / user123
INSERT INTO users (email, password, role, adherent_id) VALUES
('admin@biblio.com',  '$2y$10$7gqb6pRs4U2XTUhnQsYRBeg.R1la7ZgIvOXDIUnE1pvyqxOBO3nX6', 'admin', NULL),
('sarra@example.com', '$2y$10$dXokUd/D45kIu2ER24fJ3uNkwdz3IGNnJ2v0uPG7LHmCT/INwPLZS', 'user', 1),
('karim@example.com', '$2y$10$dXokUd/D45kIu2ER24fJ3uNkwdz3IGNnJ2v0uPG7LHmCT/INwPLZS', 'user', 2);
