<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://ncreatives.com
 * @since             0.0.1
 * @package           NCS_Cart
 *
 * @wordpress-plugin
 * Plugin Name: Studiocart (Premium)
 * Plugin URI:        https://studiocart.co
 * Description:       Stunning order pages and simplified sales flow creation that helps you sell digital products, programs, and services from your WordPress site.
 * Version:           2.3.1
 * Author:            N.Creatives
 * Author URI:        https://ncreatives.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ncs-cart
 * Domain Path:       /languages
 *
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
// Freemius integration

if ( function_exists( 'sc_fs' ) ) {
    sc_fs()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'sc_fs' ) ) {
        // Create a helper function for easy SDK access.
        function sc_fs()
        {
            global  $sc_fs ;
            
            if ( !isset( $sc_fs ) ) {
                // Activate multisite network integration.
                if ( !defined( 'WP_FS__PRODUCT_5777_MULTISITE' ) ) {
                    define( 'WP_FS__PRODUCT_5777_MULTISITE', true );
                }
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $sc_fs = fs_dynamic_init( array(
                    'id'              => '5777',
                    'slug'            => 'studiocart',
                    'premium_slug'    => 'studiocart-pro',
                    'type'            => 'plugin',
                    'public_key'      => 'pk_4d4328dbc87d3edaab1fe4158a14a',
                    'is_premium'      => false,
                    'premium_suffix'  => '(Pro)',
                    'has_addons'      => false,
                    'has_paid_plans'  => true,
                    'trial'           => array(
                    'days'               => 30,
                    'is_require_payment' => true,
                ),
                    'has_affiliation' => 'selected',
                    'menu'            => array(
                    'slug'    => 'studiocart',
                    'support' => false,
                ),
                    'is_live'         => true,
                ) );
            }
            
            return $sc_fs;
        }
        
        // Init Freemius.
        sc_fs();
        // Signal that SDK was initiated.
        do_action( 'sc_fs_loaded' );
    }
    
    /**
     * Currently plugin version.
     * Start at version 1.0.0 and use SemVer - https://semver.org
     * Rename this for your plugin and update it as you release new versions.
     */
    define( 'NCS_CART_VERSION', '2.3.1' );
    define( 'NCS_CART_BASE_DIR', plugin_dir_path( __FILE__ ) );
    /**
     * The code that runs during plugin activation.
     * This action is documented in includes/class-ncs-cart-activator.php
     */
    function activate_ncs_cart()
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-ncs-cart-activator.php';
        NCS_Cart_Activator::activate();
    }
    
    /**
     * The code that runs during plugin deactivation.
     * This action is documented in includes/class-ncs-cart-deactivator.php
     */
    function deactivate_ncs_cart()
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-ncs-cart-deactivator.php';
        NCS_Cart_Deactivator::deactivate();
    }
    
    /**
     * The code that runs during plugin upgrade.
     * This action is documented in includes/class-ncs-cart-upgrade.php
     */
    function upgrade_ncs_cart( $upgrader_object, $options )
    {
        $current_plugin_path_name = plugin_basename( __FILE__ );
        if ( $options['action'] == 'update' && $options['type'] == 'plugin' ) {
            foreach ( $options['plugins'] as $each_plugin ) {
                
                if ( $each_plugin == $current_plugin_path_name ) {
                    require_once plugin_dir_path( __FILE__ ) . 'includes/class-ncs-cart-upgrade.php';
                    NCS_Cart_Upgrade::upgrade();
                }
            
            }
        }
    }
    
    register_activation_hook( __FILE__, 'activate_ncs_cart' );
    register_deactivation_hook( __FILE__, 'deactivate_ncs_cart' );
    add_action(
        'upgrader_process_complete',
        'upgrade_ncs_cart',
        10,
        2
    );
    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-ncs-cart.php';
    require_once plugin_dir_path( __FILE__ ) . 'models/ScrtOrder.php';
    require_once plugin_dir_path( __FILE__ ) . 'models/ScrtSubscription.php';
    /**
     * Include helper functions
     */
    require_once plugin_dir_path( __FILE__ ) . 'includes/functions.php';
    add_action( 'after_setup_theme', 'crb_load' );
    function crb_load()
    {
        require_once plugin_dir_path( __FILE__ ) . 'includes/vendor/autoload.php';
    }
    
    /**
     * Begins execution of the plugin.
     *
     * Since everything within the plugin is registered via hooks,
     * then kicking off the plugin from this point in the file does
     * not affect the page life cycle.
     *
     * @since    1.0.0
     */
    function run_ncs_cart()
    {
        $plugin = new NCS_Cart();
        $plugin->run();
    }
    
    run_ncs_cart();
}
