<?php

/**
 * Provides the markup for a select field
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */


$replace = ($atts['value'] && $atts['value'] != '...') ? $atts['value'] : 0;
$atts['class'] = str_replace('{val}',$replace[0] , $atts['class']);

if ( ! empty( $atts['label'] ) ) {

    ?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'employees' ); ?> </label><?php

}
echo '<div style="flex-grow: 1;">';
if($atts['id'] == 'converkit_tags' || $atts['id'] == 'mail_groups' || $atts['id'] == 'mail_tags'){
    ?>
        <select
            aria-label="<?php esc_attr( _e( $atts['label'], 'ncs-cart' ) ); ?>"
            class="<?php echo esc_attr( $atts['class'] ); ?>"
            id="<?php echo esc_attr( $atts['id'] ); ?>"
            name="<?php echo esc_attr( $atts['id'] ); ?>[]">
<?php
} else if ($atts['id'] == 'type') { // needed to avoid warnings on post save
    ?>
        <select
            aria-label="<?php esc_attr( _e( $atts['label'], 'ncs-cart' ) ); ?>"
            class="<?php echo esc_attr( $atts['class'] ); ?>"
            id="<?php echo esc_attr( $atts['id'] ); ?>"
            name="coupon_<?php echo esc_attr( $atts['id'] ); ?>[]">
<?php
} else {
?>
        <select
        <?php if (strpos($atts['class'],'multiple') !== false) {
            $atts['name'] .= '[]';
            echo 'multiple="multiple"';
        } ?>
        aria-label="<?php esc_attr( _e( $atts['label'], 'ncs-cart' ) ); ?>"
        class="<?php echo esc_attr( $atts['class'] ); ?>"
        id="<?php echo esc_attr( $atts['id'] ); ?>"
        <?php if ($atts['placeholder']) { echo 'data-placeholder="'.esc_attr( $atts['placeholder'] ).'"'; } ?>
        name="<?php echo esc_attr( $atts['name'] ); ?>">
    <?php
}
    
if ( ! empty( $atts['blank'] ) ) {

    ?><option value><?php esc_html_e( $atts['blank'], 'ncs-cart' ); ?></option><?php

}
if (is_array($atts['selections'])) {
    foreach ( $atts['selections'] as $value=>$label ) {

        ?><option
            value="<?php echo esc_attr( $value ); ?>" <?php
            if (is_array($atts['value']) && in_array( $value, $atts['value'] )) {
                echo 'selected="selected"';
            }
            selected( $atts['value'], $value ); ?>><?php

            esc_html_e( $label, 'ncs-cart' );

        ?></option><?php

    } // foreach
}
?></select>
<?php if (isset($atts['description'])):?>
<p class="description"><?php echo wp_specialchars_decode( $atts['description'], 'ENT_QUOTES' ); ?></p>
            <?php endif; ?></div>