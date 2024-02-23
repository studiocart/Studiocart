<?php
namespace Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class NCS_Elementor_Countdown_Timer extends Widget_Base {

	public function get_name() { 		//Function for get the slug of the element name.
		return 'sc-countdown';
	}

	public function get_title() { 		//Function for get the name of the element.
		return apply_filters('studiocart_plugin_title','Studiocart') . __( ' Countdown', 'ncs-cart' );
	}
	public function get_icon() { 		//Function for get the icon of the element.
		return 'eicon-countdown';
	}	
	public function get_categories() { 		//Function for include element into the category.
		return [ 'general' ];
	}	
    /* 
	 * Adding the controls fields for the countdown timer
	*/
	protected function register_controls() {
		$this->start_controls_section(
			'sc_countdown_section',
			[
				'label' => __( 'Countdown Timer', 'ncs-cart' ),
			]
		);
		
		$this->add_control(
			'sc_countdown_expire_type',
			[
				'label'			=> __('Type', 'ncs-cart'),
				'label_block'	=> false,
				'type'			=> Controls_Manager::SELECT,
				'options'		=> [
					'due_date'		=> __('Due Date', 'ncs-cart'),
					'evergreen'	=> __('Evergreen Timer', 'ncs-cart'),
					'deadline'	=> __('Order-Based Evergreen', 'ncs-cart')
				],
				'default' => 'due_date'
			]
		);
		
	    $this->add_control(
			'sc_countdown_due_date',
			[
				'label' => __( 'Due Date', 'ncs-cart' ),
				'type' => Controls_Manager::DATE_TIME,
				'default' => date( 'Y-m-d H:i', strtotime( '+1 month' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
				'description' => sprintf( __( 'Date set according to your timezone: %s.', 'ncs-cart' ), Utils::get_timezone_string() ),
				'condition'		=> [
					'sc_countdown_expire_type' => 'due_date'
				],
			]
		);
        
        
        $scp = get_posts('post_type="sc_product"&numberposts=-1');
        $products[''] = __('Select a product', 'ncs-cart');
        if ($scp) {
            foreach ($scp as $p) {
                $products[$p->ID] = $p->post_title;
            }
        } else {
            $products[0] = __('No products found', 'ncs-cart');
        }

        $this->add_control(
            'sc_countdown_product',
            [
                'label'   => __('Product', 'ncs-cart'),
                'type'    => Controls_Manager::SELECT,
                'options' => $products,
                'default' => '',
				'condition'		=> [
					'sc_countdown_expire_type' => 'deadline'
				],
            ]
        );
		
		$this->add_control(
			'sc_countdown_after_day',
			[
				'label' => __( 'Days', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 30,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'evergreen'
				],
			]
		);
		$this->add_control(
			'sc_countdown_after_hours',
			[
				'label' => __( 'Hours', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 24,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'evergreen'
				],
			]
		);
		$this->add_control(
			'sc_countdown_after_minutes',
			[
				'label' => __( 'Minutes', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'evergreen'
				],
			]
		);
		$this->add_control(
			'sc_countdown_after_seconds',
			[
				'label' => __( 'Seconds', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 1,
				'default' => 20,
				'condition'		=> [
					'sc_countdown_expire_type' => 'evergreen'
				]
			]
		);
        
        $this->add_control(
			'sc_countdown_deadline_day',
			[
				'label' => __( 'Days', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 30,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'deadline'
				],
			]
		);
		$this->add_control(
			'sc_countdown_deadline_hours',
			[
				'label' => __( 'Hours', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 24,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'deadline'
				],
			]
		);
		$this->add_control(
			'sc_countdown_deadline_minutes',
			[
				'label' => __( 'Minutes', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 1,
				'default' => 0,
				'condition'		=> [
					'sc_countdown_expire_type' => 'deadline'
				],
			]
		);
		$this->add_control(
			'sc_countdown_deadline_seconds',
			[
				'label' => __( 'Seconds', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 1,
				'default' => 20,
				'condition'		=> [
					'sc_countdown_expire_type' => 'deadline'
				],
			]
		);
        
        //updateClock show block
        
		$this->add_control(
			'sc_countdown_show_days',
			[
				'label' => __( 'Days', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'ncs-cart' ),
				'label_off' => __( 'Hide', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'yes',
                'separator' => 'before',
			]
		);
		$this->add_control(
			'sc_countdown_show_hours',
			[
				'label' => __( 'Hours', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'ncs-cart' ),
				'label_off' => __( 'Hide', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'sc_countdown_show_minutes',
			[
				'label' => __( 'Minutes', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'ncs-cart' ),
				'label_off' => __( 'Hide', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'yes',
			]
		);
		$this->add_control(
			'sc_countdown_show_seconds',
			[
				'label' => __( 'Seconds', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Show', 'ncs-cart' ),
				'label_off' => __( 'Hide', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'yes',
				'separator' => 'after',
			]
		);
        
        $this->add_control(
			'sc_countdown_change_labels',
			[
				'label' => __( 'Custom Labels', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'ncs-cart' ),
				'label_off' => __( 'No', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'no',
			]
		);
		$this->add_control(
			'sc_countdown_label_days',
			[
				'label' => __( 'Days', 'ncs-cart' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Days', 'ncs-cart' ),
				'placeholder' => __( 'Days', 'ncs-cart' ),
				'condition' => [
					'sc_countdown_change_labels' => 'yes',
					'sc_countdown_show_days' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_label_hours',
			[
				'label' => __( 'Hours', 'ncs-cart' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Hours', 'ncs-cart' ),
				'placeholder' => __( 'Hours', 'ncs-cart' ),
				'condition' => [
					'sc_countdown_change_labels' => 'yes',
					'sc_countdown_show_hours' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_label_minutes',
			[
				'label' => __( 'Minutes', 'ncs-cart' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Minutes', 'ncs-cart' ),
				'placeholder' => __( 'Minutes', 'ncs-cart' ),
				'condition' => [
					'sc_countdown_change_labels' => 'yes',
					'sc_countdown_show_minutes' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_label_seconds',
			[
				'label' => __( 'Seconds', 'ncs-cart' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Seconds', 'ncs-cart' ),
				'placeholder' => __( 'Seconds', 'ncs-cart' ),
				'condition' => [
					'sc_countdown_change_labels' => 'yes',
					'sc_countdown_show_seconds' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_expire_show_type',
			[
				'label'			=> __('Action After Expire', 'ncs-cart'),
				'label_block'	=> true,
				'type'			=> Controls_Manager::SELECT2,
                'multiple'      => true,
				'options'		=> [
					'message'		   => __('Show Message', 'ncs-cart'),
					'redirect_link'	   => __('Redirect to Link', 'ncs-cart'),
					'hide_sccd'        => __('Hide Timer', 'ncs-cart'),
                    'disable_coupon'    => __('Disable URL Coupons', 'ncs-cart'),
				],
				'default' => 'message',
                'separator' => 'before',
			]
		);
		$this->add_control(
			'sc_countdown_expire_message',
			[
				'label'			=> __('Expire Message', 'ncs-cart'),
				'type'			=> Controls_Manager::TEXTAREA,
				'default'		=> __('Sorry you are late!','ncs-cart'),
				'condition'		=> [
					'sc_countdown_expire_show_type' => 'message'
				]
			]
		);
		$this->add_control(
			'sc_countdown_expire_redirect_link',
			[
				'label'			=> __('Redirect URL', 'ncs-cart'),
				'type'			=> Controls_Manager::URL,
				'show_external' => true,
				'default' => [
					'url' => '',
					'is_external' => true,
					'nofollow' => false,
				],
		
				'condition'		=> [
					'sc_countdown_expire_show_type' => 'redirect_link'
				],
			]
		);
		$this->end_controls_section();
		
		$this->start_controls_section(
			'sc_countdown_restart_section',
			[
				'label' => __( 'Restart Countdown' , 'ncs-cart' ),
				'condition' => [
					'sc_countdown_expire_type' => 'evergreen testing'
				],
			]
		);
        $this->add_control(
			'sc_countdown_restart',
			[
				'label' => __( 'Restart Countdown', 'ncs-cart' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'Yes', 'ncs-cart' ),
				'label_off' => __( 'No', 'ncs-cart' ),
				'return_value' => 'yes',
				'default' => 'no',
				'description' => __('Automatically restart the countdown after the specified amount of time passes.', 'ncs-cart' )
			]
		);
		$this->add_control(
			'sc_countdown_restart_after_days',
			[
				'label' => __( 'Days', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 24,
				'step' => 0,
				'default' => 0,
				'condition' => [
					'sc_countdown_restart' => 'yes',
					'sc_countdown_show_days' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_restart_after_hours',
			[
				'label' => __( 'Hours', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 24,
				'step' => 0,
				'default' => 0,
				'condition' => [
					'sc_countdown_restart' => 'yes',
					'sc_countdown_show_days' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_restart_after_minutes',
			[
				'label' => __( 'Minutes', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 0,
				'default' => 0,
				'condition' => [
					'sc_countdown_restart' => 'yes',
					'sc_countdown_show_days' => 'yes',
				],
			]
		);
		$this->add_control(
			'sc_countdown_restart_after_seconds',
			[
				'label' => __( 'Seconds', 'ncs-cart' ),
				'type' => Controls_Manager::NUMBER,
				'min' => 0,
				'max' => 60,
				'step' => 0,
				'default' => 0,
				'condition' => [
					'sc_countdown_restart' => 'yes',
					'sc_countdown_show_days' => 'yes',
				],
			]
		);
        
		$this->end_controls_section();
		
		$this->start_controls_section(   
			'sc_countdown_style_section',
			[
				'label' => __( 'Box', 'ncs-cart' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
            'sc_countdown_box_align',
                [
                    'label'         => esc_html__( 'Alignment', 'ncs-cart' ),
                    'type'          => Controls_Manager::CHOOSE,
                    'options'       => [
                        'left'      => [
                            'title'=> esc_html__( 'Left', 'ncs-cart' ),
                            'icon' => 'fa fa-align-left',
                            ],
                        'center'    => [
                            'title'=> esc_html__( 'Center', 'ncs-cart' ),
                            'icon' => 'fa fa-align-center',
                            ],
                        'right'     => [
                            'title'=> esc_html__( 'Right', 'ncs-cart' ),
                            'icon' => 'fa fa-align-right',
                            ],
                        ],
                    'toggle'        => false,
                    'default'       => 'center',
                    'selectors'     => [
                        '{{WRAPPER}} .sc-countdown' => 'text-align: {{VALUE}};',
                        ],
                ]
        );
		$this->add_control(
			'sc_countdown_box_background_color',
			[
				'label' => __( 'Background Color', 'ncs-cart' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-box' => 'background-color: {{VALUE}};',
				],
				'separator' => 'after',
			]
		);
		$this->add_responsive_control(
			'sc_countdown_column_width',
			[
				'label' => __( 'Width', 'ncs-cart' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 100,
				],
				'range' => [
					'px' => [
						'min' => 50,
						'max' => 500,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .cd-column' => 'width:calc( {{SIZE}}{{UNIT}} );',
				],
			]
		);
		$this->add_responsive_control(
			'sc_countdown_box_spacing',
			[
				'label' => __( 'Space Between', 'ncs-cart' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'body:not(.rtl) {{WRAPPER}} .sc-countdown .cd-column:not(:first-of-type)' => 'margin-left: calc( {{SIZE}}{{UNIT}}/2 );',
					'body:not(.rtl) {{WRAPPER}} .sc-countdown .cd-column:not(:last-of-type)' => 'margin-right: calc( {{SIZE}}{{UNIT}}/2 );',
					'body.rtl {{WRAPPER}} .sc-countdown .cd-column:not(:first-of-type)' => 'margin-right: calc( {{SIZE}}{{UNIT}}/2 );',
					'body.rtl {{WRAPPER}} .sc-countdown .cd-column:not(:last-of-type)' => 'margin-left: calc( {{SIZE}}{{UNIT}}/2 );',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'box_border',
	            'selector' => '{{WRAPPER}} .sc-countdown .timer-box',
				'separator' => 'before',
			]
		);
		$this->add_control(
			'sc_countdown_box_border_radius',
			[
				'label' => __( 'Border Radius', 'ncs-cart' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		
        $this->add_responsive_control(
			'sc_countdown_box_margin',
			[
				'label' => __( 'Margin', 'ncs-cart' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .cd-column' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		
		$this->add_responsive_control(
			'sc_countdown_box_padding',
			[
				'label' => __( 'Padding', 'ncs-cart' ),
				'type' => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .cd-column .timer-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();
		$this->start_controls_section(
			'sc_countdown_digits_style_section',
			[
				'label' => __( 'Digits', 'ncs-cart' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_responsive_control(
			'sc_countdown_digit_spacing',
			[
				'label' => __( 'Spacing', 'ncs-cart' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-data' => 'margin-bottom: calc( {{SIZE}}{{UNIT}}/2 );',
				],
			]
		);
		$this->add_control(
			'sc_countdown_digit_background_color',
			[
				'label' => __( 'Background Color', 'ncs-cart' ),
				'type' => Controls_Manager::COLOR,
				'default'       => 'rgba(0, 0, 0, 0.75)',
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-data' => 'background-color: {{VALUE}};',
				],
				'separator' => 'after',
			]
		);
		$this->add_control(
			'sc_countdown_digits_color',
			[
				'label' => __( 'Color', 'ncs-cart' ),
				'type' => Controls_Manager::COLOR,
                'default' => '#FFFFFF',
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-data' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'eac_digits_typography',
				'selector' => '{{WRAPPER}} .sc-countdown .timer-data',
			]
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'digit_box_border',
	            'selector' => '{{WRAPPER}} .sc-countdown .timer-data',
				'separator' => 'before',
			]
		);
		$this->add_control(
			'sc_countdown_digit_box_border_radius',
			[
				'label' => __( 'Border Radius', 'ncs-cart' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
                'default' => [
					'size' => 300,
				],
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-data' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);
		$this->end_controls_section();   
		
		$this->start_controls_section(
			'sc_countdown_labels_style_section',
			[
				'label' => __( 'Labels', 'ncs-cart' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		$this->add_control(
			'sc_countdown_label_background_color',
			[
				'label' => __( 'Background Color', 'ncs-cart' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-title' => 'background-color: {{VALUE}};',
				],
				'separator' => 'after',
			]
		);
		$this->add_control(
			'sc_countdown_label_color',
			[
				'label' => __( 'Color', 'ncs-cart' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sc-countdown .timer-title' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'eac_label_typography',
				'selector' => '{{WRAPPER}} .sc-countdown .timer-title',
			]
		);
		$this->end_controls_section();   
		
		$this->start_controls_section(
			'sc_countdown_finish_message_style_section',
			[
				'label' => __('Message', 'ncs-cart'),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition'		=> [
					'sc_countdown_expire_show_type' => 'message'
				],
			]
		);
		$this->add_control(
			'sc_countdown_message_color',
			[
				'label' => __('Color', 'ncs-cart'),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .sc-countdown-finished-message' => 'color: {{VALUE}};',
				],
			]
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'eac_message_typography',
				'selector' => '{{WRAPPER}} .sc-countdown-finished-message',
			]
		);
		$this->end_controls_section();  
	}
	
	/**
	 * Render countdown widget output on the frontend.
	 *
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings();
		$due_date = $settings['sc_countdown_due_date'];
        $after_days = $settings['sc_countdown_after_day'];
        $after_hours = $settings['sc_countdown_after_hours'];
        $after_minutes = $settings['sc_countdown_after_minutes'];
        $after_seconds = $settings['sc_countdown_after_seconds'];
        
        if($settings['sc_countdown_expire_type']=='deadline' ){
            $after_days = $settings['sc_countdown_deadline_day'];
            $after_hours = $settings['sc_countdown_deadline_hours'];
            $after_minutes = $settings['sc_countdown_deadline_minutes'];
            $after_seconds = $settings['sc_countdown_deadline_seconds'];
        }
		
		$change_label_text = $settings['sc_countdown_change_labels'];
		if($change_label_text=='yes'){
			$day_text = $settings['sc_countdown_label_days'];
			$hour_text = $settings['sc_countdown_label_hours'];
			$minutes_text = $settings['sc_countdown_label_minutes'];
			$seconds_text = $settings['sc_countdown_label_seconds'];
		}
        
        $expire_actions = (array) $settings['sc_countdown_expire_show_type'];
        
		if ( in_array("hide_sccd", $expire_actions) ){
			$hide_sccd ="sc-countdown-hide";
		} else {
			$hide_sccd ="sc-countdown-show";
		}
		?>
		<div id="sc-countdown-<?php echo esc_attr($this->get_id()); ?>" class="sc-countdown <?php echo $hide_sccd;?>">
		    <div class="cd-column" style=<?php if($settings['sc_countdown_show_days']=='yes'){?> "display:inline-block" <?php } else { ?> "display:none"  <?php } ?> > 
			    <div class="timer-box timer-days">
				    <div class="timer-data days"></div>
				    <div class="timer-title"><?php if($change_label_text=='yes'){echo $day_text;}else{ echo "Days"; }?></div>
			    </div>
			</div>
			
			<div class="cd-column" style=<?php if($settings['sc_countdown_show_hours']=='yes'){?> "display:inline-block" <?php } else { ?> "display:none"  <?php } ?> >
			    <div class="timer-box timer-hours">
					<div class="timer-data hours"></div>
					<div class="timer-title"><?php if($change_label_text=='yes'){echo $hour_text;}else{ echo "Hours"; }?></div>
			    </div>
			</div>
			
			<div class="cd-column" style=<?php if($settings['sc_countdown_show_minutes']=='yes'){?> "display:inline-block" <?php } else { ?> "display:none"  <?php } ?> >
			    <div class="timer-box timer-minutes">
				    <div class="timer-data minutes"></div>
				    <div class="timer-title"><?php if($change_label_text=='yes'){echo $minutes_text;}else{ echo "Minutes"; }?></div>
			    </div>
			</div>
		
			<div class="cd-column" style=<?php if($settings['sc_countdown_show_seconds']=='yes'){?> "display:inline-block" <?php } else { ?> "display:none"  <?php } ?> >
			    <div class="timer-box timer-seconds">
				    <div class="timer-data seconds"></div>
				    <div class="timer-title"><?php if($change_label_text=='yes'){echo $seconds_text;}else{ echo "Seconds"; }?></div>
			    </div>
			</div>
			
	    </div>
		<div id="sc-countdown-finished-message-<?php echo esc_attr($this->get_id()); ?>" class="sc-countdown-finished-message"></div>
		<div id="studiocart-countdown-<?php echo esc_attr($this->get_id()); ?>" class="studiocart-countdown-init"></div>
		<script>
			
            function getTimeRemaining(endtime) {		
					    var t = new Date(endtime) - new Date();
                        var seconds = Math.max(0, Math.floor((t / 1000) % 60));			
					    var minutes = Math.max(0, Math.floor((t/1000/60) % 60 ));
					    var hours = Math.max(0, Math.floor((t/(1000*60*60)) % 24 ));
					    var days = Math.max(0, Math.floor(t/(1000*60*60*24) ));
 
					return {
						'total': t,
						'days': days,
						'hours': hours,
						'minutes': minutes,
						'seconds': seconds
					};
				}

				function initializeClock(id, endtime) {
					var clock = document.getElementById(id);
					var daysSpan = clock.querySelector('.days');
					var hoursSpan = clock.querySelector('.hours');
					var minutesSpan = clock.querySelector('.minutes');
					var secondsSpan = clock.querySelector('.seconds');

					function updateClock() {
                        
						var t = getTimeRemaining(endtime);
						daysSpan.innerHTML = ('0' + t.days).slice(-2);
						hoursSpan.innerHTML = ('0' + t.hours).slice(-2);
						minutesSpan.innerHTML = ('0' + t.minutes).slice(-2);
						secondsSpan.innerHTML = ('0' + t.seconds).slice(-2);
						<?php $target = $settings['sc_countdown_expire_redirect_link']['is_external'] ? '_blank' : '_self'; ?>
						if ( t.total <= 0 ) {
						    clearInterval( timeinterval );
                            
							<?php 
                            foreach ($expire_actions as $action) :
                            if ($action=="message"): ?>
								jQuery("#sc-countdown-finished-message-<?php echo esc_attr($this->get_id()); ?>").html( "<span><?php echo $settings['sc_countdown_expire_message'];?></span>" );
							<?php elseif ( $action=="hide_sccd" ): ?>
								jQuery(".sc-countdown-hide").hide();
							<?php elseif ( $action=="disable_coupon" ): ?>
                                const queryString = window.location.search;
                                const urlParams = new URLSearchParams(queryString);
                                if(urlParams.has('coupon')){
                                    url = location.pathname + location.search.replace(/[\?&]coupon=[^&]+/, '').replace(/^&/, '?')
                                    window.location.replace(url);
                                }
                            <?php elseif ( $action=="redirect_link" ): ?>
								var ele_backend = jQuery('body').hasClass('elementor-editor-active');
								if( ele_backend ) {
									jQuery(this).find('.studiocart-countdown-init').html( '<h1>You can not redirect url from Elementor editor!!</h1>' );
								} else {
									window.location.replace("<?php echo $settings['sc_countdown_expire_redirect_link']['url'] ?>");
									exit;
								}
								
							<?php endif; endforeach;?>
						}
						<?php if( $settings['sc_countdown_restart']=='yes') {
							 
                                $restart_days = $settings['sc_countdown_restart_after_days'];
								$restart_hours = $settings['sc_countdown_restart_after_hours'];
								$restart_minutes = $settings['sc_countdown_restart_after_minutes'];
								$restart_seconds = $settings['sc_countdown_restart_after_seconds'];
						?>
						if ( t.total <= 0 ) {
							var restart_days = '<?php echo $restart_days; ?>';
							var restart_hours = '<?php echo $restart_hours; ?>';
							var restart_minutes = '<?php echo $restart_minutes; ?>';
							var restart_seconds = '<?php echo $restart_seconds; ?>';
							var newdeadline = new Date( endtime.getTime() + ((restart_days*1000*60*60*24)+(restart_hours*60*60*1000)+(restart_minutes*60*1000)+(restart_seconds*1000)));		
							
							var cdate = new Date();					
							if( newdeadline < cdate ){								
								document.cookie = 'setTimer_<?php echo esc_attr($this->get_id()); ?>' + '=; path=<?php echo wp_make_link_relative(get_permalink()); ?>; expires='+ newdeadline.toUTCString();								

								initializeClock('sc-countdown-<?php echo esc_attr($this->get_id()); ?>', endtime);								
							}
						}
						
						<?php } ?>
					}
					
					updateClock();
					
				    var timeinterval = setInterval(updateClock, 1000);
					
				}
				
				<?php if ( $settings['sc_countdown_expire_type']=='due_date' ) { ?>	
				var expdeadline = new Date('<?php echo preg_replace('/-/', '/', $due_date); ?>');
				initializeClock('sc-countdown-<?php echo esc_attr($this->get_id()); ?>', expdeadline);
				
				<?php } else { ?>
				function getCurrentDeadline () {
				  var deadline = document.cookie.match(/(^|;)setTimer_<?php echo esc_attr($this->get_id()); ?>=([^;]+)/);
				  return deadline && new Date(deadline[2]);
				}
				function initDeadline () {
                    <?php if ( $settings['sc_countdown_expire_type']=='evergreen' ) : ?>	
				    var now = new Date();
                    <?php else: 
                        $due_date = date('Y/m/d H:i', strtotime("-1 days"));
                        $product_id = intval($settings['sc_countdown_product']);
                        
                        $args = array(
                            'post_type'  => 'sc_order',
                            'post_status' => 'paid',
                            'posts_per_page' => 1,
                        );
                        
                        if(isset($_GET['email'])){
                            $order_email = sanitize_email($_GET['email']);
                            $args['meta_query'] = array(
                                array(
                                    'key' => '_sc_product_id',
                                    'value' => $product_id,
                                ),
                                array(
                                    'key' => '_sc_email',
                                    'value' => $order_email,
                                ),
                            );
                        } else {
                            $ipaddress = $_SERVER['REMOTE_ADDR'];
                            $args['meta_query'] = array(
                                array(
                                    'key' => '_sc_product_id',
                                    'value' => $product_id,
                                ),
                                array(
                                    'key' => '_sc_ip_address',
                                    'value' => $ipaddress,
                                ),
                            );
                        }

                        $posts = get_posts($args);
                        if (!empty($posts)) {
                            $post_id = $posts[0]->ID;
                            $due_date = get_the_date('Y/m/d H:i', $post_id);
                            // Check if current IP address is associated with this order (if order found by email)
                            if(!isset($ipaddress)) {
                                $ipaddress = $_SERVER['REMOTE_ADDR'];
                                $check_ip = get_posts( array(
                                    'post__in' => array($post_id),
                                    'posts_per_page' => 1,
                                    'meta_key' => '_sc_ip_address',
                                    'meta_value' => $ipaddress,
                                    'fields' => 'ids',
                                ) );

                                // If not found, add to order
                                if ( !count( $check_ip ) ) {
                                    add_post_meta($post_id, '_sc_ip_address', $ipaddress);
                                }
                            }
                        }
                    ?>
                    var now = new Date('<?php echo $due_date; ?>');
                    <?php endif; ?>
					var days = now.setDate(now.getDate() + <?php echo $after_days; ?>);
					var hours = now.setHours(now.getHours() + <?php echo $after_hours; ?>);
					var minutes = now.setMinutes(now.getMinutes() + <?php echo $after_minutes; ?>);
					var seconds = now.setSeconds(now.getSeconds() + <?php echo $after_seconds; ?>);
				    var cdeadline = new Date(seconds);
  
				    document.cookie = 'setTimer_<?php echo esc_attr($this->get_id()); ?>=' + cdeadline + '; path=<?php echo wp_make_link_relative(get_permalink()); ?>; domain=' + window.location.hostname;
				  return cdeadline;
				}
				var expdeadline = getCurrentDeadline() || initDeadline();
				initializeClock('sc-countdown-<?php echo esc_attr($this->get_id()); ?>', expdeadline);			
				<?php } ?>
        </script>
		<?php
	}

    /**
	 * Render countdown output in the editor.
	 
	 
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @access protected
	 */
	protected function content_template() { 
		 
	}	
}
Plugin::instance()->widgets_manager->register( new NCS_Elementor_Countdown_Timer() );