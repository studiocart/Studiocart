<?php

namespace Studiocart;

use WP_Query;

if (!defined('ABSPATH'))
	exit;
	
class CancelSubscription {
    
    /**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $service_name;
	private $service_label;
    
	public function __construct() {
		$this->service_name = "sc_subscription";
		$this->service_label = "Cancel Subscription";
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        add_filter('sc_integrations', array($this, 'add_subscription_service'));
        add_filter('sc_integration_fields', array($this, 'add_integration_fields'), 10, 2);
        add_action('studiocart_'.$this->service_name.'_integrations', array($this, 'maybe_cancel_subscription'), 10, 3);
    }
	
    public function add_subscription_service($options) {
        $options[$this->service_name] = $this->service_label;
        return $options;
    }
    
    public function add_integration_fields($fields, $save) {
        
        $fields[1]['fields'][] = array(
            'select' => array(
                'class'		    => 'widefat update-plan-product recurring',
                'id'			=> 'sc_sub_prod_id',
                'label'		    => esc_html__('Select Product','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'class_size'    => ' one-half first',
                'selections'    => ($save) ? '' : $this->get_products(),
                'conditional_logic' => array( 
                        array(
                            'field' => 'services',
                            'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                )
            )
        ); 
        
        $fields[1]['fields'][] = array(
            'select' => array(
                'class'		    => 'widefat update-plan ob-{val}',
                'id'			=> 'sc_sub_plan_id',
                'label'		    => esc_html__('Select Plan','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'class_size'    => ' one-half',
                'selections'    => $this->get_plans('sc_sub_prod_id'),
                'conditional_logic' => array( 
                        array(
                            'field' => 'services',
                            'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                )
            )
        );
        
        $fields[1]['fields'][] = array(
            'select' => array(
                'class'		    => 'widefat',
                'id'			=> 'sc_sub_cancel',
                'label'		    => esc_html__('Cancel','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'select',
                'value'		    => '',
                'class_size'    => '',
                'selections'    => array('yes' => __('Immediately'), 'no' => __('At the end of the current billing period')),
                'conditional_logic' => array( 
                        array(
                            'field' => 'services',
                            'value' => $this->service_name, // Optional, defaults to "". Should be an array if "IN" or "NOT IN" operators are used.
                            'compare' => '=', // Optional, defaults to "=". Available operators: =, <, >, <=, >=, IN, NOT IN
                        )
                )
            )
        );
        
        return $fields;
    }
	
	public function maybe_cancel_subscription($int, $sc_product_id, $order) {       
        
        $past_product_ids = get_post_meta($order['id'], '_sc_sub_cancel_integration_runs');
        if(is_array($past_product_ids) && in_array($int['sc_sub_prod_id'].':'.$int['sc_sub_plan_id'], $past_product_ids)) {
            return;
        }

        sc_log_entry($order['id'], sprintf('Subscription cancel integration called by product ID: %s', $sc_product_id));
        
        $args = array();
        if($order['user_account']) { $args[] = 'user_id='.$order['user_account']; }
        if($order['email']) { $args[] = 'email='.$order['email']; }

        $shortcode = sprintf('[sc_customer_has_subscription product_id=%s plan_id=%s %s]', $int['sc_sub_prod_id'], $int['sc_sub_plan_id'], implode(' ', $args));
        
        if($id = do_shortcode($shortcode)) {
            $sub = new \ScrtSubscription($id);
            $now = ($int['sc_sub_cancel'] == 'yes') ? true : false;
            
            $out = sc_do_cancel_subscription($sub, $sub->subscription_id, $now, $echo=false);
            
            if($out == 'OK') {
                sc_log_entry($order['id'], sprintf(__("Subscription ID: %s has been canceled", 'ncs-cart'), $id));
                add_post_meta($order['id'], '_sc_sub_cancel_integration_runs', $int['sc_sub_prod_id'].':'.$int['sc_sub_plan_id']);
            } else {
                sc_log_entry($order['id'], sprintf(__("Error canceling subscription ID: %s! Message: %s", 'ncs-cart'), $id, $out));
            }
        } else {
            sc_log_entry($order['id'], sprintf(__('No active subscriptions found for product ID: %s and plan ID=%s', 'ncs-cart'), $int['sc_sub_prod_id'], $int['sc_sub_plan_id']));
        }
    }   
      
    private function get_products(){
        if (!isset($_GET['post'])) {
            return;
        }
        
        global $studiocart;
        remove_filter( 'the_title', array( $studiocart, 'public_product_name' ) );

        $options = array('' => __('-- Select Product --','ncs-cart'));
                
        // The Query
        $args = array(
            'post_type' => array( 'sc_product', 'sc_collection' ),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'key' => '_sc_pay_options',
                'compare' => 'EXISTS'
            )
        );
        $the_query = new WP_Query( $args );

        // The Loop
        if ( $the_query->have_posts() ) {
            while ( $the_query->have_posts() ) {
                $the_query->the_post(); 
                $options[get_the_ID()] = get_the_title() . ' (ID: '.get_the_ID().')';
            }
        } else{
            $options = array('' => __('-- none found --','ncs-cart'));
		}
        /* Restore original Post Data */
        wp_reset_postdata();
        
        add_filter( 'the_title', array( $studiocart, 'public_product_name' ) );
		return $options;
	}

    private function get_plans($key) {
        if(!isset($_GET['post'])) {
            return;
        }
        $options = array();
        $post_id = intval($_GET['post']);

        if ($integrations = get_post_meta($post_id, '_sc_integrations', true)) {
            if(is_array($integrations)){
                foreach($integrations as $integration){
                    if(isset($integration[$key])) {
                        $pid = $integration[$key];
                        $options = $this->get_plan_data($pid);
                    }
                }
            }
        }

        return $options;
    }

    private function get_plan_data($product_id){
        $product_plan_data = get_post_meta($product_id, '_sc_pay_options', true);

        if(!$product_plan_data) {
            return array(""=> esc_html__('No plans found'));
        } else {
            $options = array();
            foreach ( $product_plan_data as $val ) {
                $type = $val['product_type'] ?? '';
                if ($type == 'recurring') {
                    $options[$val['option_id']] = $val['option_name'];
                }
            }
            if(!empty($options)) {
                return $options;
            } else {
                return array("" => esc_html__('No plans found'));
            }
        }
    }
}