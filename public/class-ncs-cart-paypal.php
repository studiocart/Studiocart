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
    private  $api_url ;
    public static  $api_sandbox = 'https://api.sandbox.paypal.com/v1' ;
    public static  $api_production = 'https://api.paypal.com/v1' ;
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
        $this->api_url = ( $this->sandbox_enabled() ? self::$api_sandbox : self::$api_production );
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
            'sc_subscription_pause_restart',
            [ $this, 'sc_paypal_pause_restart_subscription' ],
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
    
    public function sandbox_enabled()
    {
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        if ( $enableSandbox != 'disable' ) {
            return true;
        }
        return false;
    }
    
    public function maybe_add_paypal_pay_method( $payment_methods, $post_id )
    {
        // Paypal
        if ( !get_post_meta( $post_id, '_sc_disable_paypal', true ) ) {
            
            if ( $this->paypal_configured() ) {
                $icon = '<svg style="margin: -6px 5px -5px 0;" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="paypal" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" class="svg-inline--fa fa-paypal fa-w-12 fa-2x"><path fill="currentColor" d="M111.4 295.9c-3.5 19.2-17.4 108.7-21.5 134-.3 1.8-1 2.5-3 2.5H12.3c-7.6 0-13.1-6.6-12.1-13.9L58.8 46.6c1.5-9.6 10.1-16.9 20-16.9 152.3 0 165.1-3.7 204 11.4 60.1 23.3 65.6 79.5 44 140.3-21.5 62.6-72.5 89.5-140.1 90.3-43.4.7-69.5-7-75.3 24.2zM357.1 152c-1.8-1.3-2.5-1.8-3 1.3-2 11.4-5.1 22.5-8.8 33.6-39.9 113.8-150.5 103.9-204.5 103.9-6.1 0-10.1 3.3-10.9 9.4-22.6 140.4-27.1 169.7-27.1 169.7-1 7.1 3.5 12.9 10.6 12.9h63.5c8.6 0 15.7-6.3 17.4-14.9.7-5.4-1.1 6.1 14.4-91.3 4.6-22 14.3-19.7 29.3-19.7 71 0 126.4-28.8 142.9-112.3 6.5-34.8 4.6-71.4-23.8-92.6z" class=""></path></svg>';
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
        $access_token = $this->sc_paypal_oauthtoken();
        $ret = '';
        $payment_intent = trim( $order->transaction_id );
        $sc_currency = get_option( '_sc_currency' );
        $amount = $data['refund_amount'];
        
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
            'description' => 'Cancelled in Studiocart',
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
            $ret = 'Error:' . curl_error( $ch1 );
        }
        curl_close( $ch1 );
        $respons = json_decode( $result );
        
        if ( $respons->state == 'completed' ) {
            $order->refund_log( $data['refund_amount'], $respons->id );
            return;
        } else {
            $ret = $respons->message;
        }
        
        throw new Exception( $ret );
    }
    
    public function sc_paypal_pause_restart_subscription( $return, $sub, $type )
    {
        if ( $sub->pay_method != 'paypal' ) {
            return $return;
        }
        $status = 'paused';
        $paypalUrl = $this->api_url . '/billing/subscriptions/' . $sub->subscription_id . '/suspend';
        
        if ( $type == 'started' ) {
            $paypalUrl = $this->api_url . '/billing/subscriptions/' . $sub->subscription_id . '/activate';
            $status = 'active';
        }
        
        $sub_args = array(
            'reason' => $type . ' subscription',
        );
        $response = $this->doCurlRequest( $paypalUrl, $sub_args );
        return $this->handleCurlResponse( $response, $sub, $status );
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
            'item_name'     => $order->product_name,
            'currency_code' => strtoupper( $sc_currency ),
            'custom'        => json_encode( apply_filters(
            'sc_paypal_custom_payment_vars',
            array(
            'order_id' => $order->id,
        ),
            $order->get_data(),
            false
        ) ),
            'cancel_return' => stripslashes( $order->cancel_url ),
            'notify_url'    => stripslashes( get_site_url() . '/sc-webhook/paypal' ),
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
            
            if ( $sub->free_trial_days || !$sub->free_trial_days && $order->amount != $sub_amount ) {
                $data['a1'] = $order->amount;
                
                if ( !$sub->free_trial_days ) {
                    // add trial period and reduce # of installments by 1
                    if ( isset( $data["srt"] ) ) {
                        $data["srt"] -= 1;
                    }
                    $data['p1'] = $sub->sub_frequency;
                    $data['t1'] = strtoupper( substr( $sub->sub_interval, 0, 1 ) );
                } else {
                    $data['p1'] = $sub->free_trial_days;
                    $data['t1'] = 'D';
                }
            
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
        $data['lc'] = ( !empty($order->country) ? $order->country : get_option( '_sc_country', 'US' ) );
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
        $order = apply_filters( 'sc_after_order_load_from_post', $order );
        $sc_product_id = intval( $_POST['sc_product_id'] );
        $order->gateway_mode = ( get_option( '_sc_paypal_enable_sandbox' ) != 'disable' ? 'test' : 'live' );
        if ( $order->plan->type == 'recurring' ) {
            $is_sub = true;
        }
        if ( is_array( $order->order_bumps ) && !$is_sub ) {
            foreach ( $order->order_bumps as $bump ) {
                // do order bumps have a subscription?
                if ( isset( $bump['plan'] ) && $bump['plan']->type == 'recurring' ) {
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
        
        if ( @$scp->upsell_path && $scp->confirmation != 'redirect' ) {
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
        // handle wishlist member redirect url
        
        if ( isset( $_GET['wlfrom'] ) ) {
            $url_components = parse_url( $_GET['wlfrom'] );
            parse_str( $url_components['query'], $params );
            if ( isset( $params['sc-pp'] ) && isset( $params['sc-order'] ) ) {
                $_GET = $params;
            }
        }
        
        // add tracking/redirect for initial charge
        
        if ( isset( $_GET['sc-pp'] ) && isset( $_GET['sc-order'] ) ) {
            $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
            $paypalPDT = ( $enableSandbox != 'disable' ? get_option( '_sc_paypal_sandbox_pdt_token' ) : get_option( '_sc_paypal_pdt_token' ) );
            $paypalUrl = ( $enableSandbox != 'disable' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' );
            $order_id = intval( $_GET['sc-order'] );
            
            if ( !$scp || $scp->ID != $_GET['sc-pid'] ) {
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
                
                if ( !is_user_logged_in() && get_post_meta( $order_id, '_sc_auto_login', true ) ) {
                    $user_id = get_post_meta( $order_id, '_sc_user_account', true );
                    
                    if ( $user_id ) {
                        wp_set_current_user( $user_id );
                        wp_set_auth_cookie( $user_id );
                    }
                
                }
            
            }
            
            
            if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
                // Show downsell
                
                if ( isset( $_GET['sc-oto'] ) && $_GET['sc-oto'] == 0 && $scp->upsell_path ) {
                    $_POST['sc_downsell_nonce'] = wp_create_nonce( 'studiocart_downsell-' . $order_info['ID'] );
                    return;
                }
                
                // Show upsell
                
                if ( $scp->upsell_path && !isset( $_GET['sc-oto'] ) ) {
                    $_POST['sc_order'] = $order_info;
                    $_POST['sc_upsell_nonce'] = wp_create_nonce( 'studiocart_upsell-' . $order_info['ID'] );
                    return;
                }
            
            }
            
            do_action( 'studiocart_checkout_complete', $order_id, $scp );
            
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
            sc_redirect( $return_url );
            exit;
        }
    
    }
    
    private function run_pdt_check( $order_info, $paypal_data )
    {
        // check subscription (if exists) to see if this is an upsell
        $order = new ScrtOrder( $order_info['ID'] );
        $sub = false;
        if ( isset( $order_info['subscription_id'] ) && $order_info['subscription_id'] ) {
            $sub = new ScrtSubscription( $order_info['subscription_id'] );
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
        
        // grab Studiocart order/subscription IDs from custom meta
        
        if ( isset( $final_data['custom'] ) ) {
            $custom = urldecode( $final_data['custom'] );
            $subscription_id = false;
            
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
            
            if ( !$order || $order->id != $order_id ) {
                $order = new ScrtOrder( $order_id );
            }
            $order->transaction_id = $final_data['txn_id'];
            $order->store();
            
            if ( $subscription_id ) {
                if ( !$sub || $sub->id != $subscription_id ) {
                    $sub = new ScrtSubscription( $subscription_id );
                }
                $sub->subscription_id = $final_data['subscr_id'];
                $sub->store();
            }
        
        }
        
        
        if ( !empty($final_data['payment_status']) ) {
            $status = strtolower( $final_data['payment_status'] );
            
            if ( $status == 'completed' ) {
                $order->status = 'paid';
                $order->payment_status = $final_data['payment_status'];
                $order->store();
                
                if ( $subscription_id ) {
                    $sub->status = 'active';
                    $sub->sub_status = 'payment received';
                    $sub->store();
                }
                
                if ( $order->auto_login && $order->user_account ) {
                    
                    if ( !is_user_logged_in() && $order->user_account ) {
                        wp_set_current_user( $order->user_account );
                        wp_set_auth_cookie( $order->user_account );
                    }
                
                }
            }
        
        }
    
    }
    
    public function maybe_add_paypal_enabled( $payment_methods )
    {
        if ( $this->paypal_configured() ) {
            $payment_methods['paypal'] = esc_html__( 'PayPal', 'ncs-cart' );
        }
        return $payment_methods;
    }
    
    /**
     * Send Curl request to Paypal API
     * @param string $paypalUrl Paypal API
     * @param array $args Post Data
     */
    public function doCurlRequest( $paypalUrl, $args = array() )
    {
        $chs = curl_init();
        curl_setopt( $chs, CURLOPT_URL, $paypalUrl );
        curl_setopt( $chs, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt( $chs, CURLOPT_POST, 1 );
        curl_setopt( $chs, CURLOPT_SSL_VERIFYHOST, false );
        if ( !empty($args) ) {
            curl_setopt( $chs, CURLOPT_POSTFIELDS, json_encode( $args ) );
        }
        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer ' . $this->sc_paypal_oauthtoken();
        curl_setopt( $chs, CURLOPT_HTTPHEADER, $headers );
        $result = curl_exec( $chs );
        if ( curl_errno( $chs ) ) {
            echo  'Error:' . curl_error( $chs ) ;
        }
        curl_close( $chs );
        $results = json_decode( $result );
        return $results;
    }
    
    public function handleCurlResponse( $response, $sub, $status = 'active' )
    {
        $return = false;
        
        if ( empty($response) ) {
            $return = true;
            $sub->status = $status;
            $sub->sub_status = $status;
            $sub->store();
        } else {
            
            if ( isset( $response->message ) ) {
                esc_html_e( $response->message, 'ncs-cart' );
            } else {
                if ( isset( $response->error_description ) ) {
                    esc_html_e( $response->error_description, 'ncs-cart' );
                }
            }
        
        }
        
        return $return;
    }

}
$sc_paypal = new NCS_Cart_Paypal( $this->get_plugin_name(), $this->get_version() );