<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/public
 */
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/public
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Paypal extends NCS_Cart_Public
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private  $plugin_name ;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private  $version ;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $paypal_enabled = $this->paypal_configured();
        if ( empty($paypal_enabled) ) {
            return;
        }
        add_action( 'template_redirect', [ $this, 'sc_paypal_process_payment' ], 9999 );
        //add_action( 'sc_payment_intent', array($this, 'sc_paypal_payment_intent'),10,2);
        //add_action( 'sc_payment_method', array($this, 'sc_paypal_payment_method'),10,2);
        add_action(
            'sc_order_refund_paypal',
            [ $this, 'sc_paypal_refund' ],
            10,
            2
        );
        add_filter(
            'sc_cancel_subscription',
            [ $this, 'sc_paypal_cancel_subscription' ],
            10,
            4
        );
        add_filter(
            'sc_sub_item_id',
            array( $this, 'sc_paypal_sub_item_id' ),
            10,
            3
        );
        add_filter( 'sc_enabled_payment_gateways', [ $this, 'maybe_add_paypal_enabled' ] );
        add_filter(
            'sc_payment_methods',
            [ $this, 'maybe_add_paypal_pay_method' ],
            10,
            2
        );
        add_action( 'wp_ajax_sc_paypal_request', [ $this, 'sc_paypal_request' ] );
        add_action( 'wp_ajax_nopriv_sc_paypal_request', [ $this, 'sc_paypal_request' ] );
        add_action( 'wp_ajax_paypal_process_upsell', [ $this, 'paypal_process_upsell__premium_only' ] );
        add_action( 'wp_ajax_nopriv_paypal_process_upsell', [ $this, 'paypal_process_upsell__premium_only' ] );
    }
    
    public function maybe_add_paypal_pay_method( $payment_methods, $post_id )
    {
        // Paypal
        if ( !get_post_meta( $post_id, '_sc_disable_paypal', true ) ) {
            
            if ( $this->paypal_configured() ) {
                $icon = '<svg style="margin-right: 5px;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="paypal" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="svg-inline--fa fa-paypal fa-w-12 fa-2x"><path fill="currentColor" d="M111.4 295.9c-3.5 19.2-17.4 108.7-21.5 134-.3 1.8-1 2.5-3 2.5H12.3c-7.6 0-13.1-6.6-12.1-13.9L58.8 46.6c1.5-9.6 10.1-16.9 20-16.9 152.3 0 165.1-3.7 204 11.4 60.1 23.3 65.6 79.5 44 140.3-21.5 62.6-72.5 89.5-140.1 90.3-43.4.7-69.5-7-75.3 24.2zM357.1 152c-1.8-1.3-2.5-1.8-3 1.3-2 11.4-5.1 22.5-8.8 33.6-39.9 113.8-150.5 103.9-204.5 103.9-6.1 0-10.1 3.3-10.9 9.4-22.6 140.4-27.1 169.7-27.1 169.7-1 7.1 3.5 12.9 10.6 12.9h63.5c8.6 0 15.7-6.3 17.4-14.9.7-5.4-1.1 6.1 14.4-91.3 4.6-22 14.3-19.7 29.3-19.7 71 0 126.4-28.8 142.9-112.3 6.5-34.8 4.6-71.4-23.8-92.6z" class=""></path></svg>';
                $payment_methods['paypal'] = array(
                    'value'        => esc_html__( 'paypal', 'ncs-cart' ),
                    'label'        => esc_html__( 'PayPal', 'ncs-cart' ) . ' ' . $icon,
                    'single_label' => $icon . ' ' . esc_html__( 'Pay with PayPal', 'ncs-cart' ),
                );
            }
        
        }
        return $payment_methods;
    }
    
    public function paypal_configured()
    {
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        
        if ( $enableSandbox && get_option( '_sc_paypal_enable' ) ) {
            $paypalEmail = ( $enableSandbox != 'disable' ? get_option( '_sc_paypal_sandbox_email' ) : get_option( '_sc_paypal_email' ) );
            if ( $paypalEmail ) {
                return true;
            }
        }
        
        return false;
    }
    
    public function sc_paypal_payment_method( $payment_method, $order )
    {
        if ( $order->pay_method != 'paypal' ) {
            return $payment_method;
        }
        return 'paypal';
    }
    
    public function sc_paypal_refund( $data, $order )
    {
        
        if ( !isset( $data['nonce'] ) || !wp_verify_nonce( $data['nonce'], 'sc-ajax-nonce' ) ) {
            esc_html_e( 'Ooops, something went wrong, please try again later.', 'ncs-cart' );
            die;
        }
        
        
        if ( !isset( $order->transaction_id ) ) {
            esc_html_e( 'INVALID CHARGE ID', 'ncs-cart' );
            wp_die;
        }
        
        $access_token = $this->sc_paypal_oauthtoken();
        $postID = intval( $_POST['id'] );
        $prodID = intval( $_POST['pid'] );
        $payment_intent = trim( $order->transaction_id );
        $sc_currency = get_option( '_sc_currency' );
        $amount = $_POST['refund_amount'];
        
        if ( get_option( '_sc_paypal_enable_sandbox' ) == 'enable' ) {
            $refundurl = 'https://api.sandbox.paypal.com/v1/payments/sale/';
        } else {
            $refundurl = 'https://api.paypal.com/v1/payments/sale/';
        }
        
        $amount = array(
            'amount'      => array(
            'total'    => $amount,
            'currency' => $sc_currency,
        ),
            'description' => 'Defective product',
        );
        $amounts = json_encode( $amount );
        $ch1 = curl_init();
        curl_setopt( $ch1, CURLOPT_URL, $refundurl . $payment_intent . '/refund' );
        curl_setopt( $ch1, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch1, CURLOPT_POST, 1 );
        curl_setopt( $ch1, CURLOPT_POSTFIELDS, $amounts );
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $access_token;
        curl_setopt( $ch1, CURLOPT_HTTPHEADER, $headers );
        $result = curl_exec( $ch1 );
        if ( curl_errno( $ch1 ) ) {
            echo  'Error:' . curl_error( $ch1 ) ;
        }
        curl_close( $ch1 );
        $respons = json_decode( $result );
        //print_r($respons);exit();
        
        if ( $respons->state == 'completed' ) {
            $amount = $_POST['refund_amount'];
            update_post_meta( $postID, '_sc_payment_status', "refunded" );
            wp_update_post( array(
                'ID'          => $postID,
                'post_status' => "refunded",
            ) );
            update_post_meta( $postID, '_sc_status', "refunded" );
            update_post_meta( $postID, '_sc_refund_amount', $amount );
            //update_post_meta( $postID, '_sc_remaining_amount' , $ramount);
            $refundID = $respons->id;
            NCS_Cart_Admin::sc_refund_log( $postID, $amount, $refundID );
            $current_user = wp_get_current_user();
            $log_entry = __( 'Payment refunded by', 'ncs-cart' ) . ' ' . $current_user->user_login;
            sc_log_entry( $postID, $log_entry );
            
            if ( $_POST['restock'] == 'YSE' ) {
                sc_maybe_update_stock( $prodID, 'increase' );
                update_post_meta( $postID, '_sc_refund_restock', 'YES' );
            }
            
            //sc_maybe_update_stock( $prodID, 'increase' );
            sc_trigger_integrations( 'refunded', $postID );
            esc_html_e( 'OK', 'ncs-cart' );
        } else {
            esc_html_e( $respons->message, 'ncs-cart' );
            wp_die;
        }
    
    }
    
    public function sc_paypal_cancel_subscription(
        $canceled,
        $sub,
        $sub_id,
        $now = true
    )
    {
        global  $sc_currency ;
        if ( $sub->pay_method != 'paypal' ) {
            return $canceled;
        }
        $access_token = $this->sc_paypal_oauthtoken();
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        $paypalUrl = ( $enableSandbox != 'disable' ? 'https://api.sandbox.paypal.com/v1/billing/subscriptions/' : 'https://api.paypal.com/v1/billing/subscriptions/' );
        $subscription_id = $sub->subscription_id;
        $paypal_curl_url = $paypalUrl . $subscription_id . '/cancel';
        $sub_args = array(
            'reason' => 'Not satisfied with the service',
        );
        $chs = curl_init();
        curl_setopt( $chs, CURLOPT_URL, $paypal_curl_url );
        curl_setopt( $chs, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $chs, CURLOPT_POST, 1 );
        curl_setopt( $chs, CURLOPT_POSTFIELDS, json_encode( $sub_args ) );
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $access_token;
        curl_setopt( $chs, CURLOPT_HTTPHEADER, $headers );
        $result = curl_exec( $chs );
        
        if ( curl_errno( $chs ) ) {
            $canceled = false;
            echo  'Error:' . curl_error( $chs ) ;
        }
        
        curl_close( $chs );
        $results = json_decode( $result );
        
        if ( empty($results) ) {
            $canceled = true;
            $sub->cancel_date = date( 'Y-m-d' );
            
            if ( $now ) {
                $sub->status = 'canceled';
                $sub->sub_status = 'canceled';
            } else {
                // change status at next bill date
                $sub->cancel_at();
            }
            
            $sub->store();
        } else {
            
            if ( isset( $results->message ) ) {
                esc_html_e( $results->message, 'ncs-cart' );
            } else {
                if ( isset( $results->error_description ) ) {
                    esc_html_e( $results->error_description, 'ncs-cart' );
                }
            }
            
            $canceled = false;
        }
        
        return $canceled;
    }
    
    public function sc_paypal_sub_item_id( $item_id, $item, $order )
    {
        if ( $order->pay_method != 'paypal' ) {
            return $item_id;
        }
        return $item['id'] ?? $order->product_id;
    }
    
    public function sc_paypal_oauthtoken()
    {
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        
        if ( get_option( '_sc_paypal_enable_sandbox' ) == 'enable' ) {
            $paypalurl = 'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $clientID = get_option( '_sc_paypal_sandbox_client_id' );
            $secret = get_option( '_sc_paypal_sandbox_secret' );
        } else {
            $paypalurl = 'https://api-m.paypal.com/v1/oauth2/token';
            $clientID = get_option( '_sc_paypal_client_id' );
            $secret = get_option( '_sc_paypal_secret' );
        }
        
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $paypalurl );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $ch, CURLOPT_POST, 1 );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials" );
        curl_setopt( $ch, CURLOPT_USERPWD, $clientID . ':' . $secret );
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Accept-Language: en_US';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        $result = curl_exec( $ch );
        if ( curl_errno( $ch ) ) {
            echo  'Error:' . curl_error( $ch ) ;
        }
        curl_close( $ch );
        $results = json_decode( $result );
        return $access_token = $results->access_token;
    }
    
    private function sc_build_paypal_url( $order, $sub )
    {
        global  $sc_currency ;
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        $paypalUrl = ( $enableSandbox != 'disable' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' );
        $business = ( $enableSandbox != 'disable' ? get_option( '_sc_paypal_sandbox_email' ) : get_option( '_sc_paypal_email' ) );
        // Grab the post data so that we can set up the query string for PayPal.
        // Ideally we'd use a whitelist here to check nothing is being injected into
        // our post data.
        $data = array(
            "cmd"           => "_xclick",
            "no_note"       => "1",
            "bn"            => "PP-BuyNowBF:btn_buynow_LG.gif:NonHostedGuest",
            "first_name"    => $order->first_name,
            "last_name"     => $order->last_name,
            "payer_email"   => $order->email,
            "item_number"   => $order->product_id,
            "submit"        => "Submit Payment",
            'business'      => $business,
            'item_name'     => $order->item_name,
            'currency_code' => strtoupper( $sc_currency ),
            'custom'        => apply_filters(
            'sc_paypal_custom_payment_vars',
            $order->id,
            $order->get_data(),
            false
        ),
            'cancel_return' => stripslashes( $order->cancel_url ),
            'notify_url'    => stripslashes( get_site_url() . '/?sc-api=paypal' ),
            'return'        => stripslashes( $order->return_url ),
        );
        
        if ( !empty($sub) ) {
            $sub_amount = $sub->sub_amount;
            $sub_amount = number_format(
                (double) $sub_amount,
                2,
                '.',
                ''
            );
            $data['cmd'] = "_xclick-subscriptions";
            $data['bn'] = "PP-SubscriptionsBF:btn_donateCC_LG.gif:NonHosted";
            $data['no_shipping'] = "1";
            $data['tax'] = $sub->tax_rate;
            $data['p3'] = $sub->sub_frequency;
            $data['t3'] = strtoupper( substr( $sub->sub_interval, 0, 1 ) );
            $data['a3'] = $sub_amount;
            $data['src'] = "1";
            if ( $sub->sub_installments > 0 ) {
                $data["srt"] = $sub->sub_installments;
            }
            $custom = apply_filters(
                'sc_paypal_custom_payment_vars',
                array(
                'order_id'        => $order->id,
                'subscription_id' => $sub->id,
            ),
                $order->get_data(),
                $sub->get_data()
            );
            $data['custom'] = json_encode( $custom );
            
            if ( !empty($order->amount) ) {
                $data['sra'] = "1";
                $data['a1'] = $order->amount;
                $data['p1'] = $sub->sub_frequency;
                $data['t1'] = strtoupper( substr( $sub->sub_interval, 0, 1 ) );
            }
            
            
            if ( $sub->free_trial_days || $order->coupon && in_array( $order->coupon['type'], array( 'cart-percent', 'cart-fixed' ) ) ) {
                // add trial period so that the 1st payment can be discounted
                
                if ( $order->coupon && !$sub->free_trial_days ) {
                    $now = new DateTime( "now" );
                    $next = new DateTime();
                    $next->setTimestamp( $sub->sub_next_bill_date );
                    $interval = $now->diff( $next );
                    $sub->free_trial_days = $interval->format( '%d' );
                    // reduce installments and end date by 1 pay period
                    
                    if ( isset( $data["srt"] ) ) {
                        $data["srt"] -= 1;
                        $cancel_at = date( "Y-m-d\\TH:i:s\\Z", $sub->cancel_at );
                        $cancel_at .= " -" . $sub->sub_frequency . ' ' . $sub->sub_interval;
                        $sub->cancel_at = strtotime( $cancel_at );
                        $sub->sub_end_date = date( "Y-m-d", $sub->cancel_at );
                        var_dump( $sub->cancel_at, $sub->sub_end_date );
                        $sub->store();
                    }
                
                }
                
                $data['a1'] = $order->amount;
                $data['p1'] = $sub->free_trial_days;
                $data['t1'] = 'D';
            }
        
        } else {
            $data['amount'] = $order->amount;
        }
        
        $address_fields = array(
            'address1',
            'address2',
            'city',
            'state',
            'zip',
            'country'
        );
        $data["address_override"] = "1";
        foreach ( $address_fields as $info ) {
            
            if ( $info != 'address2' && !isset( $order->{$info} ) ) {
                unset( $data["address_override"] );
                break;
            } else {
                $data[$info] = $order->{$info};
            }
        
        }
        // Build the query string from the data.
        $data = apply_filters(
            'sc_paypal_payment_vars',
            $data,
            $order,
            $sub
        );
        $queryString = http_build_query( $data );
        // Redirect to paypal IPN
        return $paypalUrl . '?' . $queryString;
    }
    
    public function sc_paypal_request()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        global  $scp ;
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "An error occurred, please refresh the page and try again.", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        do_action( 'sc_before_create_main_order' );
        $is_sub = false;
        $sc_product_id = intval( $_POST['sc_product_id'] );
        // setup product info
        $scp = sc_setup_product( $sc_product_id );
        // setup order info
        $order = new ScrtOrder();
        $order->load_from_post();
        $sc_product_id = intval( $_POST['sc_product_id'] );
        $sc_option_id = sanitize_text_field( $_POST['sc_product_option'] );
        $order->gateway_mode = ( get_option( '_sc_paypal_enable_sandbox' ) != 'disable' ? 'test' : 'live' );
        if ( $order->plan->type == 'recurring' ) {
            $is_sub = true;
        }
        if ( is_array( $order->order_bumps ) && !$is_sub ) {
            foreach ( $order->order_bumps as $bump ) {
                // do order bumps have a subscription?
                if ( isset( $bump['plan'] ) ) {
                    $is_sub = true;
                }
            }
        }
        $sub = '';
        
        if ( $is_sub ) {
            $sub = ScrtSubscription::from_order( $order );
            $sub->store();
            $order->subscription_id = $sub->id;
        }
        
        $order_id = $order->store();
        $order->cancel_url = esc_url_raw( $_POST['cancel_url'] );
        
        if ( @$scp->upsell && $scp->confirmation != 'redirect' ) {
            $return_url = $scp->form_action;
        } else {
            $return_url = $scp->thanks_url;
        }
        
        $return_url = apply_filters(
            'studiocart_post_purchase_url',
            $return_url,
            $order_id,
            $sc_product_id
        );
        $order->return_url = add_query_arg( array(
            'sc-order' => $order_id,
            'sc-pid'   => $sc_product_id,
            'sc-pp'    => 1,
        ), $return_url );
        $paypalUrl = $this->sc_build_paypal_url( $order, $sub );
        $res = [
            'url' => $paypalUrl,
        ];
        echo  json_encode( $res ) ;
        exit;
    }
    
    public function sc_paypal_process_payment()
    {
        global  $scp ;
        // add tracking/redirect for initial charge
        
        if ( isset( $_GET['sc-pp'] ) && isset( $_GET['sc-order'] ) ) {
            $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
            $paypalPDT = ( $enableSandbox != 'disable' ? get_option( '_sc_paypal_sandbox_pdt_token' ) : get_option( '_sc_paypal_pdt_token' ) );
            $paypalUrl = ( $enableSandbox != 'disable' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' );
            $order_id = intval( $_GET['sc-order'] );
            
            if ( !$scp ) {
                $sc_product_id = intval( $_GET['sc-pid'] );
                $scp = sc_setup_product( $sc_product_id );
            }
            
            $order_info = (array) sc_setup_order( $order_id );
            
            if ( !isset( $_GET['sc-oto'] ) ) {
                $_POST['purchase_amount'] = $order_info['amount'];
                $_POST['sc_order_id'] = $order_id;
                if ( isset( $order_info['order_bumps'] ) ) {
                    $_POST['sc-orderbump'] = $order_info['order_bumps'];
                }
            }
            
            
            if ( isset( $_GET['sc-oto-2'] ) ) {
                $pdt_order = (array) sc_setup_order( intval( $_GET['sc-oto-2'] ) );
            } else {
                
                if ( isset( $_GET['sc-oto'] ) ) {
                    $pdt_order = (array) sc_setup_order( intval( $_GET['sc-oto'] ) );
                } else {
                    $pdt_order = $order_info;
                }
            
            }
            
            // Check PDT Status and run integrations
            
            if ( !empty($paypalPDT) && isset( $_GET['tx'] ) ) {
                $paypal_data = array(
                    'paypalPDT' => $paypalPDT,
                    'tx'        => $_GET['tx'],
                    'paypalUrl' => $paypalUrl,
                );
                $this->run_pdt_check( $pdt_order, $paypal_data );
            } else {
                // check custom field post data to see if we need to autologin
                
                if ( get_post_meta( $order_id, '_sc_auto_login', true ) ) {
                    $user_id = get_post_meta( $order_id, '_sc_user_account', true );
                    
                    if ( $user_id ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                    }
                
                }
            
            }
            
            
            if ( isset( $scp->redirect_url ) ) {
                $return_url = esc_url( sc_personalize( $scp->redirect_url, $order_info, 'urlencode' ) );
            } else {
                $return_url = esc_url( sc_personalize( $scp->form_action, $order_info, 'urlencode' ) );
            }
            
            $return_url = apply_filters(
                'studiocart_post_purchase_url',
                $return_url,
                $order_id,
                $scp->ID
            );
            $return_url = add_query_arg( array(
                'sc-order' => $order_id,
                'sc-pid'   => $scp->ID,
            ), $return_url );
            wp_redirect( $return_url, 302 );
            exit;
        }
    
    }
    
    private function run_pdt_check( $order_info, $paypal_data )
    {
        // check subscription (if exists) to see if this is an upsell
        if ( (!isset( $order_info['order_type'] ) || $order_info['order_type'] == 'main') && get_post_type( $order_info['ID'] ) == 'sc_order' && ($sub_id = get_post_meta( $order_info['ID'], '_sc_subscription_id', true )) ) {
            
            if ( get_post_meta( $order_info['ID'], '_sc_us_parent', true ) ) {
                $order_info['order_type'] = 'upsell';
            } else {
                if ( get_post_meta( $order_info['ID'], '_sc_ds_parent', true ) ) {
                    $order_info['order_type'] = 'downsell';
                }
            }
        
        }
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => $paypal_data['paypalUrl'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => array(
            'cmd'    => '_notify-synch',
            'tx'     => $paypal_data['tx'],
            'at'     => $paypal_data['paypalPDT'],
            'submit' => 'PDT',
        ),
        ) );
        $response = curl_exec( $curl );
        curl_close( $curl );
        $response_array = preg_split( '/\\r\\n|\\r|\\n/', $response );
        $final_data = array();
        
        if ( $response_array[0] == 'SUCCESS' ) {
            unset( $response_array[0] );
            foreach ( $response_array as $data ) {
                $key_value = explode( '=', $data );
                if ( isset( $key_value[1] ) ) {
                    $final_data[$key_value[0]] = $key_value[1];
                }
            }
        }
        
        $order_id = '';
        $subscription_id = '0';
        // grab Studiocart order/subscription IDs from custom meta
        
        if ( isset( $final_data['custom'] ) ) {
            $custom = urldecode( $final_data['custom'] );
            
            if ( is_numeric( $custom ) || strpos( $custom, '=' ) !== false ) {
                // deprecated
                $custom_id = explode( "=", $custom );
                $order_id = $custom_id[0];
                if ( isset( $custom_id[1] ) ) {
                    $subscription_id = $custom_id[1];
                }
            } else {
                $custom = json_decode( $custom );
                $order_id = $custom->order_id;
                if ( isset( $custom->subscription_id ) ) {
                    $subscription_id = $custom->subscription_id;
                }
            }
            
            
            if ( $subscription_id ) {
                update_post_meta( $subscription_id, '_sc_subscription_id', $final_data['subscr_id'] );
                update_post_meta( $subscription_id, '_sc_transaction_id', $final_data['txn_id'] );
            }
            
            update_post_meta( $order_id, '_sc_transaction_id', $final_data['txn_id'] );
        }
        
        
        if ( !empty($final_data['payment_status']) ) {
            $old_status = get_post_status( $order_id );
            if ( $subscription_id ) {
                $old_sub_status = get_post_status( $subscription_id );
            }
            $status = strtolower( $final_data['payment_status'] );
            
            if ( $status == 'completed' ) {
                $status = 'paid';
                
                if ( get_post_status( $order_id ) != 'paid' ) {
                    
                    if ( $subscription_id ) {
                        update_post_meta( $subscription_id, '_sc_sub_status', 'active' );
                        update_post_meta( $subscription_id, '_sc_status', 'active' );
                        wp_update_post( array(
                            'ID'          => $subscription_id,
                            'post_status' => 'active',
                        ) );
                    }
                    
                    $submitSuccess = wp_update_post( array(
                        'ID'          => $order_id,
                        'post_status' => $status,
                    ) );
                    update_post_meta( $order_id, '_sc_payment_status', $status );
                    update_post_meta( $order_id, '_sc_status', $status );
                }
                
                
                if ( get_post_meta( $order_id, '_sc_auto_login', true ) ) {
                    $user_id = get_post_meta( $order_id, '_sc_user_account', true );
                    
                    if ( $user_id ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                    }
                
                }
            
            }
            
            $integration_status = (array) get_post_meta( $order_info['ID'], '_sc_trigger_integrations', true );
            
            if ( $old_status != $status ) {
                update_post_meta( $order_info['ID'], '_sc_status', $status );
                sc_log_entry( $order_info['ID'], __( 'PayPal order status changed to ' . $status, 'ncs-cart' ) );
                
                if ( get_post_meta( $order_info['ID'], '_sc_us_parent', true ) ) {
                    $order_info['plan_id'] = 'upsell';
                    $order_info['order_type'] = 'upsell';
                }
                
                
                if ( get_post_meta( $order_info['ID'], '_sc_ds_parent', true ) ) {
                    $order_info['plan_id'] = 'downsell';
                    $order_info['order_type'] = 'downsell';
                }
                
                sc_trigger_integrations( $status, $order_info );
                $integration_status[] = $status;
                update_post_meta( $order_info['ID'], '_sc_trigger_integrations', $integration_status );
            }
        
        }
    
    }
    
    private function sc_paypal_subscription_save( $order )
    {
        //insert SUBSCRIPTION
        
        if ( !isset( $_POST['sc-order'] ) ) {
            // only present for upsells
            $subscription = $this->calculate_cart_total( array() );
            $order['sub_amount'] = $subscription['amount'];
            // recurring amount with discount applied
        }
        
        $post_id = $this->do_subscription_save( $order );
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'pending-payment',
        ) );
        update_post_meta( $post_id, '_sc_sub_status', 'pending' );
        sc_log_entry( $post_id, __( 'Creating subscription.', 'ncs-cart' ) );
        return $post_id;
    }
    
    public function maybe_add_paypal_enabled( $payment_methods )
    {
        if ( $this->paypal_configured() ) {
            $payment_methods['paypal'] = esc_html__( 'PayPal', 'ncs-cart' );
        }
        return $payment_methods;
    }

}
$sc_paypal = new NCS_Cart_Paypal( $this->get_plugin_name(), $this->get_version() );