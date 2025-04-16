<?php
/*
Plugin Name: WP SPID+CIE Italia
Description: Plugin per l’autenticazione su WordPress tramite SPID e CIE.
Version: 1.0.0
Author: Totolabs (fork da Marco Milesi)
Author URI: http://www.totolabs.it
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Percorso assoluto a SimpleSAMLphp
define('SSP_PATH', plugin_dir_path(__FILE__) . 'simplesamlphp/');

// Autoload di SimpleSAMLphp
require_once SSP_PATH . 'lib/_autoload.php';

// Includi logica di autenticazione SPID e CIE
require_once plugin_dir_path(__FILE__) . 'spid-cie-auth.php';

// Azione per mostrare pulsanti SPID/CIE nel login WP
add_action('login_form', 'wpsc_add_spid_cie_buttons');
function wpsc_add_spid_cie_buttons() {
    echo '<div style="margin-bottom: 15px">';
    echo '<a href="' . esc_url(site_url('?auth_type=spid')) . '" class="button button-primary">Accedi con SPID</a><br><br>';
    echo '<a href="' . esc_url(site_url('?auth_type=cie')) . '" class="button button-secondary">Accedi con CIE</a>';
    echo '</div>';
}

// Router SPID / CIE
add_action('init', function () {
    if (!isset($_GET['auth_type'])) return;

    $authType = sanitize_text_field($_GET['auth_type']);

    if (!in_array($authType, ['spid', 'cie'])) return;

    $as = new \SimpleSAML\Auth\Simple($authType);
    $as->requireAuth();

    $attributes = $as->getAttributes();

    // Esempio: estrai codice fiscale e nome
    $codiceFiscale = $attributes['fiscalNumber'][0] ?? '';
    $nome = $attributes['givenName'][0] ?? '';
    $email = $attributes['email'][0] ?? $codiceFiscale . '@spidcie.local';

    // Trova o crea utente WP
    $user = get_user_by('login', $codiceFiscale);
    if (!$user) {
        $user_id = wp_create_user($codiceFiscale, wp_generate_password(), $email);
        wp_update_user([
            'ID' => $user_id,
            'display_name' => $nome,
            'first_name' => $nome,
        ]);
        $user = get_user_by('id', $user_id);
    }

    // Esegui login
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID);
    do_action('wp_login', $user->user_login, $user);

    // Redirect home
    wp_redirect(home_url());
    exit;
});


// Azione per mostrare il plugin nel menù admin
add_action('admin_menu', 'wpsc_add_admin_page');

function wpsc_add_admin_page() {
    add_options_page(
        'WP SPID & CIE',
        'SPID & CIE',
        'manage_options',
        'wp-spid-cie-settings',
        'wpsc_render_admin_page'
    );
}

function wpsc_render_admin_page() {
    ?>
    <div class="wrap">
        <h1>Configurazione SPID & CIE</h1>
        <p>Qui puoi visualizzare e verificare la configurazione dei provider.</p>
        
        <h2>Provider attivi</h2>
        <ul>
            <li><strong>SPID:</strong> <?php echo file_exists(SSP_PATH . 'config/authsources.php') ? '✔️ configurato' : '❌ non trovato'; ?></li>
            <li><strong>CIE:</strong> <?php echo file_exists(SSP_PATH . 'config/authsources.php') ? '✔️ configurato' : '❌ non trovato'; ?></li>
        </ul>

        <h2>Metadata</h2>
        <p><a href="<?php echo site_url('/wp-content/plugins/wp-spid-cie-italia/simplesamlphp/module.php/saml/sp/metadata.php/spid'); ?>" target="_blank">🔗 Metadata SPID</a></p>
        <p><a href="<?php echo site_url('/wp-content/plugins/wp-spid-cie-italia/simplesamlphp/module.php/saml/sp/metadata.php/cie'); ?>" target="_blank">🔗 Metadata CIE</a></p>

        <h2>Certificati</h2>
        <p>Assicurati di aver caricato i certificati in <code>/cert</code> e che i file <code>spid.key</code>, <code>spid.crt</code>, <code>cie.key</code>, <code>cie.crt</code> siano presenti.</p>
    </div>
    <?php
}