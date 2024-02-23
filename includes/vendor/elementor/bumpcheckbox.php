<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;

/**
 * Elementor image box widget.
 *
 * Elementor widget that displays an image, a headline and a text.
 *
 * @since 1.0.0
 */
class SC_Bump_Checkbox extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve image box widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'bump-checkbox';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve image box widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Bump Checkbox', 'elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve image box widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-image-box';
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 2.1.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'image', 'photo', 'visual', 'box' ];
	}

	/**
	 * Register image box widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 3.1.0
	 * @access protected
	 */
	protected function register_controls() {

        $this->start_controls_section(
			'section_image',
			[
				'label' => esc_html__( 'Bump Checkbox', 'elementor' ),
			]
		);	

		$this->add_control(
			'position',
			[
				'label' => esc_html__( 'Checkbox Position', 'elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'default' => 'initial',
				'options' => [
					'initial' => [
						'title' => esc_html__( 'Left', 'elementor' ),
						'icon' => 'eicon-h-align-left',
					],
					'column' => [
						'title' => esc_html__( 'Top', 'elementor' ),
						'icon' => 'eicon-v-align-top',
					],
					'row-reverse' => [
						'title' => esc_html__( 'Right', 'elementor' ),
						'icon' => 'eicon-h-align-right',
					],
				],
				'prefix_class' => 'elementor-position-',
				'toggle' => false,
				'selectors' => [
                    '{{WRAPPER}} .sc-elementor-cb-wrap' => 'display: flex; flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'content_vertical_alignment',
			[
				'label' => esc_html__( 'Vertical Alignment', 'elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Top', 'elementor' ),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'elementor' ),
						'icon' => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => esc_html__( 'Bottom', 'elementor' ),
						'icon' => 'eicon-v-align-bottom',
					],
				],
				'default' => 'center',
				'toggle' => false,
				'prefix_class' => 'elementor-vertical-align-',
				'condition' => [
					'position!' => 'column',
				],
                'selectors' => [
                    '{{WRAPPER}} .sc-elementor-cb-wrap' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'text_align',
			[
				'label' => esc_html__( 'Alignment', 'elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'elementor' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementor' ),
						'icon' => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'elementor' ),
						'icon' => 'eicon-text-align-right',
					],
					'space-between' => [
						'title' => esc_html__( 'Justified', 'elementor' ),
						'icon' => 'eicon-text-align-justify',
					],
				],
				'condition' => [
					'position!' => 'column',
				],
				'selectors' => [
					'{{WRAPPER}} .sc-elementor-cb-wrap' => 'justify-content: {{VALUE}};',
				],
			]
		);

        $this->add_responsive_control(
			'column_text_align',
			[
				'label' => esc_html__( 'Alignment', 'elementor' ),
				'type' => Controls_Manager::CHOOSE,
				'options' => [
					'start' => [
						'title' => esc_html__( 'Left', 'elementor' ),
						'icon' => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'elementor' ),
						'icon' => 'eicon-text-align-center',
					],
					'end' => [
						'title' => esc_html__( 'Right', 'elementor' ),
						'icon' => 'eicon-text-align-right',
					],
				],
				'condition' => [
					'position' => 'column',
				],
				'selectors' => [
					'{{WRAPPER}} .sc-elementor-cb-wrap' => 'align-items: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'view',
			[
				'label' => esc_html__( 'View', 'elementor' ),
				'type' => Controls_Manager::HIDDEN,
				'default' => 'traditional',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_checkbox',
			[
				'label' => esc_html__( 'Checkbox', 'elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_space',
			[
				'label' => esc_html__( 'Spacing', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'size' => 10,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors' => [
					'{{WRAPPER}}.elementor-position-row-reverse .item-name' => 'margin-left: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.elementor-position-initial .item-name' => 'margin-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}}.elementor-position-column .item-name' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				],
			]
		);

        $this->add_responsive_control(
			'cb_offset',
			[
				'label' => esc_html__( 'Adjust Vertical Position', 'elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem', 'vw', 'custom' ],
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [
						'min' => -15,
						'max' => 15,
					],
				],
				'condition' => [
					'position!' => 'column',
				],
				'selectors' => [
					'{{WRAPPER}} .item-name' => 'postition: relative; top: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_content',
			[
				'label' => esc_html__( 'Label', 'elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label' => esc_html__( 'Color', 'elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .sc-elementor-cb-wrap .sc-elementor-cb-content' => 'color: {{VALUE}};',
				],
				'global' => [
					'default' => Global_Colors::COLOR_PRIMARY,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'title_typography',
				'selector' => '{{WRAPPER}} .sc-elementor-cb-wrap .sc-elementor-cb-content',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
			]
		);

		$this->add_group_control(
			Group_Control_Text_Stroke::get_type(),
			[
				'name' => 'title_stroke',
				'selector' => '{{WRAPPER}} .sc-elementor-cb-wrap .sc-elementor-cb-content',
			]
		);

		$this->add_group_control(
			Group_Control_Text_Shadow::get_type(),
			[
				'name' => 'title_shadow',
				'selector' => '{{WRAPPER}} .sc-elementor-cb-wrap .sc-elementor-cb-content',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render image box widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
        global $sc_bump;

		$settings = $this->get_settings_for_display();

		$html = '<label class="sc-elementor-cb-wrap">';

        if(!is_countable($sc_bump)) { $html = '<div class="studiocart" style="padding:0"><div id="sc-payment-form">'.$html; }

			$html .= (is_countable($sc_bump)) ? '<input 
                    type="checkbox" 
                    id="'. $sc_bump['cb_id'] .'" 
                    name="sc-orderbump['.  $sc_bump['key'] .']" 
                    class="'.  $sc_bump['class'] .'" 
                    value="'.  $sc_bump['bump_id'] .'"
                /> ' : '<input type="checkbox" />';

			$html .= '<span class="item-name" style="height: 17px; padding-left: 17px;"></span>';

            //$this->add_render_attribute( 'title_text', 'class', 'elementor-image-box-title' );

            $title_html = $sc_bump['cta'] ?? 'This is a call to action!';

            $html .= sprintf( '<%1$s class="sc-elementor-cb-content">%2$s</%1$s>', Utils::validate_html_tag( 'div' ), $title_html );

		$html .= '</label>';

        if(!is_countable($sc_bump)) { $html .= '</div></div>'; }

		Utils::print_unescaped_internal_string( $html );
	}

	/**
	 * Render image box widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 2.9.0
	 * @access protected
	 */
	protected function content_template() {
		?>
		<#
		var html = '<div class="studiocart" style="padding:0"><div id="#sc-payment-form"><label class="sc-elementor-cb-wrap">';

			html += '<input type="checkbox" />';
            
			html += '<span class="item-name" style="height: 17px"></span>';

            var title_html = 'This is a call to action!',
                titleSizeTag = 'div';

            html += '<' + titleSizeTag  + ' class="sc-elementor-cb-content">' + title_html + '</' + titleSizeTag  + '>';

		html += '</label></div></div>';

		print( html );
		#>
		<?php
	}
}

\Elementor\Plugin::instance()->widgets_manager->register( new SC_Bump_Checkbox() );