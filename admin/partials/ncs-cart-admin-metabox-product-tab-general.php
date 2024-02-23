<?php

/**
 * Provide the view for a metabox
 *
 * @link 		https://studiocart.co
 * @since 		1.0.0
 *
 * @package 	Studiocart
 * @subpackage 	Studiocart/admin/partials
 */

wp_nonce_field( $this->plugin_name, 'sc-product_additional_info' );

$atts 					= array();
$atts['class'] 			= 'widefat';
$atts['description'] 	= '';
$atts['id'] 			= '_sc_show_coupon_field';
$atts['label'] 			= __('Show Coupon Field?', 'ncs-cart');
$atts['name'] 			= 'sc-show-coupon-field';
$atts['placeholder'] 	= '';
$atts['type'] 			= 'checkbox';
$atts['value'] 			= '';

if ( ! empty( $this->meta[$atts['id']][0] ) ) {
	$atts['value'] = 1;
}

apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );

?><div class="sc-field"><?php

include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-checkbox.php' );

?></div><?php


$atts 					= array();
$atts['class'] 			= 'widefat';
$atts['description'] 	= '';
$atts['id'] 			= '_sc_button_text';
$atts['label'] 			= __('Button Text', 'ncs-cart');
$atts['name'] 			= 'sc-button-text';
$atts['placeholder'] 	= '';
$atts['type'] 			= 'text';
$atts['value'] 			= '';

if ( ! empty( $this->meta[$atts['id']][0] ) ) {

	$atts['value'] = $this->meta[$atts['id']][0];

}

apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );

?><div class="sc-field sc-row"><?php

include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-text.php' );

?></div><?php


$atts 					= array();
$atts['class'] 			= 'sc-color-field';
$atts['description'] 	= '';
$atts['id'] 			= '_sc_button_color';
$atts['label'] 			= __('Primary Color', 'ncs-cart');
$atts['name'] 			= 'sc-button-color';
$atts['placeholder'] 	= '';
$atts['type'] 			= 'text';
$atts['value'] 			= '';

if ( ! empty( $this->meta[$atts['id']][0] ) ) {

	$atts['value'] = $this->meta[$atts['id']][0];

}

apply_filters( $this->plugin_name . '-field-' . $atts['id'], $atts );

?><div class="sc-field sc-row"><?php

include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-text.php' );

?></div>
