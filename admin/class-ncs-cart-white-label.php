<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      2.1.29
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
class NCS_Cart_White_Label {

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
    
    private $section;
    private $page;
    private $option_group;
    private $studiocart;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_title, $version, $studiocart ) {

		$this->plugin_name = $plugin_name;
		$this->plugin_title = $plugin_title;
		$this->version = $version;
        $this->studiocart = $studiocart;
        
        $this->section = $this->plugin_name . '-wl-settings';
        $this->page = 'sc-white-label';
        $this->option_group = $this->plugin_name . '-wl-settings-options';
            
        add_action( 'admin_head', function() {
            remove_submenu_page( 'studiocart', $this->page );
        });
        
        $this->maybe_do_white_label();        
	}
    
    private function maybe_do_white_label() {

        if (!get_option('_sc_wl_enable')) {
            return;
        }
        
        add_filter( 'all_plugins', array($this, 'plugins_page' ));
        
        add_action( 'admin_head', function() {
            remove_submenu_page( 'studiocart', 'studiocart-account' );
            remove_submenu_page( 'studiocart', 'studiocart-affiliation' );
        });
        
        if (get_option('_sc_wl_name')) {
            add_filter('studiocart_plugin_title', function($label) {
                return get_option('_sc_wl_name');
            });
        }
        
        if (get_option('_sc_wl_hide')) {
            add_filter('_sc_option_list',function($options){
                unset($options['settings']['whitelabel']);
                return $options;
            });
        }
                        
        if ($icon = get_option('_sc_menu_image')) {
            add_action('admin_head', function() {
              echo '<style>
                .toplevel_page_studiocart > .wp-menu-image:before {
                  background: url('.get_option('_sc_menu_image').') no-repeat center;
                  background-size: contain;
                  text-indent: -999px;
                } 
              </style>';
            });
        } else if ($icon = get_option('_sc_menu_icon')) {
            add_action('admin_head', function() {
              echo '<style>
                .toplevel_page_studiocart > .wp-menu-image:before {
                  font-family: dashicons;
                  content: "'.get_option('_sc_menu_icon').'";
                } 
              </style>';
            });
        }
        
        add_action('admin_head', function() {
          echo '<style>
            .plugin-title .activate-license.studiocart,
            .fs-submenu-item.studiocart.account {
              display: none;
            } 
          </style>';
        });
        
        if ($author = get_option('_sc_wl_author_name')) {
            add_filter('plugin_row_meta', function($meta, $file, $data, $status) { 
                $author = get_option('_sc_wl_author_name');
                if ($url = get_option('_sc_wl_author_url')) {
                    $author = sprintf('<a href="%s">%s</a>', $url, $author);
                }
                if( $file === 'studiocart-pro/studiocart.php' ) {
                    $meta[1] = sprintf(__('By %s', 'ncs-cart'), $author);
                }
                unset($meta[2]);
                return $meta;
            }, 10, 4 );
        }

        
        if(get_option('_sc_wl_slug')) {
            // change shortcode label in backend
            add_filter('studiocart_slug', function($label) {
                return get_option('_sc_wl_slug');
            });

            // make white-labeled order form shortcode functional
            add_shortcode( get_option('_sc_wl_slug').'-form', function($atts){
                return $this->studiocart->sc_product_shortcode( $atts );
            });
            add_shortcode( get_option('_sc_wl_slug').'-receipt', function($atts){
                return $this->studiocart->sc_receipt_shortcode( $atts );
            });
        }
    }
    
    /**
     * Branding addon on the plugins page.
     *
     * @since 1.0.4
     * @param array $plugins An array data for each plugin.
     * @return array
     */
    public static function plugins_page( $plugins ) {

        $branding = self::get_branding();
        $basename = plugin_basename( NCS_CART_BASE_DIR . 'studiocart.php' );
        

        if ( isset( $plugins[ $basename ] ) && is_array( $branding ) ) {
            
            if ( $branding['name'] ) {
                $plugins[ $basename ]['Name']  = $branding['name'];
                $plugins[ $basename ]['Title'] = $branding['name'];
            }

            if ( $branding['description'] ) {
                $plugins[ $basename ]['Description'] = $branding['description'];
            }

            if ( $branding['author'] ) {
                $plugins[ $basename ]['Author']     = $branding['author'];
                $plugins[ $basename ]['AuthorName'] = $branding['author'];
            }

            if ( $branding['author_url'] ) {
                $plugins[ $basename ]['AuthorURI'] = $branding['author_url'];
                $plugins[ $basename ]['PluginURI'] = $branding['author_url'];
            }
        }

        return $plugins;
    }
    
    /**
		 * Returns Branding details for the plugin.
		 *
		 * @since 1.0.4
		 * @return array
		 */
		public static function get_branding() {

			$branding['name'] = get_option( '_sc_wl_name' );
			$branding['description'] = get_option( '_sc_wl_description' );
			$branding['author'] = get_option( '_sc_wl_author_name' );
			$branding['author_url'] = esc_url( get_option( '_sc_wl_author_url' ) );

			return $branding;
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
        
        add_submenu_page(
			'studiocart',
			apply_filters( $this->plugin_name . '-settings-page-title', esc_html__( 'White Label Settings', 'ncs-cart' ) ),
			'',
			'sc_manager_option',
			$this->page,
			array( $this, 'page_options' )
		);
        
	}
    
    /**
	 * Creates the options page
	 *
	 * @since 		1.0.2
	 * @return 		void
	 */
	public function page_options() {

		?><h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

        <form method="post" action="options.php"><?php

        settings_fields( $this->option_group );

        do_settings_sections( $this->page );

        submit_button( __('Save Settings', 'ncs-cart') );

        ?></form> <?php

	} // page_options()

	/**
	 * Registers settings sections with WordPress
	 */
	public function register_sections() {

		// add_settings_section( $id, $title, $callback, $menu_slug );

		add_settings_section(
			$this->plugin_name . '-wl-settings',
			'',
			array( $this, 'section_settings' ),
			$this->page
		);
        
        
	} // register_sections()

    
    /**
	 * Returns an array of options names, fields types, and default values
	 *
	 * @return 		array 			An array of options
	 */
	public function get_options_list() {
        
        $options = array(
            'sc-whitelist-options' => array(
                'sc_wl_enable' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Enable White Label', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_enable',
                        'value'         => '',
                        'description'   => '',
                    ),
                ),
                'sc_wl_hide' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Hide from Settings', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_hide',
                        'value'         => '',
                        'description'   => __('Hide the "White Label > Manage" link on the Settings page. (Bookmark this page before turning this setting on!)', 'ncs-cart'),
                    ),
                ),
                'sc_wl_show_resources' => array(
                    'type'          => 'checkbox',
                    'label'         => esc_html__( 'Keep Resources link visible', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_show_resources',
                        'value'         => '',
                        'description'   => __('Allow the Resources link to remain visible in the Studiocart menu with White Label turned on.', 'ncs-cart'),
                    ),
                ),
                'sc_wl_name' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Plugin Name', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_name',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'sc_wl_slug' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Shortcode Slug', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_slug',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'menu_icon' => array(
                    'type'          => 'select',
                    'label'         => esc_html__( 'Menu Icon (Dashicons)', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_menu_icon',
                        'value' 		=> '',
                        'selections' 	=> $this->dashicons_options(),
                    ),
                ),
                'menu_image' => array(
                    'type'          => 'upload',
                    'label'         => esc_html__( 'Menu Icon (Image)', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_menu_image',
                        'value'         => '',
                        'description'   => esc_html__( 'Overrides menu icon', 'ncs-cart' ),
                        'field-type'	=> 'url',
                        'label-remove'	=> __('Remove Image','ncs-cart'),
                        'label-upload'	=> __('Set Image','ncs-cart'),
                    ),
                ),
                'sc_wl_author_name' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Author Name', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_author_name',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'sc_wl_author_url' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Author URL', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_author_url',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
                'sc_wl_description' => array(
                    'type'          => 'text',
                    'label'         => esc_html__( 'Plugin Description', 'ncs-cart' ),
                    'settings'      => array(
                        'id'            => '_sc_wl_description',
                        'value' 		=> '',
                        'description' 	=> '',
                    ),
                ),
            ),
        );
        return $options;
        
	} // get_options_list()
    
    /**
	 * Registers settings fields with WordPress
	 */
	public function register_fields() {
        
        $fields = $this->get_options_list();
        foreach($fields as $section => $sfields) {
            foreach($sfields as $k=>$v) {
        		
                
                // add_settings_field( $id, $title, $callback, $menu_slug, $section, $args );
                add_settings_field(
                    $k,
                    apply_filters( $this->plugin_name . 'label-'.$k, $v['label'] ),
                    array( $this, 'field_'.$v['type'] ),
                    $this->page,
                    $this->plugin_name . '-wl-settings',
                    $v['settings']
                );
                
                $callback = ($v['type']=='checkbox') ? array($this, 'sanitize_checkbox') : 'sanitize_text_field';

                register_setting( 
                    $this->option_group, 
                    $v['settings']['id'], 
                    array('sanitize_callback' => $callback)
                );
            }
            
        }
        
	} // register_fields()
    
    public function sanitize_checkbox($val) {
        $val = ($val) ? 1 : 0 ;
        return $val;
    }
    
    /**
	 * Creates a settings section
	 *
	 * @since 		1.0.2
	 * @param 		array 		$params 		Array of parameters for the section
	 * @return 		mixed 						The settings section
	 */
	public function section_settings( $params ) {
         include( plugin_dir_path( __FILE__ ) . 'partials/ncs-cart-admin-section-messages.php' );
	} // section_messages()

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

		if ( $option_val = get_option( $atts['id'] ) ) {

			$atts['value'] = $option_val;

		}
        
		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );

	} // field_text()
    
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
	 * Creates a textarea field
	 *
	 * @param 	array 		$args 			The arguments for the field
	 * @return 	string 						The HTML field
	 */
	public function field_textarea( $args ) {

		$defaults['class'] 			= 'regular-text';
		$defaults['cols'] 			= 2;
		$defaults['context'] 		= '';
		$defaults['description'] 	= '';
		$defaults['label'] 			= '';
		$defaults['name'] 			= $this->plugin_name . '-options[' . $args['id'] . ']';
		$defaults['rows'] 			= 2;
		$defaults['value'] 			= '';

		apply_filters( $this->plugin_name . '-field-textarea-options-defaults', $defaults );

		$atts = wp_parse_args( $args, $defaults );

		if ( $option_val = get_option( $atts['id'] ) ) {

			$atts['value'] = $option_val;

		}

		include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-textarea.php' );

	} // field_textarea()
    
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
    
    private function dashicons_options() {
        $dashicons	=	array( 
          ''       => '-- ' .__('select', 'ncs-cart'). ' --',
          '\f333' => 'menu',
          '\f319' => 'admin-site',
          '\f226' => 'dashboard',
          '\f109' => 'admin-post',
          '\f104' => 'admin-media',
          '\f103' => 'admin-links',
          '\f105' => 'admin-page',
          '\f101' => 'admin-comments',
          '\f100' => 'admin-appearance',
          '\f106' => 'admin-plugins',
          '\f110' => 'admin-users',
          '\f107' => 'admin-tools',
          '\f108' => 'admin-settings',
          '\f112' => 'admin-network',
          '\f102' => 'admin-home',
          '\f111' => 'admin-generic',
          '\f148' => 'admin-collapse',
          '\f536' => 'filter',
          '\f540' => 'admin-customizer',
          '\f541' => 'admin-multisite',
          '\f119' => 'welcome-write-blog',
          '\f133' => 'welcome-add-page',
          '\f115' => 'welcome-view-site',
          '\f116' => 'welcome-widgets-menus',
          '\f117' => 'welcome-comments',
          '\f118' => 'welcome-learn-more',
          '\f123' => 'format-aside',
          '\f128' => 'format-image',
          '\f161' => 'format-gallery',
          '\f126' => 'format-video',
          '\f130' => 'format-status',
          '\f122' => 'format-quote',
          '\f125' => 'format-chat',
          '\f127' => 'format-audio',
          '\f306' => 'camera',
          '\f232' => 'images-alt',
          '\f233' => 'images-alt2',
          '\f234' => 'video-alt',
          '\f235' => 'video-alt2',
          '\f236' => 'video-alt3',
          '\f501' => 'media-archive',
          '\f500' => 'media-audio',
          '\f499' => 'media-code',
          '\f498' => 'media-default',
          '\f497' => 'media-document',
          '\f496' => 'media-interactive',
          '\f495' => 'media-spreadsheet',
          '\f491' => 'media-text',
          '\f490' => 'media-video',
          '\f492' => 'playlist-audio',
          '\f493' => 'playlist-video',
          '\f522' => 'controls-play',
          '\f523' => 'controls-pause',
          '\f519' => 'controls-forward',
          '\f517' => 'controls-skipforward',
          '\f518' => 'controls-back',
          '\f516' => 'controls-skipback',
          '\f515' => 'controls-repeat',
          '\f521' => 'controls-volumeon',
          '\f520' => 'controls-volumeoff',
          '\f165' => 'image-crop',
          '\f531' => 'image-rotate',
          '\f166' => 'image-rotate-left',
          '\f167' => 'image-rotate-right',
          '\f168' => 'image-flip-vertical',
          '\f169' => 'image-flip-horizontal',
          '\f533' => 'image-filter',
          '\f171' => 'undo',
          '\f172' => 'redo',
          '\f200' => 'editor-bold',
          '\f201' => 'editor-italic',
          '\f203' => 'editor-ul',
          '\f204' => 'editor-ol',
          '\f205' => 'editor-quote',
          '\f206' => 'editor-alignleft',
          '\f207' => 'editor-aligncenter',
          '\f208' => 'editor-alignright',
          '\f209' => 'editor-insertmore',
          '\f210' => 'editor-spellcheck',
          '\f211' => 'editor-expand',
          '\f506' => 'editor-contract',
          '\f212' => 'editor-kitchensink',
          '\f213' => 'editor-underline',
          '\f214' => 'editor-justify',
          '\f215' => 'editor-textcolor',
          '\f216' => 'editor-paste-word',
          '\f217' => 'editor-paste-text',
          '\f218' => 'editor-removeformatting',
          '\f219' => 'editor-video',
          '\f220' => 'editor-customchar',
          '\f221' => 'editor-outdent',
          '\f222' => 'editor-indent',
          '\f223' => 'editor-help',
          '\f224' => 'editor-strikethrough',
          '\f225' => 'editor-unlink',
          '\f320' => 'editor-rtl',
          '\f474' => 'editor-break',
          '\f475' => 'editor-code',
          '\f476' => 'editor-paragraph',
          '\f535' => 'editor-table',
          '\f135' => 'align-left',
          '\f136' => 'align-right',
          '\f134' => 'align-center',
          '\f138' => 'align-none',
          '\f160' => 'lock',
          '\f528' => 'unlock',
          '\f145' => 'calendar',
          '\f508' => 'calendar-alt',
          '\f177' => 'visibility',
          '\f530' => 'hidden',
          '\f173' => 'post-status',
          '\f464' => 'edit',
          '\f182' => 'trash',
          '\f537' => 'sticky',
          '\f504' => 'external',
          '\f142' => 'arrow-up',
          '\f140' => 'arrow-down',
          '\f139' => 'arrow-right',
          '\f141' => 'arrow-left',
          '\f342' => 'arrow-up-alt',
          '\f346' => 'arrow-down-alt',
          '\f344' => 'arrow-right-alt',
          '\f340' => 'arrow-left-alt',
          '\f343' => 'arrow-up-alt2',
          '\f347' => 'arrow-down-alt2',
          '\f345' => 'arrow-right-alt2',
          '\f341' => 'arrow-left-alt2',
          '\f156' => 'sort',
          '\f229' => 'leftright',
          '\f503' => 'randomize',
          '\f163' => 'list-view',
          '\f164' => 'exerpt-view',
          '\f509' => 'grid-view',
          '\f545' => 'move',
          '\f237' => 'share',
          '\f240' => 'share-alt',
          '\f242' => 'share-alt2',
          '\f301' => 'twitter',
          '\f303' => 'rss',
          '\f465' => 'email',
          '\f466' => 'email-alt',
          '\f304' => 'facebook',
          '\f305' => 'facebook-alt',
          '\f462' => 'googleplus',
          '\f325' => 'networking',
          '\f308' => 'hammer',
          '\f309' => 'art',
          '\f310' => 'migrate',
          '\f311' => 'performance',
          '\f483' => 'universal-access',
          '\f507' => 'universal-access-alt',
          '\f486' => 'tickets',
          '\f484' => 'nametag',
          '\f481' => 'clipboard',
          '\f487' => 'heart',
          '\f488' => 'megaphone',
          '\f489' => 'schedule',
          '\f120' => 'wordpress',
          '\f324' => 'wordpress-alt',
          '\f157' => 'pressthis',
          '\f463' => 'update',
          '\f180' => 'screenoptions',
          '\f348' => 'info',
          '\f174' => 'cart',
          '\f175' => 'feedback',
          '\f176' => 'cloud',
          '\f326' => 'translation',
          '\f323' => 'tag',
          '\f318' => 'category',
          '\f480' => 'archive',
          '\f479' => 'tagcloud',
          '\f478' => 'text',
          '\f147' => 'yes',
          '\f158' => 'no',
          '\f335' => 'no-alt',
          '\f132' => 'plus',
          '\f502' => 'plus-alt',
          '\f460' => 'minus',
          '\f153' => 'dismiss',
          '\f159' => 'marker',
          '\f155' => 'star-filled',
          '\f459' => 'star-half',
          '\f154' => 'star-empty',
          '\f227' => 'flag',
          '\f534' => 'warning',
          '\f230' => 'location',
          '\f231' => 'location-alt',
          '\f178' => 'vault',
          '\f332' => 'shield',
          '\f334' => 'shield-alt',
          '\f468' => 'sos',
          '\f179' => 'search',
          '\f181' => 'slides',
          '\f183' => 'analytics',
          '\f184' => 'chart-pie',
          '\f185' => 'chart-bar',
          '\f238' => 'chart-line',
          '\f239' => 'chart-area',
          '\f307' => 'groups',
          '\f338' => 'businessman',
          '\f336' => 'id',
          '\f337' => 'id-alt',
          '\f312' => 'products',
          '\f313' => 'awards',
          '\f314' => 'forms',
          '\f473' => 'testimonial',
          '\f322' => 'portfolio',
          '\f330' => 'book',
          '\f331' => 'book-alt',
          '\f316' => 'download',
          '\f317' => 'upload',
          '\f321' => 'backup',
          '\f469' => 'clock',
          '\f339' => 'lightbulb',
          '\f482' => 'microphone',
          '\f472' => 'desktop',
          '\f547' => 'laptop',
          '\f471' => 'tablet',
          '\f470' => 'smartphone',
          '\f525' => 'phone',
          '\f510' => 'index-card',
          '\f511' => 'carrot',
          '\f512' => 'building',
          '\f513' => 'store',
          '\f514' => 'album',
          '\f527' => 'palmtree',
          '\f524' => 'tickets-alt',
          '\f526' => 'money',
          '\f328' => 'smiley',
          '\f529' => 'thumbs-up',
          '\f542' => 'thumbs-down',
          '\f538' => 'layout',
          '\f546' => 'paperclip' 
        );
        return $dashicons;
    }
}