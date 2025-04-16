<?php
/*
 * Plugin Name: WP SPID CIE Italia
 * Description: Plugin WordPress per autenticazione con SPID e CIE, basato su WP SPID Italia e SimpleSAMLphp
 * Version: 1.1.0
 * Author: Totolabs Srl
 * Author URI: https://totolabs.it
 * License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('WPSCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSCI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPSCI_CERT_DIR', WPSCI_PLUGIN_DIR . 'cert/');
define('WPSCI_BACKUP_DIR', WPSCI_PLUGIN_DIR . 'cert/backup/');
define('WPSCI_ACS_URL', home_url('/wp-spid-cie-acs'));

require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';

// Funzione unica per attivazione plugin
function wpsci_plugin_activate() {
    // Crea directory certificati
    if (!file_exists(WPSCI_CERT_DIR)) {
        mkdir(WPSCI_CERT_DIR, 0755, true);
    }
    if (!file_exists(WPSCI_BACKUP_DIR)) {
        mkdir(WPSCI_BACKUP_DIR, 0755, true);
    }

    // Controllo presenza SimpleSAMLphp
    if (!file_exists(WPSCI_PLUGIN_DIR . 'simplesamlphp')) {
        error_log('WP SPID CIE: manca la directory simplesamlphp');
    }

    // Generazione certificati se richiesto
    $options = get_option('wpsci_settings');
    if (!empty($options['regenerate_cert']) && !empty($options['cert_path'])) {
        wpsci_generate_self_signed_cert($options['cert_path']);
    }
}
register_activation_hook(__FILE__, 'wpsci_plugin_activate');

// Funzione di disattivazione del plugin
function wpsci_plugin_deactivate() {
    // Backup dei certificati esistenti, se presenti
    $cert_file = WPSCI_CERT_DIR . 'certificate.crt';
    $key_file = WPSCI_CERT_DIR . 'private.key';

    if (file_exists($cert_file)) {
        copy($cert_file, WPSCI_BACKUP_DIR . 'certificate_backup_' . date('Ymd_His') . '.crt');
    }

    if (file_exists($key_file)) {
        copy($key_file, WPSCI_BACKUP_DIR . 'private_backup_' . date('Ymd_His') . '.key');
    }

    // Opzionale: scrivi su log
    error_log('WP SPID CIE disattivato. Backup certificati effettuato.');
}

// Registra la funzione di disattivazione
register_deactivation_hook(__FILE__, 'wpsci_plugin_deactivate');

// Aggiungi il menu nel pannello di amministrazione
function wpsci_add_admin_menu() {
    add_menu_page(
        'SPID CIE Italia',       // Nome della pagina
        'SPID CIE Italia',       // Nome nel menu
        'manage_options',        // Permessi di accesso
        'wp-spid-cie-italia',    // Slug della pagina
        'wpsci_admin_page',      // Funzione che carica la pagina
        'dashicons-admin-network', // Icona del menu
        6                        // Posizione nel menu
    );
}
add_action('admin_menu', 'wpsci_add_admin_menu');

// Funzione che carica la pagina di amministrazione
function wpsci_admin_page() {
    ?>
    <div class="wrap">
        <h1>Configurazione SPID e CIE</h1>
        <form method="post" action="options.php">
            <?php
            // Questo genera i campi di configurazione
            settings_fields('wpsci_options_group'); // Campo nascosto per il gruppo di opzioni
            do_settings_sections('wpsci_admin_page'); // Mostra i campi registrati nella sezione
            submit_button(); // Bottone per salvare le modifiche
            ?>
        </form>
    </div>
    <?php
}

// Funzione di callback per la sezione
function wpsci_general_section_callback() {
    echo 'Inserisci i dettagli di configurazione per SPID e CIE.';
}

// Funzione di callback per il campo "Entity ID"
function wpsci_entity_id_field_callback() {
    $options = get_option('wpsci_options');
    $entity_id = isset($options['entity_id']) ? esc_attr($options['entity_id']) : '';
    echo '<input type="text" name="wpsci_options[entity_id]" value="' . $entity_id . '" />';
}