<?php

$scFiles = new NCS_Cart_Files();
$key = sanitize_text_field(get_query_var('sc-download'));

$check_login = get_option('sc_login_to_download');
if($check_login && !is_user_logged_in()) {
    if($pid = get_option('_sc_myaccount_page_id')) {
        wp_redirect( get_permalink($pid) );
    } else {
        esc_html_e('Unauthorized, please log in to download.','ncs-cart');
    }
    exit;
}

if($download = $scFiles->get_download_by_key($key)) {

    do_action('sc_before_show_download', $download);
    
    NCS_Cart_Files::log_download($download);

    if (!$download->file_redirect) {
        header("Content-type: application/x-file-to-save"); 
        header("Content-Disposition: attachment; filename=".basename($download->path));
        ob_end_clean();
        //header("Location: {$_SERVER["HTTP_REFERER"]}");
        readfile($download->path);
    } else {
        header('Location: '.$download->path);
    }
    
    exit;
} else {
    esc_html_e('Sorry, this file is no longer available.','ncs-cart');
    exit;
}