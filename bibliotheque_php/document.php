<?php
// Affiche un document d'inscription : réservé à l'administrateur
require_once 'includes/auth.php';
require_admin();

$st = $pdo->prepare('SELECT * FROM documents WHERE id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$d = $st->fetch();

$path = $d ? __DIR__ . '/uploads/' . basename($d['fichier']) : '';
if (!$d || !is_file($path)) {
    http_response_code(404);
    exit('Document introuvable.');
}

header('Content-Type: ' . $d['mime']);
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="document.' . pathinfo($d['fichier'], PATHINFO_EXTENSION) . '"');
readfile($path);
