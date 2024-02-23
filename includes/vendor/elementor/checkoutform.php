<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
use Elementor\Controls_Manager;
//use ElementorExtra\Module;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Typography;
use Elementor\Scheme_Color;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Border;

class NCS_Elementor_OrderForm extends Elementor\Widget_Base {

    public function get_name() {
        return 'sc-orderform';
    }

    public function get_title() {
        return apply_filters('studiocart_plugin_title','Studiocart') . __(' Order Form', 'ncs-cart');
    }

    public function get_categories() {
        return array('general');
    }

    public function get_icon() {
        return 'eicon-price-list';
    }

    /*
    public function get_script_depends() {
        return ['magnific-popup'];
    }

    public function get_style_depends() {
        return ['magnific-popup'];
    }
    */

    protected function register_controls() {
        $this->start_controls_section(
            'orderform',
            [
                'label' => __('General', 'ncs-cart'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );
        $scp = get_posts('post_type="sc_product"&numberposts=-1');
        $products[''] = __('Dynamic', 'ncs-cart');
        if ($scp) {
            foreach ($scp as $p) {
                $products[$p->ID] = $p->post_title;
            }
        } else {
            $products[0] = __('Dynamic', 'ncs-cart');
        }

        $this->add_control(
            'cf_id',
            [
                'label'   => __('Select product', 'ncs-cart'),
                'type'    => Controls_Manager::SELECT,
                'options' => $products,
                'default' => ''
            ]
        );

        $this->add_control(
            'hide_labels',
            [
                'label'        => __('Hide Labels', 'ncs-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on' => __( 'Hide', 'ncs-cart' ),
				'label_off' => __( 'Show', 'ncs-cart' ),
                'default'      => 'yes',
                'return_value' => 'yes',
                'prefix_class' => ' elementor-show-before-',
                'selectors' => [],
            ]
        );
        
        $this->add_control(
            'sc_template',
            [
                'label'   => __('Form Skin', 'ncs-cart'),
                'type'    => Controls_Manager::SELECT,
                'options' => [''=>__( 'Default', 'ncs-cart' ), 'normal'=>__( 'Normal', 'ncs-cart' ), 'yes' => __('2-Step', 'ncs-cart'), 'opt-in' =>  __('Opt-in', 'ncs-cart')],
                'default' => ''
            ]
        );
        
        $this->add_control(
            'coupon',
            [
                'label'        => __('Coupon Code', 'ncs-cart'),
                'type'         => Controls_Manager::TEXT,
                'default'      => '',
                'placeholder' => '',
                'selectors' => [],
            ]
        );

        $this->add_control(
            'ele_popup',
            [
                'label'        => __('Use in Elementor Popup', 'ncs-cart'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on' => __( 'Yes', 'ncs-cart' ),
				'label_off' => __( 'No', 'ncs-cart' ),
                'default'      => '',
                'return_value' => '1',
                'prefix_class' => ' elementor-show-before-',
                'selectors' => [],
            ]
        );

        $this->end_controls_section();


        //WRAPPER
        $this->start_controls_section(
            'section_wrapper_style',
            [
                'label' => __( 'Wrapper', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'wrapper_bgcolor',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'wrapper_border',
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .scshortcode',
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'wrapper_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'wrapper_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'wrapper_typography',
                'selector' => '{{WRAPPER}} .scshortcode',
            ]
        );

        $this->add_control(
            'wrapper_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();        
        
        //Tabs
        $this->start_controls_section(
            'section_tabs_style',
            [
                'label' => __( '2-Step Form Tabs', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition'		=> [
					'sc_template' => ['','yes']
				],
            ]
        );

        $this->add_control(
            'tabs_border_color',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_active_border_color',
            [
                'label' => __( 'Active Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps.sc-current' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_bgcolor',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_active_bgcolor',
            [
                'label' => __( 'Active Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps.sc-current' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps' => 'color: {{VALUE}};',
                ],
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'tabs_active_color',
            [
                'label' => __( 'Text Active Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps.sc-current' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_number_color',
            [
                'label' => __( 'Number Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps a .step-number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_active_number_color',
            [
                'label' => __( 'Number Active Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps.sc-current a .step-number' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_headline_color',
            [
                'label' => __( 'Headline Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps .step-heading .step-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'tabs_headline_active_color',
            [
                'label' => __( 'Headline Active Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .sc-checkout-form-steps .steps.sc-current .step-heading .step-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        //HEADING
        $this->start_controls_section(
            'section_heading_style',
            [
                'label' => __( 'Heading', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'label_bgcolor',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode h3.title' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'label_border',
				'label'     => __( 'Border', 'plugin-domain' ),
                'selector'  => '{{WRAPPER}} .scshortcode h3.title',
			]
		);


        $this->add_control(
            'label_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode h3.title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode h3.title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'label_typography',
                'selector' => '{{WRAPPER}} .scshortcode h3.title',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  h3.title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_align',
            [
                'label' => __( 'Alignment', 'ncs-cart' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'Left', 'ncs-cart' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'ncs-cart' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'ncs-cart' ),
                        'icon' => 'fa fa-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  h3.title' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  h3.title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
        
        //INPUT LABEL
        $this->start_controls_section(
            'section_inputlabel_style',
            [
                'label' => __( 'Form Field Label', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'inputlabel_bgcolor',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'inputlabel_border',
				'label'     => __( 'Border', 'plugin-domain' ),
                'selector'  => '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label',
			]
		);


        $this->add_control(
            'inputlabel_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'inputlabel_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'inputlabel_typography',
                'selector' => '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label',
            ]
        );

        $this->add_control(
            'inputlabel_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'inputlabel_align',
            [
                'label' => __( 'Alignment', 'ncs-cart' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'Left', 'ncs-cart' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'ncs-cart' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'ncs-cart' ),
                        'icon' => 'fa fa-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'inputlabel_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .pay-info label, {{WRAPPER}} .scshortcode .card-details label, {{WRAPPER}} .scshortcode .address-info label' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        //INPUT
        $this->start_controls_section(
            'section_input_style',
            [
                'label' => __( 'Text Field', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'input_size',
            [
                'label' => __( 'Input Height', 'ncs-cart' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} ElementsApp .InputElement' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'area_size',
            [
                'label' => __( 'Textarea Height', 'ncs-cart' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} textarea' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'input_typography',
                'selector' => '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} ElementsApp .InputElement, {{WRAPPER}} textarea',
            ]
        );

        $this->add_control(
            'input_color_placeholder',
            [
                'label' => __( 'Placeholder Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart.scshortcode ::-webkit-input-placeholder' => 'color: {{VALUE}} !important',
                    '{{WRAPPER}} .studiocart.scshortcode ::placeholder' => 'color: {{VALUE}} !important',
                    '{{WRAPPER}} .studiocart.scshortcode :-ms-input-placeholder' => 'color: {{VALUE}} !important',
                ],
            ]
        );

        $this->add_control(
            'input_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} ElementsApp .InputElement, {{WRAPPER}} textarea' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_input_style' );

        $this->start_controls_tab(
            'tab_input_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'input_background_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} .studiocart .selectize-dropdown.single, {{WRAPPER}} ElementsApp .InputElement, {{WRAPPER}} textarea' => 'background-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_input_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'input_background_color_hover',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]):hover, {{WRAPPER}} .studiocart .StripeElement:hover, {{WRAPPER}} ElementsApp .InputElement:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_border_bottom_color_hover',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]):hover, {{WRAPPER}} .studiocart .StripeElement:hover, {{WRAPPER}} ElementsApp .InputElement:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_input_focus',
            [
                'label' => __( 'Focus', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'input_background_color_focus',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]):focus, {{WRAPPER}} .studiocart .StripeElement:focus, {{WRAPPER}} ElementsApp .InputElement:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_border_bottom_color_focus',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]):focus, {{WRAPPER}} .studiocart .StripeElement:focus, {{WRAPPER}} ElementsApp .InputElement:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'tab_input_error',
            [
                'label' => __( 'Error', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'input_background_color_error',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input.form-control.invalid, {{WRAPPER}} .studiocart .StripeElement--invalid' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'input_border_bottom_color_error',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart input.form-control.invalid, {{WRAPPER}} .studiocart .StripeElement--invalid' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'input_border',
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} .studiocart .selectize-dropdown.single, {{WRAPPER}} ElementsApp .InputElement',
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'input_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} .studiocart .selectize-dropdown.single, {{WRAPPER}} ElementsApp .InputElement' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'input_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} ElementsApp .InputElement' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'input_margin',
            [
                'label'      => __( 'Margin', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .studiocart input:not([type="radio"]):not([type="checkbox"]), {{WRAPPER}} .studiocart .StripeElement, {{WRAPPER}} .studiocart select.form-control, {{WRAPPER}} .studiocart .selectize-control.single .selectize-input, {{WRAPPER}} ElementsApp .InputElement' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        //VALID

        $this->start_controls_section(
            'section_valid_style',
            [
                'label' => __( 'Error Message', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'valid_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode.studiocart .error, {{WRAPPER}} .scshortcode.studiocart #card-errors' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'valid_typography',
                'selector' => '{{WRAPPER}} .scshortcode.studiocart .error, {{WRAPPER}} .scshortcode.studiocart #card-errors',
            ]
        );

        $this->add_responsive_control(
            'valid_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode.studiocart .error, {{WRAPPER}} .scshortcode.studiocart #card-errors' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        //SELECT

        $this->start_controls_section(
            'section_radio_style',
            [
                'label' => __( 'Radio Button', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'radio_typography',
                'selector' => '{{WRAPPER}} .scshortcode .products .item,{{WRAPPER}} .studiocart #sc-payment-form input[type="radio"] + label',
            ]
        );

        $this->start_controls_tabs( 'tabs_radio_style' );

        $this->start_controls_tab(
            'tab_radio_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'radio_text_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item, {{WRAPPER}} .studiocart input[type="radio"] + label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'radio_fill_color',
            [
                'label' => __( 'Selection Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .studiocart #sc-payment-form input[type="checkbox"] + label:after' => 'border-color: {{VALUE}};',
                    '{{WRAPPER}} .studiocart #sc-payment-form input[type="radio"] + label:after' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_radio_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'radio_text_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:hover, {{WRAPPER}} .studiocart input[type="radio"] + label:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_radio_focus',
            [
                'label' => __( 'Focus', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'radio_text_focus_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:focus, {{WRAPPER}} .studiocart input[type="radio"] + label:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
        
        //PAYMENT PLAN

        $this->start_controls_section(
            'section_seclect_style',
            [
                'label' => __( 'Payment Plan', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'select_size',
            [
                'label' => __( 'Height', 'ncs-cart' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'select_typography',
                'selector' => '{{WRAPPER}} .scshortcode .products .item, {{WRAPPER}} .studiocart #sc-payment-form .products input[type="radio"] + label',
            ]
        );

        $this->start_controls_tabs( 'tabs_select_style' );

        $this->start_controls_tab(
            'tab_select_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'select_text_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_price_color',
            [
                'label' => __( 'Price Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item .price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_bg_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'background-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_select_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'select_text_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_price_hover_color',
            [
                'label' => __( 'Price Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:hover .price' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_bg_color_hover',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_bd_color_hover',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_select_focus',
            [
                'label' => __( 'Focus', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'select_text_focus_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item.sc-selected' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_bg_color_focus',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item.sc-selected' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'select_bd_color_focus',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item.sc-selected' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'select_border',
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .scshortcode .products .item',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'select_border_radius',
            [
                'label'      => __('Border Radius', 'ncs-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'select_padding',
            [
                'label' => __( 'Padding', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'select_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .products .item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
        
        //ORDER TOTAL

        $this->start_controls_section(
            'section_total_style',
            [
                'label' => __( 'Order Total', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'total_size',
            [
                'label' => __( 'Height', 'ncs-cart' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'total_typography',
                'selector' => '{{WRAPPER}} .scshortcode .total',
            ]
        );

        $this->start_controls_tabs( 'tabs_total_style' );

        $this->start_controls_tab(
            'tab_total_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'total_text_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_bg_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total' => 'background-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_total_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'total_text_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_bg_color_hover',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_bd_color_hover',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_total_focus',
            [
                'label' => __( 'Focus', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'total_text_focus_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:focus' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_bg_color_focus',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'total_bd_color_focus',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'total_border',
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .scshortcode .total',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'total_border_radius',
            [
                'label'      => __('Border Radius', 'ncs-cart'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .total' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'total_padding',
            [
                'label' => __( 'Padding', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'total_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .total' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        //BUTTON

        $this->start_controls_section(
            'section_button_style',
            [
                'label' => __( 'Submit Button', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'button_size',
            [
                'label'        => __('Size', 'ncs-cart'),
                'type'         => Controls_Manager::SELECT,
                'default'      => 'md',
                'options'      => [
                    'xs'                  => __('Extra Small', 'ncs-cart'),
                    'sm'           => __('Small', 'ncs-cart'),
                    'md'         => __('Medium', 'ncs-cart'),
                    'lg'   => __('Large', 'ncs-cart'),
                    'xl' => __('Extra Large', 'ncs-cart'),
                ],
                'prefix_class' => 'elementor-wpcf7-button-',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .scshortcode input[type="submit"], {{WRAPPER}} button',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'label' => __( 'Subtext Typography', 'ncs-cart' ),
                'name' => 'button_subtext_typography',
                'selector' => '{{WRAPPER}} .scshortcode input[type="submit"] .sub-text, {{WRAPPER}} button .sub-text',
            ]
        );

        $this->start_controls_tabs( 'tabs_button_style' );

        $this->start_controls_tab(
            'tab_button_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:not(:hover), {{WRAPPER}} button:not(:hover)' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_subtext_color',
            [
                'label' => __( 'Subtext Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:not(:hover) .sub-text, {{WRAPPER}} button:not(:hover) .sub-text' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'background_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:not(:hover), {{WRAPPER}} button:not(:hover)' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_button_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'button_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:hover, {{WRAPPER}} button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_subtext_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:hover .sub-text, {{WRAPPER}} button:hover .sub-text' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_hover_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:hover, {{WRAPPER}} button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_border_color',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"]:hover, {{WRAPPER}} button:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .scshortcode input[type="submit"], {{WRAPPER}} button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => __( 'Border Radius', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode input[type="submit"], {{WRAPPER}} button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .scshortcode input[type="submit"], {{WRAPPER}} button',
            ]
        );
        $this->add_responsive_control(
            'align',
            [
                'label' => __( 'Alignment', 'ncs-cart' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'Left', 'ncs-cart' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'ncs-cart' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'ncs-cart' ),
                        'icon' => 'fa fa-align-right',
                    ],
                    'justify' => [
                        'title' => __( 'Justified', 'ncs-cart' ),
                        'icon' => 'fa fa-align-justify',
                    ],
                ],
                'prefix_class' => 'elementor%s-align-',
                'default' => '',
            ]
        );
        $this->add_responsive_control(
            'text_padding',
            [
                'label' => __( 'Padding', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} input[type="submit"],{{WRAPPER}} button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'text_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} input[type="submit"],{{WRAPPER}} button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        //COUPON TOGGLE
        $this->start_controls_section(
            'section_coupon_heading_style',
            [
                'label' => __( 'Coupon Toggle', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'coupon_heading_bgcolor',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode #sc-coupon-toggle' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'      => 'border_coupon_heading',
				'label'     => __( 'Border', 'plugin-domain' ),
                'selector'  => '{{WRAPPER}} .scshortcode #sc-coupon-toggle',
			]
		);


        $this->add_control(
            'coupon_heading_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode #sc-coupon-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'coupon_heading_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode #sc-coupon-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'coupon_heading_typography',
                'selector' => '{{WRAPPER}} .scshortcode #sc-coupon-toggle',
            ]
        );

        $this->add_control(
            'coupon_heading_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  #sc-coupon-toggle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'coupon_heading_align',
            [
                'label' => __( 'Alignment', 'ncs-cart' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'Left', 'ncs-cart' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'ncs-cart' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'ncs-cart' ),
                        'icon' => 'fa fa-align-right',
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  #sc-coupon-toggle' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'coupon_heading_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode  #sc-coupon-toggle' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();


        //COUPON INPUT
        $this->start_controls_section(
            'section_coupon_input_style',
            [
                'label' => __( 'Coupon Input', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'coupon_input_size',
            [
                'label' => __( 'Input Height', 'ncs-cart' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'coupon_input_typography',
                'selector' => '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]',
            ]
        );

        $this->add_control(
            'coupon_input_color_placeholder',
            [
                'label' => __( 'Placeholder Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .studiocart.scshortcode .coupon-code ::-webkit-input-placeholder' => 'color: {{VALUE}} !important',
                    '{{WRAPPER}} .studiocart.scshortcode .coupon-code ::placeholder' => 'color: {{VALUE}} !important',
                    '{{WRAPPER}} .studiocart.scshortcode .coupon-code :-ms-input-placeholder' => 'color: {{VALUE}} !important',
                ],
            ]
        );

        $this->add_control(
            'coupon_input_color',
            [
                'label' => __( 'Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->start_controls_tabs( 'tabs_coupon_input_style' );

        $this->start_controls_tab(
            'tab_coupon_input_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'coupon_input_background_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'background-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_coupon_input_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'coupon_input_background_color_hover',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'coupon_input_border_bottom_color_hover',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );


        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_coupon_input_focus',
            [
                'label' => __( 'Focus', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'coupon_input_background_color_focus',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]:focus' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'coupon_input_border_bottom_color_focus',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]:focus' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'        => 'coupon_input_border',
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]',
                'separator'   => 'before',
            ]
        );

        $this->add_control(
            'coupon_input_border_radius',
            [
                'label'      => __( 'Border Radius', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'coupon_input_padding',
            [
                'label'      => __( 'Padding', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        $this->add_responsive_control(
            'coupon_input_margin',
            [
                'label'      => __( 'Margin', 'ncs-cart' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="text"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
        
        //COUPON BUTTON
        
        $this->start_controls_section(
            'section_coupon_button_style',
            [
                'label' => __( 'Coupon Button', 'ncs-cart' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'coupon_button_size',
            [
                'label'        => __('Size', 'ncs-cart'),
                'type'         => Controls_Manager::SELECT,
                'default'      => 'md',
                'options'      => [
                    'xs'                  => __('Extra Small', 'ncs-cart'),
                    'sm'           => __('Small', 'ncs-cart'),
                    'md'         => __('Medium', 'ncs-cart'),
                    'lg'   => __('Large', 'ncs-cart'),
                    'xl' => __('Extra Large', 'ncs-cart'),
                ],
                'prefix_class' => 'elementor-wpcf7-button-',
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'coupon_button_typography',
                'selector' => '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]',
            ]
        );

        $this->start_controls_tabs( 'tabs_coupon_button_style' );

        $this->start_controls_tab(
            'tab_coupon_button_normal',
            [
                'label' => __( 'Normal', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'coupon_button_text_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]:not(:hover)' => 'color: {{VALUE}};',
                ],
            ]
        );
        $this->add_control(
            'coupon_button_background_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]:not(:hover)' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_coupon_button_hover',
            [
                'label' => __( 'Hover', 'ncs-cart' ),
            ]
        );

        $this->add_control(
            'coupon_button_hover_color',
            [
                'label' => __( 'Text Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'coupon_button_background_hover_color',
            [
                'label' => __( 'Background Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'coupon_button_hover_border_color',
            [
                'label' => __( 'Border Color', 'ncs-cart' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]:hover' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name' => 'coupon_button_border',
                'placeholder' => '1px',
                'default' => '1px',
                'selector' => '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'coupon_button_border_radius',
            [
                'label' => __( 'Border Radius', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'coupon_button_box_shadow',
                'selector' => '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]',
            ]
        );
        $this->add_responsive_control(
            'coupon_button_align',
            [
                'label' => __( 'Alignment', 'ncs-cart' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'left'    => [
                        'title' => __( 'Left', 'ncs-cart' ),
                        'icon' => 'fa fa-align-left',
                    ],
                    'center' => [
                        'title' => __( 'Center', 'ncs-cart' ),
                        'icon' => 'fa fa-align-center',
                    ],
                    'right' => [
                        'title' => __( 'Right', 'ncs-cart' ),
                        'icon' => 'fa fa-align-right',
                    ],
                    'justify' => [
                        'title' => __( 'Justified', 'ncs-cart' ),
                        'icon' => 'fa fa-align-justify',
                    ],
                ],
                'prefix_class' => 'elementor%s-align-',
                'default' => '',
            ]
        );
        $this->add_responsive_control(
            'coupon_button_padding',
            [
                'label' => __( 'Padding', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'coupon_button_margin',
            [
                'label' => __( 'Margin', 'ncs-cart' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors' => [
                    '{{WRAPPER}} .scshortcode .coupon-code input[type="button"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
    }

    protected function render() {
        global $post;
        $settings = $this->get_settings_for_display();
        
        $args['id']             = intval($settings['cf_id']);
        $args['hide_labels']    = ($settings['hide_labels']=='yes') ? 'hide' : 'show';
        $args['template']       = ($settings['sc_template']=='yes') ? '2-step' : $settings['sc_template'];
        
        $shortcode = '[studiocart-form builder=true id='.intval($args['id']).' ele_popup='.intval($settings['ele_popup']).' hide_labels="'.$args['hide_labels'].'" template="'.$args['template'].'"';
        if ($settings['coupon']) {
            $shortcode .= ' coupon="'.$settings['coupon'].'"]';
        } else {
            $shortcode .= ']';
        }
        
        echo do_shortcode($shortcode);
    }
}

\Elementor\Plugin::instance()->widgets_manager->register( new NCS_Elementor_OrderForm() );