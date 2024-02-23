<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      2.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 */

/**
 * The Customer-report-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the Customer-specific stylesheet and JavaScript.
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Contacts_page {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	
	/**
	 * The Nice Name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_title    The Nice Name of this plugin.
	 */
	private $plugin_title;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_title, $version ) {

		$this->plugin_name = $plugin_name;
		$this->plugin_title = $plugin_title;
		$this->version = $version;

	}

	/**
	 * This function introduces the plugin options into a top-level
	 * 'CreativCart' menu.
	 */
	public function setup_plugin_options_menu() {       
		
         add_submenu_page(
            'studiocart',
            apply_filters( $this->plugin_name . '-settings-page-title', esc_html__( 'Contacts', 'ncs-cart' ) ),
            apply_filters( $this->plugin_name . '-settings-menu-title', esc_html__( 'Contacts', 'ncs-cart' ) ),
            'sc_manager_option',
            $this->plugin_name . '-contacts-page',
            array( $this, 'render_page_contacts' )
        );

	}
	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	public function render_page_contacts( $active_tab = '' ) {
        global $sc_currency_symbol; global $wpdb;
        global $current_user; ?>
        <div class="wrap">
            <h1><?php esc_html_e( sprintf('%s Contacts', apply_filters('studiocart_plugin_title', $this->plugin_title)), 'ncs-cart' ); ?></h1>
            <div class="sc-reports">                
                 <a href="<?php echo home_url(); ?>/?sc-csv-export=contacts&type=contact"><button type="button" id="customer_csv_export__" class="button button-primary customer_csv_export pull-right"><?php esc_html_e('Export Contacts', 'ncs-cart'); ?></button></a><br /><br />
               
                <table id="contacts_table" cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed striped table-view-list posts" width="100%">
                    <thead>
                        <tr>
                            <th class="item" style="display: none;"><?php esc_html_e( 'Date', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Email', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Name', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Orders', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'LTV', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Last Order Date', 'ncs-cart' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="the-list">
                    <?php  
                    $get_user = $wpdb->get_results("SELECT {$wpdb->prefix}posts.ID,{$wpdb->prefix}postmeta.meta_value FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key = '_sc_email' ) AND {$wpdb->prefix}posts.post_type = 'sc_order' AND (({$wpdb->prefix}posts.post_status <> 'trash' AND {$wpdb->prefix}posts.post_status <> 'auto-draft')) ORDER BY `{$wpdb->prefix}posts`.`post_date` DESC");
                    $user_contact_array = array();
                    foreach ($get_user as $key => $post) {
                        $user_email = strtolower($post->meta_value); 
                            $status = (in_array(get_post_status( $post->ID ),['pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID );
                            $refundedarray = array();
                            if (get_post_meta( $post->ID, '_sc_payment_status', true ) == 'refunded') {
                                $refund_logs_entrie = get_post_meta( $post->ID, '_sc_refund_log', true);
                                $total_amount = get_post_meta( $post->ID, '_sc_amount', true );
                                if(is_array($refund_logs_entrie)) {
                                    $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                                    $total_amount = floatval(get_post_meta( $post->ID , '_sc_amount', true )) - $refund_amount;  
                                    $total_amount = $total_amount;
                                    $refundedarray[]= $refund_amount;                        
                                } 
                            }else {                               
                                if($status == 'paid'){
                                    $total_amount=  get_post_meta( $post->ID, '_sc_amount', true);
                                }
                            } 

                            if($status == 'paid'){
                                 $user_contact_array[$user_email][] = array('id' => $post->ID, 'total_amount' => $total_amount, );
                            }else{
                                 $user_contact_array[$user_email][] = array('id' => $post->ID, 'total_amount' => 0, );  
                            }                          
                       
                        }
                      
                     
                        foreach ($user_contact_array as $key => $value) {
                            $post_id = $value[0]['id'];
                            $paid_amount = array_sum(array_column($value , 'total_amount'));
                            $num_of_record = count($value);
                           
                         ?>
                            <tr>
                                <td valign="top" style="display: none;"><?php echo get_the_time('Y-m-d h:i:s',$post_id ); ?></td>
                                <td valign="top"><a href="<?php echo get_admin_url(); ?>admin.php?page=ncs-cart-customer-reports&reportstypes=order&customerid=<?php echo $key; ?>"><?php echo $key; ?></a></td>
                                <td valign="top"><?php echo get_post_meta($post_id, '_sc_firstname', true).' '.get_post_meta($post_id, '_sc_lastname', true); ?></td>
                                <td valign="top"><a href="<?php echo get_admin_url(); ?>edit.php?post_type=sc_order&order_email=<?php echo $key; ?>"><?php echo $num_of_record; ?></a></td>                               
                                <td valign="top"><?php echo $sc_currency_symbol.''.number_format($paid_amount, 2); ?> <?php //echo $paid_amount; ?></td>
                                <td valign="top"><?php echo get_the_time( 'M j, Y', $post_id ); ?></td>
                            </tr>
                       <?php  } ?>                      
                    </tbody>
                </table>
            </div>
        </div> 
        <script type="text/javascript">
            jQuery(document).ready( function () {
                jQuery('#contacts_table').DataTable( {
                    "pageLength": 25,
                    "order": [[ 0, "desc" ]]
                });
            } );
        </script>      
    <?php    
	}
}