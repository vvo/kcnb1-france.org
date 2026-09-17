<?php
/*
Plugin Name: OVH SMTP
Description: Sends every wp_mail() through OVH authenticated SMTP with a fixed From. Inactive unless OVH_SMTP_USER and OVH_SMTP_PASSWORD are defined in wp-config.php.
Version: 1.0
*/

if (!defined('OVH_SMTP_USER') || !defined('OVH_SMTP_PASSWORD')) {
    return;
}

add_action('phpmailer_init', function ($phpmailer) {
    $phpmailer->isSMTP();
    $phpmailer->Host = 'smtp.mail.ovh.net';
    $phpmailer->Port = 465;
    $phpmailer->SMTPSecure = 'ssl';
    $phpmailer->SMTPAuth = true;
    $phpmailer->Username = OVH_SMTP_USER;
    $phpmailer->Password = OVH_SMTP_PASSWORD;
});

add_filter('wp_mail_from', fn() => OVH_SMTP_USER, PHP_INT_MAX);
add_filter('wp_mail_from_name', fn() => 'Association KCNB1 France', PHP_INT_MAX);
