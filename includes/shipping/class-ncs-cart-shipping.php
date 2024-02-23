<?php

/**
 * The shipping specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.1
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/shipping
 */

 class NCS_Cart_Shipping {

    public function __construct() {
        add_filter('sc_product_setting_tabs', [$this, 'shipping_tab']);
        add_filter("sc_product_setting_tab_shipping_fields", [$this, 'shipping_fields']);
        add_filter('sc_product_field_groups', [$this, 'shipping_group'] );
        add_action('sc_after_load_from_post', [$this, 'add_shipping'] );
        add_filter('sc_checkout_stripe_subscription_args', [$this, 'add_stripe_sub_shipping'], 10, 3 );
        add_action('sc_order_marked_complete', [$this, 'do_completed_email'], 10, 3);
        add_filter('_sc_option_list', [$this, 'order_complete_email_settings']);
        add_filter( '_sc_emails_tab_section', [$this, 'add_email_group']);
        add_action('sc_before_email_footer', [$this, 'maybe_add_order_table'], 10, 2);
    }

    function maybe_add_order_table($type, $order_info) {
        if($type == 'completed') {
            sc_do_order_table($type, $order_info);
        }
    }

    function add_email_group($emails) {
        $ret = array();
        foreach($emails as $k=>$val) {
            $ret[$k] = $val;
            if ($k=='emailtemplate_purchase') {
                $ret['emailtemplate_completed'] = esc_html__('Order Complete Notification', 'ncs-cart' );
            }
        }
        return $ret;
    }

    function order_complete_email_settings($options) {
        $options['email_settings']['email_preview']['settings']['description'] = '
        <select id="sc-email-type">
            <option value="confirmation">'.__('-- Select --', 'ncs-cart').'</option>
            <option value="pending">'.__('Order Received', 'ncs-cart').'</option>
            <option value="registration">'.__('New User Welcome', 'ncs-cart').'</option>
            <option value="confirmation">'.__('Purchase Confirmation', 'ncs-cart').'</option>
            <option value="completed">'.__('Order Complete', 'ncs-cart').'</option>
            <option value="refunded">'.__('Order Refunded', 'ncs-cart').'</option>
            <option value="trial_ending">'.__('Trial Ending Reminder', 'ncs-cart').'</option>
            <option value="reminder">'.__('Upcoming Renewal Reminder', 'ncs-cart').'</option>
            <option value="renewal">'.__('Subscription Renewal Confirmation', 'ncs-cart').'</option>
            <option value="failed">'.__('Failed Renewal Payment', 'ncs-cart').'</option>
            <option value="canceled">'.__('Subscription Canceled Confirmation', 'ncs-cart').'</option>
            <option value="paused">'.__('Subscription Paused Confirmation', 'ncs-cart').'</option>
        </select>
        <a id="sc-preview-email" class="button" href="'.site_url( '/?sc-preview=email&type=[confirmation]&_wpnonce='.wp_create_nonce( 'sc-cart')).'" target="_blank">'.__('Preview Email', 'ncs-cart').'</a> 
        <a id="sc-email-send" class="button" href="#">'.__('Send Test', 'ncs-cart').'</a>
        <p class="description">'.__('Personalization tags will not render in test emails sent from this page.','ncs-cart').'</p>
        ';

        $options['emailtemplate_completed'] = array(
            'purchase_completed_enable' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Enable', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_email_completed_enable',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'email'
            ),
            'purchase_completed_subject' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Subject', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_email_completed_subject',
                    'value'         => esc_html__( 'Your order from {site_name} is complete!', 'ncs-cart' ),
                    'description'   => '',
                    'placeholder'   => ''
                ),
                'tab'=>'email'
            ),
            'purchase_completed_email_admin' => array(
                'type'          => 'checkbox',
                'label'         => esc_html__( 'Send to admin?', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_email_completed_admin',
                    'value'         => '',
                    'description'   => '',
                ),
                'tab'=>'email'
            ),
            'purchase_completed_headline' => array(
                'type'          => 'text',
                'label'         => esc_html__( 'Headline', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_email_completed_headline',
                    'value'         => '',
                    'placeholder'   => 'Thank you for your order!',
                    'description'   => '',
                ),
                'tab'=>'email'
            ),
            'purchase_completed_body' => array(
                'type'          => 'editor',
                'label'         => esc_html__( 'Body Text', 'ncs-cart' ),
                'settings'      => array(
                    'id'            => '_sc_email_completed_body',
                    'value'         => '',
                    'placeholder'   => '',
                    'description'   => '',
                    'show_tags'     => true,
                    'rows'          => 2,

                ),
                'tab'=>'email'
            ),           
        );
        return $options;
    }

    function do_completed_email($status, $order_data, $order_type) {
        if($order_type != 'bump') {
            studiocart_notification_send($status, $order_data);
        }  
    }

    function add_stripe_sub_shipping($args, $order, $sub) {

        global $sc_currency;

        $addon = $order->shipping_amount + $order->shipping_tax;
        $stripe_id = get_post_meta($order->product_id, '_sc_stripe_prod_id', true);

        if(!$addon) {
            return $args;
        }

        $args['add_invoice_items'] = $args['add_invoice_items'] ?? array();

        $item_args = array(
            'price_data' => [
                'currency'      => $sc_currency,
                'product'       => $stripe_id,
                'unit_amount'   => sc_price_in_cents($addon, $sc_currency)
            ],
            'tax_rates' => []
        );

        $args['add_invoice_items'][] = $item_args;

        return $args;
    }

    function add_shipping($order) {

        if (method_exists($order, 'get_items') && isset($order->items)) {
            $items = array();
            foreach($order->items as $item) {
                if(!isset($item['shipping_amount']) && $item['item_type'] == 'main' || $item['item_type'] == 'bump') {
                    if ($shipping = $this->maybe_add_shipping_to_order($order, $item['product_id'], $item['quantity'])) {
                        $item['shipping_amount'] = $shipping;
                    }
                }
                $items[] = $item;
            }
            $order->items = $items;
        } else { // remove
            if ($order->product_id) {
                $this->maybe_add_shipping_to_order($order, $order->product_id, $order->quantity);
            }

            if ($order->order_bumps) {
                foreach($order->order_bumps as $bump) {
                    $bump['quantity'] = $bump['quantity'] ?? 1;
                    $this->maybe_add_shipping_to_order($order, $bump['id'], $bump['quantity']);
                }
            }
        }

        if($order->shipping_amount && isset($order->order_summary_items)) {
            $shipping_item = array(
                'name'          => esc_html__('Shipping', 'ncs-cart'),
                'total_amount'  => $order->shipping_amount,
                'subtotal'      => $order->shipping_amount,
                'type'          => 'shipping'
            );

            $tax = false;

            $i = count($order->order_summary_items)-1;
            if($order->order_summary_items[$i]['type'] == 'tax') {
                $tax = array_pop($order->order_summary_items);
            }
            $order->order_summary_items[] = $shipping_item;
            if($tax) {
                $order->order_summary_items[] = $tax;
            }
        }
    }

    function maybe_add_shipping_to_order($order, $product_id, $quantity) {
        //var_dump('start', $order->amount);
        if ($shipping = $this->get_shipping($product_id, $quantity)) {
            $order->shipping_amount += $shipping;

			$tax = get_post_meta($product_id, '_sc_product_taxable', true);
            $tax_shipping = get_post_meta($product_id, '_sc_shipping_taxable', true);
            if ($tax_shipping && $tax =='tax') {
                $res = $order->calculate_tax($shipping, $product_id);
                $order->tax_amount += $res['tax'];
                $order->shipping_tax += $res['tax'];
                $order->amount += $res['total'];
            } else {
                $order->amount += $shipping;
            }
        }
        
        return $shipping;
    }

    function get_shipping($product_id, $quantity) {
        if($rates = $this->get_rates($product_id)) {
            return $this->calculate_shipping($quantity, $rates);
        }
        return false;
    }

    function get_rates($product_id) {

        $scp = sc_setup_product($product_id);

        if(!isset($scp->enable_shipping)) {
            return false;
        }

        $shipping 	= $scp->shipping_single ?? 0;
        $additional = $scp->shipping_addl ?? 0;

        $location = get_option( '_sc_country');
        $is_local = (!$location || !$this->country || ($location == $this->country));

        if(!$is_local) {
            $shipping 	= $scp->shipping_single_intl ?? $shipping;
            $additional = $scp->shipping_addl_intl ?? $additional;
        }

        return [$shipping, $additional];
    }

    function calculate_shipping($quantity, $rates) {
        $cost = 0;

        if($quantity && $rates[0]) {
            $cost = $rates[0];
        }

        if($quantity > 1 && $rates[1]) {
            $cost += ($rates[1] * ($quantity - 1));
        }

        return $cost;
    }

    function shipping_group($groups) {
        $groups[] = 'shipping';
        return $groups;
    }

    function shipping_tab($tabs) {
        $tabs['shipping'] = __('Shipping','ncs-cart');
        return $tabs;
    }

    function shipping_fields($fields) {
        return array(
            array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> '_sc_enable_shipping',
                'label'		    => __('Enable shipping','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'checkbox',
                'value'		    => '',
                'class_size'	=> ''
            ),
            array(
                'class'		    => 'widefat required',
                'id'			=> '_sc_shipping_single',
                'label'		    => __('Single item','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => 'one-half',
                'conditional_logic' => array(
					array(
						'field' => '_sc_enable_shipping',
						'value' => true,
					)
				)
            ),
            array(
                'class'		    => '',
                'id'			=> '_sc_shipping_single_intl',
                'label'		    => __('Single Item (Intl.)','ncs-cart'),
                'placeholder'	=> __('Same as single','ncs-cart'),
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => 'one-half',
                'conditional_logic' => array(
					array(
						'field' => '_sc_enable_shipping',
						'value' => true,
					)
				)
            ),
            array(
                'class'		    => '',
                'id'			=> '_sc_shipping_addl',
                'label'		    => __('Each additional item','ncs-cart'),
                'placeholder'	=> __('None','ncs-cart'),
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => 'one-half',
                'conditional_logic' => array(
					array(
						'field' => '_sc_enable_shipping',
						'value' => true,
					)
				)
            ),
            array(
                'class'		    => '',
                'id'			=> '_sc_shipping_addl_intl',
                'label'		    => __('Each additional item (Intl.)','ncs-cart'),
                'placeholder'	=> __('None','ncs-cart'),
                'type'		    => 'price',
                'value'		    => '',
                'class_size'    => 'one-half',
                'conditional_logic' => array(
					array(
						'field' => '_sc_enable_shipping',
						'value' => true,
					)
				)
            ),
            array(
                'class'		    => 'widefat',
                'description'	=> '',
                'id'			=> '_sc_shipping_taxable',
                'label'		    => __('Taxable','ncs-cart'),
                'placeholder'	=> '',
                'type'		    => 'checkbox',
                'value'		    => '',
                'class_size'	=> 'one-half',
                'conditional_logic' => array(
					array(
						'field' => '_sc_enable_shipping',
						'value' => true,
					)
				)
            ),
        );
    }
}

$sc_shipping = new NCS_Cart_Shipping();