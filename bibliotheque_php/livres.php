<?php
require_once 'includes/auth.php';
require_admin();

// ---- Traitement des actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id        = (int)($_POST['id'] ?? 0);
        $titre     = trim($_POST['titre'] ?? '');
        $auteur    = trim($_POST['auteur'] ?? '');
        $isbn      = trim($_POST['isbn'] ?? '');
        $categorie = trim($_POST['categorie'] ?? '');
        $quantite  = max(1, (int)($_POST['quantite'] ?? 1));

        if ($titre === '' || $auteur === '') {
            flash('Le titre et l\'auteur sont obligatoires.', 'error');
        } elseif ($id) {
            $pdo->prepare('UPDATE livres SET titre=?, auteur=?, isbn=?, categorie=?, quantite=? WHERE id=?')
                ->execute([$titre, $auteur, $isbn, $categorie, $quantite, $id]);
            flash('Livre modifié.');
        } else {
            $pdo->prepare('INSERT INTO livres (titre, auteur, isbn, categorie, quantite) VALUES (?,?,?,?,?)')
                ->execute([$titre, $auteur, $isbn, $categorie, $quantite]);
            flash('Livre ajouté.');
        }
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare('DELETE FROM livres WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Livre supprimé.');
        } catch (PDOException $e) {
            flash('Impossible de supprimer : ce livre a des emprunts associés.', 'error');
        }
    }
    redirect('livres.php');
}

$title = 'Livres'; $active = 'livres';
require 'includes/header.php';

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM livres WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}

$livres = $pdo->query(
    'SELECT l.*, l.quantite - (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.date_retour_effective IS NULL) AS disponible
     FROM livres l ORDER BY l.titre'
)->fetchAll();
?>
<h1>Gestion des livres</h1>

<form method="post" class="form">
  <h2><?= $edit ? 'Modifier le livre' : 'Ajouter un livre' ?></h2>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
  <div class="grid">
    <label>Titre *<input name="titre" required value="<?= e($edit['titre'] ?? '') ?>"></label>
    <label>Auteur *<input name="auteur" required value="<?= e($edit['auteur'] ?? '') ?>"></label>
    <label>ISBN<input name="isbn" value="<?= e($edit['isbn'] ?? '') ?>"></label>
    <label>Catégorie<input name="categorie" value="<?= e($edit['categorie'] ?? '') ?>"></label>
    <label>Quantité<input type="number" name="quantite" min="1" value="<?= e($edit['quantite'] ?? 1) ?>"></label>
  </div>
  <button class="btn" type="submit"><?= $edit ? 'Enregistrer' : 'Ajouter' ?></button>
  <?php if ($edit): ?><a class="btn light" href="livres.php">Annuler</a><?php endif; ?>
</form>

<input type="search" id="search" class="search" placeholder="🔍 Rechercher un livre..." data-table="tbl">
<table id="tbl">
  <thead><tr><th>Titre</th><th>Auteur</th><th>ISBN</th><th>Catégorie</th><th>Dispo / Total</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($livres as $l): ?>
    <tr>
      <td><?= e($l['titre']) ?></td>
      <td><?= e($l['auteur']) ?></td>
      <td><?= e($l['isbn']) ?></td>
      <td><?= e($l['categorie']) ?></td>
      <td><span class="badge <?= $l['disponible'] > 0 ? 'ok' : 'late' ?>"><?= (int)$l['disponible'] ?> / <?= (int)$l['quantite'] ?></span></td>
      <td class="actions">
        <a class="btn small light" href="livres.php?edit=<?= $l['id'] ?>">Modifier</a>
        <form method="post" class="inline" data-confirm="Supprimer ce livre ?">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $l['id'] ?>">
          <button class="btn small danger">Supprimer</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$livres): ?><tr><td colspan="6" class="empty">Aucun livre.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require 'includes/footer.php'; ?>
