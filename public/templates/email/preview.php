<?php
if (isset($_GET['type'])) {
    preg_match('/\[([a-z_]*?)\]/', $_GET['type'], $match);
    $type = $match[1];
} else {
    $type = 'confirmation';
} 

$em = ($type == 'registration') ? '_sc_'.$type.'_email_' : '_sc_email_'.$type.'_';
$headline = ($type == 'registration') ? get_option('_sc_registration_subject') : get_option($em.'headline');
$body = get_option($em.'body') ?? '';

$order_info = sc_test_order_data();
$atts = array(
    'type' => $type,
    'order_info' => $order_info,
    'headline' => $headline,
    'body' => sc_personalize($body,$order_info),
);

$body = sc_get_email_html($atts);
echo html_entity_decode($body, ENT_QUOTES, "UTF-8");   