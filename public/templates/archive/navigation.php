<?php
/**
 * The Template for displaying a product archive navigation
 * This template can be overridden by copying it to <active-theme-folder>/studiocart/shortcodes/archive/navigation.php.
 */
?>

<?php $the_query = $attr['query']; ?>

<div class="sc-archive-navigation"> 
    <?php
    $GLOBALS['wp_query']->max_num_pages = $the_query->max_num_pages;
    the_posts_pagination( array(
        'mid_size' => 3,
        'prev_text' => __( '<svg id="left" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>', 'ncs-cart' ),
        'next_text' => __( '<svg id="right" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>', 'ncs-cart' ),
    ) ); ?>
</div>