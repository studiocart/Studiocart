<?php

 /**
 * Add REST API support to 'sc_product'.
 */
 
add_action( 'admin_enqueue_scripts', 'pm_enqueue_script_sc' );
function pm_enqueue_script_sc() {
	wp_enqueue_style( "sc-select2_css", 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css', array(), '', 'all' );
	wp_enqueue_script( 'sc-select2_js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js', array( 'jquery'), '', true );
}
	
add_filter( 'register_post_type_args', 'pm_post_type_args', 10, 2 ); 
function pm_post_type_args( $args, $post_type ) {
    if ( 'sc_product' === $post_type ) {
        $args['show_in_rest'] = true; 
        // Optionally customize the rest_base or rest_controller_class
        $args['rest_base']    = 'sc_product';
        $args['rest_controller_class'] = 'WP_REST_Posts_Controller';
    }
 
    return $args;
}
 
 //add shortcode
function sc_product_shortcode_handler($atts){
	$atts = shortcode_atts([
		'id' => 0,
		'pid' => 0,
		'hide_labels' => 'yes',
		'template' => 'false',
		'coupon' => false,
	], $atts, 'sc_gb_product_shortcode');
	return sc_gb_product_shortcode_fn($atts[ 'pid' ], $atts[ 'hide_labels' ], $atts[ 'template' ],  $atts[ 'coupon' ]);
}

/**
 * Register Shortcode
 */
add_shortcode('sc_gb_product_shortcode', 'sc_product_shortcode_handler');

/** 
 * @param $atts *
 * @return string
 */
function sc_gb_product_shortcode_cb($atts){	
	return sc_gb_product_shortcode_fn($atts[ 'pid' ], $atts[ 'hide_labels' ],  $atts[ 'template' ],  $atts[ 'coupon' ] );
}

/**
 * @param int $post_id The post ID
 * @param string $hide_labels Allows : yes,no only
 * @param string $template Allows : true,false only *
 * @return string
 */
function sc_gb_product_shortcode_fn($post_id, $hide_labels, $template , $coupon)
{
	$coupon = $coupon ? $coupon : false;
	$template = $template == "true" ? "2-step" : $template;
	$title = get_the_title(absint($post_id));
    $hide_labels = ($hide_labels) ? true : false;

	//in admin section
	if( isset($_GET['context']) && $_GET['context'] == "edit" ){
		$html = "<div class='sc_order_form_section'>";
		$html .= "<div class='sc_order_form_heading'>".sprintf(__('%s Order Form','ncs-cart'), apply_filters('studiocart_plugin_title','Studiocart'))." : {$title}</div>";
		$html .= "</div>";
		return $html;
	}	
    if( $template ){
        return "[studiocart-form id={$post_id} hide_labels='{$hide_labels}' template='{$template}' coupon='{$coupon}']";				
    }else{			
        return "[studiocart-form id={$post_id} hide_labels='{$hide_labels}' coupon='{$coupon}']";		
    }
}

/**
 * Register block
 */
add_action('init', function () {
	// Skip block registration if Gutenberg is not enabled/merged.
	if (!function_exists('register_block_type')) {
		return;
	}
	$dir = dirname(__FILE__);
	$index_js = 'index.js';		
	$index_css = 'style.css';		
	wp_register_script(
		'sc-product-shortcode-script-gb',
		plugins_url($index_js, __FILE__),
		array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-editor',			
			'wp-components',
            'jquery'

		),
		filemtime("$dir/$index_js"),
        true
	);
	
	wp_localize_script( 'sc-product-shortcode-script-gb', 'sc_product_shortcode_script_gb',
        array( 
            'may_white_label_title'=>apply_filters('studiocart_plugin_title','Studiocart')
        )
    );

	wp_register_style(
        'sc-procudt-shortcode-script-gb_css',
        plugins_url( 'style.css', __FILE__ ),
        array( ),
        filemtime( "$dir/$index_css" )
    );
	
	//register block type	
	register_block_type('sc-products-shortcode/product-shortcode', array(
		'editor_script' => 'sc-product-shortcode-script-gb',
		'style' => 'sc-procudt-shortcode-script-gb_css',
		'render_callback' => 'sc_gb_product_shortcode_cb',
		'attributes' => [
			'id' => [
				'default' => 0
			],
			'pid' => [
				'default' => 0
			],
			'hide_labels' => [
				'default' => true
			],
			'template' => [
				'default' => false
			],
			'coupon' => [
				'default' => ''
			]
		]
	));
});
