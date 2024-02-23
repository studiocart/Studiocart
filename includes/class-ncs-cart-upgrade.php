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
        
        do_action('studiocart_upgrade');
        
        flush_rewrite_rules();
        self::setup_tax_table();
		self::add_cap();
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

	public static function compile_post_type_capabilities($singular = 'post', $plural = 'posts') {
        //var_dump($singular);

        return [
            'edit_post'		 => "edit_$singular",
            'read_post'		 => "read_$singular",
            'delete_post'		 => "delete_$singular",
            'edit_posts'		 => "edit_$plural",
            'edit_others_posts'	 => "edit_others_$plural",
            'publish_posts'		 => "publish_$plural",
            'read_private_posts'	 => "read_private_$plural",
            'read'                   => "read",
            'delete_posts'           => "delete_$plural",
            'delete_private_posts'   => "delete_private_$plural",
            'delete_published_posts' => "delete_published_$plural",
            'delete_others_posts'    => "delete_others_$plural",
            'edit_private_posts'     => "edit_private_$plural",
            'edit_published_posts'   => "edit_published_$plural",
            'create_posts'           => "edit_$plural",
        ];
    }

	/**
     * Adding new capability in the plugin
     */
    public static function add_cap()
    {
        $editor_role = get_role('editor');
        $sc_manager_role = add_role( 'sc_cart_manager', 'Cart Manager', $editor_role->capabilities );
        if(!$sc_manager_role){
            $sc_manager_role = get_role('sc_cart_manager');
        }
        // Get administrator role
        $role = get_role('administrator');
        
        $post_type = array('sc_product'=>'sc_products',
                        'sc_order'=>'sc_orders',
                        'sc_subscription'=>'sc_subscriptions',
                        'sc_us_path'=>'sc_us_paths'
                    );
        foreach($post_type as $key => $post_data){
            $sc_product_capabilities = self::compile_post_type_capabilities($key, $post_data);
            foreach($sc_product_capabilities as $cap){
                $role->add_cap($cap);
                $sc_manager_role->add_cap($cap);
            }
        }
        $role->add_cap('sc_manager_option');
        $sc_manager_role->add_cap('sc_manager_option');
        add_role( 'sc_cart_administrator', 'Cart Administrator', $role->capabilities );
    }
}
