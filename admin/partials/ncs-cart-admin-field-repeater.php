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

$repeater_id = "repeater".$setatts['id'];
$didscripts = false;

?><ul id="<?php echo $repeater_id; ?>" class="repeaters"><?php

	for ( $i = 0; $i <= $count; $i++ ) {

		$k = $i;
        if ( $i === $count ) {

			$setatts['class'] .= ' hidden';
            $k = null;

		}

		if ( ! empty( $repeater[$i][$setatts['title-field']] ) ) {

			$setatts['label-header'] = $repeater[$i][$setatts['title-field']];

		}

		?><li class="<?php echo esc_attr( $setatts['class'] ); ?>">
			<div class="handle">
				<span class="title-repeater" data-label="<?php echo esc_html( $setatts['label-header'], 'ncs-cart' ); ?>"><?php echo esc_html( $setatts['label-header'], 'ncs-cart' ); ?></span>
				<button aria-expanded="true" class="btn-edit" type="button">
					<span class="screen-reader-text"><?php echo esc_html( $setatts['label-edit'], 'ncs-cart' ); ?></span>
					<span class="toggle-arrow"></span>
				</button>
			</div><!-- .handle -->
			<div class="repeater-content">
				<div class="wrap-fields"> <?php
        
                    $defaults['class_size'] 	= '';
                    $defaults['description'] 	= '';
                    $defaults['label'] 			= '';
        

					foreach ( $setatts['fields'] as $fieldcount => $field ) {

						foreach ( $field as $type => $atts ) {                            

                            if ($atts['id'] ==  'ob_plan' && !empty($prod_id)) {
                                $atts['selections'] = $atts['selections'][$prod_id];                            
                            }

                            $atts = wp_parse_args( $atts, $defaults );

							if ( ! empty( $repeater ) && isset( $repeater[$i][$atts['id']] ) ) {

								$atts['value'] = $repeater[$i][$atts['id']];
                                if ($atts['id'] ==  'ob_product') {
                                    $prod_id = $atts['value'];
                                }

							}
                            
                            if(isset($setting_field) && $setting_field){

                                if ( ! empty( $repeater ) && ! empty( $repeater[$i][$atts['key']] ) ) {

                                    $atts['value'] = $repeater[$i][$atts['key']];
    
                                }
                            }

							$atts['name'] 	  = $atts['id'].'['.$k.']';

							?><div class="rid<?php echo esc_attr( $atts['id'] ); ?> wrap-field <?php echo esc_attr( $atts['class_size'] ); ?>"><?php
                                                        
                            if($type=='checkbox') {
                                //var_dump($atts['id'], $repeater[$i]);
                                $atts['rid'] = $atts['name'];
                            }
                            
							include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-' . $type . '.php' );

							?></div><?php
             
                            // conditional logic
                            if ( !empty( $atts['conditional_logic'] ) && !$didscripts ) {
                            
                                $conditions = array();
                                $row_id = '$(this).closest(".repeater-content").find(".rid'.$atts['id'].'")';
                                
                                foreach ($atts['conditional_logic'] as $l) {
                                    $fieldname = '[name^=\"'.$l['field'].'[\"]';
                                    if ($l['compare'] == 'IN' || $l['compare'] == 'NOT IN') {
                                        $scripts .= 'var arr_'.$atts['id'].' = '.json_encode($l['value']).';
                                        ';
                                        if ($l['compare'] == 'IN') {
                                            $conditions[] = sprintf("(arr_%s.includes($(this).val()))", $atts['id'], $l['value']);
                                        } else {
                                            $conditions[] = sprintf("(!arr_%s.includes($(this).val()))", $atts['id'], $l['value']);
                                        }
                                    } else {
                                        if ( !isset($l['compare']) || $l['compare'] == '=' ) {
                                            $l['compare'] = '==';
                                        }
                                        $eval = '$(this).closest(".repeater-content").find("'.$fieldname.'").val()';
                                        $conditions[] = sprintf("(%s %s '%s')", $eval, $l['compare'], $l['value']);                             
                                    }
                                } 

                                if(!empty($conditions)) {
                                    $conditions = implode(' && ', $conditions);
                                    $eval = sprintf('if ( %s ) {
                                        %s.css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400) 
                                    } else { 
                                        %s.hide() 
                                    }', $conditions, $row_id, $row_id);
                                    
                                    foreach ($atts['conditional_logic'] as $l) {
                                        $fieldname = '[name^=\"'.$l['field'].'[\"]';
                                        $l['field'] = '#'.$repeater_id.' '.$fieldname;
                                        $eval .= '$("'.$l['field'].'").change(function(){
                                            '.$eval.'
                                        });';
                                        $scripts .= '$("'.$l['field'].'").each(function(index){
                                            '.$eval.'
                                        });
                                        ';
                                    }
                                }
                            }
						}
					} // $fieldset foreach

				?></div>
				<div>
					<a class="link-remove" href="#">
						<span><?php

							echo esc_html( apply_filters( $this->plugin_name . '-repeater-remove-link-label', $setatts['label-remove'] ), 'ncs-cart' );

						?></span>
					</a>
				</div>
			</div>
		</li><!-- .repeater --><?php
        
        $didscripts = true;

	} // for

	?><div class="repeater-more">
		<span class="status"></span>
		<a class="button add-repeater" href="#" ><?php

			echo esc_html( apply_filters( $this->plugin_name . '-repeater-more-link-label', $setatts['label-add'] ), 'ncs-cart' );

		?></a>
	</div><!-- .repeater-more -->
</ul><!-- repeater -->
