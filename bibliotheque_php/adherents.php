<?php
require_once 'includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id     = (int)($_POST['id'] ?? 0);
        $nom    = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $tel    = trim($_POST['telephone'] ?? '');
        $mdp    = $_POST['mot_de_passe'] ?? '';

        if ($nom === '' || $prenom === '') {
            flash('Le nom et le prénom sont obligatoires.', 'error');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Adresse e-mail invalide.', 'error');
        } elseif ($mdp !== '' && $email === '') {
            flash('Une adresse e-mail est nécessaire pour créer un compte de connexion.', 'error');
        } elseif ($mdp !== '' && strlen($mdp) < 6) {
            flash('Le mot de passe doit contenir au moins 6 caractères.', 'error');
        } else {
            try {
                $pdo->beginTransaction();
                if ($id) {
                    $pdo->prepare('UPDATE adherents SET nom=?, prenom=?, email=?, telephone=? WHERE id=?')
                        ->execute([$nom, $prenom, $email, $tel, $id]);
                } else {
                    $pdo->prepare('INSERT INTO adherents (nom, prenom, email, telephone, date_inscription) VALUES (?,?,?,?,CURDATE())')
                        ->execute([$nom, $prenom, $email, $tel]);
                    $id = (int)$pdo->lastInsertId();
                }

                // Compte de connexion lié à l'adhérent
                $st = $pdo->prepare('SELECT id FROM users WHERE adherent_id = ?');
                $st->execute([$id]);
                $uid = $st->fetchColumn();

                if ($uid) {
                    if ($email !== '') {
                        $pdo->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $uid]);
                    }
                    if ($mdp !== '') {
                        $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                            ->execute([password_hash($mdp, PASSWORD_DEFAULT), $uid]);
                    }
                } elseif ($mdp !== '') {
                    $pdo->prepare('INSERT INTO users (email, password, role, adherent_id) VALUES (?,?,\'user\',?)')
                        ->execute([$email, password_hash($mdp, PASSWORD_DEFAULT), $id]);
                }
                $pdo->commit();
                flash('Adhérent enregistré.');
            } catch (PDOException $e) {
                $pdo->rollBack();
                flash('Cet e-mail est déjà utilisé par un autre compte.', 'error');
            }
        }
    } elseif ($action === 'delete') {
        try {
            $pdo->prepare('DELETE FROM adherents WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Adhérent supprimé.');
        } catch (PDOException $e) {
            flash('Impossible de supprimer : cet adhérent a des emprunts associés.', 'error');
        }
    }
    redirect('adherents.php');
}

$title = 'Adhérents'; $active = 'adherents';
require 'includes/header.php';

$edit = null;
if (isset($_GET['edit'])) {
    $st = $pdo->prepare('SELECT * FROM adherents WHERE id=?');
    $st->execute([(int)$_GET['edit']]);
    $edit = $st->fetch() ?: null;
}
$adherents = $pdo->query(
    'SELECT a.*, (SELECT COUNT(*) FROM users u WHERE u.adherent_id = a.id) AS has_account
     FROM adherents a ORDER BY a.nom, a.prenom'
)->fetchAll();
?>
<h1>Gestion des adhérents</h1>

<form method="post" class="form">
  <h2><?= $edit ? 'Modifier l\'adhérent' : 'Ajouter un adhérent' ?></h2>
  <input type="hidden" name="action" value="save">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
  <div class="grid">
    <label>Nom *<input name="nom" required value="<?= e($edit['nom'] ?? '') ?>"></label>
    <label>Prénom *<input name="prenom" required value="<?= e($edit['prenom'] ?? '') ?>"></label>
    <label>E-mail<input type="email" name="email" value="<?= e($edit['email'] ?? '') ?>"></label>
    <label>Téléphone<input name="telephone" value="<?= e($edit['telephone'] ?? '') ?>"></label>
    <label>Mot de passe (compte de connexion)
      <input type="password" name="mot_de_passe" minlength="6" autocomplete="new-password"
             placeholder="<?= $edit ? 'Laisser vide pour ne pas changer' : 'Min. 6 caractères' ?>">
    </label>
  </div>
  <button class="btn" type="submit"><?= $edit ? 'Enregistrer' : 'Ajouter' ?></button>
  <?php if ($edit): ?><a class="btn light" href="adherents.php">Annuler</a><?php endif; ?>
</form>

<input type="search" id="search" class="search" placeholder="🔍 Rechercher un adhérent..." data-table="tbl">
<table id="tbl">
  <thead><tr><th>Nom</th><th>Prénom</th><th>E-mail</th><th>Téléphone</th><th>Inscrit le</th><th>Compte</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($adherents as $a): ?>
    <tr>
      <td><?= e($a['nom']) ?></td>
      <td><?= e($a['prenom']) ?></td>
      <td><?= e($a['email']) ?></td>
      <td><?= e($a['telephone']) ?></td>
      <td><?= e($a['date_inscription']) ?></td>
      <td><span class="badge <?= $a['has_account'] ? 'ok' : 'late' ?>"><?= $a['has_account'] ? 'Oui' : 'Non' ?></span></td>
      <td class="actions">
        <a class="btn small light" href="adherents.php?edit=<?= $a['id'] ?>">Modifier</a>
        <form method="post" class="inline" data-confirm="Supprimer cet adhérent ?">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $a['id'] ?>">
          <button class="btn small danger">Supprimer</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$adherents): ?><tr><td colspan="7" class="empty">Aucun adhérent.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require 'includes/footer.php'; ?>
