<?php

/**
 * Sanitize anything
 *
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 */

class NCS_Cart_Sanitize {

	/**
	 * The data to be sanitized
	 *
	 * @access 	private
	 * @since 	0.1
	 * @var 	string
	 */
	private $data = '';

	/**
	 * The type of data
	 *
	 * @access 	private
	 * @since 	0.1
	 * @var 	string
	 */
	private $type = '';

	/**
	 * Constructor
	 */
	public function __construct() {

		// Nothing to see here...

	} // __construct()

	/**
	 * Cleans the data
	 *
	 * @access 	public
	 * @since 	0.1
	 *
	 * @uses 	sanitize_email()
	 * @uses 	sanitize_phone()
	 * @uses 	esc_textarea()
	 * @uses 	sanitize_text_field()
	 * @uses 	esc_url()
	 *
	 * @return  mixed         The sanitized data
	 */
	public function clean() {

		$sanitized = '';

		/**
		 * Add additional santization before the default sanitization
		 */
		do_action( 'sc_cart_pre_sanitize', $sanitized );

		switch ( $this->type ) {

			case 'color'				:
			case 'radio'				:
			case 'select'				: $sanitized = $this->sanitize_random( $this->data ); break;

			case 'date'					:
			case 'datetime'				:
			case 'datetime-local'		: $sanitized = sanitize_text_field($this->data); break;
			case 'time'					:
			case 'week'					: $sanitized = strtotime( $this->data ); break;

			case 'price'				: $sanitized = $this->format_price(); break;
            case 'number'				: $sanitized = intval( $this->data ); break;
			case 'range'				: $sanitized = intval( $this->data ); break;

			case 'hidden'				: $sanitized = sanitize_text_field( $this->data ); break;
			case 'month'				:
			case 'text'					: $sanitized = sanitize_text_field( $this->data ); break;

			case 'checkbox'				: $sanitized = ( isset( $this->data ) ? 1 : 0 ); break;
			case 'html' 				: $sanitized = wp_kses_post( $this->data ); break;
			case 'editor' 				: $sanitized = wp_kses_post( $this->data ); break;
			case 'email'				: $sanitized = sanitize_email( $this->data ); break;
			case 'file'					: $sanitized = sanitize_file_name( $this->data ); break;
			case 'tel'					: $sanitized = $this->sanitize_phone( $this->data ); break;
			case 'textarea'				: $sanitized = esc_textarea( $this->data ); break;
			case 'file-upload'			: $sanitized = esc_url( $this->data ); break;
			case 'secure-file-upload'	: $sanitized = esc_url( $this->data ); break;
			case 'url'					: $sanitized = esc_url( $this->data ); break;

		} // switch

		/**
		 * Add additional santization after the default .
		 */
		do_action( 'sc_cart_post_sanitize', $sanitized );

		return $sanitized;

	} // clean()

	private function format_price(){
		$thousand_sep = get_option( '_sc_thousand_separator' );
        $decimal_sep = get_option( '_sc_decimal_separator' );
		
		$price = $this->data;

		if($price){

            if($thousand_sep && strpos($price, $thousand_sep) !== false){
				$price = $this->is_thousand_separator($price,$thousand_sep,$decimal_sep);
            } else if($decimal_sep  != ','){
				$price = $this->is_thousand_separator($price,',',$decimal_sep);
			}

            if($decimal_sep && strpos($price, $decimal_sep) !== false){
                $price = str_replace($decimal_sep, '.', $price);
            }
			
        }

		return $price;
	}

	private function is_thousand_separator($price,$thousand_sep,$decimal_sep){
		
		$parts = explode($thousand_sep, $price);

		if(count($parts) > 1){
			foreach($parts as $k => $v){
				if($decimal_sep && strpos($v, $decimal_sep) !== false){
					$decparts = explode($decimal_sep, $v);
					$parts[$k] = $decparts[0];
				}
			}

			$groupLengths = array_map('strlen', $parts);
			if (max($groupLengths) == 3) {
				if($groupLengths[0] == 3 && $groupLengths[1] != 3){
					wp_redirect($_REQUEST['_wp_http_referer'].'&format_err='.$price);
					die;
				}
				$price = str_replace($thousand_sep, '', $price);
			}else{
				wp_redirect($_REQUEST['_wp_http_referer'].'&format_err='.$price);
				die;
			}
		}

		return $price;
	}

	/**
	 * Checks a date against a format to ensure its validity
	 *
	 * @link 	http://www.php.net/manual/en/function.checkdate.php
	 *
	 * @param  	string 		$date   		The date as collected from the form field
	 * @param  	string 		$format 		The format to check the date against
	 * @return 	string 		A validated, formatted date
	 */
	private function validate_date( $date, $format = 'Y-m-d H:i:s' ) {

		$version = explode( '.', phpversion() );

		if ( ( (int) $version[0] >= 5 && (int) $version[1] >= 2 && (int) $version[2] > 17 ) ) {

			$d = DateTime::createFromFormat( $format, $date );

		} else {

			$d = new DateTime( date( $format, strtotime( $date ) ) );

		}

		return $d && $d->format( $format ) == $date;

	} // validate_date()

	/**
	 * Validates a phone number
	 *
	 * @access 	private
	 * @since	0.1
	 * @link	http://jrtashjian.com/2009/03/code-snippet-validate-a-phone-number/
	 * @param 	string 			$phone				A phone number string
	 * @return	string|bool		$phone|FALSE		Returns the valid phone number, FALSE if not
	 */
	private function sanitize_phone( $phone ) {

		if ( empty( $phone ) ) { return FALSE; }

		if ( preg_match( '/^[+]?([0-9]?)[(|s|-|.]?([0-9]{3})[)|s|-|.]*([0-9]{3})[s|-|.]*([0-9]{4})$/', $phone ) ) {

			return trim( $phone );

		} // $phone validation

		return FALSE;

	} // sanitize_phone()

	/**
	 * Performs general cleaning functions on data
	 *
	 * @param 	mixed 	$input 		Data to be cleaned
	 * @return 	mixed 	$return 	The cleaned data
	 */
	private function sanitize_random( $input ) {

			$one	= trim( $input );
			$two	= stripslashes( $one );
			$return	= htmlspecialchars( $two );

		return $return;

	} // sanitize_random()

	/**
	 * Sets the data class variable
	 *
	 * @param 	mixed 		$data			The data to sanitize
	 */
	public function set_data( $data ) {

		$this->data = $data;

	} // set_data()

	/**
	 * Sets the type class variable
	 *
	 * @param 	string 		$type			The field type for this data
	 */
	public function set_type( $type ) {

		$check = '';

		if ( empty( $type ) ) {

			$check = new WP_Error( 'forgot_type', __( 'Specify the data type to sanitize.', 'ncs-cart' ) );

		}

		if ( is_wp_error( $check ) ) {

			wp_die( $check->get_error_message(), __( 'Forgot data type', 'ncs-cart' ) );

		}

		$this->type = $type;

	} // set_type()

} // class