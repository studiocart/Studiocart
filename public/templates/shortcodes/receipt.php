<?php
/**
 * The Template for displaying customer's card details
 * This template can be overridden by copying it to <active-theme-folder>/studiocart/shortcodes/receipt.php.
 */
?>

<div id="sc-order-details">
    <h3><?php _e("Order Details", "ncs-cart"); ?></h3>
    <div class="sc-order-table">
        <div class="item sc-heading"><strong><?php _e("Product", "ncs-cart"); ?></strong></div>
        <div class="order-total sc-heading"><strong><?php _e("Price", "ncs-cart"); ?></strong></div>
        <?php foreach($attr['items'] as $item) : ?>
            <?php $item['item_type'] = $item['item_type'] ?? ''; ?>
            <div class="item">
                <?php echo $item['product_name']; echo isset($item['price_name']) ? "-".$item['price_name'] : ""; ?>
                <?php if (isset($item['sub_summary'])): ?>
                    <br><small><?php echo $item['sub_summary']; ?></small>
                <?php endif; ?>
                <?php if (isset($item['purchase_note'])): ?>
                    <br><span class="sc-purchase-note"><?php echo $item['purchase_note']; ?></span>
                <?php endif; ?>
            </div>
            <div class="order-total">
                <?php echo isset($item['subtotal']) ? sc_format_price($item['subtotal']) : ''; ?>
            </div>                    
        <?php endforeach; ?>

        <?php if(isset($attr['discounts']) || (isset($attr['tax']) && $attr['tax']['product_name']) || isset($attr['shipping'])): ?>
            <div class="item" style="border: 0"><strong><?php echo $attr['subtotal']['product_name']; ?></strong></div>
            <div class="order-total" style="border: 0">
                <strong><?php sc_formatted_price($attr['subtotal']['total_amount']); ?></strong>
            </div>
            <br><br>
        <?php endif; ?>

        <?php if(isset($attr['discounts'])): ?>
            <?php foreach($attr['discounts'] as $item) : ?>
                <div class="item"><?php echo $item['product_name']; ?></div>
                <div class="order-total">
                    -<?php sc_formatted_price($item['total_amount']); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if(isset($attr['shipping'])): ?>
            <div class="item"><?php echo $attr['shipping']['product_name']; ?></div>
            <div class="order-total">
                <?php sc_formatted_price($attr['shipping']['total_amount']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($attr['tax']) && isset($attr['tax']['product_name']) && $attr['tax']['product_name']): ?>
            <div class="item"><?php echo $attr['tax']['product_name']; ?></div>
            <div class="order-total">
                <?php sc_formatted_price($attr['tax']['total_amount']); ?>
            </div>
        <?php endif; ?>

        <div class="item" style="border: 0"><strong><?php echo $attr['total']['product_name']; ?></strong></div>
        <div class="order-total" style="border: 0">
            <strong><?php sc_formatted_price($attr['total']['total_amount']); ?></strong>
        </div>
    </div>
    <?php do_action('sc_receipt_after_order_details', $attr); ?>
</div>
            

            
        