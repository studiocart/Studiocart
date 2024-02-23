<?php

/**
 * The collections specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.1
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/collections
 */

 class NCS_Cart_Collections {
    public function __construct() {
        require_once plugin_dir_path( __FILE__ ) . 'ScrtCollection.php';
        add_filter('sc_after_order_load_from_post', [$this, 'setup_collection']);
        //add_filter('studiocart_order', [$this, 'init_collection']);
        add_action('init', [$this, 'register_post_type'], 99);
        add_action( 'manage_sc_collection_posts_custom_column', [$this, 'custom_column'], 10, 2 );
        add_filter( 'manage_sc_collection_posts_columns', [$this, 'set_custom_edit_columns'] );

    }

    public function set_custom_edit_columns($columns) {
		$columns['shortcode'] = __( 'Shortcode', 'ncs-cart' );
		return $columns;
	}

    public function custom_column( $column, $post_id ) {
		switch ( $column ) {

			case 'shortcode' :
				echo '<code>['.apply_filters('studiocart_slug', 'studiocart') . '-form'.' id='.$post_id.']</code>';
				break;

		}
	}

    function register_post_type() {
        $post_type = array(
            'cap_type' 	=> 'sc_product',
            'plural' 	=> __('Collections', 'ncs-cart'),
            'single' 	=> __('Collection', 'ncs-cart'),
            'cpt_name' 	=> 'sc_collection',
            'supports'  => array( 'title', 'editor', 'thumbnail'  ),
            'public'    => true
        );
        $post_type = apply_filters('sc_register_post_type_'.$post_type['cpt_name'].'_args', $post_type);
        NCS_Cart_Post_Types::register_single_post_type( $post_type['cap_type'], $post_type['plural'], $post_type['single'], $post_type['cpt_name'], $post_type['supports'], $post_type['public'] );
    }

    function setup_collection($order) {
        global $scp;
        if (get_post_type($scp->ID) != 'sc_collection') {
            return $order;
        }
        
        $order = new ScrtCollection();
        $order->load_from_post();        
        return $order;
    }

    function init_collection($order) {
        if (!isset($order->product_id) || get_post_type($order->product_id) != 'sc_collection') {
            return $order;
        }
        
        $order = new ScrtCollection($order->id);
        return $order;
    }
}

class NCS_Cart_Collections_Metabox_Fields extends NCS_Cart_Post_Metabox_Fields {
    public function __construct() {
        $this->plugin_name = 'ncs-cart';
		$this->post_type = 'sc_collection';
		$this->set_meta();

        add_action( 'wp', array($this, 'set_global'), 99 );
        add_action( 'admin_init', array($this, 'validate_c_meta'), 99 );
        add_action( 'add_meta_boxes', array($this, 'set_global'), 99 );
        add_action( 'sc_checkout_form_open', array($this, 'checkout_form'), 1 );
        add_filter( 'sc_create_stripe_intent', array($this, 'check_order_amount'), 10, 2 );

        add_filter('sc_product_post_type', function($type){ 
            $type = (array) $type;
            $type[] = $this->post_type;
            return $type;  
        });

        if($this->get_edit_post_type() == $this->post_type) { 
            add_filter('sc_product_setting_tabs', [$this, 'collection_tabs']);

            add_filter('sc_product_general_fields', [$this, 'product_string_replace']);
            add_filter('sc_pricing_fields', [$this, 'product_string_replace']);

            add_filter('sc_product_general_fields', [$this, 'general_fields']);
            add_filter('sc_pricing_fields', [$this, 'product_fields']);

            add_filter('sc_product_field_scripts', [$this, 'field_scripts']);
        }
	}

    function check_order_amount($create_intent, $order) {
        if (!$order->plan && $order->amount) {
            $create_intent = true;
        }
        return $create_intent;
    }

    function checkout_form($product_id) {
        if (get_post_type($product_id) != $this->post_type) { return; }
        if (get_post_meta($product_id, '_sc_price_type', true) == 'product') {
            remove_action('sc_card_details_fields', 'sc_payment_plan_options', 1); 
            remove_action('sc_card_details_fields', 'sc_payment_plan_options', 5);
            add_action('sc_card_details_fields', [$this, 'do_coupon_fields'], 1);
        }
    }

    function do_coupon_fields($post_id) {
        
        if (get_post_meta($post_id, '_sc_price_type', true) == 'product' && get_post_meta($post_id, '_sc_qty_field', true)) {
            return;
        }

        echo '<div style="margin: 0">';
        do_action('sc_coupon_fields', $post_id); 
        do_action('sc_coupon_status', $post_id);
        echo '</div>';
    }

    function field_scripts($scripts) {
        $scripts .= "
        if($('#_sc_price_type').val() == 'product') {
            $('.ridprod_plan, .ridprod_on_sale, .ridprod_show_full_price:visible').removeAttr('style');
        } else {
            $('.ridprod_plan, .ridprod_on_sale, .ridprod_show_full_price:visible').hide();
        }

        $('#_sc_price_type').change(function(){
            if($(this).val() == 'product') {
            $('.ridprod_plan, .ridprod_on_sale, .ridprod_show_full_price:visible').removeAttr('style');
        } else {
            $('.ridprod_plan, .ridprod_on_sale, .ridprod_show_full_price:visible').hide();
        }
        });";

        return $scripts;

    }

    function collection_tabs($tabs) {
        $tabs['pricing'] = __('Products &amp; Pricing', 'ncs-cart');
        return $tabs;
    }

    function product_string_replace($fields) {
        foreach($fields as $k=>$field) {
            foreach(['label', 'description', 'note', 'placeholder'] as $key) {
                if (isset($field[$key])) {
                    $fields[$k][$key] = $this->product_str_replace($field[$key]);
                }
            }
        }
        return $fields;
    }

    function product_str_replace($val) {
        return str_replace([__('product', 'ncs-cart'), __('Product', 'ncs-cart')], [__('collection', 'ncs-cart'), __('Collection', 'ncs-cart')], $val);
    }

    public function set_global() {
        global $is_studiocart;
        if (get_post_type() == 'sc_collection' && !$is_studiocart) {
            $is_studiocart = true;
        }
    }

    public function validate_c_meta() {
        global $sc_product_fields;
        add_action( 'save_post_sc_collection', array($sc_product_fields, 'validate_meta'), 10, 2 );
    }

    public function get_edit_post_type() {

        if(isset($_POST['sc-edit-collection'])) {
            return $this->post_type;
        }

        if(isset($_GET['post_type'])) {
            return sanitize_text_field($_GET['post_type']);
        }

        $post_id = $_GET['post'] ?? null;
        $post_id = absint($post_id);

        if(!$post_id) {
            return false;
        }

        if($type = get_post_type($post_id)) {
            return $type;
        }

        return false;
    }
    
    public function general_fields($fields) {  
        //if(isset($fields['Tax Status'])) { unset($fields['Tax Status']); }
        array_unshift($fields,
        array(
            'class'		    => '',
            'description'	=> '',
            'id'			=> '_sc_edit_collection',
            'label'	    	=> '',
            'placeholder'	=> '',
            'type'		    => 'hidden',
            'value'		    => 1,
            'class_size'    => 'hide'
        ));
        return $fields;  
    }

    public function product_fields($fields) {  
        array_unshift($fields,
        array(
            'class'		    => '',
            'description'	=> '',
            'id'			=> '_sc_price_type',
            'label'	    	=> __('Pricing','ncs-cart'),
            'placeholder'	=> '',
            'type'		    => 'select',
            'value'		    => '',
            'selections'    => array('custom'=>__('Use custom prices','ncs-cart'), 'product'=>__('Use product prices','ncs-cart')),
            'class_size'=> ''
        ));
        $fields['Hide Plans Section']['conditional_logic'] = array(
            array(
                'field' => '_sc_price_type',
                'compare' => '!=',
                'value' => 'product',
            )
        );
        $fields['Section Heading']['conditional_logic'][] = array(
                'field' => '_sc_price_type',
                'compare' => '!=',
                'value' => 'product',
        );
        $fields['On Sale?']['conditional_logic'] = array(
            array(
                'field' => '_sc_price_type',
                'compare' => '!=',
                'value' => 'product',
            )
        );
        $fields['Schedule Sale?']['conditional_logic'] = array(
            array(
                'field' => '_sc_price_type',
                'compare' => '!=',
                'value' => 'product',
            )
        );
        $fields['Payment Plan']['conditional_logic'] = array(
            array(
                'field' => '_sc_price_type',
                'compare' => '!=',
                'value' => 'product',
            )
        );

        $new_fields = array();
        
        foreach($fields as $k=>$field) {
            $new_fields[$k] = $field;
            if($k == "Section Heading") {
                $new_fields[] = array(
                    'class'		=> 'widefat',
                    'id'		=> '_sc_products_heading',
                    'type'		=> 'html',
                    'value'		=> '<div id="rid_sc_products_heading" class="sc-field sc-row"><div class="input-group field-text"><div style="width: 100%;"><h4 style="margin-bottom: 0;padding-bottom: 7px;border-bottom: 1px solid #d5d5d5;font-weight: normal;"><b>'.__('Add Pricing', 'ncs-cart').'</b></h4></div></div></div>',
                    'class_size'=> '',
                    'conditional_logic' => array(
                        array(
                            'field' => '_sc_price_type',
                            'compare' => '!=',
                            'value' => 'product',
                        ),
                    )
                );
            }
        }

        $fields = $new_fields;

        $fields[] = array(
            'class'		=> 'widefat',
            'id'		=> '_sc_products',
            'type'		=> 'html',
            'value'		=> '<div id="rid_sc_additional" class="sc-field sc-row"><div class="input-group field-text"><div style="width: 100%;"><h4 style="margin-bottom: 0;padding-bottom: 7px;border-bottom: 1px solid #d5d5d5;font-weight: normal;"><b>'.__('Add Products', 'ncs-cart').'</b></h4></div></div></div>',
            'class_size'=> '',
            'conditional_logic' => '',
        );
        $fields[] = array(
            'class'         => 'repeater',
            'id'            => '_sc_product_options',
            'label-add'		=> __('+ Add Product','ncs-cart'),
            'label-edit'    => __('Edit Product','ncs-cart'),
            'label-header'  => __('Product','ncs-cart'),
            'label-remove'  => __('Remove Product','ncs-cart'),
            'title-field'	=> 'name',
            'type'		    => 'repeater',
            'value'         => '',
            'class_size'    => '',
            'fields'        => $this->product_repeater_fields(),
        );

        return $fields;
    }

    public function get_plan_data($product_id){
        $product_plan_data = get_post_meta($product_id, '_sc_pay_options', true);

        if(!$product_plan_data) {
            return false;
        } else {
            $options = array();
            foreach ( $product_plan_data as $val ) {
                if (!isset($val['product_type']) || $val['product_type']=='free') {
                    $label = $val['option_name'] ?? $val['option_id'];
                    $options[$val['option_id']] = $label;
                }
            }
            if(!empty($options)) {
                return $options;
            } else {
                return false;
            }
        }
    }
    
    public function product_options(){
        
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

    public function product_repeater_fields() {
        //if ($save = false) {
        
            $fields = array(
                array('select'=>array(
                    'class'		    => 'update-plan-product required repeater-title',
                    'description'	=> '',
                    'id'			=> 'prod_product',
                    'label'	    	=> __('Select Product','ncs-cart'),
                    'placeholder'	=> '',
                    'type'		    => 'select',
                    'value'		    => '',
                    'selections'    => $this->product_options(),
                    'class_size'=> '',
                )),            
                array('select'=>array(
                    'class'		    => 'widefat update-plan ob-{val}',
                    'description'	=> __('Select an existing payment plan for this product. One-time prices only.','ncs-cart'),
                    'id'			=> 'prod_plan',
                    'label'		    => __('Payment Plan ID','ncs-cart'),
                    'placeholder'	=> '',
                    'value'		    => '',
                    'selections'    => $this->get_collection_plans('_sc_product_options'),
                    'class_size'    => '',
                    'step'          => 'any',
                    'type'          => 'select',
                )),
                array('checkbox'=> array(
                    'class'		=> 'widefat',
                    'note'	=> '',
                    'id'			=> 'prod_on_sale',
                    'label'		=> __('Use on sale price','ncs-cart'),
                    'placeholder'	=> '',
                    'type'		=> 'checkbox',
                    'value'		=> '',
                    'class_size'		=> '',
                )),
                array('checkbox'=> array(
                    'class'		=> 'widefat',
                    'note'	=> '',
                    'id'			=> 'prod_show_full_price',
                    'label'		=> __('Show original price','ncs-cart'),
                    'placeholder'	=> '',
                    'type'		=> 'checkbox',
                    'value'		=> '',
                    'class_size'		=> '',
                    'conditional_logic' => array(
                        array(
                            'field' => 'prod_on_sale',
                            'value' => true,
                        )
                    ),
                )),
            );
            
            return $fields;
        //}
    }

    private function get_collection_plans($key) {
        if(!isset($_GET['post'])) {
            return;
        }
        $options = array();
        $post_id = intval($_GET['post']);

        if ($product_id = get_post_meta($post_id, $key, true)) {
            
            if(is_array($product_id)){
                foreach($product_id as $prod_id){
                    $pid = $prod_id['prod_product'];
                    $options[$pid] = $this->get_plan_data($pid);
                }
            } else {
                $options = $this->get_plan_data($product_id);
            }
        }
        return $options;
    }
}

$mb_fields = new NCS_Cart_Collections_Metabox_Fields();
$sc_collections = new NCS_Cart_Collections();