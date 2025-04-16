<?php

function wpsci_generate_certificates() {
    $cert_dir = plugin_dir_path(__DIR__) . 'cert/';
    $key_path = $cert_dir . 'private.key';
    $crt_path = $cert_dir . 'public.crt';

    if (!file_exists($cert_dir)) {
        mkdir($cert_dir, 0755, true);
    }

    if (file_exists($key_path)) {
        copy($key_path, $cert_dir . 'private.key.bak.' . time());
    }
    if (file_exists($crt_path)) {
        copy($crt_path, $cert_dir . 'public.crt.bak.' . time());
    }

    $config = [
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    $privkey = openssl_pkey_new($config);

    $dn = [
        "countryName" => get_option('wpsci_country_name', 'IT'),
        "stateOrProvinceName" => get_option('wpsci_state_or_province_name', ''),
        "localityName" => get_option('wpsci_locality_name', ''),
        "organizationName" => get_option('wpsci_sp_org_name', ''),
        "commonName" => parse_url(get_option('wpsci_entity_id', ''), PHP_URL_HOST),
        "emailAddress" => get_option('wpsci_email_address', ''),
    ];

    $csr = openssl_csr_new($dn, $privkey, $config);
    $x509 = openssl_csr_sign($csr, null, $privkey, 365);

    openssl_pkey_export($privkey, $privkey_out);
    openssl_x509_export($x509, $x509_out);

    file_put_contents($key_path, $privkey_out);
    file_put_contents($crt_path, $x509_out);
}

function wpsci_generate_config_files() {
    $config_dir = plugin_dir_path(__DIR__) . 'cert/';
    if (!file_exists($config_dir)) {
        mkdir($config_dir, 0755, true);
    }

    $entity_id = get_option('wpsci_entity_id', '');
    $sp_config = [
        'entity_id' => $entity_id,
        'organization' => [
            'name' => get_option('wpsci_sp_org_name', ''),
            'display_name' => get_option('wpsci_sp_org_display_name', ''),
            'url' => $entity_id,
        ],
        'contacts' => [
            [
                'contact_type' => 'technical',
                'email_address' => get_option('wpsci_sp_contact_email', ''),
                'telephone_number' => get_option('wpsci_sp_contact_phone', ''),
                'fiscal_code' => get_option('wpsci_sp_contact_fiscal_code', ''),
                'ipa_code' => get_option('wpsci_sp_contact_ipa_code', ''),
            ],
        ],
        'certificate' => file_exists($config_dir . 'public.crt') ? file_get_contents($config_dir . 'public.crt') : '',
    ];

    file_put_contents($config_dir . 'sp_metadata.json', json_encode($sp_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function wpsci_generate_certificates_on_save($old_value, $value) {
    wpsci_generate_certificates();
    wpsci_generate_config_files();
}