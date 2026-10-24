<?php
require_once 'includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'emprunter') {
        $livre = (int)($_POST['livre_id'] ?? 0);
        $adh   = (int)($_POST['adherent_id'] ?? 0);
        $duree = max(1, min(60, (int)($_POST['duree'] ?? 14)));

        // Vérifier la disponibilité
        $st = $pdo->prepare(
            'SELECT l.quantite - (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.date_retour_effective IS NULL)
             FROM livres l WHERE l.id = ?'
        );
        $st->execute([$livre]);
        $dispo = $st->fetchColumn();

        if (!$livre || !$adh) {
            flash('Choisissez un livre et un adhérent.', 'error');
        } elseif ($dispo === false || $dispo < 1) {
            flash('Ce livre n\'est plus disponible.', 'error');
        } else {
            $pdo->prepare('INSERT INTO emprunts (livre_id, adherent_id, date_emprunt, date_retour_prevue)
                           VALUES (?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))')
                ->execute([$livre, $adh, $duree]);
            flash('Emprunt enregistré.');
        }
    } elseif ($action === 'retour') {
        $pdo->prepare('UPDATE emprunts SET date_retour_effective = CURDATE() WHERE id = ? AND date_retour_effective IS NULL')
            ->execute([(int)$_POST['id']]);
        flash('Retour enregistré.');
    }
    redirect('emprunts.php');
}

$title = 'Emprunts'; $active = 'emprunts';
require 'includes/header.php';

$livresDispo = $pdo->query(
    'SELECT l.id, l.titre, l.quantite - (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.date_retour_effective IS NULL) AS dispo
     FROM livres l HAVING dispo > 0 ORDER BY l.titre'
)->fetchAll();
// Seuls les adhérents dont le compte n'est pas en attente / refusé peuvent emprunter
$adherents = $pdo->query(
    "SELECT a.id, a.nom, a.prenom FROM adherents a
     WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.adherent_id = a.id AND u.statut <> 'actif')
     ORDER BY a.nom, a.prenom"
)->fetchAll();

$emprunts = $pdo->query(
    'SELECT e.*, l.titre, CONCAT(a.prenom, " ", a.nom) AS adherent
     FROM emprunts e
     JOIN livres l ON l.id = e.livre_id
     JOIN adherents a ON a.id = e.adherent_id
     ORDER BY (e.date_retour_effective IS NULL) DESC, e.date_retour_prevue ASC'
)->fetchAll();
?>
<h1>Gestion des emprunts</h1>

<form method="post" class="form">
  <h2>Nouvel emprunt</h2>
  <input type="hidden" name="action" value="emprunter">
  <div class="grid">
    <label>Livre *
      <select name="livre_id" required>
        <option value="">-- Choisir --</option>
        <?php foreach ($livresDispo as $l): ?>
          <option value="<?= $l['id'] ?>"><?= e($l['titre']) ?> (<?= (int)$l['dispo'] ?> dispo)</option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Adhérent *
      <select name="adherent_id" required>
        <option value="">-- Choisir --</option>
        <?php foreach ($adherents as $a): ?>
          <option value="<?= $a['id'] ?>"><?= e($a['nom'] . ' ' . $a['prenom']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Durée (jours)<input type="number" name="duree" min="1" max="60" value="14"></label>
  </div>
  <button class="btn" type="submit">Enregistrer l'emprunt</button>
</form>

<input type="search" id="search" class="search" placeholder="🔍 Rechercher un emprunt..." data-table="tbl">
<table id="tbl">
  <thead><tr><th>Livre</th><th>Adhérent</th><th>Emprunté le</th><th>Retour prévu</th><th>Statut</th><th>Action</th></tr></thead>
  <tbody>
  <?php foreach ($emprunts as $d):
      if ($d['date_retour_effective']) { $s = ['Rendu le ' . $d['date_retour_effective'], 'ok']; }
      elseif ($d['date_retour_prevue'] < date('Y-m-d')) { $s = ['En retard', 'late']; }
      else { $s = ['En cours', 'run']; } ?>
    <tr>
      <td><?= e($d['titre']) ?></td>
      <td><?= e($d['adherent']) ?></td>
      <td><?= e($d['date_emprunt']) ?></td>
      <td><?= e($d['date_retour_prevue']) ?></td>
      <td><span class="badge <?= $s[1] ?>"><?= e($s[0]) ?></span></td>
      <td>
        <?php if (!$d['date_retour_effective']): ?>
        <form method="post" class="inline" data-confirm="Confirmer le retour de ce livre ?">
          <input type="hidden" name="action" value="retour">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <button class="btn small">Marquer rendu</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$emprunts): ?><tr><td colspan="6" class="empty">Aucun emprunt.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require 'includes/footer.php'; ?>
