<?php
// Gestion de l'authentification et des rôles (admin / user)
require_once __DIR__ . '/../config.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function home_for($role) {
    return $role === 'admin' ? 'dashboard.php' : 'mon_espace.php';
}

function require_login() {
    if (!current_user()) {
        flash('Veuillez vous connecter pour accéder à cette page.', 'error');
        redirect('login.php');
    }
}

// Pages réservées au bibliothécaire
function require_admin() {
    require_login();
    if (current_user()['role'] !== 'admin') {
        redirect('mon_espace.php');
    }
}

// Pages réservées aux adhérents
function require_user() {
    require_login();
    if (current_user()['role'] !== 'user') {
        redirect('dashboard.php');
    }
}
