<?php
if(!defined('ABSPATH')) {die('You are not allowed to call this page directly.');}

class ScrtCollection extends ScrtOrder {

    public function load_from_post() {
      
        global $scp, $sc_currency;

        $this->setup_atts_from_post(); 
    
        if($scp->price_type == 'product') {

            if (isset($_POST['sc_qty']) && is_array($_POST['sc_qty'])) {
                $this->quantity = array();
                foreach($_POST['sc_qty'] as $pid=>$arr) {
                    foreach($arr as $plan=>$val ) {
                        $this->quantity[$pid][$plan] = sanitize_text_field($val);
                    }
                } 
            }
            
            $this->plan = null;
            $this->plan_id = null;
            $this->option_id = null;
            $this->tax_data = null;
            $this->tax_rate = null;	
            $this->tax_desc = null;
            $this->stripe_tax_id = null;
            $this->tax_amount = 0;
            $this->pre_tax_amount = 0;

            foreach($scp->product_options as $prod) {
                if (is_countable($this->quantity)) {
                    $prod['quantity'] = $this->quantity[$prod['prod_product']][$prod['prod_plan']];
                } else {
                    $prod['quantity'] = $this->quantity;
                }

                if (intval($prod['quantity']) > 0) {
                    $this->add_collection_item_from_post($prod);
                }
            } 
        } else {
            $this->add_main_item_from_post();

            if ($this->coupon) {
                $args = $this->apply_plan_coupon_to_items();
            }

            foreach($scp->product_options as $prod) {
                $this->add_bundled_item($prod);
            }
        }

        if (isset($scp->custom_fields)) {
            $this->add_line_item_from_post();
        }

        if ( isset($_POST['sc-orderbump'])) {
            $this->order_bumps = array();
            $this->add_bump_items_from_post($_POST['sc-orderbump']);
        }

        if ($this->coupon) {
            $args = $this->apply_cart_coupon_to_items();
        }

        $this->calculate_pre_tax_amount_from_items();
        $this->calculate_tax_amount_from_items();
        $this->calculate_total_amount_from_items();

        $this->invoice_subtotal = $this->pre_tax_amount;
    
        $this->amount = apply_filters('sc_charge_amount', $this->amount, $scp);
        $this->amount = $this->amount;
        $this->invoice_total = $this->amount;

        // setup order_summary_items for checkout form
        $this->calculate_final_amounts();

		// end order setup   
		do_action('sc_after_load_from_post',$this,$_POST);
    }

    // exclude shipping costs at product level and hide bundled subitem from receipts, invoices, etc. 
    public function add_bundled_item($prod) {

        $unit_price     = 0;
        $total          = 0;
        $product_name   = get_the_title($prod['prod_product']);
        $product_id     = $prod['prod_product'];
        $price_id       = 'sc_collection';
        $price_name     = '';
        $quantity       = $this->quantity;

        $args = array(
            'product_id'     => $product_id,
            'price_id'       => $price_id,
            'product_name'   => $product_name,
            'price_name'     => $price_name,
            'unit_price'     => $unit_price,
            'item_type'      => 'bundled',
            'quantity'       => $quantity,
            'subtotal'       => $total,
            'total_amount'   => $total,
        );

        if ($note = get_post_meta($args['product_id'], '_sc_purchase_note', true)) {
            $args['purchase_note'] = $note;
        }

        $this->add_item($args);
    }

    public function add_collection_item_from_post($prod) {

        $plan = studiocart_plan($prod['prod_plan'], isset($prod['prod_on_sale']), $prod['prod_product']);
        $unit_price     = $plan->initial_payment;
        $total          = $plan->initial_payment * $prod['quantity'];
        $product_name   = get_the_title($prod['prod_product']);
        $product_id     = $prod['prod_product'];
        $price_id       = $prod['prod_plan'];
        $price_name     = $plan->name;
        $quantity       = $prod['quantity'];

        $args = array(
            'product_id'     => $product_id,
            'price_id'       => $price_id,
            'product_name'   => $product_name,
            'price_name'     => $price_name,
            'unit_price'     => $unit_price,
            'item_type'      => 'main',
            'quantity'       => $quantity,
            'subtotal'       => $total,
            'total_amount'   => $total,
        );

        if (!empty($plan->trial_days)) {
            $args['trial_days'] = $plan->trial_days;
        }

        if (!empty($plan->fee)) {
            $args['sign_up_fee'] = $plan->fee;
        }

        if ($note = get_post_meta($args['product_id'], '_sc_purchase_note', true)) {
            $args['purchase_note'] = $note;
        }

        $this->main_offer_amt += $total;
        $this->add_item($args);
    }
}