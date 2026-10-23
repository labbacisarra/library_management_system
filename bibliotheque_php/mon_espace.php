<?php
// Espace adhérent : catalogue + mes emprunts (lecture seule)
require_once 'includes/auth.php';
require_user();
$u = current_user();

$title = 'Mon espace'; $active = 'espace';
require 'includes/header.php';

$adh = null;
$mesEmprunts = [];
if ($u['adherent_id']) {
    $st = $pdo->prepare('SELECT * FROM adherents WHERE id = ?');
    $st->execute([$u['adherent_id']]);
    $adh = $st->fetch();

    $st = $pdo->prepare(
        'SELECT e.*, l.titre, l.auteur FROM emprunts e
         JOIN livres l ON l.id = e.livre_id
         WHERE e.adherent_id = ?
         ORDER BY (e.date_retour_effective IS NULL) DESC, e.date_retour_prevue ASC'
    );
    $st->execute([$u['adherent_id']]);
    $mesEmprunts = $st->fetchAll();
}

$catalogue = $pdo->query(
    'SELECT l.*, l.quantite - (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.date_retour_effective IS NULL) AS disponible
     FROM livres l ORDER BY l.titre'
)->fetchAll();

$enCours = 0; $retard = 0;
foreach ($mesEmprunts as $m) {
    if (!$m['date_retour_effective']) {
        $enCours++;
        if ($m['date_retour_prevue'] < date('Y-m-d')) $retard++;
    }
}
?>
<h1>Bonjour <?= e($adh ? $adh['prenom'] : $u['email']) ?> 👋</h1>

<section class="cards">
  <div class="card blue"><span class="num"><?= $enCours ?></span><span>Emprunts en cours</span></div>
  <div class="card red"><span class="num"><?= $retard ?></span><span>En retard</span></div>
  <div class="card"><span class="num"><?= count($mesEmprunts) - $enCours ?></span><span>Livres rendus</span></div>
</section>

<?php if ($retard): ?>
  <div class="flash error">Vous avez <?= $retard ?> livre(s) en retard. Merci de les rapporter à la bibliothèque.</div>
<?php endif; ?>

<h2>Mes emprunts</h2>
<table>
  <thead><tr><th>Livre</th><th>Auteur</th><th>Emprunté le</th><th>Retour prévu</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($mesEmprunts as $d):
      if ($d['date_retour_effective']) { $s = ['Rendu le ' . $d['date_retour_effective'], 'ok']; }
      elseif ($d['date_retour_prevue'] < date('Y-m-d')) { $s = ['En retard', 'late']; }
      else { $s = ['En cours', 'run']; } ?>
    <tr>
      <td><?= e($d['titre']) ?></td>
      <td><?= e($d['auteur']) ?></td>
      <td><?= e($d['date_emprunt']) ?></td>
      <td><?= e($d['date_retour_prevue']) ?></td>
      <td><span class="badge <?= $s[1] ?>"><?= e($s[0]) ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$mesEmprunts): ?><tr><td colspan="5" class="empty">Vous n'avez aucun emprunt.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2 style="margin-top:32px">Catalogue</h2>
<input type="search" id="search" class="search" placeholder="🔍 Rechercher un livre, un auteur..." data-table="tbl">
<table id="tbl">
  <thead><tr><th>Titre</th><th>Auteur</th><th>Catégorie</th><th>Disponibilité</th></tr></thead>
  <tbody>
  <?php foreach ($catalogue as $l): ?>
    <tr>
      <td><?= e($l['titre']) ?></td>
      <td><?= e($l['auteur']) ?></td>
      <td><?= e($l['categorie']) ?></td>
      <td><span class="badge <?= $l['disponible'] > 0 ? 'ok' : 'late' ?>"><?= $l['disponible'] > 0 ? (int)$l['disponible'] . ' disponible(s)' : 'Indisponible' ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<p class="muted small">Pour emprunter un livre, rendez-vous à la bibliothèque : le bibliothécaire enregistrera l'emprunt.</p>
<?php require 'includes/footer.php'; ?>
