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

 * The admin-specific functionality of the plugin.

 *

 * Defines the plugin name, version, and two examples hooks for how to

 * enqueue the admin-specific stylesheet and JavaScript.

 *

 * @package    NCS_Cart

 * @subpackage NCS_Cart/admin

 * @author     N.Creative Studio <info@ncstudio.co>

 */

class NCS_Cart_Admin_Tax {
	/**
	 * The prefix of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $stripe    The current version of this plugin.
	 */
	private $stripe;
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct() {
        global $sc_stripe;
        if ( function_exists( 'sc_setup_stripe' ) && !$sc_stripe ) {
            sc_setup_stripe();
        }
        if($sc_stripe){
            require_once( plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php');
            $this->stripe = new \Stripe\StripeClient($sc_stripe['sk']);
        } else {
            $this->stripe = false;
        }
	}


	public function save_stripe_tax_rate($objects,$tax_id=0){
		
        if($this->stripe){
            if(!empty($tax_id)){
                $objects = NCS_Cart_Tax::get_tax_rate_by_id($tax_id);
            }
            if($tax_rate_id = $this->get_stripe_tax_rate($objects,$tax_id)){
                $stripe_tax_rate_id = $this->update_stripe_tax_rate($objects,$tax_rate_id);
            } else {                
                $stripe_tax_rate_id = $this->create_stripe_tax_rate($objects,$tax_id);
            }
            return $stripe_tax_rate_id;
        }
	}

    

    public function get_stripe_tax_rate($objects,$tax_id){
        if ( !$this->stripe ) {
            return false;
        } else{
            if(empty($tax_id)){
                $tax_rate_id = $objects['stripe_tax_rate']??'';
            } else {
                $tax_rate_id = NCS_Cart_Tax::ncs_get_tax_meta($tax_id,'stripe_tax_id');
            }
            try {
                if(!empty($tax_rate_id)){
                    $this->stripe->taxRates->retrieve($tax_rate_id);
                    return $tax_rate_id;
                } else {
                    return false;
                }
                
            } catch(Exception $e) {
                return false;
            }
        }
    }

    

    private function create_stripe_tax_rate($objects,$tax_id){  
        global $wpdb, $sc_currency;
        $tax_type = get_option( '_sc_tax_type', 'inclusive_tax' );
        $inclusive = ($tax_type=='inclusive_tax')?true:false;
        $tax_title = !empty($objects['tax_rate_title'])?$objects['tax_rate_title']:$objects['tax_rate']."%";

        try {
            $tax_object = [
                'display_name'  =>  $tax_title,
                'description'   =>  $tax_title,
                'percentage'    =>  $objects['tax_rate'],
                'inclusive'     =>  $inclusive,   
                'metadata'      =>  array('sc_tax_id'=>$tax_id)
            ];
            if(!empty($objects['tax_rate_country'])){
                $tax_object['country'] = $objects['tax_rate_country'];
            }
            if(!empty($objects['tax_rate_state'])){
                $tax_object['state'] = $objects['tax_rate_state'];
            }
            $tax_rate = $this->stripe->taxRates->create($tax_object);
            if(!empty($tax_id)){
                NCS_Cart_Tax::ncs_update_tax_meta($tax_id,'stripe_tax_id',$tax_rate->id);
            }
            return $tax_rate->id;
		}
		catch(Exception $e) {
            wp_send_json(array('message'=>$e->getMessage()),401);
            exit;
		}
	}

    private function update_stripe_tax_rate($objects,$tax_rate_id){  
        global $wpdb, $sc_currency;
        try {
            $tax_type = get_option( '_sc_tax_type', 'inclusive_tax' );
            $inclusive = ($tax_type=='inclusive_tax')?true:false;
            $tax_title = !empty($objects['tax_rate_title'])?$objects['tax_rate_title']:$objects['tax_rate']."%";
            $tax_rate = $this->stripe->taxRates->retrieve($tax_rate_id);
            $tax_rate_country = $objects['tax_rate_country']??'';
            $tax_rate_state = $objects['tax_rate_state']??'';
            if( $tax_rate->inclusive==$inclusive 
                && $tax_rate->percentage==$objects['tax_rate']
                && $tax_rate->country==$tax_rate_country
                && $tax_rate->state==$tax_rate_state
            ){
                $tax_object = [
                    'display_name'  =>  $tax_title,
                    'description'   =>  $tax_title,
                ];
                $this->stripe->taxRates->update(
                    $tax_rate_id,
                    $tax_object
                );
                return $tax_rate_id;
            } else {
                return $this->create_stripe_tax_rate($objects,$objects['tax_rate_id']??0);
            }
		} 

		catch(Exception $e) {
            wp_send_json(array('message'=>$e->getMessage()),401);
            exit;
		}	

	}

    public function create_stripe_vat($vat_rate,$countries,$country){
        if($this->stripe){
            $tax_rate = $this->stripe->taxRates->create([
                'display_name' => 'VAT_'.$country,
                'description' => 'VAT '.$countries[$country],
                'jurisdiction' => $country,
                'percentage' => $vat_rate,
                'inclusive' => false,
            ]);
            return $tax_rate->id;
        }
        return "";
    }

    public function check_create_stripe_vat($stripe_tax_rate,$vat_rate,$countries,$country){
        if($this->stripe){
            try{
                $tax_exsist = $this->stripe->taxRates->retrieve($stripe_tax_rate);
                if(!$tax_exsist->id):
                    return $this->create_stripe_vat($vat_rate,$countries,$country);
                else:
                    return $tax_exsist->id;
                endif;
            } catch(Exception $e) {
                return $this->create_stripe_vat($vat_rate,$countries,$country);
            }
        }
    }

}
global $NCS_Cart_Admin_Tax;
$NCS_Cart_Admin_Tax = new NCS_Cart_Admin_Tax();