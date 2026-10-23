<?php
// Connexion à la base de données (modifier selon votre configuration XAMPP/WAMP)
session_start();

$host = 'localhost';
$db   = 'bibliotheque';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Erreur de connexion à la base : ' . htmlspecialchars($e->getMessage()));
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function flash($msg, $type = 'success') { $_SESSION['flash'] = [$msg, $type]; }
function redirect($url) { header("Location: $url"); exit; }
