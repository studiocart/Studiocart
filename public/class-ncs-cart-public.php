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
class NCS_Cart_Public
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
     * The prefix of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $prefix    The current version of this plugin.
     */
    public  $prefix ;
    public static  $lastaction = false ;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name = '', $version = '', $prefix = '' )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;
        add_shortcode( 'studiocart-form', array( $this, 'sc_product_shortcode' ) );
        add_shortcode( 'studiocart-receipt', array( $this, 'sc_receipt_shortcode' ) );
        add_shortcode( 'studiocart_account', array( $this, 'sc_my_account_page_shortcode' ) );
        add_action(
            'sc_order_pending',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_order_complete',
            [ $this, 'do_order_complete_functions' ],
            10,
            3
        );
        add_action(
            'sc_order_refunded',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_renewal_payment',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_renewal_failed',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_renewal_uncollectible',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_subscription_active',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_subscription_canceled',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_subscription_past_due',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action(
            'sc_subscription_completed',
            [ $this, 'do_order_integration_functions' ],
            10,
            3
        );
        add_action( 'wp_ajax_create_payment_change_request_intent', [ $this, 'create_payment_change_request_intent' ] );
        add_action( 'wp_ajax_nopriv_create_payment_change_request_intent', [ $this, 'create_payment_change_request_intent' ] );
        add_action( 'wp_ajax_payment_change_subscription', [ $this, 'payment_change_subscription' ] );
        add_action( 'wp_ajax_nopriv_payment_change_subscription', [ $this, 'payment_change_subscription' ] );
        add_action( 'wp_ajax_get_match_tax_rate', [ $this, 'get_match_tax_rate' ] );
        add_action( 'wp_ajax_nopriv_get_match_tax_rate', [ $this, 'get_match_tax_rate' ] );
        add_action( 'sc_before_create_main_order', array( $this, 'validate_order_form_submission' ) );
        add_action( 'sc_before_create_stripe_payment_intent', array( $this, 'validate_order_form_submission' ) );
    }
    
    /**
     * Do order integrations
     *
     * @since    2.1.29
     */
    public function do_order_integration_functions( $status, $order_data, $order_type = 'main' )
    {
        if ( self::$lastaction == $order_data['product_id'] . ':' . $status ) {
            return;
        }
        
        if ( get_post_meta( $order_data['ID'], '_sc_renewal_order', true ) ) {
            if ( $status == 'paid' ) {
                $status = 'renewal';
            }
        } else {
            if ( in_array( $status, [ 'failed', 'uncollectible' ] ) ) {
                // only run these integrations for subscription renewals
                return;
            }
        }
        
        sc_do_integrations( $order_data['product_id'], $order_data, $status );
        studiocart_notification_send( $status, $order_data['ID'] );
        self::$lastaction = $order_data['product_id'] . ':' . $status;
    }
    
    /**
     * Do order complete functions.
     *
     * @since    2.1.0
     */
    public function do_order_complete_functions( $status, $order_data, $order_type = 'main' )
    {
        if ( self::$lastaction == $order_data['product_id'] . ':' . $status ) {
            return;
        }
        sc_do_integrations( $order_data['product_id'], $order_data );
        sc_do_notifications( $order_data );
        
        if ( $order_type != 'bump' ) {
            sc_maybe_update_stock( $order_data['product_id'] );
            studiocart_notification_send( $status, $order_data );
        }
        
        self::$lastaction = $order_data['product_id'] . ':' . $status;
    }
    
    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Nc_Cart_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Nc_Cart_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/ncs-cart-public.css',
            array(),
            $this->version,
            'all'
        );
        wp_enqueue_style(
            'sc-selectize-default',
            plugin_dir_url( __FILE__ ) . 'css/selectize.default.css',
            array(),
            $this->version,
            'all'
        );
    }
    
    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Nc_Cart_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Nc_Cart_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */
        global  $sc_stripe, $sc_currency, $scp ;
        
        if ( !$scp && isset( $_GET['sc-order'] ) && isset( $_GET['sc-pid'] ) ) {
            $sc_product_id = intval( $_GET['sc-pid'] );
            $scp = sc_setup_product( $sc_product_id );
        }
        
        wp_enqueue_script(
            'sc-script-selectize',
            plugin_dir_url( __FILE__ ) . 'js/selectize.js',
            array( 'jquery' ),
            $this->version,
            true
        );
        wp_register_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/ncs-cart-public.js',
            array( 'jquery' ),
            $this->version,
            true
        );
        wp_localize_script( $this->plugin_name, 'sc_translate_frontend', sc_translate_js( 'ncs-cart-public.js' ) );
        wp_localize_script( $this->plugin_name, 'sc_country_select_states', sc_states_list() );
        wp_localize_script( $this->plugin_name, 'sc_currency', sc_currency_settings() );
        if ( count( NCS_Cart_Tax::get_tax_rate() ) > 0 && get_option( '_sc_tax_enable', false ) || get_option( '_sc_vat_enable', false ) ) {
            wp_localize_script( $this->plugin_name, 'sc_tax_settings', array(
                'nonce' => wp_create_nonce( 'tax-ajax-nonce' ),
            ) );
        }
        $user = [ $_SERVER['REMOTE_ADDR'] ];
        if ( isset( $_GET['email'] ) ) {
            $user[] = sanitize_text_field( $_GET['email'] );
        }
        wp_localize_script( $this->plugin_name, 'sc_user', $user );
        
        if ( isset( $sc_stripe['pk'] ) ) {
            $stripe = $sc_stripe['pk'];
            wp_localize_script( $this->plugin_name, 'stripe_key', array( $stripe ) );
            wp_register_script( 'stripe-api-v3', 'https://js.stripe.com/v3/' );
            if ( get_post_type() == 'sc_product' || isset( $_POST['sc_purchase_amount'] ) ) {
                wp_enqueue_script( 'stripe-api-v3' );
            }
        }
        
        wp_enqueue_script( $this->plugin_name );
        $studiocart = array(
            'ajax'    => admin_url( 'admin-ajax.php' ),
            'page_id' => get_the_ID(),
        );
        
        if ( is_object( $scp ) && get_option( '_sc_fb_add_payment_info' ) ) {
            $studiocart['fb_add_payment_info'] = 'enabled';
            $studiocart['content_id'] = $scp->ID;
        }
        
        
        if ( isset( $_POST['sc_purchase_amount'] ) || isset( $_GET['sc-order'] ) && !isset( $_GET['sc-oto'] ) ) {
            
            if ( !isset( $_POST['sc_purchase_amount'] ) ) {
                $order_id = intval( $_GET['sc-order'] );
                $order_info = (array) sc_setup_order( $order_id );
                $_POST['sc_purchase_amount'] = $order_info['amount'];
                $_POST['sc_order_id'] = $order_id;
                if ( isset( $order_info['order_bumps'] ) ) {
                    $_POST['sc-orderbump'] = $order_info['order_bumps'];
                }
            }
            
            
            if ( get_option( '_sc_fb_purchase' ) ) {
                $studiocart['amount'] = $_POST['sc_purchase_amount'];
                $studiocart['currency'] = $sc_currency;
                $studiocart['fb_purchase_event'] = 'enabled';
                unset( $studiocart['fb_add_payment_info'], $studiocart['content_id'] );
            }
        
        }
        
        if ( isset( $_GET['sc-order'] ) ) {
            $studiocart = apply_filters(
                'studiocart_script_vars',
                $studiocart,
                intval( $_GET['sc-order'] ),
                $scp
            );
        }
        
        if ( !empty($studiocart) ) {
            wp_localize_script( $this->plugin_name, 'studiocart', $studiocart );
            add_action( 'wp_footer', array( $this, 'sc_js_fb_events' ) );
        }
    
    }
    
    public function sc_js_fb_events()
    {
        global  $scp ;
        
        if ( isset( $_POST['sc_order_id'] ) ) {
            $order_info = (array) sc_setup_order( $_POST['sc_order_id'] );
            if ( isset( $scp->tracking_main ) ) {
                echo  wp_specialchars_decode( sc_personalize( $scp->tracking_main, $order_info ), 'ENT_QUOTES' ) ;
            }
            if ( isset( $_POST['sc-orderbump'] ) && isset( $scp->tracking_bump ) ) {
                echo  wp_specialchars_decode( sc_personalize( $scp->tracking_bump, $order_info ), 'ENT_QUOTES' ) ;
            }
        } else {
            
            if ( isset( $_GET['sc-oto'] ) && $_GET['sc-oto'] > 0 ) {
                $order_info = (array) sc_setup_order( $_GET['sc-oto'] );
                echo  wp_specialchars_decode( sc_personalize( $scp->tracking_oto, $order_info ), 'ENT_QUOTES' ) ;
            } else {
                
                if ( isset( $_GET['sc-oto-2'] ) && $_GET['sc-oto-2'] > 0 ) {
                    $order_info = (array) sc_setup_order( $_GET['sc-oto'] );
                    echo  wp_specialchars_decode( sc_personalize( $scp->tracking_oto_2, $order_info ), 'ENT_QUOTES' ) ;
                }
            
            }
        
        }
        
        
        if ( isset( $studiocart['fb_purchase_event'] ) || isset( $studiocart['fb_add_payment_info'] ) ) {
            ?>
        <script>
            
            jQuery('document').ready(function($){   
                if ( typeof fbq !== "undefined") {
                    if( 'undefined' !== typeof studiocart.fb_purchase_event && 
                    'enabled' == studiocart.fb_purchase_event && 
                    typeof studiocart.amount !== 'undefined') {
                        <?php 
            $fbevent = ( isset( $_POST['fb_event'] ) ? $_POST['fb_event'] : 'Purchase' );
            ?>
                        fbq('track', '<?php 
            echo  sanitize_text_field( $fbevent ) ;
            ?>', {
                            currency: studiocart.currency, 
                            value: studiocart.amount
                        });
                    }
                    
                    if( 'undefined' !== typeof studiocart.fb_add_payment_info && 
                    'enabled' == studiocart.fb_add_payment_info ) {                        
                        var tracked = false;
                        <?php 
            
            if ( !$scp ) {
                ?>
                            var content_id = studiocart.content_id;
                        <?php 
            } else {
                ?>
                            var content_id = (!studiocart.content_id) ? <?php 
                echo  $scp->ID ;
                ?> : studiocart.content_id;
                        <?php 
            }
            
            ?>
                        if ( !tracked && typeof fbq !== "undefined" ) {
                            $('#sc-payment-form input').focus(function(){
                                fbq('track', 'AddPaymentInfo', {
                                    content_ids: [content_id],
                                    eventref: '' // or set to empty string
                                });
                            });
                            tracked = true;
                        }
                    }
                }
            });
        </script>
        <?php 
        }
    
    }
    
    public function sc_product_shortcode( $atts )
    {
        global  $post, $sc_stripe ;
        if ( isset( $sc_stripe['pk'] ) ) {
            wp_enqueue_script( 'stripe-api-v3' );
        }
        if ( !isset( $atts['id'] ) || !$atts['id'] ) {
            $atts['id'] = $post->ID;
        }
        // 2-step option now stored in _sc_display meta
        
        if ( get_post_meta( $atts['id'], '_sc_show_2_step', true ) ) {
            update_post_meta( $atts['id'], '_sc_display', 'two_step' );
            delete_post_meta( $atts['id'], '_sc_show_2_step' );
        }
        
        $default_template = get_post_meta( $atts['id'], '_sc_display', true );
        if ( $default_template == 'two_step' ) {
            $default_template = '2-step';
        }
        extract( shortcode_atts( array(
            'product_id'  => $atts['id'],
            'hide_labels' => false,
            'template'    => false,
            'skin'        => false,
            'coupon'      => false,
            'builder'     => false,
        ), $atts ) );
        ob_start();
        if ( $skin ) {
            $template = $skin;
        }
        if ( !$template ) {
            $template = $default_template;
        }
        
        if ( file_exists( plugin_dir_path( __FILE__ ) . 'templates/checkout-shortcode-' . $template . '.php' ) ) {
            include plugin_dir_path( __FILE__ ) . 'templates/checkout-shortcode-' . $template . '.php';
        } else {
            include plugin_dir_path( __FILE__ ) . 'templates/checkout-shortcode.php';
        }
        
        $output_string = ob_get_contents();
        ob_end_clean();
        return $output_string;
    }
    
    public function sc_receipt_shortcode()
    {
        
        if ( isset( $_GET['sc-order'] ) ) {
            ob_start();
            sc_order_details( intval( $_GET['sc-order'] ) );
            $output_string = ob_get_contents();
            ob_end_clean();
            return $output_string;
        } else {
            return;
        }
    
    }
    
    public function sc_product_template( $single )
    {
        global  $post ;
        /* Checks for single template by post type */
        if ( $post->post_type == 'sc_product' ) {
            if ( !get_option( '_sc_disable_template' ) ) {
                if ( file_exists( plugin_dir_path( __FILE__ ) . 'templates/checkout1.php' ) ) {
                    return plugin_dir_path( __FILE__ ) . 'templates/checkout1.php';
                }
            }
        }
        return $single;
    }
    
    public function sc_query_vars( $qvars )
    {
        $qvars[] = 'coupon';
        $qvars[] = 'plan';
        $qvars[] = 'sc-preview';
        return $qvars;
    }
    
    public function sc_redirect()
    {
        /*if ( sc_fs()->is__premium_only() ) {
              if ( get_post_type() == 'sc_offer') {
                  if(!isset($_POST['sc_upsell_nonce']) && !isset($_POST['sc_downsell_nonce']) && !current_user_can('edit_posts') ) {
                      sc_redirect(home_url());
                  }
              }   
          }*/
        // page redirect
        $sc_id = intval( get_post_meta( get_the_ID(), '_sc_related_product', true ) );
        
        if ( $sc_id ) {
            global  $scp ;
            $scp = sc_setup_product( $sc_id );
            
            if ( sc_is_cart_closed() ) {
                switch ( $scp->cart_close_action ) {
                    case 'redirect':
                        $redirect = $scp->cart_redirect;
                        break;
                    default:
                        $redirect = get_permalink( $sc_id );
                        break;
                }
                sc_redirect( $redirect );
            }
        
        }
        
        // product page redirect
        if ( get_post_type() != 'sc_product' || isset( $_GET['sc-order'] ) || isset( $_POST['sc_purchase_amount'] ) ) {
            return;
        }
        $cart_close_action = get_post_meta( get_the_ID(), '_sc_cart_close_action', true );
        if ( sc_is_cart_closed( get_the_ID() ) ) {
            
            if ( $cart_close_action == 'redirect' ) {
                $redirect = get_post_meta( get_the_ID(), '_sc_cart_redirect', true );
                sc_redirect( esc_url( $redirect ) );
            }
        
        }
        
        if ( get_post_meta( get_the_ID(), '_sc_hide_product_page', true ) ) {
            $redirect = get_post_meta( get_the_ID(), '_sc_product_page_redirect', true );
            
            if ( !$redirect ) {
                $redirect = get_home_url();
            } else {
                $redirect = get_permalink( $redirect );
            }
            
            sc_redirect( esc_url( $redirect ) );
        }
    
    }
    
    public function sc_customer_csv_export()
    {
        if ( get_query_var( 'sc-csv-export' ) ) {
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'export/csv_export.php' ) ) {
                require plugin_dir_path( __FILE__ ) . 'export/csv_export.php';
            }
        }
    }
    
    public function sc_subscription_renew_reminder()
    {
        if ( get_query_var( 'sc-renew-reminde' ) ) {
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'webhooks/renew_reminde.php' ) ) {
                require plugin_dir_path( __FILE__ ) . 'webhooks/renew_reminde.php';
            }
        }
    }
    
    public function sc_invoices_download()
    {
        if ( get_query_var( 'sc-invoice' ) ) {
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'partials/invoice-pdf.php' ) ) {
                require plugin_dir_path( __FILE__ ) . 'partials/invoice-pdf.php';
            }
        }
    }
    
    public function sc_stripe_webhook()
    {
        
        if ( get_query_var( 'sc-api' ) ) {
            $gateway = sanitize_text_field( get_query_var( 'sc-api' ) );
            
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'webhooks/' . $gateway . '.php' ) ) {
                require plugin_dir_path( __FILE__ ) . 'webhooks/' . $gateway . '.php';
            } else {
                do_action( 'sc_gateway_webhook' );
            }
        
        }
    
    }
    
    public function sc_webhook_rewrite_rule()
    {
        $page_slug = 'sc-webhook';
        // slug of the page you want to be shown to
        $param = 'sc-api';
        // param name you want to handle on the page
        add_rewrite_rule( 'sc-webhook/?([^/]*)', 'index.php?pagename=' . $page_slug . '&' . $param . '=$matches[1]', 'top' );
    }
    
    public function sc_api_query_vars( $qvars )
    {
        $qvars[] = 'sc-api';
        $qvars[] = 'sc-csv-export';
        $qvars[] = 'sc-renew-reminde';
        $qvars[] = 'sc-invoice';
        return $qvars;
    }
    
    function public_product_name( $title, $id = null )
    {
        $pid = false;
        if ( !$id ) {
            $pid = get_the_ID();
        }
        switch ( $id ) {
            case $pid:
                $id = $pid;
                break;
            case is_object( $id ):
                if ( is_object( $id ) ) {
                    $id = $id->ID;
                }
                break;
        }
        if ( !is_admin() ) {
            if ( get_post_type( $id ) == 'sc_product' && ($name = get_post_meta( $id, '_sc_product_name', true )) ) {
                return $name;
            }
        }
        return $title;
    }
    
    public function sc_process_payment()
    {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        global  $scp ;
        // add tracking/redirect for initial charge
        if ( !isset( $_POST['sc_process_payment'] ) ) {
            return;
        }
        $sc_product_id = intval( $_POST['sc_product_id'] );
        $order_id = intval( $_POST['sc_order_id'] );
        $scp = sc_setup_product( $sc_product_id );
        $order_info = (array) sc_setup_order( $order_id );
        if ( isset( $scp->autologin ) && !is_user_logged_in() && in_array( $order_info['status'], [ 'paid', 'completed' ] ) && $order_info['user_account'] ) {
            sc_maybe_auto_login_user( $order_info['user_account'], $order_id );
        }
        $_POST['sc_purchase_amount'] = $order_info['amount'];
        
        if ( isset( $scp->redirect_url ) ) {
            $redirect = esc_url_raw( sc_personalize( $scp->redirect_url, $order_info ) );
            sc_redirect( $redirect );
        }
        
        return;
    }
    
    public function update_stripe_order_status()
    {
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        // Do integrations if charge status = suceeded
        
        if ( $_POST['paymentIntent']['status'] == 'succeeded' ) {
            $post_id = intval( $_POST['response']['order_id'] );
            $order = new ScrtOrder( $post_id );
            $order->status = 'paid';
            $order->payment_status = sanitize_text_field( $_POST['paymentIntent']['status'] );
            $order->store();
        }
        
        echo  json_encode( $_POST['response'] ) ;
        exit;
    }
    
    public function validate_order_form_submission()
    {
        global  $scp ;
        
        if ( !isset( $_POST['sc_product_id'] ) ) {
            echo  json_encode( array(
                'error' => __( "There was a problem with your submission, please refresh the page and try again.", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        
        if ( !$scp ) {
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $scp = sc_setup_product( $sc_product_id );
        }
        
        $required = array();
        $errors = array();
        $messages = array();
        if ( get_option( '_sc_terms_url' ) ) {
            $required[] = 'sc_accept_terms';
        }
        if ( get_option( '_sc_privacy_url' ) ) {
            $required[] = 'sc_accept_privacy';
        }
        
        if ( $scp->show_optin_cb ) {
            $req_consent = apply_filters( 'sc_consent_required', $scp->optin_required, $scp );
            if ( $req_consent ) {
                $required[] = 'sc_consent';
            }
        }
        
        $defaultfields = array(
            'firstname' => array(
            'name'     => 'first_name',
            'required' => true,
        ),
            'lastname'  => array(
            'name'     => 'last_name',
            'required' => true,
        ),
            'email'     => array(
            'name'     => 'email',
            'required' => true,
        ),
            'phone'     => array(
            'name'     => 'phone',
            'required' => false,
        ),
        );
        $address_fields = array(
            'country'  => array(
            'name'     => 'country',
            'required' => true,
        ),
            'address1' => array(
            'name'     => 'address1',
            'required' => true,
        ),
            'city'     => array(
            'name'     => 'city',
            'required' => true,
        ),
            'state'    => array(
            'name'     => 'state',
            'required' => true,
        ),
            'zip'      => array(
            'name'     => 'zip',
            'required' => true,
        ),
        );
        // default fields
        $defaultfields = apply_filters( 'studiocart_order_form_fields', $defaultfields, $scp );
        foreach ( $defaultfields as $k => $field ) {
            if ( isset( $field['required'] ) && $field['required'] ) {
                $required[] = $field['name'];
            }
        }
        // address fields
        
        if ( isset( $scp->show_address_fields ) ) {
            $address_fields = apply_filters( 'studiocart_order_form_address_fields', $address_fields, $scp );
            foreach ( $address_fields as $k => $field ) {
                if ( isset( $field['required'] ) && $field['required'] ) {
                    $required[] = $field['name'];
                }
            }
        }
        
        // custom fields
        
        if ( isset( $scp->custom_fields ) ) {
            $required['sc_custom_fields'] = array();
            foreach ( $scp->custom_fields as $field ) {
                if ( is_user_logged_in() ) {
                    if ( ($field['field_type'] == 'password' || isset( $field['field_username'] )) && !current_user_can( 'administrator' ) ) {
                        continue;
                    }
                }
                if ( isset( $field['field_required'] ) && $field['field_required'] ) {
                    $required['sc_custom_fields'][] = esc_attr( $field['field_id'] );
                }
            }
        }
        
        // do validation
        if ( !empty($required) ) {
            foreach ( $required as $key ) {
                
                if ( !is_array( $key ) ) {
                    if ( !isset( $_POST[$key] ) || !$_POST[$key] ) {
                        $errors[] = [
                            'field'   => $key,
                            'message' => __( "This field is required", "ncs-cart" ),
                        ];
                    }
                } else {
                    foreach ( $key as $k ) {
                        if ( !isset( $_POST['sc_custom_fields'][$k] ) || !$_POST['sc_custom_fields'][$k] ) {
                            $errors[] = [
                                'field'   => 'sc_custom_fields[' . $k . ']',
                                'message' => __( "This field is required", "ncs-cart" ),
                            ];
                        }
                    }
                }
            
            }
        }
        // add error messages
        if ( !empty($errors) ) {
            $messages[] = '• ' . __( "Required fields missing", "ncs-cart" );
        }
        // check email
        if ( isset( $_POST['email'] ) ) {
            
            if ( !is_email( sanitize_text_field( $_POST['email'] ) ) ) {
                $messages[] = '• ' . __( "Invalid email address", "ncs-cart" );
                $errors[] = [
                    'field'   => 'email',
                    'message' => __( 'Enter a valid email', "ncs-cart" ),
                ];
            }
        
        }
        
        if ( !empty($messages) ) {
            echo  json_encode( array(
                'error'  => __( "There was a problem with your submission, please check your info and try again:", "ncs-cart" ) . "\n" . implode( "\n", $messages ),
                'fields' => $errors,
            ) ) ;
            exit;
        }
    
    }
    
    public function create_payment_intent()
    {
        global  $sc_stripe, $sc_currency, $scp ;
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        do_action( 'sc_before_create_stripe_payment_intent' );
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        \Stripe\Stripe::setApiVersion( "2020-03-02" );
        //$customer = \Stripe\Customer::create();
        //print_r($_POST); exit();
        $email = sanitize_text_field( $_POST['email'] );
        $name = sanitize_text_field( $_POST['first_name'] );
        $last_name = sanitize_text_field( $_POST['last_name'] );
        $args = array(
            'email'       => $email,
            'name'        => $name . ' ' . $last_name,
            'description' => $name . ' ' . $last_name,
        );
        if ( isset( $_POST['phone'] ) ) {
            $args['phone'] = sanitize_text_field( $_POST['phone'] );
        }
        
        if ( isset( $_POST['country'] ) && isset( $_POST['address1'] ) && isset( $_POST['city'] ) && isset( $_POST['state'] ) && isset( $_POST['zip'] ) ) {
            $country = sanitize_text_field( $_POST['country'] );
            $address1 = sanitize_text_field( $_POST['address1'] );
            $city = sanitize_text_field( $_POST['city'] );
            $state = sanitize_text_field( $_POST['state'] );
            $zip = sanitize_text_field( $_POST['zip'] );
            $args['address'] = [
                'line1'       => $address1,
                'postal_code' => $zip,
                'city'        => $city,
                'state'       => $state,
                'country'     => $country,
            ];
        }
        
        $stripe = new \Stripe\StripeClient( $apikey );
        $customer = $stripe->customers->all( [
            'email' => $email,
            'limit' => 1,
        ] );
        
        if ( !empty($customer->data) ) {
            $customer = $customer->data[0];
        } else {
            $customer = \Stripe\Customer::create( $args );
        }
        
        $_POST['customerId'] = $customer->id;
        $sc_product_id = intval( $_POST['sc_product_id'] );
        $sc_option_id = sanitize_text_field( $_POST['sc_product_option'] );
        // setup product info
        $scp = sc_setup_product( $sc_product_id );
        // setup order info
        $order = new ScrtOrder();
        $order->load_from_post();
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        // stripe only fields
        if ( $order->pay_method == 'stripe' && $order->amount && isset( $sc_stripe['mode'] ) ) {
            $order->gateway_mode = $sc_stripe['mode'];
        }
        $amount = $order->amount;
        $sub_total = $order->invoice_subtotal;
        $tax_applied = false;
        if ( $this->tax_amount > 0 ) {
            $tax_applied = true;
        }
        // Setup payment intent and return result
        $amount_for_stripe = get_sc_price( $amount, $sc_currency );
        $client_secret = '';
        $intent_id = '';
        $descriptor = get_option( '_sc_stripe_descriptor', false );
        if ( !$descriptor ) {
            $descriptor = get_bloginfo( 'name' ) ?? sc_get_public_product_name( $sc_product_id );
        }
        $bump_plan = false;
        if ( is_array( $order->order_bumps ) ) {
            foreach ( $order->order_bumps as $bump ) {
                // do order bumps have a subscription?
                
                if ( isset( $bump['plan'] ) ) {
                    $bump_plan = true;
                    break;
                }
            
            }
        }
        $order_id = "";
        
        if ( ($order->plan->type == 'one-time' || $order->plan->type == 'pwyw') && !$bump_plan ) {
            $intent = \Stripe\PaymentIntent::create( [
                'amount'               => $amount_for_stripe,
                'currency'             => $sc_currency,
                'statement_descriptor' => preg_replace( "/[^0-9a-zA-Z ]/", '', substr( $descriptor, 0, 22 ) ),
                'description'          => substr( sc_get_public_product_name( $sc_product_id ), 0, 22 ),
                'customer'             => $customer->id,
                'confirmation_method'  => 'automatic',
                'setup_future_usage'   => 'off_session',
                'metadata'             => [
                'sc_product_id' => $sc_product_id,
                'origin'        => get_site_url(),
            ],
            ] );
            $client_secret = $intent->client_secret;
            $intent_id = $intent->id;
            $order->transaction_id = sanitize_text_field( $intent_id );
            $order_id = $order->store();
            $intent = \Stripe\PaymentIntent::update( $intent_id, [
                'metadata' => [
                'sc_order_id' => $order_id,
            ],
            ] );
        }
        
        
        if ( $customer ) {
            echo  json_encode( array(
                'clientSecret'     => $client_secret,
                'intent_id'        => $intent_id,
                'customer_id'      => $customer->id,
                'amount'           => $amount,
                'sub_total'        => $sub_total,
                'tax_applied'      => $tax_applied,
                'sc_temp_order_id' => $order_id,
            ) ) ;
        } else {
            json_encode( array(
                'error' => 'There was an error, please try again.',
            ) );
        }
        
        exit;
    }
    
    public function update_payment_intent_amt( $pid = false )
    {
        global  $sc_stripe, $sc_currency ;
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        \Stripe\Stripe::setApiVersion( "2020-03-02" );
        $echo = false;
        $amount = $this->calculate_cart_total();
        $amount_for_stripe = get_sc_price( $amount, $sc_currency );
        // this is an ajax call, get pid from post data
        
        if ( !$pid ) {
            $pid = sanitize_text_field( $_POST['intent_id'] );
            $echo = true;
        }
        
        // Update payment intent amount
        try {
            $intent = \Stripe\PaymentIntent::update( $pid, [
                'amount' => $amount_for_stripe,
            ] );
        } catch ( Exception $e ) {
            echo  $e ;
            exit;
            //$_POST["sc_errors"]['messages'][] = __("There was a problem processing this order. Please try again", "ncs-cart");
            //return;
        }
        // ajax call, echo new amount and exit early;
        
        if ( isset( $_POST['sc_order_id'] ) ) {
            $order_id = intval( $_POST['sc_order_id'] );
            update_post_meta( $order_id, '_sc_amount', $amount );
            
            if ( $echo ) {
                echo  $amount ;
                exit;
            }
        
        }
        
        return $amount;
    }
    
    public function save_order_to_db( $order_info = false, $subscription = false )
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        global  $sc_stripe, $scp ;
        // order id only present for upsells
        
        if ( isset( $_POST['sc-order'] ) ) {
            $is_downsell = isset( $_POST['downsell'] );
            $oto_type = ( $is_downsell ? 'downsell' : 'upsell' );
            $order_info = (array) sc_setup_order( intval( $_POST['sc-order'] ) );
            
            if ( !wp_verify_nonce( $_POST['sc-nonce'], 'studiocart_' . $oto_type . '-' . $order_info['ID'] ) ) {
                echo  json_encode( array(
                    'error' => __( "Invalid Request", "ncs-cart" ),
                ) ) ;
                exit;
            }
        
        } else {
            // base order
            
            if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
                echo  json_encode( array(
                    'error' => __( "Invalid Request", "ncs-cart" ),
                ) ) ;
                exit;
            }
            
            do_action( 'sc_before_create_main_order' );
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $sc_option_id = sanitize_text_field( $_POST['sc_product_option'] );
            // setup product info
            $scp = sc_setup_product( $sc_product_id );
            
            if ( isset( $_POST['sc_temp_order_id'] ) ) {
                $order = new ScrtOrder( $_POST['sc_temp_order_id'] );
            } else {
                // setup order info
                $order = new ScrtOrder();
                $order->load_from_post();
            }
            
            // stripe only fields
            
            if ( $order->pay_method == 'stripe' && $order->amount && isset( $sc_stripe['mode'] ) ) {
                $order->gateway_mode = $sc_stripe['mode'];
                $order->transaction_id = sanitize_text_field( $_POST['intent_id'] );
            }
            
            // Free order
            
            if ( $order->amount == 0 ) {
                $order->pay_method = 'free';
                // Free item
                $order->status = 'completed';
                if ( !empty($order->coupon) ) {
                    $order->status = 'paid';
                }
            }
            
            // save order to db
            $post_id = $order->store();
        }
        
        $data['order_id'] = $post_id;
        $data['amount'] = $order->amount;
        $order_info = $order->get_data();
        
        if ( isset( $scp->redirect_url ) && !isset( $scp->upsell ) ) {
            $redirect = esc_url_raw( sc_personalize( $scp->redirect_url, $order_info, 'urlencode' ) );
            $data['redirect'] = $redirect;
        }
        
        // if this is a free plan skip showing upsell and redirect to thank you
        if ( !$order_info['amount'] && @$scp->upsell || !@$scp->upsell && $scp->confirmation != 'redirect' ) {
            $data['formAction'] = add_query_arg( 'sc-order', $post_id, $scp->thanks_url );
        }
        $data['formAction'] = apply_filters(
            'studiocart_post_purchase_url',
            $data['formAction'],
            $data['order_id'],
            $sc_product_id
        );
        if ( !$data['formAction'] ) {
            unset( $data['formAction'] );
        }
        echo  json_encode( $data ) ;
        exit;
    }
    
    public static function get_custom_fields_from_post( $scp )
    {
        if ( is_int( $scp ) ) {
            $scp = sc_setup_product( $scp );
        }
        $custom_fields = array();
        foreach ( $scp->custom_fields as $field ) {
            $key = str_replace( [ ' ', '.' ], [ '_', '_' ], $field['field_id'] );
            
            if ( isset( $_POST['sc_custom_fields'][$key] ) && $_POST['sc_custom_fields'][$key] != '' && $field['field_type'] != 'password' ) {
                $field_id = sanitize_text_field( $field['field_id'] );
                $value = sanitize_text_field( $_POST['sc_custom_fields'][$key] );
                $custom_fields[$field_id] = array(
                    'label' => sanitize_text_field( $field['field_label'] ),
                    'value' => $value,
                );
                
                if ( $field['field_type'] == 'select' ) {
                    $choices = [];
                    $options = explode( "\n", str_replace( "\r", "", esc_attr( $field['select_options'] ) ) );
                    for ( $i = 0 ;  $i < count( $options ) ;  $i++ ) {
                        $option = explode( ':', $options[$i] );
                        if ( count( $option ) > 1 ) {
                            
                            if ( trim( $option[0] ) == $value ) {
                                $custom_fields[$field_id]['value_label'] = trim( $option[1] );
                                break;
                            }
                        
                        }
                    }
                }
            
            }
        
        }
        return $custom_fields;
    }
    
    public static function get_custom_fields_post_data( $pid )
    {
        $custom_fields = false;
        
        if ( isset( $_POST['sc_custom_fields'] ) ) {
            $custom_fields['sc_custom_fields'] = array();
            foreach ( $_POST['sc_custom_fields'] as $key => $value ) {
                $value = sanitize_text_field( $_POST['sc_custom_fields'][$key] );
                $custom_fields['sc_custom_fields'][$key] = $value;
            }
            if ( isset( $_POST['sc-auto-login'] ) && $_POST['sc-auto-login'] == 1 ) {
                $custom_fields['sc-auto-login'] = 1;
            }
            $custom_fields['sc_product_id'] = $pid;
        }
        
        return $custom_fields;
    }
    
    public function do_subscription_save( $order )
    {
        //insert SUBSCRIPTION
        $post_id = wp_insert_post( array(
            'post_title'  => time() . " " . $order['name'],
            'post_type'   => 'sc_subscription',
            'post_status' => 'publish',
        ), FALSE );
        //update stripe meta
        update_post_meta( $post_id, '_sc_firstname', $order['firstname'] );
        update_post_meta( $post_id, '_sc_lastname', $order['lastname'] );
        update_post_meta( $post_id, '_sc_email', $order['email'] );
        update_post_meta( $post_id, '_sc_phone', $order['phone'] );
        update_post_meta( $post_id, '_sc_product_id', $order['product_id'] );
        update_post_meta( $post_id, '_sc_product_name', $order['product_name'] );
        update_post_meta( $post_id, '_sc_item_name', $order['item_name'] );
        update_post_meta( $post_id, '_sc_plan_id', $order['plan_id'] );
        update_post_meta( $post_id, '_sc_plan_price', $order_info['plan_price'] );
        update_post_meta( $post_id, '_sc_option_id', $order['option_id'] );
        update_post_meta( $post_id, '_sc_amount', $order['amount'] );
        update_post_meta( $post_id, '_sc_vat_customer_type', $order['vat_customer_type'] );
        update_post_meta( $post_id, '_sc_vat_number', $order['vat_number'] );
        update_post_meta( $post_id, '_sc_ip_address', $order['ip_address'] );
        update_post_meta( $post_id, '_sc_user_account', $order['user_account'] );
        update_post_meta( $post_id, '_sc_accept_terms', $order['accept_terms'] );
        update_post_meta( $post_id, '_sc_consent', $order['consent'] );
        update_post_meta( $post_id, '_sc_on_sale', $order['on_sale'] );
        update_post_meta( $post_id, '_sc_pay_method', $order['pay_method'] );
        update_post_meta( $post_id, '_sc_sub_amount', $order['sub_amount'] );
        update_post_meta( $post_id, '_sc_sub_item_name', $order['sub_item_name'] );
        update_post_meta( $post_id, '_sc_sub_installments', $order['sub_installments'] );
        update_post_meta( $post_id, '_sc_sub_interval', $order['sub_interval'] );
        update_post_meta( $post_id, '_sc_sub_frequency', $order['sub_frequency'] );
        update_post_meta( $post_id, '_sc_sub_next_bill_date', $order['next_bill_date'] );
        update_post_meta( $post_id, '_sc_sub_end_date', $order['sub_end_date'] );
        $vat_applied = 0;
        
        if ( isset( $order['vat'] ) && !empty($order['vat']) ) {
            $vat_applied = ($order['sub_amount'] + $order['sign_up_fee']) * $order['vat']['vat_rate'] / 100;
            $vat_applied = round( $vat_applied, 2 );
            update_post_meta( $post_id, '_sc_vat_amount', $vat_applied );
            update_post_meta( $post_id, '_sc_vat_data', $order['vat_data'] );
        }
        
        
        if ( !empty($order['tax']) && $order['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0 ) {
            $tax_applied = ($order['sub_amount'] + $order['sign_up_fee']) * $order['tax']['tax_rate'] / 100;
            $tax_applied = round( $tax_applied, 2 );
            update_post_meta( $post_id, '_sc_tax_amount', $tax_applied );
            update_post_meta( $post_id, '_sc_tax_data', $order['tax'] );
        }
        
        
        if ( !empty($order['page_id']) ) {
            update_post_meta( $post_id, '_sc_page_id', $order['page_id'] );
            update_post_meta( $post_id, '_sc_page_url', $order['page_url'] );
        }
        
        if ( $order['product_replaced'] ) {
            update_post_meta( $post_id, '_sc_product_replaced', $order['product_replaced'] );
        }
        if ( !empty($order['free_trial_days']) ) {
            update_post_meta( $post_id, '_sc_free_trial_days', $order['free_trial_days'] );
        }
        update_post_meta( $post_id, '_sc_sign_up_fee', $order['sign_up_fee'] );
        update_post_meta( $post_id, '_sc_order_id', $order['order_id'] );
        update_post_meta( $order['order_id'], '_sc_subscription_id', $post_id );
        return $post_id;
    }
    
    private function do_stripe_subscription_save( $order )
    {
        //insert SUBSCRIPTION
        $post_id = $this->do_subscription_save( $order );
        
        if ( isset( $order['subscription'] ) ) {
            $subscription = $order['subscription'];
            update_post_meta( $post_id, '_sc_sub_status', $subscription->status );
            update_post_meta( $post_id, '_sc_status', $subscription->status );
            update_post_meta( $post_id, '_sc_subscription_id', $subscription->id );
            update_post_meta( $post_id, '_sc_stripe_subscription_id', $subscription->id );
            update_post_meta( $post_id, '_sc_stripe_plan_id', $subscription->plan->id );
            update_post_meta( $post_id, '_sc_stripe_customer_id', $subscription->customer );
            update_post_meta( $post_id, '_sc_sub_customer_id', $subscription->customer );
            update_post_meta( $post_id, '_sc_sub_interval', $subscription->plan->interval );
            update_post_meta( $post_id, '_sc_sub_next_bill_date', $subscription->current_period_end );
            update_post_meta( $post_id, '_sc_stripe_mode', $order['stripe_mode'] );
        }
        
        
        if ( isset( $subscription ) ) {
            $status = $subscription->status;
        } else {
            $status = 'pending-payment';
        }
        
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => $status,
        ) );
        sc_log_entry( $post_id, __( 'Creating subscription.', 'ncs-cart' ) );
        do_action(
            'studiocart_after_order_created',
            $post_id,
            $status,
            $order
        );
        return $post_id;
    }
    
    private function find_stripe_webhook_order( $order_info )
    {
        
        if ( $order_info['pay_method'] == 'stripe' && $_POST['sc_amount'] > 0 ) {
            $args = array(
                'post_type'      => 'sc_order',
                'post_status'    => 'paid',
                'posts_per_page' => 1,
                'meta_query'     => array( array(
                'key'   => '_sc_intent_id',
                'value' => $order_info['intent_id'],
            ) ),
            );
            $posts = get_posts( $args );
            if ( !empty($posts) ) {
                return $posts[0]->ID;
            }
        }
        
        return false;
    }
    
    private function do_stripe_order_save( $order_info )
    {
        //var_dump($order_info['intent_id']);
        
        if ( $order_info['intent_id'] && $this->find_stripe_webhook_order( $order_info ) ) {
            $post_id = $this->find_stripe_webhook_order( $order_info );
            update_post_meta( $post_id, '_sc_product_id', $order_info['product_id'] );
            update_post_meta( $post_id, '_sc_item_name', $order_info['item_name'] );
            update_post_meta( $post_id, '_sc_plan_id', $order_info['plan_id'] );
            update_post_meta( $post_id, '_sc_ip_address', $order_info['ip_address'] );
            update_post_meta( $post_id, '_sc_user_account', $order_info['user_account'] );
            update_post_meta( $post_id, '_sc_accept_terms', $order_info['accept_terms'] );
            update_post_meta( $post_id, '_sc_consent', $order_info['consent'] );
            update_post_meta( $post_id, '_sc_stripe_mode', $order_info['stripe_mode'] );
            update_post_meta( $post_id, '_sc_pay_method', $order_info['pay_method'] );
            update_post_meta( $post_id, '_sc_currency', $order_info['currency'] );
            update_post_meta( $post_id, '_sc_sub_total', $order_info['amount'] );
            update_post_meta( $post_id, '_sc_vat_customer_type', $order_info['vat_customer_type'] );
            update_post_meta( $post_id, '_sc_vat_number', $order_info['vat_number'] );
            $vat_applied = 0;
            
            if ( isset( $order_info['vat'] ) && !empty($order_info['vat']) ) {
                $vat_applied = $order_info['plan_price'] * $order_info['vat']['vat_rate'] / 100;
                $vat_applied = round( $vat_applied, 2 );
                update_post_meta( $post_id, '_sc_vat_amount', $vat_applied );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['vat'] );
            }
            
            
            if ( !empty($order_info['tax']) && $order_info['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0 ) {
                $tax_applied = $order_info['plan_price'] * $order_info['tax']['tax_rate'] / 100;
                $tax_applied = round( $tax_applied, 2 );
                update_post_meta( $post_id, '_sc_tax_amount', $tax_applied );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['tax'] );
            }
            
            
            if ( isset( $order_info['us_vat_data'] ) && !empty($order_info['us_vat_data']) ) {
                update_post_meta( $post_id, '_sc_vat_amount', $order_info['us_vat_amount'] );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['us_vat_data'] );
            }
            
            
            if ( isset( $order_info['ds_vat_data'] ) && !empty($order_info['ds_vat_data']) ) {
                update_post_meta( $post_id, '_sc_vat_amount', $order_info['ds_vat_amount'] );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['ds_vat_data'] );
            }
            
            
            if ( isset( $order_info['us_tax_data'] ) && !empty($order_info['us_tax_data']) && isset( $order_info['us_tax_amount'] ) && !empty($order_info['us_tax_amount']) && !isset( $order_info['us_vat_amount'] ) ) {
                update_post_meta( $post_id, '_sc_tax_amount', $order_info['us_tax_amount'] );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['us_tax_data'] );
            }
            
            
            if ( isset( $order_info['ds_tax_data'] ) && !empty($order_info['ds_tax_data']) && isset( $order_info['ds_tax_amount'] ) && !empty($order_info['ds_tax_amount']) && !isset( $order_info['ds_vat_amount'] ) ) {
                update_post_meta( $post_id, '_sc_tax_amount', $order_info['ds_tax_amount'] );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['ds_tax_data'] );
            }
            
            
            if ( $order_info['pay_method'] == 'stripe' ) {
                update_post_meta( $post_id, '_sc_intent_id', $order_info['intent_id'] );
                update_post_meta( $post_id, '_sc_stripe_customer_id', $order_info['customer'] );
                update_post_meta( $post_id, '_sc_stripe_mode', $order_info['stripe_mode'] );
            }
            
            add_filter(
                'sc_is_order_complete',
                array( $this, 'is_stripe_order_complete' ),
                10,
                2
            );
            $this->maybe_do_order_complete( $post_id, $order_info );
            remove_filter(
                'sc_is_order_complete',
                array( $this, 'is_stripe_order_complete' ),
                10,
                2
            );
        } else {
            $post_id = $this->do_order_save( $order_info );
            
            if ( $order_info['pay_method'] == 'stripe' ) {
                update_post_meta( $post_id, '_sc_intent_id', $order_info['intent_id'] );
                update_post_meta( $post_id, '_sc_stripe_customer_id', $order_info['customer'] );
                update_post_meta( $post_id, '_sc_stripe_mode', $order_info['stripe_mode'] );
            }
            
            update_post_meta( $post_id, '_sc_sub_total', $order_info['amount'] );
            $vat_applied = 0;
            
            if ( isset( $order_info['vat'] ) && !empty($order_info['vat']) ) {
                $vat_applied = $order_info['plan_price'] * $order_info['vat']['vat_rate'] / 100;
                $vat_applied = round( $vat_applied, 2 );
                update_post_meta( $post_id, '_sc_vat_amount', $vat_applied );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['vat'] );
            }
            
            
            if ( !empty($order_info['tax']) && $order_info['tax']['tax_type'] != 'inclusive_tax' && $vat_applied == 0 ) {
                $tax_applied = $order_info['plan_price'] * $order_info['tax']['tax_rate'] / 100;
                $tax_applied = round( $tax_applied, 2 );
                update_post_meta( $post_id, '_sc_tax_amount', $tax_applied );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['tax'] );
            }
            
            
            if ( isset( $order_info['us_vat_data'] ) && !empty($order_info['us_vat_data']) ) {
                update_post_meta( $post_id, '_sc_vat_amount', $order_info['us_vat_amount'] );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['us_vat_data'] );
            }
            
            
            if ( isset( $order_info['ds_vat_data'] ) && !empty($order_info['ds_vat_data']) ) {
                update_post_meta( $post_id, '_sc_vat_amount', $order_info['ds_vat_amount'] );
                update_post_meta( $post_id, '_sc_vat_data', $order_info['ds_vat_data'] );
            }
            
            
            if ( isset( $order_info['us_tax_data'] ) && !empty($order_info['us_tax_data']) && isset( $order_info['us_tax_amount'] ) && !empty($order_info['us_tax_amount']) && !isset( $order_info['us_vat_amount'] ) ) {
                update_post_meta( $post_id, '_sc_tax_amount', $order_info['us_tax_amount'] );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['us_tax_data'] );
            }
            
            
            if ( isset( $order_info['ds_tax_data'] ) && !empty($order_info['ds_tax_data']) && isset( $order_info['ds_tax_amount'] ) && !empty($order_info['ds_tax_amount']) && !isset( $order_info['ds_vat_amount'] ) ) {
                update_post_meta( $post_id, '_sc_tax_amount', $order_info['ds_tax_amount'] );
                update_post_meta( $post_id, '_sc_tax_data', $order_info['ds_tax_data'] );
            }
        
        }
        
        return $post_id;
    }
    
    public function is_stripe_order_complete( $is_complete, $post_id )
    {
        if ( get_post_meta( $post_id, '_sc_transaction_id', true ) ) {
            return 'paid';
        }
        return $is_complete;
    }
    
    public function do_order_save( $order_info, $status = 'pending-payment' )
    {
        //insert order
        $post_id = wp_insert_post( array(
            'post_title'  => time() . " " . $order_info['name'],
            'post_type'   => 'sc_order',
            'post_status' => 'publish',
        ), FALSE );
        //update stripe meta
        update_post_meta( $post_id, '_sc_firstname', $order_info['firstname'] );
        update_post_meta( $post_id, '_sc_lastname', $order_info['lastname'] );
        update_post_meta( $post_id, '_sc_email', $order_info['email'] );
        update_post_meta( $post_id, '_sc_phone', $order_info['phone'] );
        update_post_meta( $post_id, '_sc_country', $order_info['country'] );
        update_post_meta( $post_id, '_sc_address1', $order_info['address1'] );
        update_post_meta( $post_id, '_sc_address2', $order_info['address2'] );
        update_post_meta( $post_id, '_sc_city', $order_info['city'] );
        update_post_meta( $post_id, '_sc_state', $order_info['state'] );
        update_post_meta( $post_id, '_sc_zip', $order_info['zip'] );
        update_post_meta( $post_id, '_sc_vat_customer_type', $order_info['vat_customer_type'] );
        update_post_meta( $post_id, '_sc_vat_number', $order_info['vat_number'] );
        update_post_meta( $post_id, '_sc_transaction_id', $order_info['transaction_id'] );
        update_post_meta( $post_id, '_sc_product_id', $order_info['product_id'] );
        update_post_meta( $post_id, '_sc_product_name', $order_info['product_name'] );
        update_post_meta( $post_id, '_sc_amount', $order_info['amount'] );
        update_post_meta( $post_id, '_sc_item_name', $order_info['item_name'] );
        update_post_meta( $post_id, '_sc_plan_id', $order_info['plan_id'] );
        update_post_meta( $post_id, '_sc_option_id', $order_info['option_id'] );
        update_post_meta( $post_id, '_sc_ip_address', $order_info['ip_address'] );
        update_post_meta( $post_id, '_sc_user_account', $order_info['user_account'] );
        update_post_meta( $post_id, '_sc_accept_terms', $order_info['accept_terms'] );
        update_post_meta( $post_id, '_sc_consent', $order_info['consent'] );
        update_post_meta( $post_id, '_sc_on_sale', $order_info['on_sale'] );
        if ( $order_info['product_replaced'] ) {
            update_post_meta( $post_id, '_sc_product_replaced', $order_info['product_replaced'] );
        }
        $vat_applied = 0;
        
        if ( isset( $order_info['vat'] ) && !empty($order_info['vat']) ) {
            $vat_data = NCS_Cart_VAT::get_order_vat_data( $order_info );
            $planPrice = $order_info['plan_price'];
            if ( isset( $vat_data ) && !empty($vat_data) ) {
                
                if ( $_POST['vat-customer-type'] == 'consumer' || $_POST['vat-customer-type'] == 'business' && $_POST['country'] == $vat_data['vat_merchant_country'] || $_POST['vat-customer-type'] == 'business' && isset( $vat_data['vat_all_eu_businesses'] ) && !empty($vat_data['vat_all_eu_businesses']) ) {
                    $vat_applied = $planPrice * $vat_data['vat_rate'] / 100;
                    $vat_applied = round( $vat_applied, 2 );
                    update_post_meta( $post_id, '_sc_vat_amount', $vat_applied );
                    update_post_meta( $post_id, '_sc_vat_data', $tax_data );
                }
            
            }
        }
        
        
        if ( isset( $order_info['tax'] ) && !empty($order_info['tax']) && $vat_applied == 0 ) {
            $tax_data = NCS_Cart_Tax::get_order_tax_data( $order_info );
            $planPrice = $order_info['plan_price'];
            $tax_applied = 0;
            
            if ( !empty($tax_data) && $tax_data['tax_type'] != 'inclusive_tax' ) {
                $tax_applied = $planPrice * $tax_data['tax_rate'] / 100;
                $tax_applied = round( $tax_applied, 2 );
                update_post_meta( $post_id, '_sc_tax_amount', $tax_applied );
                update_post_meta( $post_id, '_sc_tax_data', $tax_data );
            }
        
        }
        
        
        if ( !empty($order_info['page_id']) ) {
            update_post_meta( $post_id, '_sc_page_id', $order_info['page_id'] );
            update_post_meta( $post_id, '_sc_page_url', $order_info['page_url'] );
        }
        
        if ( isset( $order_info['custom'] ) ) {
            update_post_meta( $post_id, '_sc_custom_prices', $order_info['custom'] );
        }
        if ( isset( $order_info['custom_fields'] ) ) {
            update_post_meta( $post_id, '_sc_custom_fields', $order_info['custom_fields'] );
        }
        if ( isset( $order_info['custom_fields_post_data'] ) ) {
            update_post_meta( $post_id, '_sc_custom_fields_post_data', $order_info['custom_fields_post_data'] );
        }
        if ( isset( $_POST['sc-auto-login'] ) ) {
            update_post_meta( $post_id, '_sc_auto_login', 1 );
        }
        
        if ( $order_info['amount'] > 0 || $order_info['plan_type'] == 'recurring' ) {
            update_post_meta( $post_id, '_sc_pay_method', $order_info['pay_method'] );
            update_post_meta( $post_id, '_sc_currency', $order_info['currency'] );
            update_post_meta( $post_id, '_sc_payment_status', $status );
            update_post_meta( $post_id, '_sc_status', $status );
            
            if ( $order_info['pay_method'] == 'cod' ) {
                $parent_id = $order_info['ID'];
                $order_info['ID'] = $post_id;
                sc_trigger_integrations( 'pending', $order_info );
                $order_info['ID'] = $parent_id;
            }
            
            $submitSuccess = wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => $status,
            ) );
            sc_log_entry( $post_id, __( ucwords( $status ) . ' order created.', 'ncs-cart' ) );
        } else {
            // Free item
            $status = 'completed';
            if ( !empty($order_info['coupon']) ) {
                $status = 'paid';
            }
            update_post_meta( $post_id, '_sc_payment_status', $status );
            update_post_meta( $post_id, '_sc_status', $status );
            $submitSuccess = wp_update_post( array(
                'ID'          => $post_id,
                'post_status' => $status,
            ) );
            sc_log_entry( $post_id, __( 'Order complete.', 'ncs-cart' ) );
        }
        
        //data updated
        // Add user ID to order if logged in.
        
        if ( $order_info['user_account'] && ($current_user = get_user_by( 'id', $order_info['user_account'] )) ) {
            $user_data = array();
            if ( !$current_user->user_firstname ) {
                $user_data['first_name'] = $order_info['firstname'];
            }
            if ( !$current_user->user_lastname ) {
                $user_data['last_name'] = $order_info['lastname'];
            }
            
            if ( !empty($user_data) ) {
                $user_data['ID'] = $order_info['user_account'];
                $user_id = wp_update_user( $user_data );
            }
        
        }
        
        $this->maybe_do_order_complete( $post_id, $order_info, $status );
        do_action(
            'studiocart_after_order_created',
            $post_id,
            $status,
            $order_info
        );
        return $post_id;
    }
    
    private function maybe_do_order_complete( $post_id, $order_info, $status = false )
    {
        $status = apply_filters( 'sc_is_order_complete', $status, $post_id );
        
        if ( $status ) {
            $order_info['ID'] = $post_id;
            sc_trigger_integrations( $status, $order_info );
        }
        
        return $post_id;
    }
    
    public function maybe_change_thank_you_page( $formAction, $order_id, $product_id )
    {
        // does this order have a bump?
        $ob = get_post_meta( $order_id, '_sc_order_bumps', true );
        if ( !$ob || !isset( $ob['main'] ) ) {
            return $formAction;
        }
        $ob_id = $ob['main']['id'];
        // get bump redirect settings
        
        if ( $ob_id && ($override = get_post_meta( $product_id, '_sc_ob_conf_override', true )) ) {
            $page = intval( get_post_meta( $product_id, '_sc_ob_page', true ) );
            
            if ( $override ) {
                
                if ( !$page ) {
                    // get bump product's thank you page
                    $scp = sc_setup_product( $ob_id );
                    $page = $scp->thanks_url;
                } else {
                    $page = get_permalink( $page );
                }
                
                
                if ( isset( $_POST['sc-orderbump']['main'] ) ) {
                    return add_query_arg( 'sc-order', $order_id, $page );
                } else {
                    return $page;
                }
            
            }
        
        }
        
        return $formAction;
    }
    
    public function create_subscription()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        global  $sc_stripe, $sc_currency ;
        require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        $email = sanitize_email( $_POST['email'] );
        $customer_id = sanitize_text_field( $_POST['customerId'] );
        $paymethod_id = sanitize_text_field( $_POST['paymentMethodId'] );
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name = sanitize_text_field( $_POST['last_name'] );
        // create base order
        do_action( 'sc_before_create_main_order' );
        $order = new ScrtOrder();
        $order->load_from_post();
        $order->gateway_mode = $sc_stripe['mode'];
        $apikey = $sc_stripe['sk'];
        $stripe = new \Stripe\StripeClient( [
            "api_key"        => $apikey,
            "stripe_version" => "2020-08-27",
        ] );
        try {
            $payment_method = $stripe->paymentMethods->retrieve( $paymethod_id );
            $payment_method->attach( [
                'customer' => $customer_id,
            ] );
        } catch ( Exception $e ) {
            echo  json_encode( $e->getJsonBody() ) ;
            exit;
        }
        $args = [
            'name'             => $first_name . ' ' . $last_name,
            'email'            => $email,
            'invoice_settings' => [
            'default_payment_method' => $paymethod_id,
        ],
        ];
        if ( isset( $_POST['phone'] ) ) {
            $args['phone'] = sanitize_text_field( $_POST['phone'] );
        }
        // Set the default payment method on the customer
        $stripe->customers->update( $customer_id, $args );
        // retry Invoice With New Payment Method
        if ( isset( $_POST['invoiceId'] ) ) {
            try {
                $invoiceId = sanitize_text_field( $_POST['invoiceId'] );
                $invoice = $stripe->invoices->retrieve( $invoiceId, [
                    'expand' => [ 'payment_intent' ],
                ] );
                echo  json_encode( $invoice ) ;
                exit;
            } catch ( Exception $e ) {
                unset( $_POST['invoiceId'] );
            }
        }
        // save subscription
        $sub = ScrtSubscription::from_order( $order );
        $sub->store();
        $order->subscription_id = $sub->id;
        $order->store();
        $subscription = $this->create_stripe_subscription( $order, $sub );
        
        if ( !$subscription ) {
            $subscription = array(
                'error' => 'Something went wrong, please try again later.',
            );
        } else {
            $sub->sub_status = $subscription->status;
            $sub->status = $subscription->status;
            $sub->subscription_id = $subscription->id;
            $sub->sub_next_bill_date = $subscription->current_period_end;
            $sub->customer_id = $subscription->customer;
            $sub->cancel_at = $subscription->cancel_at;
            $sub->sub_end_date = date( "Y-m-d", $subscription->cancel_at );
            $order->customer_id = $subscription->customer;
            
            if ( $sub->status == 'trialing' && !$sub->free_trial_days ) {
                $sub->status = 'active';
                $sub->sub_status = 'active';
            }
            
            // add stripe item IDs to bump sub info
            
            if ( $sub->order_bump_subs ) {
                $items = $subscription->items->data;
                foreach ( $sub->order_bump_subs as $k => $v ) {
                    foreach ( $items as $item ) {
                        
                        if ( $order->plan->type == 'recurring' && $order->plan->stripe_id == $item->price->id ) {
                            // add item for main product sub if present
                            $sub->main_product_sub['item_id'] = $item->id;
                        } else {
                            if ( $v['plan']->stripe_id == $item->price->id ) {
                                $sub->order_bump_subs[$k]['item_id'] = $item->id;
                            }
                        }
                    
                    }
                }
            }
            
            $sub->store();
            
            if ( $subscription->latest_invoice->charge ) {
                $order->transaction_id = $subscription->latest_invoice->charge;
            } else {
                $order->transaction_id = $subscription->latest_invoice->payment_intent->id;
            }
            
            // run integrations
            
            if ( isset( $subscription->status ) && $subscription->status != 'incomplete' ) {
                $order->status = 'paid';
                $order->payment_status = 'paid';
            }
            
            $order->store();
            // setup redirect
            $subscription->sc_order_id = $order->id;
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $scp = sc_setup_product( $sc_product_id );
            
            if ( !$scp->upsell && $scp->confirmation != 'redirect' ) {
                $subscription->formAction = add_query_arg( 'sc-order', $order->id, $scp->thanks_url );
                $subscription->formAction = apply_filters(
                    'studiocart_post_purchase_url',
                    $subscription->formAction,
                    $order->id,
                    $sc_product_id
                );
            }
            
            if ( !$subscription->formAction ) {
                unset( $subscription->formAction );
            }
        }
        
        echo  json_encode( $subscription ) ;
        exit;
    }
    
    public function create_stripe_subscription( $order, $sub )
    {
        global  $sc_stripe, $sc_currency ;
        $apikey = $sc_stripe['sk'];
        $stripe = new \Stripe\StripeClient( [
            "api_key"        => $apikey,
            "stripe_version" => "2020-08-27",
        ] );
        // Create the subscription
        $args = [
            'customer'        => $order->customer_id,
            'items'           => array(),
            'trial_from_plan' => false,
            'expand'          => [ 'latest_invoice.payment_intent' ],
            'metadata'        => [
            'sc_subscription_id' => $sub->id,
            'origin'             => get_site_url(),
        ],
        ];
        $addon = 0;
        // add trial period
        if ( $sub->free_trial_days ) {
            $args['trial_period_days'] = $sub->free_trial_days;
        }
        $tax_rates = [];
        if ( !empty($sub->tax_rate) && !empty($sub->stripe_tax_id) ) {
            $tax_rates = [
                'tax_rates' => [ $sub->stripe_tax_id ],
            ];
        }
        // calculate addons and discount
        if ( $sub->cancel_at ) {
            $args['cancel_at'] = $sub->cancel_at;
        }
        // Add sign up fee
        if ( $sub->sign_up_fee ) {
            $addon += $order->plan->fee;
        }
        // is main order a subscription?
        
        if ( $order->plan->type == 'recurring' ) {
            $item_args = array_merge( [
                'price' => $order->plan->stripe_id,
            ], $tax_rates );
            $args['items'][] = $item_args;
        } else {
            $addon += $order->plan->price;
        }
        
        // process order bumps
        if ( is_array( $order->order_bumps ) ) {
            foreach ( $order->order_bumps as $bump ) {
                // do order bumps have a subscription?
                
                if ( isset( $bump['plan'] ) ) {
                    $item_args = array_merge( [
                        'price' => $bump['plan']->stripe_id,
                    ], $tax_rates );
                    $args['items'][] = $item_args;
                } else {
                    $addon += $bump['amount'];
                }
            
            }
        }
        $discount = false;
        try {
            $subscription = $stripe->subscriptions->create( $args );
        } catch ( Exception $e ) {
            echo  json_encode( [
                'error' => $e->getMessage(),
            ] ) ;
            exit;
        }
        if ( isset( $subscription->id ) ) {
            return $subscription;
        }
        return false;
    }
    
    public function update_subscription_db( $post_id, $subscription )
    {
        update_post_meta( $post_id, '_sc_sub_status', $subscription->status );
        update_post_meta( $post_id, '_sc_status', $subscription->status );
        update_post_meta( $post_id, '_sc_subscription_id', $subscription->id );
        update_post_meta( $post_id, '_sc_stripe_subscription_id', $subscription->id );
        update_post_meta( $post_id, '_sc_stripe_plan_id', $subscription->plan->id );
        update_post_meta( $post_id, '_sc_stripe_customer_id', $subscription->customer );
        update_post_meta( $post_id, '_sc_sub_customer_id', $subscription->customer );
        update_post_meta( $post_id, '_sc_sub_interval', $subscription->plan->interval );
        update_post_meta( $post_id, '_sc_sub_next_bill_date', $subscription->current_period_end );
        if ( isset( $subscription->plan->trial_period_days ) ) {
            update_post_meta( $post_id, '_sc_free_trial_days', $subscription->plan->trial_period_days );
        }
        if ( isset( $subscription->sign_up_fee ) ) {
            update_post_meta( $post_id, '_sc_sign_up_fee', $subscription->sign_up_fee );
        }
        wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => $subscription->status,
        ) );
        sc_log_entry( $post_id, __( 'Subscription status updated to ' . $subscription->status, 'ncs-cart' ) );
        sc_trigger_integrations( $subscription->status, $post_id );
        // run integrations
        if ( isset( $subscription->status ) && $subscription->status != 'incomplete' ) {
            
            if ( !isset( $_POST['sc-order'] ) ) {
                // only check post data when this is not an upsell
                $order_info = $this->order_info_from_post();
                $order_info['ID'] = $post_id;
                sc_trigger_integrations( $status = 'paid', $order_info );
            }
        
        }
    }
    
    public function sc_check_vat_applicable()
    {
        $vat_data = NCS_Cart_VAT::get_order_vat_data( $_POST );
        $country = $_POST['country'];
        $customer_type = $_POST['customer-type'];
        $vat_applied = false;
        $vat_per = 0;
        
        if ( isset( $vat_data ) && !empty($vat_data) ) {
            $vat_applied = true;
            if ( $customer_type == 'consumer' || $customer_type == 'business' && $country == $vat_data['vat_merchant_country'] || $customer_type == 'business' && isset( $vat_data['vat_all_eu_businesses'] ) && !empty($vat_data['vat_all_eu_businesses']) ) {
                $vat_per = $vat_data['vat_rate'];
            }
        }
        
        echo  json_encode( array(
            'vat_rate'    => $vat_per,
            'vat_applied' => $vat_applied,
        ) ) ;
        exit;
    }
    
    public function custom_bump_summary_text( $str, $post_id, $ob_id )
    {
        if ( $custom = get_post_meta( $post_id, '_sc_ob_custom_description', true ) ) {
            return $custom;
        }
        return $str;
    }
    
    public function sc_set_form_views()
    {
        $postID = intval( $_POST['page_id'] );
        $prodID = intval( $_POST['prod_id'] );
        $slug = sanitize_text_field( $_POST['url'] );
        $count_key = 'sc_form_view_counts';
        $views = get_post_meta( $prodID, $count_key, true );
        // set views to array if false
        if ( $views == '' ) {
            $views = [];
        }
        // set counts for all view types to 0 if not present
        if ( !isset( $views['total'] ) ) {
            $views['total'] = 0;
        }
        if ( !isset( $views['ids'][$postID] ) ) {
            $views['ids'][$postID] = 0;
        }
        if ( !isset( $views['urls'][$slug] ) ) {
            $views['urls'][$slug] = 0;
        }
        // update view counts
        $views['total']++;
        $views['ids'][$postID]++;
        $views['urls'][$slug]++;
        update_post_meta( $prodID, $count_key, $views );
    }
    
    public function sc_my_account_page_shortcode()
    {
        
        if ( is_user_logged_in() ) {
            ob_start();
            
            if ( isset( $_GET['sc-plan'] ) ) {
                include plugin_dir_path( __FILE__ ) . 'templates/subscription_detail.php';
                add_action(
                    'sc_card_details_fields',
                    'sc_address_fields',
                    1,
                    2
                );
            } else {
                include plugin_dir_path( __FILE__ ) . 'templates/my_account.php';
            }
            
            $output_string = ob_get_contents();
            ob_end_clean();
            return $output_string;
        } else {
            $args = array(
                'redirect'       => admin_url(),
                'form_id'        => 'loginform-custom',
                'label_username' => __( 'Username' ),
                'label_password' => __( 'Password' ),
                'label_remember' => __( 'Remember Me' ),
                'label_log_in'   => __( 'Log In' ),
                'remember'       => true,
            );
            return wp_login_form( $args );
        }
    
    }
    
    public function create_payment_change_request_intent()
    {
        global  $sc_stripe, $sc_currency, $scp ;
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        \Stripe\Stripe::setApiVersion( "2020-03-02" );
        
        if ( $_POST['pay-method'] == get_post_meta( $_POST['sc_orderid'], '_sc_pay_method', true ) ) {
            $res = [
                'error' => 'You have selected your current payment method. To change your subscription  payment method, please select another payment option.',
            ];
            echo  json_encode( $res ) ;
            exit;
        }
        
        //$customer = \Stripe\Customer::create();
        $subscription_id = $_POST['sc_orderid'];
        $email = get_post_meta( $subscription_id, '_sc_email', true );
        $name = get_post_meta( $subscription_id, '_sc_firstname', true );
        $last_name = get_post_meta( $subscription_id, '_sc_lastname', true );
        
        if ( get_post_meta( $subscription_id, '_sc_address1', true ) ) {
            $customer = \Stripe\Customer::create( array(
                'email'       => $email,
                'name'        => $name,
                'description' => $name . ' ' . $last_name,
                'address'     => [
                'line1'       => get_post_meta( $subscription_id, '_sc_address1', true ),
                'postal_code' => get_post_meta( $subscription_id, '_sc_zip', true ),
                'city'        => get_post_meta( $subscription_id, '_sc_city', true ),
                'state'       => get_post_meta( $subscription_id, '_sc_state', true ),
                'country'     => get_post_meta( $subscription_id, '_sc_address1', true ),
            ],
            ) );
        } else {
            $customer = \Stripe\Customer::create( array(
                'email'       => $email,
                'name'        => $name,
                'description' => $name . ' ' . $last_name,
            ) );
        }
        
        $sc_product_id = get_post_meta( $subscription_id, '_sc_product_id', true );
        $scp = sc_setup_product( $sc_product_id );
        $amount = get_post_meta( $subscription_id, '_sc_sub_amount', true );
        // Setup payment intent and return result
        $amount_for_stripe = get_sc_price( $amount, $sc_currency );
        $client_secret = '';
        $intent_id = '';
        $descriptor = get_option( '_sc_stripe_descriptor', false );
        if ( !$descriptor ) {
            $descriptor = get_bloginfo( 'name' ) ?? sc_get_public_product_name( $sc_product_id );
        }
        $intent = \Stripe\PaymentIntent::create( [
            'amount'               => $amount_for_stripe,
            'currency'             => $sc_currency,
            'statement_descriptor' => mb_strimwidth( $descriptor, 0, 22 ),
            'customer'             => $customer->id,
            'confirmation_method'  => 'automatic',
            'setup_future_usage'   => 'off_session',
            'metadata'             => [
            'sc_product_id' => $sc_product_id,
            'origin'        => get_site_url(),
        ],
        ] );
        $client_secret = $intent->client_secret;
        $intent_id = $intent->id;
        
        if ( $customer ) {
            echo  json_encode( array(
                'clientSecret' => $client_secret,
                'intent_id'    => $intent_id,
                'customer_id'  => $customer->id,
                'amount'       => $amount,
            ) ) ;
        } else {
            json_encode( array(
                'error' => 'There was an error, please try again.',
            ) );
        }
        
        exit;
    }
    
    public function payment_change_subscription()
    {
        global  $sc_stripe, $sc_currency ;
        require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
        $email = sanitize_email( $_POST['email'] );
        $customer_id = sanitize_text_field( $_POST['customerId'] );
        $paymethod_id = sanitize_text_field( $_POST['paymentMethodId'] );
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name = sanitize_text_field( $_POST['last_name'] );
        $post_id = $_POST['sc_orderid'];
        $amount = get_post_meta( $post_id, '_sc_sub_amount', true );
        $plan_id = get_post_meta( $post_id, '_sc_plan_id', true );
        // Setup payment intent and return result
        $amount_for_stripe = get_sc_price( $amount, $sc_currency );
        $apikey = $sc_stripe['sk'];
        $stripe = new \Stripe\StripeClient( [
            "api_key"        => $apikey,
            "stripe_version" => "2020-08-27",
        ] );
        try {
            $payment_method = $stripe->paymentMethods->retrieve( $paymethod_id );
            $payment_method->attach( [
                'customer' => $customer_id,
            ] );
        } catch ( Exception $e ) {
            echo  json_encode( $e->getJsonBody() ) ;
            exit;
        }
        // Set the default payment method on the customer
        $stripe->customers->update( $customer_id, [
            'name'             => $first_name . ' ' . $last_name,
            'email'            => $email,
            'invoice_settings' => [
            'default_payment_method' => $paymethod_id,
        ],
        ] );
        // retry Invoice With New Payment Method
        
        if ( isset( $_POST['invoiceId'] ) ) {
            $invoiceId = sanitize_text_field( $_POST['invoiceId'] );
            $invoice = $stripe->invoices->retrieve( $invoiceId, [
                'expand' => [ 'payment_intent' ],
            ] );
            echo  json_encode( $invoice ) ;
            exit;
        }
        
        // Create the subscription
        /*$unsub = sc_payments_unsubscribe($post_id);
          $unsubscribe = json_decode($unsub); 
          if($unsubscribe->details == ''){
             print_r($unsubscribe); exit();
              
          } */
        $_POST['sc_product_id'] = get_post_meta( $post_id, '_sc_product_id', true );
        $_POST['sc_product_option'] = get_post_meta( $post_id, '_sc_product_option', true );
        $sub_info = $this->calculate_cart_total( array() );
        $args = [
            'customer'        => $customer_id,
            'items'           => [ [
            'price' => $sub_info['priceId'],
        ] ],
            'trial_from_plan' => true,
            'expand'          => [ 'latest_invoice.payment_intent' ],
            'metadata'        => [
            'sc_subscription_id' => $post_id,
            'origin'             => get_site_url(),
        ],
        ];
        if ( !empty($sub_info['cancel_at']) ) {
            $args['cancel_at'] = $sub_info['cancel_at'];
        }
        $discount = false;
        //print_r($args); exit();
        try {
            $subscription = $stripe->subscriptions->create( $args );
        } catch ( Exception $e ) {
            echo  $e->getMessage() ;
            exit;
        }
        //save data to db
        
        if ( isset( $subscription->id ) ) {
            if ( isset( $sub_info['sign_up_fee'] ) ) {
                $subscription->sign_up_fee = $sub_info['sign_up_fee'];
            }
            $this->update_subscription_db( $post_id, $subscription );
            $subscription->sc_order_id = $post_id;
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $scp = sc_setup_product( $sc_product_id );
            
            if ( !$scp->upsell && $scp->confirmation != 'redirect' ) {
                update_post_meta( $post_id, '_sc_pay_method', 'stripe' );
                $subscription->formAction = add_query_arg( array(
                    'sc-order'      => $post_id,
                    'method-change' => 1,
                ), $scp->thanks_url );
                //$subscription->formAction = add_query_arg('sc-order', $post_id, $scp->thanks_url);
            }
            
            // print_r($subscription); exit();
            echo  json_encode( $subscription ) ;
        }
        
        exit;
    }
    
    public function get_match_tax_rate()
    {
        $params = $_POST;
        if ( !isset( $params['nonce'], $params['country'] ) ) {
            wp_send_json( array(
                'message' => 'Missing Fields',
            ), '400' );
        }
        if ( !wp_verify_nonce( wp_unslash( $params['nonce'] ), 'tax-ajax-nonce' ) ) {
            wp_send_json( array(
                'message' => 'Invalid Request',
            ), '401' );
        }
        $is_vat = true;
        $apply_vat = true;
        $is_valid = true;
        
        if ( !empty($params['vat_number']) ) {
            $client = new SoapClient( "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl" );
            try {
                $vat_data = $client->checkVat( array(
                    'countryCode' => $params['country'],
                    'vatNumber'   => $params['vat_number'],
                ) );
                $is_valid = $vat_data->valid;
            } catch ( Exception $e ) {
                $is_valid = false;
            }
            if ( !get_option( '_sc_vat_all_eu_businesses', false ) && $params['country'] != get_option( '_sc_vat_merchant_state', false ) ) {
                if ( $is_valid ) {
                    $apply_vat = false;
                }
            }
        }
        
        if ( $apply_vat ) {
            $tax_rate = NCS_Cart_Tax::get_country_vat_rates( $params['country'] );
        }
        
        if ( empty($tax_rate) ) {
            $is_vat = false;
            $tax_rate = NCS_Cart_Tax::get_matched_tax_rates(
                $params['country'],
                $params['state'] ?? '',
                $params['zip'] ?? '',
                $params['city'] ?? ''
            );
        }
        
        $vat_enable_countries_list = sc_vat_countries_list();
        if ( isset( $vat_enable_countries_list[$params['country']] ) ) {
            $is_vat = true;
        }
        $response = array(
            'rates'  => $tax_rate,
            'is_vat' => $is_vat,
        );
        wp_send_json( $response );
    }

}