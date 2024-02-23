<?php
    global $wpdb, $sc_currency_symbol;  

    $post_id = $order->id;
    $sub = $order->get_subscription();

    if($pid = get_post_meta( $post_id, '_sc_us_parent', true )) {
        $post_id = $pid;
    } else if($pid = get_post_meta( $post_id, '_sc_us_parent', true )) {
        $post_id = $pid;
    }
    $image_file = get_option('_sc_company_logo');

    $order = new ScrtOrder($post_id);
    $order = apply_filters('studiocart_order', $order);
    $sub = $order->get_subscription();

    $invoice_number = $post_id;
    $prefix = '';
    if(get_option('_sc_enable_invoice_number') && $order->invoice_number){
        $invoice_number = $order->invoice_number;
    }
    $order = (object) $order->get_data();
    $total = 0;
    $font = 'Arial, Helvetica, sans-serif;';

    if(isset($order->main_offer)) { // backwards compatibility
        if(isset($order->main_offer["plan"]->initial_payment)) {
            $order->main_offer_amt = $order->main_offer["plan"]->initial_payment;
        }
    }

    $company_name = get_option('_sc_company_name');
    $company_address = get_option('_sc_company_address');
    $notes = get_option('_sc_invoice_notes');
    
    $upload_dir = wp_upload_dir( );
    $basedir = $upload_dir['basedir'];
    $baseurl = $upload_dir['baseurl'];
    if($image_file) {
        $image_file = str_replace($baseurl,$basedir,$image_file);
        $image_file = base64_encode(file_get_contents($image_file));
        $image_file = '<img src="data:image/png;base64,'. $image_file.'" alt="'.$company_name.'" style="max-width:250px;margin-bottom:40px">';
    } else {
        $image_file = '';
    }
    $html = '<table style="width: 100%;font-size: 13px;line-height: 18px;color:#3e3e48;" autosize="1">
        <tr class="top">
            <td class="title" style="width: 30%; font-family:'.$font.';">'. $image_file . '<br>'; 

                $html .= '<span style="color:#818d90; font-weight:bold;font-family:'.$font.';">'.esc_attr__('Bill To', 'ncs-cart').':</span><br>
                    '.$order->customer_name.'<br>';
                    if(isset($order->company)){ $html .= $order->company.'<br>'; }
                    if(isset($order->email)){ $html .= $order->email.'<br>'; }
                    if(isset($order->phone)){ $html .= $order->phone.'<br>'; }
                    $address = sc_format_order_address($order);
                    if($address) {
                        $html .= '<br>'.$address;
                    }

                    if(!empty($order->vat_number)){
                        $html .= '<br>'.esc_attr__(apply_filters("sc_vat_title","VAT Number"), 'ncs-cart').' '.$order->vat_number;
                    }
		
		    $html .= '</td>
            <td valign="top" style="width: 70%;text-align: right;clear:both;">
                <p style="font-size: 46px;color: rgb(165,179,183);line-height: 2em;font-family:'.$font.';">'.esc_attr__('Invoice', 'ncs-cart').'</p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:'.$font.';">'.esc_attr__('Invoice Number', 'ncs-cart').'</span>
                    <br/>
                    <span style="font-family:'.$font.';">'.  $invoice_number.'</span>
                </p>
                <p>
                    <span style="color:#818d90; font-weight:bold;font-family:'.$font.';">'.esc_attr__('Issue Date', 'ncs-cart').'</span>
                    <br/>
                    <span style="font-family:'.$font.';">'. $order->date.'</span>
                </p>
                <p style="font-family:'.$font.';">
                    <span style="color:#818d90; font-weight:bold;text-transform: capitalize;">'.$company_name.'</span><br>
                    <span style="font-family:'.$font.';">'.nl2br($company_address).'</span>
                </p>
            </td>
        </tr>
        <tr class="information">
            <td colspan="2">
                <table style="margin-top:20px;" width="100%" cellspacing="0" cellpadding="13">
                    <tr class="heading">
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:'.$font.';" width="50%">'.esc_attr__('Description', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:'.$font.';">'.esc_attr__('Qty', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:'.$font.';">'.esc_attr__('Price', 'ncs-cart').'</td>
                        <td style="border-bottom:0.75px solid #6f7b7e; color:#818d90; font-weight:bold;font-family:'.$font.';" align="right">'.esc_attr__('Amount', 'ncs-cart').'</td>                                   
                    </tr>';
                    $bg = ['','background: #f1f6f7;'];
                    $i=0;

                    $items = sc_get_item_list($order->id, $full=true, $qty_col=true);
                    foreach($items['items'] as $item) {
                        
                        $item['item_type'] = $item['item_type'] ?? '';
                        $item['subtotal'] = isset($item['subtotal']) ? $item['subtotal'] : '';
                        $item['unit_price'] = isset($item['unit_price']) ? $item['unit_price'] : '';

                        $html .= '<tr>
                                <td style="font-family:'.$font.';'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.$item['product_name'];

                                if($item['price_name']) {
                                    $html .= ' - ' . $item['price_name'];
                                }

                                if(isset($order->coupon) && !isset($order->coupon_id)) {
                                    $html .= ' (coupon ' . $order->coupon .')';
                                }

                                if(isset($item['subscription_id'])) {
                                    $year = sc_maybe_format_date($order->date, 'Y');
                                    if($year == sc_maybe_format_date($sub->sub_next_bill_date, 'Y')) {
                                        switch(get_option('date_format')) {
                                            case 'F j, Y':
                                                $start = sc_maybe_format_date($order->date, 'F j');
                                                break;
                                            case 'Y-m-d':
                                                $start = sc_maybe_format_date($order->date, 'Y-m');
                                                break;
                                            case 'm/d/Y':
                                            case 'd/m/Y':
                                                $start = str_replace('/'.$year, '', $order->date);
                                                break;
                                            default:
                                                $start = str_replace($year, '', $order->date);
                                                break;
                                        }
                                        
                                    }
                                    $html .= '<br>'.$start. ' - ' . sc_maybe_format_date($sub->sub_next_bill_date);
                                    if($sub->sign_up_fee && floatval($sub->sign_up_fee) > 0) {
                                        $item['subtotal'] -= $sub->sign_up_fee;
                                        $item['unit_price'] -= ($sub->sign_up_fee / $sub->quantity);
						            }

                                }

                                $html .= '</td>
                                <td style="font-family:'.$font.';'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.$item['quantity'].'</td>
                                <td style="font-family:'.$font.';'.$bg[$i%2].'border-bottom:0px solid #a5b3b7">'.sc_format_price($item['unit_price']).'</td>
                                <td style="font-family:'.$font.';'.$bg[$i%2].'border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($item['subtotal']).'</td>
                            </tr>';
                        $i++;

                        if(isset($item['subscription_id']) && $sub->sign_up_fee && $sub->sign_up_fee > 0) {
                            $html .= '<tr>
                            <td style="font-family:'.$font.';'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.esc_attr__('Sign-up Fee', 'ncs-cart').'</td>
                            <td style="font-family:'.$font.';'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.$sub->quantity.'</td>
                            <td style="font-family:'.$font.';'.$bg[$i%2].';border-bottom:0px solid #a5b3b7">'.sc_format_price($sub->sign_up_fee / $sub->quantity).'</td>
                            <td style="font-family:'.$font.';'.$bg[$i%2].';border-bottom:0px solid #a5b3b7; font-weight: bold;" align="right">'.sc_format_price($sub->sign_up_fee).'</td></tr>';
                            $i++;
                        }
                    }

                    $html .= '
                    <tr>
                        <td style="font-family:'.$font.';border-top:0.75px solid #6f7b7e" colspan="2"></td>
                        <td style="font-family:'.$font.';border-top:0.75px solid #6f7b7e; font-weight: bold;">'.esc_attr__('Subtotal', 'ncs-cart').'</td>
                        <td style="font-family:'.$font.';border-top:0.75px solid #6f7b7e; font-weight: bold;" align="right">'.sc_format_price($items['subtotal']['total_amount']).'</td>
                    </tr>';
                    if(isset($items['discounts'])) {
                        foreach($items['discounts'] as $item) {
                            $html .= '<tr>
                                <td colspan="2"></td>
                                <td style="font-family:'.$font.';background:#f1f6f7;">'
                                    .esc_attr__('Coupon:', 'ncs-cart') . ' ' .$item['product_name'].'
                                </td>
                                <td style="font-family:'.$font.';background:#f1f6f7;" align="right">
                                    -'.sc_format_price($item['total_amount']).'
                                </td>
                            </tr>';
                        }
                    }
                    if(isset($items['shipping'])) {
                        $html .= '<tr>
                            <td colspan="2"></td>
                            <td style="font-family:'.$font.';background:#f1f6f7;">'
                                .$items['shipping']['product_name'].'
                            </td>
                            <td style="font-family:'.$font.';background:#f1f6f7;" align="right">
                                '.sc_format_price($items['shipping']['total_amount']).'
                            </td>
                        </tr>';
                    }
                    if(isset($items['tax']) && $items['tax']['product_name']) {
                        $colspan = ($items['tax']['total_amount']) ? '' : 'colspan="2"';
                        $html .= '<tr>
                            <td colspan="2"></td>
                            <td style="font-family:'.$font.';background:#f1f6f7;" '.$colspan.'>'
                                .$items['tax']['product_name'].'
                            </td>';
                            if($items['tax']['total_amount']) {
                                $html .= '<td style="font-family:'.$font.';background:#f1f6f7;" align="right">
                                '.sc_format_price($items['tax']['total_amount']).'
                            </td>';
                            }
                        $html .= '</tr>';
                    }
                    $html .= '
                    <tr>
                        <td colspan="2"></td>
                        <td style="font-family:'.$font.';background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;">'.esc_attr__('Amount due', 'ncs-cart').'</td>
                        <td style="font-family:'.$font.';background:#e2e9eb; border-top:1px solid #fff; border-bottom:0.75px solid #6f7b7e; font-weight: bold;" align="right">'.sc_format_price($items['total']['total_amount']).'</td>
                    </tr>';

                    if($notes) {
                        $html .= '<tr>
                        <td colspan="4" style="font-family:'.$font.'; "><br><br><br>
                        <strong style="">'.esc_attr__('Notes / Terms', 'ncs-cart').'</strong><br>
                        '.wpautop(wp_specialchars_decode($notes)).'</td>
                    </tr>';
                    }
                $html .= '</table>
            </td>
        </tr>
    </tbody></table>';