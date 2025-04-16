# WP SPID-CIE Italia

Plugin WordPress per l'autenticazione degli utenti tramite SPID e CIE, usando SimpleSAMLphp.

## Funzionalità

- Autenticazione via SPID e CIE
- Login utente automatico
- Creazione utente WordPress in caso non esista
- Certificati generati automaticamente
- Configurazione semplificata via pannello admin

## Requisiti

- WordPress 5.5+
- PHP 7.4+
- Estensione `openssl` attiva
- Hosting che consenta `exec()` e scrittura in `cert/`

## Installazione

1. Carica la cartella `wp-spid-cie-italia` in `wp-content/plugins`
2. Inserisci la libreria SimpleSAMLphp in `wp-spid-cie-italia/simplesamlphp`
3. Attiva il plugin
4. Vai in **SPID e CIE** nel menu admin per configurare

## Note

- I certificati sono salvati nella cartella `cert/` e rigenerati a ogni salvataggio.
- È possibile sostituirli con certificati reali.
