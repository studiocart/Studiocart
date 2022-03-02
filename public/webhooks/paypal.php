<?php

// Handle the PayPal response.

// Assign posted variables to local data array.
$order_id = ''; 
$subscription_id ='0'; 

// grab Studiocart order/subscription IDs from custom meta
if(isset($_REQUEST['custom'])){ 
    $custom = $_REQUEST['custom'];
    if(is_numeric($custom) || strpos($custom,'=') !== false){ // deprecated
        $custom_id  = explode("=", $custom);            
        $order_id = $custom_id[0];  
        if(isset($custom_id[1])){
            $subscription_id = $custom_id[1];
        }
    } else {
        $custom = json_decode(stripslashes(urldecode($custom)));
        $order_id = $custom->order_id;  
        if(isset($custom->subscription_id)){
            $subscription_id = $custom->subscription_id;
        }
    }
} 

$initial_order = true;
if(isset($_REQUEST['txn_id'])){
    $retry = sc_checkTxnid($_REQUEST['txn_id'],$order_id);
} else {
    $retry = array();
    $retry[] = (object) array('ID'=>$order_id);
}

if($subscription_id) {
    $initial_order = false;
    // see if there are any other orders created under this subscription
    $args = array(
        'post_type'  => 'sc_order',
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => '_sc_subscription_id',
                'value' => $subscription_id
            )
        )
    );
    
    $sub_orders = get_posts($args);
    
    // treat as initial payment when first and only payment is failed so that new order integrations can run
    if (empty($sub_orders) || (count($sub_orders)==1 && ($retry || get_post_status($order_id) == 'failed')) ) {
        $initial_order = true;
        if (get_post_status($order_id) == 'failed') {
            if(isset($_REQUEST['txn_id']) && $_REQUEST['txn_id']){
                update_post_meta($order_id, '_sc_transaction_id', $_REQUEST['txn_id']);
            }
        }
    }
    
    // if subscr_start comes before we got a chance to update the transaction ID
    if (count($sub_orders)==1 && (!$retry && !get_post_meta($order_id, '_sc_paypal_txn_id', true) && !get_post_meta($order_id, '_sc_transaction_id', true)) ) {
        $retry = array();
        $retry[] = (object) array('ID'=>$order_id);
    }
    
    $next_bill_time = false;
    if(isset($_REQUEST['next_payment_date']) && (($timestamp = strtotime($_REQUEST['next_payment_date'])) !== false)){        
        $next_bill_time = $timestamp;
    }
}
                    
$data = [       
    'item_name' => $_REQUEST['item_name'],  
    'item_number' => $_REQUEST['item_number'],  
    'paypal_status' => $_REQUEST['payment_status'],    
    'payment_amount' => $_REQUEST['mc_gross'],  
    'payment_currency' => $_REQUEST['mc_currency'], 
    'txn_id' => isset($_REQUEST['txn_id']) ? $_REQUEST['txn_id'] : false,    
    'txn_type' => $_REQUEST['txn_type'],    
    'subscr_id' => ($_REQUEST['subscr_id'] != '') ? $_REQUEST['subscr_id'] : '',    
    'receiver_email' => $_REQUEST['receiver_email'],    
    'payer_email' => $_REQUEST['payer_email'],  
    'order_id' => intval($order_id),    
    'subscription_id' => intval($subscription_id),  
];

switch($_REQUEST['payment_status']) {
    case 'Completed':
    case 'Canceled_Reversal':
        $data['payment_status'] = 'paid';
        break;
    case 'Created':
    case 'Pending':
    case 'Processed':
        $data['payment_status'] = 'pending-payment';
        break;
    case 'Failed':
        $data['payment_status'] = 'failed';
        break;
    case 'Denied':
    case 'Expired':
    case 'Voided':
        $data['payment_status'] = 'uncollectible';
        break;
    case 'Reversed':
        $data['payment_status'] = 'refunded';
        break;
    default:
        $data['payment_status'] = isset($_REQUEST['payment_status']) ? strtolower($_REQUEST['payment_status']) : false;
        break;       
}

// We need to verify the transaction comes from PayPal and check we've not
// already processed the transaction before adding the payment to our
// database.
if ( ($data['subscr_id'] || $data['txn_id']) && sc_verifyTransaction($_REQUEST) ) {
        
    $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
    $paypalPDT = ($enableSandbox != 'disable') ? get_option( '_sc_paypal_sandbox_pdt_token' ) : get_option( '_sc_paypal_pdt_token' );
    
    if ($initial_order) {
        
        // free trial start
        if($data['txn_type'] == 'subscr_signup' || $data['txn_type'] == 'subscr_payment') {
            
            // update subscription ID
            if(!get_post_meta($subscription_id, '_sc_subscription_id', true)) {
                update_post_meta($subscription_id, '_sc_subscription_id', $data['subscr_id']);
            }
            
            //set a txn_id for free trials
            if(isset($_REQUEST['amount1']) && $_REQUEST['amount1']==0){
                $data['txn_id'] = uniqid();
                $data['payment_status'] = 'paid';
            }
        }
        
        $post_id = $data['order_id'];
        
        // update transaction ID
        $has_tid = get_post_meta($post_id, '_sc_transaction_id', true);
        if(!$has_tid) {
            update_post_meta($post_id, '_sc_transaction_id', $data['txn_id']);
        }
        
        $old_status = get_post_status($post_id);
        if($data['payment_status'] && $old_status != $data['payment_status']) {
            wp_update_post( array( 'ID' => $post_id, 'post_status'=>$data['payment_status'] ), false );
            update_post_meta( $post_id , '_sc_status' , $data['payment_status'] );
            update_post_meta( $post_id , '_sc_payment_status' , $data['paypal_status'] );
            $log_entry = sprintf(__('Order status changed to %s', 'ncs-cart'),$data['payment_status']) ;
            sc_log_entry($post_id, $log_entry);
            sc_trigger_integrations($data['payment_status'], $post_id);

        }
    }
    
    if (strpos($data['txn_type'], 'subscr_') === 0 && isset($data['subscription_id'])) {
        
        // handle renewal order
        if (!$initial_order) {
            if ($retry && !empty($retry)) {
                $post_id = $retry[0]->ID;

                // update transaction ID
                $has_tid = get_post_meta($post_id, '_sc_transaction_id', true);
                if(!$has_tid && $data['txn_id']) {
                    update_post_meta($post_id, '_sc_transaction_id', $data['txn_id']);
                }

                $old_status = get_post_status($post_id);
                if($data['payment_status'] && $old_status != $data['payment_status']) {
                    wp_update_post( array( 'ID' => $post_id, 'post_status'=>$data['payment_status'] ), false );
                    update_post_meta( $post_id , '_sc_status' , $data['payment_status'] );
                    update_post_meta( $post_id , '_sc_payment_status' , $data['paypal_status'] );
                    $log_entry = sprintf(__('Order status changed to %s', 'ncs-cart'),$data['payment_status']) ;
                    sc_log_entry($post_id, $log_entry);
                    sc_trigger_integrations($data['payment_status'], $post_id);

                }
            } else {

                $order_info = sc_setup_order($data['order_id'], true);
                $order_info['amount'] = $data['payment_amount'];
                $order_info['transaction_id'] = $data['txn_id'];
                $order_info['renewal_order'] = 1;
                $order_info['status'] = $data['payment_status'];
                $order_info['payment_status'] = $data['paypal_status'];
                $order_info['paypal_payer_email'] = $data['payer_email'];

                $children = array('ID','bump_id','bump_name','bump_amt','bump_option_id','main_offer','order_child','trigger_integrations','order_log');
                foreach($children as $child){
                    if(isset($order_info[$child])) {
                        unset($order_info[$child]);
                    }
                }
                
                // set this payment date
                $date_time = date('Y-m-d H:i:s');
                if (isset($_REQUEST['payment_date']) && ((strtotime($_REQUEST['payment_date'])) !== false)) {
                    $date_time = $_REQUEST['payment_date'];
                    
                    // and next bill date if not already set
                    if (!$next_bill_time) {
                        $next_bill_time = sc_next_bill_time($data['subscription_id'], $_REQUEST['payment_date']);
                    }
                }

                // create order
                $customer_name = $order_info['firstname'] . ' ' . $order_info['lastname'];
                $post_title = $order_info['item_name'];
                $post_id = wp_insert_post(array('post_title'=> $post_title , 'post_type'=>'sc_order', 'post_status'=>$order_info['status']), FALSE );
                
                wp_update_post( array( 
                    'ID' => $post_id, 
                    'post_title' => "#" . $post_id . " " . $customer_name,    
                    'post_date' => get_gmt_from_date( $date_time ),
                    'post_date_gmt' => get_gmt_from_date( $date_time )
                ),
                false );

                //add order info to DB
                foreach($order_info as $k=>$v) {
                    update_post_meta( $post_id , '_sc_'.$k , $v );
                }

                // after order actions
                sc_log_entry( $post_id, sprintf(__('%s renewal order created.', 'ncs-cart'),ucwords($order_info['status'])) );
                sc_trigger_integrations($order_info['status'], $post_id);

            }

            do_action('sc_paypal_recurring_payment_data', $_REQUEST, $data);
        }
        
        // handle subscription
        $post_id = $data['subscription_id'];
        $order_info = sc_setup_order($post_id, true);
        switch($data['txn_type']) {
            case 'subscr_signup':
                $order_info['status'] = 'active';
                $order_info['payment_status'] = 'started';
                break;
            case 'subscr_cancel':
                $order_info['status'] = 'canceled';
                $order_info['payment_status'] = 'canceled';
                break;
            case 'subscr_eot':
                $order_info['status'] = 'completed';
                $order_info['payment_status'] = 'expired';
                break;
            case 'subscr_failed':
                $order_info['status'] = 'past_due';
                $order_info['payment_status'] = 'failed';
                break;
           case 'subscr_payment':
                $order_info['status'] = 'active';
                $order_info['payment_status'] = 'payment received';
                break;
        }
        
        // make status trialing if still in trial
        if(get_post_meta( $post_id , '_sc_free_trial_days', true)){
            $free_trial_days = get_post_meta( $post_id , '_sc_free_trial_days', true);                  
            $datepay = date("Y-m-d", strtotime(get_the_time( 'Y-m-d', $post_id ) ."+".$free_trial_days." day"));
            if(date("Y-m-d") <  $datepay){
                $order_info['status'] = 'trialing';
            }
        }
        
        // update transaction ID
        $has_tid = !isset($order_info['subscription_id']);
        if(!$has_tid || !$order_info['subscription_id']) {
            update_post_meta($post_id, '_sc_subscription_id', $data['subscr_id']);
        }
        
        // update next bill date
        if($next_bill_time){
            update_post_meta($post_id, '_sc_sub_next_bill_date' , $next_bill_time );
        }
         
        $old_status = get_post_status($post_id);
        if($old_status != $order_info['status']) {
            wp_update_post( array( 'ID' => $post_id, 'post_status'=>$order_info['status'] ), false );
            update_post_meta( $post_id , '_sc_status' , $order_info['status'] );
            update_post_meta( $post_id , '_sc_sub_status' , $order_info['payment_status'] );
            $log_entry = sprintf(__('Subscription status changed to %s', 'ncs-cart'),$order_info['status']) ;
            sc_log_entry($post_id, $log_entry);
            sc_trigger_integrations($order_info['status'], $post_id);            
        }   
    }
}
   

function sc_verifyTransaction($data) {
    
    $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
    $paypalUrl = ($enableSandbox != 'disable') ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr';
    
    $req = 'cmd=_notify-validate';
    foreach ($data as $key => $value) {
        $value = urlencode(stripslashes($value));
        $value = preg_replace('/(.*[^%^0^D])(%0A)(.*)/i', '${1}%0D%0A${3}', $value); // IPN fix
        $req .= "&$key=$value";
    }

    $ch = curl_init($paypalUrl);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT,  apply_filters('studiocart_plugin_title', 'Studiocart') . ' ' . NCS_CART_VERSION);    
    curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
    curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
    $res = curl_exec($ch);

    if (!$res) {
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error: [$errno] $errstr");
    }

    $info = curl_getinfo($ch);
    //print_r($res);
    // Check the http response
    $httpCode = $info['http_code'];
   
    if ($httpCode != 200) {
        throw new Exception("PayPal responded with http code $httpCode");
    }

    curl_close($ch);

    return $res === 'VERIFIED';
}

function sc_checkTxnid($txnid, $order_id=0) {

    $txnid = sanitize_text_field($txnid);

    if($txnid){
        $results = false;
        $args = array(
            'post_type'  => 'sc_order',
            'post_status' => 'any',
            'posts_per_page' => 1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_sc_paypal_txn_id',
                    'value' => $txnid,
                ),
                array(
                    'key' => '_sc_transaction_id',
                    'value' => $txnid,
                ),
            )
        );

        return get_posts($args);
    } else if($order_id) {
        // if order ID and no transaction ID then were dealing with a free trial, set order ID to initial order
        $posts = (object) array('ID'=>$order_id);
        return array($posts);
    }
    return false;
}

exit();