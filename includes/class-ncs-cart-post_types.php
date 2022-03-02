<?php

/**
 * Register custom post type
 *
 * @link       https://ncstudio.co
 * @since      1.0.0
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/includes
 */
class NCS_Cart_Post_Types
{
    /**
     * Creates a new custom post type
     *
     * @since 	1.0.0
     * @access 	public
     * @uses 	register_post_type()
     */
    /**
     * Create post types
     */
    private function register_single_post_type(
        $cap_type,
        $plural,
        $single,
        $cpt_name,
        $supports = false,
        $public = false
    )
    {
        $opts = [];
        $opts['can_export'] = TRUE;
        $opts['capability_type'] = $cap_type;
        $opts['description'] = '';
        $opts['exclude_from_search'] = TRUE;
        $opts['has_archive'] = FALSE;
        $opts['hierarchical'] = FALSE;
        $opts['map_meta_cap'] = TRUE;
        $opts['menu_icon'] = 'dashicons-products';
        $opts['menu_position'] = 25;
        $opts['public'] = $public;
        $opts['publicly_querable'] = TRUE;
        $opts['query_var'] = TRUE;
        $opts['register_meta_box_cb'] = '';
        $opts['rewrite'] = FALSE;
        $opts['show_in_admin_bar'] = TRUE;
        if ( $cpt_name != 'sc_offer' ) {
            $opts['show_in_menu'] = 'studiocart';
        }
        $opts['show_in_nav_menu'] = FALSE;
        $opts['show_ui'] = TRUE;
        $opts['supports'] = $supports;
        $opts['taxonomies'] = array();
        $opts['capabilities']['delete_others_posts'] = "delete_others_{$cap_type}s";
        $opts['capabilities']['delete_post'] = "delete_{$cap_type}";
        $opts['capabilities']['delete_posts'] = "delete_{$cap_type}s";
        $opts['capabilities']['delete_private_posts'] = "delete_private_{$cap_type}s";
        $opts['capabilities']['delete_published_posts'] = "delete_published_{$cap_type}s";
        $opts['capabilities']['edit_others_posts'] = "edit_others_{$cap_type}s";
        $opts['capabilities']['edit_post'] = "edit_{$cap_type}";
        $opts['capabilities']['edit_posts'] = "edit_{$cap_type}s";
        $opts['capabilities']['edit_private_posts'] = "edit_private_{$cap_type}s";
        $opts['capabilities']['edit_published_posts'] = "edit_published_{$cap_type}s";
        $opts['capabilities']['publish_posts'] = "publish_{$cap_type}s";
        $opts['capabilities']['read_post'] = "read_{$cap_type}";
        $opts['capabilities']['read_private_posts'] = "read_private_{$cap_type}s";
        $opts['labels']['add_new'] = esc_html__( "Add New {$single}", 'ncs-cart' );
        $opts['labels']['add_new_item'] = esc_html__( "Add New {$single}", 'ncs-cart' );
        $opts['labels']['all_items'] = esc_html__( $plural, 'ncs-cart' );
        $opts['labels']['edit_item'] = esc_html__( "Edit {$single}", 'ncs-cart' );
        $opts['labels']['menu_name'] = esc_html__( $plural, 'ncs-cart' );
        $opts['labels']['name'] = esc_html__( $plural, 'ncs-cart' );
        $opts['labels']['name_admin_bar'] = esc_html__( $single, 'ncs-cart' );
        $opts['labels']['new_item'] = esc_html__( "New {$single}", 'ncs-cart' );
        $opts['labels']['not_found'] = esc_html__( "No {$plural} Found", 'ncs-cart' );
        $opts['labels']['not_found_in_trash'] = esc_html__( "No {$plural} Found in Trash", 'ncs-cart' );
        $opts['labels']['parent_item_colon'] = esc_html__( "Parent {$plural} :", 'ncs-cart' );
        $opts['labels']['search_items'] = esc_html__( "Search {$plural}", 'ncs-cart' );
        $opts['labels']['singular_name'] = esc_html__( $single, 'ncs-cart' );
        $opts['labels']['view_item'] = esc_html__( "View {$single}", 'ncs-cart' );
        $opts['rewrite']['ep_mask'] = EP_PERMALINK;
        $opts['rewrite']['feeds'] = FALSE;
        $opts['rewrite']['pages'] = TRUE;
        $opts['rewrite']['slug'] = esc_html__( strtolower( $plural ), 'ncs-cart' );
        $opts['rewrite']['with_front'] = FALSE;
        $opts = apply_filters( 'sc-cart-cpt-options', $opts );
        if ( $cpt_name == 'sc_subscription' ) {
            $opts['capabilities']['create_posts'] = false;
        }
        if ( $cpt_name == 'sc_product' ) {
            $opts['hierarchical'] = TRUE;
        }
        register_post_type( strtolower( $cpt_name ), $opts );
    }
    
    private function register_single_post_type_taxonomy( $plural, $single, $tax_name )
    {
        $opts = [];
        $opts['hierarchical'] = ( $tax_name == 'sc_product_tag' ? false : TRUE );
        $opts['labels']['name'] = esc_html__( $plural, 'ncs-cart' );
        $opts['labels']['singular_name'] = esc_html__( $single, 'ncs-cart' );
        $opts['labels']['search_items'] = esc_html__( "Search {$plural}", 'ncs-cart' );
        $opts['labels']['all_items'] = esc_html__( $plural, 'ncs-cart' );
        $opts['labels']['parent_item'] = esc_html__( "Parent {$single}", 'ncs-cart' );
        $opts['labels']['parent_item_colon'] = esc_html__( "Parent {$single}:", 'ncs-cart' );
        $opts['labels']['edit_item'] = esc_html__( "Edit {$single}", 'ncs-cart' );
        $opts['labels']['update_item'] = esc_html__( "Update {$single}", 'ncs-cart' );
        $opts['labels']['add_new_item'] = esc_html__( "Add New {$single}", 'ncs-cart' );
        $opts['labels']['new_item_name'] = esc_html__( "New {$single} Name", 'ncs-cart' );
        $opts['labels']['menu_name'] = esc_html__( $plural, 'ncs-cart' );
        $opts['show_ui'] = TRUE;
        $opts['show_in_rest'] = TRUE;
        $opts['show_admin_column'] = true;
        $opts['query_var'] = true;
        $opts['rewrite']['slug'] = esc_html__( strtolower( $tax_name ), 'ncs-cart' );
        $opts = apply_filters( 'sc-cart-taxonomy-options', $opts );
        register_taxonomy( $tax_name, 'sc_product', $opts );
    }
    
    public function create_custom_post_type()
    {
        $post_types_args = array( array(
            'cap_type' => 'post',
            'plural'   => __( 'Products', 'ncs-cart' ),
            'single'   => __( 'Product', 'ncs-cart' ),
            'cpt_name' => 'sc_product',
            'supports' => array( 'title', 'editor', 'thumbnail' ),
            'public'   => true,
        ), array(
            'cap_type' => 'post',
            'plural'   => __( 'Orders', 'ncs-cart' ),
            'single'   => __( 'Order', 'ncs-cart' ),
            'cpt_name' => 'sc_order',
            'supports' => false,
            'public'   => false,
        ), array(
            'cap_type' => 'post',
            'plural'   => __( 'Subscriptions', 'ncs-cart' ),
            'single'   => __( 'Subscription', 'ncs-cart' ),
            'cpt_name' => 'sc_subscription',
            'supports' => false,
            'public'   => false,
        ) );
        foreach ( $post_types_args as $post_type ) {
            $this->register_single_post_type(
                $post_type['cap_type'],
                $post_type['plural'],
                $post_type['single'],
                $post_type['cpt_name'],
                $post_type['supports'],
                $post_type['public']
            );
        }
        $taxonomies = array( array(
            'plural'   => __( 'Categories', 'ncs-cart' ),
            'single'   => __( 'Category', 'ncs-cart' ),
            'tax_name' => 'sc_product_cat',
        ), array(
            'plural'   => __( 'Tags', 'ncs-cart' ),
            'single'   => __( 'Tag', 'ncs-cart' ),
            'tax_name' => 'sc_product_tag',
        ) );
        foreach ( $taxonomies as $taxonomy ) {
            $this->register_single_post_type_taxonomy( $taxonomy['plural'], $taxonomy['single'], $taxonomy['tax_name'] );
        }
    }

}