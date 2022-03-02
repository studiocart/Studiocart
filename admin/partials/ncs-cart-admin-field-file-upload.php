<?php

/**
 * Provides the markup for an upload field
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */

if ( ! empty( $atts['label'] ) ) {

	?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'ncs-cart' ); ?> </label><?php

}

?><div class="input-group field-upload">
<input
	class="<?php echo esc_attr( $atts['class'] ); ?>"
	data-id="url-file"
	id="<?php echo esc_attr( $atts['id'] ); ?>"
	name="<?php echo esc_attr( $atts['name'] ); ?>"
	type="<?php echo esc_attr( $atts['field-type'] ); ?>"
	value="<?php echo esc_attr( $atts['value'] ); ?>" />
<a href="#" class="button upload-file <?php echo ($atts['value']) ? 'hide' : ''; ?>"><?php esc_html_e( $atts['label-upload'], 'ncs-cart' ); ?></a>
<a href="#" class="button remove-file <?php echo (!$atts['value']) ? 'hide' : ''; ?>"><?php esc_html_e( $atts['label-remove'], 'ncs-cart' ); ?></a>
</div>