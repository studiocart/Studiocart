<?php
/**
 * The Template for displaying the product archive wrapper start
 * This template can be overridden by copying it to <active-theme-folder>/studiocart/archive/item-wrapper-start.php.
 */
?>

    <?php do_action('sc_before_product_list'); ?>
    <ul class="cards col-<?php esc_html_e($attr['cols']); ?>">