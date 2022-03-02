<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Order_Admin {
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The prefix of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $prefix    The current version of this plugin.
	 */
	private $prefix;
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version, $prefix ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->prefix = $prefix;

	}
	
	public function save_post_sc_order($post_id, $post){
		global $wpdb; 
        
        // leave if not on the post edit screen
		if ($post->post_type != 'sc_order' || !is_admin() || !isset($_POST['original_publish'])){
			return;
		}
        
        if ( wp_is_post_revision( $post_id ) || $post->post_status == 'auto-draft' ){
            return;
        }
        
        remove_action('save_post_sc_order',[$this,'save_post_sc_order'],1);
        
        $order = new ScrtOrder($post_id);
    
        $current_user = wp_get_current_user();
        $sc_status = sanitize_text_field( @$_POST['_sc_status'] );
        $status = ($sc_status == 'pending') ? 'pending-payment' : $sc_status; // "pending" order status removed for gateway charges
        
        // Check if this is a new post
        $log_entries = sc_order_log($post_id);
        
        $order->firstname = sanitize_text_field($_POST['_sc_firstname']);
        $order->lastname = sanitize_text_field($_POST['_sc_lastname']);
        $order->plan_id = sanitize_text_field($_POST['_sc_plan_id']);
        $order->email = sanitize_email($_POST['_sc_email']);
        $order->phone = sanitize_text_field($_POST['_sc_phone']);
        $order->product_id = intval($_POST['_sc_product_id']);
        $order->payment_status = $status;
        $order->status = $status;
        $order->store();
        
        if(empty($log_entries)) {
		                        
            $log_entry = sprintf(__('New order manually created by %s', 'ncs-cart'), $current_user->user_login) ;
            sc_log_entry($post_id, sanitize_text_field($log_entry));
            
        } else if (!empty($status) ) {
                                    
            $log_entry = sprintf(__('Order updated by %s', 'ncs-cart'), $current_user->user_login) ;
            sc_log_entry($post_id, $log_entry);
                        
        }
        add_action('save_post_sc_order',[$this,'save_post_sc_order'],1,2);
	}
	
}