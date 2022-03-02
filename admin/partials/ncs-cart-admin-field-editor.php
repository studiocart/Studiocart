<?php

/**
 * Provides the markup for any WP Editor field
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */

// wp_editor( $content, $editor_id, $settings = array() );

if ( ! empty( $atts['label'] ) ) {

	?><label for="<?php

	echo esc_attr( $atts['id'] );

	?>"><?php

		esc_html_e( $atts['label'], 'ncs-cart' );

	?>: </label><?php

}
//print_r($atts); 
wp_editor( html_entity_decode( $atts['value'] ), 'sc-'.$atts['id'], array('textarea_name' => 'sc-'.$atts['name']) );

?><span class="description"><?php esc_html_e( $atts['description'], 'ncs-cart' ); ?></span>