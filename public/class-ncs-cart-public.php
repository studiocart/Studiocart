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
     * The version of this plugin.
     *
     * @since    2.4
     * @access   private
     * @var      string    $version    URL to user selected my account/login page.
     */
    private  $my_account_url ;
    /**
     * The prefix of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $prefix    The current version of this plugin.
     */
    public  $prefix ;
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
        add_action( 'init', array( $this, 'set_my_account_url' ), 1 );
        add_action( 'login_form_bottom', array( $this, 'add_lost_password_link' ) );
        add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
        add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );
        add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
        add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );
        add_filter(
            'authenticate',
            array( $this, 'maybe_redirect_at_authenticate' ),
            101,
            3
        );
        add_filter(
            'sc_send_new_user_email',
            array( $this, 'maybe_disable_welcome_email' ),
            101,
            3
        );
        add_shortcode( 'studiocart_account', array( $this, 'my_account_page_shortcode' ) );
        add_shortcode( 'studiocart_account_link', array( $this, 'my_account_page_link_shortcode' ) );
        add_shortcode( 'studiocart_order_detail', array( $this, 'order_detail_shortcode' ) );
        add_shortcode( 'studiocart_subscription_detail', array( $this, 'subscription_detail_shortcode' ) );
        add_shortcode( 'studiocart-form', array( $this, 'sc_product_shortcode' ) );
        add_shortcode( 'studiocart-receipt', array( $this, 'sc_receipt_shortcode' ) );
        add_shortcode( 'studiocart-store', array( $this, 'sc_store_shortcode' ) );
        add_shortcode( 'sc_customer_bought_product', array( $this, 'customer_bought_product' ) );
        add_shortcode( 'sc_customer_has_subscription', array( $this, 'customer_has_subscription' ) );
        add_action( 'sc_js_purchase_tracking', array( $this, 'ga_purchase_tracking' ) );
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
            'sc_subscription_paused',
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
        add_action(
            'sc_run_after_integrations',
            [ $this, 'do_after_integration_functions' ],
            10,
            3
        );
        add_action( 'wp_ajax_get_match_tax_rate', [ $this, 'get_match_tax_rate' ] );
        add_action( 'wp_ajax_nopriv_get_match_tax_rate', [ $this, 'get_match_tax_rate' ] );
        add_action( 'wp_ajax_sc_update_cart_amount', [ $this, 'sc_update_cart_amount' ] );
        add_action( 'wp_ajax_nopriv_sc_update_cart_amount', [ $this, 'sc_update_cart_amount' ] );
        add_action( 'sc_before_create_main_order', array( $this, 'check_product_purchase_limit' ) );
        add_action( 'sc_before_create_main_order', array( $this, 'validate_order_form_submission' ) );
    }
    
    /**
     * Do order integrations
     *
     * @since    2.1.29
     */
    public function do_order_integration_functions( $status, $order_data, $order_type = 'main' )
    {
        sc_do_integrations( $order_data['product_id'], $order_data, $status );
        if ( $status == 'pending' && $order_data['pay_method'] == 'cod' ) {
            sc_do_notifications( $order_data );
        }
    }
    
    public function do_after_integration_functions( $status, $order_data, $event_type = 'order' )
    {
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            $webhook_trigger = ( $status == 'paid' ? 'purchased' : $status );
            sc_do_webhooks( $order_data, $order_data['product_id'], $webhook_trigger );
            if ( $status == 'paid' ) {
                sc_maybe_update_coupon_limit( $order_data['coupon'], $order_data['product_id'] );
            }
        }
        
        if ( $status != 'pending' || $order_data['pay_method'] == 'cod' ) {
            if ( !($status == 'paid' && get_post_meta( $order_data['product_id'], '_sc_disable_purchase_email', true ) || $status == 'pending' && get_post_meta( $order_data['product_id'], '_sc_disable_pending_email', true )) ) {
                studiocart_notification_send( $status, $order_data );
            }
        }
    }
    
    /**
     * Do order complete functions.
     *
     * @since    2.1.0
     */
    public function do_order_complete_functions( $status, $order_data, $order_type = 'main' )
    {
        sc_do_integrations( $order_data['product_id'], $order_data );
        sc_maybe_update_stock( $order_data['product_id'] );
        sc_do_notifications( $order_data );
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
        if ( isset( $_GET['sc-plan'] ) ) {
            wp_register_script(
                'sc-stripe',
                plugin_dir_url( __FILE__ ) . 'js/ncs-stripe.js',
                array( 'jquery' ),
                $this->version,
                true
            );
        }
        wp_localize_script( $this->plugin_name, 'sc_translate_frontend', sc_translate_js( 'ncs-cart-public.js' ) );
        wp_localize_script( $this->plugin_name, 'sc_country_select_states', array(
            'sc_states'  => sc_states_list(),
            'sc_country' => get_option( '_sc_country' ),
        ) );
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
            global  $post ;
            $stripe = $sc_stripe['pk'];
            wp_localize_script( $this->plugin_name, 'stripe_key', array( $stripe ) );
            wp_register_script( 'stripe-api-v3', 'https://js.stripe.com/v3/' );
            $post_types = (array) apply_filters( 'sc_product_post_type', 'sc_product' );
            if ( in_array( get_post_type(), $post_types ) || isset( $_POST['sc_purchase_amount'] ) || isset( $_GET['sc-plan'] ) || isset( $_GET['sc-order'] ) && isset( $_GET['step'] ) ) {
                wp_enqueue_script( 'stripe-api-v3' );
            }
        }
        
        wp_enqueue_script( $this->plugin_name );
        $studiocart = array(
            'ajax'    => admin_url( 'admin-ajax.php' ),
            'page_id' => get_the_ID(),
        );
        wp_enqueue_script( 'sc-stripe' );
        
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
            add_action( 'wp_footer', array( $this, 'js_order_tracking' ) );
        }
    
    }
    
    public function js_order_tracking()
    {
        global  $scp ;
        $oto = intval( $_GET['sc-oto'] ?? 0 );
        $oto2 = intval( $_GET['sc-oto-2'] ?? 0 );
        $step = intval( $_GET['step'] ?? 1 );
        $order = false;
        // main order
        
        if ( isset( $_POST['sc_order_id'] ) || isset( $_GET['sc-order'] ) && !isset( $_GET['sc-oto'] ) ) {
            $order_id = intval( $_POST['sc_order_id'] ?? $_GET['sc-order'] );
            $order = new ScrtOrder( $order_id );
            if ( isset( $scp->tracking_main ) ) {
                echo  wp_specialchars_decode( sc_personalize( $scp->tracking_main, $order->get_data() ), 'ENT_QUOTES' ) ;
            }
            // downsell
        } else {
            
            if ( $oto2 ) {
                $order = new ScrtOrder( $oto2 );
            } else {
                
                if ( isset( $_GET['sc-oto'] ) && !$oto && $step > 1 ) {
                    $downsell = $order->get_downsell( $step );
                    if ( $downsell ) {
                        $order = $downsell;
                    }
                    // upsell
                } else {
                    if ( $oto ) {
                        $order = new ScrtOrder( $oto );
                    }
                }
            
            }
        
        }
        
        if ( $order && $order->id ) {
            do_action( 'sc_js_purchase_tracking', $order );
        }
        ?>
        <script>
        jQuery('document').ready(function($){   
            if ( typeof fbq !== "undefined") {
                
                <?php 
        
        if ( $order && $order->id ) {
            ?>
                if('undefined' !== typeof studiocart.fb_purchase_event && 'enabled' == studiocart.fb_purchase_event) {
                    fbq('track', 'Purchase', {
                        currency: '<?php 
            echo  $order->currency ;
            ?>', 
                        value: '<?php 
            echo  $order->amount ;
            ?>'
                    });
                }
                <?php 
        }
        
        ?>

                if( 'undefined' !== typeof studiocart.fb_add_payment_info && 'undefined' !== typeof studiocart.content_id && 
                'enabled' == studiocart.fb_add_payment_info ) {                        
                    var tracked = false;
                    if (!tracked) {
                        $('#sc-payment-form input').focus(function(){
                            fbq('track', 'AddPaymentInfo', {
                                content_ids: [studiocart.content_id],
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
    
    public function ga_purchase_tracking( $order )
    {
        
        if ( get_option( '_sc_ga_purchase' ) ) {
            $order = $order->get_data();
            $order = (object) $order;
            $ga_type = get_option( '_sc_ga_type' );
            ?>
            <script>
            <?php 
            
            if ( !$ga_type ) {
                ?>
                if ( typeof ga !== "undefined") {
                    ga( 'send', 'event', 'ecommerce', 'purchase', '<?php 
                echo  get_the_title( $order->product_id ) ;
                ?>' );
                    ga( 'require', 'ecommerce' );
                    
                    ga( 'ecommerce:addTransaction', {
                      'id': '<?php 
                echo  $order->id ;
                ?>', // Transaction ID. Required.
                      'affiliation': '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',              // Affiliation or store name.
                      'revenue': '<?php 
                echo  (double) $order->amount ;
                ?>',                      // Grand Total.
                      'shipping': '0',                       // Shipping.
                      'tax': '<?php 
                echo  (double) $order->tax_amount ;
                ?>'                             // Tax.
                    } );

                    ga( 'ecommerce:addItem', {
                      'id': '<?php 
                echo  $order->id ;
                ?>',                // Transaction ID. Required.
                      'name': '<?php 
                echo  get_the_title( $order->product_id ) ;
                ?>',                                  // Product name. Required.
                      'sku': '<?php 
                echo  $order->product_id ;
                ?>',          // SKU/code.
                      'category': '<?php 
                echo  $order->item_name ;
                ?>',  // Category or variation.
                      'price': '<?php 
                echo  $order->main_offer_amt ;
                ?>', // Unit price.
                      'quantity': '1' // Quantity.
                    } );

                    <?php 
                
                if ( isset( $order->custom_prices ) ) {
                    ?> 
                        <?php 
                    foreach ( $order->custom_prices as $id => $price ) {
                        ?>
                        ga( 'ecommerce:addItem', {
                          'id': '<?php 
                        echo  $order->id ;
                        ?>',                // Transaction ID. Required.
                          'name': '<?php 
                        echo  $price['label'] ;
                        ?>',                                  // Product name. Required.
                          'sku': '<?php 
                        echo  $id ;
                        ?>',          // SKU/code.
                          'category': '',                                 // Category or variation.
                          'price': '<?php 
                        echo  $price['price'] ;
                        ?>', // Unit price.
                          'quantity': '<?php 
                        echo  $price['qty'] ;
                        ?>' // Quantity.
                        } );
                        <?php 
                    }
                    ?> 
                    <?php 
                }
                
                ?>

                    <?php 
                
                if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
                    ?> 
                        <?php 
                    foreach ( $order->order_bumps as $k => $order_bump ) {
                        ?>
                        <?php 
                        $category = $order_bump['plan']->name ?? __( 'Order Bump', 'ncs-cart' );
                        ?>
                        ga( 'ecommerce:addItem', {
                          'id': '<?php 
                        echo  $order->id ;
                        ?>',                // Transaction ID. Required.
                          'name': '<?php 
                        echo  $order_bump['name'] ;
                        ?>',                                  // Product name. Required.
                          'sku': '<?php 
                        echo  'bump_' . $k . '_' . $order_bump['id'] ;
                        ?>',          // SKU/code.
                          'category': '<?php 
                        echo  $category ;
                        ?>',                                 // Category or variation.
                          'price': '<?php 
                        echo  $order_bump['amount'] ;
                        ?>', // Unit price.
                          'quantity': '1' // Quantity.
                        } );
                        <?php 
                    }
                    ?> 
                    <?php 
                }
                
                ?>
                }
            <?php 
            } elseif ( in_array( $ga_type, array( 'universal', 'ga4' ) ) ) {
                ?> 
                <?php 
                //universal
                $id_label = 'id';
                $name_label = 'name';
                $variant_label = 'variant';
                // ga4
                
                if ( $ga_type == 'ga4' ) {
                    $id_label = 'item_' . $id_label;
                    $name_label = 'item_' . $name_label;
                    $variant_label = 'item_' . $variant_label;
                }
                
                ?>
                if ( typeof gtag !== "undefined") {
                    gtag("event", "purchase", {
                      "transaction_id": '<?php 
                echo  $order->id ;
                ?>',                          // Transaction ID. Required.
                      "affiliation": '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',                   // Affiliation or store name.
                      "value": '<?php 
                echo  (double) $order->amount ;
                ?>',                       // Grand Total.
                      "currency": '<?php 
                echo  $order->currency ;
                ?>',
                      "shipping": '0',                                                        // Shipping.
                      "tax": '<?php 
                echo  (double) $order->tax_amount ;
                ?>',                     // Tax.
                      "items": [
                        {
                          "<?php 
                echo  $id_label ;
                ?>": '<?php 
                echo  $order->product_id ;
                ?>',                     // Transaction ID. Required.
                          "<?php 
                echo  $name_label ;
                ?>": '<?php 
                echo  get_the_title( $order->product_id ) ;
                ?>',    // Product name. Required.
                          "affiliation": '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',               // Affiliation or store name
                          "currency": '<?php 
                echo  $order->currency ;
                ?>',
                          "<?php 
                echo  $variant_label ;
                ?>": '<?php 
                echo  $order->item_name ;
                ?>',// Variation.
                          "price": '<?php 
                echo  $order->main_offer_amt ;
                ?>',                   // Unit price.
                          "quantity": 1                                                       // Quantity.
                        },
                        <?php 
                
                if ( isset( $order->custom_prices ) ) {
                    ?> 
                            <?php 
                    foreach ( $order->custom_prices as $id => $price ) {
                        ?>
                            {
                              "<?php 
                        echo  $id_label ;
                        ?>": '<?php 
                        echo  $id ;
                        ?>',                 // Transaction ID. Required.
                              "<?php 
                        echo  $name_label ;
                        ?>": '<?php 
                        echo  $price['label'] ;
                        ?>',   // Product name. Required.
                              "affiliation": '<?php 
                        echo  get_bloginfo( 'name' ) ;
                        ?>',             // Affiliation or store name
                              "currency": '<?php 
                        echo  $order->currency ;
                        ?>',
                              "<?php 
                        echo  $variant_label ;
                        ?>": '<?php 
                        _e( 'Custom add-on', 'ncs-cart' );
                        ?>',
                              "price": '<?php 
                        echo  $price['price'] ;
                        ?>', // Unit price.
                              "quantity": <?php 
                        echo  $price['qty'] ;
                        ?>, // Quantity.
                            },
                            <?php 
                    }
                    ?> 
                        <?php 
                }
                
                ?>

                        <?php 
                
                if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
                    ?> 
                            <?php 
                    foreach ( $order->order_bumps as $k => $order_bump ) {
                        ?>
                            <?php 
                        $category = $order_bump['plan']->name ?? __( 'Order Bump', 'ncs-cart' );
                        ?>
                            {
                              "<?php 
                        echo  $id_label ;
                        ?>": '<?php 
                        echo  $order_bump['id'] ;
                        ?>',   // Transaction ID. Required.
                              "<?php 
                        echo  $name_label ;
                        ?>": '<?php 
                        echo  $order_bump['name'] ;
                        ?>',    // Product name. Required.
                              "affiliation": '<?php 
                        echo  get_bloginfo( 'name' ) ;
                        ?>',             // Affiliation or store name
                              "currency": '<?php 
                        echo  $order->currency ;
                        ?>',
                              "<?php 
                        echo  $variant_label ;
                        ?>": '<?php 
                        echo  $category ;
                        ?>',
                              "price": '<?php 
                        echo  $order_bump['amount'] ;
                        ?>', // Unit price.
                              "quantity": 1 // Quantity.
                            },
                            <?php 
                    }
                    ?> 
                        <?php 
                }
                
                ?>
                        ]
                    });
                }
            <?php 
            } elseif ( $ga_type == 'datalayer_ga4' ) {
                ?> 
                if ( typeof dataLayer !== "undefined") {
                    dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
                    dataLayer.push({
                        event: "purchase",
                        ecommerce: {
                            "transaction_id": '<?php 
                echo  $order->id ;
                ?>',                          // Transaction ID. Required.
                            "affiliation": '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',                   // Affiliation or store name.
                            "value": '<?php 
                echo  (double) $order->amount ;
                ?>',                       // Grand Total.
                            "currency": '<?php 
                echo  $order->currency ;
                ?>',
                            "shipping": '0',                                                        // Shipping.
                            "tax": '<?php 
                echo  (double) $order->tax_amount ;
                ?>',                     // Tax.
                            "items": [
                            {
                              "item_id": '<?php 
                echo  $order->product_id ;
                ?>',                     // Transaction ID. Required.
                              "item_name": '<?php 
                echo  get_the_title( $order->product_id ) ;
                ?>',    // Product name. Required.
                              "affiliation": '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',               // Affiliation or store name
                              "currency": '<?php 
                echo  $order->currency ;
                ?>',
                              "item_variant": '<?php 
                echo  $order->item_name ;
                ?>',// Variation.
                              "price": '<?php 
                echo  $order->main_offer_amt ;
                ?>',                   // Unit price.
                              "quantity": 1                                                       // Quantity.
                            },
                            <?php 
                
                if ( isset( $order->custom_prices ) ) {
                    ?> 
                                <?php 
                    foreach ( $order->custom_prices as $id => $price ) {
                        ?>
                                {
                                  "item_id": '<?php 
                        echo  $id ;
                        ?>',                 // Transaction ID. Required.
                                  "item_name": '<?php 
                        echo  $price['label'] ;
                        ?>',   // Product name. Required.
                                  "affiliation": '<?php 
                        echo  get_bloginfo( 'name' ) ;
                        ?>',             // Affiliation or store name
                                  "currency": '<?php 
                        echo  $order->currency ;
                        ?>',
                                  "item_variant": '<?php 
                        _e( 'Custom add-on', 'ncs-cart' );
                        ?>',
                                  "price": '<?php 
                        echo  $price['price'] ;
                        ?>', // Unit price.
                                  "quantity": <?php 
                        echo  $price['qty'] ;
                        ?>, // Quantity.
                                },
                                <?php 
                    }
                    ?> 
                            <?php 
                }
                
                ?>

                            <?php 
                
                if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
                    ?> 
                                <?php 
                    foreach ( $order->order_bumps as $k => $order_bump ) {
                        ?>
                                <?php 
                        $category = $order_bump['plan']->name ?? __( 'Order Bump', 'ncs-cart' );
                        ?>
                                {
                                  "item_id": '<?php 
                        echo  $order_bump['id'] ;
                        ?>',   // Transaction ID. Required.
                                  "item_name": '<?php 
                        echo  $order_bump['name'] ;
                        ?>',    // Product name. Required.
                                  "affiliation": '<?php 
                        echo  get_bloginfo( 'name' ) ;
                        ?>',             // Affiliation or store name
                                  "currency": '<?php 
                        echo  $order->currency ;
                        ?>',
                                  "item_variant": '<?php 
                        echo  $category ;
                        ?>',
                                  "price": '<?php 
                        echo  $order_bump['amount'] ;
                        ?>', // Unit price.
                                  "quantity": 1 // Quantity.
                                },
                                <?php 
                    }
                    ?> 
                            <?php 
                }
                
                ?>
                            ]
                        }
                    });
                }
            <?php 
            } elseif ( $ga_type == 'datalayer_enhanced' ) {
                ?>

                dataLayer.push({ ecommerce: null });  // Clear the previous ecommerce object.
                dataLayer.push({
                  'ecommerce': {
                    'purchase': {
                      'actionField': {
                        'id': '<?php 
                echo  $order->id ;
                ?>',                      // Transaction ID. Required for purchases and refunds.
                        'affiliation': '<?php 
                echo  get_bloginfo( 'name' ) ;
                ?>',
                        'revenue': '<?php 
                echo  (double) $order->amount ;
                ?>',     // Total transaction value (incl. tax and shipping)
                        'tax':'<?php 
                echo  (double) $order->tax_amount ;
                ?>',
                        'shipping': '0',
                        'coupon': ''
                      },
                      'products': [
                          {                                                    // List of productFieldObjects.
                            'name': '<?php 
                echo  get_the_title( $order->product_id ) ;
                ?>',     // Name or ID is required.
                            'id': '<?php 
                echo  $order->product_id ;
                ?>',
                            'price': '<?php 
                echo  $order->main_offer_amt ;
                ?>',
                            'category': '<?php 
                echo  $order->item_name ;
                ?>',
                            'variant': '<?php 
                echo  $order->item_name ;
                ?>',
                            'quantity': 1,
                            'coupon': ''                                            // Optional fields may be omitted or set to empty string.
                        },

                        <?php 
                
                if ( isset( $order->custom_prices ) ) {
                    ?> 
                            <?php 
                    foreach ( $order->custom_prices as $id => $price ) {
                        ?>
                            {
                                'name': '<?php 
                        echo  $price['label'] ;
                        ?>',
                                'id': '<?php 
                        echo  $id ;
                        ?>',
                                'price': '<?php 
                        echo  $price['price'] ;
                        ?>',
                                'category': '<?php 
                        _e( 'Custom add-on', 'ncs-cart' );
                        ?>',
                                'variant': '<?php 
                        _e( 'Custom add-on', 'ncs-cart' );
                        ?>',
                                'quantity': '<?php 
                        echo  $price['qty'] ;
                        ?>'
                            },
                            <?php 
                    }
                    ?> 
                        <?php 
                }
                
                ?>

                        <?php 
                
                if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
                    ?> 
                            <?php 
                    foreach ( $order->order_bumps as $k => $order_bump ) {
                        ?>
                            <?php 
                        $category = $order_bump['plan']->name ?? __( 'Order Bump', 'ncs-cart' );
                        ?>
                            {

                                'name': '<?php 
                        echo  $order_bump['name'] ;
                        ?>',
                                'id': '<?php 
                        echo  $order_bump['id'] ;
                        ?>',
                                'price': '<?php 
                        echo  $order_bump['amount'] ;
                        ?>',
                                'category': '<?php 
                        echo  $category ;
                        ?>',
                                'variant': '<?php 
                        echo  $category ;
                        ?>',
                                'quantity': 1
                            },
                            <?php 
                    }
                    ?> 
                        <?php 
                }
                
                ?>                                       
                       ]
                    }
                  }
                });
            <?php 
            }
            
            ?>
            </script>
        <?php 
        }
    
    }
    
    public function sc_product_shortcode( $atts )
    {
        global  $post, $sc_stripe ;
        if ( !isset( $atts['id'] ) || !$atts['id'] ) {
            $atts['id'] = $post->ID;
        }
        // handle default confirmations in custom product templates
        
        if ( isset( $_GET['sc-order'] ) ) {
            $closed_msg = get_post_meta( $atts['id'], '_sc_confirmation_message', true );
            $closed_msg = ( !$closed_msg ? __( "Thank you. We've received your order.", "ncs-cart" ) : $closed_msg );
            $msg = '<p>' . wp_specialchars_decode( $closed_msg, 'ENT_QUOTES' ) . '</p>';
            return $msg . do_shortcode( '[studiocart-receipt]' );
        }
        
        if ( isset( $sc_stripe['pk'] ) ) {
            wp_enqueue_script( 'stripe-api-v3' );
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
            'plan'        => false,
            'hide_labels' => false,
            'template'    => false,
            'skin'        => false,
            'coupon'      => false,
            'builder'     => false,
            'ele_popup'   => false,
        ), $atts ) );
        ob_start();
        
        if ( $skin ) {
            $template = $skin;
        } else {
            
            if ( !$template ) {
                $template = $default_template;
            } else {
                if ( $template == 'normal' ) {
                    $template = '';
                }
            }
        
        }
        
        
        if ( $ele_popup ) {
            wp_localize_script( $this->plugin_name, 'sc_popup', array(
                'is_popup' => 'true',
            ) );
        } else {
            wp_localize_script( $this->plugin_name, 'sc_popup', array(
                'is_popup' => 'false',
            ) );
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
            return ncs_get_template( 'shortcodes/receipt', '', sc_get_item_list( intval( $_GET['sc-order'] ) ) );
        } else {
            return;
        }
    
    }
    
    public function sc_store_shortcode( $attr )
    {
        // Parse shortcode attributes
        $defaults = array(
            'button_text'    => __( 'Purchase', 'ncs-cart' ),
            'purchased_text' => __( 'Already Purchased', 'ncs-cart' ),
            'posts_per_page' => 12,
            'cols'           => 3,
        );
        $attr = shortcode_atts( $defaults, $attr );
        $paged = get_query_var( 'paged', 1 );
        $args = array(
            'posts_per_page' => intval( $attr['posts_per_page'] ),
            'paged'          => $paged,
            'post_type'      => array( 'sc_product', 'sc_collection' ),
        );
        $args = apply_filters( 'sc_product_archive_args', $args );
        $the_query = new WP_Query( $args );
        
        if ( $the_query->have_posts() ) {
            $attr['query'] = $the_query;
            return ncs_get_template( 'shortcodes/archive', '', $attr );
        }
    
    }
    
    public function product_payment_plans( $prod_id )
    {
        $ret = array();
        $options = get_post_meta( $prod_id, '_sc_pay_options' );
        foreach ( $options as $option ) {
            foreach ( $option as $value ) {
                
                if ( isset( $value['option_id'] ) ) {
                    $value['option_name'] = $value['option_name'] ?? $value['option_id'];
                    $ret[$value['option_id']] = $value['option_name'];
                }
            
            }
        }
        return $ret;
    }
    
    public function customer_has_subscription( $atts )
    {
        $atts = shortcode_atts( array(
            'email'      => '',
            'user_id'    => '',
            'product_id' => '',
            'plan_id'    => false,
        ), $atts );
        $atts['has_subscription'] = true;
        return $this->customer_bought_product( $atts );
    }
    
    public function customer_bought_product( $atts )
    {
        $atts = shortcode_atts( array(
            'email'            => '',
            'user_id'          => '',
            'ip_address'       => false,
            'product_id'       => '',
            'plan_id'          => false,
            'has_subscription' => false,
        ), $atts );
        $args = array(
            'posts_per_page' => 1,
            'post_status'    => array( 'any' ),
        );
        // set post type
        
        if ( $atts['has_subscription'] ) {
            $args['post_type'] = 'sc_subscription';
            $post_status = array(
                'active',
                'trialing',
                'paused',
                'past_due'
            );
        } else {
            $args['post_type'] = 'sc_order';
            $post_status = array( 'paid', 'completed' );
        }
        
        // set post status
        $args['meta_query'] = array(
            'relation' => 'AND',
            array(
            'relation' => 'OR',
            array(
            'key'     => '_sc_status',
            'value'   => $post_status,
            'compare' => 'IN',
        ),
            array(
            'relation' => 'AND',
            array(
            'key'   => '_sc_status',
            'value' => 'pending-payment',
        ),
            array(
            'key'   => '_sc_pay_method',
            'value' => 'cod',
        ),
        ),
        ),
        );
        // set user
        
        if ( !$atts['email'] && $atts['user_id'] ) {
            $user = get_userdata( $atts['user_id'] );
            if ( $user ) {
                $atts['email'] = $user->user_email;
            }
        } else {
            
            if ( $atts['email'] && !$atts['user_id'] ) {
                $user = get_user_by( 'email', $atts['email'] );
                if ( $user ) {
                    $atts['user_id'] = $user->ID;
                }
            }
        
        }
        
        $user_args = array(
            'relation' => 'OR',
            array(
            'key'   => '_sc_user_account',
            'value' => $atts['user_id'],
        ),
            array(
            'key'   => '_sc_email',
            'value' => $atts['email'],
        ),
        );
        if ( $atts['ip_address'] ) {
            $user_args[] = array(
                'key'   => '_sc_ip_address',
                'value' => $atts['ip_address'],
            );
        }
        $args['meta_query'][] = $user_args;
        // set product
        if ( $atts['product_id'] ) {
            $args['meta_query'][] = array(
                'relation' => 'OR',
                array(
                'key'   => '_sc_product_id',
                'value' => intval( $atts['product_id'] ),
            ),
                array(
                'key'     => '_sc_order_bumps',
                'value'   => intval( $atts['product_id'] ),
                'compare' => 'LIKE',
            ),
            );
        }
        // set plan ID
        
        if ( $atts['plan_id'] ) {
            $p_args = array(
                'key' => '_sc_option_id',
            );
            
            if ( is_array( $atts['plan_id'] ) ) {
                $p_args['compare'] = 'IN';
                for ( $i = 0 ;  $i < count( $atts['plan_id'] ) ;  $i++ ) {
                    $atts['plan_id'][$i] = sanitize_text_field( $atts['plan_id'][$i] );
                }
                $p_args['value'] = $atts['plan_id'];
            } else {
                $p_args['value'] = sanitize_text_field( $atts['plan_id'] );
            }
            
            $args['meta_query'][] = $p_args;
        }
        
        $posts = get_posts( $args );
        if ( !empty($posts) ) {
            return $posts[0]->ID;
        }
        return false;
    }
    
    public function sc_email_preview_template( $template )
    {
        
        if ( get_query_var( 'sc-preview' ) == 'email' && current_user_can( 'edit_posts' ) && file_exists( plugin_dir_path( __FILE__ ) . 'templates/email/preview.php' ) && (isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'sc-cart' )) ) {
            require_once plugin_dir_path( __FILE__ ) . 'templates/email/preview.php';
            exit;
        }
    
    }
    
    public function sc_product_template( $single )
    {
        global  $post ;
        /* Checks for single template by post type */
        $post_type = (array) apply_filters( 'sc_product_post_type', 'sc_product' );
        
        if ( in_array( $post->post_type, $post_type ) ) {
            $page_template = get_post_meta( $post->ID, '_sc_page_template', true );
            if ( !$page_template && !get_option( '_sc_disable_template' ) ) {
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
        $post_types = (array) apply_filters( 'sc_product_post_type', 'sc_product' );
        if ( !in_array( get_post_type(), $post_types ) || isset( $_GET['sc-order'] ) || isset( $_POST['sc_purchase_amount'] ) ) {
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
            if ( file_exists( plugin_dir_path( __FILE__ ) . 'partials/csv-export.php' ) ) {
                require plugin_dir_path( __FILE__ ) . 'partials/csv-export.php';
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
            $post_types = (array) apply_filters( 'sc_product_post_type', 'sc_product' );
            if ( in_array( get_post_type( $id ), $post_types ) && ($name = get_post_meta( $id, '_sc_product_name', true )) ) {
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
            
            if ( isset( $_GET['step'] ) ) {
                $_POST['sc_process_payment'] = 1;
                $order_id = $_GET['sc-order'];
                $sc_product_id = get_post_meta( $order_id, '_sc_product_id', true );
            } else {
                return;
            }
        
        } else {
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $order_id = intval( $_POST['sc_order_id'] );
        }
        
        $scp = sc_setup_product( $sc_product_id );
        $order_info = (array) sc_setup_order( $order_id );
        if ( isset( $scp->autologin ) && !is_user_logged_in() && in_array( $order_info['status'], [ 'paid', 'completed' ] ) && isset( $order_info['user_account'] ) ) {
            sc_maybe_auto_login_user( $order_info['user_account'], $order_id );
        }
        if ( !isset( $_GET['step'] ) || $_GET['step'] == 1 ) {
            $_POST['sc_purchase_amount'] = $order_info['amount'];
        }
        do_action( 'studiocart_checkout_complete', $order_info['ID'], $scp );
        
        if ( isset( $scp->redirect_url ) ) {
            $redirect = esc_url_raw( sc_personalize( $scp->redirect_url, $order_info ) );
            sc_redirect( $redirect );
        }
        
        return;
    }
    
    public function update_stripe_order_status()
    {
        // base order
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        
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
    
    public function check_product_purchase_limit()
    {
        global  $scp, $sc_debug_logger ;
        if ( !isset( $scp->customer_purchase_limit ) ) {
            return;
        }
        $sc_debug_logger->log_debug( 'Checking product purchase limit' );
        
        if ( !isset( $_POST['sc_product_id'] ) ) {
            echo  json_encode( array(
                'error' => __( "There was a problem with your submission, please refresh the page and try again.", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        
        if ( !$scp ) {
            $product_id = intval( $_POST['sc_product_id'] );
            $scp = sc_setup_product( $product_id );
        } else {
            $product_id = $scp->ID;
        }
        
        $limit = $scp->customer_limit ?? 1;
        $posts = ScrtOrder::get_current_user_orders( $limit, $product_id );
        
        if ( !empty($posts) && count( $posts ) >= $limit ) {
            $sc_debug_logger->log_debug( 'Error: current user ID: ' . get_current_user_id() . ' has already purchased product ID: ' . $product_id, 4 );
            $message = $scp->customer_limit_message ?? __( 'Sorry, you have already purchased this product!', 'ncs-cart' );
            echo  json_encode( array(
                'error' => $message,
            ) ) ;
            exit;
        }
    
    }
    
    public function validate_order_form_submission()
    {
        global  $scp, $sc_debug_logger ;
        $sc_debug_logger->log_debug( "Begin validate_order_form_submission" );
        
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
        if ( $scp->terms_url ) {
            $required[] = 'sc_accept_terms';
        }
        if ( $scp->privacy_url ) {
            $required[] = 'sc_accept_privacy';
        }
        $scp->optin_required = $scp->optin_required ?? false;
        
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
            'company'   => array(
            'name'     => 'company',
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
        // check PWYW
        
        if ( isset( $_POST['sc_product_option'] ) ) {
            $sc_option_id = sanitize_text_field( $_POST['sc_product_option'] );
            $sale = ( isset( $_POST['on-sale'] ) && sc_is_prod_on_sale() ? 1 : 0 );
            $plan = studiocart_plan( $sc_option_id, $sale );
            if ( !$plan ) {
                $messages[] = '• ' . __( "Something went wrong, please refresh the page and try again. ", "ncs-cart" );
            }
            $plan->type = $plan->type ?? '';
            if ( $plan->type == 'pwyw' ) {
                
                if ( !isset( $_POST['pwyw_amount'][$sc_option_id] ) || $_POST['pwyw_amount'][$sc_option_id] < $plan->price ) {
                    $price = sc_format_price( $plan->price, $html = false );
                    $messages[] = '• ' . sprintf( __( "Please enter an amount greater than or equal to %s", "ncs-cart" ), html_entity_decode( $price ) );
                    $errors[] = [
                        'field'   => 'pwyw_amount[' . $sc_option_id . ']',
                        'message' => sprintf( __( "Please enter an amount greater than or equal to %s", "ncs-cart" ), sc_format_price( $plan->price ) ),
                    ];
                }
            
            }
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
        $messages = apply_filters( 'sc_checkout_form_validation_messafes', $messages );
        
        if ( !empty($messages) ) {
            $sc_debug_logger->log_debug( "Form validation errors: " . print_r( $messages, true ), 4 );
            echo  json_encode( array(
                'error'  => __( "There was a problem with your submission, please check your info and try again:", "ncs-cart" ) . "\n" . implode( "\n", $messages ),
                'fields' => $errors,
            ) ) ;
            exit;
        } else {
            $sc_debug_logger->log_debug( "Form submission passed validation" );
        }
    
    }
    
    public function create_payment_intent()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        global 
            $sc_stripe,
            $sc_currency,
            $scp,
            $sc_debug_logger
        ;
        $sc_debug_logger->log_debug( "Creating Stripe payment intent" );
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            $sc_debug_logger->log_debug( 'Nonce check "sc_purchase_nonce:1" failed', 4 );
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        do_action( 'sc_before_create_stripe_payment_intent' );
        do_action( 'sc_before_create_main_order' );
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
        $order = apply_filters( 'sc_after_order_load_from_post', $order );
        // stripe only fields
        if ( $order->pay_method == 'stripe' && $order->amount && isset( $sc_stripe['mode'] ) ) {
            $order->gateway_mode = $sc_stripe['mode'];
        }
        $amount = $order->amount;
        $sub_total = $order->invoice_subtotal;
        $tax_applied = false;
        if ( $order->tax_amount > 0 ) {
            $tax_applied = true;
        }
        // Setup payment intent and return result
        $amount_for_stripe = sc_price_in_cents( $amount, $sc_currency );
        $client_secret = '';
        $intent_id = '';
        $descriptor = get_option( '_sc_stripe_descriptor', false );
        if ( !$descriptor ) {
            $descriptor = get_bloginfo( 'name' ) ?? sc_get_public_product_name( $sc_product_id );
        }
        $bump_plan = false;
        
        if ( is_array( $order->order_bumps ) && isset( $order->order_bumps['main'] ) ) {
            // does main order bump have a subscription?
            $bump = $order->order_bumps['main'];
            if ( isset( $bump['plan'] ) && $bump['plan']->type == 'recurring' ) {
                $bump_plan = true;
            }
        }
        
        $order_id = "";
        $create_intent = apply_filters( 'sc_create_stripe_intent', ($order->plan->type == 'free' || $order->plan->type == 'one-time' || $order->plan->type == 'pwyw') && !$bump_plan, $order );
        
        if ( $create_intent ) {
            $args = [
                'amount'                      => $amount_for_stripe,
                'currency'                    => $sc_currency,
                'statement_descriptor_suffix' => preg_replace( "/[^0-9a-zA-Z ]/", '', substr( $descriptor, 0, 22 ) ),
                'description'                 => sc_get_public_product_name( $sc_product_id ),
                'customer'                    => $customer->id,
                'confirmation_method'         => 'automatic',
                'setup_future_usage'          => 'off_session',
                'metadata'                    => [
                'sc_product_id' => $sc_product_id,
                'origin'        => get_site_url(),
            ],
            ];
            $sc_debug_logger->log_debug( "Stripe payment intent args - " . print_r( $args, true ) );
            try {
                $intent = \Stripe\PaymentIntent::create( $args );
            } catch ( Exception $e ) {
                $sc_debug_logger->log_debug( "Stripe error: " . $e->getMessage(), 4 );
                echo  json_encode( array(
                    'error' => __( $e->getMessage(), "ncs-cart" ),
                ) ) ;
                exit;
            }
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
            $sc_debug_logger->log_debug( "Stripe payment intent created", 0 );
            echo  json_encode( array(
                'clientSecret'     => $client_secret,
                'intent_id'        => $intent_id,
                'customer_id'      => $customer->id,
                'amount'           => $amount,
                'sub_total'        => $sub_total,
                'tax_applied'      => $tax_applied,
                'sc_temp_order_id' => $order_id,
                'prod_id'          => $sc_product_id,
            ) ) ;
        } else {
            $sc_debug_logger->log_debug( "Error: Stripe customer not created", 4 );
            json_encode( array(
                'error' => 'There was an error, please try again.',
            ) );
        }
        
        exit;
    }
    
    public function update_payment_intent_amt( $pid = false )
    {
        global  $sc_stripe, $sc_currency, $sc_debug_logger ;
        $sc_debug_logger->log_debug( "Updating Stripe payment intent amount" );
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        \Stripe\Stripe::setApiVersion( "2020-03-02" );
        $echo = false;
        $amount = $this->calculate_cart_total();
        $amount_for_stripe = sc_price_in_cents( $amount, $sc_currency );
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
            $sc_debug_logger->log_debug( "Stripe error: " . $e->getMessage(), 4 );
            echo  $e ;
            exit;
            //$_POST["sc_errors"]['messages'][] = __("There was a problem processing this order. Please try again", "ncs-cart");
            //return;
        }
        $sc_debug_logger->log_debug( "Stripe payment intent amount updated", 0 );
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
        global  $sc_stripe, $scp, $sc_debug_logger ;
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
            $sc_debug_logger->log_debug( "Checkout form submitted - " . print_r( $_POST, true ) );
            // base order
            
            if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
                $sc_debug_logger->log_debug( 'Nonce check "sc_purchase_nonce:2" failed', 4 );
                echo  json_encode( array(
                    'error' => __( "Invalid Request", "ncs-cart" ),
                ) ) ;
                exit;
            }
            
            $sc_debug_logger->log_debug( "Validating submission" );
            do_action( 'sc_before_create_main_order' );
            $sc_debug_logger->log_debug( "Submission validated", 0 );
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
                $order = apply_filters( 'sc_after_order_load_from_post', $order );
                
                if ( !isset( $order->order_summary_items ) || empty($order->order_summary_items) ) {
                    echo  json_encode( array(
                        'error' => __( "No items added", "ncs-cart" ),
                    ) ) ;
                    exit;
                }
            
            }
            
            // stripe only fields
            
            if ( $order->pay_method == 'stripe' && $order->amount && isset( $sc_stripe['mode'] ) ) {
                $order->gateway_mode = $sc_stripe['mode'];
                $order->transaction_id = sanitize_text_field( $_POST['intent_id'] );
            }
            
            // Free order
            
            if ( !isset( $_POST['sc_temp_order_id'] ) && $order->non_formatted_amount == 0 ) {
                $order->pay_method = 'free';
                $order->status = 'paid';
            }
            
            $sc_debug_logger->log_debug( "Storing order" );
            // save order to db
            $post_id = $order->store();
            
            if ( $post_id ) {
                $sc_debug_logger->log_debug( "Order #{$post_id} stored, status {$order->status}", 0 );
            } else {
                $sc_debug_logger->log_debug( "Failed to store order", 4 );
            }
        
        }
        
        $data['order_id'] = $post_id;
        $data['amount'] = $order->amount;
        $order_info = $order->get_data();
        
        if ( !$scp->upsell_path && $scp->confirmation != 'redirect' ) {
            $data['formAction'] = add_query_arg( 'sc-order', $post_id, $scp->thanks_url );
            $data['formAction'] = apply_filters(
                'studiocart_post_purchase_url',
                $data['formAction'],
                $post_id,
                $sc_product_id
            );
        }
        
        
        if ( isset( $scp->redirect_url ) && !$scp->upsell_path ) {
            $redirect = esc_url_raw( sc_personalize( $scp->redirect_url, $order_info, 'urlencode' ) );
            $data['redirect'] = $redirect;
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
                
                if ( is_array( $_POST['sc_custom_fields'][$key] ) && count( $_POST['sc_custom_fields'][$key] ) == 1 ) {
                    $value = sanitize_text_field( $_POST['sc_custom_fields'][$key][0] );
                } else {
                    
                    if ( is_array( $_POST['sc_custom_fields'][$key] ) ) {
                        $value = array();
                        foreach ( $_POST['sc_custom_fields'][$key] as $val ) {
                            $value[] = sanitize_text_field( $val );
                        }
                    } else {
                        $value = sanitize_text_field( $_POST['sc_custom_fields'][$key] );
                    }
                
                }
                
                $custom_fields[$field_id] = array(
                    'label' => sanitize_text_field( $field['field_label'] ),
                    'value' => $value,
                );
                
                if ( in_array( $field['field_type'], [ 'select', 'checkbox', 'radio' ] ) ) {
                    $choices = [];
                    $options = explode( "\n", str_replace( "\r", "", esc_attr( $field['select_options'] ) ) );
                    for ( $i = 0 ;  $i < count( $options ) ;  $i++ ) {
                        $option = explode( ':', $options[$i] );
                        if ( count( $option ) > 1 ) {
                            
                            if ( trim( $option[0] ) == $value ) {
                                $label = trim( $option[1] );
                                
                                if ( is_array( $custom_fields[$field_id]['value'] ) ) {
                                    if ( !isset( $custom_fields[$field_id]['value_label'] ) ) {
                                        $custom_fields[$field_id]['value_label'] = array();
                                    }
                                    $custom_fields[$field_id]['value_label'][] = $label;
                                } else {
                                    $custom_fields[$field_id]['value_label'] = $label;
                                    break;
                                }
                            
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
        update_post_meta( $post_id, '_sc_plan_price', $order['plan_price'] );
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
                    update_post_meta( $post_id, '_sc_vat_data', $vat_data );
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
    
    private function test_conditions( $value, $compare, $type )
    {
        $ret = false;
        
        if ( is_bool( $type ) === true ) {
            if ( $value && $compare === '' || $compare === $value ) {
                $ret = true;
            }
            if ( !$type ) {
                return !$ret;
            }
        } else {
            switch ( $type ) {
                case $type == 'greater':
                    $ret = intval( $value ) > intval( $compare );
                    break;
                case $type == 'less':
                    $ret = intval( $value ) < intval( $compare );
                    break;
                case $type == 'contains':
                    $ret = strpos( $value, $compare ) !== false;
                    break;
            }
        }
        
        return $ret;
    }
    
    public function conditional_order_confirmations( $order_id, $scp )
    {
        // does this product have multiple confirmations?
        if ( !isset( $scp->confirmations ) || isset( $_GET['sc-cc'] ) ) {
            return;
        }
        /* did not order means $type = false
        
                // did not order any
                $type = false;
                $compare_to = '';
                $value = false;
        
                // bump but not main
                $type = false;
                $compare_to = 'main';
                $value = true;
        
                // no bump and not main
                $type = false;
                $compare_to = 'main';
                $value = false;
        
                // did not order any, compare to main
                $type = true;
                $compare_to = 'main';
                $value = false;
        
                // did order any
                $type = true;
                $compare_to = true;
                $value = true;
        
                // did order main
                $type = true;
                $compare_to = 'main';
                $value = 'main';*/
        $order = new ScrtOrder( $order_id );
        foreach ( $scp->confirmations as $confirmation ) {
            $matched = $confirmation['condition_type'] == 'and';
            $upsell = $downsell = false;
            // loop through each confirmation group until we find first match
            foreach ( $confirmation['conditions'] as $condition ) {
                
                if ( $condition['action'] == 'ordered' || $condition['action'] == 'not-ordered' ) {
                    switch ( $condition['product_type'] ) {
                        case 'plan':
                            $value = $order->option_id;
                            $compare_to = $condition['plan'] ?? '';
                            break;
                        case 'upsell':
                            $compare_to = $condition['upsell'] ?? '';
                            $value = (bool) $order->get_upsell( $compare_to );
                            if ( $compare_to !== '' && $value ) {
                                $value = $compare_to;
                            }
                            break;
                        case 'downsell':
                            $compare_to = $condition['upsell'] ?? '';
                            $value = (bool) $order->get_downsell( $compare_to );
                            if ( $compare_to !== '' && $value ) {
                                $value = $compare_to;
                            }
                            break;
                        case 'bump':
                            $value = is_countable( $order->order_bumps );
                            $compare_to = $condition['bump'] ?? '';
                            if ( is_numeric( $compare_to ) ) {
                                $compare_to--;
                            }
                            if ( $compare_to !== '' && $value && array_key_exists( $compare_to, $order->order_bumps ) ) {
                                $value = $compare_to;
                            }
                            break;
                    }
                    $type = ( $condition['action'] == 'ordered' ? true : false );
                } else {
                    
                    if ( $condition['action'] == 'field-value' ) {
                        switch ( $condition['cfield_compare'] ) {
                            case 'is':
                                $type = true;
                                break;
                            case 'not':
                                $type = false;
                                break;
                            default:
                                $type = $condition['cfield_compare'];
                                break;
                        }
                        $field = $condition['cfield'];
                        $value = $order->{$field} ?? $order->custom_fields[$field]['value'] ?? '';
                        $compare_to = ( isset( $condition['cfield_value'] ) && $condition['cfield_value'] ? $condition['cfield_value'] : '' );
                    }
                
                }
                
                $passed = $this->test_conditions( $value, $compare_to, $type );
                
                if ( $passed === true && $confirmation['condition_type'] == 'or' ) {
                    $matched = true;
                    break;
                } else {
                    
                    if ( $passed === false && $confirmation['condition_type'] == 'and' ) {
                        $matched = false;
                        break;
                    }
                
                }
            
            }
            // end foreach
            // match found, set up redirect for current confirmation
            
            if ( $matched ) {
                
                if ( $confirmation['confirmation_type'] == 'redirect' ) {
                    // handle confirmation redirect
                    $url = $confirmation['confirmation_redirect'];
                    $url = esc_url_raw( sc_personalize( $url, $order->get_data() ) );
                } else {
                    // handle confirmation page and appends
                    $data = $order->get_data();
                    // add flag "sc-cc" to know we did this already
                    $args = array(
                        'sc-order' => $order_id,
                        'sc-cc'    => 1,
                    );
                    if ( isset( $_GET['sc-oto'] ) ) {
                        $args['sc-oto'] = intval( $_GET['sc-oto'] );
                    }
                    if ( isset( $_GET['sc-oto-2'] ) ) {
                        $args['sc-oto-2'] = intval( $_GET['sc-oto-2'] );
                    }
                    if ( isset( $_GET['step'] ) ) {
                        $args['step'] = intval( $_GET['step'] );
                    }
                    
                    if ( $confirmation['confirmation_type'] == 'append' ) {
                        // use default product URL if appending
                        
                        if ( isset( $scp->redirect_url ) ) {
                            $url = $scp->redirect_url . $confirmation['confirmation_append'];
                        } else {
                            $url = $scp->thanks_url;
                            parse_str( $confirmation['confirmation_append'], $customargs );
                            $args = array_merge( $args, $customargs );
                            $url = add_query_arg( $args, $url );
                        }
                        
                        $url = esc_url_raw( sc_personalize( $url, $data ) );
                    } else {
                        $url = get_permalink( $confirmation['confirmation_page'] );
                        $url = add_query_arg( $args, $url );
                        $url = esc_url_raw( sc_personalize( $url, $data ) );
                    }
                
                }
                
                sc_redirect( $url );
            }
        
        }
    }
    
    public function maybe_change_thank_you_page( $formAction, $order_id, $product_id )
    {
        // Bump redirect (deprecated)
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
        global  $sc_stripe, $sc_currency, $sc_debug_logger ;
        $sc_debug_logger->log_debug( "Checkout form submitted (has subscription)" );
        require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
        // base order
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            $sc_debug_logger->log_debug( 'Nonce check "sc_purchase_nonce:3" failed' );
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
        $sc_debug_logger->log_debug( "Validating submission" );
        // create base order
        do_action( 'sc_before_create_main_order' );
        $sc_debug_logger->log_debug( "Submission validated", 0 );
        $order = new ScrtOrder();
        $order->load_from_post();
        $order = apply_filters( 'sc_after_order_load_from_post', $order );
        $order->gateway_mode = $sc_stripe['mode'];
        $apikey = $sc_stripe['sk'];
        $stripe = new \Stripe\StripeClient( [
            "api_key"        => $apikey,
            "stripe_version" => "2020-08-27",
        ] );
        $sc_debug_logger->log_debug( "Retrieving Stripe payment method" );
        try {
            $payment_method = $stripe->paymentMethods->retrieve( $paymethod_id );
            $payment_method->attach( [
                'customer' => $customer_id,
            ] );
        } catch ( Exception $e ) {
            $sc_debug_logger->log_debug( "Stripe error: " . $e->getMessage(), 4 );
            echo  json_encode( array(
                'error' => __( $e->getMessage(), "ncs-cart" ),
            ) ) ;
            exit;
        }
        $sc_debug_logger->log_debug( "Stripe payment method retrieved", 0 );
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
            $sc_debug_logger->log_debug( "Retrying Stripe invoice with updated payment method" );
            try {
                $invoiceId = sanitize_text_field( $_POST['invoiceId'] );
                $invoice = $stripe->invoices->retrieve( $invoiceId, [
                    'expand' => [ 'payment_intent' ],
                ] );
                echo  json_encode( $invoice ) ;
                exit;
            } catch ( Exception $e ) {
                $sc_debug_logger->log_debug( "Stripe error: " . $e->getMessage(), 4 );
                unset( $_POST['invoiceId'] );
            }
        }
        
        $sc_debug_logger->log_debug( "Storing subscription" );
        // save subscription
        $sub = ScrtSubscription::from_order( $order );
        $sub->store();
        $order->subscription_id = $sub->id;
        $order->store();
        
        if ( $sub->id ) {
            $sc_debug_logger->log_debug( "Stored subscription #{$sub->id}, status {$sub->status}", 0 );
        } else {
            $sc_debug_logger->log_debug( "Failed to store subscription", 4 );
        }
        
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
            
            $sub->store();
            
            if ( $sub->id ) {
                $sc_debug_logger->log_debug( "Updated subscription status to {$sub->status}", 0 );
            } else {
                $sc_debug_logger->log_debug( "Failed to update subscription status", 4 );
            }
            
            
            if ( $subscription->latest_invoice->charge ) {
                $order->transaction_id = $subscription->latest_invoice->charge;
            } else {
                $order->transaction_id = $subscription->latest_invoice->payment_intent->id;
            }
            
            // if setting for webhook updates only, then evaluate to false
            $update_invoice = !get_option( '_sc_stripe_invoice_webhook_updates_only', false );
            
            if ( apply_filters(
                'sc_update_stripe_invoice_during_checkout',
                $update_invoice,
                $order,
                $sub
            ) ) {
                // run integrations
                
                if ( isset( $subscription->status ) && $subscription->status != 'incomplete' ) {
                    $order->status = 'paid';
                    $order->payment_status = 'paid';
                }
                
                $order->store();
                
                if ( $order->id ) {
                    $sc_debug_logger->log_debug( "Updated order status to {$order->status}", 0 );
                } else {
                    $sc_debug_logger->log_debug( "Failed to update order status", 4 );
                }
            
            }
            
            // setup redirect
            $subscription->sc_order_id = $order->id;
            $sc_product_id = intval( $_POST['sc_product_id'] );
            $scp = sc_setup_product( $sc_product_id );
            
            if ( !$scp->upsell_path && $scp->confirmation != 'redirect' ) {
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
        global  $sc_stripe, $sc_currency, $sc_debug_logger ;
        $sc_debug_logger->log_debug( "Preparing to send subscription to Stripe" );
        $apikey = $sc_stripe['sk'];
        $stripe = new \Stripe\StripeClient( [
            "api_key"        => $apikey,
            "stripe_version" => "2020-08-27",
        ] );
        // Create the subscription
        $args = [
            'customer'           => $order->customer_id,
            'items'              => array(),
            'trial_from_plan'    => false,
            'proration_behavior' => 'none',
            'expand'             => [ 'latest_invoice.payment_intent' ],
            'metadata'           => [
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
            
            if ( isset( $sub->tax_data->redeem_vat ) && $sub->tax_data->redeem_vat ) {
                try {
                    $coupon = $stripe->coupons->create( [
                        'amount_off'      => sc_price_in_cents( $sub->tax_amount, $sc_currency ),
                        'currency'        => $sc_currency,
                        'name'            => get_option( '_sc_vat_reverse_charge', "VAT Reversal" ),
                        'duration'        => 'forever',
                        'max_redemptions' => 1,
                    ] );
                    $args['coupon'] = $coupon->id;
                } catch ( Exception $e ) {
                    echo  json_encode( [
                        'error' => $e->getMessage(),
                    ] ) ;
                    exit;
                }
            } else {
                $tax_rates = [
                    'tax_rates' => [ $sub->stripe_tax_id ],
                ];
            }
        
        }
        // calculate addons and discount
        if ( $sub->cancel_at ) {
            $args['cancel_at'] = $sub->cancel_at;
        }
        // Add sign up fee
        if ( $sub->sign_up_fee ) {
            $addon += $sub->sign_up_fee;
        }
        // is main order a subscription?
        
        if ( $order->plan->type == 'recurring' ) {
            $item_args = array_merge( [
                'price' => $order->plan->stripe_id,
            ], $tax_rates );
            if ( $sub->quantity > 1 ) {
                $item_args['quantity'] = $sub->quantity;
            }
            $args['items'][] = $item_args;
        } else {
            $addon += $order->plan->price;
        }
        
        // process order bumps
        if ( is_array( $order->order_bumps ) ) {
            foreach ( $order->order_bumps as $bump ) {
                // do order bumps have a subscription?
                
                if ( isset( $bump['plan'] ) && $bump['plan']->type == 'recurring' ) {
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
        $args = apply_filters(
            'sc_checkout_stripe_subscription_args',
            $args,
            $order,
            $sub
        );
        $sc_debug_logger->log_debug( "Creating Stipe subscription - args: " . print_r( $args, true ) );
        try {
            $subscription = $stripe->subscriptions->create( $args );
        } catch ( Exception $e ) {
            $sc_debug_logger->log_debug( "Stripe error: " . $e->getMessage(), 4 );
            echo  json_encode( [
                'error' => $e->getMessage(),
            ] ) ;
            exit;
        }
        
        if ( isset( $subscription->id ) ) {
            $sc_debug_logger->log_debug( "Stripe subscription created - ID {$subscription->id}", 0 );
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
    
    /**
     * Renders the contents of the given template to a string and returns it.
     *
     * @param string $template_name The name of the template to render (without .php)
     * @param array  $attr    The PHP variables for the template
     *
     * @return string               The contents of the template.
     */
    private function get_template( $template_name, $attr = null )
    {
        if ( !$attr ) {
            $attr = array();
        }
        ob_start();
        do_action( 'sc_login_before_' . $template_name );
        require 'templates/' . $template_name . '.php';
        do_action( 'sc_login_after_' . $template_name );
        $html = ob_get_contents();
        ob_end_clean();
        return $html;
    }
    
    public function set_my_account_url()
    {
        $this->my_account_url = $this->get_my_account_url();
    }
    
    public function get_my_account_url()
    {
        
        if ( $pid = get_option( '_sc_myaccount_page_id' ) ) {
            return get_permalink( $pid );
        } else {
            
            if ( $pid = get_option( '_my_account' ) ) {
                delete_option( '_my_account' );
                update_option( '_sc_myaccount_page_id', $pid );
                return get_permalink( $pid );
            }
        
        }
        
        return false;
    }
    
    public function add_lost_password_link()
    {
        if ( get_the_ID() != get_option( '_sc_myaccount_page_id' ) ) {
            return;
        }
        return '<a href="?action=lostpassword">' . __( 'Reset Password', 'ncs-cart' ) . '</a>';
    }
    
    public function order_detail_shortcode( $attr, $content = null )
    {
        if ( is_user_logged_in() && isset( $_REQUEST['sc-order'] ) ) {
            return ncs_get_template( 'my-account/order', 'detail', $attr );
        }
        return;
    }
    
    public function subscription_detail_shortcode( $attr, $content = null )
    {
        if ( is_user_logged_in() && isset( $_REQUEST['sc-plan'] ) ) {
            return ncs_get_template( 'my-account/subscription', 'detail', $attr );
        }
        return;
    }
    
    public function my_account_page_link_shortcode( $attr, $content = null )
    {
        // Parse shortcode attributes
        $defaults = array(
            'label' => __( 'My Account', 'ncs-cart' ),
        );
        $label = $attr['label'];
        if ( get_the_ID() != get_option( '_sc_myaccount_page_id' ) ) {
            return '<a href="' . $this->my_account_url . '">' . $label . '</a>';
        }
        return false;
    }
    
    public function my_account_page_shortcode( $attr, $content = null )
    {
        // Parse shortcode attributes
        $defaults = array(
            'show_title' => false,
            'hide_login' => false,
            'tab'        => '',
        );
        $attr = shortcode_atts( $defaults, $attr );
        $show_title = $attr['show_title'];
        $tab = $attr['tab'];
        $messages = sc_translate_js( 'ncs-cart-public.js' );
        
        if ( is_user_logged_in() ) {
            
            if ( !$tab ) {
                
                if ( isset( $_REQUEST['sc-order'] ) ) {
                    $attr['order'] = intval( $_REQUEST['sc-order'] );
                    $ret = ncs_get_template( 'my-account/order', 'detail', $attr );
                } else {
                    
                    if ( isset( $_REQUEST['sc-plan'] ) ) {
                        $attr['plan'] = intval( $_REQUEST['sc-plan'] );
                        $ret = ncs_get_template( 'my-account/subscription', 'detail', $attr );
                    } else {
                        $ret = ncs_get_template( 'my-account/my-account', '', $attr );
                    }
                
                }
            
            } else {
                if ( !isset( $_REQUEST['sc-order'] ) && !isset( $_REQUEST['sc-plan'] ) ) {
                    $ret = '<div class="ncs-my-account">' . ncs_get_template( 'my-account/tabs/' . $tab, '', $attr ) . '</div>';
                }
            }
            
            if ( $ret = apply_filters( 'sc_my_account_tab_content', $ret ) ) {
                return $ret;
            }
        } else {
            
            if ( !$tab && (!$attr['hide_login'] || $attr['hide_login'] == 'false') ) {
                $attr['login_url'] = $this->my_account_url;
                $attr['action'] = $_REQUEST['action'] ?? 'login';
                $attr['errors'] = array();
                
                if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
                    // show password reset
                    $attr['action'] = 'reset';
                    $attr['login'] = $_REQUEST['login'];
                    $attr['key'] = $_REQUEST['key'];
                    // Error messages
                    
                    if ( isset( $_REQUEST['error'] ) ) {
                        $error_codes = explode( ',', $_REQUEST['error'] );
                        foreach ( $error_codes as $error_code ) {
                            $attr['errors'][] = $messages[$error_code];
                        }
                    }
                
                } else {
                    
                    if ( $attr['action'] == 'lostpassword' ) {
                        // Check if the user just requested a new password
                        $attr['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';
                        // Retrieve possible errors from request parameters
                        $attr['errors'] = array();
                        
                        if ( isset( $_REQUEST['errors'] ) ) {
                            $error_codes = explode( ',', $_REQUEST['errors'] );
                            foreach ( $error_codes as $error_code ) {
                                $attr['errors'][] = $messages[$error_code];
                            }
                        }
                    
                    } else {
                        
                        if ( isset( $_REQUEST['login'] ) ) {
                            $errors = array();
                            $error_codes = explode( ',', $_REQUEST['login'] );
                            foreach ( $error_codes as $error_code ) {
                                $errors[] = $messages[$error_code];
                            }
                            $attr['errors'] = $errors;
                        }
                    
                    }
                
                }
                
                // Check if user just updated password
                $attr['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';
                return ncs_get_template( 'my-account/forms/login', 'form', $attr );
            } else {
                return;
            }
        
        }
    
    }
    
    /**
     * Initiates password reset.
     */
    public function do_password_lost()
    {
        if ( $this->maybe_use_default_login_authentication() ) {
            return;
        }
        
        if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $errors = retrieve_password();
            
            if ( is_wp_error( $errors ) ) {
                // Errors found
                $args = array(
                    'action' => 'lostpassword',
                    'errors' => join( ',', $errors->get_error_codes() ),
                );
                $redirect_url = add_query_arg( $args, $this->my_account_url );
            } else {
                // Email sent
                $args = array(
                    'action'     => 'lostpassword',
                    'checkemail' => 'confirm',
                );
                $redirect_url = add_query_arg( $args, $this->my_account_url );
            }
            
            wp_redirect( $redirect_url );
            exit;
        }
    
    }
    
    /**
     * Redirects to the custom password reset page, or the login page
     * if there are errors.
     */
    public function redirect_to_custom_password_reset()
    {
        if ( $this->maybe_use_default_login_authentication() ) {
            return;
        }
        $redirect_url = $this->my_account_url;
        $redirect_url = add_query_arg( 'action', 'lostpassword', $redirect_url );
        if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
            
            if ( isset( $_REQUEST['key'] ) && isset( $_REQUEST['login'] ) ) {
                // Verify key / login combo
                $user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
                
                if ( !$user || is_wp_error( $user ) ) {
                    
                    if ( $user && $user->get_error_code() === 'expired_key' ) {
                        wp_redirect( add_query_arg( 'errors', 'expiredkey', $redirect_url ) );
                    } else {
                        wp_redirect( add_query_arg( 'errors', 'invalidkey', $redirect_url ) );
                    }
                    
                    exit;
                }
                
                $redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
                $redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );
            }
        
        }
        wp_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Resets the user's password if the password reset form was submitted.
     */
    public function do_password_reset()
    {
        if ( $this->maybe_use_default_login_authentication() ) {
            return;
        }
        $redirect_url = $this->my_account_url;
        
        if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
            $rp_key = $_REQUEST['rp_key'];
            $rp_login = $_REQUEST['rp_login'];
            $user = check_password_reset_key( $rp_key, $rp_login );
            
            if ( !$user || is_wp_error( $user ) ) {
                
                if ( $user && $user->get_error_code() === 'expired_key' ) {
                    $redirect_url = add_query_arg( 'login', 'expiredkey', $redirect_url );
                } else {
                    $redirect_url = add_query_arg( 'login', 'invalidkey', $redirect_url );
                }
                
                exit;
            }
            
            
            if ( isset( $_POST['pass1'] ) ) {
                
                if ( $_POST['pass1'] != $_POST['pass2'] ) {
                    // Passwords don't match
                    $redirect_url = add_query_arg( 'action', 'lostpassword', $redirect_url );
                    $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                    $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                    $redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );
                    wp_redirect( $redirect_url );
                    exit;
                }
                
                
                if ( empty($_POST['pass1']) ) {
                    // Password is empty
                    $redirect_url = add_query_arg( 'action', 'lostpassword', $redirect_url );
                    $redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
                    $redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
                    $redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );
                    wp_redirect( $redirect_url );
                    exit;
                }
                
                // Parameter checks OK, reset password
                reset_password( $user, $_POST['pass1'] );
                wp_redirect( add_query_arg( 'password', 'changed', $redirect_url ) );
            } else {
                echo  "Invalid request." ;
            }
            
            exit;
        }
    
    }
    
    /**
     * Restricts when WordPress will use our authentication redirects and logic vs the defaults.
     *
     * @param boolean  $return       Whether or not to use the default WP logic.
     *
     * @return boolean Whether or not to use the default WP logic.
     */
    function maybe_use_default_login_authentication( $return = false )
    {
        $login_url = $this->my_account_url;
        $return = !$this->my_account_url || !isset( $_SERVER['HTTP_REFERER'] ) || strtok( $_SERVER['HTTP_REFERER'], '?' ) != $login_url;
        return apply_filters( 'sc_use_default_authentication_logic', $return );
    }
    
    /**
     * Redirect the user after authentication if there were any errors.
     *
     * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
     * @param string            $username   The user name used to log in.
     * @param string            $password   The password used to log in.
     *
     * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
     */
    function maybe_redirect_at_authenticate( $user, $username, $password )
    {
        // Check if the earlier authenticate filter (most likely,
        // the default WordPress authentication) functions have found errors
        $login_url = $this->my_account_url;
        if ( $this->maybe_use_default_login_authentication() ) {
            return $user;
        }
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            
            if ( is_wp_error( $user ) ) {
                $error_codes = join( ',', $user->get_error_codes() );
                $login_url = add_query_arg( 'login', $error_codes, $login_url );
                wp_redirect( $login_url );
                exit;
            }
        
        }
        return $user;
    }
    
    public function maybe_disable_welcome_email( $send_email, $order_id )
    {
        $pid = ScrtOrder::get_meta_value( $order_id, 'product_id' );
        if ( get_post_meta( $pid, '_sc_disable_welcome_email', true ) ) {
            $send_email = false;
        }
        return $send_email;
    }
    
    public function sc_update_cart_amount()
    {
        //error_reporting(E_ALL);
        //ini_set("display_errors", 1);
        $params = $_POST;
        
        if ( !wp_verify_nonce( $_POST['sc-nonce'], "sc_purchase_nonce" ) ) {
            echo  json_encode( array(
                'error' => __( "Invalid Request", "ncs-cart" ),
            ) ) ;
            exit;
        }
        
        // setup product info
        $sc_product_id = intval( $_POST['sc-prod-id'] );
        $scp = sc_setup_product( $sc_product_id );
        // setup order info
        $order = new ScrtOrder();
        $order->load_from_post();
        $order = apply_filters( 'sc_after_order_load_from_post', $order );
        foreach ( $order->order_summary_items as $k => $item ) {
            $order->order_summary_items[$k]['subtotal'] = sc_format_price( $order->order_summary_items[$k]['subtotal'] );
            if ( $item['type'] == 'discount' ) {
                $order->order_summary_items[$k]['subtotal'] = '-' . $order->order_summary_items[$k]['subtotal'];
            }
        }
        $response = array(
            'total'               => $order->amount,
            'order_summary_items' => $order->order_summary_items,
            'total_html'          => esc_html__( 'Amount Due', 'ncs-cart' ),
        );
        if ( !$order->order_summary_items ) {
            $response['empty'] = esc_html__( "Your cart is empty.", 'ncs-cart' );
        }
        
        if ( $order->plan->type == 'recurring' ) {
            $sub = ScrtSubscription::from_order( $order );
            $sub = $sub->get_data();
            $response['sub_summary'] = apply_filters(
                'sc_format_subcription_order_detail',
                $sub['sub_payment_terms_plain'],
                $sub['sub_payment_terms_plain'],
                $sub['free_trial_days'],
                $sub['sign_up_fee'],
                $sub['sub_discount'],
                $sub['sub_discount_duration'],
                $order->plan
            );
            $response['sub_summary'] = html_entity_decode( $response['sub_summary'] );
            $response['total_html'] = esc_html__( 'Due Today', 'ncs-cart' );
        }
        
        $response['total_html'] .= '<span class="price">' . sc_format_price( $response['total'], false ) . '</span>';
        //var_dump($order->get_data());
        wp_send_json( $response );
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
        $redeem_vat = false;
        $is_valid = true;
        $vat_error = "";
        $merchant_country = get_option( '_sc_vat_merchant_state' );
        $params['country'] = ( !empty($params['country']) ? $params['country'] : $merchant_country );
        
        if ( !empty($params['vat_number']) ) {
            
            if ( !get_option( '_sc_vat_disable_vies_database_lookup', false ) ) {
                $vat_number = $params['vat_number'];
                // Strip VAT Number of country code, spaces and periods
                if ( ctype_alpha( substr( $vat_number, 0, 2 ) ) ) {
                    $vat_number = substr( $vat_number, 2 );
                }
                $vat_number = str_replace( " ", "", $vat_number );
                $vat_number = str_replace( ".", "", $vat_number );
                
                if ( $params['country'] == "GB" ) {
                    $curl = curl_init();
                    curl_setopt_array( $curl, array(
                        CURLOPT_URL            => 'https://api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/' . $vat_number,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING       => '',
                        CURLOPT_MAXREDIRS      => 10,
                        CURLOPT_TIMEOUT        => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST  => 'GET',
                        CURLOPT_HTTPHEADER     => array( 'Accept: application/vnd.hmrc.1.0+json' ),
                    ) );
                    $response = curl_exec( $curl );
                    
                    if ( $response == false ) {
                        $is_valid = false;
                        $vat_error = "Vat Validation Failed";
                    } else {
                        $vat_data = json_decode( $response );
                        
                        if ( $vat_data->target ) {
                            $is_valid = true;
                        } else {
                            $is_valid = false;
                            $vat_error = $vat_data->message;
                        }
                    
                    }
                    
                    curl_close( $curl );
                } else {
                    $client = new SoapClient( "http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl" );
                    try {
                        $vat_data = $client->checkVat( array(
                            'countryCode' => $params['country'],
                            'vatNumber'   => $vat_number,
                        ) );
                        $is_valid = $vat_data->valid;
                    } catch ( Exception $e ) {
                        $is_valid = false;
                        $vat_error = $e->getMessage();
                    }
                }
            
            }
            
            if ( !get_option( '_sc_vat_all_eu_businesses', false ) && $params['country'] != get_option( '_sc_vat_merchant_state', false ) ) {
                if ( $is_valid ) {
                    $redeem_vat = true;
                }
            }
        }
        
        $tax_rate = NCS_Cart_Tax::get_country_vat_rates( $params['country'], $params['zip'] ?? '' );
        
        if ( empty($tax_rate) ) {
            $is_vat = false;
            $tax_rate = NCS_Cart_Tax::get_matched_tax_rates(
                $params['country'],
                $params['state'] ?? '',
                $params['zip'] ?? '',
                $params['city'] ?? ''
            );
        }
        
        $response = array(
            'rates'        => $tax_rate,
            'is_vat'       => $is_vat,
            'redeem_vat'   => $redeem_vat,
            'is_valid_vat' => $is_valid,
            'vat_error'    => $vat_error,
        );
        wp_send_json( $response );
    }
    
    /**
     * Update customer's stripe card
     */
    public function ncs_update_stripe_card()
    {
        $response = array();
        $ncs_stripe = NCS_Stripe::instance();
        $_POST['post_id'] = intval( $_POST['post_id'] );
        $sub = new ScrtSubscription( $_POST['post_id'] );
        $sc_subscription_id = $sub->subscription_id;
        $sc_customer_id = $sub->customer_id;
        if ( $sc_subscription_id ) {
            
            if ( !empty($_POST['payment_method']) ) {
                $stripe = $ncs_stripe->stripe();
                
                if ( $sub->status == 'incomplete' || $sub->status == 'past_due' || $sub->status == 'pending-payment' ) {
                    $invoice = $stripe->subscriptions->retrieve( $sc_subscription_id, [
                        'expand' => [ 'latest_invoice' ],
                    ] );
                    $invoice = $invoice['latest_invoice'];
                    if ( $invoice->status == 'open' || $invoice->status == 'uncollectible' ) {
                        $stripe->invoices->pay( $invoice->id );
                    }
                }
                
                $response = $ncs_stripe->updatePaymentMethod( $sc_subscription_id, $_POST['payment_method'], $sc_customer_id );
                if ( $response->id || empty($response) ) {
                    $response = array(
                        'status'  => 'success',
                        'message' => 'New card has been saved.',
                    );
                }
            }
        
        }
        wp_send_json( $response );
    }
    
    /**
     * Display customer's default payment method on subscription view
     */
    public function show_stripe_payment_method( $order )
    {
        $instance = NCS_Stripe::instance();
        $instance->stripe();
        
        if ( !empty($order->customer_id) ) {
            $cards = $instance->getPaymentMethods( $order->customer_id );
            
            if ( !empty($cards) ) {
                $order->card = current( $cards['data'] );
                ncs_helper()->renderTemplate( 'my-account/card-details', $order );
            }
        
        }
    
    }

}