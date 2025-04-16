<?php
/*
Plugin Name: WP SPID CIE Italia
Description: Login SPID e CIE per WordPress, basato su WP SPID Italia con integrazione SimpleSAMLphp.
Version: 1.0.0
Author: Totolabs + ChatGPT
*/

define('WPSCI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSCI_CONFIG_DIR', WPSCI_PLUGIN_DIR . 'config/');
define('WPSCI_CERT_DIR', WPSCI_PLUGIN_DIR . 'cert/');
define('WPSCI_BACKUP_DIR', WPSCI_CONFIG_DIR . 'backup/');
define('WPSCI_SAML_DIR', WPSCI_PLUGIN_DIR . 'simplesamlphp/');

// Crea cartelle se non esistono
function wpsci_maybe_create_directories() {
    $dirs = [WPSCI_CONFIG_DIR, WPSCI_BACKUP_DIR, WPSCI_CERT_DIR];
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
register_activation_hook(__FILE__, 'wpsci_maybe_create_directories');

// Aggiunge pagina admin
require_once WPSCI_PLUGIN_DIR . 'admin/settings-page.php';
