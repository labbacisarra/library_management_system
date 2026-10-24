-- À exécuter UNE SEULE FOIS dans phpMyAdmin (onglet SQL) sur la base "bibliotheque".
-- Ajoute le statut des comptes. Les comptes existants restent "actif".
ALTER TABLE users
  ADD COLUMN statut ENUM('en_attente','actif','refuse') NOT NULL DEFAULT 'actif' AFTER role;
