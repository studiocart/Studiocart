<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/include
 */

/**
 * The admin-ajax functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * 
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/include
 * @author     N.Creative Studio <info@ncstudio.co>
 */

class NCS_Cart_Tax {

    /**
	 * Format the city.
	 *
	 * @param  string $city Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_city( $city ) {
		return strtoupper( trim( $city ) );
	}

	/**
	 * Format the state.
	 *
	 * @param  string $state Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_state( $state ) {
		$state = strtoupper( $state );
		return ( '*' === $state ) ? '' : $state;
	}

	/**
	 * Format the country.
	 *
	 * @param  string $country Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_country( $country ) {
		$country = strtoupper( $country );
		return ( '*' === $country ) ? '' : $country;
	}

    /**
	 * Format the postcode.
	 *
	 * @param  string $postcode Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_postcode( $postcode ) {
		return sanitize_key( $postcode );
	}

    /**
	 * Format the tax meta.
	 *
	 * @param  string $country Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_meta( $meta ) {
		$meta = (array)$meta;
		return maybe_serialize($meta);
	}

	/**
	 * Format the tax rate name.
	 *
	 * @param  string $name Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate_title( $name ) {
		return $name ? $name : __( 'Tax', 'ncs-cart' );
	}

	/**
	 * Format the rate.
	 *
	 * @param  float $rate Value to format.
	 * @return string
	 */
	private static function format_ncs_tax_rate( $rate ) {
		return number_format( (float) $rate, 2, '.', '' );
	}

	/**
	 * Format the priority.
	 *
	 * @param  string $priority Value to format.
	 * @return int
	 */
	private static function format_ncs_tax_rate_priority( $priority ) {
		return absint( $priority );
	}

    public static function insert_tax_rate( $tax_rate ) {
		global $wpdb,$NCS_Cart_Admin_Tax;
		$tax_rate = self::prepare_tax_rate( $tax_rate );
		$wpdb->insert( $wpdb->prefix . 'ncs_tax_rate', $tax_rate );

		$tax_rate_id = $wpdb->insert_id;
		$NCS_Cart_Admin_Tax->save_stripe_tax_rate($tax_rate,$tax_rate_id);
		do_action( '_sc_tax_rate_added', $tax_rate_id, $tax_rate );

		return $tax_rate_id;
	}

    public static function update_tax_rate( $tax_rate_id, $tax_rate ) {
		global $wpdb,$NCS_Cart_Admin_Tax;

		$tax_rate_id = absint( $tax_rate_id );
		$tax_rate = self::prepare_tax_rate( $tax_rate );
		$wpdb->update(
			$wpdb->prefix . 'ncs_tax_rate',
			$tax_rate,
			array(
				'id' => $tax_rate_id,
			)
		);
		$NCS_Cart_Admin_Tax->save_stripe_tax_rate($tax_rate,$tax_rate_id);
		do_action( '_sc_tax_rate_updated', $tax_rate_id, $tax_rate );
	}

    /**
	 * Prepare and format tax rate for DB insertion.
	 *
	 * @param  array $tax_rate Tax rate to format.
	 * @return array
	 */
	private static function prepare_tax_rate( $tax_rate ) {
		foreach ( $tax_rate as $key => $value ) {
			if ( method_exists( __CLASS__, 'format_ncs_' . $key ) ) {
                $tax_rate[ $key ] = call_user_func( array( __CLASS__, 'format_ncs_' . $key ), $value );
			}
		}
		return $tax_rate;
	}
    
    public static function delete_tax_rate($tax_rate_id){
        global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ncs_tax_rate WHERE id = %d;", $tax_rate_id ) );
		do_action( '_sc_tax_rate_deleted', $tax_rate_id );
    }

    public static function get_tax_rate(){
        global $wpdb;

		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$rates     = $wpdb->get_results(  "SELECT * FROM `{$wpdb->prefix}ncs_tax_rate`","ARRAY_A"  );
		$rates = array_map('self::render_rates',$rates);
        return $rates;

    }

	public static function get_tax_rate_by_id($tax_rate_id){
        global $wpdb;

		$rate    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}ncs_tax_rate` WHERE id = %d;" , $tax_rate_id),"ARRAY_A" );
        $rate['tax_rate_id']=$rate['id'];
		unset($rate['id']);
		return $rate;

    }

	public static function render_rates($rates){
		$rates['tax_rate_id']=$rates['id'];
		unset($rates['id']);
		return $rates;
	}
    public static function get_tax_rate_metabox($save){
        $tax_rate = get_option( '_sc_tax_rates', array() );
        if ( ! empty( $tax_rate ) ) {
            $selection = array();
            $selection[''] = '~~Select Tax Rate~~';
            $fields = array('_sc_tax_rate_title','_sc_tax_rate_slug','_sc_tax_rate');
            $count = count($tax_rate['_sc_tax_rate_title']);
            for($i=0;$i<$count;$i++){
                $inner_val = array();
                foreach($fields as $field):
                    $inner_val[$field] = $tax_rate[$field][$i];
                endforeach;
                $new_val[$i] = $inner_val;
            }
            $tax_rate = $new_val;
            $tax_rate = array_map('remove_repeater_blank', $tax_rate);
            $tax_rate = array_filter( $tax_rate);
            foreach($tax_rate as $tax):
                $selection[$tax['_sc_tax_rate_slug']] = $tax['_sc_tax_rate_title'].'('.$tax['_sc_tax_rate'].'%)';
            endforeach;
        }
        return $selection;
    }

    public static function get_custom_tax_rate(){
        $tax_rate = get_option( '_sc_tax_rates', array() );
        if ( ! empty( $tax_rate ) ) {
            $custom_tax_rate = array();
            $fields = array('_sc_tax_rate_title','_sc_tax_rate_slug','_sc_tax_rate','_sc_stripe_tax_rate');
            $count = count($tax_rate['_sc_tax_rate_title']);
            for($i=0;$i<$count;$i++){
                $inner_val = array();
                foreach($fields as $field):
                    $inner_val[$field] = $tax_rate[$field][$i];
                endforeach;
                $new_val[$i] = $inner_val;
            }
            $tax_rate = $new_val;
            $tax_rate = array_map('remove_repeater_blank', $tax_rate);
            $tax_rate = array_filter( $tax_rate);
            foreach($tax_rate as $tax):
                $custom_tax_rate[$tax['_sc_tax_rate_slug']] = array('tax_rate_title'=>$tax['_sc_tax_rate_title'],'tax_rate'=>$tax['_sc_tax_rate'],'stripe_tax_rate'=>$tax['_sc_stripe_tax_rate']);
            endforeach;
        }
        return $custom_tax_rate;
    }

	public static function ncs_get_tax_meta($tax_rate_id,$key)
	{
		global $wpdb;
		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		$tax_rate_meta    = $wpdb->get_var(  $wpdb->prepare("SELECT tax_rate_meta FROM `{$wpdb->prefix}ncs_tax_rate` WHERE id = %d;", $tax_rate_id ) );
		if(!empty($tax_rate_meta)){
			$tax_rate_meta = maybe_unserialize($tax_rate_meta);
			if(isset($tax_rate_meta[$key])){
				return maybe_unserialize($tax_rate_meta[$key]);
			}
		}
		return false;
	}

	public static function ncs_update_tax_meta($tax_rate_id,$key,$value)
	{
		global $wpdb;
		// Get all the rates and locations. Snagging all at once should significantly cut down on the number of queries.
		
		$tax_rate_meta    = $wpdb->get_var(  $wpdb->prepare("SELECT tax_rate_meta FROM `{$wpdb->prefix}ncs_tax_rate` WHERE id = %d;", $tax_rate_id ) );
		if(!empty($tax_rate_meta)){
			$tax_rate_meta = maybe_unserialize($tax_rate_meta);
			if(isset($tax_rate_meta[$key])){
				unset($tax_rate_meta[$key]);
			}
		} else {
			$tax_rate_meta = array();
		}
		
		$tax_rate_meta[$key] = maybe_serialize($value);
		$tax_rate_meta = maybe_serialize($tax_rate_meta);
		$tax_rate_meta    = $wpdb->update(  
								$wpdb->prefix.'ncs_tax_rate', 
								array('tax_rate_meta'=> $tax_rate_meta),
								array('id' =>$tax_rate_id)
							);
		return true;
	}

	public static function get_matched_tax_rates( $country, $state, $postcode, $city ) {
		global $wpdb;

		$condition   = array();
		$condition[] = $wpdb->prepare( "tax_rate_country IN ( %s, '' )", strtoupper( $country ) );
		$condition[] = $wpdb->prepare( "tax_rate_state IN ( %s, '' )", strtoupper( $state ) );
		$condition[] = $wpdb->prepare( "tax_rate_postcode IN ( %s, '' )", $postcode );
		$condition[] = $wpdb->prepare( "tax_rate_city IN ( %s, '' )",strtoupper( $city) );

		$where = implode(' AND ',$condition);
		$tax_rate =  $wpdb->get_row("SELECT *,tax_rate as rate,tax_rate_title as title FROM `{$wpdb->prefix}ncs_tax_rate` WHERE $where ORDER BY `tax_rate_priority` DESC " );
		return $tax_rate;
	}

	public static function get_global_tax_rates(){
		$tax_rate = get_option( '_sc_tax_rates', array() );
        if ( ! empty( $tax_rate ) ) {
            $fields = array('_sc_tax_rate_title','_sc_tax_rate_slug','_sc_tax_rate','_sc_stripe_tax_rate','_sc_tax_global');
            $count = count($tax_rate['_sc_tax_rate_slug']);
            for($i=0;$i<$count;$i++){
                if($tax_rate['_sc_tax_global'][$i]){
                    $inner_val = array();
                    foreach($fields as $field):
                        $field_title = str_replace('_sc_','',$field);
                        $inner_val[$field_title] = $tax_rate[$field][$i]??'';
                    endforeach;
                }
            }
            return $inner_val;
        }
	}

	public static function get_order_tax_data($order){
		$tax_rate = array();
		if (!isset($order->is_taxable)) {
            $order->is_taxable = 'tax';
        }
        if($order->is_taxable=='tax'){
			$vat_data = self::get_order_vat_data($order);
			if(!empty((array)$vat_data)){
				$order->tax_type = 'vat';
				return $vat_data;
			}
			$order->tax_type = 'tax';
			$country = $order->country??get_option('_sc_vat_merchant_state',"");
			$state = $order->state??'';
			$postcode = $order->zip??'';
			$city = $order->city??'';
			$tax = self::get_matched_tax_rates( $country, $state, $postcode, $city );
			if(!empty($tax)){
				$tax_rate = array('rate' =>	$tax->rate,
								'title' =>	$tax->title,
								'stripe_tax_rate' =>	self::ncs_get_tax_meta($tax->id,'stripe_tax_id'),
				);
			} elseif(get_option('_sc_vat_enable',false) && empty($order->country)){ 
				$merchant_country = get_option('_sc_vat_merchant_state');
				$vat_data = NCS_Cart_Tax::get_country_vat_rates($merchant_country, $postcode);
				$vat_data->redeem_vat = true;
				$order->tax_type = 'vat';
				return $vat_data;
			}
		}
		return (object)$tax_rate;
	}

	public static function get_order_vat_data($order) {
		$vat_data = array();
		$country = $order->country??get_option('_sc_vat_merchant_state',"");
		$zip = $order->zip ?? '';
		
		if($order->is_taxable=='tax' && get_option('_sc_vat_enable',false) && !empty($country) ){
			$redeem_vat = false;
			if(!empty($order->vat_number)){
				if(!get_option('_sc_vat_disable_vies_database_lookup',false)){
					
                    $vat_number = $order->vat_number;
                    
					// Strip VAT Number of country code, spaces and periods
                    if (ctype_alpha(substr($vat_number, 0, 2))) $vat_number = substr($vat_number, 2);
                    $vat_number = str_replace(" ", "", $vat_number);
                    $vat_number = str_replace(".", "", $vat_number);
                    if($country == "GB"){
						$curl = curl_init();
	
						curl_setopt_array($curl, array(
							CURLOPT_URL => 'https://api.service.hmrc.gov.uk/organisations/vat/check-vat-number/lookup/'.$vat_number,
							CURLOPT_RETURNTRANSFER => true,
							CURLOPT_ENCODING => '',
							CURLOPT_MAXREDIRS => 10,
							CURLOPT_TIMEOUT => 0,
							CURLOPT_FOLLOWLOCATION => true,
							CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
							CURLOPT_CUSTOMREQUEST => 'GET',
							CURLOPT_HTTPHEADER => array(
								'Accept: application/vnd.hmrc.1.0+json'
							),
						));
	
						$response = curl_exec($curl);
						if($response == false){
							$is_valid = false;
						} else {
							$vat_data = json_decode($response);
							if($vat_data->target){
								$is_valid = true;
							} else {
								$is_valid = false;
							}
						}
						curl_close($curl);
					} else {
						$client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
						try{
							$vat_data = $client->checkVat(array(
								'countryCode' => $country,
								'vatNumber' => $vat_number
							));
							$is_valid = $vat_data->valid;
						} catch(Exception $e) {
							$is_valid = false;
						}
					}
				}
				if(!get_option('_sc_vat_all_eu_businesses',false) 
					&& $country!=get_option('_sc_vat_merchant_state',false)){
						if($is_valid){
							$redeem_vat = true;
						}
				}
			} 
            $vat_data = self::get_country_vat_rates($country, $zip);
			if($vat_data){
                $vat_data->redeem_vat = $redeem_vat;
				$vat_data->title = __($vat_data->title,"ncs-cart");
			}
		}
		return $vat_data;
	}


	public static function excluded_location($country, $zip) {
		// Canary Islands
		if($country == 'ES' && $zip) {
			$zip = (string) $zip;
			switch($zip){
				case str_starts_with($zip, '35'): 
				case str_starts_with($zip, '38'): 
				case str_starts_with($zip, '51'): 
				case str_starts_with($zip, '52'): 
					return true;
				default:
					return false;
			}
		}
		return false;
	}

	public static function get_country_vat_rates($country, $zip){
		global $NCS_Cart_Admin_Tax;
		if(!get_option('_sc_vat_enable',false) || self::excluded_location($country, $zip)){
			return array();
		}
		$vat_rates = get_option('_sc_vat_rates',array());
		$countries = sc_countries_list();
		
		if(isset($vat_rates['vat_'.$country]) && isset($vat_rates['vat_'.$country]->rate) && $vat_rates['vat_'.$country]->last_update>strtotime('-60 days')){
			if(empty($vat_rates['vat_'.$country]->stripe_tax_rate)){
				$vat_rates['vat_'.$country]->stripe_tax_rate = $NCS_Cart_Admin_Tax->create_stripe_vat($vat_rates['vat_'.$country]->rate,$countries,$country);
			} else {
				$vat_rates['vat_'.$country]->stripe_tax_rate = $NCS_Cart_Admin_Tax->check_create_stripe_vat($vat_rates['vat_'.$country]->stripe_tax_rate,$vat_rates['vat_'.$country]->rate,$countries,$country);
			}
			update_option( '_sc_vat_rates', $vat_rates );
			return $vat_rates['vat_'.$country];
		} else {
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_URL => 'https://api.vatstack.com/v1/rates?country_code='.$country.'&limit=1',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HTTPHEADER => array(
					'X-API-KEY: pk_live_840d17419f7fdfc5d8b0a83f1b5f8d65'
				),
			));

			$response = curl_exec($curl);
			curl_close($curl);
			$vat_api_data = json_decode($response);
			if(!isset($vat_api_data->rates) || count($vat_api_data->rates)==0){
				return array();
			}else{
				
				foreach($vat_api_data->rates as $key => $value){
					if($value->standard_rate){
						$vat_rate = $value->standard_rate;
						break;
					}
				}

				if(!empty($vat_rate)):
					if($vat_rate == $vat_rates['vat_'.$country]->vat_rate){
						$vat_rates['vat_'.$country]->last_update = time();
						if(empty($vat_rates['vat_'.$country]->stripe_tax_rate)){
							$vat_rates['vat_'.$country]->stripe_tax_rate = $NCS_Cart_Admin_Tax->create_stripe_vat($vat_rate,$countries,$country);
						} else {
							$vat_rates['vat_'.$country]->stripe_tax_rate = $NCS_Cart_Admin_Tax->check_create_stripe_vat($vat_rates['vat_'.$country]->stripe_tax_rate,$vat_rate,$countries,$country);
						}
						update_option( '_sc_vat_rates', $vat_rates );
						return $vat_rates['vat_'.$country];
					} else {
						$vat_data = array(	'title'=>__('VAT',"ncs-cart"), 
											'rate'=>$vat_rate,
											'vat_country'=>array('name'=>$countries[$country],'code'=>$country),
											'last_update'=>time(),
									);
						$vat_data['stripe_tax_rate']=$NCS_Cart_Admin_Tax->create_stripe_vat($vat_rate,$countries,$country);
						$vat_rates['vat_'.$country] = (object)$vat_data;
						update_option( '_sc_vat_rates', $vat_rates );
						return (object)$vat_data;
					}
				endif;
			}
		}
		
	}
}