<?php

/**
 * Provides the markup for any textarea field
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */
$default_atts = array(  'type'		=>	'text',
                        'value'		=>	'',
                        'class'		=>	'',
						'id'		=>	'',
                        'placeholder'=>	'',
						'cols'		=>	'10',
						'rows'		=>	'5',
						'description'=>	'',
                        'note'=>	'');

$atts = wp_parse_args($atts,$default_atts);
if ( ! empty( $atts['label'] ) ) {

	?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'ncs-cart' ); ?> 
    <?php
    if ( ! empty( $atts['description'] ) ) {

        ?><i class="sc-tooltip" data-balloon-length="medium" aria-label="<?php esc_html_e( $atts['description'], 'ncs-cart' ); ?>" data-balloon-pos="up">?</i>
    <?php

    } ?>
    </label><?php

}

?><div class="input-group field-<?php echo esc_attr( $atts['type'] ); ?>"><div class="textarea-wrap"><textarea
	class="<?php echo esc_attr( $atts['class'] ); ?>"
	cols="<?php echo esc_attr( $atts['cols'] ); ?>"
	id="<?php echo esc_attr( $atts['id'] ); ?>"
	name="<?php echo esc_attr( $atts['name'] ); ?>"
	placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
	rows="<?php echo esc_attr( $atts['rows'] ); ?>"><?php

	echo esc_html( $atts['value'] );

?></textarea>

<?php if (! empty($atts['note'])):?>
	<p class="description"><?php echo wp_specialchars_decode( $atts['note'], 'ENT_QUOTES' ); ?></p>
<?php endif; ?>

</div></div>