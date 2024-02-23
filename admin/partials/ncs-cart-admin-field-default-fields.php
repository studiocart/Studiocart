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

if($setatts['id']=='_sc_default_fields'){
    $group = 'default';
    $default_fields = array(
        'first_name' => array('name'=>'first_name','label'=>esc_html__('First Name', 'ncs-cart'),'required'=> true,'cols'=>6),
        'last_name' => array('name'=>'last_name','label'=>esc_html__('Last Name', 'ncs-cart'),'required'=> true,'cols'=>6),
        'email' => array('name'=>'email','label'=>esc_html__('Email', 'ncs-cart'),'type'=>'email','required'=> true,'cols'=>6),
        'phone' => array('name'=>'phone','label'=>esc_html__('Phone Number', 'ncs-cart'),'cols'=>6),
        'company' => array('name'=>'company','label'=>esc_html__('Company Name', 'ncs-cart'),'required'=> false,'cols'=>6),
    );
} else if($setatts['id']=='_sc_address_fields'){
    $group = 'address';
    $default_fields = array(
        'country' => array('name'=>'country','label'=>esc_html__('Country', 'ncs-cart'),'required'=> true,'cols'=>12),
        'address1' => array('name'=>'address1','label'=>esc_html__('Address', 'ncs-cart'),'required'=> true,'cols'=>12),
        'address2' => array('name'=>'address2','label'=>esc_html__('Address Line 2', 'ncs-cart'),'cols'=>12),
        'city' => array('name'=>'city','label'=>esc_html__('Town / City', 'ncs-cart'),'required'=> true,'cols'=>12),
        'state' => array('name'=>'state','label'=>esc_html__('State / County', 'ncs-cart'),'required'=> true,'cols'=>6),
        'zip' => array('name'=>'zip','label'=>esc_html__('Postcode / Zip', 'ncs-cart'),'required'=> true,'cols'=>6)
    );
}

$repeater_id = "repeater".$setatts['id'];

$setatts['id'] = '_sc_default_fields';
$setatts['label-edit'] = __('Edit Field','ncs-cart');


?><ul id="<?php echo $repeater_id; ?>" class="repeaters"><?php

	//for ( $i = 0; $i <= $count; $i++ ) {
    $i = 0;
    $use_defaults = false;

    $saved_data = $repeater ?? array();
        
    if(!$repeater) {
        $use_defaults = true;
        $repeater = $default_fields;
    } else {
        // add default field info if missing from saved data
        foreach($default_fields as $key=>$value) {
            if(!isset($repeater[$key])) {
                $repeater[$key] = $value;
            }
        }
    }
    
    foreach ( $repeater as $key => $saved ) {
        
        if(!isset($default_fields[$key])) {
            continue;
        }
        
        $field = $default_fields[$key];
                
        if ( ($use_defaults && !isset($field['required'])) || (!$use_defaults && !isset($saved['default_field_required'])) ) {
            $field['required'] = '';
        }

		$k = $field['name'];
        $key = str_replace('_','',$key);

		$setatts['label-header'] = $field['label'];
        $setatts['class'] = 'repeater';
        
        // check $saved_data to see if key exists. If not, it's a new default field and should be hidden by default
        $hidden = ($saved_data && !isset($saved_data[$k])) ? true : isset($hide_fields[$key]);
        
        if ($hidden) {
            $setatts['class'] .= ' disabled';
        }
        
        $setatts['fields'] = array(
            array(
            'checkbox' =>array(
                'class'		=> 'default_field_disabled',
                'description'	=> '',
                'id'			=> 'default_field_disabled',
                'label'		=> __('Disabled','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> $hidden,
                'class_size'    => 'one-half first',
            )),
            array(
            'checkbox' =>array(
                'class'		=> '',
                'description'	=> '',
                'id'			=> 'default_field_required',
                'label'		=> __('Required Field','ncs-cart'),
                'placeholder'	=> '',
                'type'		=> 'checkbox',
                'value'		=> $field['required'] ?? '',
                'class_size'    => 'one-half',
            )),
            array(
            'text' =>array(
                'class'		    => 'widefat repeater-title required',
                'description'	=> '',
                'id'			=> 'default_field_label',
                'label'		    => __('Label','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'text',
                'value'		    => $field['label'],
                'class_size'    => 'one-half first',
            )),
            array(
            'select' =>array(
                'class'		    => '',
                'description'	=> '',
                'id'			=> 'default_field_size',
                'label'	    	=> __('Size','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => $field['cols'],
                'selections'    =>  array(
                    '12' 	    => __( 'Large','ncs-cart'),
                    '6' 	    => __( 'Medium','ncs-cart'),
                ),
                'class_size'    => 'one-half',
            )),

        );

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
                            
                            $atts = wp_parse_args( $atts, $defaults );

							if ( ! empty( $repeater ) && isset( $repeater[$k][$atts['id']] ) ) {
								$atts['value'] = $repeater[$k][$atts['id']];
							}
                            
                            if(isset($setting_field) && $setting_field){

                                if ( ! empty( $repeater ) && ! empty( $repeater[$k][$atts['key']] ) ) {

                                    $atts['value'] = $repeater[$k][$atts['key']];
    
                                }
                            }

							$atts['name'] = $setatts['id'].'['.$k.']['.$atts['id'].']';

							?><div class="rid<?php echo esc_attr( $atts['id'] ); ?> wrap-field <?php echo esc_attr( $atts['class_size'] ); ?>"><?php
                                                        
                            if($type=='checkbox') {
                                //var_dump($atts['id'], $repeater[$i]);
                                $atts['rid'] = $atts['name'];
                            }
                            
							include( plugin_dir_path( __FILE__ ) . $this->plugin_name . '-admin-field-' . $type . '.php' );

							?></div><?php
						}
					} // $fieldset foreach

				?></div>
			</div>
		</li><!-- .repeater --><?php
        
        $i++;

	} // foreach field type

	?>
</ul><!-- repeater -->
