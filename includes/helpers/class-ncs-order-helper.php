<?php

/**
 * Class for reusable order functions.
 *
 * @link       https://ncstudio.co
 * @since      2.3
 *
 * @package    NCS_Order_Helper
 * @author     N.Creative Studio <info@ncstudio.co>
 */

class NCS_Order_Helper {

    /**
	 * The single instance of the class.
	 *
	 * @var NCS_Order_Helper
	 * @since 2.3.0
	 */

	protected static $_instance = null;

    public $order = null;

    /**
     * NCS_Order_Helper class Instance
     */
    public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

    /**
     * Check if order has bump product
     */
    public function is_bump(){

        $bump_plan = false;
        if(is_array($this->order->order_bumps)) {
            foreach($this->order->order_bumps as $bump) {
                // do order bumps have a subscription?
                if(isset($bump['plan'])) {
                    $bump_plan = true;
                    break;
                }
            }
        }
        return $bump_plan;
    }

    /**
     * Check if tax has been applied
     */
    public function tax_applied(){
        
    }

}
?>