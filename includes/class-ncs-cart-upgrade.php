<?php

/**
 * Fired during plugin upgrade
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
 * This class defines all code necessary to run during the plugin's update.
 *
 * @since      1.0.0
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Upgrade {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function upgrade() {
        
        self::setup_tax_table();

	}

	public static function setup_tax_table(){
		global $wpdb;

   		$ncs_tax = $wpdb->prefix . "ncs_tax_rate"; 
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $ncs_tax (
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
