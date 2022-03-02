<?php 
    global $wpdb, $sc_currency_symbol;  
    $post_id = intval($_REQUEST['id']);

    $image_file = get_option('_sc_company_logo');
    $order = sc_setup_order($post_id);
    $company_name = get_option('_sc_company_name');
    $company_address = get_option('_sc_company_address');

    if($image_file) {
        $image_file = '<img src="'. $image_file.'" alt="'.$company_name.'" style="width: 35%;">';
    } else {
        $image_file = '';
    }

    $html = '<table style="width: 100%;line-height: 18px;color:#3e3e48;padding: 50px;style="font-size: 14px;">
    <tr><td>
        <table id="email-header" width="100%">
            <tr class="top">
                <td class="title" colspan="2" align="center">'. $image_file.'
                    <h1 style="font-size: 26px;margin:20px;font-weight:bold;">'.do_action('sc_email_headline', $type, $post_id).'</h1>
                    <p style="font-size: 14px;">'.do_action('sc_email_body', $type, $post_id).'</p>
                </td>
            </tr>
        </table>
        
        <table id="email-footer" style="margin-top: 60px;">
            <tr><td align="center">
            <p style="font-size: 12px;">Site Name - Powered by Studiocart</p>
            </td></tr>
        </table>
    </td></tr></table>';