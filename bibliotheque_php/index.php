<?php
// Page d'accueil publique
require_once 'includes/auth.php';
$title = 'Accueil'; $active = 'home';
require 'includes/header.php';

$nbTitres = $pdo->query('SELECT COUNT(*) FROM livres')->fetchColumn();
$nbAdh    = $pdo->query('SELECT COUNT(*) FROM adherents')->fetchColumn();
$livres   = $pdo->query(
    'SELECT l.id, l.titre, l.auteur, l.categorie,
            l.quantite - (SELECT COUNT(*) FROM emprunts e WHERE e.livre_id = l.id AND e.date_retour_effective IS NULL) AS disponible
     FROM livres l ORDER BY l.id DESC LIMIT 6'
)->fetchAll();
?>
<section class="hero">
  <h1>Bienvenue à la bibliothèque</h1>
  <p>Consultez notre catalogue, suivez vos emprunts et gérez votre compte adhérent en ligne.</p>
  <?php if ($u): ?>
    <a class="btn big" href="<?= home_for($u['role']) ?>">Accéder à mon espace</a>
  <?php else: ?>
    <a class="btn big" href="login.php">Se connecter</a>
  <?php endif; ?>
</section>

<section class="cards">
  <div class="card"><span class="num"><?= $nbTitres ?></span><span>Livres au catalogue</span></div>
  <div class="card blue"><span class="num"><?= $nbAdh ?></span><span>Adhérents</span></div>
  <div class="card"><span class="num">14 j</span><span>Durée d'emprunt habituelle</span></div>
</section>

<h2>Derniers livres ajoutés</h2>
<div class="books">
  <?php foreach ($livres as $l): ?>
    <article class="book">
      <img src="images/<?= (int)$l['id'] ?>.jpg" alt="غلاف <?= e($l['titre']) ?>">
    <h3><?= e($l['titre']) ?></h3>
    
      <p><?= e($l['auteur']) ?></p>
      <?php if ($l['categorie']): ?><small><?= e($l['categorie']) ?></small><?php endif; ?>
      <span class="badge <?= $l['disponible'] > 0 ? 'ok' : 'late' ?>"><?= $l['disponible'] > 0 ? 'Disponible' : 'Indisponible' ?></span>
    </article>
  <?php endforeach; ?>
  <?php if (!$livres): ?><p class="empty">Le catalogue est vide pour le moment.</p><?php endif; ?>
</div>
<?php require 'includes/footer.php'; ?>
