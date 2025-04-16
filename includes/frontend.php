<?php
if (!defined('ABSPATH')) exit;

function wpsci_add_login_buttons() {
    $spid_url = home_url('/acs.php?provider=spid');
    $cie_url  = home_url('/acs.php?provider=cie');
    echo '<div style="margin:20px 0;">';
    echo '<a href="' . esc_url($spid_url) . '" class="button button-primary">Login con SPID</a> ';
    echo '<a href="' . esc_url($cie_url) . '" class="button">Login con CIE</a>';
    echo '</div>';
}
add_action('login_form', 'wpsci_add_login_buttons');
