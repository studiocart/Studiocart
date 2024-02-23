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

$default_atts = array(  'value'=>'',
                        'class'=>'',
                        'note'=>'',
                        'description'=>'',
                        'id'=>'');

$atts = wp_parse_args($atts,$default_atts);

$id = (isset($atts['rid'])) ? $atts['rid'] : $atts['id'];
?>
<span class="sc-label"><?php echo esc_attr( $atts['label'] ); ?></span>
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
<i class="sc-tooltip" data-balloon-length="medium" aria-label="<?php esc_html_e( $atts['description'], 'ncs-cart' ); ?>" data-balloon-pos="up">?</i>
<?php endif; ?>
<?php if($atts['note']): ?>
	<p class="description"><?php echo wp_specialchars_decode( $atts['note'], 'ENT_QUOTES' ); ?></p>
<?php endif; ?>
</div>
