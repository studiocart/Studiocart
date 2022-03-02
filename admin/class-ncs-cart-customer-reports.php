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
class NCS_Cart_Customer_Reports {

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
        
        add_action( 'admin_head', function() {
            remove_submenu_page( 'studiocart', $this->plugin_name . '-customer-reports' );
        });

	}

	/**
	 * This function introduces the plugin options into a top-level
	 * 'CreativCart' menu.
	 */
	public function setup_plugin_options_menu() {	
        
		add_submenu_page(
			'studiocart',
			'',
			'',
			'manage_options',
			$this->plugin_name . '-customer-reports',
			array( $this, 'render_reports_page_content' )
		);

	}
	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	public function render_reports_page_content( $active_tab = '' ) {
        
        if(!isset($_REQUEST['customerid'])){ 
          return;
        } 
        
        global $wpdb;
        global $current_user;     
        if(isset($_REQUEST['date'])){
           $date =  esc_html($_REQUEST['date']);
           $dates = explode(" to ",$date);
           $fromdate = $dates[0]." 00:00:00";
           $todate = $dates[1]." 23:59:59";
        }else{
            $todate = date("Y-m-d")." 23:59:59";;
            $fromdate = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" ) )." 00:00:00";;
            $date = date("Y-m-d", strtotime( date( "Y-m-d", strtotime( date("Y-m-d") ) ) . "-1 month" ) ).' to '.date("Y-m-d");  
        }      
        $customer = esc_html($_REQUEST['customerid']);
                
     ?>
    <div class="wrap">
        <h1><?php echo apply_filters('studiocart_plugin_title',$this->plugin_title); ?></h1>
        <div class="sc-reports">
            <div class="sc-reports-header">
                <?php echo get_avatar($customer, 64); ?>
                <div>
                    <h3 id="sc-customer-name">
                    <?php 
                    $dynamicname = false;    
                    if(isset($_REQUEST['customername'])){ 
                      echo esc_html($_REQUEST['customername']);
                    } else if($user = get_user_by( 'email', $customer )) {
                        echo esc_html($user->first_name . ' ' . $user->last_name);
                    } else {
                        $dynamicname = true;
                    }?>
                    </h3>
                    <?php echo $customer; ?>
                </div>
            </div>
                    
            <script type="text/javascript">
                jQuery(document).ready(function($){
                    var configObject = {
                                        format: 'YYYY-MM-DD',
                                        showShortcuts: true,
                                        shortcuts :
                                        {
                                            'prev-days': [1,3,7,30],
                                            'prev': ['week','month','year']
                                        }
                                    }
                    var dateRange = $('#reports-range').dateRangePicker(configObject);                
                   
                })
            </script>
            <div id="tabs" class="form-group">
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo home_url(); ?>/wp-admin/admin.php?page=ncs-cart-customer-reports&reportstypes=order&customerid=<?php echo $customer; ?>" class="nav-tab <?php if($_REQUEST['reportstypes'] == 'order'){ ?> nav-tab-active <?php } ?>" >Orders</a>
                    <a href="<?php echo home_url(); ?>/wp-admin/admin.php?page=ncs-cart-customer-reports&reportstypes=subscription&customerid=<?php echo $customer; ?>" class="nav-tab <?php if($_REQUEST['reportstypes'] == 'subscription'){ ?> nav-tab-active <?php } ?>" ><?php esc_html_e('Subscriptions', 'ncs-cart'); ?></a>
                </nav> 
                <?php if($_REQUEST['reportstypes'] == 'order'){ ?>  
                    
                    <div class="sc_order_info">
                        <table id="customer-stats">
                            <tr>
                                <td>
                                    <table cellspacing="20">
                                        <tr>
                                            <td><?php esc_html_e('Net Revenue', 'ncs-cart'); ?><br>
                                            <span id="total_price_report"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                                <td>
                                    <table cellspacing="20" style="margin-left: 20px; background: transparent">
                                        <tr>
                                            <td><?php esc_html_e('Purchases', 'ncs-cart'); ?><br>
                                            <span id="paid_price_report"></span>
                                            </td>
                                            <td><?php esc_html_e('Refunds', 'ncs-cart'); ?><br>
                                            <span id="refunded_price_report"></span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="sc-reports-header">  
                        <h2><?php esc_html_e('Customer Report', 'ncs-cart'); ?></h2> 
                        
                        
                        <form class="pull-right" style="margin: 0 10px 0 0;">                
                            <input type="hidden" name="page" value="ncs-cart-customer-reports" />
                            <input type="hidden" id="customer_emailid" name="customerid" value="<?php echo $customer; ?>" />
                            <input type="hidden" name="reportstypes" value="order" />
                            <input type="text" name="date" id="reports-range" value="<?php echo $date; ?>"   />
                            <input type="submit" value="<?php esc_html_e('Apply', 'ncs-cart'); ?>" class="button button-primary" /> 
                        </form>
                                                
                        <a id="customer_csv_export__" class="ml15 button customer_csv_export" href="<?php echo home_url(); ?>/?sc-csv-export=customer&type=order&emailid=<?php echo $customer; ?>&daterange=<?php echo $date; ?>"><?php esc_html_e('Export Orders', 'ncs-cart'); ?></a>
                    </div>          
                    <table id="sc-customer-table" cellpadding="0" cellspacing="0" width="100%">
                        <tbody id="order_line_items">
                        <?php
                        $args = array(          
                                'post_type' => 'sc_order',
                                'post_status' => 'any',
                                'order' => 'ASC',
                                'date_query' => array(
                                    array(
                                        'post_date' => 'post_date',
                                        'after' => $fromdate,
                                    ),
                                    array(
                                        'post_date' => 'post_date',
                                        'before'  => $todate,
                                    ),
                                ),
                                'posts_per_page' => -1,
                                'meta_query' => array(
                                    array(
                                        'key' => '_sc_email',
                                        'value' => $customer,
                                    ),
                                )
                            );
                        $current_date = '';
                        $results = new WP_Query($args); 
                        if( $results->have_posts() ){
                            $paidarray = array();
                            $refundedarray = array();
                            while( $results->have_posts() ) {
                                $results->the_post();  global $post;
                                
                                $order = new ScrtOrder($post->ID);
                                $order = (object) $order->get_data();
                                
                                if($dynamicname === true) {
                                    $dynamicname = $order->firstname . ' ' . $order->lastname;
                                }
                                
                                $amount = $order->amount;
                                $status = (in_array(get_post_status( $post->ID ),['pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID );
                                $total_amount = number_format( $order->amount, 2);
                                if ($order->payment_status == 'refunded') {
                                    if(isset($order->refund_log)) {
                                        $refund_logs_entrie = $order->refund_log;
                                        $total_amount = $order->amount;
                                        if(is_array($refund_logs_entrie)) {
                                           $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                                           $total_amount = $order->amount - $refund_amount;  
                                           $total_amount = number_format($total_amount , 2);
                                           $refundedarray[]= $refund_amount;                        
                                        } 
                                    }
                                } 
                                if($status == 'paid'){
                                   $paidarray[] = $total_amount;
                                } 
                                if($status == 'pending'){
                                    continue;
                                    $total_amount = $order->main_offer_amt;
                                } 
                            
                                $post_date = get_the_time( 'M j, Y', $post->ID );
                                if($current_date != $post_date) {
                                    if ($current_date != '') {
                                        echo '<tr><td class="end-order-date"></td></tr>';
                                    }
                                    echo '<tr class="order-date"><td align="center"><h3>'.$post_date.'</h3><div class="date-divider"></div></td></tr>';
                                    $current_date = $post_date;
                                }
                                $icon_class = 'dashicons-saved'
                                ?>
                                    <tr>
                                        <td valign="top">
                                            
                                            <h4><a href="<?php echo get_edit_post_link( $post->ID ); ?>&action=edit" >
                                            <?php if (isset($order->product_id)) { echo get_the_title($order->product_id); } ?></a>
                                            <span class="price">
                                                <?php if ($order->payment_status == 'refunded') { ?>
                                                        <s><?php sc_formatted_price($order->amount); ?></s> <?php sc_formatted_price($total_amount); ?>
                                                        <?php $icon_class = 'dashicons-image-rotate'; ?>
                                                <?php } else { ?>
                                                         <?php sc_formatted_price($total_amount); ?>
                                                <?php } ?>    
                                            </span>    
                                            </h4>
                                                    
                                            <div class="order-meta">
                                                <?php if(isset($order->us_parent)){  ?>
                                                <span class="order-plan upsell"><?php esc_html_e('Upsell', 'ncs-cart'); ?></span>
                                                <?php } elseif(isset($order->ds_parent)){ ?>
                                                 <span class="order-plan upsell-2"><?php esc_html_e('2nd upsell', 'ncs-cart'); ?></span>
                                                <?php } else { ?>
                                                 <span class="order-plan"><?php if (isset($order->item_name)) { echo $order->item_name; } ?></span> 
                                                <?php  } ?>
                                                
                                                <span class="divider">&middot;</span>
                                                
                                                <?php echo ucwords($status); ?>
                                                
                                                <span class="divider">&middot;</span>
                                                
                                                <?php /*<td><?php echo get_post_meta( $post->ID , '_sc_coupon', true ); ?></td>*/ ?>
                                                <a href="<?php echo get_edit_post_link( $post->ID ); ?>"><?php esc_html_e('View Order', 'ncs-cart'); ?></a>
                                            </div>
                                            
                                            <span class="dashicons <?php echo $icon_class; ?>"></span>
                                        </td>
                                    </tr>
                            
                                    <?php if (isset($order->bump_id)): ?>
                                        <tr>
                                            <td valign="top">
                                                <h4><a href="<?php echo admin_url( 'post.php?post='.$post->ID );?>&action=edit" ><?php echo  get_the_title($order->bump_id); ?>
                                                </a>
                                                <span class="price"><?php sc_formatted_price($order->bump_amt); ?></span>
                                                </h4>

                                                <div class="order-meta">
                                                    <span class="order-plan bump"><?php esc_html_e('Order Bump', 'ncs-cart'); ?></span>

                                                    <span class="divider">&middot;</span>

                                                    <?php echo ucwords($status); ?>

                                                    <span class="divider">&middot;</span>

                                                    <?php /*<td><?php echo get_post_meta( $post->ID , '_sc_coupon', true ); ?></td>*/ ?>
                                                    <a href="<?php echo get_edit_post_link( $order->product_id ); ?>"><?php esc_html_e('View Order', 'ncs-cart'); ?></a>
                                                </div>
                                                <span class="dashicons <?php echo $icon_class; ?>"></span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                        <?php
                            } }
                        $paid_amount = array_sum($paidarray);
                        $refunded_amount = array_sum($refundedarray);
                        $total_amount = $paid_amount - $refunded_amount;?>
                                                               
                       </tbody>
                    </table>
                    <script type="text/javascript">
                        jQuery(document).ready(function($){
                            jQuery("#paid_price_report").html('<?php sc_formatted_price($paid_amount); ?>');
                            jQuery("#refunded_price_report").html('-<?php sc_formatted_price($refunded_amount); ?>');
                            jQuery("#total_price_report").html('<?php sc_formatted_price($total_amount); ?>');   
                            
                            <?php if($dynamicname): ?>
                                jQuery("#sc-customer-name").html('<?php echo esc_html($dynamicname); ?>');
                            <?php endif; ?>
                         });
                    </script>
                    <br />
                <?php } ?>
                <?php if($_REQUEST['reportstypes'] == 'subscription'){ ?> 
                    <div class="sc-reports-header"> 
                        <h2><?php esc_html_e('Subscriptions', 'ncs-cart'); ?></h2>
                        <form class="pull-right" style="margin: 0 10px 0 0">                
                            <input type="hidden" name="page" value="ncs-cart-customer-reports" />
                            <input type="hidden" id="customer_emailid" name="customerid" value="<?php echo $customer; ?>" />
                            <input type="hidden" name="reportstypes" value="subscription" />
                            <input type="text" name="date" id="reports-range" value="<?php echo $date; ?>"   />
                            <input type="submit" value="<?php esc_html_e('Apply', 'ncs-cart'); ?>" class="button button-primary" /> 
                        </form>
                        <a class="ml15 button" href="<?php echo home_url(); ?>/?sc-csv-export=customer&type=subscription&emailid=<?php echo $customer; ?>&daterange=<?php echo $date; ?>"><?php esc_html_e('CSV Export', 'ncs-cart'); ?></a>
                    </div>            
                    <table cellpadding="0" cellspacing="0" class="sc_order_items sc_order_items_new" width="100%">
                        <thead>
                            <tr> 
                                <th class="item" width="10%"><?php esc_html_e( 'Date', 'ncs-cart' ); ?></th>
                                <th class="item"><?php esc_html_e( 'Product', 'ncs-cart' ); ?></th>
                                <th class="item"><?php esc_html_e( 'Plan', 'ncs-cart' ); ?></th>  
                                <th class="line_cost" width="12%"><?php esc_html_e( 'Pay Interval', 'ncs-cart' ); ?></th>                          
                                <th class="item"><?php esc_html_e( 'Status', 'ncs-cart' ); ?></th>                        
                                <th class="line_cost"><?php esc_html_e( 'Total Revenue', 'ncs-cart' ); ?></th>
                                <th class="line_cost"><?php esc_html_e( 'Remaining Payments', 'ncs-cart' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="order_line_items">
                            <?php 
                            $subscription_args = array(                      
                                    'post_type' => 'sc_subscription',
                                    'post_status' => 'any',
                                    'date_query' => array(
                                        array(
                                            'post_date' => 'post_date',
                                            'after' => $fromdate,
                                        ),
                                        array(
                                            'post_date' => 'post_date',
                                            'before'  => $todate,
                                        ),
                                    ),
                                    'posts_per_page' => -1,
                                    'meta_query' => array(
                                        array(
                                            'key' => '_sc_email',
                                            'value' => $customer,
                                        ),
                                    )
                                );
                            $subscription_results = new WP_Query($subscription_args);                
                            if( $subscription_results->have_posts() ){                  
                                while( $subscription_results->have_posts() ) {
                                    $subscription_results->the_post();  global $post;
                                    $amount = get_post_meta( $post->ID , '_sc_amount', true );

                                    $status = (in_array(get_post_status( $post->ID ),['pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID ); 

                                    if($status != 'pending') {
                                        $total_amount = 0;
                                        $logs = get_post_meta( $post->ID, '_sc_order_log', true);
                                        $installments = get_post_meta( $post->ID, '_sc_sub_installments', true) ?? -1; 
                                        //$interval = get_post_meta( $post->ID, '_sc_sub_interval', true); 
                                       // $installments--;  
                                        $interval = get_post_meta( $post->ID , '_sc_sub_interval', true );
                                        if ($installments > 1) {
                                            $dateTime = DateTime::createFromFormat('Y-m-d', get_the_time( 'Y-m-d', $post->ID ));
                                            $dateTime->add(DateInterval::createFromDateString($installments . ' ' . $interval.'s'));
                                            $expiresdate = $dateTime->format('Y-m-d'); 
                                        }
                                        if($status == 'canceled') {
                                            $date = get_post_meta( $post->ID, '_sc_subscription_canceled_date', true); 
                                        }else{
                                            $date = date( 'Y-m-d');  
                                        }
                                        
                                        if(get_post_meta( $post->ID, '_sc_sub_installments', true) == '-1'){
                                            $expiresdate =  get_the_time( 'Y-m-d', $post->ID );
                                            $diff = strtotime($expiresdate) - strtotime($date); 
                                         }else{
                                             $diff = strtotime($date) - strtotime($expiresdate); 
                                         } 
                                        
                                        $installments = get_post_meta( $post->ID, '_sc_sub_installments', true); 
                                        $payments_remaining = '&infin;';
                                        
                                        if($installments > 0) {
                                            
                                            $payments_remaining = $installments;
                                                                                        
                                            global $post;
                                            $backup = $post;
                                            
                                            // The Query
                                            $args = array(
                                                'post_type' => array( 'sc_order' ),
                                                'orderby' => 'date',
                                                'order'   => 'ASC',
                                                'post_status' => 'paid',
                                                'meta_query'=>array(
                                                    array(
                                                        'key' => '_sc_subscription_id',
                                                        'value' => $post->ID,
                                                    ),
                                                ),
                                            );
                                            $the_query = new WP_Query( $args );
                                            
                                            // The Loop
                                            if ( $the_query->have_posts() ) {
                                                
                                                $num = $the_query->post_count;
                                                $payments_remaining = $installments - $num;
                                                
                                                while ( $the_query->have_posts() ) {
                                                    $the_query->the_post(); 
                                                    $total_amount += get_post_meta( get_the_ID(), '_sc_amount', true );
                                                }
                                            } 
                                            /* Restore original Post Data */
                                            wp_reset_postdata();
                                            $post = $backup;
                                        }
                                        $order = sc_setup_order($post->ID);
                                        ?>
                                        <tr>
                                            <td valign="top"  ><a href="<?php echo admin_url( 'post.php?post='.$post->ID );?>&action=edit" ><?php echo  get_the_time( 'M j, Y', $post->ID ); ?></a></td>
                                            <td valign="top" ><?php echo get_the_title(get_post_meta( $post->ID, '_sc_product_id', true)); ?></td>
                                            <td valign="top" ><?php echo $order->sub_item_name; ?></td>
                                            <td valign="top" ><?php echo $order->sub_payment_terms; ?></td>                          
                                            <td valign="top"><?php echo ucwords($status); ?></td>                    
                                            <td valign="top"><?php sc_formatted_price($total_amount); ?></td>
                                            <td valign="top"><?php echo $payments_remaining; ?></td>
                                        </tr>                         
                            <?php } } } ?>               
                       </tbody>
                    </table> 
               <?php } ?>  
            </div>                       
        </div>
    </div>			
	<?php     
       
	}
}