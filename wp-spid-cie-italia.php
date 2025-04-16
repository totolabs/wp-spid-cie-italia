<?php
/*
 * Plugin Name: WP SPID CIE Italia
 * Description: Plugin WordPress per autenticazione con SPID e CIE, basato su WP SPID Italia e SimpleSAMLphp
 * Version: 1.1.0
 * Author: Totolabs Srl
 * Author URI: https://totolabs.it
 * License: GPL2
*/

// Inclusione file
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/frontend.php';

// Plugin activation & deactivation
register_activation_hook(__FILE__, 'wpsci_plugin_activate');
register_deactivation_hook(__FILE__, 'wpsci_plugin_deactivate');

function wpsci_plugin_activate() {
    // codice opzionale in fase di attivazione
}

function wpsci_plugin_deactivate() {
    // codice opzionale in fase di disattivazione
}
