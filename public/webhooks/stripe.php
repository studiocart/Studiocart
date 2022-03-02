<?php 
global $sc_stripe, $sc_currency;

if(!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    // Direct access
    http_response_code(200);
    exit();
}

$env = $sc_stripe['mode'];
$endpoint_secret = get_option( '_sc_stripe_'. $env .'_webhook_secret' );

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload, $sig_header, $endpoint_secret
    );
} catch(\UnexpectedValueException $e) {
    // Invalid payload
    http_response_code(200);
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    
    // Invalid signature, try other mode
    $env = ($env == 'live') ? 'test' : 'live';
    $endpoint_secret = get_option( '_sc_stripe_'. $env .'_webhook_secret' );
    
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(200);
        exit();
    } catch(\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        http_response_code(200);
        exit();
    }
}

function sc_format_stripe_number($amount, $sc_currency='USD') {
    $zero_decimal_currency = get_sc_zero_decimal_currency();
    if(!in_array($sc_currency, $zero_decimal_currency)){
        $amount = $amount / 100;
    }
    return sc_format_number($amount);
}

if($event->data->object->object == 'charge') {
    
    $charge = $event->data->object;
    
    switch ($event->type) {
        case 'charge.succeeded':
            $pay_status = 'paid';
            $post_status = 'paid';
            break;
        case 'charge.refunded':
            $pay_status = 'refunded';
            $post_status = 'refunded';
            break;
        default:
            // Unexpected status
            http_response_code(200);
            exit();
    }
    
    if (!isset($charge->metadata->sc_product_id)) {
        http_response_code(200);
        exit();
    }
    
    if (isset($charge->metadata->origin)) {
        if ($charge->metadata->origin != get_site_url()) {
            http_response_code(200);
            exit();
        }
    }
    $order = ScrtOrder::get_by_trans_id($charge->id);
    if(!$order && isset($charge->payment_intent)) {
        $order = ScrtOrder::get_by_trans_id($charge->payment_intent);
    }
    if($order) {
        $order->transaction_id = $charge->id;
        $order->payment_status = $pay_status;
        $order->status = $post_status;
        $order->store();
    }
    http_response_code(200);
    exit();
}

if($event->data->object->object == 'invoice') {
    
    $invoice = $event->data->object;
    $initial_order = false;
    
    $next_bill = $found = false;
    foreach($invoice->lines->data as $item) {
        if( isset($item->subscription_item) && (isset($item->metadata->sc_product_id) || isset($item->plan->metadata->sc_product_id)) ) {
            
            if (isset($item->metadata->origin)) {
                if ($item->metadata->origin != get_site_url()) {
                    http_response_code(200);
                    exit();
                }
            }
            
            $product_id = ($item->metadata->sc_product_id) ? $item->metadata->sc_product_id : $item->plan->metadata->sc_product_id;
            $subscription = $item;
            $found = true;
            $next_bill = $item->period->end;
            break;
        }
    }
    
    $sub = ScrtSubscription::get_by_sub_id($subscription->id);  
    if (!$found || !$sub->id) {
        // charge is not related to a SC subscription
        http_response_code(200);
        exit();
    }
        
    // update subscription next bill date
    $sub->sub_next_bill_date = $next_bill;
    $sub->store();
    
    switch ($event->type) {
        case 'invoice.payment_failed':
            $post_status = 'failed';
            $pay_status = 'failed';
            break;
        case 'invoice.payment_succeeded':
            $pay_status = 'succeeded';
            $post_status = 'paid';
            break;
        case 'invoice.marked_uncollectible':
            $post_status = 'uncollectible';
            $pay_status = 'marked uncollectible';
            break;
        default:
            // Unexpected status
            http_response_code(200);
            exit();
    }
        
    // search by charge ID first, then intent ID
    $existing = ScrtOrder::get_by_trans_id($invoice->charge);
    if(!$existing && isset($invoice->payment_intent)) {
        $existing = ScrtOrder::get_by_trans_id($invoice->payment_intent);
        if($existing) {
            // replace intent id with charge ID
            $existing->transaction_id = $invoice->charge;
            $existing->store();
        }
    }
    
    if(!$existing) {
        
        // check if this is the first order
        $order = $sub->first_order(); 
        
        // update first order if it's the only one we have so far and it has a temp ID
        if ( ((!$order->transaction_id || strpos($order->transaction_id,'ch_') !== 0) && $sub->order_count() == 1) === false ) {
            $order = $sub->new_order();
        }
        
        $order->transaction_id = $invoice->charge;
        $order->amount = sc_format_stripe_number($invoice->amount_paid, $sc_currency);
        $order->payment_status = $pay_status;
        $order->status = $post_status;
        $order->store();
        
    } else {
        $order = $existing;
        if ($order->status != $post_status) {
            $order->payment_status = $pay_status;
            $order->status = $post_status;
            $order->store();
        }
    }
    
    do_action('sc_stripe_invoice_response',$order->get_data());
    
    http_response_code(200);
    exit();

} else if($event->data->object->object == 'subscription') {
    
    $sub = $event->data->object;
    $found = false;
    foreach($sub->items->data as $item) {
        if( isset($item->plan->metadata->sc_product_id) ) {
            $product_id = $item->plan->metadata->sc_product_id;
            $found = true;
            break;
        }
    }
    
    if(!$found) {
        // subscription is not related to a SC subscription
        http_response_code(200);
        exit();
    }
    
    
    switch ($event->type) {
        case 'customer.subscription.updated':
            switch ($sub->status) {
                case 'incomplete':
                case 'incomplete_expired':
                case 'past_due':
                    $post_status = 'past_due';
                    break;
                default:
                    $post_status = $sub->status;
                    break;
            }
            break;
            
        case 'customer.subscription.deleted':
                        
            // check end date to see if this is a completed subscription
            $args = array(
                'post_type'  => 'sc_subscription',
                'post_status' => 'active',
                'meta_query' => array(
                    array(
                        'key' => '_sc_stripe_subscription_id',
                        'value' => $sub->id,
                    ),
                    array(
                        'key' => '_sc_sub_end_date', // Check the start date field
                        'value' => date("Y-m-d"), // Set today's date (note the similar format)
                        'compare' => '==', // Return the ones greater than today's date
                        'type' => 'DATE' // Let WordPress know we're working with date
                    )
                )
            );
            
            // if this subscription was scheduled to end today, mark the status as "completed" and exit
            $posts = get_posts($args);
            if (!empty($posts)) {
                $post_status = 'past_due';
            } else {
                $post_status = 'canceled';
            }
            break;
            
        default:
            // Unexpected status
            //echo 2;
            http_response_code(200);
            exit();
    }
    
    // find subscription and update status
    $existing = new ScrtSubscription($sub->metadata->sc_subscription_id);
    if($existing->id) {
        $existing->status = $post_status;
        $existing->sub_status = $sub->status;
        $existing->store();
    }
    
    http_response_code(200);
    exit;
}

http_response_code(200);
exit();