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
class NCS_Cart_Admin {

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

		$this->load_dependencies();

	}
	
	/**
	 * Load the required dependencies for the Admin facing functionality.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - NCS_Cart_Admin_Settings. Registers the admin settings and page.
	 *
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/class-ncs-cart-settings.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/class-ncs-cart-reports.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/class-ncs-cart-customer-reports.php';   
        require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/class-ncs-cart-contacts.php';
		//require_once plugin_dir_path( dirname( __FILE__ ) ) .  'admin/vendor/automator/automator.php';        
        
        add_action( 'admin_notices', array($this,'admin_notices') );
        add_action( 'save_post',  array($this, 'save_access_info'));

	}
    
    public function admin_notices() {
        
        // For updating to v2.0.11
        $sc_currency = get_option( '_sc_currency' );
        $settings_url = admin_url('admin.php?page=sc-admin#settings_tab_payment_methods');
        if($sc_currency && $sc_currency == strtolower($sc_currency)) {
            echo '<div class="notice notice-error is-dismissible"><p>Please re-select your currency to continue using <b>'.apply_filters('studiocart_plugin_title', $this->plugin_title).'</b>. You can do that now by <a href="'.admin_url('admin.php?page=sc-admin').'" rel="noreferrer noopener">clicking here</a>.</p></div>';
        }
        
        if(!sc_enabled_processors()) {
            echo '<div class="notice notice-error"><p style="font-weight: bold">No payment methods found!</p> <p>Please enable at least one payment method in the <a href="'.admin_url('admin.php?page=sc-admin#settings_tab_payment_methods').'" rel="noreferrer noopener">'.apply_filters('studiocart_plugin_title', $this->plugin_title).' settings</a>.</p></div>';
        }
        
        $error = sc_validate_payment_key();
        if(!empty($error)) {
            echo '<div class="notice notice-error">
                <p><strong>' . sprintf(__('%s integration error.', 'ncs-cart'), apply_filters('studiocart_plugin_title', $this->plugin_title)) . '</strong> ';
            
            echo sprintf(
                __('Unable to connect to the integration(s) listed below. Go to the %s <a href="%s" rel="noreferrer noopener">settings page</a> to check your integration settings.</p>', 'ncs-cart')
                , apply_filters('studiocart_plugin_title', $this->plugin_title), $settings_url);
            
            foreach($error as $gateway=>$message){
                echo '<p><strong>' . $gateway. ':</strong> ' . $message . '</p>';
            }
            echo '</div>';
        }
    }

    public function register_sc_importers(){	
        global $plugin_settings;	
        if (defined( 'WP_LOAD_IMPORTERS' ) ) {	
			register_importer( 'ncs-cart_tax_rate_csv', __( 'Studiocart tax rates (CSV)', 'ncs-cart' ), __( 'Import <strong>tax rates</strong> to your store via a csv file.', 'ncs-cart' ), array( $plugin_settings, 'sc_tax_rates_importer' ) );	
		}	
    }
    
    public function register_erasers( $erasers = array() ) {
        $erasers[] = array(
            'eraser_friendly_name' => apply_filters('studiocart_plugin_title', $this->plugin_title),
            'callback'               => array($this, 'user_data_eraser'),
        );

        return $erasers;
    }
    
    /**
     * Eraser for Plugin user data.
     *
     * @param     $email_address
     * @param int $page
     *
     * @return array
     */
    public function user_data_eraser( $email_address, $page = 1 ) {

        if ( empty( $email_address ) ) {
            return array(
                'items_removed'  => false,
                'items_retained' => false,
                'messages'       => array(),
                'done'           => true,
            );
        }
        
        $done = true;
        $messages = array();
        $items_removed  = 0;
        $items_retained = 0;
        
        $args = array(          
                'post_type' => array('sc_order','sc_subscription'),
                'post_status' => 'any',
                'posts_per_page' => 100,
                'paged' => $page,
                'meta_query' => array(
                    array(
                        'key' => '_sc_email',
                        'value' => $email_address,
                    ),
                )
            );
        $results = new WP_Query($args); 
        if($results->max_num_pages > $page) {
            $done = false;
        }
                
        if( $results->have_posts() ){
            
            while( $results->have_posts() ) { $results->the_post();
                $id = get_the_ID();
                
                $fields = array(
                    '_sc_firstname' => 'first name',
                    '_sc_lastname'  => 'last name',
                    '_sc_email'     => 'email',
                    '_sc_phone'     => 'phone',
                    '_sc_country'   => 'country',
                    '_sc_address1'  => 'address',
                    '_sc_address2'  => 'address (line 2)',
                    '_sc_city'      => 'city',
                    '_sc_state'     => 'state',
                    '_sc_zip'       => 'zip', 
                    '_sc_ip_address'=> 'IP address'
                );
                
                foreach ($fields as $k=>$v) {
                    if (get_post_meta($id, $k, true)) {
                        delete_post_meta($id, $k);
                        if ( update_post_meta($id, $k, __('[removed]','ncs-cart'), $v) ) {
                            $items_removed++;
                        } else {
                            $messages[] = sprintf(__( 'There was a problem removing your %s from order #%d.', 'ncs-cart'), $v, $id);
                            $items_retained++;
                        }
                    }
                }
            }
            wp_reset_postdata();
        }

        // Returns an array of exported items for this pass, but also a boolean whether this exporter is finished.
        //If not it will be called again with $page increased by 1.
        return array(
            'items_removed'  => $items_removed,
            'items_retained' => $items_retained,
            'messages'       => $messages,
            'done'           => $done,
        );
    }
    
    public function register_exporter( $exporters_array ) {
        $exporters_array['studiocart_exporter'] = array(
            'exporter_friendly_name' => 'Studiocart exporter', // isn't shown anywhere
            'callback' => array($this, 'user_data_exporter'), // name of the callback function which is below
        );
        return $exporters_array;
    }
    
    public function user_data_exporter( $email_address, $page = 1 ) {

        $export_items = array();
        $done = true;
        
        $args = array(          
                'post_type' => array('sc_order','sc_subscription'),
                'post_status' => 'any',
                'posts_per_page' => 100,
                'paged' => $page,
                'meta_query' => array(
                    array(
                        'key' => '_sc_email',
                        'value' => $email_address,
                    ),
                )
            );
        $results = new WP_Query($args); 
        if($results->max_num_pages > $page) {
            $done = false;
        }
                        
        if( $results->have_posts() ){
            
            while( $results->have_posts() ) { $results->the_post();
                $id = get_the_ID();
                
                $fields = array(
                    '_sc_firstname' => 'First Name',
                    '_sc_lastname'  => 'Last Name',
                    '_sc_email'     => 'Email',
                    '_sc_phone'     => 'Phone',
                    '_sc_country'   => 'Country',
                    '_sc_address1'  => 'Address',
                    '_sc_address2'  => 'Address (Line 2)',
                    '_sc_city'      => 'City',
                    '_sc_state'     => 'State',
                    '_sc_zip'       => 'Zip', 
                    '_sc_ip_address'=> 'IP Address'
                );
                $data = array(
                    array('name' => 'Order ID', 'value' => get_the_ID())
                );
                foreach ($fields as $k=>$v) {
                    if ($val = get_post_meta($id, $k, true)) {
                        $data[] = array(
                                    'name' => $v,
                                    'value' => $val
                                );
                    }
                }
                                             
                $export_items[] = array(
                    'group_id' => 'sc-orders',
                    'group_label' => 'Studiocart Orders',
                    'item_id' => 'order-'.get_the_ID(),
                    'data' => $data
                );

            }
            wp_reset_postdata();
        }
        
        // Tell core if we have more orders to work on still
        return array(
            'data' => $export_items,
            'done' => $done,
        );
    }
    
    public function privacy_declarations() {

		$content = 
			__( '<p class="privacy-policy-tutorial">This sample language includes the basics around what personal data your Studiocart installation may be collecting, storing and sharing, as well as who may have access to that data. Depending on what settings are enabled and which additional plugins are used, the specific information shared by your site will vary. We recommend consulting with a lawyer when deciding what information to disclose on your privacy policy.</p>
<h2>What we collect and store</h2>
<p>We collect information about you during the checkout process as well as some basic activities such as the dates you make purchases, or cancel your subscriptions with us.</p>
<p>While you visit our site, we’ll track:</p>
<ul>
   <li>— Products you’ve viewed:  we’ll use this to, for example, send you reminders about products you’ve recently viewed</li>
   <li>— IP address: we’ll use this for purposes like tracking which products you\'ve purchased and what discounts you’re eligible for</li>
   <li>— Name, email and physical address: we’ll ask you to enter this so we can communicate with you about your order and deliver your order to you!</li>
</ul>
<p>When you purchase from us, we’ll ask you to provide information including your name, billing/shipping address, email address, phone number, credit card/payment details and optional account information like username and password. We’ll use this information for purposes, such as, to:</p>
<ul>
   <li>— Send you information about your account and order</li>
   <li>— Respond to your requests, including refunds and complaints</li>
   <li>— Process payments and prevent fraud</li>
   <li>— Set up your account for our store</li>
   <li>— Improve our store offerings</li>
   <li>— Send you marketing messages, if you choose to receive them</li>
</ul>
<p>If you create an account, we will store your name, address, email and phone number, which will be used to populate the checkout for future orders.</p>
<p>We generally store information about you for as long as we need the information for the purposes for which we collect and use it, and we are not legally required to continue to keep it. For example, we will store order information for XXX years for tax and accounting purposes. This includes your name, email address and billing/shipping address.</p>
<h2>Who on our team has access</h2>
<p>Members of our team have access to the information you provide us. For example, site Owner/Administrators can access:</p>
<ul>
   <li>— Order information like what was purchased, subscription information, payment dates and amounts, and</li>
   <li>— Customer information like your name, username / email address, and address information.</li>
</ul>
<p>Our team members have access to this information to help fulfill orders, process refunds and support you.</p>
<h2>What we share with others</h2>
<p><em>
  In this section you should list who you’re sharing data with, and for what purpose. This could include, but may not be limited to, analytics/reporting tools, marketing services (such as email services like MailChimp, ActiveCampaign or ConvertKit), payment gateways, and third party embeds.
</em></p>
<p><em>
We share information with third parties who help us provide additional contact services to you; for example – [enter your third party platforms such as Analytics, Email Marketing, or any others and short description of their purpose. If you have a DPA from that service, this would be a good place to include that also.]
</em></p>
<h3>Payments</h3>
<p class="privacy-policy-tutorial">In this subsection you should list which third party payment processors you’re using to take payments on your store since these may handle customer data. We’ve included Stripe as an example, but you should remove this if you’re not using Stripe.</p>
<p>We accept payments through Stripe. When processing payments, some of your data will be passed to Stripe, including information required to process or support the payment, such as the purchase total and billing information.</p>
<p>Please see the <a href="https://stripe.com/privacy">Stripe Privacy Policy</a> for more details.</p>',
				'ncs-cart');

		wp_add_privacy_policy_content(
			'Studiocart',
			wp_kses_post( $content )
		);
	}
	
	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in NCS_Cart_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The NCS_Cart_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

        global $is_studiocart;
        
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/ncs-cart-admin.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'selectize-default', plugin_dir_url( __FILE__ ) . 'css/selectize.default.css', array(), $this->version, 'all' );

        if(!$is_studiocart) {
            $is_studiocart = in_array(get_post_type(), array('sc_product', 'sc_order', 'sc_subscription', 'sc_us_path', 'sc_membership')) || get_admin_page_parent() == 'studiocart';
        }
        
        if (!$is_studiocart) {
            return;
        }

		wp_enqueue_style( $this->plugin_name.'-balloon.css', plugin_dir_url( __FILE__ ) . 'css/ncs-cart-daterangepicker.min.css', array(), $this->version, 'all' );
        wp_enqueue_style( $this->plugin_name.'-daterangepicker', plugin_dir_url( __FILE__ ) . 'css/ncs-cart-daterangepicker.min.css', array(), $this->version, 'all' );
        wp_enqueue_style( 'balloon.css' , plugin_dir_url( __FILE__ ) . 'vendor/balloon.min.css');
        wp_enqueue_style( 'flatpickr' , plugin_dir_url( __FILE__ ) . 'vendor/flatpickr.min.css');
        wp_enqueue_style( 'dataTables', plugin_dir_url( __FILE__ ).'css/jquery.dataTables.min.css' );
        wp_enqueue_style( 'wp-color-picker' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts($hook_suffix) {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in NCS_Cart_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The NCS_Cart_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        
        global $post_type, $is_studiocart;
        
		wp_enqueue_script('wp-script-selectize', plugin_dir_url( __FILE__ ) . 'js/selectize.js', true );
        
        if (!$is_studiocart) {
            return;
        }
        
        wp_enqueue_script( 'flatpickr', plugin_dir_url( __FILE__ ) . 'vendor/flatpickr.min.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), $this->version, false );
        wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/ncs-cart-admin.js', array( 'jquery','wp-color-picker' ), $this->version, false );
        wp_enqueue_script( 'ncs-cart-moment', plugin_dir_url( __FILE__ ) . 'js/ncs-cart-moment.min.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), $this->version, false );
        wp_enqueue_script( 'daterangepicker', plugin_dir_url( __FILE__ ) . 'js/ncs-cart-daterangepicker.min.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-slider' ), $this->version, false );
        wp_enqueue_script('dataTables', plugin_dir_url( __FILE__ ).'js/jquery.dataTables.min.js');
                
        wp_localize_script( $this->plugin_name, 'sc_reg_vars', array(
            'upload_url' => admin_url( 'async-upload.php' ),
            'sc_ajax_url' 	=> admin_url( 'admin-ajax.php' ),
            'nonce' 		=> wp_create_nonce('sc-ajax-nonce'),
            'media_nonce'      => wp_create_nonce('media-form')
            )
        );
        
        wp_localize_script( $this->plugin_name, 'sc_translate_backend', sc_translate_js('ncs-cart-admin.js') );
        if(get_option('sc_mailchimp_tags')){
            wp_localize_script( $this->plugin_name, 'sc_mc_tags', get_option('sc_mailchimp_tags') );
        }
        if(get_option('sc_mailchimp_groups')){
            wp_localize_script( $this->plugin_name, 'sc_mc_groups', get_option('sc_mailchimp_groups') );
        }
        
		$screen = get_current_screen();
        if (strpos($screen->id, 'sc-admin') !== false) {
            wp_enqueue_script( $this->plugin_name . '-admin-settings-tax', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-admin-settings-tax.js', array( 'jquery','jquery-ui-autocomplete','wp-util' ), $this->version, true );
            
            $tax_localize_data = array(
                'tax_rates'        => NCS_Cart_Tax::get_tax_rate(),
                'sc_countries'     => sc_countries_autocomplte_format_list(),
                'sc_states'        => sc_states_autocomplte_format_list(),
                'limit'         => 100,
                'page'          => ! empty( $_GET['p'] ) ? absint( $_GET['p'] ) : 1,
                'default_rate'  => array(
					'tax_rate_id'       => 0,
					'tax_rate_country'  => '',
					'tax_rate_state'    => '',
					'tax_rate'          => '',
					'tax_rate_name'     => '',
					'tax_rate_priority' => 1,
					'tax_rate_order'    => null,
					'tax_rate_class'    => 'current',
				),
                'ajaxurl'               => admin_url( 'admin-ajax.php' ),
                'ncs_tax_nonce' 		        => wp_create_nonce('sc-tax-nonce'),
                'ncs_ajax_nonce' 		    => wp_create_nonce($this->plugin_name.'_admin_ajax')
            );
            wp_localize_script( $this->plugin_name . '-admin-settings-tax', 'ncsLocalizeTaxSettings', $tax_localize_data );
        }
        wp_enqueue_media();           
        wp_enqueue_script( $this->plugin_name . '-uploader', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-file-uploader.js', array( 'jquery' ), $this->version, true );
        wp_enqueue_script( $this->plugin_name . '-repeater', plugin_dir_url( __FILE__ ) . 'js/' . $this->plugin_name . '-repeater.js', array( 'jquery' ), $this->version, true );
				
	}
    
    public function create_stripe_webhook($option, $value) {
        global $sc_stripe;
        
        $env = ($option == '_sc_stripe_live_sk') ? 'live' : 'test';
        $api_version = apply_filters('sc_stripe_api_version', '2013-08-13');
        // create webhook if new value present
        if($value) {
            \Stripe\Stripe::setApiKey($value);
            try {
                
                $webhook = \Stripe\WebhookEndpoint::create([
                    'url' => sc_get_webhook_url('stripe'),
                    'api_version' => $api_version,
                    'enabled_events' => [
                        'charge.succeeded',
                        'invoice.payment_failed',
                        'invoice.payment_succeeded',
                        'invoice.marked_uncollectible',
                        'customer.subscription.updated',
                        'customer.subscription.deleted',
                    ],
                ]);
    
                if( isset($webhook->id) ){
                    update_option( '_sc_stripe_'.$env.'_webhook_id', $webhook->id );
                    update_option( '_sc_stripe_'.$env.'_webhook_secret', $webhook->secret );
                }
            } catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                exit;
            }
        }
    }
    
    public function update_stripe_webhook($old_value, $value, $option) {
        global $sc_stripe;
        
        $env = ($option == '_sc_stripe_live_sk') ? 'live' : 'test';
        $webhook_id = get_option( '_sc_stripe_'.$env.'_webhook_id');
        $api_version = apply_filters('sc_stripe_api_version', '2013-08-13');
        // delete old webhook on Stripe
        if ($old_value && $webhook_id) {
            $stripe = new \Stripe\StripeClient($old_value);
            try {
                $stripe->webhookEndpoints->delete($webhook_id,[]);           
                // delete webhook info in DB
                delete_option( '_sc_stripe_'.$env.'_webhook_id');
                delete_option( '_sc_stripe_'.$env.'_webhook_secret'); 
            } catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                exit;
            }
        }
                
        // create webhook if new value present
        if($value) {
            $url = sc_get_webhook_url('stripe');
            $update= false;
            \Stripe\Stripe::setApiKey($value);
            try {
                $webhooks = \Stripe\WebhookEndpoint::all();
                if( !empty($webhooks) ):
                    $stripe = new \Stripe\StripeClient($value);
                    foreach($webhooks as $wh):
                        if($wh->url == $url):
                            if($wh->api_version == $api_version):
                                $update = $wh->id;
                            else:
                                $stripe->webhookEndpoints->delete($wh->id,[]);
                            endif;
                            break;
                        endif;
                    endforeach;
                endif;
            } catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                exit;
            }
            if($update):
                try {
                    $webhook = \Stripe\WebhookEndpoint::update($update,
                    [
                        'enabled_events' => [
                            'charge.succeeded',
                            'invoice.payment_failed',
                            'invoice.payment_succeeded',
                            'invoice.marked_uncollectible',
                            'customer.subscription.updated',
                            'customer.subscription.deleted',
                        ],
                    ]);
        
                    
                } catch(\Exception $e) {
                    echo $e->getMessage(); //add custom message
                    exit;
                }
            else:
                try {
                    $webhook = \Stripe\WebhookEndpoint::create([
                        'url' => $url,
                        'api_version' => $api_version,
                        'enabled_events' => [
                            'charge.succeeded',
                            'invoice.payment_failed',
                            'invoice.payment_succeeded',
                            'invoice.marked_uncollectible',
                            'customer.subscription.updated',
                            'customer.subscription.deleted',
                        ],
                    ]);
                } catch(\Exception $e) {
                    echo $e->getMessage(); //add custom message
                    exit;
                }
            endif;
            if( isset($webhook->id) ){
                update_option( '_sc_stripe_'.$env.'_webhook_id', $webhook->id );
                update_option( '_sc_stripe_'.$env.'_webhook_secret', $webhook->secret );
            }
        }
    }
    
    public function mailchimp_authentication(){
		$mailchimp_apikey = get_option( '_sc_mailchimp_api' );
		if( $mailchimp_apikey ){

			try{
				return new \DrewM\MailChimp\MailChimp($mailchimp_apikey);
			}catch(\Exception $e) {
				echo $e->getMessage(); //add custom message
				return;
			}
		}
	}
	
	public function activecampaign_authentication(){
		$activecampaign_secret_key = get_option( '_sc_activecampaign_secret_key' );
		if( $activecampaign_secret_key ){

			try{
				$activecampaign_url 	= get_option( '_sc_activecampaign_url' );
				$path = dirname(dirname(__FILE__)).'/includes/vendor/activecampaign/api-php/includes/ActiveCampaign.class.php';
				require_once($path);					
				$activecampaign = new ActiveCampaign($activecampaign_url, $activecampaign_secret_key);
				return $activecampaign;
			
			}catch(\Exception $e) {
				echo $e->getMessage(); //add custom message
				return;
			}
		}
	}
    
	public function get_mailchimp_lists($renew = false){
        if ( !$renew && $lists = get_option('sc_mailchimp_lists') ) {
            return $lists;
        } else {
            $lists = array();
            $MailChimp = $this->mailchimp_authentication();   
            if($MailChimp){
                $result = $MailChimp->get('lists?count=100');
                if( isset( $result['lists'] ) && !empty( $result['lists'] ) ){
                    foreach( $result['lists'] as $key => $list ){
                        $list_id 			= $list['id'];
                        $mail_chimplist_name 	= $list['name'];
                        //push lists
                        $lists[$list_id] = $mail_chimplist_name;
                    }
                }
            } 
            update_option('sc_mailchimp_lists', $lists);
            return $lists;
		}
	}
	
	public function get_mailchimp_tags($renew = false){
        if ( !$renew && $tags = get_option('sc_mailchimp_tags') ){
            return $tags;
        } else {
            $tags = array();
            $list_data = $this->get_mailchimp_lists();
            if($list_data) {
                $MailChimp = $this->mailchimp_authentication();
                if($MailChimp){	
                    foreach ($list_data as $list_id => $list_val){
                        if(!empty($list_id)){
                            $result = $MailChimp->get('lists/'.$list_id.'/segments?count=100');		
                            if( isset( $result['segments'] ) && !empty( $result['segments'] ) ){
                                foreach( $result['segments'] as $key => $tag ){
                                    $tag_id                 = 'tag-'.$tag['id'];
                                    $mail_chimptag_name 	= $tag['name'];

                                    //push tags
                                    $tags[$list_id]["{$tag_id}"] = $mail_chimptag_name;
                                }
                            } 
                        }
                    }
                }
            }
            update_option('sc_mailchimp_tags', $tags);
            return $tags;
        } 
	}
	
	public function get_mailchimp_groups($renew = false){
        
        if ( !$renew && $groups = get_option('sc_mailchimp_groups') ){
            return $groups;
        } else {
            $list_data = $this->get_mailchimp_lists();
            $groups = array();
            $MailChimp = $this->mailchimp_authentication();
            if($MailChimp){	

                foreach ($list_data as $list_id => $list_val){
                    if(!empty($list_id)){
                        $parent_groups = $MailChimp->get('lists/'.$list_id.'/interest-categories?count=100');	

                        if( isset( $parent_groups['categories'] ) && !empty( $parent_groups['categories'] ) ){
                            foreach( $parent_groups['categories'] as $key => $parent_group ){
                                $groups_id =$parent_group['id'];
                                $mail_chimpparent_group_name =$parent_group['title']; 
                                $result = $MailChimp->get('lists/'.$list_id.'/interest-categories/'.$groups_id.'/interests');
                                // "<pre>";
                                //var_dump($result); 
                                //echo "</pre>"; die();
                                foreach( $result['interests'] as $key => $group ){
                                    $group_id 			= $group['id'];
                                    $mail_chimpgroup_name 	= $group['name'];

                                    //push tags
                                    //groups[$groups_id.'-'.$group_id] = $mail_chimpparent_group_name.' - '.$mail_chimpgroup_name;
                                    $groups[$list_id][$group_id] = $mail_chimpparent_group_name.' - '.$mail_chimpgroup_name;
                                }
                            }
                        }
                    }
                }			
            }			
            update_option('sc_mailchimp_groups', $groups);
            return $groups;
        }
	}
	
	//get_convertkit_forms
	public function get_convertkit_forms($renew = false){
        if ( !$renew && $tags = get_option('sc_convertkit_forms') ){
            return $tags;
        } else {
            $tags = array();
            $apikey 	= get_option( '_sc_converkit_api' );
            $secretKey 	= get_option( '_sc_converkit_secret_key' );
            if( $apikey &&  $secretKey ){
                try{
                    $url = "https://api.convertkit.com/v3/forms?api_key={$apikey}";
                    $response = wp_remote_get($url);
                    $responseBody = wp_remote_retrieve_body( $response );
                    $result = json_decode( $responseBody, true );

                    if ( is_array( $result ) && ! is_wp_error( $result ) ) {
                        foreach( $result['forms'] as $tag ){
							$id 	= $tag['id'];
							$name 	= $tag['name'];
							$tags[$id] = $name;
						}
                    }
                    
                }catch(\Exception $e) {
                    echo $e->getMessage(); //add custom message
                    return;
                }
            }
            update_option('sc_convertkit_forms', $tags);
            return $tags;
        }
	}
	
    //get_sc_converkit_tags
	public function get_convertkit_tags($renew = false){
        $tags = array();
        $apikey 	= get_option( '_sc_converkit_api' );
        $secretKey 	= get_option( '_sc_converkit_secret_key' );
        if( $apikey &&  $secretKey ){
            try{
                $url = "https://api.convertkit.com/v3/tags?api_key={$apikey}";
                $response = wp_remote_get($url);
                $responseBody = wp_remote_retrieve_body( $response );
                $result = json_decode( $responseBody, true );
                if ( is_array( $result ) && ! is_wp_error( $result ) ) {
                    foreach( $result['tags'] as $tag ){
                        $id 	= $tag['id'];
                        $name 	= $tag['name'];
                        $tags[$id] = $name;
                    }
                }

            }catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                return;
            }
        }
        update_option('sc_converkit_tags', $tags);
        return $tags;
	}
	
	//get_sc_activecampaign_lists
	public function get_activecampaign_lists($renew = false){
        $lists = array();
        $activecampaign = $this->activecampaign_authentication();
        if( $activecampaign ){
            try{
                $params = ['ids'  => 'all','full' => '1'];
                //$account = $activecampaign->api("account/list", $params);
                $ac_lists = $activecampaign->api("list/list_", $params);
                /* echo "<pre>";
                print_r($ac_lists);
                die;  */
                if( isset( $ac_lists ) && !empty( $ac_lists ) ){
                    foreach( $ac_lists as $key => $value ){
                        $id 	= $value->id;
                        $name 	= $value->name;
                        if(!empty($id)){
                            $lists['list-'.$id] = $name;
                        }
                    }
                }
            } catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                return;
            }
        }
        update_option('sc_activecampaign_lists', $lists);
        return $lists;
	}
	
	//get_sc_activecampaign_tags
	public function get_activecampaign_tags($renew = false){
        $tags = array();
        $activecampaign = $this->activecampaign_authentication();
        if( $activecampaign ){
            try{
                $ac_tags = $activecampaign->api("tag/list_");
                $ac_tags = json_decode($ac_tags);
                if(!empty( $ac_tags ) ){
                    foreach( $ac_tags as $key => $value ){
                        //$id 	= $value->id;
                        $name 	= $value->name;
                        if(!empty($name)){
                            $tags[$name] = $name;
                        }
                    }
                }
            }catch(\Exception $e) {
                echo $e->getMessage(); //add custom message
                return;
            }
        }
        update_option('sc_activecampaign_tags', $tags);
        return $tags;
	}
    
    public function get_sendfox_lists($renew = false){
		
        if ( !$renew && $lists = get_option('sc_sendfox_lists') ){
            return $lists;
        } else {
                        
            $lists = $this->sc_get_sendfox_list();
			
            if ( 
                (isset($lists['status']) && $lists['status'] === 'error') || 
                empty( $lists['result'] ) || 
                empty( $lists['result']['data'] )
            ) {
                // no results
                delete_option('sc_sendfox_lists');
                return $lists;
            } else {
                $sf_lists = [];
                foreach( $lists['result']['data'] as $l ) {
                    $sf_lists[$l['id']] = $l['name'];
                }
            
                update_option('sc_sendfox_lists', $sf_lists);
                return $sf_lists;
            }
        }
	}
	
    /**
     * Fetch all list page by page
     */
    private function sc_get_sendfox_list($lists = array(),$page=1){

        $temp_list = sc_sendfox_api_request('lists',array('page'=>$page));
        if (
            !empty( $temp_list['result'] ) && 
            $temp_list['result']['current_page']<$temp_list['result']['last_page']
        ) {
            if(empty($lists)){
                if(isset($temp_list['result']) && is_array($temp_list['result'])){
                    $lists['result']['data'] = $temp_list['result']['data'];
                }
            } else {
                foreach($temp_list['result']['data'] as $data){
                    array_push($lists['result']['data'],$data);
                }
                
            }
            $page++;
            $temp_list = $this->sc_get_sendfox_list($lists,$page);
        }
        if(empty($lists)){
            if(isset($temp_list['result']) && is_array($temp_list['result'])){
                $lists['result']['data'] = $temp_list['result']['data'];
            }
        } else {
            foreach($temp_list['result']['data'] as $data){
                array_push($lists['result']['data'],$data);
            }
        }

        return $lists;
    }
	
	/**
	 * Shows order info
	 *
	*/
    public function add_metaboxes() {

		// add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );        
        add_meta_box(
			'sc-order-notes',
			apply_filters( $this->plugin_name . '-metabox-title-order-notes', esc_html__( 'Order Notes', 'ncs-cart' ) ),
			array( $this, 'order_notes' ),
			array('sc_order', 'sc_subscription'),
			'side',
			'default'
		);
        
        add_meta_box(
			'sc-product-reports',
			apply_filters( $this->plugin_name . '-metabox-title-order-stats', esc_html__( 'Analytics', 'ncs-cart' ) ),
			array( $this, 'product_reports' ),
			'sc_product',
			'normal',
			'default'
		);
        
        add_meta_box(
			'sc-product',
			apply_filters( $this->plugin_name . '-metabox-title-access', esc_html__( apply_filters('studiocart_plugin_title', 'Studiocart'), 'ncs-cart' ) ),
			array( $this, 'related_product' ),
			array('page','post'),
			'side',
			'default'
		);
	}
    
    public function related_product($post) {
        
        do_action('studiocart_page_metabox', $post);
        
        wp_nonce_field( 'sc_related_product', 'sc_related_product' );
        $value = intval( (get_post_meta($post->ID, '_sc_related_product', true)) );

        echo '<p class="post-attributes-label-wrapper"><label class="post-attributes-label" for="_sc_related_product">'.esc_html__( 'Related Product', 'ncs-cart' ).'</label>
        <br>'.esc_html__( 'Apply a product\'s access rules to this page.', 'ncs-cart' ).'</p>';
        wp_dropdown_pages(array(
            'name'      => '_sc_related_product',
            'id'        => 'sc-product',
            'post_type' => 'sc_product',
            'show_option_none' => esc_html__( 'None', 'ncs-cart' ),
            'selected' => $value
        ));        
    }
    
    public function save_access_info($post_id) {
        if (array_key_exists('_sc_related_product', $_POST)) {
            
            // Check if our nonce is set.
            if ( ! isset( $_POST['sc_related_product'] ) ) {
                return;
            }

            // Verify that the nonce is valid.
            if ( ! wp_verify_nonce( $_POST['sc_related_product'], 'sc_related_product' ) ) {
                return;
            }

            // If this is an autosave, our form has not been submitted, so we don't want to do anything.
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            // Check the user's permissions.
            if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

                if ( ! current_user_can( 'edit_page', $post_id ) ) {
                    return;
                }

            }
            else {

                if ( ! current_user_can( 'edit_post', $post_id ) ) {
                    return;
                }
            }
            
            update_post_meta(
                $post_id,
                '_sc_related_product',
                $_POST['_sc_related_product']
            );
        } else {
            delete_post_meta($post_id,'_sc_related_product');
        }
    }
    
    function order_notes($object) {
        if ( $tz = get_option('timezone_string') ) {
            date_default_timezone_set($tz);
        }
        $log_entries = sc_order_log($object->ID);
        $entries = '';
        if(empty($log_entries)) {
            $entries .= __('No notes found', 'ncs-cart');
        } else {
            foreach($log_entries as $time=>$entry) {
                $time = explode(' - ', $time);
                $time = $time[0];
                $entries .= date('Y-m-d g:i a', $time) . ' - ' . $entry;
                $entries .= '&#013;&#010;';
                $entries .= '------------------------------';
                $entries .= '&#013;&#010;';
            }
        }
        ?>
        <div>
            <textarea id="sc-order-notes" readonly><?php echo $entries; ?></textarea>
        </div>
        <?php
    }
    
    /**
	 * Shows product reports
	 *
	*/
    
    function product_reports($object) {
        
        global $post;
        $old_post = $post;
        
        $id = $object->ID;
        $count_key = 'sc_form_view_counts';
        $allviews = (array) get_post_meta($id, $count_key, true);

        if(isset($_GET['sc-reset']) && $_GET['sc-reset'] == 1) {
            $prev = $allviews['since'] ?? '';
            $allviews['since'] = date('Y-m-d');
            unset($allviews['total']);

            if($prev != $allviews['since']) {
                if (isset($allviews['ids']) && is_countable($allviews['ids'])) {
                    unset($allviews['ids']);
                }
                update_post_meta($id, $count_key, $allviews);
            }
        }
        
        $args1 = array(
            'post_type'  => 'sc_order',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_sc_product_id',
                    'value' => $id,
                ),
                array(
                    'key' => '_sc_page_url',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_sc_renewal_order',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_sc_ds_parent',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_sc_us_parent',
                    'compare' => 'NOT EXISTS',
                ),
            )
        );

        if(isset($allviews['since'])) {
            $args1['date_query'] = array(
                array(
                    'after'     => $allviews['since'],
                    'inclusive' => true,
                ),
            );
        }
        $totalOrders = count(get_posts($args1));
        ?>
        <table aria-label="Product Stats">
            <tr>
                <th id="URL" align="left"><?php _e( 'URL', 'ncs-cart' ); ?></th>
                <th id="Page Views" align="middle"><?php _e( 'Page Views', 'ncs-cart' ); ?></th>
                <th id="Conversions (Main)" align="middle"><?php _e( 'Conversions (Main)', 'ncs-cart' ); ?></th>
                <th id="Conversion Rate" align="middle"><?php _e( 'Conversion Rate', 'ncs-cart' ); ?></th>
            </tr>
            <?php
            if(isset($allviews['ids'])){
                foreach($allviews['ids'] as $page_id => $views) {

                    $args = array(
                        'post_type'  => 'sc_order',
                        'post_status' => 'any',
                        'posts_per_page' => -1,
                        'meta_query' => array(
                            array(
                                'key' => '_sc_product_id',
                                'value' => $id,
                            ),
                            array(
                                'key' => '_sc_page_id',
                                'value' => $page_id,
                            ),
                            array(
                                'key' => '_sc_renewal_order',
                                'compare' => 'NOT EXISTS',
                            ),
                            array(
                                'key' => '_sc_ds_parent',
                                'compare' => 'NOT EXISTS',
                            ),
                            array(
                                'key' => '_sc_us_parent',
                                'compare' => 'NOT EXISTS',
                            ),
                        )
                    );

                    if(isset($allviews['since'])) {
                        $args['date_query'] = array(
                            array(
                                'after'     => $allviews['since'],
                                'inclusive' => true,
                            ),
                        );
                    }

                    $orders = count(get_posts($args));
                    $relative_url = str_replace( home_url(), "", get_permalink($page_id) );

                    echo '<tr><td><a href="' . get_permalink($page_id) . '">' . $relative_url . '</a></td><td align="middle">' . $views . '</td><td align="middle">' . $orders . '</td><td align="middle">' . round((float)($orders/$views) * 100 ) . '%' . '</td><tr>'; 
                }
            }
            ?>
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <td align="left" width="350"><?php _e( 'Total', 'ncs-cart' ); ?></td>
                <td align="middle"><?php echo (isset($allviews['total'])) ? $allviews['total'] : '0' ; ?></td>
                <td align="middle" width="120"><?php echo $totalOrders; ?></td>
                <td align="middle" width="120"><?php echo ($totalOrders && isset($allviews['total'])) ? round((float)($totalOrders/$allviews['total']) * 100 ) . '%' : 'n/a'; ?></td>
            </tr>
            
        <?php
        
        $bumps = 0;
        $products = array();
        
        $args = array(
            'post_type'  => 'sc_order',
            'post_status' => 'paid',
            'posts_per_page' => -1,
            
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_sc_product_id',
                    'value' => $id,
                ),
            )
        );

        if(isset($allviews['since'])) {
            $args['date_query'] = array(
                array(
                    'after'     => $allviews['since'],
                    'inclusive' => true,
                ),
            );
        }
        
        $args1 = $args;
        $args1['meta_query'][] =
                array(
                    'key' => '_sc_bump_id',
                    'compare' => 'EXISTS',
                );
        
        $query = new WP_Query( $args1 );
        if ( $query->have_posts() ) {
            while( $query->have_posts() ) {
                $query->the_post();
                
                $ob = get_post_meta( get_the_ID(), '_sc_bump_id', true );
                if(is_numeric($ob)){
                    if(!isset($products[$ob]['title'])) {
                        $products[$ob]['title'] = ($name = get_post_meta( get_the_ID(),'_sc_bump_name',true)) ? $name : get_the_title($ob);
                        $products[$ob]['amount'] = 0;
                    }
                    
                    $bumps++;
                    $products[$ob]['amount']++;
                }
            }
        }
        wp_reset_postdata();

        // repeater bumps
        $args1 = $args;
        $args1['meta_query'][] =
                array(
                    'key' => '_sc_order_bumps',
                    'compare' => 'EXISTS',
                );

        $query = new WP_Query( $args1 );
        if ( $query->have_posts() ) {
            while( $query->have_posts() ) {
                $query->the_post();
                
                $obs = get_post_meta( get_the_ID(), '_sc_order_bumps', true );
                foreach($obs as $bump) {
                    $ob = $bump['id'];
                    $name = $bump['name'];
                    if(!isset($products[$ob])) {
                        $products[$ob]['title'] = $bump['name'];
                        $products[$ob]['amount'] = 0;
                    }
                    $bumps++;
                    $products[$ob]['amount']++;
                }
            }
        }
        wp_reset_postdata();
        
        $post = $old_post;
        
        if($bumps): ?>
            <tr><td colspan="4">&nbsp;<br></td></tr>
            <tr><th colspan="4" align="left"><?php _e( 'Bumps sold by product', 'ncs-cart' ); ?></th></tr>
            
            <?php foreach($products as $k=>$v):?>
            <tr><td><?php echo $v['title']; ?></td><td align="middle"><?php echo $v['amount']; ?></td><td colspan="2"></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="4">&nbsp;</td></tr>
            <tr>
                <td><?php _e( 'Total sold', 'ncs-cart' ); ?></td>
                <td align="middle"><?php echo $bumps; ?></td>
                <td colspan="2"></td>
            </tr>
        <?php endif; ?>

        </table>
        <p style="text-align: right">
            <a href="<?php echo get_edit_post_link(); ?>&sc-reset=1" onclick="return confirm('Are you sure? Changes you made might not be saved.')">Reset stats</a>
            <?php 
            if(isset($allviews['since'])) {
                echo '<br><i style="opacity: .5">Reset on '.date_i18n( get_option( 'date_format' ), strtotime($allviews['since']) ).'</i>';
            } 
            ?>
        </p>
        <?php
        
    }
    
    public function product_info_callback( $post ){
                
        $orderClass = new ScrtOrder($post->ID);
        $order = apply_filters('studiocart_order', $orderClass);
        $order = (object) $orderClass->get_data();
		
        if( 'sc_order' === $post->post_type || 'sc_subscription' === $post->post_type ) {
            echo '<style type="text/css">    
                    .misc-pub-section {
                        display: none;
                    }
                </style>';
        }
        
        if( 'sc_order' !== $post->post_type || !isset($_GET['post']) ) {
			return;
		}        

        $str_mode = $order->gateway_mode ?? $order->stripe_mode ?? 'test';

        $stripe_id = $order->stripe_charge_id??$order->transaction_id??false;

        $paypal_id = $order->paypal_txn_id??$order->transaction_id??false;

        // if (isset($order->stripe_mode)){
        //     $str_mode = $order->stripe_mode;
        // } else {
        //     $str_mode = 'test';
        // }
        
        // if (isset($order->stripe_charge_id)){
        //     $stripe_id = $order->stripe_charge_id;
        // } else {
        //     $stripe_id = false;
        // }
        
        // if (isset($order->paypal_txn_id)){
        //     $paypal_id = $order->paypal_txn_id;
        // } else {
        //     $paypal_id = false;
        // }
        
        ?>
        <style type="text/css">    
            #rid_sc_item_name,
            #rid_sc_product_id {
                display: none !important;
            }
            
            #edit-disabled  {
                display: none;
                opacity: 0.6;
            }
        </style>
    
        <?php        
        if ( in_array(get_post_status(), ['canceled', 'refunded']) ) : ?>
            <style type="text/css">
                #edit-order  {
                    display: none;
                }
                
                #edit-disabled  {
                    display: inline;
                }
            </style>
        <?php endif; ?>
        <input type="hidden" id="stripe-mode" value="<?php echo $str_mode; ?>">

		<div id="sc-order-details" class="sc-product-info postbox-container">
			<div class="postbox">
				<h1 style="display: block">
					<?php 
					printf(
						__( 'Order #%s Details', 'ncs-cart' ), $post->ID
					);
                    $link = '';
					
                    if(isset($order->stripe_charge_id) || $order->pay_method == 'stripe') {
                        $str_url = ($str_mode == 'test') ? 'test/' : '';
                        $link .= esc_html( 'Stripe ID: ', 'ncs-cart' );
                        $link .= '<a id="stripe-id" href="https://dashboard.stripe.com/'. $str_url .'payments/'. $stripe_id .'" target="_blank" rel="noopener noreferrer">';
                        $link .= $stripe_id . '</a>';
                    } else if (isset($order->paypal_txn_id) || $order->pay_method == 'paypal') {
                        $link .= esc_html( 'PayPal ID: ', 'ncs-cart' ); 
                        $link .= '<a id="paypal-id" href="https://www.paypal.com/activity/payment/'. $paypal_id .'" target="_blank" rel="noopener noreferrer">';
                        $link .= $paypal_id . '</a>';
                    } else if(isset($order->pay_method)) {
                        switch($order->pay_method){
                            case('paypal'):
                                $link .= esc_html( 'Awaiting confirmation from PayPal', 'ncs-cart' );
                                break;
                            case('stripe'):
                                $link .= esc_html( 'Awaiting confirmation from Stripe', 'ncs-cart' );
                                break;
                            case('cod'):
                                $link .= esc_html( 'Cash on delivery', 'ncs-cart' );
                                break;   
                            case('manual'):
                                $link .= esc_html( 'Manually created', 'ncs-cart' );
                                break;   
                            default:
                                break;
                        }
                    } ?>
                    <small><?php echo apply_filters('studiocart_order_details_link', $link, $order); ?></small>
                </h1>
				<div>
                    <a href="#" id="edit-order" class="edit-hide"><?php esc_html_e( 'Edit', 'ncs-cart' ); ?></a>
                    <span id="edit-disabled"><?php esc_html_e( 'Edit', 'ncs-cart' ); ?></span>
                    <?php if($invoice = $orderClass->invoice_link_html()) {
                        echo ' | ' . $invoice;
                    }
                    ?>
                </div>
				
				<div class="col-lg-3 edit-hide">
					<p>
						<strong><?php esc_html_e( 'Customer Info:', 'ncs-cart' ); ?> </strong><br>
						<?php echo $order->firstname . ' ' . $order->lastname;?>
                        <br>
                        <?php if ($order->company) echo $order->company . '<br>'; ?>
						<?php echo $order->email; ?>
						<br>
						<?php if(isset($order->phone) && $order->phone !=""){ ?>  
                        <?php echo $order->phone; ?>    
                        <br>    
                       <?php } ?>                           
                        <a href="<?php echo get_site_url(); ?>/wp-admin/admin.php?page=ncs-cart-customer-reports&reportstypes=order&customerid=<?php echo $order->email; ?>"><?php esc_html_e( 'View Past Purchases', 'ncs-cart' ); ?> →</a>
					</p>                    
                    <?php if($address = sc_format_order_address($order)) : ?>
                        <p>
                            <strong><?php esc_html_e( 'Address:', 'ncs-cart' ); ?></strong><br>
                            <?php echo $address; ?>
                        </p>
                    <?php endif; ?>
                    
					<p>
						<strong><?php esc_html_e( 'Customer Account:', 'ncs-cart' ); ?> </strong><br>
						<?php 
						if (isset($order->user_account) && $user_id = $order->user_account) {
							$user_info = get_userdata($user_id);
							echo $user_info->display_name . ' ('. $user_info->user_email .')<br>';
							?>
							<a href="<?php echo get_edit_user_link($user_id); ?>"><?php esc_html_e( 'View Profile', 'ncs-cart' ); ?> →</a>
						<?php } else {
							esc_html_e('Guest', 'ncs-cart');
						}
						?>
					</p>
                    <?php if(isset($order->page_url)): ?>
                    <p>
						<strong><?php esc_html_e( 'URL:', 'ncs-cart' ); ?></strong><br>
						<a href="<?php echo get_permalink($order->page_id); ?>">...<?php echo $order->page_url; ?></a>
					</p>
                    <?php endif; ?>
				</div>
				
				<div class="col-lg-3 edit-hide">
                    <p>
						<strong><?php esc_html_e( 'Order Date:', 'ncs-cart' ); ?> </strong><br>
						<?php echo get_the_date(); ?>
					</p>
                    <p>
						<strong><?php esc_html_e( 'Order Amount:', 'ncs-cart' ); ?></strong><br>
                        <?php sc_formatted_price($order->amount); ?>
                    </p>
					<p>
						<strong><?php esc_html_e( 'Order Status:', 'ncs-cart' ); ?></strong><br>
						<?php echo $order->status_label; ?>
                    </p>
					<p>
						<strong><?php esc_html_e( 'IP Address:', 'ncs-cart' ); ?> </strong><br>
						<?php echo $order->ip_address; ?>
					</p>
                    <?php do_action('sc_order_details',$order); ?>
				</div>
                
                
                <div class="col-lg-3 edit-hide">
                    <?php if(isset($order->vat_number) && $order->vat_number) : ?>
                    <p>
						<strong><?php esc_html_e( 'VAT Number:', 'ncs-cart' ); ?> </strong> <?php echo $order->vat_number; ?><br>
					</p>
                    <?php endif; ?>
                    
                    <?php if(isset($order->custom_fields)) : ?>
                    <p>
						<strong><?php esc_html_e( 'Custom Fields:', 'ncs-cart' ); ?> </strong><br>
						<?php 
                        foreach($order->custom_fields as $k=>$v) { 
                            if(is_array($v['value'])) {
                                $value = array();
                                for($i=0;$i<count($v['value']);$i++) {
                                    $value[] = (isset($v['value_label'][$i])) ? $v['value_label'][$i] : $v['value'][$i];
                                }
                                $value = implode(', ', $value);
                            } else {                        
                                $value = (isset($v['value_label'])) ? $v['value_label'] : $v['value'];
                            }
                            echo $v['label'] . ': ' . $value . '<br>';
                        } ?>
					</p>
                    <?php endif; ?>
                    
                    <?php if(isset($order->consent)) : ?>
                    <p>
						<strong><?php esc_html_e( 'Opted-in:', 'ncs-cart' ); ?> </strong> <?php echo $order->consent; ?><br>
					</p>
                    <?php endif; ?>
				</div>
                
                
			</div>
		</div><!-- .sc-product-settings -->
	<?php
	}
    
    public function send_email_test(){
        
        if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce' ) ){
            esc_html_e('Ooops, something went wrong, please try again later.', 'ncs-cart');
            die();
        }
        
        $order_info = sc_test_order_data();
        
        $type = sanitize_text_field($_POST['type']);
        if( $type=='registration' ) {
            $res = sc_new_user_notification($user=false, $order_info, $test=true);
        } else {
            $res = studiocart_notification_send($type, $order_info, $test=true);
        }
        
        if($res) {
            esc_html_e('Test email sent.', 'ncs-cart');
        } else {
            esc_html_e('Test email failed to send.', 'ncs-cart');
        }
        
        die();
    }
    
    public function clean_product_meta_duplicate() {
        
        if( !isset( $_POST['post_id'] ) ){
            esc_html_e('Invalid Post ID', 'ncs-cart');
            die();
        }
        
        if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce' ) ){
            esc_html_e('Ooops, something went wrong, please try again later.', 'ncs-cart');
            die();
        }
        
        // Get pay options
        $product_id = intval($_POST['post_id']);
        update_post_meta($product_id, '_sc_stripe_prod_id', "");

        echo "success";
        die();
    }

    public function product_plan_options_html() {
        
        if( !isset( $_POST['post_id'] ) ){
            esc_html_e('Invalid Post ID', 'ncs-cart');
            die();
        }
        
        if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce' ) ){
            esc_html_e('Ooops, something went wrong, please try again later.', 'ncs-cart');
            die();
        }
        
        // Get pay options
        $product_id = intval($_POST['post_id']);
        $product_plan_data = get_post_meta($product_id, '_sc_pay_options', true);
                
        $default = '<option>'.esc_html__('No recurring payment plans found', 'ncs-cart').'</option>';
        
        if($product_plan_data && is_array($product_plan_data)) {
            $options = '';
            foreach ( $product_plan_data as $val ) {
                if( isset( $_POST['type'] ) && $_POST['type'] == 'recurring' ){
                    if(!isset($val['product_type']) || $val['product_type'] != 'recurring') {
                        continue;
                    }
                }
                $label = $val['option_name'] ?? $val['option_id'];
                $options .= '<option value="'.esc_html__($val['option_id'], 'ncs-cart').'">'.esc_html__($label, 'ncs-cart').'</option>';
            }
            
            if(!$options) {
                $options = $default;
            }
        } else {
            $options = $default;
        }
        
        echo $options;
        die();
    }

	public function product_form_callback( $post ){
        
        global $sc_currency_symbol;
        
        if( 'sc_order' !== $post->post_type || !isset($_GET['post']) ){
            return;
        }
        
        $scorder = new ScrtOrder($post->ID);
        $scorder = apply_filters('studiocart_order', $scorder);
        $order = (object) $scorder->get_data();
        $product_id = $order->product_id;
        
        ?>
        <div class="sc-product-info sc-product-table meta-box-sortables ui-sortable">
            <table cellpadding="0" cellspacing="0" class="sc_order_items">
                <caption>Product Info</caption>
                <thead>
                    <tr>
                        <th id="Item" class="item" colspan="2"><?php esc_html_e( 'Item', 'ncs-cart' ); ?></th>
                        <th id="Quantity" class="line_cost"><?php esc_html_e( 'Quantity', 'ncs-cart' ); ?></th>
                        <th id="Total" class="line_cost"><?php esc_html_e( 'Total', 'ncs-cart' ); ?></th>
                    </tr>
                </thead>
                <tbody id="order_line_items">
                        
                    <?php                         
    
                    $show_related = false;
                    $item_name = (isset($order->item_name)) ? $order->item_name : '';
        
                    if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
                        
                        if (isset($order->ob_parent) || isset($order->us_parent) || isset($order->ds_parent)) {
                            if (isset($order->ob_parent)) {
                                $parent_id = $order->ob_parent;
                                $child_type = 'bump';
                            } else if (isset($order->ds_parent)) {
                                $parent_id = $order->ds_parent;
                                $child_type = 'downsell';
                            } else {
                                $parent_id = $order->us_parent;
                                $child_type = 'upsell';
                            }
                            $parent_prod_id = get_post_meta( $parent_id , '_sc_product_id', true );
                            switch($child_type) {
                                case 'bump':
                                    $item_name = __('Order Bump', 'ncs-cart');
                                    break;
                                case 'downsell':
                                    $item_name = __('Downsell', 'ncs-cart');
                                    break;
                                default:
                                    $item_name = __('Upsell', 'ncs-cart');
                                    break;
                            }
                            $show_related = true;
                        }
                        
                        if (isset($order->order_child)) {                                
                            $show_related = true;
                        } 
                    }
                    
                    $list = sc_get_order_items($scorder, true, true);
                    foreach($list as $item):
                        $product_id = $item['product_id'];
                        $item_name = ($item['price_name']) ? '('. $item['price_name'] .')' : '';
                        $item['subtotal'] = sc_format_price($item['subtotal']);
                        
                        if($item['item_type'] == 'bundled') {
                            $item['subtotal'] = $item['quantity'] = '';
                        }
                        
                        ?>
                        <tr class="item">

                            <?php
                            if ( has_post_thumbnail( $product_id ) ) {
                                echo '<td class="thumb">';
                                    echo '<div class="sc-order-item-thumbnail">';
                                    echo get_the_post_thumbnail( $product_id, 'thumbnail' );
                                    echo '</div>';
                                echo '</td>';
                            } else {
                                echo '<td style="padding:0"></td>';
                            }
                            ?>

                            <td class="name">
                                <?php if($item['item_type'] != 'bundled') : ?>
                                    <span style="opacity: 65%"><?php esc_html_e( 'Product ID: #', 'ncs-cart' ); ?><span id="product_ID"><?php echo $product_id; ?></span></span><br>
                                <?php else: ?>
                                    &rdsh; <span style="opacity: 65%"><?php esc_html_e( 'ID: #', 'ncs-cart' ); ?><span id="product_ID"><?php echo $product_id; ?></span></span> 
                                <?php endif; ?>
                                <a href="<?php echo get_edit_post_link($product_id); ?>" class="sc-order-item-name"><?php echo get_the_title( $product_id); ?></a> 
                                <?php echo $item_name; ?>
                            </td>

                            <td class="item_cost" width="5%">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        <?php echo $item['quantity']; ?>
                                    </span>     
                                </div>
                            </td>

                            <td class="item_cost" width="1%">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        <?php echo $item['subtotal']; ?>
                                    </span>     
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <?php /*if ( isset($order->custom_prices) ): foreach($order->custom_prices as $price): ?>
                    <tr class="item bump-item">                     
                        <td style="padding:0;"></td>
                        <td class="name">
                            <span class="badge bump badge-addon"><?php _e( 'Add On', 'ncs-cart') ?></span>
                            <?php echo $price['label']; ?>
                        </td>

                        <td class="item_cost" width="1%">
                            <div class="view">
                                <span class="sc-Price-amount amount">
                                    <?php echo $price['qty'] ?>
                                </span>     
                            </div>
                        </td>
                        
                        <td class="item_cost" width="1%">
                            <div class="view">
                                <span class="sc-Price-amount amount">
                                    <?php sc_formatted_price($price['price']); ?>
                                </span>     
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                        
                    <?php if (isset($order->order_bumps)):?>
                        <?php foreach($order->order_bumps as $bump): ?>
                            <tr class="item bump-item">                     
                                <td style="padding:0"></td>
                                <td class="name">
                                <span class="badge bump"><?php _e( 'Order Bump', 'ncs-cart') ?></span>
                                    <a href="<?php echo get_edit_post_link($bump['id']); ?>" class="sc-order-item-name"><?php echo $bump['name']; ?></a> 
                                </td>

                                

                                <td class="item_cost" width="1%">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php echo $bump['qty'] ?? 1; ?>
                                        </span>     
                                    </div>
                                </td>

                                <td class="item_cost" width="1%">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php sc_formatted_price($bump['amount']); ?>
                                        </span>     
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php elseif (isset($order->bump_id)) : ?>
                        <?php if(is_countable($order->bump_id) && is_countable($order->bump_amt)):?>
                            <?php for($j=0;$j<count($order->bump_id);$j++):?>
                                <tr class="item bump-item">                     
                                    <td style="padding:0"></td>
                                    <td class="name">
                                    <span class="badge bump"><?php _e( 'Order Bump', 'ncs-cart') ?></span>
                                        <a href="<?php echo get_edit_post_link($order->bump_id[$j]); ?>" class="sc-order-item-name"><?php echo get_the_title( $order->bump_id[$j]); ?></a> 
                                    </td>

                                    <td class="item_cost" width="1%">
                                        <div class="view">
                                            <span class="sc-Price-amount amount">
                                                <?php echo $order->bump_qty[$j] ?? 1; ?>
                                            </span>     
                                        </div>
                                    </td>
                                    
                                    <td class="item_cost" width="1%">
                                        <div class="view">
                                            <span class="sc-Price-amount amount">
                                                <?php sc_formatted_price($order->bump_amt[$j]); ?>
                                            </span>     
                                        </div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        <?php endif; ?>
                    <?php endif; */?>
                    
                    <?php if ($order->coupon_id && in_array($order->coupon['type'], array('cart-percent', 'cart-fixed')) ): ?>
                        <tr class="item bump-item">                     
                            <td style="padding:0;"></td>
                            <td class="name" colspan="2">
                                <?php echo '<span class="badge">'.esc_html( 'Coupon: ', 'ncs-cart') . $order->coupon_id . '<span>' ?>
                            </td>
                            <td class="item_cost" width="1%">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        -<?php sc_formatted_price($order->coupon['discount_amount']); ?>
                                    </span>     
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($order->shipping_amount): ?>
                        <tr class="item items-total">                     
                            <td style="padding:0;"></td>
                            <td class="name" colspan="2">
                                <?php echo esc_html( 'Shipping', 'ncs-cart'); ?>
                            </td>
                            <td class="item_cost" width="1%">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        <?php sc_formatted_price($order->shipping_amount); ?>
                                    </span>     
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if(isset($order->tax_data) && is_object($order->tax_data) && $order->tax_desc): 
                        $redeem_tax = false;
                        if(isset($order->tax_data->redeem_vat) && $order->tax_data->redeem_vat): 
                            $redeem_tax = true;
                            if($order->tax_data->type != 'inclusive'):
                                $order->tax_amount = 0;
                                $order->tax_rate = "0%";
                            endif;
                        endif;?>
                        <tr class="item items-total">                     
                            <td style="padding:0;"></td>
                            <td class="name" colspan="2">
                                <?php echo $order->tax_desc.' ('.$order->tax_rate.')'; ?>
                            </td>
                            <td class="item_cost" width="1%">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        <?php sc_formatted_price($order->tax_amount); ?>
                                    </span>     
                                </div>
                            </td>
                        </tr>
                        <?php if($redeem_tax && $order->tax_data->type == 'inclusive'): ?>
                            <tr class="item">                     
                                <td style="padding:0;"></td>
                                <td class="name" colspan="2">
                                    <?php _e(get_option('_sc_vat_reverse_charge',"VAT Reversal"), 'ncs-cart') ?>
                                </td>
                                <td class="item_cost" width="2%">
                                    <div class="view" style="display: block;width: 50px;">
                                        - <?php sc_formatted_price($order->tax_amount); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <tr class="item items-total" style="font-weight: bold">                     
                        <td style="padding:0;"></td>
                        <td class="name" colspan="2">
                            <?php _e('Total:', 'ncs-cart') ?>
                        </td>

                        <td class="item_cost" width="1%">
                            <div class="view">
                                <span class="sc-Price-amount amount">
                                    <?php sc_formatted_price($order->amount); ?>
                                </span>     
                            </div>
                        </td>
                    </tr>

                    <?php if( get_post_status( $post->ID ) == 'paid' || get_post_status( $post->ID ) == 'refunded' ){
                        $refund_logs_entrie = $order->refund_log;
                        $total_amount = $order->amount;
                        if(is_array($refund_logs_entrie)) {
                           $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                           $total_amount -= $refund_amount;
                           
                        } if($total_amount) { ?>
                        <tr >
                            <td class="refund" colspan="4"><button type="button" id="sc_refund_items_btn" class="button refund-items"><?php esc_html_e( 'Refund', 'ncs-cart' ); ?></button></td>
                        </tr>
                        <?php }
                            $order->intent_id = $order->intent_id ?? '';
                            $sc_payment_intent = apply_filters( 'sc_payment_intent', $order->intent_id ,$order );
                            $sc_payment_method = apply_filters( 'sc_payment_method', $order->pay_method ,$order );
                        ?>

                        <input type="hidden" name="sc_payment_intent" id="stripe_sc_payment_intent" value="<?php echo $sc_payment_intent; ?>">
                        <input type="hidden" id="sc_payment_method" name="sc_payment_method" value="<?php echo $sc_payment_method; ?>">
                        <tr class="refund_amount_tr" style="display: none;">
                            <td class="refund" colspan="4">
                                <?php 
                                 if(get_post_meta(  get_the_ID() , '_sc_refund_restock', true ) == 'YES'){ ?>
                                    <p><?php _e('Restock Refunded Items','ncs-cart'); ?> <input type="checkbox" value="YES" id="sc_restock_refunded" name="sc_restock_refunded" disabled="disabled" ></p>
                                <?php }else{ ?>
                                    <p><?php _e('Restock Refunded Items','ncs-cart'); ?> <input type="checkbox" value="YES" id="sc_restock_refunded" name="sc_restock_refunded"  ></p>
                               <?php  } 
                                $right_currency = (in_array(get_option( '_sc_currency_position' ), ['right', 'right-space'])) ? true : false;
                                _e('Refund amount ','ncs-cart');
                                if (!$right_currency) { echo ' '. $sc_currency_symbol; } ?> 
                                    <input type="text" id="sc_refund_amount" name="sc_refund_amount" placeholder="Refund Amount" value="<?php echo sc_format_number($total_amount); ?>">       
                                <?php if ($right_currency) { echo $sc_currency_symbol; } ?>
                            </td>
                        </tr>                       
                        <tr class="refund_amount_tr" style="display: none;">
                            <td class="refund" colspan="3">
                                <button type="button" class="button refund-items sc_refund_btn"><?php esc_html_e( 'Refund this payment', 'ncs-cart' ); ?></button>
                            </td>
                            <td  class="refund"><button type="button" id="sc_cancel_refund_btn" class="button refund-items"><?php esc_html_e( 'Cancel', 'ncs-cart' ); ?></button></td>
                        </tr>
                        <?php 
                       
                            if(is_array($refund_logs_entrie)) { ?>
                                <tr>
                                    <th class="refund" colspan="4">Past Refund Amount</th>
                                </tr>
                                <tr>
                                    <td class="refund" colspan="4">
                                        <table width="100%">
                                            <tr>
                                                <th>Refund ID</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                            </tr>
                                            <?php foreach ($refund_logs_entrie as $key => $value) { ?>
                                            <tr>
                                                <td><?php echo $value['refundID']; ?></td>
                                                <td><?php echo date("F d, Y h:i a", strtotime($value['date']));  ?></td>
                                                <td><?php echo sc_format_price($value['amount']); ?></td>
                                            </tr>
                                            <?php } ?>
                                        </table>

                                    </td>
                                </tr>
                            <?php } ?>  
                    <?php } ?>  
                </tbody>
            </table>
        </div>

        <!--show if subscription order -->
        <?php 
        $subID = isset($order->subscription_id) ? $order->subscription_id : ''; 
               
        if($subID) {
            $show_related = true;
        }
        
        if($show_related){  ?>
            <div class="sc-product-info sc-product-table meta-box-sortables ui-sortable">
                <div class="postbox">
                    <h2><?php esc_html_e( 'Related Orders', 'ncs-cart' ); ?></h2>
                </div>  
                <table cellpadding="0" cellspacing="0" class="sc_order_items" width="100%">
                    <thead>
                        <tr>
                            <th class="item"><?php esc_html_e( 'Order Number', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Relationship', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Date', 'ncs-cart' ); ?></th>
                            <th class="item"><?php esc_html_e( 'Status', 'ncs-cart' ); ?></th>
                            <th class="line_cost"><?php esc_html_e( 'Total', 'ncs-cart' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="order_line_items">
                        
                        <?php
                        if(!empty($subID)) { ?>
                        
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($subID); ?>" class="sc-order-item-name">#<?php echo $subID; ?></a>
                                </td>
                                <td><?php esc_html_e( 'Subscription', 'ncs-cart' ); ?></td>
                                <td><?php echo get_the_date('F d, Y h:i a', $subID); ?></td>
                                <td class="name">
                                    <?php echo $order->status_label;
                                    ?>
                                </td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php sc_formatted_price(get_post_meta( $subID, '_sc_sub_amount', true )); ?>
                                        </span> / 
                                        <?php 
                                        $frequency = get_post_meta( $subID, '_sc_sub_frequency', true );
                                        $interval = get_post_meta( $subID, '_sc_sub_interval', true );
                                        if($frequency > 1) {
                                            echo $frequency . ' ' . sc_pluralize_interval($interval);
                                        } else {
                                            echo $interval;
                                        }
                                        ?>
                                    </div>
                                </td>
                            </tr>

                            <?php
                            // The Query
                            $args = array(
                                'post_type' => array( 'sc_order' ),
                                'orderby' => 'date',
                                'order'   => 'ASC',
                                'post__not_in' => (array) get_the_ID(),
                                'posts_per_page' => -1,
                                'meta_query'=>array(
                                    array(
                                        'key' => '_sc_subscription_id',
                                        'value' => $subID,
                                    ),
                                ),
                            );
                            $the_query = new WP_Query( $args );
                            $initial_order = true;
                           // print_r($the_query->request);
                            // The Loop
                            if ( $the_query->have_posts() ) {
                                while ( $the_query->have_posts() ) {
                                    $the_query->the_post(); 
                                    $child_order = new ScrtOrder(get_the_ID());
                                    ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="sc-order-item-name">#<?php echo get_the_ID(); ?></a>
                                        </td>
                                        <td><?php echo $initial_order ? 'Initial Order' : 'Renewal Order'; ?></td>
                                        <td><?php echo get_the_date('F d, Y h:i a', get_the_ID()); ?></td>
                                        <td class="name">
                                            <?php  echo $child_order->get_status(); ?>
                                        </td>
                                        <td class="sub_cost">
                                            <div class="view">
                                                <span class="sc-Price-amount amount">
                                                    <?php sc_formatted_price($child_order->amount); ?>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    $initial_order = false;
                                }
                            } 
                            /* Restore original Post Data */
                            wp_reset_postdata();
                        }
                          
                            
                        if (isset($parent_id) && $parent_id) :
                            $parent = new ScrtOrder($parent_id); ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($parent_id); ?>" class="sc-order-item-name">#<?php echo $parent_id; ?></a>
                                </td>
                                <td><?php echo __('Parent Order', 'ncs-cart' ) ?></td>
                                <td><?php echo get_the_date('F d, Y h:i a', $parent_id); ?></td>
                                <td class="name"><?php echo $parent->get_status(); ?></td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php sc_formatted_price(get_post_meta( $parent_id, '_sc_amount', true )); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endif;

                        $child_orders = get_post_meta($post->ID, '_sc_order_child');
                        if ($child_orders) : foreach($child_orders as $child_order){ 
                            $child_order = new ScrtOrder($child_order['id']);
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($child_order->id); ?>" class="sc-order-item-name">#<?php echo $child_order->id; ?></a>
                                </td>
                                <td><?php echo ($child_order->order_type=='downsell') ? __('Downsell', 'ncs-cart' ) : __('Upsell', 'ncs-cart' ); ?></td>
                                <td><?php echo get_the_date('F d, Y h:i a', $child_order->id); ?></td>
                                <td class="name">
                                    <?php 
                                        echo $child_order->get_status();
                                    ?>
                                </td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php sc_formatted_price($child_order->amount); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php } 
                        endif;

                    ?>
                        
                    </tbody>
                </table>
            </div>
        <?php } ?><!--close subscription details section--> 
       <?php
    }
	
	/**
	 * Shows subscription info
	 *
	*/
	public function subscription_info_callback( $post ){
                
        if( 'sc_subscription' !== $post->post_type || !isset($_GET['post']) ) {
            return;
        }
        
        $order = new ScrtSubscription($post->ID);
        $order = (object) $order->get_data();

        $str_mode = $order->gateway_mode ?? $order->stripe_mode ?? 'test';
        
        $stripe_id = $order->subscription_id ?? $order->stripe_subscription_id ?? '';
        $paypal_id = $order->subscription_id ?? $order->paypal_txn_id ?? ''; 
        ?>
        <div id="sc-order-details" class="sc-product-info postbox-container">
            <div class="postbox">
                <h1 style="display: block">
                    <?php 
                    printf(
                        __( 'Subscription #%s Details', 'ncs-cart' ), $post->ID
                    );
                    
                    $link = '';
					
                    if($order->pay_method == 'stripe' || isset($order->stripe_subscription_id)) {
                        $str_url = ($str_mode == 'test') ? 'test/' : '';
                        $link .= esc_html( 'Stripe ID: ', 'ncs-cart' );
                        $link .= '<a id="stripe-id" href="https://dashboard.stripe.com/'. $str_url .'subscriptions/'. $stripe_id .'" target="_blank" rel="noopener noreferrer">';
                        $link .= $stripe_id . '</a>';
                    } else if ($order->pay_method == 'paypal' || isset($order->paypal_txn_id)) {
                        $link .= esc_html( 'PayPal ID: ', 'ncs-cart' ); 
                        $link .= '<a id="paypal-id" href="https://www.paypal.com/activity/payment/'. $paypal_id .'" target="_blank" rel="noopener noreferrer">';
                        $link .= $paypal_id . '</a>';
                    } else {
                        switch($order->pay_method){
                            case('paypal'):
                                $link .= esc_html( 'Awaiting confirmation from PayPal', 'ncs-cart' );
                                break;
                            case('stripe'):
                                $link .= esc_html( 'Awaiting confirmation from Stripe', 'ncs-cart' );
                                break;
                            case('cod'):
                                $link .= esc_html( 'Cash on delivery', 'ncs-cart' );
                                break;   
                            case('manual'):
                                $link .= esc_html( 'Manually created', 'ncs-cart' );
                                break;   
                            default:
                                break;
                        }
                    } ?>
                    <small><?php echo apply_filters('studiocart_subscription_details_link', $link, $order); ?></small>
                    <input type="hidden" id="sc_payment_method" name="sc_payment_method" value="<?php echo $order->pay_method; ?>">
                    <?php if($stripe_id):?>
                        <input type="hidden" name="sc_payment_intent" id="stripe_sc_payment_intent" value="<?php echo $stripe_id; ?>">
                    <?php elseif($paypal_id):?>
                        <input type="hidden" name="sc_payment_intent" id="stripe_sc_payment_intent" value="<?php echo get_post_meta( $post->ID, '_sc_paypal_subscr_id', true ); ?>">
                    <?php endif; ?>
                </h1>
                <div><a href="#" id="edit-order" class="edit-hide"><?php esc_html_e( 'Edit', 'ncs-cart' ); ?></a></div>
                
                <div class="col-lg-3 edit-hide">
                    <p>
                        <strong><?php esc_html_e( 'Start Date:', 'ncs-cart' ); ?></strong><br>
                        <?php echo get_the_date(); ?>
                    </p>
                    
                    <p>
                        <strong><?php esc_html_e( 'Customer Info:', 'ncs-cart' ); ?></strong><br>
                        <?php echo $order->firstname . ' ' . $order->lastname; ?>
                        <br>
                        <?php if ($order->company) echo $order->company . '<br>'; ?>
                        <?php echo $order->email; ?>                        
                        <br>
                        <?php if(isset($order->phone)){ 
                         echo $order->phone; ?>
                        <br>
                        <?php } ?>                      
                        <a href="<?php echo get_site_url(); ?>/wp-admin/admin.php?page=ncs-cart-customer-reports&reportstypes=subscription&customerid=<?php echo $order->email; ?>&customername=<?php echo $order->firstname . ' ' . $order->lastname; ?>"><?php esc_html_e( 'View Past Purchases', 'ncs-cart' ); ?> →</a>
                    </p>
                    
                    <?php if($address = sc_format_order_address($order)) : ?>
                        <p>
                            <strong><?php esc_html_e( 'Address:', 'ncs-cart' ); ?></strong><br>
                            <?php echo $address; ?>
                        </p>
                    <?php endif; ?>
                    
                    <p>
                        <strong><?php esc_html_e( 'Customer Account:', 'ncs-cart' ); ?></strong><br>
                        <?php if ($user_id = $order->user_account ) {
                            $user_info = get_userdata($user_id);
                            echo $user_info->display_name . ' ('. $user_info->user_email .') <br>';
                            ?>
                            <a href="<?php echo get_edit_user_link($user_id); ?>"><?php esc_html_e( 'View Profile', 'ncs-cart' ); ?> →</a>
                            
                        <?php } else {
                            esc_html_e( 'Guest', 'ncs-cart' );
                        }
                        ?>
                        
                    </p>
                </div>
                
                <div class="col-lg-3 edit-hide">
                    <p>
                        <strong><?php esc_html_e( 'Subscription Status:', 'ncs-cart' ); ?></strong><br>
                        <?php echo $order->status_label; ?>
                    </p>
                    <p>
                        <strong><?php esc_html_e( 'Next Billing Date:', 'ncs-cart' ); ?></strong><br>
                        <?php $nextdate = $order->sub_next_bill_date; 
                        $status = (in_array(get_post_status( $post->ID ),['pending','pending-payment','initiated'])) ? 'pending' : get_post_status( $post->ID );
                        if($status == 'completed' || $status == 'canceled'|| $status == 'paused'){
                            echo "--";
                        }else{
                            if (is_numeric($nextdate)) {
                				$nextdate = get_date_from_gmt(date( 'Y-m-d H:i:s', $nextdate ), 'M j, Y');
                            } else {
                                $dateTime = DateTime::createFromFormat('Y-m-d', $nextdate);
                                if ($dateTime !== FALSE) {
                                    $nextdate = $dateTime->format('M j, Y');
                                }
                            }
                            
                            if (isset($order->cancel_date)) {
                                echo "--";
                            } else {
                                echo $nextdate;
                            }
                        }
                        ?>
                    </p>
                    <?php if (isset($order->cancel_date) && $order->status != 'canceled'): ?>
                    <p>
                        <strong><?php esc_html_e( 'Cancels On:', 'ncs-cart' ); ?></strong><br>
                        <?php echo $nextdate; ?>
                    </p>
                    <?php endif; ?>
                    <p>
                        <strong><?php esc_html_e( 'IP Address:', 'ncs-cart' ); ?></strong><br>
                        <?php echo $order->ip_address; ?>
                    </p>
                    <?php do_action('sc_sub_details',$order); ?>
                    
                    <?php if (isset($_GET['sc-subscription-sync'])) {
                        $this->sync_stripe_subscription($order);
                    } ?>
                                                
                </div>
            </div>
        </div><!-- .sc-product-settings -->
       <?php
    }
    
    public function sync_stripe_subscription($order){
        global $sc_currency;

        $sub = new ScrtSubscription($order->id);
        require_once( plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php');
        $gateway_mode = $order->gateway_mode;
        $apikey = get_option( '_sc_stripe_'. sanitize_text_field($gateway_mode) .'_sk' );
        if(empty($apikey)){
            esc_html_e('Oops, Stripe '.$gateway_mode.' key missing!', 'ncs-cart');
            return;
        }
        \Stripe\Stripe::setApiKey($apikey);
        $stripe = new \Stripe\StripeClient(["api_key" =>$apikey, "stripe_version" => "2022-08-01"]);

        try{            
           $invoices = $stripe->invoices->search([
              'query' => 'subscription:"'.$order->subscription_id.'"',
            ]);

          // rearrange invoices from oldest to newest
          $invoices = array_reverse($invoices->data);
          foreach ($invoices as $k=>$invoice) { 

            switch ($invoice['status']) {
                case 'uncollectible':
                    $post_status = 'failed';
                    $pay_status = 'failed';
                    break;
                case 'paid':
                    $pay_status = 'succeeded';
                    $post_status = 'paid';
                    break;
                default:
                    // Unexpected status
                    continue 2;
            }
              
            // update next bill date on this subscription
            if(($k+1) == count($invoices)) {
              $next_bill = $invoice['period_end'];
              $sub->sub_next_bill_date = $next_bill;
              $sub->store();
            }
              
            // search existing order by charge ID first, then intent ID
            $existing = ScrtOrder::get_by_trans_id($invoice['charge']);
            if(!$existing && isset($invoice['payment_intent'])) {
                $existing = ScrtOrder::get_by_trans_id($invoice['payment_intent']);
                if($existing) {
                    // replace intent id with charge ID
                    $existing->transaction_id = $invoice->charge;
                    $existing->store();
                }
            }

            if(!$existing) {

                // check if this is the first order
                $neworder = $sub->first_order(); 

                // update first order if it's the only one we have so far and it has a temp ID
                if ( ((!$neworder->transaction_id || strpos($neworder->transaction_id,'ch_') !== 0) && $sub->order_count() == 1) === false ) {
                    $neworder = $sub->new_order();
                }

                $neworder->transaction_id = $invoice->charge;
                $neworder->amount = sc_format_stripe_number($invoice->amount_paid, $sc_currency);
                $neworder->payment_status = $pay_status;
                $neworder->status = $post_status;
                $neworder->store();
                $post_id = $neworder->id;

                // add correct pay date
                $date_time        = date( 'Y-m-d H:i:s', $invoice['created'] );
                wp_update_post( array( 
                    'ID' => $post_id, 
                    'post_date' => get_gmt_from_date( $date_time ),
                    'post_date_gmt' => get_gmt_from_date( $date_time )
                ),
                false );

            } else {
                // update existing order if status is different
                $neworder = $existing;
                if ($neworder->status != $post_status) {
                    $neworder->payment_status = $pay_status;
                    $neworder->status = $post_status;
                    $neworder->store();
                    $post_id = $neworder->id;

                    // add correct pay date
                    $date_time        = date( 'Y-m-d H:i:s', $invoice['created'] );
                    wp_update_post( array( 
                        'ID' => $post_id, 
                        'post_date' => get_gmt_from_date( $date_time ),
                        'post_date_gmt' => get_gmt_from_date( $date_time )
                    ),
                    false );
                }
            }
          }
          
          echo 'Subscription sync successful.';

        }catch(\Exception $e) {
            echo $e->getMessage(); //add custom message
        }  
    }
    
    private function main_product_sub_row($order, $sub = false) {
        $installments = $order->sub_installments;
		if ($installments == '-1') {
		    $installments = '&infin;';
		}
        ?>
        <tr class="item">

            <?php 
            if ( has_post_thumbnail( $order->product_id ) ) {
                echo '<td class="thumb">';
                    echo '<div class="sc-order-item-thumbnail">';
                    echo get_the_post_thumbnail( $order->product_id, 'thumbnail' );
                    echo '</div>';
                echo '</td>';
            } else {
                echo '<td style="padding:0"></td>';
            }
            ?>

            <td class="name">
                <span style="opacity: 75%"><?php esc_html_e( 'Product ID: ', 'ncs-cart' ); ?><span id="product_ID"><?php echo $order->product_id; ?></span></span><br>
                <a href="<?php echo get_edit_post_link($order->product_id); ?>" class="sc-order-item-name"><?php echo $order->product_name; ?></a> 
                (<?php echo ($sub) ? $sub['plan']->name : $order->sub_item_name; ?>)
                <?php if ( $order->coupon_id && !in_array($order->coupon['type'], array('cart-percent', 'cart-fixed')) ) echo '<br><span class="badge">'.esc_html( 'Coupon: ', 'ncs-cart') . $order->coupon_id . '<span>'; ?>
 
            </td>
            <td><?php echo $order->quantity; ?></td>
            <td><?php echo $installments; ?></td>
            <?php if($sub): ?>
                <td>
                    <div class="view">
                        <span class="sc-Price-amount amount">
                            <?php sc_formatted_price($sub['plan']->price); ?>
                        </span> / 
                        <?php 
                        if($sub['plan']->frequency > 1) {
                            echo esc_html($sub['plan']->frequency . ' ' . sc_pluralize_interval($sub['plan']->interval));
                        } else {
                            echo esc_html($sub['plan']->interval);
                        }
                        ?>

                    </div>
                </td>
            <?php else: ?>
                <td>
                    <div class="view"><?php echo wp_specialchars_decode( $order->sub_payment, 'ENT_QUOTES' ); ?></div>
                </td>
            <?php endif; ?>
            <?php if ($order->status != 'canceled' && !$order->cancel_date): ?>
                <td>
                    <button type="button" class="button refund-items sc_unsubscribe_btn"><?php esc_html_e( 'Cancel Subscription', 'ncs-cart' ); ?></button>
                    <?php if ($order->status != 'paused'): ?>
                    <button type="button" class="button refund-items sc_pause_restart" data-action="paused" data-id="<?php echo $order->id; ?>"><?php esc_html_e( 'Pause', 'ncs-cart' ); ?></button>
                    <?php else: ?>
                    <button type="button" class="button refund-items sc_pause_restart" data-action="started" data-id="<?php echo $order->id; ?>"><?php esc_html_e( 'Resume', 'ncs-cart' ); ?></button>
                    <?php endif; ?>
                </td>
            <?php else: 
                $cancel_text = __('Canceled', 'ncs-cart');
                if ($order->cancel_date && $order->status != 'canceled'):
                    $cancel_text = __('Cancellation Pending', 'ncs-cart');
                endif; ?>
                <td>
                    <button type="button" class="button" disabled><?php esc_html_e( $cancel_text, 'ncs-cart' ); ?></button>
                </td>
            <?php endif; ?>
        </tr>

        <?php
    }

	public function subscription_form_callback( $post ){
        if( 'sc_subscription' !== $post->post_type || !isset($_GET['post']) ){
			return;
		}
        
        $order = new ScrtSubscription($post->ID);
        $order = (object) $order->get_data();

		$product_id = $order->product_id; 
		$installments = $order->sub_installments;
		if ($installments == '-1') {
		    $installments = '&infin;';
		}
		?>
		<?php  //$subID = $order->subscription_id' ); ?>
		<div class="sc-product-info sc-product-table meta-box-sortables ui-sortable">
			<table cellpadding="0" cellspacing="0" class="sc_order_items" width="100%">
				<thead>
					<tr>
						<th class="item" colspan="2"><?php esc_html_e( 'Item', 'ncs-cart' ); ?></th>
						<th class="line_cost"><?php esc_html_e( 'Qty', 'ncs-cart' ); ?></th>
                        <th class="line_cost"><?php esc_html_e( '# of Payments', 'ncs-cart' ); ?></th>
						<th class="line_cost"><?php esc_html_e( 'Recurring Payment', 'ncs-cart' ); ?></th>
                        <th></th>
					</tr>
				</thead>
				<tbody id="order_line_items">

                    <?php 
                    if(isset($order->main_product_sub) && $order->main_product_sub) { // Deprecated
                        if($order->main_product_sub) {
                            $sub = $order->main_product_sub; 
                            $this->main_product_sub_row($order, $sub);
                        } 
                    } elseif($order->sub_payment) {
                        
                        $this->main_product_sub_row($order);
                    } ?>
                    
                    <?php if(isset($order->tax_data) && is_object($order->tax_data) && $order->tax_desc): 
                        $redeem_tax = false;
                        if(isset($order->tax_data->redeem_vat) && $order->tax_data->redeem_vat): 
                            $redeem_tax = true;
                            if($order->tax_data->type != 'inclusive'):
                                $order->tax_amount = 0;
                                $order->tax_rate = "0";
                            endif;
                        endif; ?>
                        <tr class="item">                     
                            <td style="padding:0;"></td>
                            <td class="name" colspan="2">
                                <?php echo $order->tax_desc.' ('.$order->tax_rate.'%)'; ?>
                            </td>
                            <td style="padding:0;"></td>
                            <td colspan="2">
                                <div class="view">
                                    <?php sc_formatted_price($order->tax_amount); ?>
                                </div>
                            </td>
                        </tr>
                        <?php if($redeem_tax && $order->tax_data->type == 'inclusive'): ?>
                            <tr class="item">                     
                                <td style="padding:0;"></td>
                                <td class="name" colspan="2">
                                    <?php _e(get_option('_sc_vat_reverse_charge',"VAT Reversal"), 'ncs-cart') ?>
                                </td>
                                <td style="padding:0;"></td>
                                <td colspan="2">
                                    <div class="view">
                                        - <?php sc_formatted_price($order->tax_amount); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                        <tr class="item items-total" style="font-weight: bold">                     
                            <td style="padding:0;"></td>
                            <td class="name" colspan="2">
                                <?php _e('Total', 'ncs-cart') ?>
                            </td>
                            <td style="padding:0;"></td>
                            <td colspan="2">
                                <div class="view">
                                    <span class="sc-Price-amount amount">
                                        <?php sc_formatted_price($order->sub_amount); ?>
                                    </span>     
                                </div>
                            </td>
                        </tr>
				</tbody>
			</table>
		</div>
		<div class="sc-product-info sc-product-table meta-box-sortables ui-sortable">
			<div class="postbox">
				<h2><?php esc_html_e( 'Related Orders', 'ncs-cart' ); ?></h2>
			</div>	
			
			<table cellpadding="0" cellspacing="0" class="sc_order_items" width="100%">
					<thead>
						<tr>
							<th class="item"><?php esc_html_e( 'Order Number', 'ncs-cart' ); ?></th>
							<th class="item"><?php esc_html_e( 'Relationship', 'ncs-cart' ); ?></th>
							<th class="item"><?php esc_html_e( 'Date', 'ncs-cart' ); ?></th>
							<th class="item"><?php esc_html_e( 'Status', 'ncs-cart' ); ?></th>
							<th class="line_cost"><?php esc_html_e( 'Total', 'ncs-cart' ); ?></th>
						</tr>
					</thead>
					<tbody id="order_line_items">
						
						<?php
					    // The Query
						$args = array(
  							'post_type' => array( 'sc_order' ),
                            'orderby' => 'date',
                            'order'   => 'ASC',
                            'post_status' => array('any'),
                            'posts_per_page' => -1,
							//'post__not_in' => [get_the_ID()],
							'meta_query'=>array(
								array(
									'key' => '_sc_subscription_id',
									'value' => $post->ID,
								),
							),
						);
						$the_query = new WP_Query( $args );
                        $initial_order = true;

						// The Loop
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post(); 
                                $related_order = new ScrtOrder(get_the_ID()); ?>
								<tr>
									<td>
										<a href="<?php echo get_edit_post_link(get_the_ID()); ?>" class="sc-order-item-name">#<?php echo get_the_ID(); ?></a>
									</td>
									<td><?php echo $initial_order ? __('Initial Order', 'ncs-cart') : __('Renewal Order', 'ncs-cart'); ?>  </td>
									<td><?php echo get_the_date('F d, Y h:i a', get_the_ID()); ?></td>
									<td class="name"><?php echo $related_order->get_status(); ?></td>
									<td class="sub_cost">
										<div class="view">
											<span class="sc-Price-amount amount">
												<?php sc_formatted_price(get_post_meta( get_the_ID(), '_sc_amount', true )); ?>
											</span>
										</div>
									</td>
								</tr>
								<?php
                                $initial_order = false;
							}
						} 
						/* Restore original Post Data */
						wp_reset_postdata();
						
                        if (isset($order->ob_parent) || isset($order->us_parent) || isset($order->ds_parent)) {
                            if (isset($order->ob_parent)) {
                                $parent_id = $order->ob_parent;
                            } else if (isset($order->ds_parent)) {
                                $parent_id = $order->ds_parent;
                            } else {
                                $parent_id = $order->us_parent;
                            }
                            $parent_prod_id = get_post_meta( $parent_id , '_sc_product_id', true );
                            $show_related = true;
                        }
                        if (isset($parent_id) && $parent_id) : ?>
                            <?php $parent = new ScrtOrder($parent_id); ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_edit_post_link($parent_id); ?>" class="sc-order-item-name">#<?php echo $parent_id; ?></a>
                                </td>
                                <td><?php echo __('Parent Order', 'ncs-cart' ) ?></td>
                                <td><?php echo get_the_date('F d, Y h:i a', $parent_id); ?></td>
                                <td class="name"><?php echo $parent->get_status(); ?></td>
                                <td class="sub_cost">
                                    <div class="view">
                                        <span class="sc-Price-amount amount">
                                            <?php sc_formatted_price(get_post_meta( $parent_id, '_sc_amount', true )); ?>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        endif;

                    ?>
						
					</tbody>
				</table>
		</div>
	<?php
	}

	//NC ORDER MODIFY ORDER post title
	function sc_modify_order_details( $data ){
		if( $data['post_type'] == 'sc_order' && isset( $_POST['_sc_firstname'] ) && isset( $_POST['post_ID'] ) ){			
			$order_title = sanitize_title("#" . $_POST['post_ID'] . " " . $_POST['_sc_firstname'] . " " . $_POST['_sc_lastname']);
			$data['post_title'] =  $order_title ; //Updates the post title to your new title.			
		}

		
		return $data; // Returns the modified data.
	}
    
	public function sc_mailchimp_groups_tags(){
        $groups = get_option('sc_mailchimp_groups');
        
        foreach($groups as $k=>$v){
            if($k == $_POST['id']) {
                //var_dump($v);
                $opts = '<option> ' . __('Select', 'ncs-cart') . ' </option>';
                foreach($v as $id=>$label){
                    echo $id . ' - ' . $label;
                    $opts = '<option id="'.$id.'">'.$label.'</option>';
                }
            }
            echo $opts;
            wp_die();
        }
        echo '<option>' . __('No groups found', 'ncs-cart') . '</option>';
        wp_die();
    }

    //REFUND CUSTOMER
    public function sc_order_refund(){       
        global $wpdb;   
        //print_r($_POST); exit();

        if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce') || $_POST['refund_amount'] === '' ) {
            esc_html_e('Oops, something went wrong, please try again later.', 'ncs-cart');
            die;
        }

        $_POST['refund_amount'] = ( $_POST['refund_amount'] === '0' ) ? 0 : $this->sanitizer( 'price', $_POST['refund_amount'] );
        $data = $_POST;
          
        echo sc_order_refund($data);

        wp_die();
    }

	public static function sc_refund_log($postID,$amount,$refundID){       
        $logs_entrie = get_post_meta( $postID, '_sc_refund_log', true);
        if(!is_array($logs_entrie)) {
            $logs_entrie = array(); 
        }
        $logs_entrie[]= array(
                            'refundID' => $refundID,
                            'date' => date('Y-m-d H:i'),
                            'amount' => $amount );

        update_post_meta( $postID, '_sc_refund_log', $logs_entrie );
    }

    private function sanitizer( $type, $data ) {

		if ( empty( $type ) ) { return; }
		if ( empty( $data ) ) { return; }

		$return 	= '';
		$sanitizer 	= new NCS_Cart_Sanitize();

		$sanitizer->set_data( $data );
		$sanitizer->set_type( $type );

		$return = $sanitizer->clean();

		unset( $sanitizer );

		return $return;

	} // sanitizer()

  
    public function sc_paypal_cancel_subscription(){

        $access_token = $this->sc_paypal_oauthtoken();
        
        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );

         $paypalUrl = ($enableSandbox != 'disable') ? 'https://api.sandbox.paypal.com/v1/billing/subscriptions/' : 'https://api.paypal.com/v1/billing/subscriptions/';

        $subscription_id = trim($_POST['subscription_id']);
        $post_id = intval($_POST['id']);

        $chs = curl_init();
        $reason= array('reason'=> 'Not satisfied with the service');
        $reasons = json_encode($reason);
        curl_setopt($chs, CURLOPT_URL, $paypalUrl.$subscription_id.'/cancel');
        curl_setopt($chs, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($chs, CURLOPT_POST, 1);
        curl_setopt($chs, CURLOPT_POSTFIELDS, $reasons);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($chs, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($chs);
        if (curl_errno($chs)) {
            echo 'Error:' . curl_error($chs);
        }
        curl_close($chs);

        $results = json_decode($result);
        if(empty($results)){
            return true;
        }else{	
			if(isset($results->message)) {
            	esc_html_e($results->message, 'ncs-cart');
			} else if(isset($results->error_description)) {
				esc_html_e($results->error_description, 'ncs-cart');
			}
            wp_die();
        }        
    }
    
    public function sc_paypal_oauthtoken(){ 

        $enableSandbox = get_option( '_sc_paypal_enable_sandbox' );
        if(get_option( '_sc_paypal_enable_sandbox' ) == 'enable'){
            $paypalurl =  'https://api-m.sandbox.paypal.com/v1/oauth2/token';
            $clientID = get_option( '_sc_paypal_sandbox_client_id' );
            $secret = get_option( '_sc_paypal_sandbox_secret' );
        }else{
            $paypalurl = 'https://api-m.paypal.com/v1/oauth2/token'; 
            $clientID = get_option( '_sc_paypal_client_id' ); 
            $secret = get_option( '_sc_paypal_secret' );
        } 

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $paypalurl );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
        curl_setopt($ch, CURLOPT_USERPWD, $clientID . ':' . $secret);

        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Accept-Language: en_US';
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);
        $results = json_decode($result);
         return $access_token=$results->access_token;
    }
    public function sc_paypal_refund(){        

        $access_token = $this->sc_paypal_oauthtoken();

        $postID = intval($_POST['id']);
        $prodID = intval($_POST['pid']);
        $payment_intent = trim($_POST['payment_intent']);
        $sc_currency = get_option( '_sc_currency' );
        $amount = $_POST['refund_amount'];
         if(get_option( '_sc_paypal_enable_sandbox' ) == 'enable'){
            $refundurl = 'https://api.sandbox.paypal.com/v1/payments/sale/';
         }else{
           $refundurl = 'https://api.paypal.com/v1/payments/sale/'; 
         }        
        $amount= array('amount' => array('total'=> $amount, 'currency' => $sc_currency), 'description'=> 'Defective product');
        $amounts = json_encode($amount);
       
        $ch1 = curl_init();

        curl_setopt($ch1, CURLOPT_URL, $refundurl.$payment_intent.'/refund');
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch1, CURLOPT_POST, 1);
        curl_setopt($ch1, CURLOPT_POSTFIELDS, $amounts);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Authorization: Bearer '.$access_token;
        curl_setopt($ch1, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch1);
        if (curl_errno($ch1)) {
            echo 'Error:' . curl_error($ch1);
        }
        curl_close($ch1);        
        $respons = json_decode($result); 
        //print_r($respons);exit();            
        if($respons->state == 'completed'){
            $amount = $_POST['refund_amount']; 
            update_post_meta( $postID, '_sc_payment_status' , "refunded" );
            wp_update_post( array( 'ID'   =>  $postID, 'post_status'   =>  "refunded" ) );
            update_post_meta( $postID, '_sc_status' , "refunded" );
            update_post_meta( $postID, '_sc_refund_amount' , $amount);
           //update_post_meta( $postID, '_sc_remaining_amount' , $ramount);
            $refundID = $respons->id;
            self::sc_refund_log($postID,$amount,$refundID); 
            
            $current_user = wp_get_current_user();
            $log_entry = __( 'Payment refunded by', 'ncs-cart' ) . ' ' . $current_user->user_login;
            sc_log_entry($postID, $log_entry);
            if($_POST['restock'] == 'YSE'){
                sc_maybe_update_stock( $prodID, 'increase' );
                 update_post_meta( $postID, '_sc_refund_restock' , 'YES');
            }
            //sc_maybe_update_stock( $prodID, 'increase' );
            sc_trigger_integrations('refunded', $postID);
            
            esc_html_e('OK', 'ncs-cart');
        }else{
            esc_html_e($respons->message, 'ncs-cart');
            wp_die;
        }
        
    }

	public function sc_get_payment_options(){
		global $wpdb;
        $options = '<option value="">' . __('Select Payment Plan', 'ncs-cart') . '</option>';
        
		if( !isset( $_POST['productId'] ) && empty( $_POST['productId'] )){
			esc_html_e('error', 'ncs-cart');
			wp_die;
		}
		// Verify nonce
		if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce' ) ){
			esc_html_e('error', 'ncs-cart');
			die();
		}
		
		$sc_product_id = intval($_POST['productId']);
		
		//GET PRODUCT PAYMENT OPTIONS get_post_meta
		$items = get_post_meta($sc_product_id, '_sc_pay_options' );
		if( $items ) :
			foreach ($items as $pay_options ) {
                foreach ( $pay_options as $value ) {
                    $item_id  = $value['option_id'];
                    $item_name  = $value['option_name'] ?? $item_id;
                    $sale_item_name    = $value['sale_option_name'] ?? $value['option_name'] . ' (on sale)';;
                    $options .= "<option value='{$item_id}'>{$item_name}</option>";
                    
                    if(isset($value['sale_price'])){
                        $options .= "<option value='{$item_id}_sale'>{$sale_item_name}</option>";
                    }
                }
			}
		endif;
        
		//print_r($options);
		wp_send_json_success($options);
		wp_die();
	}
	
	public function sc_load_edit_php_action() {
		if (isset($_GET['post_type']) && $_GET['post_type'] == 'sc_order' && isset($_GET['subscription_related_orders']) ){
            add_action( 'pre_get_posts', array('NCS_Cart_Admin', 'sc_order_related_orders_filter') );
        }else
        if (isset($_GET['post_type']) && $_GET['post_type'] == 'sc_order' && isset($_GET['order_email']) ){
            add_action( 'pre_get_posts', array('NCS_Cart_Admin', 'sc_order_email_filter') );
        }
	}

	public function sc_order_related_orders_filter($query) {	  
		//$current_meta = $query->get('meta_query');
		$custom_meta = array(
			'key' => '_sc_subscription_id',
			'value' => sanitize_text_field($_GET['subscription_related_orders']),
			'compare' => '=='
		);
		//$meta_query = $current_meta[] = $custom_meta;
    	$query->set('meta_query', array($custom_meta));
		
		return $query;

	}
    public function sc_order_email_filter($query) { 
        
        $custom_meta = array(
            'key' => '_sc_email',
            'value' => sanitize_text_field($_GET['order_email']),
            'compare' => '=='
        );
        //$meta_query = $current_meta[] = $custom_meta;
        $query->set('meta_query', array($custom_meta));
        
        return $query;

    }
    public function sc_renew_integrations_lists() {
        
        if( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sc-ajax-nonce' ) ){
			esc_html_e('Ooops, something went wrong, please try again later.', 'ncs-cart');
			die;
		}
		$mc_lists 	= $this->get_mailchimp_lists(true);
        $mc_groups 	= $this->get_mailchimp_groups(true);
        $mc_tags	= $this->get_mailchimp_tags(true);
        $ck_forms 	= $this->get_convertkit_forms(true);
        $ck_tags 	= $this->get_convertkit_tags(true);
        $ac_lists 	= $this->get_activecampaign_lists(true);
		$ac_tags 	= $this->get_activecampaign_tags(true);
        $sf_lists 	= $this->get_sendfox_lists(true);
        do_action('sc_renew_integrations_lists');
        
        esc_html_e("OK", 'ncs-cart');
        wp_die();
    }
	
	public function set_custom_edit_sc_product_columns($columns) {
		$columns['shortcode'] = __( 'Shortcode', 'ncs-cart' );

		return $columns;
	}

	public function custom_sc_product_column( $column, $post_id ) {
		switch ( $column ) {

			case 'shortcode' :
				echo '<code>['.apply_filters('studiocart_slug', 'studiocart') . '-form'.' id='.$post_id.']</code>';
				break;

		}
	}
    
    public function set_custom_edit_sc_order_columns($columns) {
		unset( $columns['title'], $columns['author'],$columns['date'] );
		$columns['order'] = __( 'Order #', 'ncs-cart' );
		$columns['order_date'] = __( 'Date', 'ncs-cart' );
		$columns['amount'] = __( 'Amount', 'ncs-cart' );
		$columns['status'] = __( 'Status', 'ncs-cart' );
		$columns['product'] = __( 'Product', 'ncs-cart' );
		$columns['name'] = __( 'Name', 'ncs-cart' );
		$columns['email'] = __( 'Email', 'ncs-cart' );
        if(get_option('_sc_enable_invoice_number',false)){
		    $columns['invoice_number'] = __( 'Invoice Number', 'ncs-cart' );
        }

		return $columns;
	}

	public function custom_sc_order_column( $column, $post_id ) {
        
        $order = new ScrtOrder($post_id);
        $order = apply_filters('studiocart_order', $order);
        $order = $order->get_data();
        
		switch ( $column ) {
			case 'order' :
				echo edit_post_link( $post_id );
				break;
			case 'status' :
				echo '<span class="sc-status '.$order['status'].'">'.$order['status_label'].'</span>';
				break;
			case 'name' :
                if ($order['user_account']) { 
                    $user_id = $order['user_account'] ?>
                    <a href="<?php echo get_edit_user_link($user_id); ?>">
                        <?php echo $order['customer_name']; ?>
                    </a>
                <?php } else {
				    echo $order['customer_name'];
                }
				break;
			case 'email' :
				echo '<a href="mailto:'.$order['email'].'" target="_blank" rel="noopener noreferrer">'.$order['email'].'</a>';
				break;
			case 'order_date' :
				echo get_the_time( 'M j, Y', $post_id );
				break;
			case 'amount' :
				 if ($order['status'] == 'refunded') {
                    $refund_logs_entrie = get_post_meta( $post_id, '_sc_refund_log', true);
                    $total_amount = $order['amount'];
                    if(is_array($refund_logs_entrie)) {
                       $refund_amount = array_sum(array_column($refund_logs_entrie, 'amount'));
                       $total_amount -= $refund_amount;
                    } 
                    echo '<s>' . sc_format_price($order['amount']) . '</s> ' . sc_format_price($total_amount);
                } else {
                    echo sc_format_price($order['amount']);
                }
				break;
			case 'product' :
                echo '<a href="'.get_edit_post_link($order['product_id']).'" class="sc-order-item-name">'.$order['product_name'].'</a>';
				break;
            case 'invoice_number':
                $invoice_number = get_post_meta($post_id,'_sc_invoice_number',true);
                echo $invoice_number;
                break;
		}
	}
	
	public function set_custom_edit_sc_subscription_columns($columns) {
		unset( $columns['title'], $columns['author'],$columns['date'] );
		$columns['sub_id'] = __( 'Sub ID', 'ncs-cart' );
		$columns['status'] = __( 'Status', 'ncs-cart' );
		$columns['amount'] = __( 'Amount', 'ncs-cart' );
		$columns['start_date'] = __( 'Start Date', 'ncs-cart' );
		$columns['next_payment'] = __( 'Next Payment', 'ncs-cart' );
		$columns['end_date'] = __( 'End Date', 'ncs-cart' );
		$columns['product'] = __( 'Product', 'ncs-cart' );
		$columns['name'] = __( 'Name', 'ncs-cart' );
		$columns['email'] = __( 'Email', 'ncs-cart' );
		$columns['orders'] = __( 'Orders', 'ncs-cart' );
		return $columns;
	}

	public function custom_sc_subscription_column( $column, $post_id ) {
        $scsub = new ScrtSubscription($post_id);
            $sub = $scsub->get_data();
        
		switch ( $column ) {

			case 'sub_id' :
				echo edit_post_link( $post_id );
				break;
			case 'status' :
				echo '<span class="sc-status '.$sub['status'].'">'.$sub['status_label'].'</span>';
				break;
			case 'name' :
				if ($sub['user_account']) { 
                    $user_id = $sub['user_account'] ?>
                    <a href="<?php echo get_edit_user_link($user_id); ?>">
                        <?php echo $sub['customer_name']; ?>
                    </a>
                <?php } else {
				    echo $sub['customer_name'];
                }
                break;
			case 'email' :
				echo '<a href="mailto:'.$sub['email'].'" target="_blank" rel="noopener noreferrer">'.$sub['email'].'</a>';
				break;
			case 'start_date' :
				echo $sub['start_date'];
				break;
			case 'next_payment' :
                echo ($sub['next_pay_date']) ? $sub['next_pay_date'] : '-';
				break;
			case 'end_date' :
                echo ($sub['end_date']) ? $sub['end_date'] : '-';
				break;
			case 'orders' :
				echo $scsub->order_count();
                break;
			case 'amount' :
                echo $sub['sub_payment'];
				break;
			case 'product' :
                $pid = $sub['product_id'];
                echo '<a href="'.get_edit_post_link($pid).'" class="sc-order-item-name">'.$sub['product_name'].'</a>';
				break;
		}
	}
	
	public function sc_order_sortable_columns( $columns ) {
		$columns['order'] = 'ID';
		$columns['order_date'] = 'date';
		$columns['start_date'] = 'date';
		$columns['email'] = 'email';
		$columns['amount'] = 'amount';

		//To make a column 'un-sortable' remove it from the array
		//unset($columns['date']);

		return $columns;
	}
	
	function sc_order_sortable_columns_orderby( $query ) {
		if( ! is_admin() )
			return;

		$orderby = $query->get( 'orderby');

		if( 'email' == $orderby ) {
			$query->set('meta_key', '_sc_email');
			$query->set('orderby','meta_value');
		}
		
		if( 'amount' == $orderby ) {
			$query->set('meta_key', '_sc_amount');
			$query->set('orderby','meta_value_num');
      		$query->set( 'meta_type', 'numeric' );
			//print_r($wpdb->last_query); die;
		}
	}

    public function sc_order_bulk_action($bulk_array){
        $bulk_array[ 'sc_make_paid' ] = __( 'Update status to paid', 'ncs-cart');
        $bulk_array[ 'sc_make_failed' ] = __( 'Update status to failed', 'ncs-cart');
        $bulk_array[ 'sc_make_pending' ] = __( 'Update status to pending', 'ncs-cart');
        $bulk_array[ 'sc_make_completed' ] = __( 'Update status to completed', 'ncs-cart');
        return $bulk_array;
    }

    public function sc_subscription_bulk_action($bulk_array){
        $bulk_array[ 'sc_make_active' ] = __( 'Update status to active', 'ncs-cart');
        $bulk_array[ 'sc_make_canceled' ] = __( 'Update status to canceled', 'ncs-cart');
        $bulk_array[ 'sc_make_completed' ] = __( 'Update status to completed', 'ncs-cart');
        return $bulk_array;
    }

    public function sc_bulk_action_handler($redirect, $doaction, $object_ids){
        $action = array( 'bulk_sc_make_paid', 
            'bulk_sc_make_failed', 
            'bulk_sc_make_pending', 
            'bulk_sc_make_active',
            'bulk_sc_make_canceled',
            'bulk_sc_make_completed',
        );
        $redirect = remove_query_arg(
            $action,
            $redirect
        );
        if ( in_array('bulk_'.$doaction,$action ) ) {
            $status_to = str_replace("sc_make_","",$doaction);
            foreach ( $object_ids as $post_id ) {
                $post_type = get_post_type($post_id);
                switch($post_type){
                    case "sc_order":
                        $sc_order = new ScrtOrder($post_id);
                        $sc_order->status = $status_to;
                        ScrtOrder::update($sc_order);
                    break;
                    case "sc_subscription":
                        $sc_subscription = new ScrtSubscription($post_id);
                        $sc_subscription->status = $status_to;
                        ScrtSubscription::update($sc_order);
                    break;
                    default:
                    break;
                }
            }
            $redirect = add_query_arg(
                'bulk_'.$doaction, // just a parameter for URL
                count( $object_ids ), // how many posts have been selected
                $redirect
            );
        }
        return $redirect;
        
    }
	
	public function sc_order_custom_status() {
        
        register_post_status( 'pending-payment', array(
			'label'                     => __( 'Pending', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription', 'sc_order' ),
			'label_count'               => _n_noop( 'Pending <span class="count">(%s)</span>', 'Pending <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'paid', array(
			'label'                     => __( 'Paid', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_order' ),
			'label_count'               => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>' ),
		) );
		
		register_post_status( 'refunded', array(
			'label'                     => __( 'Refunded', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_order' ),
			'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'failed', array(
			'label'                     => __( 'Failed', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_order'),
			'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'past_due', array(
			'label'                     => __( 'Past Due', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_order', 'sc_subscription'),
			'label_count'               => _n_noop( 'Past Due <span class="count">(%s)</span>', 'Past Due <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'uncollectible', array(
			'label'                     => __( 'Uncollectible', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_order' ),
			'label_count'               => _n_noop( 'Uncollectible <span class="count">(%s)</span>', 'Uncollectible <span class="count">(%s)</span>' ),
		) );

		register_post_status( 'active', array(
			'label'                     => __( 'Active', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription' ),
			'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>' ),
		) );
		
		register_post_status( 'unpaid', array(
			'label'                     => __( 'Unpaid', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription', 'sc_order' ),
			'label_count'               => _n_noop( 'Unpaid <span class="count">(%s)</span>', 'Unpaid <span class="count">(%s)</span>' ),
		) );
		
		register_post_status( 'paused', array(
			'label'                     => __( 'Paused', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription' ),
			'label_count'               => _n_noop( 'Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>' ),
		) );
		
		register_post_status( 'canceled', array(
			'label'                     => __( 'Canceled', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription' ),
			'label_count'               => _n_noop( 'Canceled <span class="count">(%s)</span>', 'Canceled <span class="count">(%s)</span>' ),
		) );
		
		register_post_status( 'completed', array(
			'label'                     => __( 'Completed', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription', 'sc_order' ),
			'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'incomplete', array(
			'label'                     => __( 'Incomplete', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription' ),
			'label_count'               => _n_noop( 'Incomplete <span class="count">(%s)</span>', 'Incomplete <span class="count">(%s)</span>' ),
		) );
        
        register_post_status( 'trialing', array(
			'label'                     => __( 'Trialing', 'ncs-cart' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'post_type'                 => array( 'sc_subscription' ),
			'label_count'               => _n_noop( 'Trialing <span class="count">(%s)</span>', 'Trialing <span class="count">(%s)</span>' ),
		) );
	}
	
	public function sc_order_remove_statuses($views) {
		$remove_views = [ 'mine','publish','future','sticky','draft','pending' ];

		foreach( (array) $remove_views as $view )
		{
			if( isset( $views[$view] ) )
				unset( $views[$view] );
		}
		return $views;
	}
    
    public function show_user_profile_address_fields( $user ) {
        $address = sc_get_user_address($user->ID);
        $address['phone'] = sc_get_user_phone($user->ID);
        unset($address['address']);
        
        $labels = array(
            'address_1' => esc_html__( 'Address', 'ncs-cart' ),
            'address_2' => esc_html__( 'Address Line 2', 'ncs-cart' ),
            'city' => esc_html__( 'City', 'ncs-cart' ),
            'state' => esc_html__( 'State', 'ncs-cart' ),
            'zip' => esc_html__( 'Zip', 'ncs-cart' ),
            'country' => esc_html__( 'Country', 'ncs-cart' ),
            'phone' => esc_html__( 'Phone', 'ncs-cart' ),
        );
        ?>

        <h3><?php esc_html_e( 'Studiocart', 'ncs-cart' ); ?></h3>

        <table class="form-table">
        <?php foreach ($labels as $key=>$label): 
            $address[$key] = ($address[$key]) ?? '';
            $k = '_sc_'.$key; ?>
            <tr>
                <th><label for="<?php echo $k; ?>"><?php echo $label; ?></label></th>
                <td>
                    <input type="text"
                       id="<?php echo $k; ?>"
                       name="<?php echo $k; ?>"
                       value="<?php echo esc_attr( $address[$key] ) ?? ''; ?>"
                       class="regular-text"
                    />
                </td>
            </tr>
        <?php endforeach; ?>
        </table>
        <?php
    }
    
    public function update_profile_address_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return false;
        }
        
        $fields = array(
            'address_1',
            'address_2',
            'city',
            'state',
            'zip',
            'country',
            'phone',
        );

        foreach($fields as $field) {
            if ( ! empty( $_POST['_sc_'.$field] ) ) {
                update_user_meta( $user_id, '_sc_'.$field, sanitize_text_field( $_POST['_sc_'.$field]) );
            }
        }
    }
}
