<?php

/**
 * Class for Stripe Service.
 *
 * @link       https://ncstudio.co
 * @since      2.3
 *
 * @package    NCS_Stripe
 * @author     N.Creative Studio <info@ncstudio.co>
 */

class NCS_Stripe{

    /**
	 * The single instance of the class.
	 *
	 * @var NCS_Stripe
	 * @since 2.3.0
	 */

	protected static $_instance = null;
    protected $stripeKeys = array();
	protected $stripe = '';

    public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    public function stripe(){

        $this->stripeKeys['mode'] = get_option( '_sc_stripe_api' );
		$this->stripeKeys['sk'] = get_option( '_sc_stripe_'. $this->stripeKeys['mode'] .'_sk' );
		$this->stripeKeys['pk'] = get_option( '_sc_stripe_'. $this->stripeKeys['mode'] .'_pk' );
		$this->stripeKeys['hook_id'] = get_option( '_sc_stripe_'.$this->stripeKeys['mode'].'_webhook_id' );
		
		foreach($this->stripeKeys as $k=>$v) {
			if(!$v) {
				$this->stripeKeys = false;
				break;
			}
		}

		// Create Stripe Instance
		$this->stripe = new \Stripe\StripeClient($this->stripeKeys['sk']);
        return $this->stripe;
    }

    /**
     * Update payment method to existing subscription
     */

    public function updatePaymentMethod($subscription_id, $payment_method, $customer_id)
    {  
        $this->stripe();
        $response = $this->attachPaymentMethodToCustomer($payment_method, $customer_id);
        if($response->id){

            try{
                //if($all){
                    $result = $this->setDefaultPaymentMethod($customer_id,$payment_method);
                /*}else{
                    $result = $this->stripe->subscriptions->update(
                        $subscription_id,
                        ['default_payment_method' => $payment_method],
                    );
                }*/
                
                return $result;
            
            }catch(\Stripe\Exception\InvalidRequestException $e){
               
                ncs_helper()->logException($e,__LINE__, __FILE__);
            }
        }
            
    }

    /**
     * Attach payment method to customer
     */

    public function attachPaymentMethodToCustomer($payment_method,$customer_id)
    {
        try{
            $method_response = $this->stripe->paymentMethods->attach(
                $payment_method,
                ['customer' => $customer_id]
                );
            
            return $method_response;
        }catch(\Stripe\Exception\InvalidRequestException $e){
            ncs_helper()->logException($e,__LINE__, __FILE__);
        }
    }

    /**
     * Set default Payment Method for all subscriptions
     */

    public function setDefaultPaymentMethod($customer_id,$payment_method){
        try{
            $this->stripe->customers->update(
                $customer_id,
                ['invoice_settings' => ['default_payment_method' => $payment_method]]
              );
        }catch(\Stripe\Exception\InvalidRequestException $e){
            ncs_helper()->logException($e,__LINE__, __FILE__);
        }
    }

    public function getPaymentMethods($customer_id){
        $cards = [];
        try{
            $cards =  $this->stripe->customers->allPaymentMethods(
                $customer_id,
                ['type' => 'card']
            );

            return $cards;

        }catch(\Stripe\Exception\InvalidRequestException $e){
            ncs_helper()->logException($e,__LINE__, __FILE__);
        }

        return $cards;
    }


    /**
     * Function to print data for debugging 
     */
    public function dump($data){
		echo '<pre/>';
		print_r($data);
		exit;
	}

}
?>