<?php
/**
 * 
 * NCS Metabox Fields
 * 
 * The metabox-specific functionality of the plugin.
 *
 * @since      2.5
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Post_Metabox_Fields {

	/**
	 * The post meta data
	 *
	 * @since 		1.0.0
	 * @access 		private
	 * @var 		string 			$meta    			The post meta data.
	 */
	private $meta;

	/**
	 * The ID of this plugin.
	 *
	 * @since 		1.0.0
	 * @access 		private
	 * @var 		string 			$plugin_name 		The ID of this plugin.
	 */
	protected $plugin_name;
    
    /**
	 * The post type
	 *
	 * @since 		1.0.0
	 * @access 		private
	 * @var 		string 			$post_type
	 */
	protected $post_type;
    
    /**
	 * The post type
	 *
	 * @since 		1.0.0
	 * @access 		private
	 * @var 		string 			$post_type
	 */
    protected $fields;
    
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 		1.0.0
	 * @param 		string 			$Studiocart 		The name of this plugin.
	 * @param 		string 			$version 			The version of this plugin.
	 */
    
	public function __construct( $plugin_name ) {

		$this->plugin_name = $plugin_name;
		$this->post_type = '';
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
			apply_filters( $this->plugin_name . '-metabox-title-product-settings', esc_html__( 'Settings', 'ncs-cart' ) ),
			array( $this, 'do_settings_metabox' ),
			$this->post_type,
			'normal',
			'default'
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
                
        foreach($this->fields as $id=>$groups) {
            
            foreach($groups as $key=>$group) {
            
                if ($key == 'label') { continue; }

                $type = $group['field-type'] ?? $group['type'];
                $set = array(@$group['id'], $type);
                if ($group['type'] == 'repeater') {
                    $r_fields = array();
                    foreach($group['fields'] as $gfield){
                        foreach ($gfield as $k=>$v){
                            $type = $v['type'] ?? $k;
                            $field = array($v['id'],$type);
                            
                            $pos = strpos($v['class'], 'required');
                            if ($pos !== false && !isset($v['conditional_logic'])) { $field[] = 'required'; }
                            
                            $single = strpos($v['class'], 'singular');
                            if ($single !== false) { $field[] = 'singular'; }

                            $r_fields[] = $field;
                        }
                    }
                    $set[] = $r_fields;
                } else {
                    $single = strpos($group['class'], 'singular');
                    if ($single !== false) { $set[] = 'singular'; }
                }
                $fields[] = $set;
            }
        }   

		return $fields;

	} // get_metabox_fields()
    
    
    public function do_settings_metabox( $post, $params ) {

		if ( ! is_admin() ) { return; }
        
		if ( $this->post_type !== $post->post_type ) { return; }
        
        $tabs = $this->fields;
        
        echo '<div class="sc-settings-tabs">';
            
            wp_nonce_field( $this->plugin_name, 'sc_fields_nonce' );
            
            echo '<div class="sc-left-col">';
                $i=0;
                foreach($tabs as $id=>$field_groups) {
                    $active = ($i == 0) ? 'active' : '';
                    echo '<div class="sc-tab-nav '.$active.'"><a href="#sc-tab-'.$id.'">'.$field_groups['label'].'</a></div>';
                    $i++;
                }
            echo '</div>';

            echo '<div class="sc-right-col">';
                $i=0;
                foreach($tabs as $id=>$field_groups) {
                    if(isset($field_groups['label'])) {
                        unset($field_groups['label']);
                    }
                    $fields = apply_filters($this->post_type."_setting_tab_{$id}_fields", $field_groups);
                    echo '<div id="sc-tab-'.$id.'" class="sc-tab '.$active.'">';
                        $this->metabox_fields($fields);
                    echo '</div>';
                    $i++;
                }; 
            echo '</div>
        </div>';

	}
    
    private function metabox_fields( $fields ) {
        $_GET['post'] = $_GET['post'] ?? 0;
        $scripts = '';
        foreach ($fields as $atts) {
            
            $defaults['class_size'] 	= '';
            $defaults['description'] 	= '';
            $defaults['label'] 			= '';
            $defaults['id'] 			= '';

            $atts = wp_parse_args( $atts, $defaults );
            
            if ($atts['type'] != 'repeater' && $atts['type'] != 'conditions') {
                if ($atts['type'] == 'html') {
                    echo $atts['value'];                   
                } else {
                    
                    if ( isset($this->meta[$atts['id']][0]) ) {                                            
                        if(strpos($atts['class'], 'singular')) {
                            $this->meta[$atts['id']][0] = $this->meta[$atts['id']];
                        } else {
                            $this->meta[$atts['id']][0] = maybe_unserialize( $this->meta[$atts['id']][0] );
                        }
                    }
                    
                    if ($atts['type'] == 'checkbox') {
                        $atts['value'] = isset($this->meta[$atts['id']][0]);
                    } else if (isset($this->meta[$atts['id']][0])){
                        $atts['value'] = $this->meta[$atts['id']][0];
                    }

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
                // conditional logic
                if ( !empty( $atts['conditional_logic'] ) ) {
                
                    $conditions = array();
                    $row_id = 'rid'.$atts['id'];
                    
                    foreach ($atts['conditional_logic'] as $l) {
                        if (isset($l['compare']) && ($l['compare'] == 'IN' || $l['compare'] == 'NOT IN')) {
                            $scripts .= 'var arr_'.$atts['id'].' = '.json_encode($l['value']).';';
                            
                            if ($l['compare'] == 'IN') {
                                $conditions[] = sprintf("(arr_%s.includes( $('#%s').val()))", $atts['id'], $l['value']);
                            } else {
                                $conditions[] = sprintf("(!arr_%s.includes( $('#%s').val()))", $atts['id'], $l['value']);
                            }

                        } else {
                            if ( !isset($l['compare']) || $l['compare'] == '=' ) {
                                $l['compare'] = '==';
                            }
                            $eval = ($l['value'] === true) ? $eval = $l['field'].':checked' : $l['field'];
                            $conditions[] = sprintf("($('#%s').val() %s '%s')", $eval, $l['compare'], $l['value']);                             
                        }
                    } 
                        
                    if(!empty($conditions)) {
                        $conditions = implode(' && ', $conditions);
                        $eval = sprintf("if ( %s ) { $('#%s').fadeIn();} else { $('#%s').hide();}", $conditions, $row_id, $row_id);
                        $scripts .= $eval . '$("#'.$l['field'].'").change(function(){'.$eval.';});
                        ';
                    }
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
        if ($scripts) { ?>
            <script type="text/javascript">
                jQuery('document').ready(function($){<?php echo $scripts; ?>});
            </script>
        <?php }
        
        do_action($this->post_type.'_field_scripts', intval($_GET['post']));
        
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

		    if ( $this->post_type != $post->post_type ) { return; }

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
		if ( $this->post_type !== $object->post_type ) { return $post_id; }

		$nonce_check = $this->check_nonces( $_POST );

		if ( 0 < $nonce_check ) { return $post_id; }
        
		$metas = $this->get_metabox_fields();
        
        $saved_values = array();
        
        //wp_die( '<pre>' . var_dump( $metas ) . '</pre>' );
                
		foreach ( $metas as $meta ) {

            $new_value = '';
            
			$name = $meta[0];
			$type = $meta[1];
			$singular = (isset($meta[2]) && $meta[2] == 'singular');
            
            if ( 'repeater' === $type && is_array( $meta[2] ) ) {

				$clean = array();
                $keep = array();
                $remove = array();
                $required_key = false;
						

                foreach ( $meta[2] as $field ) {
                    //  array( 'label-file', 'text', 'required' )

					if ( isset($_POST[$name][$field[0]]) ) {
                        
                        $i = 0;
						
                        foreach ( $_POST[$name][$field[0]] as $k=>$data ) {
                            
                            if ( isset($field[2]) && strpos($field[2], 'required') !== false) {
                                $required_key = $field[0];
                            }

                            if ( empty( $data ) && isset($field[2]) && strpos($field[2], 'required') !== false) {
                                $remove[] = $k; 
                            } else {
                                $keep[] = $k; 
                            }
                                                        
                            if(is_array($data)) {
                                $field_arr = [];
                                foreach($data as $d) {
                                    $field_arr[] = $this->sanitizer( $field[1], $d );
                                }
                                $clean[$field[0]][$k] = $field_arr;
                            } else {
                                $clean[$field[0]][$k] = $this->sanitizer( $field[1], $data );
                            }
                            
                            $i++;

                        } // foreach
                        
                    } // if

				} // foreach
                
				$count 		= $this->get_max( $clean );
				$new_value 	= array();

				if($required_key) {
                    for ( $i = 0; $i < $count; $i++ ) {

                        $max = count($clean[$required_key]);
                        foreach ( $clean as $field_name => $field ) {

                            if($i<$max && isset($field[$i])){
                                $new_value[$i][$field_name] = $field[$i];
                            }

                        } // foreach $clean

                    } // for
                }
                
                if(!empty($remove)) {
                    foreach($remove as $r) {
                        unset($new_value[$r]);
                    } 
                    $new_value = array_values($new_value);
                }
                
                $saved_values[$name] = $new_value;
                
			} else if('html' !== $type ) {
                
				if( empty(@$_POST[$name]) && @$_POST[$name] !== '0' ) { 
                    delete_post_meta( $post_id, $name );
                    continue; 
                }
               
                if( @$_POST[$name] === '0' ) {
                    $new_value = 0;
                } else {
                    if(is_array($_POST[$name])) {
                        $field_arr = [];
                        foreach($_POST[$name] as $val) {
                            $field_arr[] = $this->sanitizer( $type, $val );
                        }
                        $new_value = $field_arr;
                    } else {
                        $new_value = $this->sanitizer( $type, $_POST[$name] );
                    }
                    
                }
                $saved_values[$name] = $new_value;
            }

			if($singular) {
                delete_post_meta( $post_id, $name );
                foreach($new_value as $val) {
                    add_post_meta( $post_id, $name, $val );
                }
                
            } else {
                update_post_meta( $post_id, $name, $new_value );
            }
            
            //var_dump($name,$new_value);

		} // foreach
                
        do_action('sc_after_validate_'.$this->post_type.'_meta', $post_id, $saved_values);
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
    
    public function product_options(){
        if (!isset($_GET['post'])) {
            return;
        }
        
        global $studiocart;
        remove_filter( 'the_title', array( $studiocart, 'public_product_name' ) );

        $options = array('' => __('-- Select Product --','ncs-cart'));
        
        $id = ($_GET['post']) ? intval($_GET['post']) : '';
        
        // The Query
        $args = array(
            'post_type' => array( 'sc_product' ),
            'post__not_in' => array($id),
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

    public function get_pages(){
        $pages = get_pages();
        $options = array('' => __('Select Page', 'ncs-cart'));
        foreach ( $pages as $page ) {
            $options[$page->ID] = $page->post_title . ' (ID: '.$page->ID.')';
        }
        return $options;
	}

    public function get_payment_plans($plansOnly=false) {

        if(!isset($_GET['post'])) {
            return;
        }

        $integrations = array('' => __('Any','ncs-cart'));
        
        $id = intval($_GET['post']);
        
        $items = get_post_meta($id, '_sc_pay_options', true);
        if(is_array($items)) {
            foreach ( $items as $item ) {
                $type = $item['product_type'] ?? '';
                if ( (isset($item['option_id']) && $item['option_id'] != null) && (isset($item['option_name']) && $item['option_name'] != null ) ) {               
                    if(!$plansOnly || ($plansOnly =='coupons' && $type != 'free') || ($plansOnly && $type == 'recurring')) {
                        $integrations[$item['option_id']] = $item['option_name'];
                    }
                    
                    if ( !$plansOnly && isset($item['sale_option_name']) ) {
                        $integrations[$item['option_id'].'_sale'] = $item['sale_option_name'] .' '. __('(on sale)','ncs-cart');
                    }
                }
            }
        }
        
        if ( sc_fs()->is__premium_only() && sc_fs()->can_use_premium_code() ) {
            // webhook
            if(!$plansOnly){
                $integrations['bump'] = __('Purchased as a bump','ncs-cart');
                $integrations['upsell'] = __('Purchased as an upsell','ncs-cart');
                $integrations['downsell'] = __('Purchased as a downsell','ncs-cart');
            }
        }        
		return $integrations;
    }
    
    public function get_bumps() {

        if(!isset($_GET['post'])) {
            return;
        }
        
        $options = array('' => __('Any','ncs-cart'));

        $id = intval($_GET['post']);

        if($ob_id = get_post_meta($id, '_sc_ob_product', true)) {
            $ob_id = intval($ob_id);
            if(!get_post_meta($id, '_sc_ob_replace', true)){ // don't add as an option if the bump replaces the main product
                $options['main'] =  __('Main Bump','ncs-cart') . ' ('.get_the_title($ob_id).')';
            }
        }

        if($bumps = get_post_meta($id, '_sc_order_bump_options', true)) {
            foreach($bumps as $k=>$bump) {
                if(isset($bump['ob_product'])){
                    $ob_id = intval($bump['ob_product']);
                    $options[$k+1] = sprintf(__("Add'l Bump %d (%s)","ncs-cart"), $k+1, get_the_title($ob_id));
                }
            }
        }
        
		return $options;
    }

    public function multi_order_bump_fields($save = false) {
        
        $fields = array(
            array('checkbox'=>array(
                'class'		=> '',
                'description'	=> '',
                'id'			=> 'order_bump',
                'label'		=> __('Enable Order Bump','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> '',
                'class_size'		=> '',
            )),
            array('text'=>array(
                'class'		=> 'sc-color-field',
                'description'	=> '',
                'id'			=> 'bump_bg_color',
                'label'		=> __('Background Color','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
                'class_size'    => '',
            )),
            array('select'=>array(
                'class'		    => 'update-plan-product required',
                'description'	=> '',
                'id'			=> 'ob_product',
                'label'	    	=> __('Select Product','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => $this->product_options(),
                'class_size'=> '',
            )),
            array('select'=>array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> 'ob_type',
                'label'	    	=> __('Price Type','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => array(''=>__('Enter price','ncs-cart'),'plan'=>__('Existing payment plan','ncs-cart')),
                'class_size'=> '',
            )),              
            array('select'=>array(
                'class'		    => 'widefat update-plan ob-{val}',
                'description'	=> __('Select an existing payment plan for this product. One-time charges only.','ncs-cart'),
                'id'			=> 'ob_plan',
                'label'		    => __('Payment Plan ID','ncs-cart'),
                'placeholder'	=> '',
                'value'		    => '',
                'selections'    => $this->get_plans('_sc_order_bump_options'),
                'class_size'    => '',
                'step'          => 'any',
                'type'          => 'select',
                'conditional_logic' => array(
                                                array(
                                                    'field' => 'ob_type',
                                                    'value' => 'plan',
                                                )
                                            ),
            )),
            array('text'=>array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> 'ob_price',
                'label'		    => __('Price','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => '',
                'conditional_logic' => array(
                                                array(
                                                    'field' => 'ob_type',
                                                    'value' => 'plan',
                                                    'compare' => '!=',
                                                )
                                            ),
            )),
            array('file-upload'=>array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> 'ob_image',
                'label'		=> __('Product Image','ncs-cart'),
                'label-remove'		=> __('Remove Image','ncs-cart'),
                'label-upload'		=> __('Set Image','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'file-upload',
                'field-type'		=> 'url',
                'value'		=> '',
                'class_size'=> ''
            )),
            array('select'=>array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> 'ob_image_pos',
                'label'	    	=> __('Image Position','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => array(''=>__('Left','ncs-cart'),'top'=>__('Top','ncs-cart')),
                'class_size'=> ''
            )),
            array('text'=>array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> 'ob_cb_label',
                'label'		=> __('Checkbox Label','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
                'class_size'    => '',
            )),
            array('text'=>array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> 'ob_headline',
                'label'		=> __('Headline','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
                'class_size'    => '',
            )),
            array('textarea'=>array(
                'class'		=> '',
                'description'	=> '',
                'id'			=> 'ob_description',
                'label'		=> __('Product Description','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'textarea',
                'value'		=> '',
                'class_size'    => '',
            )),
        );
        
        return $fields;
    }

    private function fa_icons() {
        $options = array('none'=> __('none','ncs-cart'));
        $dir    = plugin_dir_path( __FILE__ ) . '../../includes/vendor/font-awesome/svgs/solid';
        $files = scandir($dir);
        foreach($files as $file) {
            if($file == '.' || $file == '..' || $file == '.DS_Store') {
                continue;
            }
            $name = str_replace('.svg', '', $file);
            $options[$file] = $name;
        }
        return $options;
    }

    public function upsell_paths(){
        if(!isset($_GET['post'])) return;

        $options = array('' => __('-- Select Upsell Path --','ncs-cart'));
                
        // The Query
        $args = array(
            'post_type' => array( 'sc_us_path' ),
            'post_status' => 'publish'
        );
        $the_query = new WP_Query( $args );

        // The Loop
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                $options[get_the_ID()] = get_the_title() . ' (ID: '.get_the_ID().')';
            }
        } else{
            $options = false;
		}
        /* Restore original Post Data */
        wp_reset_postdata();
        
		return $options;
	}

    public function get_plans($key) {
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

    public function get_plan_data($product_id){
        $product_plan_data = get_post_meta($product_id, '_sc_pay_options', true);

        if(!$product_plan_data) {
            return array(""=> esc_html__('No plans found'));
        } else {
            $options = array();
            foreach ( $product_plan_data as $val ) {
                $options[$val['option_id']] = $val['option_name'];
            }
            if(!empty($options)) {
                return $options;
            } else {
                return array(""=>esc_html__('No plans found'));
            }
        }
    }
    
    public function get_fields($save) {
        
        if(!isset($_GET['post'])) {
            return;
        }
        
        $id = intval($_GET['post']);
        $options = array();
        
        if ($default_fields = get_post_meta($id, '_sc_default_fields', true)) {        
            foreach($default_fields as $k=>$f) {
                if(!isset($f['default_field_disabled'])) {
                    $options[$k] = $f['default_field_label'];
                }
            }
        }
        
        if ($custom_fields = get_post_meta($id, '_sc_custom_fields', true)) {
            foreach($custom_fields as $field) {
                if(isset($field['field_id'])) {
                    $options[$field['field_id']] = $field['field_label'];
                }
            }
        }
        return $options;
    }

    public function set_field_groups($save = false) {
        
        $post_id = $_GET['post'] ?? null;
        $post_id = ( $save ? null : absint($post_id) );
        
          $this->fields['general'] = array(
            'label' => __('General','ncs-cart'),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_product_name',
                'label'		    => __('Public Product Name','ncs-cart'),
                'placeholder'	=> __('Leave empty to use the main product title','ncs-cart'),
                'type'		    => 'text',
            ),
            array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> '_sc_hide_title',
                'label'		    => __('Hide Page Title','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'checkbox',
                'value'		    => '',
                'class_size'	=> ''
            ),
            array(
                'class'		=> 'sc-color-field',
                'description'	=> '',
                'id'			=> '_sc_header_color',
                'label'		=> __('Header Background Color','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'text',
                'value'		=> '',
            ),
            array(
                'class'		=> 'widefat',
                'description'	=> '',
                'id'			=> '_sc_header_image',
                'label'		=> __('Header Background Image','ncs-cart'),
                'label-remove'		=> __('Remove Image','ncs-cart'),
                'label-upload'		=> __('Set Image','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'file-upload',
                'field-type'		=> 'url',
                'value'		=> '',
            ), 
            array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> '_sc_display',
                'label'	    	=> __('Form Skin','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => array(''=>__('Default','ncs-cart'),'two_step'=>__('2-Step','ncs-cart'),'opt_in'=>__('Opt-in (displays free plans only)','ncs-cart')),
                'class_size'=> ''
            ),
            array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> '_sc_product_page_redirect',
                'label'	    	=> __('Redirect this page to','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'selections'    => array(''=>__('Home Page','ncs-cart')),
                'class_size'=> '',
                'conditional_logic' => array(
					array(
						'field' => '_sc_hide_product_page',
						'value' => true,
					)
				)
            ),
            array(
                'class'         => 'repeater',
                'id'            => '_sc_custom_fields',
                'label-add'		=> __('+ Add New','ncs-cart'),
                'label-edit'    => __('Edit Field','ncs-cart'),
                'label-header'  => __('Custom Field','ncs-cart'),
                'label-remove'  => __('Remove Field','ncs-cart'),
                'title-field'	=> 'name',
                'type'		    => 'repeater',
                'value'         => '',
                'class_size'    => '',
                'fields'        => array(
                                    array(
                                    'text' =>array(
                                        'class'		    => 'widefat required',
                                        'description'	=> __('Allows `A-z 0-9`, dashes, &amp; underscores without spaces. Must be unique for this product.','ncs-cart'),
                                        'id'			=> 'field_id',
                                        'label'		    => __('Field ID','ncs-cart'),
                                        'placeholder'	=> '',
                                        'type'		    => 'text',
                                        'value'		    => '',
                                        'class_size'    => 'one-half first',
                                    )),
                                    array(
                                    'text' =>array(
                                        'class'		    => 'widefat repeater-title required',
                                        'description'	=> '',
                                        'id'			=> 'field_label',
                                        'label'		    => __('Label','ncs-cart'),
                                        'placeholder'	=> '',
                                        'type'		    => 'text',
                                        'value'		    => '',
                                        'class_size'    => 'one-half',
                                    )),
                                                        
                )
            ),
          );
        
    }

} // class
