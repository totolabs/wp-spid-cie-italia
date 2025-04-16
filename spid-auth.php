<?php

function spid_auth_init() {
    define('SIMPLSAML_PATH', '/percorso/assoluto/simplesamlphp');
    require_once SIMPLSAML_PATH . '/lib/_autoload.php';

    $as = new \SimpleSAML\Auth\Simple('default-sp');

    if (!$as->isAuthenticated()) {
        $as->requireAuth();
    }

    $attributes = $as->getAttributes();

    $cf = $attributes['fiscalNumber'][0] ?? null;

    if ($cf) {
        $user = get_user_by('login', $cf);
        if (!$user) {
            $user_id = wp_create_user($cf, wp_generate_password(), $cf . '@spid.it');
            $user = get_user_by('id', $user_id);
        }

        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_redirect(admin_url());
        exit;
    } else {
        wp_die('Autenticazione SPID fallita. Codice fiscale non trovato.');
    }
}
