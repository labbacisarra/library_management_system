<?php
require_once 'includes/auth.php';
require_admin();

$title = 'Tableau de bord'; $active = 'dashboard';
require 'includes/header.php';

$nbLivres    = $pdo->query('SELECT COALESCE(SUM(quantite),0) FROM livres')->fetchColumn();
$nbTitres    = $pdo->query('SELECT COUNT(*) FROM livres')->fetchColumn();
$nbAdherents = $pdo->query('SELECT COUNT(*) FROM adherents')->fetchColumn();
$nbEnCours   = $pdo->query('SELECT COUNT(*) FROM emprunts WHERE date_retour_effective IS NULL')->fetchColumn();
$nbRetard    = $pdo->query('SELECT COUNT(*) FROM emprunts WHERE date_retour_effective IS NULL AND date_retour_prevue < CURDATE()')->fetchColumn();

$derniers = $pdo->query(
    'SELECT e.*, l.titre, CONCAT(a.prenom, " ", a.nom) AS adherent
     FROM emprunts e
     JOIN livres l ON l.id = e.livre_id
     JOIN adherents a ON a.id = e.adherent_id
     ORDER BY e.id DESC LIMIT 8'
)->fetchAll();
?>
<h1>Tableau de bord</h1>

<section class="cards">
  <div class="card"><span class="num"><?= $nbTitres ?></span><span>Titres</span></div>
  <div class="card"><span class="num"><?= $nbLivres ?></span><span>Exemplaires</span></div>
  <div class="card"><span class="num"><?= $nbAdherents ?></span><span>Adhérents</span></div>
  <div class="card blue"><span class="num"><?= $nbEnCours ?></span><span>Emprunts en cours</span></div>
  <div class="card red"><span class="num"><?= $nbRetard ?></span><span>En retard</span></div>
</section>

<h2>Derniers emprunts</h2>
<table>
  <thead><tr><th>Livre</th><th>Adhérent</th><th>Emprunté le</th><th>Retour prévu</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($derniers as $d):
      if ($d['date_retour_effective']) { $s = ['Rendu', 'ok']; }
      elseif ($d['date_retour_prevue'] < date('Y-m-d')) { $s = ['En retard', 'late']; }
      else { $s = ['En cours', 'run']; } ?>
    <tr>
      <td><?= e($d['titre']) ?></td>
      <td><?= e($d['adherent']) ?></td>
      <td><?= e($d['date_emprunt']) ?></td>
      <td><?= e($d['date_retour_prevue']) ?></td>
      <td><span class="badge <?= $s[1] ?>"><?= $s[0] ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$derniers): ?><tr><td colspan="5" class="empty">Aucun emprunt pour le moment.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require 'includes/footer.php'; ?>
