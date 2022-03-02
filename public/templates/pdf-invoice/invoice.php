<?php
    global $wpdb, $sc_currency_symbol;  
    $post_id = intval($_REQUEST['sc-invoice']);
    if($pid = get_post_meta( $post_id, '_sc_us_parent', true )) {
        $post_id = $pid;
    } else if($pid = get_post_meta( $post_id, '_sc_us_parent', true )) {
        $post_id = $pid;
    }
    $image_file = get_option('_sc_company_logo');

    $order = new ScrtOrder($post_id);
    $order = (object) $order->get_data();
    $total = 0;

    if(isset($order->main_offer)) { // backwards compatibility
        if(isset($order->main_offer["plan"]->initial_payment)) {
            $order->main_offer_amt = $order->main_offer["plan"]->initial_payment;
        }
    }

    $company_name = get_option('_sc_company_name');
    $company_address = get_option('_sc_company_address');
    $upload_dir = wp_upload_dir( );
    $basedir = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];
    if($image_file) {
        $image_file = str_replace($baseurl,$basedir,$image_file);
        $image_file = base64_encode(file_get_contents($image_file));
        $image_file = '<img src="data:image/png;base64,'. $image_file.'" alt="'.$company_name.'" style="width:250px;">';
    } else {
        $image_file = '';
    }
    $html = '<table style="width: 100%;font-size: 13px;line-height: 18px;color:#3e3e48;" autosize="1">
        <tr class="top">
            <td class="title" style="width: 30%;">'. $image_file.'</td>
            <td valign="top" style="width: 70%;text-align: right;clear:both;">
                <p style="font-size: 46px;color: rgb(165,179,183);line-height: 2em;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Invoice', 'ncs-cart').'</p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Invoice Number', 'ncs-cart').'</span>
                    <br/>
                    <span style="font-family:Arial, Helvetica, sans-serif;">'. $post_id.'</span>
                </p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Issue Date', 'ncs-cart').'</span>
                    <br/>
                    <span style="font-family:Arial, Helvetica, sans-serif;">'. $order->date.'</span>
                </p>
                <p style="font-family:Arial, Helvetica, sans-serif;">
                    <span style="color:#818d90; font-weight:bold;text-transform: capitalize;">'.$company_name.'</span><br>
                    <span style="font-family:Arial, Helvetica, sans-serif;">'.nl2br($company_address).'</span>
                </p>
            </td>
        </tr>
        <tr class="information">
            <td colspan="2" style="font-family:Arial, Helvetica, sans-serif;">
                <span style="color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Bill To', 'ncs-cart').':</span><br>
                '.$order->customer_name.'<br>';
                if(isset($order->email)){ $html .= $order->email.'<br>'; }
                if(isset($order->phone)){ $html .= $order->phone.'<br>'; }
                $address = sc_format_order_address($order);
                if($address) {
                    $html .= '<br>'.$address;
                }
            $html .= '</td>
        </tr>
        <tr class="information">
            <td colspan="2">
                <table style="margin-top:20px;" width="100%" cellspacing="0" cellpadding="13">
                    <tr class="heading">
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;" width="50%">'.esc_attr__('Description', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Qty', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;">'.esc_attr__('Price', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:Arial, Helvetica, sans-serif;" align="right">'.esc_attr__('Amount', 'ncs-cart').'</td>                                   
                    </tr>';
                    $bg = ['','background: #f1f6f7;'];
                    $i=0;
                    // main offer
                    $html .= '<tr>
                    <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.$order->product_name;
                    if(isset($order->coupon) && !isset($order->coupon_id)) {
                        $html .= ' (coupon ' . $order->coupon .')';
                    }

                    $mo_amount = $order->main_offer_amt;

                    if(!$order->invoice_total) {
                        $total += $mo_amount;
                    }

                    $html .= '</td>
                    <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">1</td>
                    <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.sc_format_price($mo_amount).'</td>
                    <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($mo_amount).'</td></tr>';
                    if ( isset($order->custom_prices) ) { 
                        foreach($order->custom_prices as $price) {
                            $i++;
                            $html .= '<tr>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.$price['label'].'</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.$price['qty'].'</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.sc_format_price($price['price']/$price['qty']).'</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($price['price']).'</td></tr>';
                        }
                    }
                    if (!empty($order->order_bumps) && is_array($order->order_bumps)) {
                        foreach($order->order_bumps as $order_bump){
                            $i++;
                            $html .= '<tr>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.$order_bump['name'].'</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">1</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.sc_format_price($order_bump['amount']).'</td>
                            <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($order_bump['amount']).'</td></tr>';
                        }
                    } else if ( isset($order->bump_id) ) { 
                        $i++;
                        $html .= '<tr>
                        <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.sc_get_public_product_name($order->bump_id).'</td>
                        <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">1</td>
                        <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.sc_format_price($order->bump_amt).'</td>
                        <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].';border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($order->bump_amt).'</td></tr>';
                    }
                    if (is_array($order->order_child)){
                        foreach($order->order_child as $child_order){
                            $i++;
                            $html .= '<tr>
                                <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'">'.$child_order['product_name'].'</td> 
                                <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'">1</td>
                                <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'">'.sc_format_price($child_order['amount']).'</td>
                                <td style="font-family:Arial, Helvetica, sans-serif;'.$bg[$i%2].'font-weight:bold" align="right">'.sc_format_price($child_order['amount']).'</td>
                            </tr>';
                            
                            if(!$order->invoice_total) {
                                $total += $child_order['amount'];
                            }
                        }
                    }
                    if(!$order->invoice_subtotal) {
                        $order->invoice_subtotal = $total;
                    }
                    $html .= '
                    <tr>
                        <td style="font-family:Arial, Helvetica, sans-serif;border-top:0.75px solid #6f7b7e" colspan="2"></td>
                        <td style="font-family:Arial, Helvetica, sans-serif;border-top:0.75px solid #6f7b7e; font-weight: bold;">'.esc_attr__('Subtotal', 'ncs-cart').'</td>
                        <td style="font-family:Arial, Helvetica, sans-serif;border-top:0.75px solid #6f7b7e; font-weight: bold;" align="right">'.sc_format_price($order->invoice_subtotal).'</td>
                    </tr>';
                    if($order->coupon) {
                    $html .= '<tr>
                        <td colspan="2"></td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#f1f6f7;">'
                            .$order->coupon_id.' '.$order->coupon['description'].'
                        </td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#f1f6f7;" align="right">
                            -'.sc_format_price($order->coupon['discount_amount']).'
                        </td>
                    </tr>';
                    }
                    if($order->tax_amount) {
                    $html .= '<tr>
                        <td colspan="2"></td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#f1f6f7;">'
                            .$order->tax_desc.'
                        </td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#f1f6f7;" align="right">
                            '.sc_format_price($order->tax_amount).'
                        </td>
                    </tr>';
                    }
                    if(!$order->invoice_total) {
                        $order->invoice_total = $total;
                    }
                    $html .= '
                    <tr>
                        <td colspan="2"></td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;">'.esc_attr__('Amount due', 'ncs-cart').'</td>
                        <td style="font-family:Arial, Helvetica, sans-serif;background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;" align="right">'.sc_format_price($order->invoice_total).'</td>
                    </tr>
                </table>
            </td>
        </tr>
    </tbody></table>';