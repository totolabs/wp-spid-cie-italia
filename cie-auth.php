<?php

function cie_auth_init() {
    $base = plugin_dir_path(__FILE__);

    // Percorso di SimpleSAMLphp (modificare se necessario)
    define('SIMPLSAML_PATH', '/percorso/assoluto/simplesamlphp');

    require_once SIMPLSAML_PATH . '/lib/_autoload.php';

    // Istanzia il client CIE
    $as = new \SimpleSAML\Auth\Simple('cie-idp');

    // Se non autenticato, redirect verso il provider CIE
    if (!$as->isAuthenticated()) {
        $as->requireAuth();
    }

    // Recupera gli attributi CIE
    $attributes = $as->getAttributes();

    // Esempio: usa codice fiscale per trovare utente
    $cf = $attributes['fiscalNumber'][0] ?? null;

    if ($cf) {
        $user = get_user_by('login', $cf);
        if (!$user) {
            // Se l’utente non esiste, crealo
            $user_id = wp_create_user($cf, wp_generate_password(), $cf . '@cie.it');
            $user = get_user_by('id', $user_id);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_redirect(admin_url());
        exit;
    } else {
        wp_die('Autenticazione CIE fallita. Codice fiscale non trovato.');
    }
}
