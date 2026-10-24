<?php
// Page de connexion commune (admin et adhérent)
require_once 'includes/auth.php';

if (current_user()) {
    redirect(home_for(current_user()['role']));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $st = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $st->execute([$email]);
    $user = $st->fetch();

    if ($user && password_verify($pass, $user['password'])) {
        if ($user['statut'] === 'en_attente') {
            $error = 'Votre compte est en attente de validation par le bibliothécaire.';
        } elseif ($user['statut'] === 'refuse') {
            $error = 'Votre demande d\'inscription a été refusée. Contactez la bibliothèque.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'          => (int)$user['id'],
                'email'       => $user['email'],
                'role'        => $user['role'],
                'adherent_id' => $user['adherent_id'] ? (int)$user['adherent_id'] : null,
            ];
            redirect(home_for($user['role']));
        }
    } else {
        $error = 'E-mail ou mot de passe incorrect.';
    }
}

$title = 'Connexion'; $active = 'login';
require 'includes/header.php';
?>
<div class="login-box">
  <h1>Connexion</h1>
  <p class="muted">Administrateur ou adhérent : utilisez votre e-mail et votre mot de passe.</p>
  <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="form">
    <label>E-mail
      <input type="email" name="email" required autofocus value="<?= e($_POST['email'] ?? '') ?>">
    </label>
    <label style="margin-top:12px">Mot de passe
      <input type="password" name="password" required>
    </label>
    <button class="btn" type="submit" style="margin-top:16px;width:100%">Se connecter</button>
  </form>
  <p class="muted small">Pas encore de compte ? <a href="inscription.php">Créer un compte</a></p>
</div>
<?php require 'includes/footer.php'; ?>
