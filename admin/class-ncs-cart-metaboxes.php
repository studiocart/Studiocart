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
class NCS_Cart_Product_Metaboxes
{
    /**
     * The post meta data
     *
     * @since 		1.0.0
     * @access 		private
     * @var 		string 			$meta    			The post meta data.
     */
    private  $meta ;
    /**
     * The ID of this plugin.
     *
     * @since 		1.0.0
     * @access 		private
     * @var 		string 			$plugin_name 		The ID of this plugin.
     */
    private  $plugin_name ;
    /**
     * The version of this plugin.
     *
     * @since 		1.0.0
     * @access 		private
     * @var 		string 			$version 			The current version of this plugin.
     */
    private  $version ;
    /**
     * The prefix of this plugin.
     *
     * @since 		1.0.0
     * @access 		private
     * @var 		string 			prefix 			The prefix of this plugin.
     */
    private  $prefix ;
    /**
     * Initialize the class and set its properties.
     *
     * @since 		1.0.0
     * @param 		string 			$Studiocart 		The name of this plugin.
     * @param 		string 			$version 			The version of this plugin.
     */
    private  $general ;
    private  $access ;
    private  $payments ;
    private  $pricing ;
    private  $tax ;
    private  $fields ;
    private  $coupons ;
    private  $orderbump ;
    private  $upsell ;
    private  $downsell ;
    private  $confirmation ;
    private  $notifications ;
    private  $integrations ;
    private  $tracking ;
    public function __construct( $plugin_name, $version, $prefix )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->prefix = $prefix;
        $this->tax_enable = false;
        //get_option( '_sc_tax_enable', false );
        $this->set_meta();
        add_filter(
            'sc_integration_fields',
            array( $this, 'add_consent_field' ),
            10,
            2
        );
    }
    
    /**
     * Registers metaboxes with WordPress
     *
     * @since 	1.0.0
     * @access 	public
     */
    public function add_metaboxes()
    {
        // add_meta_box( $id, $title, $callback, $screen, $context, $priority, $callback_args );
        $this->set_field_groups();
        add_meta_box(
            'sc-product-settings',
            apply_filters( $this->plugin_name . '-metabox-title-product-settings', esc_html__( 'Product Settings', 'ncs-cart' ) ),
            array( $this, 'product_settings_fields' ),
            'sc_product',
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
    private function check_nonces( $posted )
    {
        $nonces = array();
        $nonce_check = 0;
        $nonces[] = 'sc_fields_nonce';
        foreach ( $nonces as $nonce ) {
            if ( !isset( $posted[$nonce] ) ) {
                $nonce_check++;
            }
            if ( isset( $posted[$nonce] ) && !wp_verify_nonce( $posted[$nonce], $this->plugin_name ) ) {
                $nonce_check++;
            }
        }
        return $nonce_check;
    }
    
    // check_nonces()
    /**
     * Returns an array of the all the metabox fields and their respective types
     *
     * @since 		1.0.0
     * @access 		public
     * @return 		array 		Metabox fields and types
     */
    private function get_metabox_fields()
    {
        $this->set_field_groups( true );
        $fields = array();
        $groups = array(
            'general',
            'access',
            'payments',
            'pricing',
            'confirmation',
            'notifications',
            'integrations',
            'tracking'
        );
        if ( $this->tax_enable ) {
            $groups[] = 'tax';
        }
        foreach ( $groups as $id ) {
            foreach ( $this->{$id} as $group ) {
                $type = ( isset( $group['field-type'] ) ? $group['field-type'] : $group['type'] );
                $set = array( @$group['id'], $type );
                
                if ( $group['type'] == 'repeater' ) {
                    $r_fields = array();
                    foreach ( $group['fields'] as $gfield ) {
                        foreach ( $gfield as $k => $v ) {
                            $field = array( $v['id'], $k );
                            $pos = strpos( $v['class'], 'required' );
                            if ( $pos !== false && !isset( $v['conditional_logic'] ) ) {
                                $field[] = 'required';
                            }
                            $r_fields[] = $field;
                        }
                    }
                    $set[] = $r_fields;
                }
                
                $fields[] = $set;
            }
        }
        /*$fields[] = array( 'job-requirements-education', 'textarea' );
        		$fields[] = array( 'job-requirements-experience', 'textarea' );
        		$fields[] = array( 'job-requirements-skills', 'textarea' );
        		$fields[] = array( 'job-additional-info', 'textarea' );
        		$fields[] = array( 'job-responsibilities', 'textarea' );
        		$fields[] = array( 'job-location', 'text' );
        		$fields[] = array( 'file-repeater', 'repeater', array( array( 'label-file', 'text', 'required' ), array( 'url-file', 'url' ) ) );*/
        return $fields;
    }
    
    // get_metabox_fields()
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
    public function product_settings_fields( $post, $params )
    {
        if ( !is_admin() ) {
            return;
        }
        if ( 'sc_product' !== $post->post_type ) {
            return;
        }
        $tabs = array(
            'general'       => __( 'General', 'ncs-cart' ),
            'access'        => __( 'Page Access', 'ncs-cart' ),
            'pricing'       => __( 'Payment Plans', 'ncs-cart' ),
            'confirmation'  => __( 'Order Confirmation', 'ncs-cart' ),
            'notifications' => __( 'Notifications', 'ncs-cart' ),
            'integrations'  => __( 'Integrations', 'ncs-cart' ),
            'tracking'      => __( 'Tracking', 'ncs-cart' ),
        );
        
        if ( $this->tax_enable ) {
            $tax = array(
                'tax' => 'Tax Setting',
            );
            $_tabs = array_merge( array_slice( $tabs, 0, 4 ), $tax, array_slice( $tabs, 4 ) );
            $tabs = $_tabs;
        }
        
        $tabs = apply_filters( 'sc_product_setting_tabs', $tabs );
        echo  '<div class="sc-settings-tabs">' ;
        wp_nonce_field( $this->plugin_name, 'sc_fields_nonce' );
        echo  '<div class="sc-left-col">' ;
        $i = 0;
        foreach ( $tabs as $id => $label ) {
            $active = ( $i == 0 ? 'active' : '' );
            echo  '<div class="sc-tab-nav ' . $active . '"><a href="#sc-tab-' . $id . '">' . $label . '</a></div>' ;
            $i++;
        }
        echo  '</div>' ;
        echo  '<div class="sc-right-col">' ;
        $i = 0;
        foreach ( $tabs as $id => $label ) {
            $fields = $this->{$id} ?? array();
            $fields = apply_filters( "sc_product_setting_tab_{$id}_fields", $fields );
            echo  '<div id="sc-tab-' . $id . '" class="sc-tab ' . $active . '">' ;
            $this->metabox_fields( $fields );
            echo  '</div>' ;
            $i++;
        }
        echo  '</div>
        </div>' ;
    }
    
    private function metabox_fields( $fields )
    {
        $_GET['post'] = $_GET['post'] ?? 0;
        $scripts = '';
        $hide_fields = ( isset( $this->meta['_sc_hide_fields'] ) ? maybe_unserialize( $this->meta['_sc_hide_fields'][0] ) : array() );
        foreach ( $fields as $atts ) {
            $defaults['class_size'] = '';
            $defaults['description'] = '';
            $defaults['label'] = '';
            $atts = wp_parse_args( $atts, $defaults );
            
            if ( $atts['type'] != 'repeater' ) {
                
                if ( $atts['type'] == 'html' ) {
                    echo  $atts['value'] ;
                    
                    if ( $atts['id'] == '_sc_default_fields' || $atts['id'] == '_sc_address_fields' ) {
                        $setatts = $atts;
                        apply_filters( $this->plugin_name . '-field-repeater-' . $setatts['id'], $setatts );
                        $count = 0;
                        $repeater = array();
                        if ( !empty($this->meta['_sc_default_fields']) ) {
                            $repeater = maybe_unserialize( $this->meta['_sc_default_fields'][0] );
                        }
                        include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-default-fields.php';
                    }
                
                } else {
                    
                    if ( $atts['type'] == 'checkbox' && $this->meta[$atts['id']][0] ) {
                        $atts['value'] = 1;
                    } else {
                        $atts['value'] = $this->meta[$atts['id']][0];
                    }
                    
                    apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );
                    $name = str_replace( '_sc_', 'sc-', $atts['id'] );
                    $atts['name'] = str_replace( '_', '-', $name );
                    $atts['name'] = $atts['id'];
                    ?><div id="rid<?php 
                    echo  $atts['id'] ;
                    ?>" class="sc-field sc-row <?php 
                    echo  $atts['class_size'] ;
                    ?>"><?php 
                    
                    if ( file_exists( plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-' . $atts['type'] . '.php' ) ) {
                        include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-' . $atts['type'] . '.php';
                    } else {
                        include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-text.php';
                    }
                    
                    ?></div><?php 
                }
                
                // conditional logic
                
                if ( !empty($atts['conditional_logic']) ) {
                    $conditions = array();
                    $row_id = 'rid' . $atts['id'];
                    foreach ( $atts['conditional_logic'] as $l ) {
                        
                        if ( $l['compare'] == 'IN' || $l['compare'] == 'NOT IN' ) {
                            $scripts .= 'var arr_' . $atts['id'] . ' = ' . json_encode( $l['value'] ) . ';';
                            
                            if ( $l['compare'] == 'IN' ) {
                                $conditions[] = sprintf( "(arr_%s.includes( \$('#%s').val()))", $atts['id'], $l['value'] );
                            } else {
                                $conditions[] = sprintf( "(!arr_%s.includes( \$('#%s').val()))", $atts['id'], $l['value'] );
                            }
                        
                        } else {
                            if ( !isset( $l['compare'] ) || $l['compare'] == '=' ) {
                                $l['compare'] = '==';
                            }
                            $eval = ( $l['value'] === true ? $eval = $l['field'] . ':checked' : $l['field'] );
                            $conditions[] = sprintf(
                                "(\$('#%s').val() %s '%s')",
                                $eval,
                                $l['compare'],
                                $l['value']
                            );
                        }
                    
                    }
                    
                    if ( !empty($conditions) ) {
                        $conditions = implode( ' && ', $conditions );
                        $eval = sprintf(
                            "if ( %s ) { \$('#%s').fadeIn();} else { \$('#%s').hide();}",
                            $conditions,
                            $row_id,
                            $row_id
                        );
                        $scripts .= $eval . '$("#' . $l['field'] . '").change(function(){' . $eval . ';});
                        ';
                    }
                
                }
            
            } else {
                $setatts = $atts;
                apply_filters( $this->plugin_name . '-field-repeater-' . $setatts['id'], $setatts );
                $count = 0;
                $repeater = array();
                if ( !empty($this->meta[$setatts['id']]) ) {
                    $repeater = maybe_unserialize( $this->meta[$setatts['id']][0] );
                }
                if ( !empty($repeater) ) {
                    $count = count( $repeater );
                }
                include plugin_dir_path( __FILE__ ) . 'partials/' . $this->plugin_name . '-admin-field-repeater.php';
            }
        
        }
        
        if ( $scripts != '' ) {
            ?>
            <script type="text/javascript">
                jQuery('document').ready(function($){
                    <?php 
            echo  $scripts ;
            ?>
                    
                    
                    if(!$('#_sc_on_sale').is(':checked') && !$('#_sc_schedule_sale').is(':checked')) {
                        $('.ridsale_option_name, .ridsale_price').find('input').attr('disabled','').css({background:'#f0eeee', opacity: '0.6'}).removeClass('required error');
                        $('#rid_sc_show_full_price').hide();
                    } else {
                        $('.ridsale_option_name, .ridsale_price').find('input').removeAttr('disabled').removeAttr('style').addClass('required');
                        $('#rid_sc_show_full_price').show();
                    }
                    
                    $('#_sc_on_sale, #_sc_schedule_sale').change(function(){
                        if ($(this).is(':checked')) {
                            $('.ridsale_option_name, .ridsale_price').find('input').removeAttr('disabled').removeAttr('style').addClass('required');
                            $('#rid_sc_show_full_price').show();
                        } else if(!$('#_sc_on_sale').is(':checked') && !$('#_sc_schedule_sale').is(':checked')) {
                            $('.ridsale_option_name, .ridsale_price').find('input').attr('disabled','').css({background:'#f0eeee', opacity: '0.6'}).removeClass('required error');
                            $('#rid_sc_show_full_price').hide();
                        }
                    });
                    
                    $('.service_select').each(function(){
                        var options = $(this).closest(".repeater-content").find(".riduser_role select option");
                        if($(this).val() == 'tutor'){
                            options.each(function(){
                                if ($(this).val() != 'subscriber' && $(this).val() != 'tutor_instructor') {
                                    $(this).hide();
                                }
                            });
                        } else {
                            options.show();
                        }
                    });
                    $('.service_select').on('change', function(){
                        var options = $(this).closest(".repeater-content").find(".riduser_role select option");
                        if($(this).val() == 'tutor'){
                            options.each(function(){
                                if ($(this).val() != 'subscriber' && $(this).val() != 'tutor_instructor') {
                                    $(this).hide();
                                }
                            });
                        } else {
                            options.show();
                        }
                    });
                    $('.riddrip_action select').on('change', function(){
                        var fields = '.riddrip_tag';
                        if ($(this).val() != "unsubscribe" && $(this).closest(".repeater-content").find('.service_select').val()=='drip') {
                            $(this).closest(".repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    $('.riddrip_action select').each(function(){
                        var fields = '.riddrip_tag';
                        if ($(this).val() != "unsubscribe" && $(this).closest(".repeater-content").find('.service_select').val()=='drip') {
                            $(this).closest(".repeater-content").find(fields).show();
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    $('.ridtutor_action select').each(function(){
                        var fields = '.riduser_role';
                        if ($(this).val() == "enroll" && (
                            $(this).closest(".repeater-content").find('.service_select').val()=='create user' ||
                            $(this).closest(".repeater-content").find('.service_select').val()=='tutor'
                            )
                        ) {
                            $(this).closest(".repeater-content").find(fields).show();
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    $('.ridtutor_action select').on('change', function(){
                        var fields = '.riduser_role';
                        if ($(this).val() == "enroll" && (
                            $(this).closest(".repeater-content").find('.service_select').val()=='create user' ||
                            $(this).closest(".repeater-content").find('.service_select').val()=='tutor'
                            )
                        ) {
                            $(this).closest(".repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    
                    $('.ridwlm_action select').each(function(){
                        var fields = '.ridwlm_send_email, .ridwlm_pending';
                        if ($(this).val() == "add" && $(this).closest(".repeater-content").find('.service_select').val()=='wishlist') {
                            $(this).closest(".repeater-content").find(fields).show();
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    $('.ridwlm_action select').on('change', function(){
                        var fields = '.ridwlm_send_email, .ridwlm_pending';
                        if ($(this).val() == "add" && $(this).closest(".repeater-content").find('.service_select').val()=='wishlist') {
                            $(this).closest(".repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    
                    $('.ridservice_action select').each(function(){
                        var fields = '.ridconvertkit_forms';
                        if ($(this).val() == "subscribed" && $(this).closest(".repeater-content").find('.service_select').val()=='convertkit') {
                            $(this).closest(".repeater-content").find(fields).show();
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    $('.ridservice_action select').on('change', function(){
                        var fields = '.ridconvertkit_forms';
                        if ($(this).val() == "subscribed" && $(this).closest(".repeater-content").find('.service_select').val()=='convertkit') {
                            $(this).closest(".repeater-content").find(fields).css({ opacity: 0, display: "flex" }).animate({ opacity: 1 }, 400);
                        } else {
                            $(this).closest(".repeater-content").find(fields).hide();
                        }
                    });
                    
                    $('#_sc_show_address_fields').on('change', function(){
                        if ($(this).is(':checked')) {
                            $('#repeater_sc_address_fields, #rid_sc_address_fields').fadeIn(400);
                        } else {
                            $('#repeater_sc_address_fields, #rid_sc_address_fields').hide();
                        }
                    });
                    
                    $('#_sc_show_address_fields').each(function(){
                        if ($(this).is(':checked')) {
                            $('#repeater_sc_address_fields, #rid_sc_address_fields').fadeIn(400);
                        } else {
                            $('#repeater_sc_address_fields, #rid_sc_address_fields').hide();
                        }
                    });
                    
                });
            </script><?php 
            do_action( 'sc_product_field_scripts', intval( $_GET['post'] ) );
        }
    
    }
    
    private function sanitizer( $type, $data )
    {
        if ( empty($type) ) {
            return;
        }
        if ( empty($data) ) {
            return;
        }
        $return = '';
        $sanitizer = new NCS_Cart_Sanitize();
        $sanitizer->set_data( $data );
        $sanitizer->set_type( $type );
        $return = $sanitizer->clean();
        unset( $sanitizer );
        return $return;
    }
    
    // sanitizer()
    /**
     * Sets the class variable $options
     */
    public function set_meta()
    {
        
        if ( isset( $_GET['post'] ) ) {
            $post_id = absint( $_GET['post'] );
            // Always sanitize
            $post = get_post( $post_id );
            // Post Object, like in the Theme loop
            if ( 'sc_product' != $post->post_type ) {
                return;
            }
            //wp_die( '<pre>' . var_dump( $post->ID ) . '</pre>' );
            $this->meta = get_post_custom( $post->ID );
        }
        
        return;
    }
    
    // set_meta()
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
    public function validate_meta( $post_id, $object )
    {
        //wp_die( '<pre>' . print_r( $_POST ) . '</pre>' );
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
        if ( 'sc_product' !== $object->post_type ) {
            return $post_id;
        }
        $nonce_check = $this->check_nonces( $_POST );
        if ( 0 < $nonce_check ) {
            return $post_id;
        }
        $_POST['type'] = $_POST['coupon_type'];
        unset( $_POST['coupon_type'] );
        
        if ( !empty($_POST['_sc_coupon_files']) ) {
            $sc_coupon_files = $_POST['_sc_coupon_files'];
            unset( $_POST['_sc_coupon_files'] );
        }
        
        $metas = $this->get_metabox_fields();
        $stripe_objects = array();
        //wp_die( '<pre>' . var_dump( $metas ) . '</pre>' );
        foreach ( $metas as $meta ) {
            $new_value = '';
            $name = $meta[0];
            $type = $meta[1];
            
            if ( $name == '_sc_default_fields' ) {
                $new_value = array();
                foreach ( $_POST['_sc_default_fields'] as $key => $fields ) {
                    foreach ( $fields as $field => $val ) {
                        $new_value[$key][$field] = $this->sanitizer( 'text', $val );
                    }
                }
                update_post_meta( $post_id, $name, $new_value );
            } else {
                
                if ( 'repeater' === $type && is_array( $meta[2] ) ) {
                    $clean = array();
                    $remove = array();
                    foreach ( $meta[2] as $field ) {
                        //  array( 'label-file', 'text', 'required' )
                        
                        if ( isset( $_POST[$field[0]] ) ) {
                            $i = 0;
                            foreach ( $_POST[$field[0]] as $k => $data ) {
                                if ( empty($data) && isset( $field[2] ) && strpos( $field[2], 'required' ) !== false ) {
                                    $remove[] = $i;
                                }
                                
                                if ( is_array( $data ) ) {
                                    $field_arr = [];
                                    foreach ( $data as $d ) {
                                        $field_arr[] = $this->sanitizer( $field[1], $d );
                                    }
                                    $clean[$field[0]][$k] = $field_arr;
                                } else {
                                    $clean[$field[0]][$k] = $this->sanitizer( $field[1], $data );
                                }
                                
                                $i++;
                            }
                            // foreach
                        }
                        
                        // if
                    }
                    // foreach
                    $count = $this->get_max( $clean );
                    $new_value = array();
                    for ( $i = 0 ;  $i < $count ;  $i++ ) {
                        foreach ( $clean as $field_name => $field ) {
                            $new_value[$i][$field_name] = $field[$i];
                        }
                        // foreach $clean
                    }
                    // for
                    
                    if ( !empty($remove) ) {
                        foreach ( $remove as $r ) {
                            unset( $new_value[$r] );
                        }
                        $new_value = array_values( $new_value );
                    }
                    
                    $stripe_objects[$name] = $new_value;
                } else {
                    
                    if ( 'html' !== $type ) {
                        
                        if ( empty(@$_POST[$name]) && @$_POST[$name] !== '0' ) {
                            delete_post_meta( $post_id, $name );
                            continue;
                        }
                        
                        
                        if ( @$_POST[$name] === '0' ) {
                            $new_value = 0;
                        } else {
                            $new_value = $this->sanitizer( $type, $_POST[$name] );
                        }
                    
                    }
                
                }
            
            }
            
            update_post_meta( $post_id, $name, $new_value );
            //var_dump($name,$new_value);
        }
        // foreach
        //var_dump($stripe_objects);
        $stripe_product = new NCS_Cart_Product_Admin();
        $stripe_product->save_stripe_objects( $post_id, $stripe_objects );
        if ( $sc_coupon_files ) {
            $this->bulk_upload_coupon( $sc_coupon_files, $post_id );
        }
        do_action( 'sc_after_validate_meta', $post_id, $stripe_objects );
    }
    
    // validate_meta()
    /**
     * Returns the count of the largest arrays
     *
     * @param 		array 		$array 		An array of arrays to count
     * @return 		int 					The count of the largest array
     */
    public static function get_max( $array )
    {
        if ( empty($array) ) {
            return '$array is empty!';
        }
        $count = array();
        foreach ( $array as $name => $field ) {
            $count[$name] = count( $field );
        }
        //
        $count = max( $count );
        return $count;
    }
    
    // get_max()
    private function set_field_groups( $save = false )
    {
        $post_id = $_GET['post'] ?? null;
        $post_id = ( $save ? null : absint( $post_id ) );
        $this->general = array(
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_product_name',
            'label'       => __( 'Public Product Name', 'ncs-cart' ),
            'placeholder' => __( 'Leave empty to use the main product title', 'ncs-cart' ),
            'type'        => 'text',
        ),
            array(
            'class'       => 'sc-color-field',
            'description' => '',
            'id'          => '_sc_header_color',
            'label'       => __( 'Page Header Color', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_hide_title',
            'label'       => __( 'Hide Page Title', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_button_text',
            'label'       => __( 'Button Text', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => 'Order Now',
        ),
            array(
            'class'       => 'sc-color-field',
            'description' => '',
            'id'          => '_sc_button_color',
            'label'       => __( 'Primary Color', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '#000000',
        )
        );
        global  $sc_stripe ;
        if ( 1 != 1 && is_array( $sc_stripe ) ) {
            $this->general[] = array(
                'id'                => '_sc_product_fresh_setup',
                'type'              => 'html',
                'value'             => '<div id="_sc_product_fresh_setup" class="sc-field sc-row"><label>
                <a button href="javascript:void(0)" data-id="' . $post_id . '" rel="noopener noreferrer">Clear Stripe Meta Deta</a>
                <br>
                </label><div class="input-group field-text"></div></div>',
                'class_size'        => '',
                'conditional_logic' => '',
            );
        }
        $this->access = array(
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_manage_stock',
            'label'       => __( 'Limit product sales', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'             => 'widefat',
            'description'       => __( 'Total available # of this product. Once this reaches 0, the cart will close.', 'ncs-cart' ),
            'id'                => '_sc_limit',
            'label'             => __( 'Amount Remaining', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'number',
            'value'             => '',
            'class_size'        => '',
            'conditional_logic' => array( array(
            'field' => '_sc_manage_stock',
            'value' => true,
        ) ),
        ),
            array(
            'class'       => 'datepicker',
            'description' => '',
            'id'          => '_sc_cart_open',
            'label'       => __( 'Cart Opens', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half',
        ),
            array(
            'class'       => 'datepicker',
            'description' => '',
            'id'          => '_sc_cart_close',
            'label'       => __( 'Cart Closes', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half',
        ),
            array(
            'class'       => '',
            'description' => '',
            'id'          => '_sc_cart_close_action',
            'label'       => __( 'Cart Closed Action', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'select',
            'value'       => '',
            'selections'  => array(
            'message'  => 'Display message',
            'redirect' => 'Perform redirect',
        ),
            'class_size'  => '',
        ),
            array(
            'description'       => '',
            'id'                => '_sc_cart_redirect',
            'label'             => __( 'Cart Closed Redirect URL', 'ncs-cart' ),
            'value'             => '',
            'type'              => 'url',
            'conditional_logic' => array( array(
            'field' => '_sc_cart_close_action',
            'value' => 'redirect',
        ) ),
        ),
            array(
            'description'       => '',
            'id'                => '_sc_cart_closed_message',
            'label'             => __( 'Cart Closed Message', 'ncs-cart' ),
            'value'             => __( 'Sorry, this product is no longer for sale.', 'ncs-cart' ),
            'type'              => 'text',
            'conditional_logic' => array( array(
            'field' => '_sc_cart_close_action',
            'value' => 'message',
        ) ),
        )
        );
        $this->payments = array(
            array(
            'class'             => 'widefat',
            'id'                => '_sc_enabled_gateways',
            'type'              => 'html',
            'value'             => '<div id="rid_sc_enabled_gateways" class="sc-field sc-row"><div class="input-group field-text"><div style="width: 100%;"">
                                    <b>' . __( 'Globally Enabled Methods:', 'ncs-cart' ) . '</b> ' . sc_enabled_processors() . '<br/> 
                                    <a href="' . admin_url( 'admin.php?page=sc-admin#settings_tab_payment_methods' ) . '" target="_blank" rel="noopener noreferrer">' . __( 'Change settings', 'ncs-cart' ) . ' →</a>
                                    <h4 style="margin: 20px 0 0px;padding: 20px 0 5px;border-top: 1px solid #d5d5d5;">' . __( 'Allow customers to pay for this product via:', 'ncs-cart' ) . '</h4>
                                    </div></div></div>',
            'class_size'        => '',
            'conditional_logic' => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_disable_cod',
            'label'       => __( 'Cash on Delivery', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_disable_stripe',
            'label'       => __( 'Credit card (Stripe)', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_disable_paypal',
            'label'       => __( 'PayPal', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        )
        );
        $this->payments = apply_filters( 'sc_product_payments_fields', $this->payments, $post_id );
        $this->pricing = array(
            array(
            'class'       => 'widefat',
            'description' => __( 'Temporarily sell this product at a discounted price (overrides sale schedule)', 'ncs-cart' ),
            'id'          => '_sc_on_sale',
            'label'       => __( 'On Sale?', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_schedule_sale',
            'label'       => __( 'Schedule Sale?', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'             => 'datepicker',
            'description'       => '',
            'id'                => '_sc_sale_start',
            'label'             => __( 'Sale Start', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'text',
            'value'             => '',
            'class_size'        => 'one-half',
            'conditional_logic' => array( array(
            'field' => '_sc_schedule_sale',
            'value' => true,
        ) ),
        ),
            array(
            'class'             => 'datepicker',
            'description'       => '',
            'id'                => '_sc_sale_end',
            'label'             => __( 'Sale End', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'text',
            'value'             => '',
            'class_size'        => 'one-half',
            'conditional_logic' => array( array(
            'field' => '_sc_schedule_sale',
            'value' => true,
        ) ),
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_show_full_price',
            'label'       => __( 'Show original price', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => '_sc_hide_plans',
            'label'       => __( 'Hide Plans', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
            array(
            'class'             => 'widefat',
            'description'       => '',
            'id'                => '_sc_plan_heading',
            'label'             => __( 'Section Heading', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'text',
            'value'             => __( 'Payment Plan', 'ncs-cart' ),
            'class_size'        => 'one-half first',
            'conditional_logic' => array( array(
            'field'   => '_sc_hide_plans',
            'value'   => true,
            'compare' => '!=',
        ) ),
        ),
            array(
            'class'        => 'repeater',
            'id'           => '_sc_pay_options',
            'label-add'    => __( '+ Add New', 'ncs-cart' ),
            'label-edit'   => __( 'Edit Payment Plan', 'ncs-cart' ),
            'label-header' => __( 'Payment Plan', 'ncs-cart' ),
            'label-remove' => __( 'Remove Payment Plan', 'ncs-cart' ),
            'title-field'  => 'name',
            'type'         => 'repeater',
            'value'        => '',
            'class_size'   => '',
            'fields'       => $this->pay_plan_fields( $save ),
        )
        );
        $this->pricing = apply_filters( 'sc_pricing_fields', $this->pricing, $save );
        $this->confirmation = array(
            /*array(
                  'class'		=> 'widefat',
                  'id'		=> '_sc_confirmation_heading',
                  'type'		=> 'html',
                  'value'		=> '<div id="rid_sc_confirmation_heading" class="sc-field sc-row"><div class="input-group field-text"><div style="width: 100%;"">
                                      <b>'.__('Default Confirmation:','ncs-cart').'</b><br/>
                                      <hr style="margin: 15px 0 0px;border-top: 1px solid #d5d5d5;"/>
                                      </div></div></div>',
                  'class_size'=> '',
                  'conditional_logic' => '',
              ),*/
            array(
                'class'       => '',
                'description' => '',
                'id'          => '_sc_confirmation',
                'label'       => __( 'Confirmation Type', 'ncs-cart' ),
                'placeholder' => '',
                'type'        => 'select',
                'value'       => '',
                'selections'  => array(
                'message'  => __( 'Display Message', 'ncs-cart' ),
                'page'     => __( 'Display Page' ),
                'redirect' => __( 'Perform Redirect', 'ncs-cart' ),
            ),
                'class_size'  => '',
            ),
            array(
                'id'                => '_sc_confirmation_message',
                'label'             => __( 'Message', 'ncs-cart' ),
                'type'              => 'text',
                'value'             => __( 'Thank you. We\'ve received your order.', 'ncs-cart' ),
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => '_sc_confirmation',
                'value'   => 'message',
                'compare' => '=',
            ) ),
            ),
            array(
                'class'             => '',
                'description'       => '',
                'id'                => '_sc_confirmation_page',
                'label'             => __( 'Select Page', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'selections'        => $this->get_pages(),
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field' => '_sc_confirmation',
                'value' => 'page',
            ) ),
            ),
            array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => '_sc_redirect',
                'label'             => __( 'Thank You Page URL', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => '_sc_confirmation',
                'value'   => 'redirect',
                'compare' => '=',
            ) ),
            ),
        );
        $this->notifications = array( array(
            'class'        => 'repeater',
            'id'           => '_sc_notifications',
            'label-add'    => __( '+ Add New', 'ncs-cart' ),
            'label-edit'   => __( 'Edit Notification', 'ncs-cart' ),
            'label-header' => __( 'Notification', 'ncs-cart' ),
            'label-remove' => __( 'Remove Notification', 'ncs-cart' ),
            'title-field'  => 'name',
            'type'         => 'repeater',
            'value'        => '',
            'class_size'   => '',
            'fields'       => array(
            array(
            'text' => array(
            'class'       => 'widefat required repeater-title',
            'description' => '',
            'id'          => 'notification_name',
            'label'       => __( 'Name', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'select' => array(
            'class'       => '',
            'id'          => 'send_to',
            'label'       => __( 'Send To', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'select',
            'value'       => '',
            'class_size'  => '',
            'selections'  => array(
            'enter'     => __( 'Enter Email', 'ncs-cart' ),
            'purchaser' => __( 'Purchaser Email', 'ncs-cart' ),
            'admin'     => __( 'Admin Email', 'ncs-cart' ),
        ),
        ),
        ),
            array(
            'text' => array(
            'class'             => 'widefat required',
            'description'       => '',
            'id'                => 'send_to_email',
            'label'             => __( 'Send To Email', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'text',
            'value'             => '',
            'class_size'        => '',
            'conditional_logic' => array( array(
            'field'   => 'send_to',
            'value'   => 'enter',
            'compare' => '=',
        ) ),
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat required',
            'description' => '',
            'id'          => 'from_name',
            'label'       => __( 'From Name', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half first',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat required',
            'description' => '',
            'id'          => 'from_email',
            'label'       => __( 'From Email', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => 'reply_to',
            'label'       => __( 'Reply To', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half first',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat',
            'description' => '',
            'id'          => 'bcc',
            'label'       => __( 'Bcc', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat required',
            'description' => '',
            'id'          => 'subject',
            'label'       => __( 'Subject', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'textarea' => array(
            'class'       => 'widefat required',
            'description' => '',
            'id'          => 'message',
            'label'       => __( 'Message', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'textarea',
            'value'       => '',
            'class_size'  => '',
        ),
        )
        ),
        ) );
        $this->integrations = array( array(
            'type'  => 'html',
            'value' => '<a href="#" class="button sc-renew-lists" style="margin-left: 10px;">' . __( 'Renew mailing lists', 'ncs-cart' ) . '</a> <span class="renew-status"></span>',
        ), array(
            'class'        => 'repeater',
            'id'           => '_sc_integrations',
            'label-add'    => __( '+ Add New', 'ncs-cart' ),
            'label-edit'   => __( 'Edit Integration', 'ncs-cart' ),
            'label-header' => __( 'Integration', 'ncs-cart' ),
            'label-remove' => __( 'Remove Integration', 'ncs-cart' ),
            'title-field'  => 'name',
            'type'         => 'repeater',
            'value'        => '',
            'class_size'   => '',
            'fields'       => array(
            array(
                'select' => array(
                'class'       => 'select service_select required repeater-title',
                'id'          => 'services',
                'label'       => __( 'Service', 'ncs-cart' ),
                'placeholder' => '',
                'type'        => 'select',
                'value'       => '',
                'class_size'  => '',
                'selections'  => ( $save ? '' : $this->get_sc_service_type() ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'select2 multiple',
                'id'                => 'service_trigger',
                'label'             => __( 'Trigger', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => 'one-half first',
                'selections'        => ( $save ? '' : $this->get_sc_trigger_option() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => '',
                'compare' => '!=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'select2 multiple',
                'id'                => 'int_plan',
                'label'             => __( 'Payment Plan', 'ncs-cart' ),
                'placeholder'       => __( 'Any', 'ncs-cart' ),
                'type'              => 'select',
                'value'             => '',
                'class_size'        => 'one-half',
                'selections'        => ( $save ? '' : $this->get_payment_plans() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => '',
                'compare' => '!=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'webhook_method',
                'label'             => __( 'Method', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => [
                'get'  => 'GET',
                'post' => 'POST',
            ],
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'webhook',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'text' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'webhook_url',
                'label'             => __( 'Webhook URL', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'webhook',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'textarea' => array(
                'class'             => 'field_map',
                'id'                => 'field_map',
                'label'             => __( 'Field Map', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'textarea',
                'description'       => __( 'Put each field pair on a separate line in the following format: "field_key : studiocart_field_id"', 'ncs-cart' ),
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'webhook',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'service_action',
                'label'             => __( 'Action', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => array(
                'subscribed'   => __( 'Add contact', 'ncs-cart' ),
                'unsubscribed' => __( 'Remove contact', 'ncs-cart' ),
            ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => apply_filters( 'sc_integration_service_action_field_logic_options', array(
                'activecampaign',
                'convertkit',
                'mailchimp',
                'mailpoet',
                'sendfox'
            ) ),
                'compare' => 'IN',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'mail_chimp_list_name',
                'id'                => 'mail_list',
                'label'             => __( 'Mailchimp List', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_mailchimp_lists() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'mailchimp',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'mail_chimp_list_tags',
                'id'                => 'mail_tags',
                'label'             => __( 'Mailchimp Tags', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_mailchimp_tags() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'mailchimp',
                'compare' => '=',
            ), array(
                'field'   => 'service_action',
                'value'   => 'subscribed',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'mail_chimp_list_groups',
                'id'                => 'mail_groups',
                'label'             => __( 'Mailchimp Groups', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_mailchimp_groups() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'mailchimp',
                'compare' => '=',
            ), array(
                'field'   => 'service_action',
                'value'   => 'subscribed',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'text' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'mc_phone_tag',
                'label'             => __( 'Phone Merge Tag', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'mailchimp',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'convertkit_forms',
                'label'             => __( 'Convertkit Forms', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_convertkit_forms() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'convertkit',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'converkit_tags',
                'label'             => __( 'Convertkit Tags', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_converkit_tags() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'convertkit',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'activecampaign_lists',
                'label'             => __( 'ActiveCampaign List', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sc_activecampaign_lists() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'activecampaign',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'text' => array(
                'class'             => 'widefat',
                'description'       => 'Separate multiple classes with commas',
                'id'                => 'activecampaign_tags',
                'label'             => __( 'ActiveCampaign Tags', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'activecampaign',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'textarea' => array(
                'class'             => 'field_map',
                'id'                => 'activecampaign_field_map',
                'label'             => __( 'Field Map', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'textarea',
                'description'       => __( 'Put each field pair on a separate line in the following format: "%TAG% : studiocart_field_id"', 'ncs-cart' ),
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'activecampaign',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'sendfox_list_name',
                'id'                => 'sendfox_list',
                'label'             => __( 'SendFox List', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_sendfox_lists() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'sendfox',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'tutor_action',
                'label'             => __( 'Tutor Action', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : [
                'enroll' => __( 'Enroll in Course', 'ncs-cart' ),
                'cancel' => __( 'Cancel Enrollment', 'ncs-cart' ),
            ] ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'tutor',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'tutor_course',
                'label'             => __( 'Course', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->tutor_courses() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'tutor',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'wlm_action',
                'label'             => __( 'Member Actions', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : [
                'add'    => __( 'Add to Level', 'ncs-cart' ),
                'cancel' => __( 'Cancel from Level', 'ncs-cart' ),
                'remove' => __( 'Remove from Level', 'ncs-cart' ),
            ] ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'wishlist',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'wlm_level',
                'label'             => __( 'Membership Level', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_wlm_levels() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'wishlist',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'rcp_level',
                'label'             => __( 'Membership Level', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_rcp_levels() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'rcp',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'rcp_status',
                'label'             => __( 'Member Status', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : [
                'pending'  => 'Pending',
                'active'   => 'Active',
                'canceled' => 'Canceled',
                'expired'  => 'Expired',
            ] ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'rcp',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'description'       => '',
                'id'                => 'wlm_send_email',
                'label'             => __( 'Email notification', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'selections'        => array(
                ''      => __( 'Do not send', 'ncs-cart' ),
                'level' => __( 'Send level notification', 'ncs-cart' ),
                '1'     => __( 'Send global notification', 'ncs-cart' ),
            ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'wishlist',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'checkbox' => array(
                'class'             => '',
                'description'       => '',
                'id'                => 'wlm_pending',
                'label'             => __( 'Require admin approval', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'checkbox',
                'value'             => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'wishlist',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => 'mailpoet_list_name',
                'id'                => 'mailpoet_list',
                'label'             => __( 'MailPoet List', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_mailpoet_lists() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'mailpoet',
                'compare' => '=',
            ) ),
            ),
            ),
            /*array(
              'checkbox' =>array(
                  'class'		=> '',
                  'description'	=> '',
                  'id'			=> 'mp_confirmation_email',
                  'label'		=> __('Send confirmation email','ncs-cart'),
                  'placeholder'	=> '',
                  'type'		=> 'checkbox',
                  'value'		=> '',
                  'conditional_logic' => array (
                      array(
                          'field' => 'services',
                          'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                          'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                      )
                  ),
              )),
              array(
              'checkbox' =>array(
                  'class'		=> '',
                  'description'	=> '',
                  'id'			=> 'mp_schedule_welcome',
                  'label'		=> __('Send welcome email','ncs-cart'),
                  'placeholder'	=> '',
                  'type'		=> 'checkbox',
                  'value'		=> '',
                  'conditional_logic' => array (
                      array(
                          'field' => 'services',
                          'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                          'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                      ),
                  ),
              )),
              array(
              'checkbox' =>array(
                  'class'		=> '',
                  'description'	=> '',
                  'id'			=> 'mp_admin_email',
                  'label'		=> __('Disable admin notification','ncs-cart'),
                  'placeholder'	=> '',
                  'type'		=> 'checkbox',
                  'value'		=> '',
                  'conditional_logic' => array (
                      array(
                          'field' => 'services',
                          'value' => 'mailpoet', // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                          'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                      )
                  ),
              )),*/
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'membervault_action',
                'label'             => __( 'Action', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => array(
                'add_user'    => __( 'Add user', 'ncs-cart' ),
                'remove_user' => __( 'Remove user', 'ncs-cart' ),
            ),
                'conditional_logic' => array( array(
                'field' => 'services',
                'value' => 'membervault',
            ) ),
            ),
            ),
            array(
                'text' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'member_vault_course_id',
                'label'             => __( 'Membervault Course ID', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'membervault',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'text' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'kajabi_url',
                'label'             => __( 'Webhook URL', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'text',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'kajabi',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'checkbox' => array(
                'class'             => 'widefat',
                'description'       => '',
                'id'                => 'kajabi_email_confirmation',
                'label'             => __( 'Kajabi Confirmation Email', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'checkbox',
                'value'             => '',
                'class_size'        => '',
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => 'kajabi',
                'compare' => '=',
            ) ),
            ),
            ),
            array(
                'select' => array(
                'class'             => '',
                'id'                => 'user_role',
                'label'             => __( 'New User Role', 'ncs-cart' ),
                'placeholder'       => '',
                'type'              => 'select',
                'value'             => '',
                'class_size'        => '',
                'selections'        => ( $save ? '' : $this->get_user_roles() ),
                'conditional_logic' => array( array(
                'field'   => 'services',
                'value'   => apply_filters( 'sc_create_user_integrations', [ 'create user', 'tutor' ] ),
                'compare' => 'IN',
            ) ),
            ),
            ),
        ),
        ) );
        $this->integrations = apply_filters( 'sc_integration_fields', $this->integrations, $save );
        $this->tracking = array(
            /*array(
                  'class'		=> 'widefat',
                  'description'	=> '',
                  'id'			=> '_sc_tracking_view',
                  'label'		=> __('Orderform View','ncs-cart'),
                  'placeholder'	=> '',
                  'type'		=> 'textarea',
                  'value'		=> '',
              ),*/
            array(
                'class'       => 'widefat',
                'description' => __( 'No <script> tags, only used with 2-Step checkout forms.', 'ncs-cart' ),
                'id'          => '_sc_tracking_lead',
                'label'       => __( 'Lead Captured', 'ncs-cart' ),
                'placeholder' => __( 'Javascript code to be fired when a lead is captured.', 'ncs-cart' ),
                'type'        => 'textarea',
                'value'       => '',
            ),
            array(
                'class'       => 'widefat',
                'description' => '',
                'id'          => '_sc_tracking_main',
                'label'       => __( 'Main Product Purchased', 'ncs-cart' ),
                'placeholder' => __( 'Tracking codes for when this product is purchased as a main offer.', 'ncs-cart' ),
                'type'        => 'textarea',
                'value'       => '',
            ),
            array(
                'class'       => 'widefat',
                'description' => '',
                'id'          => '_sc_tracking_bump',
                'label'       => __( 'Order Bump Purchased', 'ncs-cart' ),
                'placeholder' => __( 'Additional tracking codes for when the order bump is purchased.', 'ncs-cart' ),
                'type'        => 'textarea',
                'value'       => '',
            ),
            array(
                'class'       => 'widefat',
                'description' => '',
                'id'          => '_sc_tracking_oto',
                'label'       => __( '1st Upsell Purchased', 'ncs-cart' ),
                'placeholder' => __( 'Tracking codes for when the first upsell is purchased.', 'ncs-cart' ),
                'type'        => 'textarea',
                'value'       => '',
            ),
            array(
                'class'       => 'widefat',
                'description' => '',
                'id'          => '_sc_tracking_oto_2',
                'label'       => __( '2nd Upsell Purchased', 'ncs-cart' ),
                'placeholder' => __( 'Tracking codes for when the second upsell is purchased.', 'ncs-cart' ),
                'type'        => 'textarea',
                'value'       => '',
            ),
        );
    }
    
    private function pay_plan_fields( $save = false )
    {
        $installmentsArr = array(
            '-1' => __( 'Never expires', 'ncs-cart' ),
        );
        for ( $i = 2 ;  $i <= 36 ;  $i++ ) {
            $installmentsArr[$i] = $i . __( ' payments', 'ncs-cart' );
        }
        $fields = array(
            array(
            'text' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'stripe_plan_id',
            'label'       => '',
            'placeholder' => '',
            'type'        => 'hidden',
            'value'       => '',
            'class_size'  => 'hide',
        ),
        ),
            array(
            'text' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'sale_stripe_plan_id',
            'label'       => '',
            'placeholder' => '',
            'type'        => 'hidden',
            'value'       => '',
            'class_size'  => 'hide',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat required',
            'description' => 'Must be unique. Letters and numbers only, no spaces.',
            'id'          => 'option_id',
            'label'       => __( 'Plan ID', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'select' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'product_type',
            'label'       => __( 'Product Type', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'select',
            'value'       => '',
            'selections'  => array(
            ''          => 'One-time Payment',
            'recurring' => 'Recurring Payments',
            'pwyw'      => 'Pay What You Want',
            'free'      => 'Free',
        ),
            'class_size'  => '',
        ),
        ),
            array(
            'checkbox' => array(
            'class'             => '',
            'description'       => __( "Don't show this as a selectable payment plan on checkout forms", 'ncs-cart' ),
            'id'                => 'is_hidden',
            'label'             => __( 'Hide Plan?', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'checkbox',
            'value'             => '',
            'class_size'        => '',
            'conditional_logic' => '',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat name repeater-title',
            'description' => __( 'A description of this payment plan for the order form.', 'ncs-cart' ),
            'id'          => 'option_name',
            'label'       => __( 'Option Label', 'ncs-cart' ),
            'placeholder' => __( 'e.g. One payment of $100', 'ncs-cart' ),
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half first',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'widefat',
            'description' => 'A description of this payment plan when on sale',
            'id'          => 'sale_option_name',
            'label'       => __( 'Sale Option Label', 'ncs-cart' ),
            'placeholder' => 'e.g. One payment of $50 (that\'s 50% off!)',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => 'one-half',
        ),
        ),
            array(
            'text' => array(
            'class'             => 'widefat required',
            'description'       => '',
            'id'                => 'price',
            'label'             => __( 'Price', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'price',
            'value'             => '',
            'class_size'        => 'one-half first',
            'step'              => 'any',
            'conditional_logic' => array( array(
            'field'   => 'product_type',
            'value'   => 'free',
            'compare' => '!=',
        ) ),
        ),
        ),
            array(
            'text' => array(
            'class'             => 'widefat',
            'description'       => '',
            'id'                => 'sale_price',
            'label'             => __( 'Sale Price', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'price',
            'value'             => '',
            'class_size'        => 'one-half',
            'step'              => 'any',
            'conditional_logic' => array( array(
            'field'   => 'product_type',
            'value'   => 'free',
            'compare' => '!=',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'frequency',
            'label'             => __( 'Frequency', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => array(
            '1'  => __( 'Every' ),
            '2'  => __( 'Every 2nd' ),
            '3'  => __( 'Every 3rd' ),
            '4'  => __( 'Every 4th' ),
            '5'  => __( 'Every 5th' ),
            '6'  => __( 'Every 6th' ),
            '7'  => __( 'Every 7th' ),
            '8'  => __( 'Every 8th' ),
            '9'  => __( 'Every 9th' ),
            '10' => __( 'Every 10th' ),
            '11' => __( 'Every 11th' ),
            '12' => __( 'Every 12th' ),
        ),
            'class_size'        => 'one-half first',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'sale_frequency',
            'label'             => __( 'Frequency (On Sale)', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => array(
            '1'  => __( 'Every' ),
            '2'  => __( 'Every 2nd' ),
            '3'  => __( 'Every 3rd' ),
            '4'  => __( 'Every 4th' ),
            '5'  => __( 'Every 5th' ),
            '6'  => __( 'Every 6th' ),
            '7'  => __( 'Every 7th' ),
            '8'  => __( 'Every 8th' ),
            '9'  => __( 'Every 9th' ),
            '10' => __( 'Every 10th' ),
            '11' => __( 'Every 11th' ),
            '12' => __( 'Every 12th' ),
        ),
            'class_size'        => 'one-half',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'interval',
            'label'             => __( 'Pay Interval', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => array(
            'day'   => __( 'Day' ),
            'week'  => __( 'Week' ),
            'month' => __( 'Month' ),
            'year'  => __( 'Year' ),
        ),
            'class_size'        => 'one-half first',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'sale_interval',
            'label'             => __( 'Pay Interval (On Sale)', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => array(
            'day'   => __( 'Day' ),
            'week'  => __( 'Week' ),
            'month' => __( 'Month' ),
            'year'  => __( 'Year' ),
        ),
            'class_size'        => 'one-half',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'installments',
            'label'             => __( 'Number of Payments', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => ( $save ? '' : $installmentsArr ),
            'class_size'        => 'one-half first',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'select' => array(
            'class'             => '',
            'description'       => '',
            'id'                => 'sale_installments',
            'label'             => __( 'Number of Payments (On Sale)', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'select',
            'value'             => '',
            'selections'        => ( $save ? '' : $installmentsArr ),
            'class_size'        => 'one-half',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'recurring',
        ) ),
        ),
        ),
            array(
            'text' => array(
            'class'             => 'widefat required',
            'description'       => 'This price will show as a suggested amount to pay',
            'id'                => 'suggested_price',
            'label'             => __( 'Suggested Price', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'price',
            'value'             => '',
            'class_size'        => 'one-half first',
            'step'              => 'any',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'pwyw',
        ), array(
            'field'   => 'product_type',
            'value'   => 'free',
            'compare' => '!=',
        ) ),
        ),
        ),
            array(
            'text' => array(
            'class'             => 'widefat',
            'description'       => '',
            'id'                => 'name_your_own_price_text',
            'label'             => __( 'Name Your Own Price Text', 'ncs-cart' ),
            'placeholder'       => 'Name Your Price',
            'type'              => 'text',
            'value'             => '',
            'class_size'        => 'one-half',
            'step'              => 'any',
            'conditional_logic' => array( array(
            'field' => 'product_type',
            'value' => 'pwyw',
        ), array(
            'field'   => 'product_type',
            'value'   => 'free',
            'compare' => '!=',
        ) ),
        ),
        )
        );
        return $fields;
    }
    
    private function multi_order_bump_fields( $save = false )
    {
        $fields = array(
            array(
            'checkbox' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'order_bump',
            'label'       => __( 'Enable Order Bump', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'checkbox',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'text' => array(
            'class'       => 'sc-color-field',
            'description' => '',
            'id'          => 'bump_bg_color',
            'label'       => __( 'Background Color', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'select' => array(
            'class'       => 'update-plan-product required',
            'description' => '',
            'id'          => 'ob_product',
            'label'       => __( 'Select Product', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'select',
            'value'       => '',
            'selections'  => $this->product_options(),
            'class_size'  => '',
        ),
        ),
            array(
            'text' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'ob_price',
            'label'       => __( 'Price', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'price',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'file-upload' => array(
            'class'        => 'widefat',
            'description'  => '',
            'id'           => 'ob_image',
            'label'        => __( 'Product Image', 'ncs-cart' ),
            'label-remove' => __( 'Remove Image', 'ncs-cart' ),
            'label-upload' => __( 'Set Image', 'ncs-cart' ),
            'placeholder'  => '',
            'type'         => 'file-upload',
            'field-type'   => 'url',
            'value'        => '',
            'class_size'   => '',
        ),
        ),
            array(
            'select' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'ob_image_pos',
            'label'       => __( 'Image Position', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'select',
            'value'       => '',
            'selections'  => array(
            ''    => __( 'Left', 'ncs-cart' ),
            'top' => __( 'Top', 'ncs-cart' ),
        ),
            'class_size'  => '',
        ),
        ),
            array(
            'text' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'ob_cb_label',
            'label'       => __( 'Checkbox Label', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'text' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'ob_headline',
            'label'       => __( 'Headline', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'text',
            'value'       => '',
            'class_size'  => '',
        ),
        ),
            array(
            'textarea' => array(
            'class'       => '',
            'description' => '',
            'id'          => 'ob_description',
            'label'       => __( 'Product Description', 'ncs-cart' ),
            'placeholder' => '',
            'type'        => 'textarea',
            'value'       => '',
            'class_size'  => '',
        ),
        )
        );
        return $fields;
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
    public function remove_repeater_blank( $value )
    {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $val ) {
                if ( empty($val) ) {
                    unset( $value[$key] );
                }
            }
        }
        return $value;
    }
    
    public function add_consent_field( $fields, $save )
    {
        $fields[1]['fields'][] = array(
            'checkbox' => array(
            'class'             => '',
            'description'       => __( 'Only run this integration for customers who opt-in (Opt-In Checkbox field must be turned on.)', 'ncs-cart' ),
            'id'                => 'require_optin',
            'label'             => __( 'Require consent', 'ncs-cart' ),
            'placeholder'       => '',
            'type'              => 'checkbox',
            'value'             => '',
            'conditional_logic' => array( array(
            'field'   => 'services',
            'value'   => sc_consent_services(),
            'compare' => 'IN',
        ) ),
        ),
        );
        return $fields;
    }
    
    private function get_pages( $noblank = false )
    {
        $pages = get_pages();
        $options = array(
            '' => __( 'Select Page', 'ncs-cart' ),
        );
        if ( $noblank ) {
            $options = array();
        }
        foreach ( $pages as $page ) {
            $options[$page->ID] = $page->post_title . ' (ID: ' . $page->ID . ')';
        }
        return $options;
    }
    
    private function get_user_roles()
    {
        
        if ( !function_exists( 'get_editable_roles' ) ) {
            echo  'nope' ;
            die;
            return;
        }
        
        $options = array();
        foreach ( get_editable_roles() as $role_name => $role_info ) {
            $options[$role_name] = $role_info['name'];
        }
        return $options;
    }
    
    private function get_sc_service_type()
    {
        $options = array(
            '' => '--' . __( 'Select Integration', 'ncs-cart' ) . '--',
        );
        $activecampaign_url = get_option( '_sc_activecampaign_url' );
        $activecampaign_secret_key = get_option( '_sc_activecampaign_secret_key' );
        //if activecampaign key exists
        if ( $activecampaign_url && $activecampaign_secret_key ) {
            $options['activecampaign'] = "ActiveCampaign";
        }
        //convertkit
        $converkit_apikey = get_option( '_sc_converkit_api' );
        $converkit_secretKey = get_option( '_sc_converkit_secret_key' );
        //if converkit key exists
        if ( $converkit_apikey && $converkit_secretKey ) {
            $options['convertkit'] = "ConvertKit";
        }
        // create user
        $options['create user'] = __( 'Create User', 'ncs-cart' );
        $kajabi_enable = get_option( '_sc_kajabi_enable' );
        //if kajabi enable
        if ( $kajabi_enable ) {
            $options['kajabi'] = "Kajabi";
        }
        //mailchimp
        $mailchimp_apikey = get_option( '_sc_mailchimp_api' );
        if ( $mailchimp_apikey ) {
            $options['mailchimp'] = "MailChimp";
        }
        if ( class_exists( 'MailPoet\\API\\API' ) ) {
            $options['mailpoet'] = "MailPoet";
        }
        $member_vault_api_key = get_option( '_sc_member_vault_api_key' );
        //if MemberVault token exists
        if ( $member_vault_api_key ) {
            $options['membervault'] = "MemberVault";
        }
        $sendfox_enable = get_option( '_sc_sendfox_api_key' );
        //if sendfox enable
        if ( $sendfox_enable ) {
            $options['sendfox'] = "SendFox";
        }
        return apply_filters( 'sc_integrations', $options );
    }
    
    private function get_sc_trigger_option()
    {
        $options = array(
            'purchased' => __( 'Product Purchased', 'ncs-cart' ),
            'refunded'  => __( 'Product Refunded', 'ncs-cart' ),
            'pending'   => __( 'COD Order Created', 'ncs-cart' ),
            'active'    => __( 'Subscription Active', 'ncs-cart' ),
            'completed' => __( 'Subscription Completed', 'ncs-cart' ),
            'canceled'  => __( 'Subscription Canceled', 'ncs-cart' ),
            'renewal'   => __( 'Subscription Charged', 'ncs-cart' ),
            'failed'    => __( 'Subscription Charge Failed', 'ncs-cart' ),
        );
        return $options;
    }
    
    private function get_plans( $key )
    {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $options = array();
        $post_id = intval( $_GET['post'] );
        if ( $product_id = get_post_meta( $post_id, $key, true ) ) {
            
            if ( is_array( $product_id ) ) {
                foreach ( $product_id as $prod_id ) {
                    if ( $key == '_sc_order_bump_options' ) {
                        $pid = $prod_id['ob_product'];
                    }
                    $options[$pid] = $this->get_plan_data( $pid );
                }
            } else {
                $options = $this->get_plan_data( $product_id );
            }
        
        }
        return $options;
    }
    
    private function get_plan_data( $product_id )
    {
        $product_plan_data = get_post_meta( $product_id, '_sc_pay_options', true );
        
        if ( !$product_plan_data ) {
            return array(
                "" => esc_html__( 'No plans found' ),
            );
        } else {
            $options = array();
            foreach ( $product_plan_data as $val ) {
                if ( $val['product_type'] != 'recurring' ) {
                    continue;
                }
                $options[$val['option_id']] = $val['option_name'];
            }
            
            if ( !empty($options) ) {
                return $options;
            } else {
                return array(
                    "" => esc_html__( 'No plans found' ),
                );
            }
        
        }
    
    }
    
    private function get_payment_plans( $plansOnly = false )
    {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $integrations = array(
            '' => __( 'Any', 'ncs-cart' ),
        );
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $id = intval( $_GET['post'] );
        $items = get_post_meta( $id, '_sc_pay_options', true );
        if ( is_array( $items ) ) {
            foreach ( $items as $item ) {
                
                if ( $item['option_id'] != null && $item['option_name'] != null ) {
                    if ( !$plansOnly || $item['product_type'] != 'free' ) {
                        $integrations[$item['option_id']] = $item['option_name'];
                    }
                    if ( !$plansOnly && $item['sale_option_name'] != null ) {
                        $integrations[$item['option_id'] . '_sale'] = $item['sale_option_name'] . ' ' . __( '(on sale)', 'ncs-cart' );
                    }
                }
            
            }
        }
        return $integrations;
    }
    
    private function product_options()
    {
        if ( !isset( $_GET['post'] ) && !isset( $_GET['post_type'] ) ) {
            return;
        }
        global  $studiocart ;
        remove_filter( 'the_title', array( $studiocart, 'public_product_name' ) );
        $options = array(
            '' => __( '-- Select Product --', 'ncs-cart' ),
        );
        $id = ( $_GET['post'] ? intval( $_GET['post'] ) : '' );
        // The Query
        $args = array(
            'post_type'      => array( 'sc_product' ),
            'post__not_in'   => array( $id ),
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );
        $the_query = new WP_Query( $args );
        // The Loop
        
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $options[get_the_ID()] = get_the_title() . ' (ID: ' . get_the_ID() . ')';
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        /* Restore original Post Data */
        wp_reset_postdata();
        add_filter( 'the_title', array( $studiocart, 'public_product_name' ) );
        return $options;
    }
    
    private function upsell_templates()
    {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        $options = array(
            '' => __( '-- Select Upsell Offer --', 'ncs-cart' ),
        );
        // The Query
        $args = array(
            'post_type' => array( 'sc_offer' ),
        );
        $the_query = new WP_Query( $args );
        // The Loop
        
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post();
                $options[get_the_ID()] = get_the_title() . ' (ID: ' . get_the_ID() . ')';
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        /* Restore original Post Data */
        wp_reset_postdata();
        return $options;
    }
    
    private function get_rcp_levels()
    {
        if ( !isset( $_GET['post'] ) ) {
            return;
        }
        
        if ( class_exists( 'RCP_Levels' ) ) {
            $options = array(
                '' => __( '-- Select Membership Level --', 'ncs-cart' ),
            );
            $levels_db = new RCP_Levels();
            $levels = $levels_db->get_levels( array(
                'status' => 'active',
            ) );
            
            if ( !empty($levels) ) {
                foreach ( $levels as $level ) {
                    $options[$level->id] = $level->name;
                }
                return $options;
            }
        
        }
        
        return [
            '' => __( '-- no options found --', 'ncs-cart' ),
        ];
    }
    
    private function get_sc_mailchimp_lists()
    {
        $options = array(
            '' => '',
        );
        $lists = get_option( 'sc_mailchimp_lists' );
        
        if ( !empty($lists) ) {
            $options = array_merge( $options, $lists );
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    private function get_sc_mailchimp_tags()
    {
        $options = array(
            '' => '',
        );
        $mctags = get_option( 'sc_mailchimp_tags' );
        
        if ( !empty($mctags) ) {
            $options = array(
                '' => '',
            );
            foreach ( $mctags as $list => $tags ) {
                $options = array_merge( $options, $tags );
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    private function get_sc_mailchimp_groups()
    {
        $options = array(
            '' => __( 'No Groups Found', 'ncs-cart' ),
        );
        $mcgroups = get_option( 'sc_mailchimp_groups' );
        
        if ( !empty($mcgroups) ) {
            $options = array(
                '' => '',
            );
            foreach ( $mcgroups as $list => $groups ) {
                $options = array_merge( $options, $groups );
            }
        }
        
        return $options;
    }
    
    //get_sc_convertkit_forms
    private function get_sc_convertkit_forms()
    {
        $options = array(
            '' => '-- select form --',
        );
        $tags = get_option( 'sc_convertkit_forms' );
        
        if ( !empty($tags) ) {
            // $options = array_merge($options, $tags);
            foreach ( $tags as $key => $value ) {
                $options[$key] = $value;
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    //get_sc_converkit_tags
    private function get_sc_converkit_tags()
    {
        $options = array(
            '' => '-- select tag --',
        );
        $tags = get_option( 'sc_converkit_tags' );
        
        if ( !empty($tags) ) {
            // $options = array_merge($options, $tags);
            foreach ( $tags as $key => $value ) {
                $options[$key] = $value;
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    //get_sc_activecampaign_lists
    private function get_sc_activecampaign_lists()
    {
        $lists = get_option( 'sc_activecampaign_lists' );
        
        if ( !empty($lists) ) {
            $options = $lists;
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    //get_sc_activecampaign_tags
    private function get_sc_activecampaign_tags()
    {
        $tags = get_option( 'sc_activecampaign_tags' );
        
        if ( !empty($tags) ) {
            $options = [
                "" => __( "Select", 'ncs-cart' ),
            ];
            $options += $tags;
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    private function get_wlm_levels()
    {
        if ( !function_exists( 'wlmapi_get_levels' ) ) {
            return;
        }
        $levels = wlmapi_get_levels();
        
        if ( !empty($levels['levels']['level']) ) {
            $options = array(
                '' => __( '-- Select --', 'ncs-cart' ),
            );
            foreach ( $levels['levels']['level'] as $level ) {
                $options[$level['id']] = $level['name'];
            }
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    private function tutor_courses()
    {
        $options = array(
            '' => __( '-- none found --', 'ncs-cart' ),
        );
        
        if ( class_exists( '\\TUTOR\\Utils' ) ) {
            $tutor = new \TUTOR\Utils();
            $courses = $tutor->get_courses();
            $options = array(
                '' => __( '-- select --', 'ncs-cart' ),
            );
            foreach ( $courses as $course ) {
                $options[$course->ID] = $course->post_title;
            }
        }
        
        return $options;
    }
    
    private function get_sendfox_lists()
    {
        $lists = get_option( 'sc_sendfox_lists' );
        
        if ( !empty($lists) ) {
            $options = [
                "" => __( "Select", 'ncs-cart' ),
            ];
            $options += $lists;
        } else {
            $options = array(
                '' => __( '-- none found --', 'ncs-cart' ),
            );
        }
        
        return $options;
    }
    
    private function get_mailpoet_lists()
    {
        $options = array(
            '' => __( '-- none found --', 'ncs-cart' ),
        );
        
        if ( class_exists( \MailPoet\API\API::class ) ) {
            $options = [
                "" => __( "Select", 'ncs-cart' ),
            ];
            // Get MailPoet API instance
            $mailpoet_api = \MailPoet\API\API::MP( 'v1' );
            // Get available list so that a subscriber can choose in which to subscribe
            $lists = $mailpoet_api->getLists();
            foreach ( $lists as $list ) {
                $options[$list['id']] = $list['name'];
            }
        }
        
        return $options;
    }
    
    private function bulk_upload_coupon( $sc_coupon_files, $post_id )
    {
        $old_coupon = get_post_meta( $post_id, '_sc_coupons', true );
        $csv = array_map( 'str_getcsv', file( $sc_coupon_files ) );
        array_walk( $csv, function ( &$a ) use( $csv ) {
            $a = array_combine( $csv[0], $a );
        } );
        array_shift( $csv );
        foreach ( $csv as $key => $row ) {
            $csv[$key]['exp_product'] = explode( ',', $row['exp_product'] );
            $csv[$key]['stripe_id'] = $post_id . '_' . $row['code'];
            $old_coupon[] = $csv[$key];
        }
        update_post_meta( $post_id, '_sc_coupons', $old_coupon );
    }

}
// class