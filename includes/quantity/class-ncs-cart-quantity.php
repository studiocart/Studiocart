<?php

/**
 * The quantity specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      1.0.1
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/quantity
 */

class NCS_Cart_Quantity {

    public function __construct() {
        add_action('sc_card_details_fields', array($this, 'quantity_field'), 9);
        add_action('sc_card_details_fields', array($this, 'do_checkout_page_products'), 5, 3);
        add_filter("sc_product_setting_tab_fields_fields", [$this, 'quantity_setting_field']);    
	}

    public function quantity_setting_field($fields){
        $ret = array();
        foreach($fields as $group) {
            $ret[] = $group;
            if($group['id']=='_sc_show_address_fields') {
                $ret[] = array(
                            'class'		    => '',
                            'description'	=> '',
                            'id'			=> '_sc_qty_field',
                            'label'	    	=> __('Enable quantity field','ncs-cart'),
                            'type'		    => 'checkbox',
                            'value'		    => '',
                            'class_size'    => ''
                );
            }
        }
        return $ret;
    }

    public function quantity_field($post_id) {
        
        global $scp; 
        
        if( (!isset($scp->qty_field) || !$scp->qty_field) || get_post_meta($post_id, '_sc_price_type', true) == 'product' ) {
            return;
        }
        ?>
        <div class="sc-section">
            <div class="quantity">
                <h3 class="title"><?php esc_html_e('Quantity', 'ncs-cart'); ?></h3>
                <div class="row">
                    <?php 
            
                    $field = array(
                            'id'=>'sc_qty',
                            'name'=>'sc_qty',
                            'label'=> '', 
                            'hide_labels'=>true,
                            'value'=> 1,
                            'cols'=>12,
                            'div_class'=>'',
                            'type'=>'number'
                    );

                    sc_do_field($field);
                    ?>
                </div>
            </div>
        </div>

        <?php
    }

    function do_checkout_page_products($post_id, $hide_labels, $plan=false) {

        global $scp;

        if (get_post_meta($post_id, '_sc_price_type', true) != 'product' || !get_post_meta($post_id, '_sc_qty_field', true)) {
            return;
        }

        $hide_class = '';
        
        if (isset($scp->show_coupon_field)) {
            $hide_class .= ' sc-show-coupon';
        }
        
        do_action('sc_orderform_before_product_list', $post_id);
        ?>
    
        <div class="sc-section products product-list <?php echo $hide_class; ?>">
    
            <?php    
            $on_sale = sc_is_prod_on_sale();
    
            $name = (!$on_sale) ? 'option_name' : 'sale_option_name';
            $price = (!$on_sale) ? 'price' : 'sale_price';  
            $items = $scp->product_options;
            $i = 0;
        
            $plan_heading = ( isset($scp->plan_heading) && $scp->plan_heading ) ? $scp->plan_heading : esc_html__("Payment Plan", "ncs-cart");
            $plan_heading = apply_filters('sc_plan_heading', $plan_heading, $scp->ID);
            ?>
    
            <h3 class="title"><?php echo esc_html($plan_heading); ?></h3>
    
            <?php do_action('sc_coupon_fields', $post_id); ?>

            <div class="product-list-wrap">
                
                <?php foreach ($items as $item): ?>

                    <?php
                    $prod_img = NCS_CART_BASE_URL . 'public/images/placeholder.jpg';
                    if ( has_post_thumbnail( $item['prod_product'] ) ) {
                        $prod_img = get_the_post_thumbnail_url($item['prod_product']);
                    }
                    ?>
            
                    <div class="item flex-wrap"> 
                        
                        <div class="product-image" style="background-image:url(<?php echo $prod_img; ?>)"></div> 

                        <label for="<?php printf('sc_qty[%s][%s]', $item['prod_product'], $item['prod_plan']); ?>" class="item-name">
                            <?php esc_html_e(sc_get_public_product_name($item['prod_product'])); ?>                                            
                            <span>
                                <?php if(isset($item['prod_on_sale']) && isset($item['prod_show_full_price'])): ?>
                                    <?php $plan = studiocart_plan($item['prod_plan'], '', $item['prod_product']); ?>
                                    <s><?php if(isset($plan->initial_payment)) sc_formatted_price($plan->initial_payment); ?></s> 
                                <?php endif; ?>

                                <?php $plan = studiocart_plan($item['prod_plan'], isset($item['prod_on_sale']), $item['prod_product']); ?>
                                <strong><?php if(isset($plan->initial_payment)) sc_formatted_price($plan->initial_payment); ?></strong> 
                            </span>    
                        </label>                    
                        <div class="w-100 my-4">             
                            <button class="qty-dec"><!--?xml version="1.0" ?--><svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style></defs><title></title><g id="minus"><line class="cls-1" x1="7" x2="25" y1="16" y2="16"></line></g></svg></button>
                            <input name="<?php printf('sc_qty[%s][%s]', $item['prod_product'], $item['prod_plan']); ?>" value="1" min="" class="sc_qty form-control mb-0" placeholder="Qty" type="number">
                            <button class="qty-inc"><!--?xml version="1.0" ?--><svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg"><defs><style>.cls-1{fill:none;stroke:#000;stroke-linecap:round;stroke-linejoin:round;stroke-width:2px;}</style></defs><title></title><g id="plus"><line class="cls-1" x1="16" x2="16" y1="7" y2="25"></line><line class="cls-1" x1="7" x2="25" y1="16" y2="16"></line></g></svg></button>
                        </div>
                    </div>
                    
                <?php $i++; endforeach; ?>

            </div>
    
            <?php do_action('sc_coupon_status', $post_id); ?>
    
        </div>
        <?php
    }
}

$sc_qty = new NCS_Cart_Quantity();
