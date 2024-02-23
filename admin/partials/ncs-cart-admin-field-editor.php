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

$default_atts = array(  'value'		=>	'',
						'show_tags' =>	false);

$atts = wp_parse_args($atts,$default_atts);

$atts['settings'] = $atts['settings'] ?? array();
$atts['settings']['textarea_name'] = $atts['name'];

if ( ! empty( $atts['label'] ) ) {

	?><label for="<?php

	echo esc_attr( $atts['id'] );

	?>"><?php

		esc_html_e( $atts['label'], 'ncs-cart' );

	?>: 
    
    <?php if (! empty($atts['description'])): ?>
    <span class="description"><?php echo wp_specialchars_decode( $atts['description'], 'ENT_QUOTES' ); ?></span>
    <?php endif; ?>
    
    </label><?php

}
wp_editor( html_entity_decode( $atts['value'] ), 'sc-'.$atts['id'], $atts['settings'] );

?>
    
<?php if($atts['show_tags']) { 
    sc_merge_tag_select(); 
} ?>