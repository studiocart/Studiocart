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
class NCS_Cart_Admin_Settings {

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
        
        add_filter( 'admin_body_class', array($this,'admin_body_class') );

	}
    
    public function admin_body_class( $classes ) {
        global $current_screen;
        $screen = get_admin_page_parent();
        if ( 'studiocart' == $screen || strpos($current_screen->base,'sc-white-label') !== false)
            $classes .= ' studiocart-admin-page ';
        return $classes;
    }

	/**
	 * This function introduces the plugin options into a top-level
	 * 'CreativCart' menu.
	 */
	public function setup_plugin_options_menu() {
        
        global $submenu;

		// Top-level page
		// add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );

		// Submenu Page
		// add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function);
        
		add_menu_page(
			apply_filters('studiocart_plugin_title',$this->plugin_title), 					// The title to be displayed in the browser window for this page.
			apply_filters('studiocart_plugin_title',$this->plugin_title),					// The text to be displayed for this menu item
			'sc_manager_option',					// Which type of users can see this menu item
			'studiocart',			// The unique ID - that is, the slug - for this menu item
			array( $this, 'render_settings_page_content'),				// The name of the function to call when rendering this menu's page
			'',
			30
		);
		
		//Add taxonomy menu to the Studiocart menu
		add_submenu_page(
			'studiocart', 
			__('Categories', 'ncs-cart'), 
			__('Categories', 'ncs-cart'),
			'sc_manager_option', 
			'edit-tags.php?taxonomy=sc_product_cat'
		); 
		
		add_submenu_page(
			'studiocart', 
			__('Tags','ncs-cart'), 
			__('Tags','ncs-cart'), 
			'sc_manager_option', 
			'edit-tags.php?taxonomy=sc_product_tag'
		);
        
        add_submenu_page(
			'studiocart',
			apply_filters( $this->plugin_name . '-settings-page-title', sprintf(esc_html__( '%s Settings', 'ncs-cart' ), apply_filters('studiocart_plugin_title',$this->plugin_title)) ),
			apply_filters( $this->plugin_name . '-settings-menu-title', esc_html__( 'Settings', 'ncs-cart' ) ),
			'manage_options',
			'sc-admin',
			array( $this, 'page_options' )
		);
        
        // hide templates link if white labeled
        if(!get_option('_sc_wl_enable') || get_option('_sc_wl_show_resources')) {
            add_submenu_page(
                'studiocart',
                apply_filters( $this->plugin_name . '-settings-page-title', esc_html__( 'Resources &amp; Documentation', 'ncs-cart' ) ),
                apply_filters( $this->plugin_name . '-settings-menu-title', esc_html__( 'Resources', 'ncs-cart' ) ),
                'sc_manager_option',
                'sc-docs',
                array( $this, 'render_help_page_content' )
            );
        }
	}
	
	/**
	 * This function highlights the correct top level menu item
	 *when viewing a plugin taxonomy.
	 */
	public function taxonomy_menu_highlight($parent_file) {
		
		global $current_screen;

        $taxonomy = $current_screen->taxonomy;
        if ( $taxonomy == 'sc_product_cat' || $taxonomy == 'sc_product_tag' ) {
            $parent_file = 'studiocart';
        }

        return $parent_file;
	}

	/**
	 * Renders a simple page to display for the theme menu defined above.
	 */
	public function render_settings_page_content( $active_tab = '' ) {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">

			<?php settings_errors(); ?>
            
            <h2><?php esc_html_e( apply_filters('studiocart_plugin_title',$this->plugin_title), 'ncs-cart' ); ?></h2>

			<div class="sc-getting-started">
				<div class="sc-getting-started__box postbox">
					<div class="sc-getting-started__content">
						<div class="sc-getting-started__content--narrow">
							<h2><?php printf(esc_html__( 'Welcome to %s', 'ncs-cart' ), apply_filters('studiocart_plugin_title',$this->plugin_title)) ?></h2>
							<p><?php esc_html_e( "To get started, head over to the settings page to connect your Stripe account and select your currency. Once that's done, you'll be ready to create your first product!", 'ncs-cart' ); ?></p>
						</div>

						<div class="sc-getting-started__actions e-getting-started__content--narrow">
                            <a href="<?php echo admin_url( 'admin.php?page=sc-admin' ); ?>" class="button button-primary button-hero"><?php esc_html_e( 'Get Started!', 'ncs-cart' ); ?></a>
						</div>
					</div>
				</div>
			</div>

		</div><!-- /.wrap -->
	<?php
	}

	/**
	 * Renders a help and documentation page.
	 */
	public function render_stacking_form( $active_tab = '' ) {
        global $sc_fs;
        $license = $sc_fs->_get_license();
        $user = $license->user_id;
        $licenseid = $license->id; 
        ?>
        <iframe src="https://studiocart.co/stack-codes/?userid=<?php echo $user; ?>&licenseid=<?php echo $licenseid; ?>" width="590" height="740" style="max-width: 100%;"></iframe>
    <?php
    }
        
    /**
	 * Renders a help and documentation page.
	 */
	public function render_help_page_content( $active_tab = '' ) {
		?>
		<!-- Create a header in the default WordPress 'wrap' container -->
		<div class="wrap">

			<?php settings_errors(); ?>
            
            <h2><?php esc_html_e( 'Resources &amp; Documentation', 'ncs-cart' ); ?></h2>

			<div class="sc-documentation">
				<div class="sc-getting-started__box postbox onethird">
					<div class="sc-getting-started__content">
						<div class="sc-getting-started__content--narrow">
							<h2><?php printf(esc_html__( 'Getting Started with %s', 'ncs-cart' ), apply_filters('studiocart_plugin_title',$this->plugin_title)); ?></h2>
							<p><?php esc_html_e( "Learn how to get your store up and running with our video course.", 'ncs-cart' ); ?></p>
						</div>

						<div class="sc-getting-started__actions e-getting-started__content--narrow">
                            <a href="https://www.studiocart.co/courses/getting-started-with-studiocart-video-course/lesson/creating-your-first-product/" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero"><?php esc_html_e( 'Get Started!', 'ncs-cart' ); ?></a>
						</div>
					</div>
				</div>
                <div class="sc-getting-started__box postbox onethird">
					<div class="sc-getting-started__content">
						<div class="sc-getting-started__content--narrow">
							<h2><?php esc_html_e( 'Join our Facebook Group', 'ncs-cart' ); ?></h2>
							<p><?php esc_html_e( "A community where Studiocart users can connect, share ideas, and support one another.", 'ncs-cart' ); ?></p>
						</div>

						<div class="sc-getting-started__actions e-getting-started__content--narrow">
                            <a href="https://www.facebook.com/groups/studiocart" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero"><?php esc_html_e( 'Join Facebook Group', 'ncs-cart' ); ?></a>
						</div>
					</div>
				</div>
                <div class="sc-getting-started__box postbox onethird">
					<div class="sc-getting-started__content">
						<div class="sc-getting-started__content--narrow">
							<h2><?php esc_html_e( 'Template Library', 'ncs-cart' ); ?></h2>
							<p><?php esc_html_e( "Get designer-quality templates for your funnel pages. Just add your copy and branding.", 'ncs-cart' ); ?></p>
						</div>

						<div class="sc-getting-started__actions e-getting-started__content--narrow">
                            <a href="https://studiocart.co/template-library/" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero"><?php esc_html_e( 'Get Templates', 'ncs-cart' ); ?></a>
						</div>
					</div>
				</div>
                <!--<div class="sc-getting-started__box postbox onethird">
					<div class="sc-getting-started__content">
						<div class="sc-getting-started__content--narrow">
							<h2><?php esc_html_e( 'Feature Request?', 'ncs-cart' ); ?></h2>
							<p><?php esc_html_e( "Make a feature request by leaving a comment on our public roadmap.", 'ncs-cart' ); ?></p>
						</div>

						<div class="sc-getting-started__actions e-getting-started__content--narrow">
                            <a href="https://trello.com/b/zoxaGrnB/studiocart-roadmap" target="_blank" rel="noopener noreferrer" class="button button-primary button-hero"><?php esc_html_e( 'View Roadmap', 'ncs-cart' ); ?></a>
						</div>
					</div>
				</div>-->
                <div class="sc-getting-started__box postbox twothirds">
                    <h2><?php esc_html_e( 'Create your first product in less than 5 minutes', 'ncs-cart' ); ?></h2>
					<div class="videoWrapper">
                        <iframe width="100%" height="315" src="https://www.youtube.com/embed/3HMf1zHZ5Uw" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
				</div>
                <div class="sc-getting-started__box postbox onethird">
					<div class="sc-getting-started__content user-docs">
						<div class="sc-getting-started__content--narrow">
							<h2><?php esc_html_e( 'User Docs', 'ncs-cart' ) ?></h2>
							<p><?php esc_html_e( "Check out our Docs and Tutorials for getting started with Studiocart", 'ncs-cart' ); ?></p>
						</div>

						<ul>
                            <li><a href="https://studiocart.co/docs/products/adding-and-managing-products/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Adding and Managing Products", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/products/embed-an-order-form-anywhere/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Embedding order forms anywhere", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/templates/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Using templates", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/how-tos/how-to-translate-studiocart-into-another-language/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Translate Studiocart into another language", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/getting-started/available-shortcodes/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Available Shortcodes", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/how-tos/white-labeling-2/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "How to use white-labeling", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/getting-started/managing-orders/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Managing Orders", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/getting-started/manage-subscriptions/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Manage subscriptions", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/subscriptions/creating-recurring-payment-plans-in-stripe/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Creating Recurring Payment Plans in Stripe", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/getting-started/how-to-set-up-paypal/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "How to set-up PayPal", 'ncs-cart' ); ?></a></li>
                            <li><a href="https://studiocart.co/docs/integrations/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( "Integrations", 'ncs-cart' ); ?></a></li>
                        </ul>
					</div>
				</div>
			</div>

		</div><!-- /.wrap -->
	<?php
	}
    
    /**
	 * Creates the options page
	 *
	 * @since 		1.0.2
	 * @return 		void
	 */
	public function page_options() {

        if ( isset( $_REQUEST['sc_reset_log'] ) ) {
            check_admin_referer( 'sc_reset_debug_log', 'sc_reset_debug_log_nonce' );
            global $sc_debug_logger;
            $sc_debug_logger->reset_log_file();
            echo '<div id="message" class="updated fade"><p>Debug log files have been reset!</p></div>';
        }

		include( plugin_dir_path( __FILE__ ) . 'partials/ncs-cart-admin-page-settings.php' );
	} // page_options()
	/**
	 * Registers settings sections with WordPress
	 */
	public function register_sections() {
		add_settings_section(
			$this->plugin_name . '-settings',
			apply_filters( $this->plugin_name . 'section-title-settings', esc_html__( 'Settings', 'ncs-cart' ) ),
			array( $this, 'section_settings' ),
			$this->plugin_name
		);
        $this->register_integration_tab_section();
        $this->register_payment_gateway_tab_section();
        $this->register_email_tab_section();
        $this->register_tax_tab_section();
        $this->register_invoice_tab_section();
        
        do_action('sc_register_sections');
	} // register_sections()
    /**
	 * Registers Integration Tab settings sections with WordPress
	 */
    public function register_integration_tab_section(){
        $intigrations = array(  'activecampaign'=>'ActiveCampaign',
                                'convertkit'=>'ConvertKit',
                                'mailchimp'=>'Mailchimp',
                                'membervault'=>'MemberVault',
                                'sendfox'=>'SendFox',
                                'fbads'=>'Enable Facebook Ad Events',
                                'ga'=>'Google Analytics Purchase Event Tracking',
                        );
        $intigrations = apply_filters( '_sc_integrations_tab_section', $intigrations );
        foreach($intigrations as $intigration_key => $intigration):
            add_settings_section(
                $this->plugin_name . '-'.$intigration_key,
                apply_filters( $this->plugin_name . 'section-title-'.$intigration_key, esc_html__( $intigration, 'ncs-cart' ) ),
                array( $this, 'section_settings' ),
                $this->plugin_name . '-integrations'
            );
        endforeach;
        do_action('_sc_register_sections',$this,$this->plugin_name);
    }
    /**
	 * Registers Payment Gateway Tab settings sections with WordPress
	 */
    public function register_payment_gateway_tab_section(){
        $payment_gateways = array(  'cashondelivery'=>'Cash on Delivery',
                                'stripe'=>'Stripe',
                                'paypal'=>'PayPal',
                        );
        $payment_gateways = apply_filters( '_sc_payment_gateway_tab_section', $payment_gateways);
        foreach($payment_gateways as $payment_gateway_key=>$payment_gateway):
            add_settings_section(
                $this->plugin_name . '-' . $payment_gateway_key,
                apply_filters( $this->plugin_name . 'section-title-'.$payment_gateway_key, esc_html__( $payment_gateway, 'ncs-cart' ) ),
                array( $this, 'section_settings' ),
                $this->plugin_name.'-payment'
            );  
        endforeach;
        do_action('_sc_register_gateways',$this,$this->plugin_name.'-payment');
    }
    /**
	 * Registers Email Tab settings sections with WordPress
	 */
    public function register_email_tab_section(){
        $emails = array(
                            'email_settings'=>'Settings',
                            'emailtemplate_pending'=> esc_html__('Order Received Confirmation', 'ncs-cart' ),
                            'emailtemplate_purchase'=> esc_html__('Purchase Confirmation', 'ncs-cart' ),
                            'emailtemplate_registration'=>esc_html__('New User Welcome', 'ncs-cart' ),
                            'emailtemplate_refunded'=>esc_html__('Order Refunded', 'ncs-cart' ),
                            'emailtemplate_trial_ending'=>esc_html__('Trial Ending Reminder', 'ncs-cart' ),
                            'emailtemplate_reminder'=>esc_html__('Upcoming Renewal Reminder', 'ncs-cart' ),
                            'emailtemplate_renewal'=>esc_html__('Subscription Renewal Confirmation', 'ncs-cart' ),
                            'emailtemplate_failed'=>esc_html__('Subscription Renewal Failed', 'ncs-cart' ),
                            'emailtemplate_canceled'=>esc_html__('Subscription Canceled Confirmation', 'ncs-cart' ),
                            'emailtemplate_paused'=>esc_html__('Subscription Paused Confirmation', 'ncs-cart' ),
                        );
        $emails = apply_filters( '_sc_emails_tab_section', $emails);

        foreach($emails as $email_key =>$email):
            add_settings_section(
                $this->plugin_name . '-'.$email_key,
                apply_filters( $this->plugin_name . 'section-title-'.$email_key, esc_html__( $email, 'ncs-cart' ) ),
                array( $this, 'section_settings' ),
                $this->plugin_name.'-email'
            );
        endforeach;
        /*add_settings_section(
            $this->plugin_name . '-emailtemplate_abandonment',
            apply_filters( $this->plugin_name . 'section-title-emailtemplate', esc_html__( 'Abandonment Email', 'ncs-cart' ) ),
            array( $this, 'section_settings' ),
            $this->plugin_name
        );*/
    }

    /**
	 * Registers Tax Tab settings sections with WordPress
	 */
    public function register_tax_tab_section(){
        $taxes = array(  'tax-setting'=>'Tax Options',
                        );
        $taxes = apply_filters( '_sc_taxes_tab_section', $taxes);
        foreach($taxes as $tax_key =>$tax):
            add_settings_section(
                $this->plugin_name . '-'.$tax_key,
                apply_filters( $this->plugin_name . 'section-title-'.$tax_key, esc_html__( $tax, 'ncs-cart' ) ),
                array( $this, 'section_settings' ),
                $this->plugin_name.'-tax'
            );
        endforeach;
    }

     /**
	 * Registers Tax Tab settings sections with WordPress
	 */
    public function register_invoice_tab_section(){
        $invoices = array(  'invoice-setting'=>'Invoice Options',
                        );
        $invoices = apply_filters( '_sc_invoice_tab_section', $invoices);
        foreach($invoices as $invoice_key =>$invoice):
            add_settings_section(
                $this->plugin_name . '-'.$invoice_key,
                apply_filters( $this->plugin_name . 'section-title-'.$invoice_key, esc_html__( $invoice, 'ncs-cart' ) ),
                array( $this, 'section_settings' ),
                $this->plugin_name.'-invoice'
            );
        endforeach;
    }

    /**
	 * Returns an array of options names, fields types, and default values
	 *
	 * @return 		array 			An array of options
	 */
    
	public function get_options_list() {
        $to_email   = get_option( 'admin_email' );
        $currencies = get_sc_currencies();
        foreach ( $currencies as $code => $name ) {
            $symbols = get_sc_currency_symbols();
			$currencies[ $code ] = $name . ' (' . $symbols[$code] . ')';
		}
        $options = array(
            'settings' => array(
                'currency' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Currency', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_currency',
                        'value' 		=> 'USD',
                        'selections' 	=> $currencies,
                    ),
                ),
                'country' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Default Country', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_country',
                        'value' 		=> 'US',
                        'selections' 	=> sc_countries_list(),
                    ),
                ),
                'currency-position' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Currency Position', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_currency_position',
                        'value' 		=> 'USD',
                        'selections' 	=> [''=>'Left','right'=>'Right','left-space'=>'Left with space','right-space'=>'Right with space'],
                    ),
                ),
                'thousand' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Thousand Separator', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_thousand_separator',
                        'value' 		=> ',',
                        'description' 	=> '',
                    ),
                ),
                'decimal' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Decimal Separator', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_decimal_separator',
                        'value' 		=> '.',
                        'description' 	=> '',
                    ),
                ),
                'decimal_num' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Number of Decimals', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_decimal_number',
                        'value' 		=> intval(get_option( '_sc_decimal_number' )),
                        'description' 	=> esc_html__( 'The number of decimal points shown in prices.', 'ncs-cart' ),
                    ),
                ),
                'my-account' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'My Account Page', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_myaccount_page_id',
                        'value' 		=> '',
                        'selections' 	=> NCS_Cart_Admin_Settings::get_pages(),
                    ),
                ),
                'my-account-hide-free' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Hide free orders in My Account', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_myaccount_hide_free',
                        'value' 		=> '',
                        'description'   => esc_html__( 'Turn on to hide free orders from the "Orders" tab in My Account', 'ncs-cart' ),
                    ),
                ),
                'company_name' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Company Name', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_company_name',
                        'value'         => '',
                        'description'   => '',
                    ),
                ),
                'company_address' => array(
                    'type'          => 'textarea',
                    'label'         => esc_html__( 'Company Address', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_company_address',
                        'value'         => '',
                        'description'   => '',
                    ),
                ),
                'company_logo' => array(
                    'type'          => 'upload',
                    'label'         => esc_html__( 'Logo Image', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_company_logo',
                        'value'         => '',
                        'description'   => '',
                        'field-type'	=> 'url',
                        'label-remove'	=> __('Remove Image','ncs-cart'),
                        'label-upload'	=> __('Set Image','ncs-cart'),
                    ),
                ),
                'terms-url' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Terms and Conditions URL', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_terms_url',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'privacy-url' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Privacy Policy URL', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_privacy_url',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'schedule-email' => array(
                    'type'          => 'email',
                    'label'         => esc_html__( 'Email reports and confirmations to', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => 'sc_admin_email',
                        'placeholder' 		=> $to_email,
                        'description' 	=> '',
                        'required'      => true,
                    ),
                ),
                'schedule-event' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Email Report Schedule', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => 'sc_report_schedule',
                        'selections' 	=> ['mt_none'=>'None','mt_daily'=>'Daily','mt_weekly'=>'Weekly','mt_semi_monthly'=>'Semi monthly'],
                    ),
                ),
            ),         
            'email_settings' => array(
                'email_from_name' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( '"From" Name', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_from_name',
                        'value'         => get_bloginfo('name'),
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'email_from_email' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( '"From" Email', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_from_email',
                        'value'         => get_bloginfo('admin_email'),
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'email_footer_text' => array(
                    'type'          => 'textarea',
                    'label'         => esc_html__( 'Footer text', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_footer_text',
                        'value'         => get_bloginfo('name') . ' &mdash; Built with {Studiocart}',
                        'description'   => '',
                        'rows'=>2,
                        'columns'=>6
                    ),
                    'tab'=>'email'
                ),
                'email_preview' => array(
                    'type'          => 'html',
                    'label'         => esc_html__( 'Test email', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => 'email_preview',
                        'description'   => '
                        <select id="sc-email-type">
                            <option value="confirmation">'.__('-- Select --', 'ncs-cart').'</option>
                            <option value="pending">'.__('Order Received', 'ncs-cart').'</option>
                            <option value="registration">'.__('New User Welcome', 'ncs-cart').'</option>
                            <option value="confirmation">'.__('Purchase Confirmation', 'ncs-cart').'</option>
                            <option value="refunded">'.__('Order Refunded', 'ncs-cart').'</option>
                            <option value="trial_ending">'.__('Trial Ending Reminder', 'ncs-cart').'</option>
                            <option value="reminder">'.__('Upcoming Renewal Reminder', 'ncs-cart').'</option>
                            <option value="renewal">'.__('Subscription Renewal Confirmation', 'ncs-cart').'</option>
                            <option value="failed">'.__('Failed Renewal Payment', 'ncs-cart').'</option>
                            <option value="canceled">'.__('Subscription Canceled Confirmation', 'ncs-cart').'</option>
                            <option value="paused">'.__('Subscription Paused Confirmation', 'ncs-cart').'</option>
                        </select>
                        <a id="sc-preview-email" class="button" href="'.site_url( '/?sc-preview=email&type=[confirmation]&_wpnonce='.wp_create_nonce( 'sc-cart')).'" target="_blank">'.__('Preview Email', 'ncs-cart').'</a> 
                        <a id="sc-email-send" class="button" href="#">'.__('Send Test', 'ncs-cart').'</a>
                        <p class="description">'.__('Personalization tags will not render in test emails sent from this page.','ncs-cart').'</p>
                        ',
                    ),
                    'tab'=>'email'
                ),
            ),
            'emailtemplate_pending' => array(
                'order_pending_enable' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_pending_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'order_pending_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_pending_subject',
                        'value'         => 'Your order received confirmation from {site_name}',
                        'description'   => '',
                        'placeholder'   => ''
                    ),
                    'tab'=>'email'
                ),
                'order_pending_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_pending_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'order_pending_headline' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Headline', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_pending_headline',
                        'value'         => '',
                        'placeholder'   => 'Your order has been received!',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'order_pending_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body Text', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_pending_body',
                        'value'         => '',
                        'placeholder'   => '',
                        'description'   => '',
                        'show_tags'     => true,
                        'rows'          => 2,

                    ),
                    'tab'=>'email'
                ),           
            ),
            'emailtemplate_purchase' => array(
                'purchase_confirmation_enable' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_confirmation_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'purchase_confirmation_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_confirmation_subject',
                        'value'         => 'Your order confirmation from {site_name}',
                        'description'   => '',
                        'placeholder'   => ''
                    ),
                    'tab'=>'email'
                ),
                'purchase_confirmation_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_confirmation_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'purchase_confirmation_headline' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Headline', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_confirmation_headline',
                        'value'         => '',
                        'placeholder'   => 'Thank you for your order!',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'purchase_confirmation_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body Text', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_confirmation_body',
                        'value'         => '',
                        'placeholder'   => '',
                        'description'   => '',
                        'show_tags'     => true,
                        'rows'          => 2,

                    ),
                    'tab'=>'email'
                ),           
            ),
            'emailtemplate_failed' => array(
                'subscription_failed_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_failed_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_failed_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_failed_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_failed_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_failed_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_failed_email' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_failed_body",
                        'value'         => '',
                        'show_tags'     => true,
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
            ),
            'emailtemplate_canceled' => array(    
               'subscription_canceled_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_canceled_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_canceled_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_canceled_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_canceled_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_canceled_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_canceled_email' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Subscription Canceled', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_canceled_body",
                        'value'         => '',
                        'show_tags'     => true,
                       'description'   => '',
                    ),
                    'tab'=>'email'
                ),
            ), 
            'emailtemplate_paused' => array(    
               'subscription_paused_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_paused_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_paused_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_paused_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_paused_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_paused_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_paused_email' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Subscription Paused', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_paused_body",
                        'value'         => '',
                        'show_tags'     => true,
                       'description'   => '',
                    ),
                    'tab'=>'email'
                ),
            ),
            'emailtemplate_trial_ending' => array(
                'subscription_trial_ending_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_trial_ending_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_trial_ending_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_trial_ending_subject',
                        'value'         => 'Your {product_name} trial is about to end',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_trial_ending_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_trial_ending_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ), 
                'subscription_trial_ending_days' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Send', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_trial_ending_days',
                        'value' 		=> '1',
                        'selections' 	=> array('1'=>'1 day before trial ends',
                                                '2'=>'2 days before trial ends',
                                                '3'=>'3 days before trial ends',
                                                '4'=>'4 days before trial ends',
                                                '5'=>'5 days before trial ends',
                                                '6'=>'6 days before trial ends',
                                                '7'=>'7 days before trial ends',
                                            ),
                    ),
                    'tab'=>'email'
                ),
                'subscription_trial_ending_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_trial_ending_body",
                        'description'         => '',
                        'value'   => 'Hi {customer_firstname},

                        Your trial is ending on <strong>{next_bill_date}</strong>. Once your trial is complete, we will automatically charge the payment method we have on file for <strong>{order_amount}</strong>.
                        
                        To cancel your trial, please log in to <a href="{login}">your account</a>.
                        
                        Thank you,
                        {site_name}',
                        'show_tags'     => true,
                    ),
                    'tab'=>'email'
                ),
            ),
            'emailtemplate_reminder' => array(
                'subscription_reminder_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_reminder_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_reminder_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_reminder_subject',
                        'value'         => 'Upcoming payment reminder for {product_name}',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_reminder_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_reminder_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ), 
                'subscription_reminder_days' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Send', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_reminder_days',
                        'value' 		=> '1',
                        'selections' 	=> array('1'=>'1 day before next renewal',
                                                '2'=>'2 days before next renewal',
                                                '3'=>'3 days before next renewal',
                                                '4'=>'4 days before next renewal',
                                                '5'=>'5 days before next renewal',
                                                '6'=>'6 days before next renewal',
                                                '7'=>'7 days before next renewal',
                                            ),
                    ),
                    'tab'=>'email'
                ),
                'subscription_reminder_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_reminder_body",
                        'value'         => 'Hi {customer_firstname},

                        Just a friendly reminder that we will be automatically charging the payment method we have on file for <strong>{order_amount}</strong> on <strong>{next_bill_date}</strong>.
                        
                        If you have any questions regarding your upcoming payment, please don\'t hesitate to get in touch.
                        
                        Thank you,
                        {site_name}',
                        'description'   => '',
                        'show_tags'     => true,
                    ),
                    'tab'=>'email'
                ),
            ),
            'emailtemplate_renewal' => array(
                'subscription_renewal_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_renewal_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_renewal_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_renewal_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_renewal_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_renewal_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ), 
                'subscription_renewal_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_renewal_body",
                        'value'         => '',
                        'description'   => '',
                        'show_tags'     => true,
                    ),
                    'tab'=>'email'
                ),
            ),
            /*'emailtemplate_abandonment' => array(
                'subscription_abandonment_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_abandonment_notification',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_abandonment_from_name' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'From Name', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_abandonment_from_name',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_abandonment_from_email' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'From Email', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_abandonment_from_email',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_abandonment_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_abandonment_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_abandonment_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_abandonment_email_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),   
                'subscription_abandonment_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Cart Abandonment', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_abandonment_email_body",
                        'value'         => '',
                        'description'   => 'Available Variables [FIRST_NAME] [LAST_NAME] [LOGIN_LINK]',
                    ),
                    'tab'=>'email'
                ),                 
            ),*/            
            'emailtemplate_registration' => array(
                'subscription_registration_enable' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_registration_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_registration_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_registration_subject',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'subscription_registration_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_registration_email_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),   
                'subscription_registration_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_registration_email_body",
                        'value'         => '',
                        'show_tags'     => true,
                       'description'   => '',
                    ),
                    'tab'=>'email'
                ),                 
            ), 
            'emailtemplate_refunded' => array(
                'order_refund_notification' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_refunded_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'order_refund_subject' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_refunded_subject',
                        'value'         => 'Refund from {site_name}',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ),
                'order_refund_email_admin' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_email_refunded_admin',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'email'
                ), 
                'order_refund_body' => array(
                    'type'          => 'editor',
                    'label'         => esc_html__( 'Body', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => "_sc_email_refunded_body",
                        'value'         => "Order ID: {order_id}
Order Date: {order_date}

You're receiving this email because we have processed your refund of {last_refund_amount}. It can take up to 10 days to appear on your statement, if it takes longer please contact your bank for assistance.",
                        'description'   => '',
                        'show_tags'     => true,
                    ),
                    'tab'=>'email'
                ),
            ), 
        );

        if ( !sc_fs()->is__premium_only() && !sc_fs()->can_use_premium_code()) {
            $options['emailtemplate_paused'] = $options['emailtemplate_reminder'] = $options['emailtemplate_trial_ending'] = array(
                'email_upgrade_message' => array(
                    'type'          => 'html',
                    'label'         => esc_html__( 'Enable/Disable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_disable_template',
                        'value' 		=> '',
                        'description' 	=> __('Available in Studiocart Pro!', 'ncs-cart'). sprintf(' <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', 'https://studiocart.co/pricing', __('Upgrade now.', 'ncs-cart')),
                    ),
                    'tab'=>'email'
                )
            );

            $settingOptions = array( '_sc_email_reminder_enable', '_sc_email_trial_ending_enable', '_sc_email_paused_enable' ); // etc
            // Clear up our settings
            foreach ( $settingOptions as $settingName ) {
                delete_option( $settingName );
            }
        
            unset($options['settings']['invoice_notes']);
            unset($options['settings']['invoice_footer']);
        }
        
        $payment_options = $this->get_payment_fields();
        $integration_options = $this->get_integration_fields();
        $tax_options = $this->get_tax_fields();
        $invoice_option = $this->get_invoice_fields();
        $options = array_merge($options,$payment_options,$integration_options,$tax_options,$invoice_option);
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code()) {
            $options['settings']['api-key'] = array(
                'type'          => 'text',
                'label'         => esc_html__( 'Your API Key', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_api_key',
                    'value' 		=> '',
                    'description' 	=> '',
                    'readonly'      => true
                ),
            );
            $options['settings']['coupon-url'] = array(
                'type'          => 'text',
                'label'         => esc_html__( 'URL Coupon Parameter Name', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_coupon_url',
                    'value' 		=> '',
                    'description' 	=> sprintf(__('Use something else in place of "coupon" when creating coupon URLs (e.g., %s).', 'ncs-cart'), site_url( '/product/?coupon=20off')),
                ),
            );
            $options['settings']['product-template'] = array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Disable product template', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_disable_template',
                    'value' 		=> '',
                    'description' 	=> __("Use your theme's default page template for products.", 'ncs-cart'),
                ),
            );
            $options['settings']['whitelabel'] = array(
                'type'          => 'html',
                'label'         => esc_html__( 'White Label', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_disable_template',
                    'value' 		=> '',
                    'description' 	=> sprintf('<a href="%s" rel="noopener noreferrer">%s</a>', admin_url('admin.php?page=sc-white-label'), __('Manage', 'ncs-cart')),
                ),
            );
        }
        return apply_filters('_sc_option_list',$options);
	} // get_options_list()

    /**
	 * Adding Tax Fields to the settings
	 *
	 * @return 	array 						Array of the tax fields with settings 
     * 
	 */
    public function get_tax_fields(){
        $tax_fields = array('tax-setting'=>array(
            'tax-enable' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Enable Tax', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_tax_enable',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'tax'
            ),
            'tax-type' => array(
                'type'          => 'select',
                'label'         => esc_html__( 'Price Entered', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_tax_type',
                    'value' 		=> 'inclusive_tax',
                    'selections' 	=> array('inclusive_tax'=>'prices inclusive of tax',
                                            'exclusive_tax'=>'prices exclusive of tax',
                                        ),
                ),
                'tab'=>'tax'
            ),
            'tax-price-row' => array(
                'type'          => 'select',
                'label'         => esc_html__( 'Show Price', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_price_show_with_tax',
                    'value' 		=> 'exclude_tax',
                    'selections' 	=> array('exclude_tax'=>'Show Price and Tax Indvidually',
                                            'include_tax'=>'Show Price and Tax Together',
                                        ),
                ),
                'tab'=>'tax'
            ),
            'vat-enable' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Enable VAT', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_vat_enable',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'tax'
            ),
            'merchant-vat-country' => array(
                'type'          => 'select',
                'label'         => esc_html__( 'Merchant VAT Country', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_vat_merchant_state',
                    'value' 		=> 'AL',
                    'selections' 	=> sc_vat_countries_list() 
                ),
                'tab'=>'tax'
            ),
            'vat-all-eu-businesses' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Tax all EU Businesses', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_vat_all_eu_businesses',
                    'value'         => '',
                    'description'   => '',
                    
                ),
                'tab'=>'tax'
            ),
            'vat-disable-vies-database-lookup' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Disable VAT VIES database lookup', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_vat_disable_vies_database_lookup',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'tax'
            ),
            'vat-reverse-charge' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Reverse Charge Description', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_vat_reverse_charge',
                    'value'         => esc_html__('VAT Reversal', 'ncs-cart'),
                    'description'   => '',
                ),
                'tab'=>'tax'
            ),
        ));
        return $tax_fields;
    }

    public function get_invoice_fields(){
        $invoice_fields = array('invoice-setting'=>array(
            'enable_invoice_number' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Custom Invoice Numbering', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_enable_invoice_number',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_attach_pending_email' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Attach invoice to order received emails', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_attach_pending_email',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_attach_confirmation_email' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Attach invoice to purchase confirmation emails', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_attach_confirmation_email',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_attach_renewal_email' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Attach invoice to subscription renewal emails', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_attach_renewal_email',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_prefix' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Invoice Prefix', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_prefix',
                    'value'         => '',
                    'note'   => 'Use any of the <a href="https://www.studiocart.co/docs/general/invoice-settings/" target="_blank">date formats</a> or custom alphanumeric characters',
                ),
                'tab'=>'invoice'
            ),
            'invoice_sufix' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Invoice Suffix', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_sufix',
                    'value'         => '',
                    'note'   => 'Use any of the <a href="https://www.studiocart.co/docs/general/invoice-settings/" target="_blank">date formats</a> or custom alphanumeric characters',
                ),
                'tab'=>'invoice'
            ),
            'invoice_length' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Invoice Length', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_length',
                    'value'         => '0',
                    'note'   => 'Indicate total length of the invoice number, excluding the prefix and suffix',
                ),
                'tab'=>'invoice'
            ),
            'invoice_format' => array(
                'type'          => 'select',
                'label'         => esc_html__( 'Invoice Format', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_format',
                    'value'         => 'sc_pns',
                    'selections' 	=> array('sc_pns'=>'[prefix][number][suffix]',
                                        'sc_pn'=>'[prefix][number]',
                                        'sc_ns'=>'[number][suffix]',
                                        'sc_n'=>'[number]',
                                    ),
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_start_number' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Current Invoice Number', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_start_number',
                    'value'         => '1',
                    'description'   => '',
                    'readonly'      => true
                ),
                'tab'=>'invoice'
            ),
            'invoice_notes' => array(
                'type'          => 'textarea',
                'label'         => esc_html__( 'Invoice Notes/Terms', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_notes',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
            'invoice_footer' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Invoice Footer', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_invoice_footer',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'invoice'
            ),
        ));
        return apply_filters('_sc_invoice_option_list',$invoice_fields);
    }

    /**
	 * Adding Integration Fields to the settings
	 *
	 * @return 	array 						Array of the Integration fields with settings 
     * 
	 */

    public function get_integration_fields(){
        $integration_fields = array('mailchimp' => array(
                'mailchimp-api' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Mailchimp API Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_mailchimp_api',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                )
            ),
            'convertkit' => array(
                'convertkit-api' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'ConvertKit API Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_converkit_api',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                ),
                'convertkit-sk' => array(
                    'type'          => 'password',
                    'label'         => esc_html__( 'ConvertKit Secret Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_converkit_secret_key',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                )
            ),
            'activecampaign' => array(
                'activecampaign-url' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'ActiveCampaign URL', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_activecampaign_url',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                ),
                'activecampaign-sk' => array(
                    'type'          => 'password',
                    'label'         => esc_html__( 'ActiveCampaign Secret Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_activecampaign_secret_key',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                )
            ),
            'membervault' => array(
                'membervault-url' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'MemberVault URL', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_membervault_name',
                        'value'         => '',
                        'description'   => 'e.g. https://mysubdomain.vipmembervault.com',
                    ),
                    'tab'=>'integrations'
                ),
                'member-vault-api-key' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'MemberVault API Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_member_vault_api_key',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'integrations'
                )
            ),
            'sendfox' => array(
                'sendfox-api-key' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'SendFox API Key', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_sendfox_api_key',
                        'value'         => '',
                        'description'   => '',
                    ),
                    'tab'=>'integrations'
                )
            ),
            'fbads' => array(
                'pay_info_event' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Add Payment Info', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_fb_add_payment_info',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                ),
                'purchase_event' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Purchase Complete', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_fb_purchase',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                ),
            ),
            'ga' => array(
                'purchase_event' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_ga_purchase',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                    'tab'=>'integrations'
                ),
                'ga_type' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Type', 'ncs-cart' ),
                    'settings'      => array(
                        'note'      => '',
                        'id'            => '_sc_ga_type',
                        'value' 		=> 'test',
                        'selections' 	=> array(
                            '' => __('analytics.js', 'ncs-cart'),
                            'ga4' => __('Google Analytics 4', 'ncs-cart'),
                            'universal' => __('Universal Analytics', 'ncs-cart'),
                            'datalayer_ga4' => __('DataLayer with GA4', 'ncs-cart'),
                            'datalayer_enhanced' => __('DataLayer with Enhanced Ecommerce (UA)', 'ncs-cart'),
                        ),
                    ),
                    'tab'=>'integrations',
                ),
            ),
        );
        
        return apply_filters('_sc_integrations_option_list',$integration_fields);
    }

    /**
	 * Adding Payment Fields to the settings
	 *
	 * @return 	array 						Array of the Payment fields with settings 
     * 
	 */

    public function get_payment_fields(){
        $payment_fields = array('cashondelivery' => array(
                                    'cashondelivery-gateway' => array(
                                        'type'          => 'checkbox',
                                        'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                                        'settings'      => array(
                                            'id'            => '_sc_cashondelivery_enable',
                                            'value'         => '',
                                            'description'   => '',
                                        ),
                                        'tab'=>'payment',
                                    ),
                                ),
                            'stripe' => array(
                                'stripe-gateway' => array(
                                        'type'          => 'checkbox',
                                        'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                                        'settings'      => array(
                                            'id'            => '_sc_stripe_enable',
                                            'value'         => '', 
                                            'description'   => '',                           
                                        ),
                                        'tab'=>'payment',
                                ),
                                'stripe-descriptor' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Statement Descriptor', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_descriptor',
                                        'value' 		=> get_bloginfo( 'name' ),
                                        'note'   => esc_html__( 'Required. 22 character maximum, no special characters', 'ncs-cart' ),                          
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-api' => array(
                                    'type'          => 'select',
                                    'label'         => esc_html__( 'API', 'ncs-cart' ),
                                    'settings'      => array(
                                        'note'   => sprintf(esc_html__( 'GOING LIVE? Click the "Update" button on any subscription products created on the Test API after switching this setting to Live!<br><a href="%s" target="_blank" rel="noopener noreferrer">More Information</a>', 'ncs-cart' ), 'https://studiocart.co/docs/subscriptions/using-stripe-with-recurring-payment-plans/'),
                                        'id'            => '_sc_stripe_api',
                                        'value' 		=> 'test',
                                        'selections' 	=> array(
                                            'test' => __('Test', 'ncs-cart'),
                                            'live' => __('Live', 'ncs-cart')
                                        ),
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-live-pk' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Live Public Key', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_live_pk',
                                        'value' 		=> '',
                                        'description' 	=> '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-live-sk' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'Live Secret Key', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_live_sk',
                                        'value' 		=> '',
                                        'description' 	=> '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-test-pk' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Test Public Key', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_test_pk',
                                        'value' 		=> '',
                                        'description' 	=> '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-test-sk' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'Test Secret Key', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_test_sk',
                                        'value' 		=> '',
                                        'description' 	=> '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'stripe-inv-webhook-processing' => array(
                                    'type'          => 'checkbox',
                                    'label'         => esc_html__( 'Update subscription payment status in webhook only', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_stripe_invoice_webhook_updates_only',
                                        'value'         => '',
                                        'description' 	=> '',
                                    ),
                                    'tab'=>'payment',
                                ),
                            ),
                            'paypal' => array(
                                'paypal-gateway' => array(
                                    'type'          => 'checkbox',
                                    'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_enable',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-sandbox-enable' => array(
                                        'type'          => 'select',
                                        'label'         => esc_html__( 'Enable Sandbox', 'ncs-cart' ),
                                        'settings'      => array(
                                            'id'            => '_sc_paypal_enable_sandbox',
                                            'value'         => 'test',
                                            'selections'    => array(
                                                'enable' => __('Yes', 'ncs-cart'),
                                                'disable'  => __('No', 'ncs-cart')
                                            ),
                                        ),
                                        'tab'=>'payment',
                                ),
                                'paypal-email' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'PayPal Email', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_email',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-client-id' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Client ID', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_client_id',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-secret' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'Secret', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_secret',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-pdt-token' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'PDT Token', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_pdt_token',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-sandbox-email' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Sandbox Email', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_sandbox_email',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-sandbox-client-id' => array(
                                    'type'          => 'text',
                                    'label'         => esc_html__( 'Sandbox Client ID', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_sandbox_client_id',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-sandbox-secret' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'Sandbox Secret', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_sandbox_secret',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                                'paypal-sandbox-pdt-token' => array(
                                    'type'          => 'password',
                                    'label'         => esc_html__( 'Sandbox PDT Token', 'ncs-cart' ),
                                    'settings'      => array(
                                        'id'            => '_sc_paypal_sandbox_pdt_token',
                                        'value'         => '',
                                        'description'   => '',
                                    ),
                                    'tab'=>'payment',
                                ),
                            )
                        );
        return apply_filters('_sc_payment_field_option_list',$payment_fields);
    }

    /**
	 * Registers settings fields with WordPress
	 */

	public function register_fields() {
        $fields = $this->get_options_list();
        foreach($fields as $section => $sfields) {
            foreach($sfields as $k=>$v) {
                    $page = $this->plugin_name;
                    $v['settings']['section'] = $section;
                    if(!empty($v['tab'])){
                        $page = $this->plugin_name.'-'.$v['tab'];
                    }
                    add_settings_field(
                        $k,
                        apply_filters( $this->plugin_name . 'label-'.$k, $v['label'] ),
                        array( $this, 'field_'.$v['type'] ),
                        $page,
                        $this->plugin_name . '-'.$section,
                        $v['settings']
                    );
                    $callback = ($v['type']=='checkbox') ? array($this, 'sanitize_checkbox') : 'sanitize_text_field';
                    register_setting( 
                        $this->plugin_name . '-settings', 
                        $v['settings']['id'] 
                        //array('sanitize_callback' => $callback)
                    );
            }
        }
	} // register_fields()
    public function sanitize_checkbox($val) {
        $val = ($val) ? 1 : 0 ;
        return $val;
    }
    private static function get_pages(){
        $pages = get_pages();
        $options = array('' => __('Select Page', 'ncs-cart'));
        foreach ( $pages as $page ) {
            $options[$page->ID] = $page->post_title . ' (ID: '.$page->ID.')';
        }
        return $options;
	}
    /**
	 * Creates a settings section
	 *
	 * @since 		1.0.2
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function section_settings( $params ) {
	    // include( plugin_dir_path( __FILE__ ) . 'partials/ncs-cart-admin-section-messages.php' );
	} // section_messages()
    /**
	 * Creates a checkbox field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_checkbox( $args ) {
		$defaults['class'] 			= '';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $args['id'];
		$defaults['value'] 			= 0;
		apply_filters( $this->plugin_name . '-field-checkbox-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
		if ( $option_val = get_option( $atts['id'] ) ) {
			$atts['value'] = $option_val;
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-checkbox.php' );
	} // field_checkbox()
	/**
	 * Creates an editor field
	 *
	 * NOTE: ID must only be lowercase letter, no spaces, dashes, or underscores.
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_editor( $args ) {
        $defaults['description']    = '';
        $defaults['settings'] = array('wpautop' => true,  'textarea_name' => $args['id'] ,'textarea_rows' => 20 , 'media_buttons' => false, 'editor_css' => '', 'editor_class' => '','teeny' => true, );
        $defaults['value']          = '';
        apply_filters( $this->plugin_name . '-field-editor-options-defaults', $defaults );
        $atts = wp_parse_args( $args, $defaults );
        //print_r($this->options);
        $name = str_replace('_sc_', 'sc-', $atts['id']);
        $atts['name'] = str_replace('_','-',$name);
        $atts['name'] = $atts['id'];
        if (get_option($atts['id']) !="" ) {
            $atts['value'] = get_option($atts['id']);
        }   
        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-editor.php' );
    } // field_editor()
	/**
	 * Creates a set of radios field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_radios( $args ) {
		$defaults['class'] 			= '';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $this->plugin_name . '-options[' . $args['id'] . ']';
		$defaults['value'] 			= 0;
		apply_filters( $this->plugin_name . '-field-radios-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
		if ( ! empty( $this->options[$atts['id']] ) ) {
			$atts['value'] = $this->options[$atts['id']];
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-radios.php' );
	} // field_radios()
	public function field_repeater( $args ) {
		$defaults['class'] 			= 'repeater';
		$defaults['fields'] 		= array();
		$defaults['id'] 			= '';
		$defaults['label-add'] 		= 'Add Item';
		$defaults['label-edit'] 	= 'Edit Item';
		$defaults['label-header'] 	= 'Item Name';
		$defaults['label-remove'] 	= 'Remove Item';
		$defaults['title-field'] 	= '';
/*
		$defaults['name'] 			= $this->plugin_name . '-options[' . $args['id'] . ']';
*/
		apply_filters( $this->plugin_name . '-field-repeater-options-defaults', $defaults );
		$setatts 	= wp_parse_args( $args, $defaults );
        
		$count 		= 1;
        $repeater = get_option($setatts['id']);
		if ( ! empty( $repeater ) ) {
			$repeater = maybe_unserialize( $repeater );
		}
        if ( ! empty( $repeater ) ) {
            $fields = array();
            foreach($setatts['fields'] as $field):
                foreach ( $field as $atts ):
                    $fields[] = $atts['key'];
                endforeach;
            endforeach;
            $count = count($repeater[$fields[0]]);
            for($i=0;$i<$count;$i++){
                $inner_val = array();
                foreach($fields as $field):
                    $inner_val[$field] = $repeater[$field][$i]??'';
                endforeach;
                $new_val[$i] = $inner_val;
            }
            $repeater = $new_val;
            $repeater = array_map(array($this,'remove_repeater_blank'), $repeater);
            $repeater = array_filter( $repeater);
            $count = count( $repeater );
        }
        
        $setting_field = true;
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-repeater.php' );
	} // field_repeater()

    /**
	 * Fillter array remove empty value
	 *
	 *
	 * @param 	array 		$value 			The arguments for the field
	 * @return 	array 						return filtered array
     * 
     * 
	 */
    public function remove_repeater_blank($value){
        if(is_array($value)){
            foreach($value as $key => $val):
                if(empty($val)){
                    unset($value[$key]);
                }
            endforeach;
        }
        return $value;
    }

	/**
	 * Creates a select field
	 *
	 * Note: label is blank since its created in the Settings API
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_select( $args ) {
		$defaults['aria'] 			= '';
		$defaults['blank'] 			= '';
		$defaults['class'] 			= '';
		$defaults['context'] 		= '';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $args['id'];
		$defaults['selections'] 	= array();
		$defaults['value'] 			= '';
		apply_filters( $this->plugin_name . '-field-select-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
		if ( $option_val = get_option( $atts['id'] ) ) {
			$atts['value'] = $option_val;
		}
		if ( empty( $atts['aria'] ) && ! empty( $atts['description'] ) ) {
			$atts['aria'] = $atts['description'];
		} elseif ( empty( $atts['aria'] ) && ! empty( $atts['label'] ) ) {
			$atts['aria'] = $atts['label'];
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-select.php' );
	} // field_select()
	/**
	 * Creates a text field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_text( $args ) {
		$defaults['class'] 			= 'regular-text';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $args['id'];
		$defaults['placeholder'] 	= '';
		$defaults['type'] 			= 'text';
		$defaults['value'] 			= '';
		apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
        $option_val = get_option( $atts['id'] );
		if ( $option_val !== false ) {
			$atts['value'] = $option_val;
		}
        // set API key for subsites if missing
        if ( $atts['id']=='_sc_api_key' && !$atts['value'] ) {
            $apikey = hash( 'md5', wp_create_nonce( 'sc-cart') . date( 'U' ) );
            update_option( '_sc_api_key', $apikey );
            $atts['value'] = $apikey;
        }
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );
	} // field_text()
    public function field_email( $args ) {
		$defaults['class'] 			= 'regular-text';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $args['id'];
		$defaults['placeholder'] 	= '';
		$defaults['type'] 			= 'email';
		$defaults['value'] 			= '';
		apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
        $option_val = get_option( $atts['id'] );
		if ( $option_val !== false ) {
			$atts['value'] = $option_val;
		}
        // set API key for subsites if missing
        if ( $atts['id']=='_sc_api_key' && !$atts['value'] ) {
            $apikey = hash( 'md5', wp_create_nonce( 'sc-cart') . date( 'U' ) );
            update_option( '_sc_api_key', $apikey );
            $atts['value'] = $apikey;
        }
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );
	} // field_text()
    
    public function field_password( $args ) {
		$defaults['class'] 			= 'regular-text';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $args['id'];
		$defaults['placeholder'] 	= '';
		$defaults['type'] 			= 'password';
		$defaults['value'] 			= '';
		apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
		if ( $option_val = get_option( $atts['id'] ) ) {
			$atts['value'] = $option_val;
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );
	} // field_text()
    /**
	 * Creates a html field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_html( $args ) {
		$defaults['class'] 			= 'regular-text';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
		$atts = wp_parse_args( $args, $defaults );
        echo $atts['description'];
	} // field_text()
     public function field_color( $args ) {
        $defaults['class']          = 'regular-text';
        $defaults['description']    = '';
        $defaults['label']          = '';
        $defaults['name']           = $args['id'];
        $defaults['placeholder']    = '';
        $defaults['type']           = 'color';
        $defaults['value']          = '';
        apply_filters( $this->plugin_name . '-field-text-options-defaults', $defaults );
        $atts = wp_parse_args( $args, $defaults );
        if ( $option_val = get_option( $atts['id'] ) ) {
            $atts['value'] = $option_val;
        }
        // set API key for subsites if missing
        if ( $atts['id']=='_sc_api_key' && !$atts['value'] ) {
            $apikey = hash( 'md5', wp_create_nonce( 'sc-cart') . date( 'U' ) );
            update_option( '_sc_api_key', $apikey );
            $atts['value'] = $apikey;
        }
        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );
    } // field_text()
	/**
	 * Creates a textarea field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_textarea( $args ) {
        $defaults['class']          = 'regular-text';
        $defaults['cols']           = 50;
        $defaults['context']        = '';
        $defaults['description']    = '';
        $defaults['label']          = '';
        //$defaults['name']             = $this->plugin_name . '-options[' . $args['id'] . ']';
        $defaults['name']           =  $args['id'];
        $defaults['rows']           = 10;
        $defaults['value']          = '';
        apply_filters( $this->plugin_name . '-field-textarea-options-defaults', $defaults );
        $atts = wp_parse_args( $args, $defaults );
        //if ( ! empty( $this->options[$atts['id']] ) ) {
        if ($option_val = get_option( $atts['id'] )) {
            $atts['value'] = $option_val;
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-textarea.php' );
	} // field_textarea()
    
    /**
	 * The tax rate importer which extends WP_Importer.
	 */
	public function sc_tax_rates_importer() {
		require_once ABSPATH . 'wp-admin/includes/import.php';

		if ( ! class_exists( 'WP_Importer' ) ) {
			$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

			if ( file_exists( $class_wp_importer ) ) {
				require $class_wp_importer;
			}
		}

		require dirname( __FILE__ ) . '/importers/class-ncs-cart-tax-rate-importer.php';

		$importer = new NCS_Cart_Tax_Rate_Importer();
		$importer->dispatch();
	}
    
    public function field_upload( $args ) {
        $defaults['class']          = 'regular-text';
        $defaults['name']           =  $args['id'];
        $defaults['label']          =  '';
        $defaults['label-remove']   =  '';
        $defaults['label-upload']   =  '';
        $defaults['field-type']   =  'url';
        apply_filters( $this->plugin_name . '-field-textarea-options-defaults', $defaults );
        $atts = wp_parse_args( $args, $defaults );
        //if ( ! empty( $this->options[$atts['id']] ) ) {
        if ($option_val = get_option( $atts['id'] )) {
            $atts['value'] = $option_val;
		}
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-file-upload.php' );
	} // field_textarea()
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
}