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
		global $wpdb, $sc_currency;
        
        // leave if not on the post edit screen
		if ($post->post_type != 'sc_order' || !is_admin() || !isset($_POST['original_publish'])){
			return;
		}
        
        if ( wp_is_post_revision( $post_id ) || $post->post_status == 'auto-draft' ){
            return;
        }
        
        remove_action('save_post_sc_order',[$this,'save_post_sc_order'],1);

        $current_user = wp_get_current_user();
        $sc_status = sanitize_text_field( @$_POST['_sc_status'] );
        $status = ($sc_status == 'pending') ? 'pending-payment' : $sc_status; // "pending" order status removed for gateway charges 
        
        // Check if this is a new post
        $log_entries = sc_order_log($post_id);
            
        if(empty($log_entries)){

            $_POST['first_name'] = sanitize_text_field($_POST['_sc_firstname']);
            $_POST['last_name'] = sanitize_text_field($_POST['_sc_lastname']);
            $_POST['email'] = sanitize_email($_POST['_sc_email']);
            $_POST['vat-number'] = sanitize_text_field($_POST['_sc_vat_number']);

            $fields = array('phone','country','address1','address2','city','state','zip','company');
            foreach($fields as $field) {
                if(isset($_POST['_sc_'.$field]) && $_POST['_sc_'.$field]) {
                    $_POST[$field] = sanitize_text_field($_POST['_sc_'.$field]);
                }
            }

            $_POST['sc_product_id'] = intval($_POST['_sc_product_id']);
            $_POST['sc_product_option'] = sanitize_text_field($_POST['_sc_item_name']);
            
            // flag as on sale if plan id ends with "_sale"
            if(substr($_POST['sc_product_option'], -(strlen('_sale'))) === '_sale') {
                $_POST['sc_product_option'] = substr($_POST['sc_product_option'], 0, -strlen('_sale'));
                $_POST['on-sale'] = 1;
            }            

            $order = new ScrtOrder();
            $order->load_from_post();
            $order->set_invoice_number();

            $order = apply_filters('sc_after_order_load_from_post', $order);
            $order->id = $post_id;
             
        } else {
        
            $order = new ScrtOrder($post_id);

            $order->first_name = $order->firstname = sanitize_text_field($_POST['_sc_firstname']);
            $order->last_name = $order->lastname = sanitize_text_field($_POST['_sc_lastname']);
            $order->email = sanitize_email($_POST['_sc_email']);
            $order->vat_number = sanitize_text_field($_POST['_sc_vat_number']);

            $fields = array('phone','country','address1','address2','city','state','zip','company');
            foreach($fields as $field) {
                if(isset($_POST['_sc_'.$field]) && $_POST['_sc_'.$field]) {
                    $order->$field = sanitize_text_field($_POST['_sc_'.$field]);
                } else {
                    $order->$field = null;
                }
            }

            //$order->product_id = intval($_POST['_sc_product_id']);
            //$order->product_name = sc_get_public_product_name($order->product_id);
        }

        $order->payment_status = $status;
        $order->status = $status;
        $order->user_account = 0;
        
        if(isset($_POST['_sc_user_account']) && $_POST['_sc_user_account']){
            $order->user_account = intval($_POST['_sc_user_account']);
        }
                
        $order->store();
        
        if(empty($log_entries)) {
		     /* translators: %1$s is replaced with "string" */                   
            $log_entry = sprintf(__('New order manually created by %s', 'ncs-cart'), $current_user->user_login) ;
            sc_log_entry($post_id, sanitize_text_field($log_entry));
            
        } else if (!empty($status) ) {
                                    
            $log_entry = sprintf(__('Order updated by %s', 'ncs-cart'), $current_user->user_login) ;
            sc_log_entry($post_id, $log_entry);
                        
        }
        add_action('save_post_sc_order',[$this,'save_post_sc_order'],1,2);
	}
	
}