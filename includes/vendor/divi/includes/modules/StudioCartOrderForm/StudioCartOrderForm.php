<?php
class StudioCartOrderForm extends ET_Builder_Module {
	public $slug       = 'stof_studiocart_order_form';
	public $vb_support = 'on';

	protected $module_credits = array(
		'module_uri' => '',
		'author'     => 'Studiocart',
		'author_uri' => '',
	);

	public function init() {
		$this->name = apply_filters('studiocart_plugin_title','Studiocart') . esc_html__( ' Order Form', 'ncs-cart' );
		//$this->icon_path        =  plugin_dir_url( __FILE__ ) . 'icon.png';
		$this->main_css_element = '%%order_class%% .scshortcode';
		// Toggle settings
		$this->settings_modal_toggles  = array(
			'general'  => array(
				'toggles' => array(
					'main_content' => esc_html__( 'Setting', 'ncs-cart' )
				),
			),			
		);
		
		$this->advanced_fields = array(			
			'fonts' => array(
				'title'   => array(
					'label'        => __('Title','ncs-cart'),
					'css'          => array(
						'main' => "{$this->main_css_element} h3.title",
					),
					'header_level' => array(
						'default' => 'h3',
					),
				)				
			),		
			'button' => array(
				'button' => array(
					'label'          => __('Button','ncs-cart'),
					'css'            => array(
						'main'         => "{$this->main_css_element} .form-group .btn.btn-block",
						'limited_main' => "{$this->main_css_element} .form-group .btn.btn-block",
					),
					'no_rel_attr'    => true,
					'box_shadow'     => array(
						'css' => array(
							'main' => '%%order_class%% .form-group .btn.btn-block',
						),
					),
					'margin_padding' => array(
						'css' => array(
							'important' => 'all',
						),
					),
				),
			),		
			'borders'=> array(
				'default' => array(
					'css'          => array(
						'main'      => array(
							'border_radii'  => sprintf( '%1$s input.form-control', $this->main_css_element ),
							'border_styles' => sprintf( '%1$s input.form-control', $this->main_css_element ),
						),
						'important' => 'plugin_only',
					),
					'label_prefix' => esc_html__( 'Inputs', 'et_builder' ),
				),
			),			
			'text' => array(
				'css' => array(
					'text_orientation' => '%%order_class%% input, %%order_class%% textarea, %%order_class%% label',
					'text_shadow'      => '%%order_class%%, %%order_class%% input, %%order_class%% textarea, %%order_class%% label, %%order_class%% select',
				),
			),
			'form_field'     => array(
				'form_field' => array(
					'label'          => esc_html__( 'Fields', 'ncs-cart' ),
					'css'            => array(
						'main'                         => '%%order_class%% .scshortcode',
						'background_color'             => '%%order_class%% .scshortcode input.form-control',						
						'background_color_hover'       => '%%order_class%% .scshortcode input.form-control:hover',
						'focus_background_color'       => '%%order_class%% .scshortcode input.form-control:focus',
						'focus_background_color_hover' => '%%order_class%% .scshortcode input.form-control:focus:hover',
						'placeholder_focus'            => '%%order_class%% .scshortcode input.form-control:focus::-webkit-input-placeholder',
						'padding'                      => '%%order_class%% .scshortcode .form-group input.form-control',
						'margin'                       => '%%order_class%% .scshortcode .form-group input.form-control',
						'form_text_color'              => '%%order_class%% .scshortcode input.form-control',
						'form_text_color_hover'        => '%%order_class%% .scshortcode input.form-control:hover',
						'focus_text_color'             => '%%order_class%% .scshortcode input.form-control:focus',
						'focus_text_color_hover'       => '%%order_class%% .scshortcode input.form-control:focus:hover',
					),
					'box_shadow'     => false,
					'border_styles'  => false,
					'font_field'     => array(
						'css' => array(
							'main'  => implode(
								', ',
								array(
									"{$this->main_css_element} input.form-control",
									"{$this->main_css_element} input.form-control::placeholder",
									"{$this->main_css_element} input.form-control::-webkit-input-placeholder",
									"{$this->main_css_element} input.form-control::-moz-placeholder",
									"{$this->main_css_element} input.form-control:-ms-input-placeholder",
									"{$this->main_css_element} input.form-control[type=checkbox] + label",
									"{$this->main_css_element} input.form-control[type=radio] + label",
								)
							),
							'hover' => array(
								"{$this->main_css_element} input.form-control:hover",
								"{$this->main_css_element} input.form-control:hover::placeholder",
								"{$this->main_css_element} input.form-control:hover::-webkit-input-placeholder",
								"{$this->main_css_element} input.form-control:hover::-moz-placeholder",
								"{$this->main_css_element} input.form-control:hover:-ms-input-placeholder",
								"{$this->main_css_element} input.form-control[type=checkbox]:hover + label",
								"{$this->main_css_element} input.form-control[type=radio]:hover + label",
							),
						),
					),
					'margin_padding' => array(
						'css' => array(
							'main'    => '%%order_class%% input.form-control',
							'padding' => '%%order_class%% input.form-control',
							'margin'  => '%%order_class%% input.form-control',
						),
					),
				),				
				'form_label' => array(
					'label'          => esc_html__( 'Labels', 'ncs-cart' ),
					'css'            => array(
						'main'                         => '%%order_class%% .scshortcode .form-group label',
						'background_color'             => '%%order_class%% .scshortcode .form-group label',						
						'background_color_hover'       => '%%order_class%% .scshortcode .form-group label:hover',
						'focus_background_color'       => '%%order_class%% .scshortcode .form-group label:focus',
						'focus_background_color_hover' => '%%order_class%% .scshortcode .form-group label:focus:hover',						
						'padding'                      => '%%order_class%% .scshortcode .form-group label',
						'margin'                       => '%%order_class%% .scshortcode .form-group label',
						'form_text_color'              => '%%order_class%% .scshortcode .form-group label',
						'form_text_color_hover'        => '%%order_class%% .scshortcode .form-group label:hover',
						'focus_text_color'             => '%%order_class%% .scshortcode .form-group label:focus',
						'focus_text_color_hover'       => '%%order_class%% .scshortcode .form-group label:focus:hover',
					),
					'box_shadow'     => false,
					'border_styles'  => false,
					'font_field'     => array(
						'css' => array(
							'main'  => implode(
								', ',
								array(
									"{$this->main_css_element} .form-group label",															
								)
							),
							'hover' => array(
								"{$this->main_css_element} .form-group label:hover",											
							),
						),
					),
					'margin_padding' => array(
						'css' => array(
							'main'    => '%%order_class%% .form-group label',
							'padding' => '%%order_class%% .form-group label',
							'margin'  => '%%order_class%% .form-group label',
						),
					),
				),
				'form_payment_radio_plan' => array(
					'label'          => esc_html__( 'Payment Plans', 'ncs-cart' ),
					'css'            => array(
						'main'                         => '%%order_class%% .scshortcode .item label,%%order_class%% .scshortcode .item span',
						'background_color'             => '%%order_class%% .scshortcode .item label,%%order_class%% .scshortcode .item span',						
						'background_color_hover'       => '%%order_class%% .scshortcode .item label:hover,%%order_class%% .scshortcode .item span:hover',
						'focus_background_color'       => '%%order_class%% .scshortcode .item label:focus, %%order_class%% .scshortcode .item span:focus',
						'focus_background_color_hover' => '%%order_class%% .scshortcode .item label:focus:hover,%%order_class%% .scshortcode .item span:focus:hover',						
						'padding'                      => '%%order_class%% .scshortcode .item label,%%order_class%% .scshortcode .item span',
						'margin'                       => '%%order_class%% .scshortcode .item label, %%order_class%% .scshortcode .item span',
						'form_text_color'              => '%%order_class%% .scshortcode .item label, %%order_class%% .scshortcode .item span',
						'form_text_color_hover'        => '%%order_class%% .scshortcode .item label:hover, %%order_class%% .scshortcode .item span:hover',
						'focus_text_color'             => '%%order_class%% .scshortcode .item label:focus, %%order_class%% .scshortcode .item span:focus',
						'focus_text_color_hover'       => '%%order_class%% .scshortcode .item label:focus:hover, %%order_class%% .scshortcode .item span:focus:hover',
					),
					'box_shadow'     => false,
					'border_styles'  => false,
					'font_field'     => array(
						'css' => array(
							'main'  => implode(
								', ',
								array(
									"{$this->main_css_element} .item label,{$this->main_css_element} .item span",															
								)
							),
							'hover' => array(
								"{$this->main_css_element} .item label:hover,{$this->main_css_element} .item span",											
							),
						),
					),
					'margin_padding' => array(
						'css' => array(
							'main'    => '%%order_class%% .item',
							'padding' => '%%order_class%% .item',
							'margin'  => '%%order_class%% .item',
						),
					),
				),	
				'form_heading' => array(
					'label'          => esc_html__( 'Heading', 'ncs-cart' ),
					'css'            => array(
						'main'                         => '%%order_class%% .scshortcode h3.title,%%order_class%% .scshortcode .pay-methods',
						'background_color'             => '%%order_class%% .scshortcode h3.title,%%order_class%% .scshortcode .pay-methods',						
						'background_color_hover'       => '%%order_class%% .scshortcode h3.title:hover',
						'focus_background_color'       => '%%order_class%% .scshortcode h3.title:focus',
						'focus_background_color_hover' => '%%order_class%% .scshortcode h3.title:focus:hover',						
						'padding'                      => '%%order_class%% .scshortcode h3.title',
						'margin'                       => '%%order_class%% .scshortcode h3.title',
						'form_text_color'              => '%%order_class%% .scshortcode h3.title',
						'form_text_color_hover'        => '%%order_class%% .scshortcode h3.title:hover',
						'focus_text_color'             => '%%order_class%% .scshortcode h3.title:focus',
						'focus_text_color_hover'       => '%%order_class%% .scshortcode h3.title:focus:hover',
					),
					'box_shadow'     => false,
					'border_styles'  => false,
					'font_field'     => array(
						'css' => array(
							'main'  => implode(
								', ',
								array(
									"{$this->main_css_element} h3.title, {$this->main_css_element} .pay-methods",															
								)
							),
							'hover' => array(
								"{$this->main_css_element} h3.title:hover",											
							),
						),
					),
					'margin_padding' => array(
						'css' => array(
							'main'    => '%%order_class%% h3.title',
							'padding' => '%%order_class%% h3.title',
							'margin'  => '%%order_class%% h3.title',
						),
					),
				),				
				'form_steps' => array(
					'label'          => esc_html__( 'Steps Heading', 'ncs-cart' ),
					'css'            => array(
						'main'                         => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a',
						'background_color'             => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a',						
						'background_color_hover'       => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:hover',
						'focus_background_color'       => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:focus',
						'focus_background_color_hover' => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:focus:hover',						
						'padding'                      => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a',
						'margin'                       => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a',
						'form_text_color'              => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a',
						'form_text_color_hover'        => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:hover',
						'focus_text_color'             => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:focus',
						'focus_text_color_hover'       => '%%order_class%% .scshortcode .sc-checkout-form-steps .steps a:focus:hover',
					),
					'box_shadow'     => false,
					'border_styles'  => false,
					'font_field'     => array(
						'css' => array(
							'main'  => implode(
								', ',
								array(
									"{$this->main_css_element} h3.title, {$this->main_css_element} .sc-checkout-form-steps .steps a",															
								)
							),
							'hover' => array(
								"{$this->main_css_element} .sc-checkout-form-steps .steps a:hover",											
							),
						),
					),
					'margin_padding' => array(
						'css' => array(
							'main'    => '%%order_class%% .sc-checkout-form-steps .steps a',
							'padding' => '%%order_class%% .sc-checkout-form-steps .steps a',
							'margin'  => '%%order_class%% .sc-checkout-form-steps .steps a',
						),
					),
				),
			),
		);
	}
	
	public function sc_get_studio_cart_products(){
		$products_data = [];
		// Set the arguments for the query
		$args = array( 
		  'numberposts'		=> -1,
		  'post_type'		=> 'sc_product',
		  'post_status'		=> 'publish',		  
		  'orderby' 		=> 'title',
		  'order' 			=> 'ASC',		  
		);
		$products = get_posts($args);
		if($products):
			$products_data[0] = esc_html__( 'Dynamic', 'ncs-cart' );
		   // Loop the posts
		  foreach ($products as $product):
			$id = $product->ID;
			$name = get_the_title( $id );
			$products_data[ $id ] = $name;
		  endforeach; 
		  wp_reset_postdata();
		else:
			$products_data[0] = esc_html__( 'No Products Found', 'ncs-cart' );
		endif;
		return $products_data;
	}

	public function get_fields() {
		return array(
			'sc_product' => array(
				'label'           => esc_html__( 'Product', 'ncs-cart' ),
				'type'            => 'select',
				'options'		  => $this->sc_get_studio_cart_products(),	
				'default'         => 0,
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Select Product', 'ncs-cart' ),
				'toggle_slug'     => 'main_content',
			),
			'sc_hide_labels' => array(
				'label'           => esc_html__( 'Hide Labels', 'ncs-cart' ),
				'type'            => 'select',
				'options'         => array(
										'yes' => esc_html__( 'Yes', 'ncs-cart' ),
										'no'  => esc_html__( 'No', 'ncs-cart' ),
									),
				'default'         => 'yes',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Hide labels from order form.', 'ncs-cart' ),
				'toggle_slug'     => 'main_content',
			),
			'sc_template' => array(
				'label'           => esc_html__( 'Skin', 'ncs-cart' ),
				'type'            => 'select',
				'options'         => array(
										'' => esc_html__( 'Default', 'ncs-cart' ),
										'normal'  => esc_html__( 'Normal', 'ncs-cart' ),
										'2-step'  => esc_html__( '2-Step Form', 'ncs-cart' ),
										'opt-in'  => esc_html__( 'Opt-in Form', 'ncs-cart' ),
									),
				'default'         => 'false',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Choose order form skin.', 'ncs-cart' ),
				'toggle_slug'     => 'main_content',				
			),	
			'sc_coupon' => array(
				'label'           => esc_html__( 'Coupon Code', 'ncs-cart' ),
				'type'            => 'text',				
				'default'         => '',								
				'toggle_slug'     => 'main_content',				
			),					
		);
	}
	
	
	public function get_transition_fields_css_props() {
		$fields = parent::get_transition_fields_css_props();
		$fields['sc_lable_color']       = array( 'color' => '%%order_class%% h3.title' );
		return $fields;
	}
	

	public function render( $attrs, $content = null, $render_slug = null ) {			
			ob_start();  
			$hide_labels = $this->props['sc_hide_labels'];
			$template = $this->props['sc_template'];
			$coupon = $this->props['sc_coupon'];
			if (!in_array($this->props['sc_hide_labels'], ['yes', 'no'])) {
				$hide_labels = 'yes';
			}	
			$hide_labels = $hide_labels == "yes" ? "hide" : "show";
			$template = $template == "true" ? "2-step" : $template;
			
			$post_id = $this->props['sc_product'];	
            if( $template ){
                $content = "[studiocart-form builder=true id={$post_id} hide_labels='{$hide_labels}' template='{$template}' coupon='{$coupon}']";		
            }else{			
                $content = "[studiocart-form builder=true id={$post_id} hide_labels='{$hide_labels}' coupon='{$coupon}']";		
            }
			
			
			echo do_shortcode( $content );		
			return ob_get_clean();		
		//return; 
	}
}
new StudioCartOrderForm;
