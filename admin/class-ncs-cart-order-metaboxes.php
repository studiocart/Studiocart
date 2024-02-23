<?php
/**
 * The metabox-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 */

/**
 * The metabox-specific functionality of the plugin.
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Order_Metaboxes {

	/**
	 * The post meta data
	 *
	 * @since 		1.0.2
	 * @access 		private
	 * @var 		string 			$meta    			The post meta data.
	 */
	private $meta;

	/**
	 * The ID of this plugin.
	 *
	 * @since 		1.0.2
	 * @access 		private
	 * @var 		string 			$plugin_name 		The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since 		1.0.2
	 * @access 		private
	 * @var 		string 			$version 			The current version of this plugin.
	 */
	private $version;
    
    /**
	 * The prefix of this plugin.
	 *
	 * @since 		1.0.2
	 * @access 		private
	 * @var 		string 			prefix 			The prefix of this plugin.
	 */
	private $prefix;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 		1.0.2
	 * @param 		string 			$Studiocart 		The name of this plugin.
	 * @param 		string 			$version 			The version of this plugin.
	 */
    
    private $general;
    private $sub_fields;
    
	public function __construct( $plugin_name, $version, $prefix ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->prefix = $prefix;

		$this->set_meta();

	}

	/**
	 * Registers metaboxes with WordPress
	 *
	 * @since 	1.0.0
	 * @access 	public
	 */
	public function add_metaboxes() {

		// add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
        
        

		$this->set_field_groups();
        
        add_meta_box(
			'sc-edit-order-details',
			apply_filters( $this->plugin_name . '-metabox-title-order-details', esc_html__( 'Order Details', 'ncs-cart' ) ),
			array( $this, 'order_detail_fields' ),
			'sc_order',
			'normal',
			'high'
		);
        
        add_meta_box(
			'sc-edit-order-details',
			apply_filters( $this->plugin_name . '-metabox-title-order-details', esc_html__( 'Subscriber Details', 'ncs-cart' ) ),
			array( $this, 'sub_detail_fields' ),
			'sc_subscription',
			'normal',
			'high'
		);

	}

	/**
	 * Check each nonce. If any don't verify, $nonce_check is increased.
	 * If all nonces verify, returns 0.
	 *
	 * @since 		1.0.0
	 * @access 		public
	 * @return 		int 		The value of $nonce_check
	 */
	private function check_nonces( $posted ) {

		$nonces 		= array();
		$nonce_check 	= 0;

		$nonces[] 		= 'sc_fields_nonce';

		foreach ( $nonces as $nonce ) {

			if ( ! isset( $posted[$nonce] ) ) { $nonce_check++; }
			if ( isset( $posted[$nonce] ) && ! wp_verify_nonce( $posted[$nonce], $this->plugin_name ) ) { $nonce_check++; }

		}

		return $nonce_check;

	} // check_nonces()

	/**
	 * Returns an array of the all the metabox fields and their respective types
	 *
	 * @since 		1.0.0
	 * @access 		public
	 * @return 		array 		Metabox fields and types
	 */
	private function get_metabox_fields($post_type) {
        
        $this->set_field_groups(true);
        
        $fields = array();        
        $groups = ($post_type == 'sc_order') ? array('general') : array('sub_fields');
        
        foreach($groups as $id) {
            foreach($this->$id as $group) {
                $type = (isset($group['field-type'])) ? $group['field-type'] : $group['type'];
                $set = array($group['id'], $type);
                $fields[] = $set;
            }   
        }
		return $fields;

	} // get_metabox_fields()

	/**
	 * Calls a metabox file specified in the add_meta_box args.
	 *
	 * @since 	1.0.0
	 * @access 	public
	 * @return 	void
	
	public function metabox( $post, $params ) {

		if ( ! is_admin() ) { return; }
		if ( 'sc_product' !== $post->post_type ) { return; }

		if ( ! empty( $params['args']['classes'] ) ) {

			$classes = 'repeater ' . $params['args']['classes'];

		}

		include( plugin_dir_path( __FILE__ ) . 'partials/ncs-cart-admin-metabox-' . $params['args']['file'] . '.php' );

	} // metabox() */
    
    public function order_detail_fields( $post, $params ) {
        
		if ( ! is_admin() ) { return; }
        
		if ( 'sc_order' !== $post->post_type ) { return; }
        
        echo '<div class="sc-settings-tabs">';
            
        wp_nonce_field( $this->plugin_name, 'sc_fields_nonce' );
        
            echo '<div>';
                $this->metabox_fields($this->general);
            echo '</div>';
        
        echo '</div>';

	}
    
    public function sub_detail_fields( $post, $params ) {
        
		if ( ! is_admin() ) { return; }
        
		if ( 'sc_subscription' !== $post->post_type ) { return; }
        
        echo '<div class="sc-settings-tabs">';
            
        wp_nonce_field( $this->plugin_name, 'sc_fields_nonce' );
        
            echo '<div>';
                $this->metabox_fields($this->sub_fields);
            echo '</div>';
        
        echo '</div>';

	}
    
    private function metabox_fields( $fields ) {
        $scripts = '';    
        $defaults['class_size'] 	= '';
        $defaults['description'] 	= '';
        $defaults['label'] 			= '';
        
        foreach ($fields as $atts) {

            if ($atts['type'] != 'repeater') {
                
                $atts = wp_parse_args( $atts, $defaults );
                
                if ($atts['type'] == 'html') {
                    echo $atts['value'];
                } else {
                    if ( isset( $this->meta[$atts['id']][0] ) ) {
                        if ($atts['type'] == 'checkbox') {
                            $atts['value'] = 1;
                        } else {
                            $atts['value'] = $this->meta[$atts['id']][0];
                        }
                    }

                    apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );
                    $name = str_replace('_sc_', 'sc-', $atts['id']);
                    $atts['name'] = str_replace('_','-',$name);
                    $atts['name'] = $atts['id'];

                    if ($atts['type'] != 'hidden'): ?><div id="rid<?php echo $atts['id']; ?>" class="sc-field sc-row <?php echo $atts['class_size']; ?>"><?php endif;
                    if( file_exists( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-'.$atts['type'].'.php' ) ) {
                        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-'.$atts['type'].'.php' );
                    } else {
                        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );                    
                    }
                    if ($atts['type']!='hidden'): ?></div><?php endif;
                }
                // conditional logic
                if ( !empty( $atts['conditional_logic'] ) ) : ?>
                    <?php 
                    foreach ($atts['conditional_logic'] as $l) { 
                        $row_id = 'rid'.$atts['id'];
                        if ( !isset($l['compare']) || $l['compare'] == '=' ) {
                            $l['compare'] = '==';
                        } 
                        
                        $eval = ($l['value'] === true) ? $eval = $l['field'].':checked' : $l['field'];
                        $condition = "if ( $('#%s').val() %s '%s' ) { $('#%s').fadeIn() } else { $('#%s').hide() }"; 
                        
                        $scripts .= sprintf($condition, $eval, $l['compare'], $l['value'], $row_id, $row_id);
                        $scripts .= '$("#'.$l['field'].'").change(function(){';
                        $scripts .= sprintf($condition, $eval, $l['compare'], $l['value'], $row_id, $row_id); 
                        $scripts .= '});';
                    }
                endif; 

            } else {
                
                $setatts = $atts;

                apply_filters( $this->plugin_name . '-field-repeater-'.$setatts['id'], $setatts );

                $count 		= 1;
                $repeater 	= array();

                if ( ! empty( $this->meta[$setatts['id']] ) ) {

                    $repeater = maybe_unserialize( $this->meta[$setatts['id']][0] );

                }

                if ( ! empty( $repeater ) ) {

                    $count = count( $repeater );

                }

                include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-repeater.php' );
                
            }
        }
        if ($scripts != '') : ?>
            <script type="text/javascript">
                jQuery('document').ready(function($){
                    <?php echo $scripts; ?>
                });
            </script><?php 
        endif; 
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

	/**
	 * Sets the class variable $options
	 */
	public function set_meta() {

		if( isset($_GET['post']) ) {
            $post_id = absint($_GET['post']); // Always sanitize
            $post = get_post( $post_id ); // Post Object, like in the Theme loop

		    if ( 'sc_order' != $post->post_type && 'sc_subscription' != $post->post_type ) { return; }

            //wp_die( '<pre>' . var_dump( $post->ID ) . '</pre>' );
            $this->meta = get_post_custom( $post->ID );
        }
        
        return;

	} // set_meta()

	/**
	 * Saves metabox data
	 *
	 * Repeater section works like this:
	 *  	Loops through meta fields
	 *  		Loops through submitted data
	 *  		Sanitizes each field into $clean array
	 *   	Gets max of $clean to use in FOR loop
	 *   	FOR loops through $clean, adding each value to $new_value as an array
	 *
	 * @since 	1.0.0
	 * @access 	public
	 * @param 	int 		$post_id 		The post ID
	 * @param 	object 		$object 		The post object
	 * @return 	void
	 */
	public function validate_meta( $post_id, $object ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
		if ( 'sc_subscription' !== $object->post_type ) { return $post_id; }

		$nonce_check = $this->check_nonces( $_POST );

		if ( 0 < $nonce_check ) { return $post_id; }

		$metas = $this->get_metabox_fields($object->post_type);
        
        $stripe_objects = array();
        
        //wp_die( '<pre>' . var_dump( $metas ) . '</pre>' );
        
		foreach ( $metas as $meta ) {

            $new_value = '';
            
			$name = $meta[0];
			$type = $meta[1];

            if('html' !== $type ) {
                
				if( empty($_POST[$name]) && $_POST[$name] !== '0' ) { 
                    delete_post_meta( $post_id, $name );
                    continue; 
                }
               
                if( $_POST[$name] === '0' ) {
                    $new_value = 0;
                } else {
                    $new_value = $this->sanitizer( $type, $_POST[$name] );
                }
			}

			update_post_meta( $post_id, $name, $new_value );
            //var_dump($name,$new_value);

		} // foreach
        //var_dump($stripe_objects);
        
        //die();
        
	} // validate_meta()

    
    private function set_field_groups($save = false) {
                
        $post_id = $_GET['post'] ?? null;
        $post_id = ( $save ? null : absint($post_id) );
        
        $usersArr = array('' => __( 'Guest', 'ncs-cart'));

        if (!$save) {
            $user_id = get_post_meta($post_id,'_sc_user_account',true);
            if(!empty($user_id)){
                $author_obj = get_user_by('id', $user_id);
                $usersArr[$author_obj->ID] =  __( $author_obj->display_name, 'ncs-cart');
            }
        }
        
        $this->general = array(
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_firstname',
                'label'		=> __('First Name ','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
             array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_lastname',
                'label'		=> __('Last Name','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_email',
                'label'		=> __('Email','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_phone',
                'label'		=> __('Phone','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_company',
                'label'		=> __('Company','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_vat_number',
                'label'		=> __('VAT Number','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_address1',
                'label'     => __('Address ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_address2',
                'label'     => __('Address2 ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_city',
                'label'     => __('City ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_state',
                'label'     => __('State ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_zip',
                'label'     => __('Zip ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'         => 'sc-selectize',
                'description'   => '',
                'id'            => '_sc_country',
                'label'         => __('Country','ncs-cart'),
                'placeholder'   => '',
                'type'          => 'select',
                'value'         => '',
                'selections' 	=> ($save) ? '' : array_merge([''=>'--Select--'], sc_countries_list()),
                'class_size'=> ''
            ),
            array(
                'class'		    => 'sc-user-search-custom sc-selectize',
                'description'	=> '',
                'id'			=> '_sc_user_account',
                'label'	    	=> __('Customer','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => ($save) ? '' : $usersArr,
                'class_size'=> ''
            ),
            array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> '_sc_status',
                'label'	    	=> __('Order Status','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => array(
                                    ''          	    => __('-- Select --', 'ncs-cart'),
                                    'pending' 	        => __('Pending', 'ncs-cart'),
                                    'paid' 		        => __('Paid', 'ncs-cart'),
                                    'completed'         => __('Completed', 'ncs-cart'),
                                    'failed'	        => __('Failed', 'ncs-cart') , 
                                    'refunded' 	        => __('Refunded', 'ncs-cart'),
                                    'uncollectible'     => __('Uncollectible', 'ncs-cart'),
                                ),
                'class_size'=> ''
            ),
            array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> '_sc_product_id',
                'label'	    	=> __('Product','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => ($save) ? '' : $this->get_sc_products(),
                'class_size'=> ''
            ),
            array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> '_sc_item_name',
                'label'	    	=> __('Payment Plan','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => ($save) ? '' : $this->get_sc_products_payment(),
                'class_size'=> ''
            ),
          );
        
        $this->sub_fields = array(
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_firstname',
                'label'		=> __('First Name ','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
             array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_lastname',
                'label'		=> __('Last Name','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_email',
                'label'		=> __('Email','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_phone',
                'label'		=> __('Phone','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_company',
                'label'		=> __('Company','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_vat_number',
                'label'		=> __('VAT Number','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_address1',
                'label'     => __('Address ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_address2',
                'label'     => __('Address2 ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_city',
                'label'     => __('City ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_state',
                'label'     => __('State ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'     => 'widefat',
                'description'   => '',
                'id'            => '_sc_zip',
                'label'     => __('Zip ','ncs-cart'),
                'placeholder'   => '',
                'type'      => 'text',
                'value'     => '',
            ),
            array(
                'class'         => 'sc-selectize',
                'description'   => '',
                'id'            => '_sc_country',
                'label'         => __('Country','ncs-cart'),
                'placeholder'   => '',
                'type'          => 'select',
                'value'         => '',
                'selections' 	=> ($save) ? '' : sc_countries_list(),
                'class_size'=> ''
            ),
            array(
                'class'		    => 'sc-user-search-custom sc-selectize',
                'description'	=> '',
                'id'			=> '_sc_user_account',
                'label'	    	=> __('Customer','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => ($save) ? '' : $usersArr,
                'class_size'=> ''
            ),
          );
    }
    
    //GET NCS_PRODUCTS
	private function get_sc_products(){
		$options = array('' => 'Select Product');
			$args = array( 
				'numberposts'	=> -1, // -1 is for all
				'post_type'		=> 'sc_product', // or 'post', 'page'
				'orderby' 		=> 'title', // or 'date', 'rand'
				'order' 		=> 'ASC', // or 'DESC'
				'post_status' 	=> 'publish', // or 'DESC'
			);
			// Get the posts
			$myProducts = get_posts($args);
			if($myProducts):
				foreach ( $myProducts as $product ) {
					$options[$product->ID] = get_the_title($product->ID);
				}
				wp_reset_postdata();
			endif;
		return $options;
	}
	
	//GET NCS_PRODUCTS
	private function get_sc_products_payment(){
		$options = array('' => 'Select Product Payment');
			$args = array( 
				'numberposts'	=> -1, // -1 is for all
				'post_type'		=> 'sc_product', // or 'post', 'page'
				'orderby' 		=> 'title', // or 'date', 'rand'
				'order' 		=> 'ASC', // or 'DESC'
				'post_status' 	=> 'publish', // or 'DESC'
			);
			// Get the posts
			$myProducts = get_posts($args);
		
			if($myProducts){
				foreach ( $myProducts as $product ) {
					$_sc_pay_options = get_post_meta( $product->ID, '_sc_pay_options' );
                    
 			        foreach ( $_sc_pay_options as $pay_options ) {
                        foreach ( $pay_options as $value ) {
                            if(isset($value['option_id'])){
                                $value['option_name'] = $value['option_name'] ?? $value['option_id'];
                                $options[$value['option_id']] = $value['option_name'];
                                if(isset($value['sale_price'])){
                                    $value['sale_option_name'] = $value['sale_option_name'] ?? $value['option_name'] . ' (on sale)';
                                    $options[$value['option_id'].'_sale'] = $value['sale_option_name'];
                                }
                            }
                        }
					}
				}
				wp_reset_postdata();
            }
		return $options;
	}

} // class
