<?php

add_action('admin_menu', 'wpsci_add_admin_menu');
add_action('admin_init', 'wpsci_settings_init');

function wpsci_add_admin_menu() {
    add_menu_page(
        'WP SPID CIE Italia',
        'SPID CIE',
        'manage_options',
        'wp-spid-cie-italia',
        'wpsci_plugin_settings_page'
    );
}

function wpsci_settings_init() {
    $option_group = 'wpsci_plugin_settings';
    $option_name = 'wpsci_options';

    add_settings_section(
        'wpsci_plugin_section',
        'Impostazioni SPID CIE',
        null,
        $option_group
    );

    $fields = [
        'country_name' => ['Codice Nazione', 'Codice del Paese della sede legale. Es: IT'],
        'state_or_province_name' => ['Provincia', 'Provincia della sede legale. Es: Napoli'],
        'locality_name' => ['Località', 'Comune della sede legale. Es: Napoli'],
        'sp_org_name' => ['Organizzazione', 'Denominazione completa e per esteso del SP, come su IPA. Es: Ordine degli Ingegneri della Provincia di Napoli'],
        'sp_org_display_name' => ['Nome abbreviato', 'Denominazione abbreviata e/o con acronimi. Es: Ordine Ingegneri Napoli'],
        'sp_entityid' => ['Sito internet', 'Es: https://www.ordineingegnerinapoli.com/'],
        'email_address' => ['Email', 'Email del SP. Es: info@ordineingegnerinapoli.it'],
        'sp_contact_ipa_code' => ['Codice IPA', 'Es: oring_na'],
        'sp_contact_fiscal_code' => ['Codice fiscale', 'Es: 80066170632'],
        'sp_contact_email' => ['Email contatto tecnico', 'Email del contatto tecnico. Es: tua@email.it'],
        'sp_contact_phone' => ['Telefono contatto tecnico', 'Telefono del contatto tecnico. Es: 0123456789'],
    ];

    register_setting($option_group, $option_name); // salva come array

    foreach ($fields as $field => [$label, $description]) {
        add_settings_field(
            "wpsci_{$field}",
            __($label, 'wpsci'),
            function () use ($field, $description, $option_name) {
                $options = get_option($option_name);
                $value = isset($options[$field]) ? $options[$field] : '';
                echo "<input type='text' name='{$option_name}[{$field}]' value='" . esc_attr($value) . "' class='regular-text' />";
                echo "<p class='description'>{$description}</p>";
            },
            $option_group,
            'wpsci_plugin_section'
        );
    }
}


function wpsci_plugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>Impostazioni WP SPID CIE Italia</h1>
        <form action="<?php echo admin_url('options.php'); ?>" method="post">
            <?php
            settings_fields('wpsci_plugin_settings');
            do_settings_sections('wpsci_plugin_settings');
            submit_button('Salva impostazioni');
            ?>
        </form>
        <hr>
        <form method="post">
            <?php submit_button('Rigenera certificati e configurazione', 'secondary', 'wpsci_regenerate_certificates'); ?>
        </form>
    </div>
    <?php
}

// Generazione automatica su aggiornamento
$fields_to_watch = [
    'country_name',
    'state_or_province_name',
    'locality_name',
    'sp_org_name',
    'sp_org_display_name',
    'sp_entityid',
    'email_address',
    'sp_contact_ipa_code',
    'sp_contact_fiscal_code',
    'sp_contact_email',
    'sp_contact_phone',
];

foreach ($fields_to_watch as $field) {
    add_action("update_option_wpsci_{$field}", 'wpsci_generate_certificates_on_save', 10, 2);
}

//aggiunge voce menu certificati
add_action('admin_menu', 'certificato_custom_admin_menu');

function certificato_custom_admin_menu() {
    add_menu_page(
        'Certificati ECM',
        'Certificati ECM',
        'manage_options',
        'certificati-ecm',
        'certificato_custom_admin_page',
        'dashicons-awards',
        20
    );
}

function certificato_custom_admin_page() {
    $upload_dir = wp_upload_dir();
    $certificati_dir = trailingslashit($upload_dir['basedir']) . 'certificati_ecm';

    // Verifica directory
    $is_writable = is_writable($certificati_dir);
    
    echo '<div class="wrap"><h1>Gestione Certificati ECM</h1>';
    
    // Stato directory
    if (!file_exists($certificati_dir)) {
        echo '<p style="color: red;">❌ La cartella dei certificati non esiste: <code>' . esc_html($certificati_dir) . '</code></p>';
    } elseif (!$is_writable) {
        echo '<p style="color: orange;">⚠️ La cartella dei certificati esiste ma <strong>non è scrivibile</strong>.</p>';
    } else {
        echo '<p style="color: green;">✅ Cartella certificati scrivibile: <code>' . esc_html($certificati_dir) . '</code></p>';
        
        // Elenco certificati
        $files = glob($certificati_dir . '/*.pdf');
        if (!empty($files)) {
            echo '<h2>Certificati generati:</h2><ul>';
            foreach ($files as $file) {
                $url = trailingslashit($upload_dir['baseurl']) . 'certificati_ecm/' . basename($file);
                echo '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html(basename($file)) . '</a></li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nessun certificato generato finora.</p>';
        }
    }

    echo '</div>';
}
