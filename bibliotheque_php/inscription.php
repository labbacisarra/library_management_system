<?php
// Page d'inscription publique : le compte est créé "en attente de validation"
require_once 'includes/auth.php';
require_once 'includes/documents.php';

if (current_user()) {
    redirect(home_for(current_user()['role']));
}

$errors = [];
$old = ['nom' => '', 'prenom' => '', 'email' => '', 'telephone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $k => $_) { $old[$k] = trim($_POST[$k] ?? ''); }
    $mdp  = $_POST['mot_de_passe'] ?? '';
    $mdp2 = $_POST['confirmation'] ?? '';

    if ($old['nom'] === '' || $old['prenom'] === '')            $errors[] = 'Le nom et le prénom sont obligatoires.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))      $errors[] = 'Adresse e-mail invalide.';
    if ($old['telephone'] !== '' && !preg_match('/^[0-9+\s().-]{6,20}$/', $old['telephone'])) $errors[] = 'Numéro de téléphone invalide.';
    if (strlen($mdp) < 6)                                       $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    if ($mdp !== $mdp2)                                         $errors[] = 'Les deux mots de passe ne sont pas identiques.';

    // ---- Vérification des documents (avant toute écriture en base) ----
    $fichiers = [];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    foreach ($DOCS_REQUIS as $cle => [$libelle, $oblig]) {
        $f = $_FILES['doc_' . $cle] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            if ($oblig) $errors[] = "Document manquant : $libelle.";
            continue;
        }
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > DOC_MAX_OCTETS) {
            $errors[] = "« $libelle » dépasse 2 Mo.";
            continue;
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            $errors[] = "Erreur lors de l'envoi du document : $libelle.";
            continue;
        }
        $mime = finfo_file($finfo, $f['tmp_name']);           // type réel du fichier (pas seulement son nom)
        if (!isset(DOC_TYPES[$mime])) {
            $errors[] = "« $libelle » : format non accepté (PDF, JPG ou PNG uniquement).";
            continue;
        }
        $fichiers[$cle] = ['tmp' => $f['tmp_name'], 'mime' => $mime, 'ext' => DOC_TYPES[$mime], 'nom' => mb_substr(basename($f['name']), 0, 200)];
    }

    if (!$errors) {
        $dir = __DIR__ . '/uploads';
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        if (!file_exists($dir . '/.htaccess')) {                // les fichiers ne sont jamais accessibles directement
            @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
        }
        $deplaces = [];
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO adherents (nom, prenom, email, telephone, date_inscription) VALUES (?,?,?,?,CURDATE())')
                ->execute([$old['nom'], $old['prenom'], $old['email'], $old['telephone']]);
            $adhId = (int)$pdo->lastInsertId();
            $pdo->prepare("INSERT INTO users (email, password, role, statut, adherent_id) VALUES (?,?,'user','en_attente',?)")
                ->execute([$old['email'], password_hash($mdp, PASSWORD_DEFAULT), $adhId]);
            $uid = (int)$pdo->lastInsertId();

            foreach ($fichiers as $cle => $f) {
                $nom = bin2hex(random_bytes(16)) . '.' . $f['ext']; // nom aléatoire
                if (!move_uploaded_file($f['tmp'], "$dir/$nom")) throw new RuntimeException('upload');
                $deplaces[] = "$dir/$nom";
                $pdo->prepare('INSERT INTO documents (user_id, type, fichier, nom_original, mime) VALUES (?,?,?,?,?)')
                    ->execute([$uid, $cle, $nom, $f['nom'], $f['mime']]);
            }
            $pdo->commit();

            flash('Votre demande a bien été envoyée avec vos documents. Votre compte sera activé après validation par le bibliothécaire.');
            redirect('login.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            foreach ($deplaces as $p) { @unlink($p); }
            $errors[] = ($e instanceof PDOException && $e->getCode() === '23000')
                ? 'Cette adresse e-mail est déjà utilisée.'
                : 'Une erreur est survenue. Réessayez plus tard.';
        }
    }
}

$title = 'Inscription'; $active = 'register';
require 'includes/header.php';
?>
<style>
  /* Formulaire d'inscription plus large et plus aéré */
  .inscr { max-width: 52rem; margin: 1.5rem auto 2.5rem; }
  .inscr .form { padding: 2rem; }
  .inscr .grid { grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: 1.3rem 1.6rem; margin-bottom: 1.6rem; }
  .inscr input, .inscr select { min-height: 3rem; }
  .inscr fieldset { border: 1px solid var(--border, #c7ced9); border-radius: 12px; padding: 1.2rem 1.4rem 1.4rem; margin: 0 0 1.6rem; }
  .inscr legend { font-weight: 600; padding: 0 .5rem; }
  .inscr .doc { display: block; margin-top: 1rem; }
  .inscr .doc input[type=file] { padding: .6rem; width: 100%; background: var(--surface-solid, #fff); }
  .inscr .hint { font-weight: 400; font-size: .85rem; color: var(--text-muted, #3A4E6F); }
  .inscr .file-err { color: #B3261E; font-size: .88rem; font-weight: 500; }
</style>

<div class="inscr">
  <h1>Créer un compte</h1>
  <p class="muted">Remplissez le formulaire et joignez les documents demandés. Le bibliothécaire vérifiera votre demande, puis vous recevrez un e-mail.</p>

  <?php foreach ($errors as $err): ?><div class="flash error"><?= e($err) ?></div><?php endforeach; ?>
  <?php if ($errors && $_FILES): ?><p class="muted small">Pour des raisons de sécurité, vous devez sélectionner de nouveau vos documents.</p><?php endif; ?>

  <form method="post" class="form" enctype="multipart/form-data" novalidate>
    <div class="grid">
      <label>Nom *<input name="nom" required value="<?= e($old['nom']) ?>" autocomplete="family-name"></label>
      <label>Prénom *<input name="prenom" required value="<?= e($old['prenom']) ?>" autocomplete="given-name"></label>
      <label>E-mail *<input type="email" name="email" required value="<?= e($old['email']) ?>" autocomplete="email"></label>
      <label>Téléphone<input name="telephone" value="<?= e($old['telephone']) ?>" autocomplete="tel"></label>
      <label>Mot de passe * <span class="hint">(6 caractères minimum)</span><input type="password" name="mot_de_passe" required minlength="6" autocomplete="new-password"></label>
      <label>Confirmer le mot de passe *<input type="password" name="confirmation" required minlength="6" autocomplete="new-password"></label>
    </div>

    <fieldset>
      <legend>Documents à fournir</legend>
      <p class="hint" style="margin:0">Formats acceptés : PDF, JPG ou PNG. Taille maximale : 2 Mo par document.</p>
      <?php foreach ($DOCS_REQUIS as $cle => [$libelle, $oblig]): ?>
        <label class="doc"><?= e($libelle) ?> <?= $oblig ? '*' : '<span class="hint">(facultatif)</span>' ?>
          <input type="file" name="doc_<?= e($cle) ?>" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" <?= $oblig ? 'required' : '' ?>>
          <span class="file-err" role="alert"></span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <button class="btn" type="submit" style="width:100%">Envoyer ma demande</button>
  </form>
  <p class="muted small">Déjà un compte ? <a href="login.php">Se connecter</a></p>
</div>

<script>
// Avertit tout de suite si un fichier dépasse 2 Mo (le serveur re-vérifie de toute façon)
document.querySelectorAll('.inscr input[type=file]').forEach(function (input) {
  input.addEventListener('change', function () {
    var err = input.parentElement.querySelector('.file-err');
    err.textContent = '';
    var f = input.files[0];
    if (f && f.size > <?= DOC_MAX_OCTETS ?>) { err.textContent = 'Fichier trop lourd (2 Mo maximum).'; input.value = ''; }
  });
});
</script>
<?php require 'includes/footer.php'; ?>
