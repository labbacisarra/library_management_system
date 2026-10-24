<?php
require_once __DIR__ . '/auth.php';
$u = current_user();

if (!$u) {
    $pages = [
        'home'     => ['Accueil',         'index.php'],
        'login'    => ['Connexion',       'login.php'],
        'register' => ['Créer un compte', 'inscription.php'],
    ];
} elseif ($u['role'] === 'admin') {
    // nombre de demandes d'inscription en attente (affiché dans le menu)
    $nbAttente = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE statut = 'en_attente'")->fetchColumn();
    $pages = [
        'dashboard'    => ['Tableau de bord', 'dashboard.php'],
        'livres'       => ['Livres',          'livres.php'],
        'adherents'    => ['Adhérents',       'adherents.php'],
        'inscriptions' => ['Inscriptions' . ($nbAttente ? " ($nbAttente)" : ''), 'inscriptions.php'],
        'emprunts'     => ['Emprunts',        'emprunts.php'],
    ];
} else {
    $pages = ['home' => ['Accueil', 'index.php'], 'espace' => ['Mon espace', 'mon_espace.php']];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Bibliothèque') ?> — Ma Bibliothèque</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php" ></a>
  <nav>
    <?php foreach ($pages as $key => [$label, $href]): ?>
      <a href="<?= $href ?>" class="<?= ($active ?? '') === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
    <?php if ($u): ?>
      <span class="who"><?= $u['role'] === 'admin' ? 'Admin' : 'Adhérent' ?> · <?= e($u['email']) ?></span>
      <a href="logout.php" class="logout">Déconnexion</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="flash <?= e($t) ?>"><?= e($m) ?></div>
<?php endif; ?>