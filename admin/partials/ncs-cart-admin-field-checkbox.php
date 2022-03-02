<?php

/**
 * Provides the markup for any checkbox field
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */
$id = (isset($atts['rid'])) ? $atts['rid'] : $atts['id'];
?>
<span class="label"><?php echo esc_attr( $atts['label'] ); ?></span>
<div class="checkbox-wrap">
<span class="ckbx-style">
    <input aria-role="checkbox"
		<?php checked( 1, $atts['value'], true ); ?>
		class="<?php echo esc_attr( $atts['class'] ); ?>"
		id="<?php echo esc_attr( $id ); ?>"
		name="<?php echo esc_attr( $atts['name'] ); ?>"
		type="checkbox"
		value="1" /> 
    
    <label for="<?php echo esc_attr( $id ); ?>"></label>	
</span>
<?php if($atts['description']): ?>
<p class="description" style="margin: 0"><?php esc_html_e( $atts['description'], 'ncs-cart' ); ?></p>
<?php endif; ?>
</div>
