<?php
// Envoi d'e-mails. Chaque message est aussi enregistré dans logs/mails.log :
// pratique pour la démonstration quand le serveur mail n'est pas configuré.
function envoyer_mail($to, $sujet, $message) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    if (!file_exists($dir . '/.htaccess')) { @file_put_contents($dir . '/.htaccess', "Require all denied\n"); }

    $entry = '[' . date('Y-m-d H:i:s') . "] A : $to | Sujet : $sujet\n$message\n----------------------------------------\n";
    @file_put_contents($dir . '/mails.log', $entry, FILE_APPEND);

    $headers  = "From: Ma Bibliotheque <no-reply@bibliotheque.local>\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    return @mail($to, '=?UTF-8?B?' . base64_encode($sujet) . '?=', $message, $headers);
}

// Adresse de la page de connexion (pour le lien dans l'e-mail)
function url_connexion() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $dir . '/login.php';
}
