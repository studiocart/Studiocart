<?php

class STOF_StudiocartOrderForm extends DiviExtension {

	/**
	 * The gettext domain for the extension's translations.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $gettext_domain = 'stof-studiocart-order-form';

	/**
	 * The extension's WP Plugin name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $name = 'studiocart-order-form';

	/**
	 * The extension's version
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * STOF_StudiocartOrderForm constructor.
	 *
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct( $name = 'studiocart-order-form', $args = array() ) {
		$this->plugin_dir     = plugin_dir_path( __FILE__ );
		$this->plugin_dir_url = plugin_dir_url( $this->plugin_dir );

		parent::__construct( $name, $args );
	}
	
	
	/**
	 * Enqueues non-minified, hot reloaded javascript bundles.
	 *
	 */
	protected function _enqueue_debug_bundles() {
		// Frontend Bundle
		$site_url       = wp_parse_url( get_site_url() );
		$hot_bundle_url = $this->plugin_dir_url . 'scripts/frontend-bundle.min.js';
		$hot_bundle_style_fr = $this->plugin_dir_url . 'styles/style.min.css';
		wp_enqueue_style( "{$this->name}-frontend-bundle", $hot_bundle_style_fr );
		
		wp_enqueue_script( "{$this->name}-frontend-bundle", $hot_bundle_url, '', $this->version, true );

		if ( et_core_is_fb_enabled() ) {
			$hot_bundle_style = $this->plugin_dir_url . 'styles/backend-style.min.css';
			wp_enqueue_style( "{$this->name}-backend-styles", $hot_bundle_style );
			// Builder Bundle
			$hot_bundle_url = $this->plugin_dir_url . 'scripts/builder-bundle.min.js';
			wp_enqueue_script( "{$this->name}-builder-bundle", $hot_bundle_url, '', $this->version, true );
		}
			
	}
}

new STOF_StudiocartOrderForm;
