<?php
require_once __DIR__ . '/simplesamlphp/lib/_autoload.php';

$provider = $_GET['provider'] ?? 'spid';
$auth = new SimpleSAML\Auth\Simple($provider);

$auth->requireAuth();

$attributes = $auth->getAttributes();

$email = $attributes['email'][0] ?? null;
$username = sanitize_user($attributes['name'][0] ?? $email);

if ($email) {
    $user = get_user_by('email', $email);

    if (!$user) {
        // Crea utente
        $user_id = wp_create_user($username, wp_generate_password(), $email);
        $user = get_user_by('id', $user_id);
    }

    // Login
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    wp_redirect(home_url());
    exit;
} else {
    wp_die('Errore: attributo email non disponibile.');
}