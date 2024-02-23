<?php
/**
 * The Template for displaying a product archive
 * This template can be overridden by copying it to <active-theme-folder>/studiocart/shortcodes/archive.php.
 */

 ?>

<div class="sc-archive">

    <?php ncs_template( 'archive/item', 'wrapper-start', $attr);

        $the_query = $attr['query'];
        while ( $the_query->have_posts() ) { $the_query->the_post();

            if ($current_user = wp_get_current_user()) {
                if(do_shortcode('[sc_customer_bought_product email='.$current_user->user_email.' user_id='.$current_user->ID.']')) {
                    $attr['button_text'] = $attr['purchased_text'];
                }
            }

            ncs_template( 'archive/item', '', $attr);
        } 
        
        wp_reset_postdata(); ?>

    <?php ncs_template( 'archive/item', 'wrapper-end', $attr); ?>
    <?php ncs_template( 'archive/navigation', '', $attr); ?>

</div>