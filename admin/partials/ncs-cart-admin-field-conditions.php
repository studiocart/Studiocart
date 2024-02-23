<?php

/**
 * Provides the markup for a repeater field
 *
 * Must include an multi-dimensional array with each field in it. The
 * field type should be the key for the field's attribute array.
 *
 * $fields['file-type']['all-the-field-attributes'] = 'Data for the attribute';
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Studiocart
 * @subpackage Studiocart/admin/partials
 */

//echo '<pre>'; print_r( $repeater ); echo '</pre>';

$oldatts = $setatts;
$oldrepeater = $repeater;
$oldrepeater_id = $repeater_id;

$attsname = $atts['name'];

$setatts = $atts;
$count2 		= 0;
$repeater 	= $atts['value'] ?? '';

/*if ( ! empty( $this->meta[$setatts['id']] ) ) {

    $repeater = maybe_unserialize( $this->meta[$setatts['id']][0] );

}*/

if ( ! empty( $repeater ) ) {

    $count2 = count( $repeater );

} else {
    $count2 = 1;
}

$repeater_id = "repeater".$setatts['id'];

$setatts['label-add'] = 'Add';
$setatts['label-remove'] = 'Remove';
$setatts['label-header'] = '';
$setatts['label-field'] = '';
$setatts['class'] = 'condition';
    
?><ul id="<?php echo $repeater_id; ?>" class="conditions"><?php

	for ( $i2 = 0; $i2 <= $count2; $i2++ ) {

		$k2 = $i2;
        if ( $i2 === $count2 ) {

			$setatts['class'] .= ' hidden';
            $k2 = 'hidden';

		}

		?><li class="<?php echo esc_attr( $setatts['class'] ); ?>">
			<div class="condition-content">
				<div class="wrap-fields condition-type-and"> 
                    <div id="condition-type-and" class="condition-type"><?php _e('And', 'ncs-cart') ; ?></div>
                    <div id="condition-type-or" class="condition-type"><?php _e('Or', 'ncs-cart') ; ?></div>
                    <?php
        
                    $defaults['class_size'] 	= '';
                    $defaults['description'] 	= '';
                    $defaults['label'] 			= '';
        

					foreach ( $setatts['fields'] as $fieldcount => $field ) {

						foreach ( $field as $type => $atts ) {                            

                            if ($atts['id'] ==  'ob_plan' && !empty($prod_id)) {
                                $atts['selections'] = $atts['selections'][$prod_id];                            
                            }

                            $atts = wp_parse_args( $atts, $defaults );

							if ( ! empty( $repeater ) && isset( $repeater[$i2][$atts['id']] ) ) {

								$atts['value'] = $repeater[$i2][$atts['id']];

							}

							$atts['name']  = $attsname.'['.$atts['id'].']'.'['.$k2.']';
                            $atts['class'] .= 'cinput-'.$atts['id'];

							?><div class="rid<?php echo esc_attr( $atts['id'] ); ?> wrap-field <?php echo esc_attr( $atts['class_size'] ); ?>"><?php
                                                        
                            if($type=='checkbox') {
                                //var_dump($atts['id'], $repeater[$i]);
                                $atts['rid'] = $atts['name'];
                            }
                            
							include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-' . $type . '.php' );

							?></div><?php
                            
						}
					} // $fieldset foreach

				    ?><a class="remove-condition" href="#">&times;</a>
                </div>
			</div>
		</li><!-- .condition --><?php
        
	} // for

	?><div class="condition-more">
		<span class="status"></span>
		<a class="button add-condition" href="#" ><?php _e('+ Add another condition', 'ncs-cart') ; ?></a>
	</div><!-- .repeater-more -->
</ul><!-- repeater -->

<?php
$setatts = $oldatts;
$repeater = $oldrepeater;
$repeater_id = $oldrepeater_id;

