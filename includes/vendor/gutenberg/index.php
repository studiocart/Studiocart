<?php

class SCFormBlock {
    function __construct($name, $renderCallback = true) {
        
        if (!function_exists('register_block_type')) {
            return;
        }

        $this->name = $name;
        $this->renderCallback = $renderCallback;
        add_action('init', [$this, 'onInit']);
        add_action('rest_api_init', [$this, 'ProdHtml']);

    }

    function ProdHtml() {
        register_rest_route('sc-order-form/v1', 'getHTML', array(
            'methods' => WP_REST_SERVER::READABLE,
            'callback' => [$this, 'getProdHtml'],
            'permission_callback' => '__return_true',
        ));
    }

    function getProdHtml($data) {
        if(!$data['pid']) {
            $title = __('Dynamic', 'ncs-cart');
        } else {
            $title = get_the_title($data['pid']);
        }
        
        return sprintf(__('%s Order Form','ncs-cart'), apply_filters('studiocart_plugin_title','Studiocart')).': '.wp_strip_all_tags($title);
    }

    function doRenderCallback($attr, $content) {
        ob_start();
        require plugin_dir_path( dirname( __FILE__ ) ) . "gutenberg/checkout-form/{$this->name}.php";
        return ob_get_clean();
    }

    function onInit() {

        /*if (is_admin()) {
            $typenow = scBlocksTypeNow();
            if ($typenow != 'sc_checkout_form' && $typenow != 'page') {
                return;
            }
        }*/

        $args = array(
            'editor_script' => $this->name,
        );

        if ($this->renderCallback) {
            $args['render_callback'] = array($this, 'doRenderCallback');
        }

        wp_register_script( $this->name, plugin_dir_url(__FILE__) . "build/{$this->name}.js", array( 'wp-blocks', 'wp-element', 'wp-editor') );

        wp_localize_script( $this->name, 'sc_product_shortcode_script_gb',
            array( 
                'may_white_label_title'=>apply_filters('studiocart_plugin_title','Studiocart')
            )
        );

        register_block_type('sc-products-shortcode/product-shortcode', $args);
    }
}

function scBlocksTypeNow() {
    if (is_admin()) {
        global $pagenow;
        $typenow = '';

        if ( 'post-new.php' === $pagenow ) {
            if ( isset( $_REQUEST['post_type'] ) && post_type_exists( $_REQUEST['post_type'] ) ) {
                $typenow = $_REQUEST['post_type'];
            }
        } elseif ( 'post.php' === $pagenow ) {
            if ( isset( $_GET['post'] ) && isset( $_POST['post_ID'] ) && (int) $_GET['post'] !== (int) $_POST['post_ID'] ) {
                // Do nothing
            } elseif ( isset( $_GET['post'] ) ) {
                $post_id = (int) $_GET['post'];
            } elseif ( isset( $_POST['post_ID'] ) ) {
                $post_id = (int) $_POST['post_ID'];
            }
            if ( $post_id ) {
                $post = get_post( $post_id );
                $typenow = $post->post_type;
            }
        }

        return $typenow;
    }
    return false;
}

new SCFormBlock('order-form');