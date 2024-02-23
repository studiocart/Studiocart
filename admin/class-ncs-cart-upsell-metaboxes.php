<?php
/**
 * The metabox-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      2.4.0
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
class NCS_Cart_Upsell_Metaboxes {

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
    
    private $upsell;
    private $downsell;
    
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
			'sc-product-settings',
			apply_filters( $this->plugin_name . '-metabox-title-order-details', esc_html__( 'Upsell Offers', 'ncs-cart' ) ),
			array( $this, 'upsell_fields' ),
			'sc_us_path',
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
	private function get_metabox_fields() {
        
        $this->set_field_groups(true);
        
        $fields = array();
        
        $groups = array( 'upsell', 'downsell' );
        
        foreach($groups as $id) {
            foreach($this->$id as $group) {
                $type = (isset($group['field-type'])) ? $group['field-type'] : $group['type'];
                $set = array(@$group['id'], $type);
                if ($group['type'] == 'repeater') {
                    $r_fields = array();
                    foreach($group['fields'] as $gfield){
                        foreach ($gfield as $k=>$v){
                            $field = array($v['id'],$k);

                            $pos = strpos($v['class'], 'required');
                            if ($pos !== false && !isset($v['conditional_logic'])) { $field[] = 'required'; }
                            
                            $r_fields[] = $field;
                        }
                    }
                    $set[] = $r_fields;
                }
                $fields[] = $set;
            }   
        }

		return $fields;

	} // get_metabox_fields()
    
    
    public function upsell_fields( $post, $params ) {

		if ( ! is_admin() ) { return; }
        
		if ( 'sc_us_path' !== $post->post_type ) { return; }
        
        $tabs = array(
            'upsell'         => __('Upsell','ncs-cart'),// premium
            'downsell'       => '↳ ' . __('Downsell','ncs-cart'),// premium
        );
        
        $tabs = apply_filters('sc_upsell_flow_setting_tabs', $tabs);
        
        echo '<div class="sc-settings-tabs">';
            
            wp_nonce_field( $this->plugin_name, 'sc_fields_nonce' );
            
            echo '<div class="sc-left-col">';
                $active=0;
                
                for($i=1; $i<=5; $i++) {
                    foreach($tabs as $id=>$label) {
                        $active = ($active == 0) ? 'active' : '';
                        echo '<div class="sc-tab-nav sc-tab-nav-'.$id. ' '.$active.'"><a href="#sc-tab-'.$id.'-'.$i.'">'.$label.' '.$i.'</a></div>';
                        $active++;
                    }
                }
            echo '</div>';

            echo '<div class="sc-right-col">';
                $active=0;
                for($i=1; $i<=5; $i++) {
                    foreach($tabs as $id=>$label) {
                        $active = ($active === 0) ? 'active' : '';
                        $fields = $this->$id ?? array();
                        $fields[4]['selections'] = $this->get_plans($fields[1]['id'].'_'.$i);
                        $fields = apply_filters("sc_upsell_setting_tab_{$id}_{$i}_fields", $fields);
                        echo '<div id="sc-tab-'.$id.'-'.$i.'" class="sc-tab '.$active.'">';
                            $this->metabox_fields($fields, $i);
                        echo '</div>';
                    }
                }
            echo '</div>
        </div>';

	}
    
    private function metabox_fields( $fields, $ind ) {
        $_GET['post'] = $_GET['post'] ?? 0;
        $scripts = '';
        foreach ($fields as $atts) {
            
            $defaults['class_size'] 	= '';
            $defaults['description'] 	= '';
            $defaults['label'] 			= '';
            $defaults['id'] 			= '';

            $atts = wp_parse_args( $atts, $defaults );
            
            $atts['id'] .= '_'.$ind;
            
            if ($atts['type'] != 'repeater') {
                if ($atts['type'] == 'html') {
                    echo $atts['value'];
                } else {                    
                    if ($atts['type'] == 'checkbox') {
                        $atts['value'] = isset($this->meta[$atts['id']][0]);
                    } else if (isset($this->meta[$atts['id']][0])){
                        $atts['value'] = $this->meta[$atts['id']][0];
                    }
                    
                    $atts['label'] = str_replace('{ind}',$ind,$atts['label']);

                    apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );
                    $name = str_replace('_sc_', 'sc-', $atts['id']);
                    $atts['name'] = str_replace('_','-',$name);
                    $atts['name'] = $atts['id'];

                    ?><div id="rid<?php echo $atts['id']; ?>" class="sc-field sc-row <?php echo $atts['class_size']; ?>"><?php
                    if( file_exists( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-'.$atts['type'].'.php' ) ) {
                        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-'.$atts['type'].'.php' );
                    } else {
                        include( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php' );                    
                    }
                    ?></div><?php 
                }

            } else {
                
                $setatts = $atts;

                apply_filters( $this->plugin_name . '-field-repeater-'.$setatts['id'], $setatts );

                $count 		= 0;
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

		    if ( 'sc_us_path' != $post->post_type ) { return; }

            //wp_die( '<pre>' . var_dump( get_post_custom( $post->ID ) ) . '</pre>' );
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

		//wp_die( '<pre>' . print_r( $_POST, true ) . '</pre>');

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return $post_id; }
		if ( ! current_user_can( 'edit_post', $post_id ) ) { return $post_id; }
		if ( 'sc_us_path' !== $object->post_type ) { return $post_id; }

		$nonce_check = $this->check_nonces( $_POST );

		if ( 0 < $nonce_check ) { return $post_id; }
        
		$metas = $this->get_metabox_fields();
        
        //wp_die( '<pre>' . var_dump( $metas ) . '</pre>' );
                
        for($n=0; $n<=5; $n++) {
            foreach ( $metas as $meta ) {

                $new_value = '';

                $name = $meta[0].'_'.$n;
                $type = $meta[1];

                if('html' !== $type ) {

                    if( empty(@$_POST[$name]) && @$_POST[$name] !== '0' ) { 
                        delete_post_meta( $post_id, $name );
                        continue; 
                    }

                    if( @$_POST[$name] === '0' ) {
                        $new_value = 0;
                    } else {
                        $new_value = $this->sanitizer( $type, $_POST[$name] );
                    }
                }

                update_post_meta( $post_id, $name, $new_value );
                //var_dump($name,$new_value);

            } // foreach
        } // for
        	
	} // validate_meta()
        
    /**
	 * Returns the count of the largest arrays
	 *
	 * @param 		array 		$array 		An array of arrays to count
	 * @return 		int 					The count of the largest array
	 */
 	public static function get_max( $array ) {

 		if ( empty( $array ) ) { return '$array is empty!'; }

 		$count = array();

		foreach ( $array as $name => $field ) {

			$count[$name] = count( $field );

		} //

		$count = max( $count );

		return $count;

 	} // get_max()

    
    private function set_field_groups($save = false) {
        
        $post_id = $_GET['post'] ?? null;
        $post_id = ( $save ? null : absint($post_id) );
        
        $this->upsell = array(
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_upsell',
                'label'		=> __('Enable','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> '',
                'class_size'		=> '',
            ), 
            array(
                'class'		    => 'update-plan-product required',
                'description'	=> '',
                'id'			=> '_sc_us_product',
                'label'	    	=> __('Select Product','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => $this->product_options(),
                'class_size'=> '',
            ), 
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_us_prod_type',
                'label'		=> __('Use existing payment plan','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> '',
                'class_size'		=> '',
            ),          
            array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> '_sc_us_price',
                'label'		    => __('One-time price','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => '',
                'step'          => 'any',
            ),                
            array(
                'class'		    => 'widefat update-plan',
                'description'	=> '',
                'id'			=> '_sc_us_plan',
                'label'		    => __('Payment Plan','ncs-cart'),
                'placeholder'	=> '',
                'value'		    => '',
                'class_size'    => '',
                'step'          => 'any',
                'type'          => 'select',
            ), 
            array(
                'class'		    => 'required',
                'description'	=> '',
                'id'			=> '_sc_us_page',
                'label'	    	=> __('Page','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => $this->get_pages(),
                'class_size'=> '',
            ),
            array(
                'id'			=> '_sc_us_yes_link',
                'type'		    => 'html',
                'value'		    => '<div id="rid_sc_us_yes_link" class="sc-field sc-row"><label>'.__('Accept Link','ncs-cart').'</label><div class="input-group field-text"><input type="text" readonly value="'.get_site_url().'?sc-upsell-offer=yes" /></div></div>',
                'class_size'=> '',
            ),
            array(
                'id'			=> '_sc_us_no_link',
                'type'		    => 'html',
                'value'		    => '<div id="rid_sc_us_no_link" class="sc-field sc-row "><label>'.__('Decline Link','ncs-cart').'</label><div class="input-group field-text"><input type="text" readonly value="'.get_site_url().'?sc-upsell-offer=no" /></div></div>',
                'class_size'=> '',
            )
        );

        $this->downsell = array(
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_downsell',
                'label'		=> __('Enable','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> '',
                'class_size'		=> '',
            ),
            array(
                'class'		    => 'update-plan-product required',
                'description'	=> '',
                'id'			=> '_sc_ds_product',
                'label'	    	=> __('Select Product','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => $this->product_options(),
                'class_size'=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_ds_prod_type',
                'label'		=> __('Use existing payment plan','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> '',
                'class_size'		=> '',
            ),          
            array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> '_sc_ds_price',
                'label'		    => __('Price','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => '',
                'step'          => 'any',
            ),                
            array(
                'class'		    => 'widefat update-plan',
                'description'	=> '',
                'id'			=> '_sc_ds_plan',
                'label'		    => __('Payment Plan','ncs-cart'),
                'placeholder'	=> '',
                'value'		    => '',
                'class_size'    => '',
                'step'          => 'any',
                'type'          => 'select',
            ),
            array(
                'class'		    => 'required',
                'description'	=> '',
                'id'			=> '_sc_ds_page',
                'label'	    	=> __('Page','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => $this->get_pages(),
                'class_size'=> '',
            ),
            array(
                'id'			=> '_sc_ds_yes_link',
                'type'		    => 'html',
                'value'		    => '<div id="rid_sc_ds_yes_link" class="sc-field sc-row"><label>'.__('Accept Link','ncs-cart').'</label><div class="input-group field-text"><input type="text" readonly value="'.get_site_url().'?sc-upsell-offer=yes" /></div></div>',
                'class_size'=> '',
            ),
            array(
                'id'			=> '_sc_ds_no_link',
                'type'		    => 'html',
                'value'		    => '<div id="rid_sc_ds_no_link" class="sc-field sc-row "><label>'.__('Decline Link','ncs-cart').'</label><div class="input-group field-text"><input type="text" readonly value="'.get_site_url().'?sc-upsell-offer=no" /></div></div>',
                'class_size'=> '',
            ),
        );
        
        $this->tracking = array(
           array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_tracking_oto',
                'label'		=> __('1st Upsell Purchased','ncs-cart'),
                'placeholder'	=> __('Tracking codes for when the first upsell is purchased.','ncs-cart'),
                'type'		=> 'textarea',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_tracking_oto_2',
                'label'		=> __('Downsell Purchased','ncs-cart'),
                'placeholder'	=> __('Tracking codes for when the second upsell is purchased.','ncs-cart'),
                'type'		=> 'textarea',
                'value'		=> '',
            ),
        );
    }

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
    
    private function get_pages($noblank = false){
        if (!isset($_GET['post']) && !isset($_GET['post_type'])) {
            return;
        }
        
        $types = apply_filters('sc_select_page_post_types', array( 'page', 'e-landing-page' ));
        $options = array('' => __('Select Page','ncs-cart'));        
                
        // The Query
        $args = array(
            'post_type' => $types,
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $the_query = new WP_Query( $args );

        // The Loop
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                $options[get_the_ID()] = get_the_title() . ' (ID: '.get_the_ID().')';
            }
        } else{
            $options = array('' => __('-- none found --','ncs-cart'));
		}
        /* Restore original Post Data */
        wp_reset_postdata();
        
		return $options;
	}
    
    private function get_plans($key) {
        if(!isset($_GET['post'])) {
            return;
        }
        $options = array();
        $post_id = intval($_GET['post']);

        if ($product_id = get_post_meta($post_id, $key, true)) {
            
                
            if(is_array($product_id)){
                foreach($product_id as $prod_id){
                    if($key == '_sc_order_bump_options'){
                        $pid = $prod_id['ob_product'];
                    }
                    $options[$pid] = $this->get_plan_data($pid);
                }
            } else {
                $options = $this->get_plan_data($product_id);
            }
        }
        return $options;
    }

    private function get_plan_data($product_id){
        $product_plan_data = get_post_meta($product_id, '_sc_pay_options', true);

        if(!$product_plan_data) {
            return array(""=> esc_html__('No plans found'));
        } else {
            $options = array();
            foreach ( $product_plan_data as $val ) {
                $label = $val['option_name'] ?? $val['option_id'];
                $options[$val['option_id']] = $label;
            }
            if(!empty($options)) {
                return $options;
            } else {
                return array(""=>esc_html__('No plans found'));
            }
        }
    }

    private function product_options(){
        if (!isset($_GET['post']) && !isset($_GET['post_type'])) {
            return;
        }
        
        global $studiocart;
        remove_filter( 'the_title', array( $studiocart, 'public_product_name' ) );

        $options = array('' => __('-- Select Product --','ncs-cart'));
                
        // The Query
        $args = array(
            'post_type' => array( 'sc_product' ),
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $the_query = new WP_Query( $args );

        // The Loop
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                $options[get_the_ID()] = get_the_title() . ' (ID: '.get_the_ID().')';
            }
        } else{
            $options = array('' => __('-- none found --','ncs-cart'));
		}
        /* Restore original Post Data */
        wp_reset_postdata();
        
        add_filter( 'the_title', array( $studiocart, 'public_product_name' ) );
		return $options;
	}

} // class
