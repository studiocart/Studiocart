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

$default_atts = array(  'value'=>'',
                        'placeholder'=>'',
                        'class'=>'',
                        'id'=>'',
                     'note'=>	'');

$atts = wp_parse_args($atts,$default_atts);

$replace = (isset($atts['value']) && $atts['value'] != '...') ? $atts['value'] : '';
if($replace && strpos($atts['class'],'{val}') !== false) {
    $atts['class'] = str_replace('{val}', $replace, $atts['class']);
}

if ( ! empty( $atts['label'] ) ) {

    ?><label for="<?php echo esc_attr( $atts['id'] ); ?>"><?php esc_html_e( $atts['label'], 'ncs-cart' ); ?> 

    <?php if (! empty($atts['description'])):?>
        <i class="sc-tooltip" data-balloon-length="medium" aria-label="<?php esc_html_e( $atts['description'], 'ncs-cart' ); ?>" data-balloon-pos="up">?</i>
    <?php endif; ?>

    </label><?php

}
echo '<div style="flex-grow: 1;">';
if($atts['id'] == 'converkit_tags' || $atts['id'] == 'mail_groups' || $atts['id'] == 'mail_tags'){
    ?>
        <select
            aria-label="<?php esc_attr( _e( $atts['label'], 'ncs-cart' ) ); ?>"
            class="<?php echo esc_attr( $atts['class'] ); ?>"
            id="<?php echo esc_attr( $atts['id'] ); ?>"
            name="<?php echo esc_attr( $atts['name'] ); ?>[]">
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
    foreach ( $atts['selections'] as $value => $label ) {

        if(is_array($label)){

            foreach($label as $v => $l){
            ?>
                <optgroup label="<?php echo $l['type']?>">
                <option
            value="<?php echo esc_attr( $l['value'] ); ?>" <?php
            if (!is_countable($atts['value'])) {
                selected( $atts['value'], $l['value'] ); 

            } else if (in_array( $l['value'], $atts['value'] )) {
                echo 'selected="selected"';
            } else ?>><?php

            esc_html_e( $l['name'], 'ncs-cart' );

        ?></option>
                </optgroup>
                <?php
            }

        }else{

        ?><option
            value="<?php echo esc_attr( $value ); ?>" <?php
            if (!is_countable($atts['value'])) {
                selected( $atts['value'], $value ); 

            } else if (in_array( $value, $atts['value'] )) {
                echo 'selected="selected"';
            } else ?>><?php

            if(is_object($label)){
                esc_html_e( $label->name, 'ncs-cart' );
            }else{
            esc_html_e( $label, 'ncs-cart' );
            }

        ?></option><?php
    }

    } // foreach
}
?></select>
<?php if (! empty($atts['note'])):?>
	<p class="description"><?php echo wp_specialchars_decode( $atts['note'], 'ENT_QUOTES' ); ?></p>
<?php endif; ?></div>