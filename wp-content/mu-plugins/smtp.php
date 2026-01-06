<?php
require_once __DIR__ . '/../../secrets.php';

add_action('phpmailer_init', function($phpmailer) use ($main_domain) {
    $phpmailer->isSMTP();
    $phpmailer->Host = '127.0.0.1';
    $phpmailer->Port = 25;
    $phpmailer->SMTPAuth = false;
    $phpmailer->SMTPAutoTLS = false;
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $phpmailer->SMTPDebug = 2;
        $phpmailer->Debugoutput = 'error_log';
    }

    $site_host = parse_url(get_site_url(), PHP_URL_HOST);
    $from_email = "noreply+{$site_host}@{$main_domain}";
    $from_name = get_bloginfo('name');
    $phpmailer->setFrom($from_email, $from_name);
});
