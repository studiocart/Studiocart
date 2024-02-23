<?php
// Handle the PayPal response.
if(!isset($_REQUEST['payer_email'])) {
    http_response_code(200);
    exit();
}

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

$order = new ScrtOrder($order_id);
if(isset($_REQUEST['txn_id'])){
    $txnid = sanitize_text_field($_REQUEST['txn_id']);
    if ($found = ScrtOrder::get_by_trans_id($txnid)) {
        $order = $found;
    } else if (isset($_REQUEST['parent_txn_id'])) {
        $txnid = sanitize_text_field($_REQUEST['parent_txn_id']);
        if ($found = ScrtOrder::get_by_trans_id($txnid)) {
            $order = $found;
            $child_txn_id = $_REQUEST['txn_id'];
            $_REQUEST['txn_id'] = sanitize_text_field($_REQUEST['parent_txn_id']);
        }
    }
}

global $sc_debug_logger;
$sc_debug_logger->log_debug("Processing paypal ipn response:".print_r($_REQUEST,true));

if($subscription_id) {
    $sub = new ScrtSubscription($subscription_id);
    
    $sc_debug_logger->log_debug("sub->id: ".$sub->id);
    $sc_debug_logger->log_debug("order #".$order->id." status: ". $order->status);
    
    // if the first and only payment is failed/pending  
    // leave $order as is so that 'product purchased' 
    // integrations can run. Otherwise, create a renewal.
    if ($sub->order_count() == 1 && $order->status != 'paid') {
        $sc_debug_logger->log_debug('first payment failed/pending: '.print_r([$order->status, ($sub->order_count() == 1), ($order->status === 'failed')], true));
    } else if($order->transaction_id != $_REQUEST['txn_id']) {
        $order = $sub->new_order();
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

$sc_debug_logger->log_debug("Processing paypal ipn response:".print_r($data,true));
$sc_debug_logger->log_debug('current $order->id: '.$order->id);

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
    case 'Refunded':
        $data['payment_status'] = 'refunded';
        
        $sc_debug_logger->log_debug('checking refund');
        
        // exit if we don't have the right txn ID or refund amount
        if (!isset($child_txn_id) || !isset($_REQUEST['payment_gross'])) {
            $sc_debug_logger->log_debug('exit missing info');
            exit();
        }
        
        // refund with same txn ID already processed so exit
        if(in_array($child_txn_id, wp_list_pluck( $order->refund_log, 'refundID' ))) {
            exit();
        } else {
            // add to refund log
            $refund_amount = $_REQUEST['payment_gross'];
            
            if($refund_amount < 0) {
                $refund_amount *= -1;
            }
            $order->refund_log($refund_amount, $child_txn_id);
            $data['payment_amount'] = $order->amount;
        }
        
        break;
    default:
        $data['payment_status'] = false;
        break;       
}

$sc_debug_logger->log_debug('$data[payment_status]: '.$data['payment_status']);

// We need to verify the transaction comes from PayPal and check we've not
// already processed the transaction before adding the payment to our
// database.
if ( ($data['subscr_id'] || $data['txn_id']) && sc_verifyTransaction($_REQUEST) ) {
        
    $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
    $paypalPDT = ($enableSandbox != 'disable') ? get_option( '_sc_paypal_sandbox_pdt_token' ) : get_option( '_sc_paypal_pdt_token' );

    // update subscription ID
    if($data['subscr_id']) {
        $sub->subscription_id = $data['subscr_id'];
        
        // free trial start
        if($data['txn_type'] == 'subscr_signup' || $data['txn_type'] == 'subscr_payment') {
            
            //set a txn_id for free trials
            if(isset($_REQUEST['amount1']) && $_REQUEST['amount1']==0){
                $data['txn_id'] = $data['subscr_id'];
                $data['payment_status'] = 'paid';
            }
        }
    }

    // update transaction ID
    $order->transaction_id = $data['txn_id'];
    $order->amount = $data['payment_amount'];
    $order->status = ($data['payment_status']) ? $data['payment_status'] : $order->status;
    $order->payment_status = $data['paypal_status'];
    $order->paypal_payer_email = $data['payer_email'];
    
    if($order->transaction_id) {
       $order->store(); 
    }

    if (strpos($data['txn_type'], 'subscr_') === 0 && isset($data['subscription_id'])) {
        
        do_action('sc_paypal_recurring_payment_data', $_REQUEST, $data);
        
        // handle subscription
        switch($data['txn_type']) {
            case 'subscr_payment':
            case 'subscr_signup':
                $sub->status = 'active';
                $sub->sub_status = 'active';
                break;
            case 'subscr_cancel':
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
                break;
            case 'subscr_eot':
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
                if ( isset($sub->sub_end_date) && strtotime($sub->sub_end_date) <= strtotime(date('Y-m-d')) ) {            
                    $sub->status = 'completed';
                    $sub->sub_status = 'completed';
                }
                break;
            case 'subscr_failed':
                $sub->status = 'failed';
                $sub->sub_status = 'failed';
                break;
        }
        
        $sc_debug_logger->log_debug(print_r([
            '$data[txn_type]: '.$data['txn_type'], 
            '$sub->free_trial_days: ' .$sub->free_trial_days,
            '$sub->status :'. $sub->status], true)
        );
        
        // make status trialing if still in trial
        if($sub->sub_status == 'active' && $sub->free_trial_days){
            $datepay = date("Y-m-d", strtotime(get_the_time( 'Y-m-d', $sub->id ) ."+".$sub->free_trial_days." day"));
            if(date("Y-m-d") <  $datepay){
                $sub->status = 'trialing';
                $sub->sub_status = 'trialing';
            }
        }
        
        $sc_debug_logger->log_debug(
            '$sub->status: '.$sub->status
        );
         
        $sub->store();  
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

exit();