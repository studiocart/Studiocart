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
 * The report-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Admin_Reports {

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

        // Top-level page
        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

        // Submenu Page
        // add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        
        add_submenu_page(
            'studiocart',
            apply_filters( $this->plugin_name . '-settings-page-title', esc_html__( 'Reports', 'ncs-cart' ) ),
            apply_filters( $this->plugin_name . '-settings-menu-title', esc_html__( 'Reports', 'ncs-cart' ) ),
            'sc_manager_option',
            $this->plugin_name . '-reports',
            array( $this, 'render_reports_page_content' )
        );

    }

    /**
     * Renders a simple page to display for the theme menu defined above.
     */
    public function render_reports_page_content( $active_tab = '' ) {
        $date = (!isset($_GET['date']) || $_GET['date']===NULL) ? date_i18n("Y-m-d") : sanitize_text_field($_GET['date']);
        ?>
        <!-- Create a header in the default WordPress 'wrap' container -->
        <div class="wrap">

            <?php settings_errors(); ?>
            
            <h1><?php esc_html_e( apply_filters('studiocart_plugin_title', $this->plugin_title), 'ncs-cart' ); ?></h1>

            <div class="sc-reports">
                <div class="sc-reports-header">
                    <h2><?php esc_html_e( 'Reports', 'ncs-cart' ); ?></h2>
                    
                    <select id="date-select" style="display:none;">
                        <?php 
                            $match_found = false;
                            $options = array(
                                'Today'=>date_i18n("Y-m-d"),
                                'Yesterday'=>date_i18n("Y-m-d", strtotime("-1 days")),
                                'Last 7 days'=>date_i18n("Y-m-d", strtotime("-7 days")) . ' to ' . date_i18n("Y-m-d"),
                                'Last 30 days'=>date_i18n("Y-m-d", strtotime("-30 days")) . ' to ' . date_i18n("Y-m-d"),
                                'This Month'=>date_i18n("Y-m-d", strtotime("first day of this month")) . ' to ' . date_i18n("Y-m-d"),
                                'Last Month'=>date_i18n("Y-m-d", strtotime("first day of last month")) . ' to ' . date_i18n("Y-m-d", strtotime("last day of last month")),
                                'All Time'=>'',
                                'Custom' => 'custom'
                            );
                            foreach($options as $k=>$v){
                                
                                $checked = '';
                                if($v == $date) {
                                    $checked = 'selected';
                                    $match_found = true;
                                    //var_dump($match_found, $v);
                                }
                                
                                if($v=='custom' && !$match_found) {
                                    $checked = 'selected';
                                    //var_dump($match_found, $v);
                                }
                                
                                echo '<option value="'.$v.'" '.$checked.'>'.$k.'</option>';
                            }
                        ?>
                    </select>
                    <form >
                        <input type="hidden" name="page" value="ncs-cart-reports" />
                        <input type="text" name="date" id="reports-range" value="<?php echo $date; ?>" />
                        <input class="button button-primary" type="submit" value="<?php esc_html_e('Apply', 'ncs-cart'); ?>" /> &nbsp; 
                        <a id="customer_csv_export__" class="button customer_csv_export" href="<?php echo home_url(); ?>/?sc-csv-export=reports&type=order&daterange=<?php echo $date; ?>"><?php esc_html_e('Export Orders', 'ncs-cart'); ?></a>
                    </form>
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
                </div>
                
                <?php 
                    $argsfunded = array(
                        'post_type'  => 'sc_order',
                        'post_status' => 'refunded',
                        'posts_per_page' => -1,
                    );
            

                    $args = array(
                        'post_type'  => 'sc_order',
                        'post_status' => 'paid',
                        'posts_per_page' => -1,
                    );
        
                    if($date != '') {
                        
                        $date = explode(' to ', $date);
                        $from = new DateTime($date[0]);
                        
                        if(count($date)==1) {
                            $args['date_query'] = array(
                                array(
                                    'year'  => $from->format('Y'),
                                    'month' => $from->format('m'),
                                    'day'   => $from->format('d'),
                                ),
                            );

                             $argsfunded['date_query'] = array(
                                array(
                                    'year'  => $from->format('Y'),
                                    'month' => $from->format('m'),
                                    'day'   => $from->format('d'),
                                ),
                            );
                        } else {
                            $to = new DateTime($date[1]);
                            $args['date_query'] = array(
                                array(
                                    'after' => $from->format('Y-m-d'),
                                    'before' => array(
                                        'year'  => $to->format('Y'),
                                        'month' => $to->format('m'),
                                        'day'   => $to->format('d'),
                                    ),
                                    'inclusive' => true,   
                                )
                            );
                            $argsfunded['date_query'] = array(
                                array(
                                    'after' => $from->format('Y-m-d'),
                                    'before' => array(
                                        'year'  => $to->format('Y'),
                                        'month' => $to->format('m'),
                                        'day'   => $to->format('d'),
                                    ),
                                    'inclusive' => true,   
                                )
                            );
                        }
                    }
                
                    $carttotal = array('total'=> 0);
                    $products = array();
                    $main = array('total'=> 0);
                    $upsells = array('total'=> 0);
                    $downsells = array('total'=> 0);
                    $bumps = array('total'=> 0);
                    $customers = array();
                    $gateways = array();
                    $coupons = array();
        
                    $queryrfunded = new WP_Query( $argsfunded );
                    // print_r($queryrfunded->request); //exit();
                      $refunds_array =array();
                      $refunded_amount_array = array();
                      $refunds_time_array = array();
                      if ( $queryrfunded->have_posts() ) {
                        while( $queryrfunded->have_posts() ) {
                            $queryrfunded->the_post();
                            $postid = get_the_ID();
                            $pid = get_post_meta( get_the_ID(), '_sc_product_id', true );
                            $refunds_array[$pid][]=get_the_title($pid);

                            $refund_logs_entrie = get_post_meta( $postid, '_sc_refund_log', true);
                            if(is_array($refund_logs_entrie)){
                                $refund_logs_entrie_count = count(get_post_meta( $postid, '_sc_refund_log', true));
                                $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                                $refund_amount = (!$refund_amount) ? get_post_meta( $postid , '_sc_amount', true ) : $refund_amount;
                                $refunds_time_array[]= $refund_logs_entrie_count;    
                                $refunded_time[]= $refund_amount;  
                            }

                        }
                    }
                    $refunded_amount = array_sum($refunded_amount_array);
                    $refunded_time = array_sum($refunds_time_array);
                   
                    $query = new WP_Query( $args );
                    if ( $query->have_posts() ) {
                        while( $query->have_posts() ) {
                            $query->the_post();
                            $order = new ScrtOrder(get_the_ID());
                            
                            $amount = (float) $order->amount;
                            $carttotal['total'] += (is_numeric($amount)) ? $amount : 0;
                            
                            if($pid = $order->product_id){
                                if ( !isset($products[$pid]) ){
                                    $products[$pid] = 0;
                                }
                                $products[$pid]++;
                                
                                $orderEmail = $order->email;
                                if ( $orderEmail && !in_array($orderEmail, $customers) ) {
                                    $customers[] = $orderEmail;
                                }
                                
                                $method = $order->pay_method;
                                if (!$method) {
                                    $method = 'stripe';
                                }
                                                                    
                                // payment method
                                if ( !isset($gateways[$method]) ){
                                    $gateways[$method] = 0;
                                }
                                $gateways[$method]++;
                                
                                // coupon
                                if ($coupon = $order->coupon) {
                                    if (is_array($coupon)) {
                                        $coupon = $order->coupon_id;
                                    } 
                                    if ($coupon) {
                                        if ( !isset($coupons[$pid][$coupon]) ){
                                            $coupons[$pid][$coupon] = 0;
                                        }
                                        $coupons[$pid][$coupon]++;
                                    }
                                }
                            
                                if ($usp = $order->us_parent) {
                                    if ( !isset($upsells[$pid]) ){
                                        $upsells[$pid] = 0;
                                    }
                                    $upsells[$pid]++;
                                    $upsells['total']++;
                                    
                                    // payment method
                                    if ( !isset($upsells['gateways'][$method]) ){
                                        $upsells['gateways'][$method] = 0;
                                    }
                                    $upsells['gateways'][$method]++;
                                    
                                    if ( !isset($carttotal[$usp ]) ){
                                        $carttotal[$usp] = 0;
                                    }
                                    $carttotal[$usp] += $amount;
                                    
                                } else if ($dsp = $order->ds_parent) {
                                    if ( !isset($downsells[$pid]) ){
                                        $downsells[$pid] = 0;
                                    }
                                    $downsells[$pid]++;
                                    $downsells['total']++;
                                    
                                    // payment method
                                    if ( !isset($downsells['gateways'][$method]) ){
                                        $downsells['gateways'][$method] = 0;
                                    }
                                    $downsells['gateways'][$method]++;
                                    
                                    if ( !isset($carttotal[$dsp ]) ){
                                        $carttotal[$dsp] = 0;
                                    }
                                    $carttotal[$dsp] += $amount;
                                    
                                } else if (isset($order->ob_parent) && $obp = $order->ob_parent) { // deprecated
                                    if ( !isset($bumps[$pid]) ){
                                        $bumps[$pid] = 0;
                                    }
                                    $bumps[$pid]++;
                                    $bumps['total']++;
                                    
                                    if ( !isset($carttotal[$obp]) ){
                                        $carttotal[$obp] = 0;
                                    }
                                    $carttotal[$obp] += $amount;
                                    
                                } else {
                                    if ( !isset($main[$pid]) ){
                                        $main[$pid] = 0;
                                    }
                                    $main[$pid]++;
                                    $main['total']++;
                                                                        
                                    // payment method
                                    if ( !isset($main['gateways'][$pid][$method]) ){
                                        $main['gateways'][$pid][$method] = 0;
                                    }
                                    $main['gateways'][$pid][$method]++;
                                    
                                    if ( !isset($carttotal[get_the_ID()]) ){
                                        $carttotal[get_the_ID()] = 0;
                                    }
                                    $carttotal[get_the_ID()] += $amount;
                                    
                                }
                                
                                if ( isset($order->bump_id) && ($ob = $order->bump_id) && is_int($ob) ) { // backwards compatibility
                                    if ( !isset($products[$ob]) ){
                                        $products[$ob] = 0;
                                    }
                                    $products[$ob]++;
                                
                                    if ( !isset($bumps[$ob]) ){
                                        $bumps[$ob] = 0;
                                    }
                                    
                                    $bumps[$ob]++;
                                    $bumps['total']++;
                                }
                                
                                if (is_countable($order->order_bumps)) {
                                    foreach($order->order_bumps as $bump) {
                                        $ob = $bump['id'];
                                        if ( !isset($products[$ob]) ){
                                            $products[$ob] = 0;
                                        }
                                        $products[$ob]++;

                                        if ( !isset($bumps[$ob]) ){
                                            $bumps[$ob] = 0;
                                        }

                                        $bumps[$ob]++;
                                        $bumps['total']++;
                                    }
                                }
                            }
                        }
                    }
                    wp_reset_postdata();
                ?>
                
                <div class="totals" style="flex-wrap: wrap;">
                    

                    <div class="total-sales column">
                        <div class="postbox">
                            <p><?php esc_html_e('Total Sales', 'ncs-cart'); ?></p>
                            <h3><?php sc_formatted_price($carttotal['total']); ?></h3>
                        </div>
                    </div>
                    
                    <div class="total-sales column">
                        <div class="postbox">
                            <p><?php esc_html_e('Net Sales', 'ncs-cart'); ?></p>                           
                            <h3><?php sc_formatted_price($carttotal['total'] - $refunded_amount); ?></h3>
                        </div>
                    </div>

                    <div class="total-downsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Total Refunds', 'ncs-cart'); ?></p>
                            <h3><?php echo $refunded_time; ?></h3>
                        </div>
                    </div>

                     <div class="total-downsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Amount Refunded', 'ncs-cart'); ?></p>
                            <h3><?php sc_formatted_price($refunded_amount); ?></h3>
                        </div>
                    </div>
                    
                    <?php 
                    $gatewayLabels = ['paypal' => 'PayPal', 'stripe'=>'Stripe', 'cod'=>__("Cash on Delivery","ncs-cart"), 'free'=>__("Free","ncs-cart")];
                    $orders = count($carttotal) - 1; 
                    $avg_amt = ($carttotal['total']) ? $carttotal['total'] / $orders : 0;
                    $upgrade_link = '<p style="font-style: italic;"><a href="'.get_admin_url().'admin.php?page=studiocart-pricing">Upgrade to Studiocart Pro</a></p>';
                    ?>
                    <div class="total-upsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Avg Revenue per Customer', 'ncs-cart'); ?></p>
                            <h3><?php sc_formatted_price($avg_amt); ?></h3>
                        </div>
                    </div>

                    <div class="total-orders column">
                        <div class="postbox">
                            <p><?php esc_html_e('Transactions (main orders + upsells)', 'ncs-cart'); ?></p>
                            <h3><?php echo $query->found_posts; ?></h3>
                        </div>
                    </div>

                    <div class="total-upsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Customers', 'ncs-cart'); ?></p>
                            <h3><?php echo count($customers); ?></h3>
                        </div>
                    </div>
                    
                    <div class="total-bumps column">
                        <div class="postbox">
                            <p><?php esc_html_e('Order Bumps', 'ncs-cart'); ?></p>
                            <?php echo ( !sc_fs()->is__premium_only() || !sc_fs()->can_use_premium_code()) ? $upgrade_link : '<h3>'.$bumps['total'].'</h3>'; ?>
                        </div>
                    </div>
                    
                    <div class="total-upsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Upsells', 'ncs-cart'); ?></p>
                            <?php echo ( !sc_fs()->is__premium_only() || !sc_fs()->can_use_premium_code()) ? $upgrade_link : '<h3>'.$upsells['total'].'</h3>'; ?>
                        </div>
                    </div>

                    <div class="total-downsells column">
                        <div class="postbox">
                            <p><?php esc_html_e('Downsells', 'ncs-cart'); ?></p>
                            <?php echo ( !sc_fs()->is__premium_only() || !sc_fs()->can_use_premium_code()) ? $upgrade_link : '<h3>'.$downsells['total'].'</h3>'; ?>
                        </div>
                    </div>
                
                </div>
                <div class="products">
                    <div class="product-list">
                        <h3><?php esc_html_e('Sales by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($products as $k=>$v) {
                            echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.$v.'</span></div>';
                        }
                        ?>
                    </div>
                   
                    <div class="product-list">
                        <h3><?php esc_html_e('Refunds by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($refunds_array as $k=>$v) {                            
                            echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.count($v).'</span></div>';                        
                        }
                        ?>
                    </div>
                    
                    <div class="product-list">
                        <h3><?php esc_html_e('Transactions by Payment Gateway', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($gateways as $k=>$v) {
                            echo '<div class="product-row"><span class="product-title">'.$gatewayLabels[$k] .'</span> <span class="num">'.$v.'</span></div>';
                        }
                        ?>
                    </div>
                    
                    <?php if(!empty($coupons)): ?>
                    <div class="product-list">
                        <h3>Completed Orders by Coupon Code</h3>
                        <?php
                        foreach($coupons as $prod=>$coupons) {
                            echo '<div class="product-row"><span class="product-title">'.get_the_title($prod) .'</span></div>';
                            foreach($coupons as $k=>$v) {
                                echo '<div class="product-row"><span class="product-title">'.$k.'</span> <span class="num">'.$v.'</span></div>';
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="product-list">
                        <h3><?php esc_html_e('Main Orders by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($main as $k=>$v) {
                            if($k == 'total') continue;
                            
                            if($k != 'gateways') {
                                echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.$v.'</span></div>';
                                foreach($main['gateways'][$k] as $gw => $gwa) {
                                    echo $gatewayLabels[$gw] . ': ' . $gwa . '<br>';
                                }
                            }
                        }
                        ?>
                    </div>

                    <?php if( sc_fs()->is__premium_only()&& sc_fs()->can_use_premium_code()): ?>
                    <div class="product-list">
                        <h3><?php esc_html_e('Order Bumps by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($bumps as $k=>$v) {
                            if($k=='total') continue;
                            echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.$v.'</span></div>';    
                        }
                        ?>
                    </div>
                    <div class="product-list">
                        <h3><?php esc_html_e('Upsells by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($upsells as $k=>$v) {
                            if($k=='total') continue;
                            
                            if($k != 'gateways') {
                                echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.$v.'</span></div>';
                            } else {
                                foreach($v as $gateway=>$gamt) {
                                    echo $gatewayLabels[$gateway] . ': ' . $gamt . '<br>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <div class="product-list">
                        <h3><?php esc_html_e('Downsells by Product', 'ncs-cart'); ?></h3>
                        <?php
                        foreach($downsells as $k=>$v) {
                            if($k=='total') continue;
                            
                            if($k != 'gateways') {
                                echo '<div class="product-row"><span class="product-title">'.get_the_title($k) .'</span> <span class="num">'.$v.'</span></div>';
                            } else {
                                foreach($v as $gateway=>$gamt) {
                                    echo $gatewayLabels[$gateway] . ': ' . $gamt . '<br>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /.wrap -->
    <?php
    } 
    

}