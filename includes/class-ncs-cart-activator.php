<?php

/**
 * Fired during plugin activation
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-ncs-cart-post_types.php';
        $sc_post_types = new NCS_Cart_Post_Types();
        
        $sc_post_types->create_custom_post_type();
    
        $apikey = hash( 'md5', wp_create_nonce( 'sc-cart') . date( 'U' ) );
        update_option( '_sc_api_key', $apikey );
        self::setup_tax_table();
        flush_rewrite_rules();

	}

	public static function setup_tax_table(){
		global $wpdb;

   		$ncs_tax = $wpdb->prefix . "ncs_tax_rate"; 
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE $ncs_tax (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			tax_rate_country tinytext NOT NULL,
			tax_rate_state tinytext NOT NULL,
			tax_rate_postcode tinytext NOT NULL,
			tax_rate_city tinytext NOT NULL,
			tax_rate tinytext NOT NULL,
			tax_rate_title tinytext NULL,
			tax_rate_priority mediumint(9) NULL,
			tax_rate_meta longtext NULL,
			PRIMARY KEY (id)
		  ) $charset_collate;";
		  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
