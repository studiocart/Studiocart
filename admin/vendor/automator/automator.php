<?php

// Register the integration's name, icons, and code 
add_action( 'uncanny_automator_add_integration', 'sc_add_integration_func', 10 ,1 ); // Function names need to be unique

/**
 * Add integration data to Automator
 */
function sc_add_integration_func() {

   global $uncanny_automator;

   $uncanny_automator->register_integration(
      'NCS', // Integration Code
      array(
         'name'        => 'Studiocart', // Integration Name
         'icon_16'     => plugins_url( 'studiocart-icon-16.png', __FILE__ ),
         'icon_32'     => plugins_url( 'studiocart-icon-32.png', __FILE__ ),
         'icon_64'     => plugins_url( 'studiocart-icon-64.png', __FILE__ ),
         'logo'        => plugins_url( 'studiocart.png', __FILE__ ),
         'logo_retina' => plugins_url( 'studiocart@2x.png', __FILE__ ),
      )
   );
}

// This filter adds your integration to Automator and its associated triggers and actions, if it passes the validation function
add_filter( 'uncanny_automator_maybe_add_integration', 'sc_maybe_add_integration', 11, 2 );

/**
 * Check if the plugin we are adding an integration for is active based on integration code
 *
 * @param bool   $status If the integration is already active or not
 * @param string $code   The integration code
 *
 * @return bool $status
 */
function sc_maybe_add_integration( $status, $code ) {

   if ( 'NCS' === $code ) {
      if ( class_exists( 'NCS_Cart' ) ) {
         $status = true;
      } else {
         $status = false;
      }
   }

   return $status;
}

// Add trigger during the "uncanny_automator_add_integration_triggers_actions_tokens" do_action()
add_action( 'uncanny_automator_add_integration_triggers_actions_tokens', 'sc_triggers_user_makes_purchase' );

/**
 * Define and register the trigger by pushing it into the Automator object
 */
function sc_triggers_user_makes_purchase() {

   global $uncanny_automator;

   $trigger = array(
      'author'              => 'Studiocart',
      'support_link'        => 'https://www.studiocart.co/',
      'integration'         => 'NCS',
      'code'                => 'PURCHASESCPRODUCT',
      /* Translators: 1:Products 2:Number of times*/
      'sentence'            => sprintf( __( 'A user purchases {{a product:%1$s}}', 'ncs-cart' ), 'NCSPRODUCT', 'NCSPLAN' ),
      'select_option_name'  => __( 'User purchases {{a product}}', 'ncs-cart' ),
      'action'              => 'sc_order_complete',
      'priority'            => 90,
      'accepted_args'       => 2,
      'validation_function' => 'payment_completed',
      'options'             => [
         [
            'option_code' => 'NCSPRODUCT',
            'label'       => __( 'Select a Product', 'ncs-cart' ),
            'input_type'  => 'select',
            'required'    => true,
            'options'     => sc_product_options(),
         ]
      ],
   );

   $uncanny_automator->register_trigger( $trigger );

   return;
}

/**
 * Create an array of options for the select drop down
 *
 * @return array $options
 */
function sc_product_options() {

   $args = [
      'post_type'      => 'sc_product',
      'posts_per_page' => 999,
      'orderby'        => 'title',
      'order'          => 'ASC',
      'post_status'    => 'publish',
   ];

   $posts = get_posts( $args );

   $options = [];
   $options['-1'] = __( 'Any product', 'ncs-cart' );

   foreach ( $posts as $post ) {

      $title = $post->post_title;

      if ( empty( $title ) ) {
         /* Translators: 1:The post ID*/
         $title = sprintf( __( 'ID: %1$s (no title)', 'ncs-cart' ), $post->ID );
      }

      $options[ $post->ID ] = $title;
   }

   return $options;
}

/**
 * The validation function runs during the defined add_action() that was dynamically created in our trigger
 * definition array.
 */
function payment_completed($order_info, $order_type) {

   global $uncanny_automator;

   $args = [
      'code'    => 'PURCHASESCPRODUCT',
      'meta'    => 'NCSPRODUCT',
      'post_id' => $order_info['product_id'],
   ];

   $uncanny_automator->maybe_add_trigger_entry( $args, true );

   return; 
}

/**
 * Return all the specific terms of the selected taxonomy in ajax call
 */
add_action( 'wp_ajax_select_plans_for_selected_product', 'select_plans_for_selected_product' );
function select_plans_for_selected_product() {
    global $uncanny_automator;

    $uncanny_automator->utilities->ajax_auth_check( $_POST );
    $fields = [];

    $fields[] = array(
        'value' => '0',
        'text'  => __( 'Any payment plan', 'ncs-cart' ),
    );

    if ( isset( $_POST ) && key_exists( 'value', $_POST ) && ! empty( $_POST['value'] ) ) {

        $id = intval( $_POST['value'] );

        if ( $id !== '0' ) {

            $fields = array();

            $items = get_post_meta($id, '_sc_pay_options', true);
            if(is_array($items)) {
                foreach ( $items as $item ) {
                    if ( $item['stripe_plan_id'] != null && $item['option_name'] != null ) {
                        $fields[] = array(
                            'value' => $item['stripe_plan_id'],
                            'text'  => $item['option_name'],
                        );
                    }

                    if ( $item['sale_stripe_plan_id'] != null && $item['sale_option_name'] != null ) {
                        $plans[] = array('id' => $item['sale_stripe_plan_id'], 'name' => $item['sale_option_name'].' '. __('(on sale price)','ncs-cart'));
                        $fields[] = array(
                            'value' => $item['sale_stripe_plan_id'],
                            'text'  => $item['sale_option_name'].' '. __('(on sale price)','ncs-cart'),
                        );
                    }
                }
            }
        }
    }

    echo wp_json_encode( $fields );
    die();
}