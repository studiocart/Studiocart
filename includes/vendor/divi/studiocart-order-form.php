<?php
if ( ! function_exists( 'stof_initialize_extension' ) ):
/**
 * Creates the extension's main class instance.
 *
 * @since 1.0.0
 */
function stof_initialize_extension() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/StudiocartOrderForm.php';
}
add_action( 'divi_extensions_init', 'stof_initialize_extension' );
endif;

add_action( 'wp_ajax_pm_et_request_request', 'pm_et_request_request' );
add_action( 'wp_ajax_nopriv_pm_et_request_request', 'pm_et_request_request' );
 function pm_et_request_request() {
	 $shortCode = "";
	 $post_id = isset( $_POST['postId'] ) && !empty($_POST['postId']) ? $_POST['postId'] : 0;
	 $hide_labels = isset( $_POST['label'] ) && $_POST['label'] == "yes" ? "hide" : "show";
	 $template = isset( $_POST['template'] ) && $_POST['template'] == "2-step" ? "2-step" : $_POST['template'];
	 $coupon = isset( $_POST['coupon'] ) ? $_POST['coupon'] : false;
		if( $post_id ){				
			if( $template ){
				$shortCode = do_shortcode("[studiocart-form builder=true id={$post_id} hide_labels='{$hide_labels}' template='{$template}' coupon='{$coupon}']");		
			}else{			
				$shortCode = do_shortcode("[studiocart-form builder=true id={$post_id} hide_labels='{$hide_labels}' coupon='{$coupon}']");		
			}
		}
	$response_data = ['success' => true, "data" => $shortCode ];
	wp_send_json($response_data);				
	wp_die();
  }



