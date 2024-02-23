<?php
/**
 * The Template for displaying customer's card details
 * This template can be overridden by copying it to yourtheme/studiocart/my-account/card-details.php.
 */
?>
<p>
    <?php 
    $card = $args->card->card;
    if (file_exists(NCS_CART_BASE_DIR . 'public/images/cc/'.$card->brand.'.svg')) {
        $card_image = $card->brand;
    } else {
        $card_image = 'generic';
    }
    
    esc_html_e("Payment Method", 'ncs-cart'); ?><br>
    <img class="sc-card-icon" src="<?php echo NCS_CART_BASE_URL . 'public/images/cc/'.$card_image.'.svg' ?>"> xxxx xxxx xxxx <?php echo $card->last4; ?> (<?php esc_html_e("Expires", 'ncs-cart'); ?> <?php echo $card->exp_month.'/'.$card->exp_year; ?>) | <a id="update-card" class="openmodal update_card" href="javascript:void(0);" data-id="<?php echo intval($_REQUEST['sc-plan']); ?>" data-item-id="<?php echo apply_filters('sc_sub_item_id',$args->option_id,$args); ?>"><?php esc_html_e( 'Update Card', 'ncs-cart' ); ?></a>
</p>