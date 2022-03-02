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
class NCS_Cart_Product_Admin
{
    /**
     * The prefix of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $stripe    The current version of this plugin.
     */
    private  $stripe ;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct()
    {
    }
    
    /**
     * Load the required dependencies for the Admin facing functionality.
     *
     * Include the following files that make up the plugin:
     *
     * - NC_Cart_Admin_Settings. Registers the admin settings and page.
     *
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-ncs-cart-settings.php';
    }
    
    public function save_stripe_objects( $post_id, $objects )
    {
        global  $wpdb, $sc_currency, $sc_stripe ;
        
        if ( $sc_stripe ) {
            require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
            $this->stripe = new \Stripe\StripeClient( $sc_stripe['sk'] );
            $stripe = $this->stripe;
        } else {
            $this->stripe = false;
        }
        
        $post_title = get_the_title( $post_id );
        $stripe_product = $this->get_stripe_product( $post_id );
        foreach ( $objects['_sc_pay_options'] as $key => $option ) {
            $normal_price = array(
                'name'       => $post_title . ' - ' . $option['option_name'],
                'id'         => $option['option_id'],
                'price'      => $option['price'],
                'amount'     => (double) $option['price'],
                'interval'   => $option['interval'],
                'plan_id'    => $option['stripe_plan_id'],
                'frequency'  => $option['frequency'],
                'field_name' => 'stripe_plan_id',
            );
            $sale_price = false;
            if ( !empty($option['sale_option_name']) ) {
                $sale_price = array(
                    'name'       => $post_title . ' - ' . $option['sale_option_name'],
                    'id'         => $option['option_id'] . '_sale',
                    'price'      => $option['sale_price'],
                    'amount'     => (double) $option['sale_price'],
                    'interval'   => $option['sale_interval'],
                    'plan_id'    => $option['sale_stripe_plan_id'],
                    'frequency'  => $option['sale_frequency'],
                    'field_name' => 'sale_stripe_plan_id',
                );
            }
            $plans = array( $normal_price, $sale_price );
            foreach ( $plans as $plan ) {
                
                if ( $plan ) {
                    $_stripe_id = $plan['plan_id'];
                    $plan['trial_days'] = ( isset( $option['trial_days'] ) ? $option['trial_days'] : NULL );
                    // Create Stripe Plan
                    
                    if ( $option['product_type'] == "recurring" && $this->stripe && $stripe_product !== false ) {
                        try {
                            $retrieve_plan = $stripe->prices->retrieve( $_stripe_id );
                            $plan_price_non_decimal = (string) get_sc_price( $plan['amount'], $sc_currency );
                            
                            if ( $retrieve_plan->unit_amount != $plan_price_non_decimal || $retrieve_plan->recurring->interval != $plan['interval'] || $retrieve_plan->recurring->interval_count != $plan['frequency'] || $retrieve_plan->recurring->trial_period_days != $plan['trial_days'] || $retrieve_plan->metadata->sc_product_id != $post_id || $retrieve_plan->product != $stripe_product->id ) {
                                $plan_id = $this->create_plan( $plan, $post_id, $stripe_product );
                                if ( $plan_id ) {
                                    $objects['_sc_pay_options'][$key][$plan['field_name']] = $plan_id;
                                }
                            } else {
                                $objects['_sc_pay_options'][$key][$plan['field_name']] = $_stripe_id;
                            }
                        
                        } catch ( Exception $e ) {
                            $plan_id = $this->create_plan( $plan, $post_id, $stripe_product->id );
                            if ( $plan_id ) {
                                $objects['_sc_pay_options'][$key][$plan['field_name']] = $plan_id;
                            }
                        }
                    } else {
                        $objects['_sc_pay_options'][$key][$plan['field_name']] = $plan['id'];
                    }
                
                }
            
            }
        }
        update_post_meta( $post_id, '_sc_pay_options', $objects['_sc_pay_options'] );
    }
    
    public function get_stripe_product( $post_id )
    {
        $stripe = $this->stripe;
        
        if ( !$this->stripe ) {
            return false;
        } else {
            if ( $pid = get_post_meta( $post_id, '_sc_stripe_prod_id', true ) ) {
                try {
                    $product = $stripe->products->retrieve( $pid );
                    
                    if ( !isset( $product->metadata->sc_product_id ) ) {
                        $stripe->products->update( $product->id, [
                            'metadata' => [
                            'sc_product_id' => $post_id,
                            'origin'        => get_site_url(),
                        ],
                        ] );
                    } else {
                        if ( $product->metadata->sc_product_id != $post_id ) {
                            return $this->create_stripe_product( $post_id );
                        }
                    }
                    
                    if ( $product->name != sc_get_public_product_name( $post_id ) ) {
                        $stripe->products->update( $product->id, [
                            'name'        => sc_get_public_product_name( $post_id ),
                            'description' => sc_get_public_product_name( $post_id ),
                        ] );
                    }
                    return $product;
                } catch ( Exception $e ) {
                    return $this->create_stripe_product( $post_id );
                }
            }
        }
        
        return $this->create_stripe_product( $post_id );
    }
    
    public function create_stripe_product( $post_id )
    {
        $stripe = $this->stripe;
        try {
            $product = $stripe->products->create( [
                'name'        => sc_get_public_product_name( $post_id ),
                'description' => sc_get_public_product_name( $post_id ),
                'metadata'    => [
                'sc_product_id' => $post_id,
                'origin'        => get_site_url(),
            ],
            ] );
            update_post_meta( $post_id, '_sc_stripe_prod_id', $product->id );
            return $product;
        } catch ( Exception $e ) {
            echo  $e->getMessage() ;
            exit;
        }
    }
    
    public function create_plan( $plan, $post_id, $stripe_prod_id )
    {
        //var_dump($post_id,$_plan_id_key, $_create_plan_id);
        global  $sc_currency ;
        $stripe = $this->stripe;
        $recurring = array(
            'interval'       => $plan['interval'],
            'interval_count' => $plan['frequency'],
        );
        if ( isset( $plan['trial_days'] ) ) {
            $recurring['trial_period_days'] = $plan['trial_days'];
        }
        try {
            $stripe_plan = $stripe->prices->create( [
                'unit_amount' => get_sc_price( $plan['amount'], $sc_currency ),
                'currency'    => $sc_currency,
                'recurring'   => $recurring,
                'product'     => $stripe_prod_id,
                'metadata'    => [
                'sc_product_id' => $post_id,
                'origin'        => get_site_url(),
            ],
            ] );
            return $stripe_plan->id;
        } catch ( Exception $e ) {
            echo  $e->getMessage() ;
            exit;
        }
    }
    
    private function format_coupon_id( $id )
    {
        $id = str_replace( ' ', '_', $id );
        $id = str_replace( '-', '_', $id );
        return preg_replace( '/[^A-Za-z0-9\\_]/', '', $id );
    }

}