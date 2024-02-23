<?php

/**
 * The file download specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.1
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/files
 */

class NCS_Cart_Order_Items {

    /**
	 * The order items table name.
	 *
	 * @since    2.6
	 * @access   private
	 * @var      string    $table_name    The order items table name.
	 */
	private static $table_name = 'ncs_order_items';

    /**
	 * The order items meta table name.
	 *
	 * @since    2.6
	 * @access   private
	 * @var      string    $table_name    The order items meta table name.
	 */
	private static $meta_table_name = 'ncs_order_itemmeta';
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
        
        $this->initialize();

	}

    public function init() {

    }

    public function initialize() {

		global $wpdb;
		
		$meta_table_name = self::$meta_table_name;

		$wpdb->$meta_table_name = $wpdb->prefix . $meta_table_name;
        add_action( 'studiocart_activate', array($this, 'setup_items_table') );
        add_action( 'studiocart_upgrade', array($this, 'setup_items_table') );

		require_once plugin_dir_path( __FILE__ ) . 'ScrtOrderItem.php';
				
    }
    
    public function setup_items_table(){
		global $wpdb;

   		$ncs_tax = $wpdb->prefix . self::$table_name; 
		$ncs_meta = $wpdb->prefix . self::$meta_table_name; 
		$charset_collate = $wpdb->get_charset_collate();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE IF NOT EXISTS $ncs_tax (
			order_item_id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
			product_id bigint(20) NOT NULL,
            price_id varchar(255) NOT NULL,
            item_type text,
            product_name text,
            price_name text,
			total_amount text,
			tax_amount text,
			PRIMARY KEY (order_item_id)
		  ) $charset_collate;";
		dbDelta( $sql );

		$sql = "CREATE TABLE IF NOT EXISTS $ncs_meta (
			meta_id	bigint(20) NOT NULL AUTO_INCREMENT,
			ncs_order_item_id bigint(20) NOT NULL,	
			meta_key varchar(255),	
			meta_value longtext,
			PRIMARY KEY (meta_id)
		  ) $charset_collate;";
		dbDelta( $sql );
	}
}