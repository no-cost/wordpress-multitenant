<?php
require_once __DIR__ . '/../secrets.php';

add_action('phpmailer_init', function($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = '127.0.0.1';
    $phpmailer->Port = 25;
    $phpmailer->SMTPAuth = false;

    $site_host = parse_url(get_site_url(), PHP_URL_HOST);
    $phpmailer->From = "noreply+{$site_host}@{$main_domain}";
    $phpmailer->FromName = get_bloginfo('name');
});
