<?php

/**
 * The Template for displaying an order bump on an order form
 * This template can be overridden by copying it to yourtheme/studiocart/order-form/orderbump.php.
 */
    
?>

<div id="sc-orderbump-<?php echo $args['key']; ?>" class="sc-section orderbump">
    
    <h3 class="title"><?php echo esc_html($args['headline']); ?></h3>
    
    <?php if ( $args['image'] && $args['image_pos'] == 'top' ): ?>
        <div class="sc-bump-image"><img src="<?php echo esc_attr($args['image']); ?>"></div>
    <?php endif; ?>

    <div class="row <?php echo $args['atts']; ?>">
        <div class="col-sm-12">
            <?php echo wpautop(esc_html($args['text'])); ?>
        </div>
        <div class="col-sm-12 ob-cta">
            <label>
                <input 
                    type="checkbox" 
                    id="<?php echo $args['cb_id'];?>" 
                    name="sc-orderbump[<?php echo $args['key'];?>]" 
                    class="<?php echo $args['class']; ?>" 
                    value="<?php echo $args['bump_id'];?>"
                /> 
                <span class="item-name">
                    <?php echo esc_html($args['cta']); ?>
                </span>
            </label>
        </div>
    </div>

</div>