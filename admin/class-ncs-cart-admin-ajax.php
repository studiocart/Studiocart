<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 */

/**
 * The admin-ajax functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * 
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/admin
 * @author     N.Creative Studio <info@ncstudio.co>
 */
class NCS_Cart_Admin_Ajax {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	
	/**
	 * The Nice Name of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_title    The Nice Name of this plugin.
	 */
	private $plugin_title;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_title, $version ) {

		$this->plugin_name = $plugin_name;
		$this->plugin_title = $plugin_title;
		$this->version = $version;
        
	}

	public function __call($name, $arguments){
		return $response = array(
			'response'=>array('message'=>'Invalid Method'),
			'response_code'=>'401'
		);
	}

	public function ncs_ajax_action(){
		$response_code = 200;
		if(!isset( $_POST['ncs_ajax_nonce'] )){
			$response_code = 400;
			$response = array('message'=>'Missing Fields');
		}
		if ( ! wp_verify_nonce( $_POST['ncs_ajax_nonce'] , $this->plugin_name.'_admin_ajax' ) ) { 
			$response = array('message'=>'Invalid Request');
			$response_code = 401;
		}

		if(!isset($_POST['ncs_action'])){
			$response = array('message'=>'Missing Fields');
			$response_code = 400;
		}
		$method = 'ncs_'.$_POST['ncs_action'];
		$data = $this->$method($_POST);
		extract($data);
		wp_send_json($response,$response_code);
	}

	public function ncs_save_table_tax($params){
		if ( ! isset( $params['ncs_tax_nonce'] ) ) {
			return array(
				'response'=>array('message'=>'Missing Fields'),
				'response_code'=>'400'
			);
		}
		if ( ! wp_verify_nonce( wp_unslash( $params['ncs_tax_nonce'] ), 'sc-tax-nonce' ) ) { 
			return array(
				'response'=>array('message'=>'Invalid Request'),
				'response_code'=>'401'
			);
		}

		if(!empty($params['changes'])){
			$changes = wp_unslash( $params['changes'] );

			foreach ( $changes as $tax_rate_id => $data ) {
				if ( isset( $data['deleted'] ) ) {
					if ( isset( $data['newRow'] ) ) {
						// So the user added and deleted a new row.
						// That's fine, it's not in the database anyways. NEXT!
						continue;
					}
					NCS_Cart_Tax::delete_tax_rate( $tax_rate_id );
				}

				$tax_rate = array_intersect_key(
					$data,
					array(
						'tax_rate_country'  => 1,
						'tax_rate_state'    => 1,
						'tax_rate'          => 1,
						'tax_rate_title'     => 1,
						'tax_rate_priority' => 1,
						'tax_rate_postcode' => 1,
						'tax_rate_city' => 1,
						'tax_rate_meta' => 1,
					)
				);

				if ( isset( $tax_rate['tax_rate'] ) ) {
					$tax_rate['tax_rate'] =  $tax_rate['tax_rate'] ;
				}

				if ( isset( $data['newRow'] ) ) {
					NCS_Cart_Tax::insert_tax_rate( $tax_rate );
				} elseif ( ! empty( $tax_rate ) ) {
					NCS_Cart_Tax::update_tax_rate( $tax_rate_id, $tax_rate );
				}
			}
		} else {
			$rates = NCS_Cart_Tax::get_tax_rate();
			foreach($rates as $rate){
				$tax_rate = array_intersect_key(
					$rate,
					array(
						'tax_rate_country'  => 1,
						'tax_rate_state'    => 1,
						'tax_rate'          => 1,
						'tax_rate_title'     => 1,
						'tax_rate_priority' => 1,
						'tax_rate_postcode' => 1,
						'tax_rate_city' => 1,
						'tax_rate_meta' => 1,
					)
				);
				NCS_Cart_Tax::update_tax_rate( $rate['tax_rate_id'], $tax_rate );
			}
		}
		return array(
			'response'=>array('rates'=>NCS_Cart_Tax::get_tax_rate())
		);
	}
}