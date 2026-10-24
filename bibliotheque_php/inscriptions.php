<?php
// Admin : demandes d'inscription (valider / refuser)
require_once 'includes/auth.php';
require_once 'includes/mail.php';
require_once 'includes/documents.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';

    $st = $pdo->prepare(
        "SELECT u.id, u.email, u.statut, a.prenom, a.nom
         FROM users u JOIN adherents a ON a.id = u.adherent_id
         WHERE u.id = ? AND u.role = 'user'"
    );
    $st->execute([$id]);
    $u = $st->fetch();

    if (!$u || $u['statut'] !== 'en_attente') {
        flash('Cette demande a déjà été traitée.', 'error');
    } elseif ($action === 'valider') {
        $pdo->prepare("UPDATE users SET statut = 'actif' WHERE id = ?")->execute([$id]);

        $msg = "Bonjour {$u['prenom']},\n\n"
             . "Bonne nouvelle : votre compte a été validé. Vous pouvez maintenant vous connecter,\n"
             . "consulter le catalogue et suivre vos emprunts en ligne.\n\n"
             . "Connexion : " . url_connexion() . "\n\n"
             . "À bientôt à la bibliothèque !";
        $sent = envoyer_mail($u['email'], 'Votre compte est activé', $msg);

        flash("Compte de {$u['prenom']} {$u['nom']} activé." . ($sent
            ? ' Un e-mail de confirmation a été envoyé.'
            : " L'e-mail n'a pas pu partir (serveur mail non configuré), mais il est enregistré dans logs/mails.log."));
    } elseif ($action === 'refuser') {
        $pdo->prepare("UPDATE users SET statut = 'refuse' WHERE id = ?")->execute([$id]);
        flash("Demande de {$u['prenom']} {$u['nom']} refusée.");
    }
    redirect('inscriptions.php');
}

$title = 'Inscriptions'; $active = 'inscriptions';
require 'includes/header.php';

$attente = $pdo->query(
    "SELECT u.id, u.email, a.nom, a.prenom, a.telephone, a.date_inscription
     FROM users u JOIN adherents a ON a.id = u.adherent_id
     WHERE u.statut = 'en_attente' AND u.role = 'user' ORDER BY u.id"
)->fetchAll();

// Documents joints aux demandes en attente (regroupés par compte)
$docs = [];
if ($attente) {
    $ids = array_column($attente, 'id');
    $in  = implode(',', array_fill(0, count($ids), '?'));
    $q   = $pdo->prepare("SELECT id, user_id, type FROM documents WHERE user_id IN ($in) ORDER BY id");
    $q->execute($ids);
    foreach ($q->fetchAll() as $row) { $docs[$row['user_id']][] = $row; }
}

$traitees = $pdo->query(
    "SELECT u.email, u.statut, a.nom, a.prenom, a.date_inscription
     FROM users u JOIN adherents a ON a.id = u.adherent_id
     WHERE u.statut IN ('actif','refuse') AND u.role = 'user' ORDER BY u.id DESC LIMIT 10"
)->fetchAll();
?>
<h1>Demandes d'inscription</h1>

<h2>En attente de validation (<?= count($attente) ?>)</h2>
<table>
  <thead><tr><th>Nom</th><th>Prénom</th><th>E-mail</th><th>Téléphone</th><th>Demandé le</th><th>Documents</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($attente as $d): ?>
    <tr>
      <td><?= e($d['nom']) ?></td>
      <td><?= e($d['prenom']) ?></td>
      <td><?= e($d['email']) ?></td>
      <td><?= e($d['telephone']) ?></td>
      <td><?= e($d['date_inscription']) ?></td>
      <td>
        <?php foreach ($docs[$d['id']] ?? [] as $doc): ?>
          <a href="document.php?id=<?= (int)$doc['id'] ?>" target="_blank" rel="noopener" style="display:block">
            <?= e($DOCS_REQUIS[$doc['type']][0] ?? $doc['type']) ?>
          </a>
        <?php endforeach; ?>
        <?php if (empty($docs[$d['id']])): ?><span class="muted small">Aucun document</span><?php endif; ?>
      </td>
      <td class="actions">
        <form method="post" class="inline" data-confirm="Valider cette inscription ?">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <input type="hidden" name="action" value="valider">
          <button class="btn small">Valider</button>
        </form>
        <form method="post" class="inline" data-confirm="Refuser cette inscription ?">
          <input type="hidden" name="id" value="<?= $d['id'] ?>">
          <input type="hidden" name="action" value="refuser">
          <button class="btn small danger">Refuser</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$attente): ?><tr><td colspan="7" class="empty">Aucune demande en attente.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2>Dernières demandes traitées</h2>
<table>
  <thead><tr><th>Nom</th><th>E-mail</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($traitees as $d): ?>
    <tr>
      <td><?= e($d['prenom'] . ' ' . $d['nom']) ?></td>
      <td><?= e($d['email']) ?></td>
      <td><span class="badge <?= $d['statut'] === 'actif' ? 'ok' : 'late' ?>"><?= $d['statut'] === 'actif' ? 'Validé' : 'Refusé' ?></span></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$traitees): ?><tr><td colspan="3" class="empty">Rien pour le moment.</td></tr><?php endif; ?>
  </tbody>
</table>
<?php require 'includes/footer.php'; ?>
