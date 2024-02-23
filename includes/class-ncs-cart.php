<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart
{
    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      NCS_Cart_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected  $loader ;
    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected  $plugin_name ;
    /**
     * The title of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_title    The string used to uniquely identify this plugin.
     */
    protected  $plugin_title ;
    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected  $version ;
    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        
        if ( defined( 'NCS_CART_VERSION' ) ) {
            $this->version = NCS_CART_VERSION;
        } else {
            $this->version = '1.0';
        }
        
        $this->prefix = 'sc_';
        // bug fix added v2.0.152
        
        if ( $key = get_option( 'sc_api_key' ) ) {
            update_option( '_sc_api_key', $key );
            delete_option( 'sc_api_key' );
        }
        
        if ( get_option( '_sc_decimal_number' ) === false ) {
            update_option( '_sc_decimal_number', 2 );
        }
        $this->plugin_name = 'ncs-cart';
        $this->plugin_title = 'Studiocart';
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        foreach ( glob( plugin_dir_path( __FILE__ ) . "/integrations/*.php" ) as $filename ) {
            // load all integrations
            include $filename;
            $classes = get_declared_classes();
            $class = end( $classes );
            $class_name = explode( '\\', $class );
            $class_var = strtolower( end( $class_name ) );
            ${$class_var} = new $class();
        }
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            $this->define_hooks__premium_only();
            // load all pro integrations
            foreach ( glob( plugin_dir_path( __FILE__ ) . "/integrations/pro/*.php" ) as $filename ) {
                include $filename;
                $classes = get_declared_classes();
                $class = end( $classes );
                $class_name = explode( '\\', $class );
                $class_var = strtolower( end( $class_name ) );
                ${$class_var} = new $class();
            }
        }
        
        do_action( 'sc_before_load' );
    }
    
    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - NCS_Cart_Loader. Orchestrates the hooks of the plugin.
     * - NCS_Cart_i18n. Defines internationalization functionality.
     * - NCS_Cart_Admin. Defines all hooks for the admin area.
     * - NCS_Cart_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        global  $studiocart ;
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-loader.php';
        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-i18n.php';
        /**
         * The class responsible for initializing the debug logger.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-debug-logger.php';
        global  $sc_debug_logger ;
        $sc_debug_logger = new NCS_Cart_Debug_Logger();
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-admin.php';
        /**
         * The class responsible for defining all actions that occur in the add admin product area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-add-stripe-product.php';
        /**
         * The class responsible for defining all actions that occur in the add admin setting tax area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-add-stripe-tax.php';
        /**
         * The class responsible for defining all admin product fields.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-metabox-fields.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-metaboxes.php';
        /**
         * The class responsible for defining all admin order fields.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-order-metaboxes.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-upsell-metaboxes.php';
        /**
         * File Downloads
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/files/class-ncs-cart-files.php';
        /**
         * Order Items
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/order-items/class-ncs-cart-order-items.php';
        /**
         * Custom Post Types and Taxonomies
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-post_types.php';
        /**
         * The class responsible for defining all actions that occur in the product add admin new order area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-add-order.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ncs-cart-public.php';
        $studiocart = new NCS_Cart_Public( $this->get_plugin_name(), $this->get_version(), $this->get_prefix() );
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-ncs-cart-paypal.php';
        /**
         * The class responsible for sanitizing user input
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-sanitize.php';
        /**
         * The class responsible for stripe services
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-stripe-service.php';
        /**
         * The class responsible for admin ajax
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-admin-ajax.php';
        /**
         * The class responsible for tax
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-tax.php';
        /**
         * Class for common helper functions
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/ncs-general-functions.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-ncs-order-helper.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/helpers/class-ncs-helper.php';
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            /**
             * The class responsible for defining white label actions.
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-white-label.php';
            /**
             * The class responsible for quantity field
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/quantity/class-ncs-cart-quantity.php';
            global  $sc_fs ;
            $license = $sc_fs->_get_license();
            
            if ( is_object( $license ) && $license->pricing_id != 11979 ) {
                /**
                 * The class responsible for shipping
                 */
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/shipping/class-ncs-cart-shipping.php';
                /**
                 * The class responsible for collections
                 */
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/collections/class-ncs-cart-collections.php';
                /**
                 * The class responsible for collections
                 */
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/subscription-sync/class-ncs-cart-sub-sync.php';
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-rest-customers.php';
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-rest-products.php';
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-rest-orders.php';
                require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-rest-subscriptions.php';
            }
            
            /*
             * Gutenberg product shortcode
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/vendor/gutenberg/index.php';
            /*
             * Divi custom module
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/vendor/divi/studiocart-order-form.php';
            /**
             * The class responsible for defining all API actions
             */
            require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-ncs-cart-api.php';
        }
        
        $this->loader = new NCS_Cart_Loader();
        $this->sanitizer = new NCS_Cart_Sanitize();
        $this->stripe_product = new NCS_Cart_Product_Admin();
    }
    
    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the NCS_Cart_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new NCS_Cart_i18n();
        $this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
    }
    
    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        global 
            $plugin_settings,
            $sc_product_fields,
            $studiocart,
            $scFiles
        ;
        $plugin_admin = new NCS_Cart_Admin( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $sc_product_fields = new NCS_Cart_Product_Metaboxes( $this->get_plugin_name(), $this->get_version(), $this->get_prefix() );
        $upsell_fields = new NCS_Cart_Upsell_Metaboxes( $this->get_plugin_name(), $this->get_version(), $this->get_prefix() );
        $order_fields = new NCS_Cart_Order_Metaboxes( $this->get_plugin_name(), $this->get_version(), $this->get_prefix() );
        $order_admin = new NCS_Cart_Order_Admin( $this->get_plugin_name(), $this->get_version(), $this->get_prefix() );
        $plugin_settings = new NCS_Cart_Admin_Settings( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $plugin_reports = new NCS_Cart_Admin_Reports( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $plugin_contacts_page = new NCS_Cart_Contacts_page( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $plugin_customer = new NCS_Cart_Customer_Reports( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $plugin_post_types = new NCS_Cart_Post_Types();
        $order_items = new NCS_Cart_Order_Items();
        $plugin_admin_ajax = new NCS_Cart_Admin_Ajax( $this->get_plugin_name(), $this->get_plugin_title(), $this->get_version() );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action(
            'init',
            $plugin_post_types,
            'create_custom_post_type',
            999
        );
        //admin Ajax
        $this->loader->add_action(
            'wp_ajax_ncs_ajax_action',
            $plugin_admin_ajax,
            'ncs_ajax_action',
            999
        );
        $this->loader->add_action(
            'wp_ajax_nopriv_ncs_ajax_action',
            $plugin_admin_ajax,
            'ncs_ajax_action',
            999
        );
        // Files
        
        if ( !sc_fs()->can_use_premium_code() ) {
            $scFiles = new NCS_Cart_Files();
            $this->loader->add_action(
                'admin_init',
                $scFiles,
                'init',
                99
            );
        }
        
        // Order Items
        $this->loader->add_action(
            'admin_init',
            $order_items,
            'init',
            99
        );
        // Product Metaboxes
        $this->loader->add_action(
            'admin_init',
            $sc_product_fields,
            'add_metaboxes',
            99
        );
        $this->loader->add_action(
            'save_post_sc_product',
            $sc_product_fields,
            'validate_meta',
            10,
            2
        );
        // Upsell Flow Metaboxes
        $this->loader->add_action(
            'admin_init',
            $upsell_fields,
            'add_metaboxes',
            99
        );
        $this->loader->add_action(
            'save_post_sc_us_path',
            $upsell_fields,
            'validate_meta',
            10,
            2
        );
        // Order Metaboxes
        $this->loader->add_action(
            'admin_init',
            $order_fields,
            'add_metaboxes',
            99
        );
        $this->loader->add_action(
            'save_post_sc_subscription',
            $order_fields,
            'validate_meta',
            10,
            2
        );
        //Add New Order Admin
        $this->loader->add_action(
            'save_post_sc_order',
            $order_admin,
            'save_post_sc_order',
            1,
            2
        );
        //Plugin Admin Settings
        $this->loader->add_action( 'admin_menu', $plugin_settings, 'setup_plugin_options_menu' );
        $this->loader->add_action( 'parent_file', $plugin_settings, 'taxonomy_menu_highlight' );
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_sections' );
        $this->loader->add_action( 'admin_init', $plugin_settings, 'register_fields' );
        $this->loader->add_action( 'admin_menu', $plugin_reports, 'setup_plugin_options_menu' );
        $this->loader->add_action( 'admin_menu', $plugin_customer, 'setup_plugin_options_menu' );
        $this->loader->add_action( 'admin_menu', $plugin_contacts_page, 'setup_plugin_options_menu' );
        // Register Importer
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_sc_importers' );
        // Order Custom Tax
        $this->loader->add_filter(
            'pre_update_option__sc_tax_rates',
            $plugin_settings,
            'validate_custom_tax',
            10,
            3
        );
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            //Plugin White Label Settings
            $plugin_white_label = new NCS_Cart_White_Label(
                $this->plugin_name,
                $this->plugin_title,
                $this->version,
                $studiocart
            );
            add_action( 'admin_menu', [ $plugin_white_label, 'setup_plugin_options_menu' ], 9999 );
            add_action( 'admin_init', [ $plugin_white_label, 'register_sections' ] );
            add_action( 'admin_init', [ $plugin_white_label, 'register_fields' ] );
            add_action( 'admin_menu', [ $plugin_white_label, 'setup_plugin_options_menu' ] );
        }
        
        //Plugin Admin Functionality
        //GDPR
        $this->loader->add_action(
            'admin_init',
            $plugin_admin,
            'privacy_declarations',
            10,
            2
        );
        $this->loader->add_action(
            'wp_privacy_personal_data_erasers',
            $plugin_admin,
            'register_erasers',
            10,
            2
        );
        $this->loader->add_action(
            'wp_privacy_personal_data_exporters',
            $plugin_admin,
            'register_exporter',
            10
        );
        $this->loader->add_action(
            'add_option__sc_stripe_live_sk',
            $plugin_admin,
            'create_stripe_webhook',
            10,
            2
        );
        $this->loader->add_action(
            'add_option__sc_stripe_test_sk',
            $plugin_admin,
            'create_stripe_webhook',
            10,
            2
        );
        $this->loader->add_action(
            'update_option__sc_stripe_live_sk',
            $plugin_admin,
            'update_stripe_webhook',
            10,
            3
        );
        $this->loader->add_action(
            'update_option__sc_stripe_test_sk',
            $plugin_admin,
            'update_stripe_webhook',
            10,
            3
        );
        $this->loader->add_action(
            'add_option__sc_mailchimp_api ',
            $plugin_admin,
            'get_mailchimp_groups',
            10
        );
        $this->loader->add_action(
            'add_option__sc_mailchimp_api',
            $plugin_admin,
            'get_mailchimp_tags',
            10
        );
        $this->loader->add_action(
            'add_option__sc_converkit_api',
            $plugin_admin,
            'get_convertkit_tags',
            10
        );
        $this->loader->add_action(
            'add_option__sc_converkit_api',
            $plugin_admin,
            'get_convertkit_forms',
            10
        );
        $this->loader->add_action(
            'add_option__sc_activecampaign_secret_key',
            $plugin_admin,
            'get_activecampaign_lists',
            10
        );
        $this->loader->add_action(
            'add_option__sc_activecampaign_secret_key',
            $plugin_admin,
            'get_activecampaign_tags',
            10
        );
        $this->loader->add_action(
            'update_option__sc_mailchimp_api ',
            $plugin_admin,
            'get_mailchimp_groups',
            10
        );
        $this->loader->add_action(
            'update_option__sc_mailchimp_api',
            $plugin_admin,
            'get_mailchimp_tags',
            10
        );
        $this->loader->add_action(
            'update_option__sc_converkit_api',
            $plugin_admin,
            'get_convertkit_tags',
            10
        );
        $this->loader->add_action(
            'update_option__sc_converkit_api',
            $plugin_admin,
            'get_convertkit_forms',
            10
        );
        $this->loader->add_action(
            'update_option__sc_activecampaign_secret_key',
            $plugin_admin,
            'get_activecampaign_lists',
            10
        );
        $this->loader->add_action(
            'update_option__sc_activecampaign_secret_key',
            $plugin_admin,
            'get_activecampaign_tags',
            10
        );
        $this->loader->add_action(
            'add_option__sc_sendfox_api_key',
            $plugin_admin,
            'get_sendfox_lists',
            10
        );
        $this->loader->add_action(
            'update_option__sc_sendfox_api_key',
            $plugin_admin,
            'get_sendfox_lists',
            10
        );
        $this->loader->add_action(
            'admin_init',
            $plugin_admin,
            'add_metaboxes',
            99
        );
        $this->loader->add_action( 'edit_form_after_editor', $plugin_admin, 'product_info_callback' );
        $this->loader->add_action( 'edit_form_advanced', $plugin_admin, 'product_form_callback' );
        $this->loader->add_action( 'edit_form_after_editor', $plugin_admin, 'subscription_info_callback' );
        $this->loader->add_action( 'edit_form_advanced', $plugin_admin, 'subscription_form_callback' );
        $this->loader->add_filter( 'manage_sc_product_posts_columns', $plugin_admin, 'set_custom_edit_sc_product_columns' );
        $this->loader->add_action(
            'manage_sc_product_posts_custom_column',
            $plugin_admin,
            'custom_sc_product_column',
            10,
            2
        );
        $this->loader->add_filter( 'manage_sc_order_posts_columns', $plugin_admin, 'set_custom_edit_sc_order_columns' );
        $this->loader->add_action(
            'manage_sc_order_posts_custom_column',
            $plugin_admin,
            'custom_sc_order_column',
            10,
            2
        );
        $this->loader->add_filter( 'manage_sc_subscription_posts_columns', $plugin_admin, 'set_custom_edit_sc_subscription_columns' );
        $this->loader->add_action(
            'manage_sc_subscription_posts_custom_column',
            $plugin_admin,
            'custom_sc_subscription_column',
            10,
            2
        );
        $this->loader->add_filter( 'manage_edit-sc_order_sortable_columns', $plugin_admin, 'sc_order_sortable_columns' );
        $this->loader->add_filter( 'manage_edit-sc_subscription_sortable_columns', $plugin_admin, 'sc_order_sortable_columns' );
        $this->loader->add_action( 'show_user_profile', $plugin_admin, 'show_user_profile_address_fields' );
        $this->loader->add_action( 'edit_user_profile', $plugin_admin, 'show_user_profile_address_fields' );
        $this->loader->add_action( 'personal_options_update', $plugin_admin, 'update_profile_address_fields' );
        $this->loader->add_action( 'edit_user_profile_update', $plugin_admin, 'update_profile_address_fields' );
        $this->loader->add_filter( 'bulk_actions-edit-sc_order', $plugin_admin, 'sc_order_bulk_action' );
        $this->loader->add_filter( 'bulk_actions-edit-sc_subscription', $plugin_admin, 'sc_subscription_bulk_action' );
        $this->loader->add_filter(
            'handle_bulk_actions-edit-sc_order',
            $plugin_admin,
            'sc_bulk_action_handler',
            10,
            3
        );
        $this->loader->add_filter(
            'handle_bulk_actions-edit-sc_subscription',
            $plugin_admin,
            'sc_bulk_action_handler',
            10,
            3
        );
        $this->loader->add_action( 'pre_get_posts', $plugin_admin, 'sc_order_sortable_columns_orderby' );
        $this->loader->add_action( 'load-edit.php', $plugin_admin, 'sc_load_edit_php_action' );
        $this->loader->add_action(
            'init',
            $plugin_admin,
            'sc_order_custom_status',
            999
        );
        $this->loader->add_filter( 'views_edit-sc_order', $plugin_admin, 'sc_order_remove_statuses' );
        //sc_modify_order_details MODIFY ORDER details post title
        $this->loader->add_action( 'wp_insert_post_data', $plugin_admin, 'sc_modify_order_details' );
        //AJAX REQUEST
        $this->loader->add_action( 'wp_ajax_sc_order_refund', $plugin_admin, 'sc_order_refund' );
        //refund
        $this->loader->add_action( 'wp_ajax_sc_product_plans', $plugin_admin, 'product_plan_options_html' );
        $this->loader->add_action( 'wp_ajax_sc_fresh_product', $plugin_admin, 'clean_product_meta_duplicate' );
        $this->loader->add_action( 'wp_ajax_sc_send_email_test', $plugin_admin, 'send_email_test' );
        $this->loader->add_action( 'wp_ajax_sc_mailchimp_groups_tags', $plugin_admin, 'sc_mailchimp_groups_tags' );
        $this->loader->add_action( 'wp_ajax_sc_unsubscribe_customer', $plugin_admin, 'sc_unsubscribe_customer' );
        //unsubscribe stripe
        $this->loader->add_action( 'wp_ajax_sc_get_payment_options', $plugin_admin, 'sc_get_payment_options' );
        //get payment options
        $this->loader->add_action( 'wp_ajax_sc_renew_integrations_lists', $plugin_admin, 'sc_renew_integrations_lists' );
        //Mailchimp
        $this->loader->add_action( 'wp_ajax_nopriv_nsc_renew_integrations_lists', $plugin_admin, 'sc_renew_integrations_lists' );
        //Mailchimp
    }
    
    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {
        global  $studiocart ;
        $plugin_public = $studiocart;
        $this->loader->add_filter( 'single_template', $plugin_public, 'sc_product_template' );
        $this->loader->add_filter( 'wp', $plugin_public, 'sc_email_preview_template' );
        $this->loader->add_filter( 'admin_init', $plugin_public, 'sc_email_preview_template' );
        $this->loader->add_filter( 'query_vars', $plugin_public, 'sc_query_vars' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'sc_redirect' );
        //$this->loader->add_filter( 'studiocart_post_purchase_url', $plugin_public, 'maybe_change_thank_you_page', 10, 3);
        $this->loader->add_filter(
            'studiocart_checkout_complete',
            $plugin_public,
            'conditional_order_confirmations',
            10,
            3
        );
        $this->loader->add_action(
            'wp_enqueue_scripts',
            $plugin_public,
            'enqueue_styles',
            10
        );
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
        $this->loader->add_action(
            'init',
            $plugin_public,
            'sc_process_payment',
            9999
        );
        $this->loader->add_filter(
            'the_title',
            $plugin_public,
            'public_product_name',
            10,
            2
        );
        $this->loader->add_filter(
            'wp_title',
            $plugin_public,
            'public_product_name',
            10,
            2
        );
        $this->loader->add_filter(
            'single_post_title',
            $plugin_public,
            'public_product_name',
            10,
            2
        );
        $this->loader->add_filter(
            'sc_order_summary_bump_text',
            $plugin_public,
            'custom_bump_summary_text',
            10,
            3
        );
        // stripe webhook
        $this->loader->add_filter( 'init', $plugin_public, 'sc_webhook_rewrite_rule' );
        $this->loader->add_filter( 'query_vars', $plugin_public, 'sc_api_query_vars' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'sc_stripe_webhook' );
        $this->loader->add_action( 'template_redirect', $plugin_public, 'sc_customer_csv_export' );
        $this->loader->add_action( 'wp_ajax_save_order_to_db', $plugin_public, 'save_order_to_db' );
        $this->loader->add_action( 'wp_ajax_nopriv_save_order_to_db', $plugin_public, 'save_order_to_db' );
        $this->loader->add_action( 'wp_ajax_update_stripe_order_status', $plugin_public, 'update_stripe_order_status' );
        $this->loader->add_action( 'wp_ajax_nopriv_update_stripe_order_status', $plugin_public, 'update_stripe_order_status' );
        $this->loader->add_action( 'wp_ajax_create_payment_intent', $plugin_public, 'create_payment_intent' );
        $this->loader->add_action( 'wp_ajax_nopriv_create_payment_intent', $plugin_public, 'create_payment_intent' );
        $this->loader->add_action( 'wp_ajax_create_subscription', $plugin_public, 'create_subscription' );
        $this->loader->add_action( 'wp_ajax_nopriv_create_subscription', $plugin_public, 'create_subscription' );
        $this->loader->add_action( 'wp_ajax_update_payment_intent_amt', $plugin_public, 'update_payment_intent_amt' );
        $this->loader->add_action( 'wp_ajax_nopriv_update_payment_intent_amt', $plugin_public, 'update_payment_intent_amt' );
        $this->loader->add_action( 'wp_ajax_sc_set_form_views', $plugin_public, 'sc_set_form_views' );
        $this->loader->add_action( 'wp_ajax_nopriv_sc_set_form_views', $plugin_public, 'sc_set_form_views' );
        $this->loader->add_action( 'wp_ajax_sc_check_vat_applicable', $plugin_public, 'sc_check_vat_applicable' );
        $this->loader->add_action( 'wp_ajax_nopriv_sc_check_vat_applicable', $plugin_public, 'sc_check_vat_applicable' );
        /** Update and display strpe card in my-account */
        $this->loader->add_action( 'wp_ajax_update_stripe_payment_method', $plugin_public, 'ncs_update_stripe_card' );
        $this->loader->add_action(
            'sc_show_stripe_payment_method',
            $plugin_public,
            'show_stripe_payment_method',
            10,
            1
        );
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            $this->loader->add_action( 'template_redirect', $plugin_public, 'sc_invoices_download__premium_only' );
            $this->loader->add_action( 'wp_ajax_sc_process_upsell', $plugin_public, 'sc_process_upsell__premium_only' );
            $this->loader->add_action( 'wp_ajax_nopriv_sc_process_upsell', $plugin_public, 'sc_process_upsell__premium_only' );
            $this->loader->add_action( 'wp_ajax_sc_validate_coupon', $plugin_public, 'sc_validate_coupon__premium_only' );
            $this->loader->add_action( 'wp_ajax_nopriv_sc_validate_coupon', $plugin_public, 'sc_validate_coupon__premium_only' );
            $this->loader->add_action( 'wp_ajax_sc_capture_lead', $plugin_public, 'sc_capture_lead__premium_only' );
            $this->loader->add_action( 'wp_ajax_nopriv_sc_capture_lead', $plugin_public, 'sc_capture_lead__premium_only' );
            $this->loader->add_action( 'wp_ajax_sc_check_username', $plugin_public, 'sc_check_username__premium_only' );
            $this->loader->add_action( 'wp_ajax_nopriv_sc_check_username', $plugin_public, 'sc_check_username__premium_only' );
        }
    
    }
    
    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }
    
    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }
    
    /**
     * The name of the plugin used to identify it in the frontend.
     *
     * @since     1.0.0
     * @return    string    The nice name of the plugin.
     */
    public function get_plugin_title()
    {
        return $this->plugin_title;
    }
    
    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    NCS_Cart_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }
    
    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
    
    /**
     * Retrieve the prefix  of the plugin.
     *
     * @since     1.0.0
     * @return    string    The prefix of the plugin.
     */
    public function get_prefix()
    {
        return $this->prefix;
    }

}