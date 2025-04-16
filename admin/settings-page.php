<?php
add_action('admin_menu', function () {
    add_options_page('WP SPID CIE Italia', 'SPID/CIE Login', 'manage_options', 'wpsci-settings', 'wpsci_settings_page');
});

function wpsci_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurazione SPID / CIE</h1>
        <?php if (isset($_POST['wpsci_save'])) wpsci_handle_form(); ?>
        <form method="post">
            <table class="form-table">
                <tr><th><label>Entity ID</label></th><td><input type="text" name="entity_id" value="<?= esc_attr(get_option('wpsci_entity_id', 'https://example.org')) ?>" class="regular-text"></td></tr>
                <tr><th><label>IDP SPID</label></th><td><input type="text" name="idp_spid" value="<?= esc_attr(get_option('wpsci_idp_spid', 'https://idp.spid.gov.it')) ?>" class="regular-text"></td></tr>
                <tr><th><label>IDP CIE</label></th><td><input type="text" name="idp_cie" value="<?= esc_attr(get_option('wpsci_idp_cie', 'https://idserver.servizicie.interno.gov.it')) ?>" class="regular-text"></td></tr>
                <tr><th><label>Base Path Temporanei</label></th><td><input type="text" name="tmp_path" value="<?= esc_attr(get_option('wpsci_tmp_path', '/tmp')) ?>" class="regular-text"></td></tr>
            </table>
            <?php submit_button('Salva e rigenera configurazione', 'primary', 'wpsci_save'); ?>
        </form>
    </div>
    <?php
}

// Salva opzioni, genera certificati e file config
function wpsci_handle_form() {
    $entity_id = sanitize_text_field($_POST['entity_id']);
    $idp_spid = sanitize_text_field($_POST['idp_spid']);
    $idp_cie = sanitize_text_field($_POST['idp_cie']);
    $tmp_path = sanitize_text_field($_POST['tmp_path']);

    update_option('wpsci_entity_id', $entity_id);
    update_option('wpsci_idp_spid', $idp_spid);
    update_option('wpsci_idp_cie', $idp_cie);
    update_option('wpsci_tmp_path', $tmp_path);

    wpsci_generate_certificates();
    wpsci_write_config_files($entity_id, $idp_spid, $idp_cie, $tmp_path);
    echo '<div class="updated"><p>Configurazione salvata e file rigenerati.</p></div>';
}

function wpsci_generate_certificates() {
    $keys = ['spid', 'cie'];
    foreach ($keys as $key) {
        $crt_file = WPSCI_CERT_DIR . "{$key}.crt";
        $key_file = WPSCI_CERT_DIR . "{$key}.key";
        if (!file_exists($crt_file) || !file_exists($key_file)) {
            $dn = [
                "countryName" => "IT",
                "organizationName" => "WP SPID CIE Italia",
                "commonName" => strtoupper($key)
            ];
            $privkey = openssl_pkey_new(["private_key_bits" => 2048]);
            $csr = openssl_csr_new($dn, $privkey);
            $x509 = openssl_csr_sign($csr, null, $privkey, 365);
            openssl_x509_export_to_file($x509, $crt_file);
            openssl_pkey_export_to_file($privkey, $key_file);
        }
    }
}

function wpsci_write_config_files($entity_id, $idp_spid, $idp_cie, $tmp_path) {
    $ts = date('Ymd-His');
    $config_path = WPSCI_CONFIG_DIR . 'config.php';
    $auth_path = WPSCI_CONFIG_DIR . 'authsources.php';

    // Backup
    @copy($config_path, WPSCI_BACKUP_DIR . "config_$ts.php");
    @copy($auth_path, WPSCI_BACKUP_DIR . "authsources_$ts.php");

    // config.php
    $config = <<<PHP
<?php
\$config = [
    'baseurlpath' => '/wp-content/plugins/wp-spid-cie-italia/simplesamlphp/www/',
    'certdir' => __DIR__ . '/../cert/',
    'loggingdir' => '$tmp_path',
    'datadir' => '$tmp_path',
    'tempdir' => '$tmp_path',
    'logging.level' => SimpleSAML\Logger::WARNING,
    'technicalcontact_email' => 'admin@example.org',
    'secretsalt' => '1234567890abcdef',
    'timezone' => 'Europe/Rome',
];
PHP;

    // authsources.php
    $authsources = <<<PHP
<?php
\$config = [
    'default-sp-spid' => [
        'saml:SP',
        'entityID' => '$entity_id',
        'idp' => '$idp_spid',
        'privatekey' => 'spid.key',
        'certificate' => 'spid.crt',
    ],
    'default-sp-cie' => [
        'saml:SP',
        'entityID' => '$entity_id',
        'idp' => '$idp_cie',
        'privatekey' => 'cie.key',
        'certificate' => 'cie.crt',
    ],
];
PHP;

    file_put_contents($config_path, $config);
    file_put_contents($auth_path, $authsources);
}

//codice per pagina login
add_shortcode('wpsci_login', 'wpsci_render_login_form');

function wpsci_render_login_form() {
    ob_start();
    if (is_user_logged_in()) {
        echo '<p>Sei già autenticato come <strong>' . wp_get_current_user()->display_name . '</strong>.</p>';
        echo '<p><a href="' . wp_logout_url(home_url()) . '">Logout</a></p>';
    } else {
        ?>
        <h2>Login</h2>
        <form method="post" action="<?= esc_url(wp_login_url()) ?>">
            <p><label for="user_login">Username<br><input type="text" name="log" id="user_login" class="input"></label></p>
            <p><label for="user_pass">Password<br><input type="password" name="pwd" id="user_pass" class="input"></label></p>
            <p><input type="submit" value="Login" class="button button-primary"></p>
        </form>

        <hr>
        <h3>Oppure accedi con:</h3>
        <p>
            <a href="<?= plugin_dir_url(__FILE__) . 'simplesamlphp/module.php/core/authenticate.php?as=default-sp-spid' ?>" class="button">SPID</a>
            <a href="<?= plugin_dir_url(__FILE__) . 'simplesamlphp/module.php/core/authenticate.php?as=default-sp-cie' ?>" class="button">CIE</a>
        </p>
        <?php
    }
    return ob_get_clean();
}