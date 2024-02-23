<?php

/*
* global functions
*/
add_action( 'wp_ajax_sc_unsubscribe_customer', 'sc_unsubscribe_customer' );
//unsubscribe stripe
add_action( 'wp_ajax_nopriv_sc_unsubscribe_customer', 'sc_unsubscribe_customer' );
//unsubscribe stripe
add_action( 'wp_ajax_sc_json_search_user', 'sc_json_search_user' );
function sc_json_search_user()
{
    $search_term = $_GET['term'];
    $usersArr = array();
    
    if ( !empty($search_term) ) {
        $users = get_users( array(
            'search'         => '*' . $search_term . '*',
            'search_columns' => array( 'user_login', 'user_email', 'display_name' ),
        ) );
        if ( !empty($users) ) {
            foreach ( (array) $users as $user ) {
                $usersArr[$user->ID] = $user->display_name . ' (' . $user->user_email . ')';
            }
        }
    }
    
    wp_send_json( $usersArr );
}

add_filter(
    'studiocart_order_form_fields',
    'studiocart_default_fields_filter',
    10,
    2
);
add_filter(
    'studiocart_order_form_address_fields',
    'studiocart_default_fields_filter',
    10,
    2
);
function studiocart_default_fields_filter( $fields, $scp )
{
    $new_fields = array();
    
    if ( isset( $scp->default_fields ) ) {
        foreach ( $scp->default_fields as $k => $f ) {
            $key = str_replace( '_', '', $k );
            
            if ( isset( $fields[$key] ) ) {
                $field = $fields[$key];
                
                if ( !isset( $scp->default_fields[$k]['default_field_disabled'] ) ) {
                    $field['label'] = $scp->default_fields[$k]['default_field_label'];
                    $field['required'] = isset( $scp->default_fields[$k]['default_field_required'] );
                    $field['cols'] = $scp->default_fields[$k]['default_field_size'];
                    if ( $k == 'state' ) {
                        $field['choices'][''] = $field['label'];
                    }
                    $new_fields[$k] = $field;
                }
            
            }
        
        }
        return $new_fields;
    } else {
        if ( isset( $scp->hide_fields ) ) {
            // backwards compatibility
            foreach ( $fields as $k => $f ) {
                if ( isset( $scp->hide_fields ) && isset( $scp->hide_fields[$k] ) ) {
                    unset( $fields[$k] );
                }
            }
        }
    }
    
    return $fields;
}

if ( !function_exists( 'sc_add_remove_activecampaign_subscriber' ) ) {
    function sc_add_remove_activecampaign_subscriber(
        $order_id,
        $service_id,
        $action_name,
        $list_id,
        $_sc_mail_tags,
        $_sc_mail_groups,
        $email,
        $phone,
        $fname,
        $lname,
        $fieldmap
    )
    {
        global  $wpdb ;
        if ( empty($service_id) || empty($action_name) || empty($email) ) {
            return;
        }
        try {
            //ACTIVECAMPAIGN
            $activecampaign_url = get_option( '_sc_activecampaign_url' );
            $activecampaign_secret_key = get_option( '_sc_activecampaign_secret_key' );
            require_once "vendor/activecampaign/api-php/includes/ActiveCampaign.class.php";
            $activecampaign = new ActiveCampaign( $activecampaign_url, $activecampaign_secret_key );
            $contact_add = array(
                'email'      => $email,
                'first_name' => $fname,
                'last_name'  => $lname,
                'phone'      => $phone,
            );
            
            if ( isset( $fieldmap ) && $fieldmap ) {
                $custom = [];
                $maps = explode( "\n", str_replace( "\r", "", esc_attr( $fieldmap ) ) );
                foreach ( $maps as $map ) {
                    $option = explode( ':', $map );
                    
                    if ( count( $option ) == 1 ) {
                        $custom[trim( $option[0] )] = trim( $option[0] );
                    } else {
                        $custom[trim( $option[0] )] = trim( $option[1] );
                    }
                
                }
                $map = array();
                $values = get_post_meta( $order_id, '_sc_custom_fields', true );
                $info = sc_webhook_order_body( $order_id );
                foreach ( $custom as $k => $v ) {
                    $k .= ',0';
                    
                    if ( $v && is_array( $values ) && isset( $values[$v] ) ) {
                        $map[$k] = $values[$v]['value'];
                    } else {
                        
                        if ( $v && isset( $info[$v] ) ) {
                            $map[$k] = $info[$v];
                            // order data
                        } else {
                            
                            if ( $v && preg_match( '/"([^"]+)"/', html_entity_decode( $v ), $val ) ) {
                                $map[$k] = trim( $val[1] );
                                // static value
                            } else {
                                
                                if ( $v && ($val = get_post_meta( $order_id, '_sc_' . $v, true )) ) {
                                    $map[$k] = $val;
                                    // meta key
                                }
                            
                            }
                        
                        }
                    
                    }
                
                }
                if ( !empty($map) ) {
                    $contact_add['field'] = $map;
                }
            }
            
            
            if ( !empty($list_id) ) {
                $log_entry = $email . __( ' activecampaign Listed: ', 'ncs-cart' ) . $list_id;
                sc_log_entry( $order_id, $log_entry );
                $list_id_array = explode( ',', $list_id );
                foreach ( $list_id_array as $list_id ) {
                    $list_id = str_replace( 'list-', '', $list_id );
                    
                    if ( $action_name == 'subscribed' ) {
                        $contact_add_list = array(
                            'p[' . $list_id . ']'      => $list_id,
                            'status[' . $list_id . ']' => 1,
                        );
                    } else {
                        $contact_add_list = array(
                            'p[' . $list_id . ']'      => $list_id,
                            'status[' . $list_id . ']' => 2,
                        );
                    }
                
                }
            }
            
            $contact_add = array_merge( $contact_add, $contact_add_list );
            $contact = $activecampaign->api( "contact/sync", $contact_add );
            
            if ( !empty($_sc_mail_tags) ) {
                $ac_tags = array_map( 'trim', explode( ',', $_sc_mail_tags ) );
                
                if ( isset( $contact->subscriber_id ) ) {
                    $tags = array(
                        'id'   => $contact->subscriber_id,
                        'tags' => $ac_tags,
                    );
                    
                    if ( $action_name == 'subscribed' ) {
                        $tagAdd = $activecampaign->api( "contact/tag_add", $tags );
                        $log_entry = $email . __( ' activecampaign tagged: ', 'ncs-cart' ) . $_sc_mail_tags;
                    } else {
                        $tagAdd = $activecampaign->api( "contact/tag_remove", $tags );
                        $log_entry = $email . __( ' activecampaign tag removed: ', 'ncs-cart' ) . $_sc_mail_tags;
                    }
                    
                    sc_log_entry( $order_id, $log_entry );
                }
            
            }
        
        } catch ( \Exception $e ) {
            //echo $e->getMessage(); //add custom message
            return;
        }
        return;
    }

}
if ( !function_exists( 'sc_add_remove_sendfox_subscriber' ) ) {
    function sc_add_remove_sendfox_subscriber(
        $order_id,
        $_sc_services,
        $_sc_service_action,
        $sendfox_list,
        $customerEmail,
        $first_name,
        $last_name
    )
    {
        if ( empty($sendfox_list) ) {
            return;
        }
        
        if ( $_sc_service_action == 'subscribed' ) {
            $contact = array(
                'email'      => $customerEmail,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'lists'      => array( $sendfox_list ),
            );
            $response = sc_sendfox_api_request( 'contacts', $contact, 'POST' );
            
            if ( !empty($response['status']) && $response['status'] === 'success' && !empty($response['result']) && !empty($response['result']['id']) && empty($response['result']['invalid_at']) ) {
                $msg = __( 'Contact successfully added to SendFox list ID: ', 'ncs-cart' ) . $sendfox_list;
            } else {
                $msg = __( 'Error adding contact to SendFox: ', 'ncs-cart' ) . $response['error_text'];
            }
        
        } else {
            $response = sc_sendfox_api_request( "contacts?email={$customerEmail}" );
            
            if ( !empty($response['status']) && $response['status'] === 'success' && !empty($response['result']) && !empty($response['result']['data'][0]['id']) ) {
                $contact_id = $response['result']['data'][0]['id'];
                $response = sc_sendfox_api_request( "lists/{$sendfox_list}/contacts/{$contact_id}", array(), 'DELETE' );
                
                if ( !empty($response['status']) && $response['status'] === 'success' && !empty($response['result']) && !empty($response['result']['id']) && empty($response['result']['invalid_at']) ) {
                    $msg = __( 'Contact successfully removed from SendFox, list ID: ', 'ncs-cart' ) . $sendfox_list;
                } else {
                    $msg = __( 'Error removing contact from SendFox: ', 'ncs-cart' ) . $response['error_text'];
                }
            
            } else {
                $msg = __( 'Unsubscribe failed because this email wasn\'t found in SendFox list: ', 'ncs-cart' ) . $sendfox_list;
            }
        
        }
        
        sc_log_entry( $order_id, $msg );
        return;
    }

}
if ( !function_exists( 'sc_add_remove_mailpoet_subscriber' ) ) {
    function sc_add_remove_mailpoet_subscriber(
        $order_id,
        $_sc_services,
        $_sc_service_action,
        $mailpoet_list,
        $customerEmail,
        $first_name,
        $last_name
    )
    {
        if ( empty($mailpoet_list) || !class_exists( 'MailPoet\\API\\API' ) ) {
            return;
        }
        $mailpoet_api = \MailPoet\API\API::MP( 'v1' );
        $mailpoet_lists = [ $mailpoet_list ];
        $subscriber = array(
            'email'      => $customerEmail,
            'first_name' => $first_name,
            'last_name'  => $last_name,
        );
        
        if ( $_sc_service_action == 'subscribed' ) {
            // Check if subscriber exists. If subscriber doesn't exist an exception is thrown
            try {
                $get_subscriber = $mailpoet_api->getSubscriber( $subscriber['email'] );
            } catch ( \Exception $e ) {
            }
            try {
                
                if ( !$get_subscriber ) {
                    // Subscriber doesn't exist let's create one
                    $mailpoet_api->addSubscriber( $subscriber, $mailpoet_lists );
                } else {
                    // In case subscriber exists just add him to new lists
                    $mailpoet_api->subscribeToLists( $subscriber['email'], $mailpoet_lists, array(
                        'send_confirmation_email' => false,
                    ) );
                }
                
                $msg = __( 'Contact successfully added to MailPoet list ID: ', 'ncs-cart' ) . $mailpoet_list;
            } catch ( \Exception $e ) {
                $error_message = $e->getMessage();
                $msg = __( 'Error adding contact to MailPoet: ', 'ncs-cart' ) . $error_message;
            }
        } else {
            // Check if subscriber exists. If subscriber doesn't exist, exit early.
            try {
                $get_subscriber = $mailpoet_api->getSubscriber( $subscriber['email'] );
            } catch ( \Exception $e ) {
                $error_message = $e->getMessage();
                echo  $msg = __( 'Error removing contact from MailPoet: ', 'ncs-cart' ) . $error_message ;
                sc_log_entry( $order_id, $msg );
                return;
            }
            try {
                if ( $get_subscriber ) {
                    $mailpoet_api->unsubscribeFromList( $subscriber['email'], $mailpoet_list );
                }
                $msg = __( 'Contact successfully removed from MailPoet list ID: ', 'ncs-cart' ) . $mailpoet_list;
            } catch ( \Exception $e ) {
                $error_message = $e->getMessage();
                $msg = __( 'Error removing contact from MailPoet: ', 'ncs-cart' ) . $error_message;
            }
        }
        
        sc_log_entry( $order_id, $msg );
        return;
    }

}
if ( !function_exists( 'sc_add_remove_to_tutor_course' ) ) {
    function sc_add_remove_to_tutor_course(
        $order_id,
        $_sc_services,
        $tutor_action,
        $tutor_course,
        $customerEmail,
        $first_name,
        $last_name,
        $user_role
    )
    {
        if ( empty($tutor_course) || !function_exists( 'tutor_utils' ) ) {
            return;
        }
        
        if ( $tutor_action == 'enroll' ) {
            if ( !($user_id = email_exists( $customerEmail )) ) {
                $user_id = sc_create_user(
                    $order_id,
                    $customerEmail,
                    $first_name,
                    $last_name,
                    $user_role
                );
            }
            $has_any_enrolled = tutor_utils()->has_any_enrolled( $tutor_course, $user_id );
            
            if ( !$has_any_enrolled ) {
                tutor_utils()->do_enroll( $tutor_course, $order_id, $user_id );
                tutor_utils()->complete_course_enroll( $order_id );
                $msg = __( 'Contact successfully enrolled to Tutor course ID: ', 'ncs-cart' ) . $tutor_course;
            } else {
                $msg = __( 'Contact already enrolled in Tutor course ID: ', 'ncs-cart' ) . $tutor_course;
            }
        
        } else {
            
            if ( $user_id = email_exists( $customerEmail ) ) {
                tutor_utils()->cancel_course_enrol( $tutor_course, $user_id, $tutor_action );
                $msg = __( 'Contact successfully removed from Tutor course ID: ', 'ncs-cart' ) . $tutor_course;
            } else {
                $msg = 'No Tutor LMS user found';
            }
        
        }
        
        sc_log_entry( $order_id, $msg );
        return;
    }

}
function sc_get_transaction_id( $order_id )
{
    if ( $txnid = get_post_meta( $order_id, '_sc_transaction_id', true ) ) {
        return $txnid;
    }
    $method = get_post_meta( $order_id, '_sc_pay_method', true );
    $txnid = false;
    switch ( $method ) {
        case 'stripe':
            
            if ( get_post_type( $order_id ) == 'sc_subscription' ) {
                $args = array(
                    'post_type'      => array( 'sc_order' ),
                    'orderby'        => 'date',
                    'order'          => 'ASC',
                    'posts_per_page' => 1,
                    'meta_query'     => array( array(
                    'key'   => '_sc_subscription_id',
                    'value' => $order_id,
                ) ),
                );
                $order_id = get_posts( $args )[0]->ID;
            }
            
            $txnid = get_post_meta( $order_id, '_sc_stripe_charge_id', true );
            break;
        case 'paypal':
            $txnid = get_post_meta( $order_id, '_sc_paypal_txn_id', true );
            break;
    }
    $txnid = apply_filters(
        'studiocart_transaction_id',
        $txnid,
        $method,
        $order_id
    );
    if ( $txnid ) {
        update_post_meta( $order_id, '_sc_transaction_id', $txnid );
    }
    return $txnid;
}

function sc_get_subscription_txn_id( $order_id, $subsription = false )
{
    if ( $txnid = get_post_meta( $order_id, '_sc_subscription_id', true ) ) {
        return $txnid;
    }
    $method = get_post_meta( $order_id, '_sc_pay_method', true );
    $txnid = false;
    
    if ( get_post_type( $order_id ) != 'sc_subscription' ) {
        $order_id = get_post_meta( $order_id, '_sc_subscription_id', true );
        if ( !$order_id ) {
            return false;
        }
    }
    
    switch ( $method ) {
        case 'stripe':
            $txnid = get_post_meta( $order_id, '_sc_stripe_subscription_id', true );
            if ( $txnid === false ) {
                $txnid = get_post_meta( $order_id, '_sc_stripe_charge_id', true );
            }
            break;
        case 'paypal':
            $txnid = get_post_meta( $order_id, '_sc_paypal_subscr_id', true );
            break;
    }
    $txnid = apply_filters(
        'studiocart_subscription_transaction_id',
        $txnid,
        $method,
        $order_id
    );
    if ( $txnid ) {
        update_post_meta( $order_id, '_sc_subscription_id', $txnid );
    }
    return $txnid;
}

if ( !function_exists( 'sc_add_remove_wlmember_to_level' ) ) {
    function sc_add_remove_wlmember_to_level(
        $order_id,
        $_sc_services,
        $wlm_action,
        $wlm_level,
        $customerEmail,
        $first_name,
        $last_name,
        $SendMail,
        $pending
    )
    {
        if ( empty($wlm_level) || !class_exists( 'WLMAPIMethods' ) ) {
            return;
        }
        global  $WishListMemberInstance ;
        $user_id = email_exists( $customerEmail );
        
        if ( $wlm_action == 'add' ) {
            
            if ( get_post_meta( $order_id, '_sc_subscription_id', true ) ) {
                $planid = get_post_meta( $order_id, '_sc_plan_id', true );
                $customerid = get_post_meta( $order_id, '_sc_customer_id', true );
                $txnid = $customerid . '-' . $planid;
            } else {
                $txnid = sc_get_transaction_id( $order_id );
            }
            
            $creds = sc_generate_login_creds( $customerEmail );
            
            if ( !$user_id ) {
                $args = array(
                    'user_login'          => $creds['username'],
                    'user_email'          => $customerEmail,
                    'user_pass'           => $creds['password'],
                    'last_name'           => $last_name,
                    'first_name'          => $first_name,
                    'display_name'        => $first_name . ' ' . $last_name,
                    'Levels'              => array( array( $wlm_level, $txnid ) ),
                    'SendMail'            => false,
                    'SendMailPerLevel'    => array(),
                    'wpm_registration_ip' => get_post_meta( $order_id, '_sc_ip_address', true ),
                    'custom_sc_order_id'  => $order_id,
                );
                if ( $SendMail ) {
                    
                    if ( $SendMail == 'level' ) {
                        $args['SendMailPerLevel'] = array( $wlm_level );
                    } else {
                        $args['SendMail'] = true;
                    }
                
                }
                // add custom fields
                $custom_fields = get_post_meta( $order_id, '_sc_custom_fields', true );
                if ( $custom_fields ) {
                    foreach ( $custom_fields as $k => $field ) {
                        $args['custom_' . $k] = $field['value'];
                    }
                }
                // add address fields
                $address_fields = array(
                    'address1',
                    'address2',
                    'city',
                    'state',
                    'zip',
                    'country'
                );
                foreach ( $address_fields as $info ) {
                    $val = get_post_meta( $order_id, '_sc_' . $info, true );
                    if ( $val ) {
                        $args[$info] = $val;
                    }
                }
                $member = wlmapi_add_member( $args );
                $user_id = $member['member'][0]['ID'];
                sc_maybe_auto_login_user( $user_id, $order_id );
                update_post_meta( $order_id, '_sc_user_account', $user_id );
                $sub_id = false;
                
                if ( get_post_type( $order_id ) == 'sc_order' ) {
                    $sub_id = get_post_meta( $order_id, '_sc_subscription', true );
                    // add user to subscription if exists
                    if ( $sub_id ) {
                        update_post_meta( $sub_id, '_sc_user_account', $user_id );
                    }
                }
                
                $msg = __( 'Contact created and successfully added to Wishlist Level ID: ', 'ncs-cart' ) . $wlm_level;
            } else {
                $args = array(
                    'Users'     => array( $user_id ),
                    'Pending'   => $pending,
                    'Cancelled' => false,
                );
                $members = wlmapi_add_member_to_level( $wlm_level, $args );
                $WishListMemberInstance->SetMembershipLevelTxnID( $user_id, $wlm_level, $txnid );
                //update txnid
                $args = array(
                    'user_email'         => $customerEmail,
                    'custom_sc_order_id' => $order_id,
                );
                // add custom fields
                $custom_fields = get_post_meta( $order_id, '_sc_custom_fields', true );
                if ( $custom_fields ) {
                    foreach ( $custom_fields as $k => $field ) {
                        $args['custom_' . $k] = $field['value'];
                    }
                }
                $member = wlmapi_update_member( $user_id, $args );
                $msg = __( 'Contact successfully added to Wishlist Level ID: ', 'ncs-cart' ) . $wlm_level;
            }
            
            // add stripe customer ID
            
            if ( $customerid ) {
                $stripe_cust_id = $customerid;
                $WishListMemberInstance->Update_UserMeta( $user_id, 'stripe_cust_id', $stripe_cust_id );
            }
        
        } else {
            
            if ( $user_id && $wlm_action == 'remove' ) {
                $member = wlmapi_remove_member_from_level( $wlm_level, $user_id );
                $msg = __( 'Contact successfully removed from Wishlist Level ID: ', 'ncs-cart' ) . $wlm_level;
            } else {
                
                if ( $user_id && $wlm_action == 'cancel' ) {
                    $args = array(
                        'Users'            => array( $user_id ),
                        'Cancelled'        => true,
                        'SendMail'         => false,
                        'SendMailPerLevel' => array(),
                    );
                    if ( $SendMail ) {
                        
                        if ( $SendMail == 'level' ) {
                            $args['SendMailPerLevel'] = array( $wlm_level );
                        } else {
                            $args['SendMail'] = true;
                        }
                    
                    }
                    $members = wlmapi_add_member_to_level( $wlm_level, $args );
                    $msg = __( 'Contact successfully canceled from Wishlist Level ID: ', 'ncs-cart' ) . $wlm_level;
                }
            
            }
        
        }
        
        sc_log_entry( $order_id, $msg );
        return;
    }

}
// removing action to prevent error caused by conflict with $_POST['rcp_level']
add_action( 'init', function () {
    if ( class_exists( 'RCP_Levels' ) && isset( $_POST['_sc_product_name'] ) ) {
        remove_action( 'init', 'rcp_setup_registration_init' );
    }
}, 1 );
if ( !function_exists( 'sc_add_remove_to_rcp_level' ) ) {
    function sc_add_remove_to_rcp_level( $order, $level, $status )
    {
        if ( empty($level) || !function_exists( 'rcp_add_membership' ) ) {
            return;
        }
        $order_id = $order['id'];
        $user_id = sc_get_order_user_id( $order, $create = true );
        // Retrieve the customer record associated with this user ID.
        $customer = rcp_get_customer_by_user_id( $user_id );
        // create customer if doesn't exist
        
        if ( empty($customer) ) {
            // If no customer record is found and status is pending or active, create customer and add membership
            $customer_id = rcp_add_customer( array(
                'user_id' => $user_id,
            ) );
            $msg = sprintf( __( 'Restrict Content Pro customer ID: %s created', 'ncs-cart' ), $customer_id );
            sc_log_entry( $order_id, $msg );
        } else {
            $customer_id = $customer->get_id();
        }
        
        // Once you have the customer object, you can get the customer's memberships.
        $memberships = rcp_get_memberships( array(
            'customer_id' => $customer_id,
            'object_id'   => $level,
        ) );
        
        if ( !empty($memberships) ) {
            $args = array(
                'status' => $status,
            );
            $membership_id = $memberships[0]->get_id();
            $update = rcp_update_membership( $membership_id, $args );
            
            if ( $update ) {
                $msg = sprintf( __( 'Restrict Content Pro membership ID: %s updated to status %s', 'ncs-cart' ), $membership_id, $status );
            } else {
                $msg = 'Something went wrong when updating Restrict Content Pro membership';
            }
            
            sc_log_entry( $order_id, $msg );
            return;
        } else {
            $args = array(
                'customer_id'     => $customer_id,
                'object_id'       => $level,
                'status'          => $status,
                'expiration_date' => 'none',
            );
            $membership_id = rcp_add_membership( $args );
            
            if ( $membership_id ) {
                $msg = sprintf( __( 'Restrict Content Pro membership ID: %s created with status %s.', 'ncs-cart' ), $membership_id, $status );
            } else {
                $msg = 'Something went wrong when creating Restrict Content Pro membership';
            }
            
            sc_log_entry( $order_id, $msg );
            return;
        }
    
    }

}
if ( !function_exists( 'sc_add_remove_membervault_subscriber' ) ) {
    function sc_add_remove_membervault_subscriber(
        $order_id,
        $service_id,
        $action_name,
        $member_vault_course_id,
        $email,
        $phone,
        $fname,
        $lname
    )
    {
        global  $wpdb ;
        if ( empty($service_id) || empty($action_name) || empty($email) || empty($member_vault_course_id) ) {
            return;
        }
        $member_vault_api_key = get_option( '_sc_member_vault_api_key' );
        $business_name = get_option( '_sc_membervault_name' );
        $url = ( strpos( $business_name, 'http' ) !== false ? $business_name : "https://{$business_name}.vipmembervault.com/" );
        
        if ( !empty($member_vault_api_key) ) {
            $ids = explode( ',', $member_vault_course_id );
            foreach ( $ids as $mvid ) {
                $url .= "/api/{$action_name}/?apikey={$member_vault_api_key}&course_id={$mvid}&email={$email}&first_name={$fname}&last_name={$lname}";
                $apiUrl = esc_url_raw( $url );
                $response = wp_remote_get( $apiUrl );
                $responseBody = wp_remote_retrieve_body( $response );
                $result = json_decode( $responseBody, true );
                
                if ( is_array( $result ) && !is_wp_error( $result ) ) {
                    
                    if ( is_numeric( $result['user_id'] ) ) {
                        $status = ( $action_name == 'add_user' ? __( ' added to', 'ncs-cart' ) : __( ' removed from', 'ncs-cart' ) );
                        sc_log_entry( $order_id, $email . $status . __( ' Membervault course ID: ', 'ncs-cart' ) . $mvid );
                    } else {
                        sc_log_entry( $order_id, __( 'Membervault add failed' . ': ' . $result['user_id'], 'ncs-cart' ) );
                    }
                
                } else {
                    
                    if ( $action_name == 'add_user' ) {
                        sc_log_entry( $order_id, __( 'Membervault add failed', 'ncs-cart' ) );
                    } else {
                        sc_log_entry( $order_id, __( 'Membervault remove failed', 'ncs-cart' ) );
                    }
                
                }
            
            }
        }
        
        return;
    }

}
if ( !function_exists( 'sc_add_remove_convertkit_subscriber' ) ) {
    function sc_add_remove_convertkit_subscriber(
        $order_id,
        $service_id,
        $action_name,
        $_sc_mail_forms,
        $_sc_mail_tags,
        $email,
        $phone,
        $fname,
        $lname
    )
    {
        global  $wpdb ;
        if ( empty($service_id) || empty($action_name) || empty($email) ) {
            return;
        }
        try {
            //CONVERKIT
            $apikey = get_option( '_sc_converkit_api' );
            $secretKey = get_option( '_sc_converkit_secret_key' );
            $full_name = $fname . ' ' . $lname;
            //if subscribe
            
            if ( $action_name == 'subscribed' ) {
                $data = array(
                    'api_key'    => $apikey,
                    'first_name' => $fname,
                    'email'      => $email,
                    'fields'     => array(
                    'phone' => $phone,
                ),
                );
                
                if ( !empty($_sc_mail_forms) ) {
                    $url = "https://api.convertkit.com/v3/forms/{$_sc_mail_forms}/subscribe";
                    $log_entry = __( 'Subscriber added to ConvertKit form.', 'ncs-cart' );
                    
                    if ( !empty($_sc_mail_tags) ) {
                        $log_entry = __( 'Subscriber added to ConvertKit Form and tagged.', 'ncs-cart' );
                        $data["tags"] = (array) $_sc_mail_tags;
                    }
                    
                    $response = wp_remote_post( $url, array(
                        'method'  => 'POST',
                        'headers' => array(
                        'Content-Type' => 'application/json; charset=utf-8',
                    ),
                        'body'    => wp_json_encode( $data ),
                    ) );
                    
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        sc_log_entry( $order_id, "Something went wrong adding subscriber to ConvertKit Form: {$error_message}" );
                    } else {
                        sc_log_entry( $order_id, $log_entry );
                    }
                
                } else {
                    
                    if ( isset( $_sc_mail_tags ) && is_array( $_sc_mail_tags ) && is_countable( $_sc_mail_tags ) && !empty($_sc_mail_tags[0]) ) {
                        $url = "https://api.convertkit.com/v3/tags/{$_sc_mail_tags[0]}/subscribe";
                        $data['api_secret'] = $secretKey;
                        $response = wp_remote_post( $url, array(
                            'method'  => 'POST',
                            'headers' => array(
                            'Content-Type' => 'application/json; charset=utf-8',
                        ),
                            'body'    => wp_json_encode( $data ),
                        ) );
                        
                        if ( is_wp_error( $response ) ) {
                            $error_message = $response->get_error_message();
                            sc_log_entry( $order_id, "Something went wrong with adding ConvertKit tag: {$error_message}" );
                        } else {
                            
                            if ( $response['response']['code'] === 200 ) {
                                $log_entry = __( 'ConvertKit subscriber tagged', 'ncs-cart' );
                                sc_log_entry( $order_id, $log_entry );
                            } else {
                                $error_data = json_decode( $response['body'], true );
                                $error_message = ( isset( $error_data['error'] ) ? $error_data['error'] : 'Something went wrong with adding ConvertKit tag' );
                                sc_log_entry( $order_id, "ConvertKit error: {$error_message}" );
                            }
                        
                        }
                    
                    }
                
                }
            
            } else {
                //remove contact
                
                if ( isset( $_sc_mail_tags ) && is_array( $_sc_mail_tags ) && is_countable( $_sc_mail_tags ) && !empty($_sc_mail_tags[0]) ) {
                    $url = "https://api.convertkit.com/v3/tags/{$_sc_mail_tags[0]}/unsubscribe";
                    $data = array(
                        'api_secret' => $secretKey,
                        'email'      => $email,
                    );
                    $response = wp_remote_post( $url, array(
                        'method'  => 'POST',
                        'headers' => array(
                        'Content-Type' => 'application/json; charset=utf-8',
                    ),
                        'body'    => wp_json_encode( $data ),
                    ) );
                    
                    if ( is_wp_error( $response ) ) {
                        $error_message = $response->get_error_message();
                        sc_log_entry( $order_id, "Something went wrong with adding ConvertKit tag: {$error_message}" );
                    } else {
                        
                        if ( $response['response']['code'] === 200 ) {
                            $log_entry = __( 'ConvertKit tag removed from subscriber.', 'ncs-cart' );
                            sc_log_entry( $order_id, $log_entry );
                        } else {
                            $error_data = json_decode( $response['body'], true );
                            $error_message = ( isset( $error_data['error'] ) ? $error_data['error'] : 'Something went wrong with adding ConvertKit tag' );
                            sc_log_entry( $order_id, "ConvertKit error: {$error_message}" );
                        }
                    
                    }
                
                }
            
            }
        
        } catch ( \Exception $e ) {
            //echo $e->getMessage(); //add custom message
            return;
        }
        return;
    }

}
if ( !function_exists( 'sc_add_remove_mailchimp_subscriber' ) ) {
    function sc_add_remove_mailchimp_subscriber(
        $order_id,
        $service_id,
        $action_name,
        $list_id,
        $_sc_mail_tags,
        $_sc_mail_groups,
        $email,
        $phone,
        $fname,
        $lname,
        $intg,
        $order
    )
    {
        global  $wpdb ;
        if ( empty($service_id) || empty($action_name) || empty($list_id) || empty($email) ) {
            return;
        }
        //MAILCHIMP
        
        if ( $service_id == 'mailchimp' ) {
            $mailchimp_apikey = get_option( '_sc_mailchimp_api' );
            try {
                $MailChimp = new \DrewM\MailChimp\MailChimp( $mailchimp_apikey );
                //if subscribe
                $mergedata = [
                    'FNAME' => $fname,
                    'LNAME' => $lname,
                ];
                if ( isset( $intg['mc_phone_tag'] ) && $intg['mc_phone_tag'] && $phone ) {
                    $mergedata[$intg['mc_phone_tag']] = $phone;
                }
                $mergedata = apply_filters( 'sc_mailchimp_merge_data', $mergedata, $order_id );
                
                if ( $action_name == 'subscribed' ) {
                    $result = $MailChimp->post( "lists/{$list_id}/members", [
                        'email_address' => $email,
                        'merge_fields'  => $mergedata,
                        'status'        => 'subscribed',
                    ] );
                    //check status
                    
                    if ( $MailChimp->success() ) {
                        $log_entry = $email . __( ' added to list ID: ', 'ncs-cart' ) . $list_id;
                        sc_log_entry( $order_id, $log_entry );
                    }
                    
                    
                    if ( !empty($_sc_mail_groups) ) {
                        $groups = ( is_array( $_sc_mail_groups ) ? $_sc_mail_groups : explode( ',', $_sc_mail_groups ) );
                        foreach ( $groups as $key => $value ) {
                            $subscriber_hash = $MailChimp->subscriberHash( $email );
                            $result = $MailChimp->patch( "lists/{$list_id}/members/{$subscriber_hash}", [
                                'merge_fields' => $mergedata,
                                'interests'    => [
                                $value => true,
                            ],
                            ] );
                        }
                        $log_entry = __( 'MailChimp added ', 'ncs-cart' ) . $email . __( ' to group: ', 'ncs-cart' ) . $_sc_mail_groups;
                        sc_log_entry( $order_id, $log_entry );
                    }
                    
                    
                    if ( !empty($_sc_mail_tags) ) {
                        $tags = ( is_array( $_sc_mail_tags ) ? $_sc_mail_tags : explode( ',', $_sc_mail_tags ) );
                        foreach ( $tags as $key => $value ) {
                            $value = str_replace( 'tag-', '', $value );
                            $result = $MailChimp->post( "lists/{$list_id}/segments/{$value}", [
                                'members_to_add' => [ $email ],
                            ] );
                        }
                        $log_entry = $email . __( ' MailChimp tagged: ', 'ncs-cart' ) . $_sc_mail_tags;
                        sc_log_entry( $order_id, $log_entry );
                    }
                
                } else {
                    //remove contact
                    $subscriber_hash = $MailChimp->subscriberHash( $email );
                    $MailChimp->delete( "lists/{$list_id}/members/{$subscriber_hash}" );
                    //check and log status
                    
                    if ( $MailChimp->success() ) {
                        $log_entry = $email . __( ' removed from list ID: ', 'ncs-cart' ) . $list_id;
                        sc_log_entry( $order_id, $log_entry );
                    } else {
                        sc_log_entry( $order_id, $MailChimp->getLastError() );
                    }
                
                }
            
            } catch ( \Exception $e ) {
                return;
                //echo $e->getMessage(); //add custom message
            }
        }
        
        return;
    }

}
function sc_order_log( $order_id )
{
    $log_entries = get_post_meta( $order_id, '_sc_order_log', true );
    if ( !is_array( $log_entries ) ) {
        $log_entries = array();
    }
    return $log_entries;
}

function sc_log_entry( $order_id, $entry )
{
    if ( !$order_id ) {
        return;
    }
    $log_entries = sc_order_log( $order_id );
    $log_entries[time() . ' - sc' . rand()] = sanitize_text_field( $entry );
    update_post_meta( $order_id, '_sc_order_log', $log_entries );
}

function sc_format_number( $amt )
{
    
    if ( $amt === '' ) {
        return '';
    } else {
        if ( $amt === '0' ) {
            return 0;
        }
    }
    
    $num = get_option( '_sc_decimal_number' );
    $decinum = ( $num === '0' || !empty($num) ? intval( get_option( '_sc_decimal_number' ) ) : 2 );
    $decisep = ( !empty(get_option( '_sc_decimal_separator' )) ? get_option( '_sc_decimal_separator' ) : '.' );
    $thousep = ( !empty(get_option( '_sc_thousand_separator' )) ? get_option( '_sc_thousand_separator' ) : ',' );
    $amt = (double) $amt;
    $formatted_amt = number_format(
        $amt,
        $decinum,
        $decisep,
        $thousep
    );
    return $formatted_amt;
}

function sc_format_price( $amt, $html = true )
{
    global  $sc_currency_symbol ;
    if ( $amt === '' ) {
        return '';
    }
    $position = get_option( '_sc_currency_position' );
    $price = '';
    $symbol = sc_get_currency_symbol();
    if ( $html ) {
        $symbol = '<span class="sc-Price-currencySymbol">' . $symbol . '</span>';
    }
    // left positioned currency
    
    if ( $position != 'right' && $position != 'right-space' ) {
        $price .= $symbol;
        // with space
        if ( $position == 'left-space' ) {
            $price .= ' ';
        }
    }
    
    // format price
    $price .= sc_format_number( $amt );
    // right positioned currency
    
    if ( $position == 'right' || $position == 'right-space' ) {
        // with space
        if ( $position == 'right-space' ) {
            $price .= ' ';
        }
        $price .= $symbol;
    }
    
    return $price;
}

function sc_formatted_price( $price )
{
    echo  sc_format_price( $price ) ;
}

function sc_format_stripe_number( $amount, $sc_currency = 'USD' )
{
    $zero_decimal_currency = get_sc_zero_decimal_currency();
    if ( !in_array( $sc_currency, $zero_decimal_currency ) ) {
        $amount = $amount / 100;
    }
    return sc_format_number( $amount );
}

function sc_get_currency_symbol()
{
    global  $sc_currency_symbol ;
    return $sc_currency_symbol;
}

function sc_currency_settings()
{
    $position = get_option( '_sc_currency_position' );
    $thousep = get_option( '_sc_thousand_separator' );
    $decisep = get_option( '_sc_decimal_separator' );
    $decinum = intval( get_option( '_sc_decimal_number' ) );
    if ( !$position ) {
        $position = '';
    }
    if ( !$thousep ) {
        $thousep = ',';
    }
    if ( !$decisep ) {
        $decisep = '.';
    }
    return [
        'symbol'   => sc_get_currency_symbol(),
        'position' => $position,
        'thousep'  => $thousep,
        'decisep'  => $decisep,
        'decinum'  => $decinum,
    ];
}

function sc_get_user_phone( $user_id )
{
    global  $wpdb ;
    $user_phone = get_user_meta( $user_id, '_sc_phone', true );
    
    if ( !$user_phone ) {
        $query = "SELECT max(post_id) FROM {$wpdb->postmeta} WHERE `meta_key` = '_sc_user_account' AND `meta_value` = {$user_id}";
        $post_id_meta = $wpdb->get_var( $query );
        $user_phone = get_post_meta( $post_id_meta, '_sc_phone', true );
        add_user_meta(
            $user_id,
            '_sc_phone',
            $user_phone,
            true
        );
    }
    
    return $user_phone;
}

function sc_get_user_address( $user_id )
{
    global  $wpdb ;
    $user_address = get_user_meta( $user_id, '_sc_address', true );
    
    if ( $user_address ) {
        $address = array(
            'address'   => $user_address,
            'address_1' => get_user_meta( $user_id, '_sc_address_1', true ),
            'address_2' => get_user_meta( $user_id, '_sc_address_2', true ),
            'city'      => get_user_meta( $user_id, '_sc_city', true ),
            'state'     => get_user_meta( $user_id, '_sc_state', true ),
            'zip'       => get_user_meta( $user_id, '_sc_zip', true ),
            'country'   => get_user_meta( $user_id, '_sc_country', true ),
        );
        return $address;
    } else {
        $query = "SELECT max(post_id) FROM {$wpdb->postmeta} WHERE `meta_key` = '_sc_user_account' AND `meta_value` = {$user_id}";
        $post_id_meta = $wpdb->get_var( $query );
        $address_1 = get_post_meta( $post_id_meta, '_sc_address1', true );
        $address_2 = get_post_meta( $post_id_meta, '_sc_address2', true );
        $city = get_post_meta( $post_id_meta, '_sc_city', true );
        $state = get_post_meta( $post_id_meta, '_sc_state', true );
        $zip = get_post_meta( $post_id_meta, '_sc_zip', true );
        $country = get_post_meta( $post_id_meta, '_sc_country', true );
        
        if ( $address_1 || $city || $state || $zip || $country ) {
            update_user_meta( $user_id, '_sc_address_1', $address_1 );
            update_user_meta( $user_id, '_sc_address_2', $address_2 );
            update_user_meta( $user_id, '_sc_city', $city );
            update_user_meta( $user_id, '_sc_state', $state );
            update_user_meta( $user_id, '_sc_zip', $zip );
            update_user_meta( $user_id, '_sc_country', $country );
            $address = array(
                'address_1' => $address_1,
                'address_2' => $address_2,
                'city'      => $city,
                'state'     => $state,
                'zip'       => $zip,
                'country'   => $country,
            );
            $address['address'] = 1;
            update_user_meta( $user_id, '_sc_address', $address['address'] );
            return $address;
        } else {
            return false;
        }
    
    }

}

function sc_format_address( $address )
{
    $order = (object) $address;
    $str = '';
    
    if ( isset( $order->address1 ) || isset( $order->city ) || isset( $order->state ) || isset( $order->zip ) || isset( $order->country ) ) {
        if ( isset( $order->address1 ) && $order->address1 ) {
            $str .= $order->address1 . '<br/>';
        }
        if ( isset( $order->address2 ) && $order->address2 ) {
            $str .= $order->address2 . '<br/>';
        }
        
        if ( isset( $order->city ) || isset( $order->state ) || isset( $order->zip ) ) {
            if ( isset( $order->city ) && $order->city ) {
                $str .= $order->city;
            }
            
            if ( isset( $order->state ) && $order->state ) {
                if ( $str != '' ) {
                    $str .= ', ';
                }
                $str .= $order->state;
            }
            
            
            if ( isset( $order->zip ) && $order->zip ) {
                if ( $str != '' ) {
                    $str .= ' ';
                }
                $str .= $order->zip;
            }
            
            if ( $str != '' ) {
                $str .= '<br>';
            }
        }
        
        if ( isset( $order->country ) && $order->country ) {
            $str .= $order->country . '<br/>';
        }
    }
    
    return $str;
}

function sc_order_address( $id )
{
    $order = sc_setup_order( $id );
    $str = '';
    
    if ( isset( $order->address1 ) || isset( $order->city ) || isset( $order->state ) || isset( $order->zip ) || isset( $order->country ) ) {
        if ( isset( $order->address1 ) && $order->address1 ) {
            $str .= $order->address1 . '<br/>';
        }
        if ( isset( $order->address2 ) && $order->address2 ) {
            $str .= $order->address2 . '<br/>';
        }
        
        if ( isset( $order->city ) || isset( $order->state ) || isset( $order->zip ) ) {
            if ( isset( $order->city ) && $order->city ) {
                $str .= $order->city;
            }
            
            if ( isset( $order->state ) && $order->state ) {
                if ( $str != '' ) {
                    $str .= ', ';
                }
                $str .= $order->state;
            }
            
            
            if ( isset( $order->zip ) && $order->zip ) {
                if ( $str != '' ) {
                    $str .= ' ';
                }
                $str .= $order->zip;
            }
            
            if ( $str != '' ) {
                $str .= '<br>';
            }
        }
        
        if ( isset( $order->country ) && $order->country ) {
            $str .= $order->country . '<br/>';
        }
    }
    
    return $str;
}

function sc_maybe_rebuild_custom_post_data( $post_id )
{
    $custom_fields = get_post_meta( $post_id, '_sc_custom_fields_post_data', true );
    
    if ( !$custom_fields ) {
        // none found here, check parent subscription for info
        $sub_id = get_post_meta( $post_id, '_sc_subscription_id', true );
        if ( $sub_id && ($custom_fields = get_post_meta( $sub_id, '_sc_custom_fields_post_data', true )) ) {
            $post_id = $sub_id;
        }
    }
    
    
    if ( $custom_fields ) {
        // set up post data for user account creation
        foreach ( $custom_fields as $k => $v ) {
            $_POST[$k] = $v;
        }
        // don't need this potentially sensitive info anymore
        delete_post_meta( $post_id, '_sc_custom_fields_post_data' );
        return true;
    } else {
        if ( $login = get_post_meta( $post_id, '_sc_auto_login', true ) ) {
            $_POST['sc-auto-login'] = $login;
        }
        return false;
    }

}

function sc_get_public_product_name( $id = false )
{
    global  $post ;
    if ( !$id ) {
        $id = $post->ID;
    }
    if ( get_post_type( $id ) == 'sc_product' && ($name = get_post_meta( $id, '_sc_product_name', true )) ) {
        return $name;
    }
    return get_the_title( $id );
}

function sc_trigger_integrations( $status, $order_info )
{
    $event_type = 'order';
    
    if ( $status == 'lead' ) {
        do_action(
            'sc_order_lead',
            $status,
            $order_info,
            'main'
        );
    } else {
        // setup order object
        
        if ( is_numeric( $order_info ) || is_array( $order_info ) && !isset( $order_info['option_id'] ) ) {
            // reset array to just the ID
            if ( is_array( $order_info ) ) {
                $order_info = $order_info['ID'];
            }
            
            if ( get_post_type( $order_info ) == 'sc_order' ) {
                $order = new ScrtOrder( $order_info );
                $order = apply_filters( 'studiocart_order', $order );
            } else {
                $order = new ScrtSubscription( $order_info );
            }
            
            $order_info = $order->get_data();
        }
        
        // set event type
        
        if ( isset( $order_info['renewal_order'] ) && $order_info['renewal_order'] ) {
            $event_type = 'renewal';
        } else {
            if ( get_post_type( $order_info['ID'] ) == 'sc_subscription' ) {
                $event_type = 'subscription';
            }
        }
        
        // set order type
        
        if ( isset( $order_info['us_parent'] ) ) {
            $order_info['order_type'] = 'upsell';
        } else {
            if ( isset( $order_info['ds_parent'] ) ) {
                $order_info['order_type'] = 'downsell';
            }
        }
        
        // also check subscription (if exists) to see if this is an upsell (we shouldn't need this anymore)
        
        if ( (!isset( $order_info['order_type'] ) || $order_info['order_type'] == 'main') && isset( $order_info['transaction_id'] ) && isset( $order_info['subscription_id'] ) ) {
            $sub_id = $order_info['subscription_id'];
            
            if ( get_post_meta( $sub_id, '_sc_us_parent', true ) ) {
                $order_info['order_type'] = 'upsell';
            } else {
                if ( get_post_meta( $sub_id, '_sc_ds_parent', true ) ) {
                    $order_info['order_type'] = 'downsell';
                }
            }
        
        }
        
        if ( $order_info['pay_method'] == 'cod' && $status == 'pending-payment' ) {
            $status = 'pending';
        }
        $order_type = 'main';
        // get correct action
        switch ( $status ) {
            case 'pending':
                $action = 'sc_order_pending';
                break;
            case 'paid':
                
                if ( $event_type == 'renewal' ) {
                    $action = 'sc_renewal_payment';
                    $status = 'renewal';
                } else {
                    $action = 'sc_order_complete';
                }
                
                break;
            case 'completed':
                
                if ( $event_type == 'subscription' ) {
                    $action = 'sc_subscription_completed';
                } else {
                    $action = 'sc_order_marked_complete';
                }
                
                break;
            case 'trialing':
                $action = 'sc_subscription_trialing';
                break;
            case 'active':
                $action = 'sc_subscription_active';
                break;
            case 'canceled':
                $action = 'sc_subscription_canceled';
                break;
            case 'paused':
                $action = 'sc_subscription_paused';
                break;
            case 'past_due':
                $action = 'sc_subscription_past_due';
                break;
            case 'trialing':
                $action = 'sc_subscription_trialing';
                break;
            case 'renewal':
                $action = 'sc_renewal_payment';
                break;
            case 'failed' && $event_type == 'renewal':
                $action = 'sc_renewal_failed';
                break;
            case 'uncollectible' && $event_type == 'renewal':
                $action = 'sc_renewal_uncollectible';
                break;
            case 'refunded':
                $action = 'sc_order_refunded';
                break;
            default:
                $action = false;
                break;
        }
        
        if ( $action ) {
            $renewal_types = array( 'sc_renewal_payment', 'sc_renewal_failed', 'sc_renewal_uncollectible' );
            // set order amount
            if ( !isset( $order_info['amount'] ) ) {
                $order_info['amount'] = get_post_meta( $order_info['ID'], '_sc_amount', true );
            }
            // set order type
            
            if ( isset( $order_info['order_type'] ) && !in_array( $action, $renewal_types ) ) {
                $order_type = $order_info['order_type'];
                //$order_info['plan_id'] = $order_info['order_type'];
            }
            
            do_action(
                $action,
                $status,
                $order_info,
                $order_type
            );
        }
    
    }
    
    // hook for after integrations have run (emails, webhooks, etc.)
    do_action(
        'sc_run_after_integrations',
        $status,
        $order_info,
        $event_type
    );
}

function sc_consent_services()
{
    return apply_filters( 'sc_show_optin_checkbox_services', array(
        'activecampaign',
        'convertkit',
        'mailchimp',
        'mailpoet',
        'sendfox',
        'fluentcrm'
    ) );
}

function sc_matched_plan( $order_data, $plan_ids, $order_ids = array() )
{
    $matched_plan = false;
    $order_ids = (array) $order_ids;
    if ( is_object( $order_data ) ) {
        $order_data = $order_data->get_data();
    }
    
    if ( $order_items = sc_get_order_items( $order_data['ID'] ) ) {
        $price_ids = wp_list_pluck( $order_items, 'price_id' );
        $item_types = wp_list_pluck( $order_items, 'item_type' );
        $order_ids = array_merge( $order_ids, $price_ids, $item_types );
        $matched_plan = sc_match_plan( $plan_ids, $order_ids );
    } else {
        $order_ids[] = $order_data['plan_id'];
        // Stripe ID, remove in 3.0
        $order_ids[] = $order_data['option_id'];
        $matched_plan = sc_match_plan( $plan_ids, $order_ids );
    }
    
    return $matched_plan;
}

function sc_match_plan( $plan_ids, $order_price_ids )
{
    $matched_plan = false;
    
    if ( empty($plan_ids) || in_array( '', $plan_ids ) ) {
        return true;
    } else {
        foreach ( $order_price_ids as $id ) {
            if ( in_array( $id, $plan_ids ) || in_array( $id . '_sale', $plan_ids ) ) {
                return true;
            }
        }
    }
    
    return $matched_plan;
}

function sc_do_integrations( $sc_product_id, $order, $trigger = 'purchased' )
{
    $customerEmail = $order['email'];
    $phone = $order['phone'];
    $first_name = $order['first_name'];
    $last_name = $order['last_name'];
    $order_type = $order['order_type'] ?? 'main';
    
    if ( $trigger != 'lead' ) {
        $order_id = $order['id'];
        $plan_id = $order['plan_id'];
        $option_id = ( isset( $order['option_id'] ) ? $order['option_id'] : get_post_meta( $order_id, '_sc_option_id', true ) );
        if ( !isset( $order['amount'] ) ) {
            $order['amount'] = get_post_meta( $order['id'], '_sc_amount', true );
        }
    }
    
    $integrations = get_post_meta( $sc_product_id, '_sc_integrations', true );
    //get integration meta mailchimp
    
    if ( $integrations ) {
        foreach ( $integrations as $ind => $intg ) {
            $_sc_services = ( isset( $intg['services'] ) ? $intg['services'] : "" );
            //mailchimp/converkit
            // do we need consent to run this integration?
            
            if ( get_post_meta( $sc_product_id, '_sc_show_optin_cb', true ) ) {
                $consent = ( strtolower( $order['consent'] ) == 'yes' ? true : false );
                $consent_services = sc_consent_services();
                if ( in_array( $intg['services'], $consent_services ) && isset( $intg['require_optin'] ) && !$consent ) {
                    continue;
                }
            }
            
            $_sc_service_trigger = ( isset( $intg['service_trigger'] ) ? (array) $intg['service_trigger'] : array() );
            //purchase or refund
            $_sc_service_action = ( isset( $intg['service_action'] ) ? $intg['service_action'] : "" );
            //subscribe or unsubscribe
            $_sc_plan_ids = ( isset( $intg['int_plan'] ) && $trigger != 'lead' ? (array) $intg['int_plan'] : array() );
            //payment plan
            $_sc_mail_list = ( isset( $intg['mail_list'] ) ? $intg['mail_list'] : "" );
            //mailchimp list ID
            $_sc_mail_tags = ( isset( $intg['mail_tags'] ) ? $intg['mail_tags'] : "" );
            //mailchimp list ID
            $_sc_mail_groups = ( isset( $intg['mail_groups'] ) ? $intg['mail_groups'] : "" );
            //mailchimp list ID
            $_sc_converkit_tags = ( isset( $intg['converkit_tags'] ) ? $intg['converkit_tags'] : "" );
            //converkit list ID
            $_sc_converkit_forms = ( isset( $intg['convertkit_forms'] ) ? $intg['convertkit_forms'] : "" );
            //converkit list ID
            $activecampaign_lists = ( isset( $intg['activecampaign_lists'] ) ? $intg['activecampaign_lists'] : "" );
            //activecampaign list ID
            $activecampaign_tags = ( isset( $intg['activecampaign_tags'] ) ? $intg['activecampaign_tags'] : "" );
            //activecampaign tags
            $member_vault_course_id = ( isset( $intg['member_vault_course_id'] ) ? $intg['member_vault_course_id'] : "" );
            //member_vault_course_id
            $membervault_action = ( isset( $intg['membervault_action'] ) ? $intg['membervault_action'] : "add_user" );
            // match plan
            $matched_plan = false;
            
            if ( empty($_sc_plan_ids) || in_array( '', $_sc_plan_ids ) ) {
                $matched_plan = true;
            } else {
                
                if ( in_array( $plan_id, $_sc_plan_ids ) ) {
                    $matched_plan = true;
                } else {
                    
                    if ( in_array( $option_id, $_sc_plan_ids ) || in_array( $option_id . '_sale', $_sc_plan_ids ) ) {
                        $matched_plan = true;
                    } else {
                        if ( in_array( $order_type, $_sc_plan_ids ) ) {
                            $matched_plan = true;
                        }
                    }
                
                }
            
            }
            
            
            if ( in_array( $trigger, $_sc_service_trigger ) && $matched_plan ) {
                $intg['service_trigger'] = $trigger;
                //check if mailchimp list id exist
                if ( $_sc_services == "mailchimp" && $_sc_mail_list != "" ) {
                    sc_add_remove_mailchimp_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $_sc_mail_list,
                        $_sc_mail_tags,
                        $_sc_mail_groups,
                        $customerEmail,
                        $phone,
                        $first_name,
                        $last_name,
                        $intg,
                        $order
                    );
                }
                //check if convertkit list id exist
                if ( $_sc_services == "convertkit" ) {
                    sc_add_remove_convertkit_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $_sc_converkit_forms,
                        $_sc_converkit_tags,
                        $customerEmail,
                        $phone,
                        $first_name,
                        $last_name
                    );
                }
                //check if activecampaign list id exist
                
                if ( $_sc_services == "activecampaign" ) {
                    $fieldmap = $intg['activecampaign_field_map'];
                    sc_add_remove_activecampaign_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $activecampaign_lists,
                        $activecampaign_tags,
                        $_sc_mail_groups = '',
                        $customerEmail,
                        $phone,
                        $first_name,
                        $last_name,
                        $fieldmap
                    );
                }
                
                //check if sendfox
                
                if ( $_sc_services == "sendfox" ) {
                    $sendfox_list = $intg['sendfox_list'];
                    sc_add_remove_sendfox_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $sendfox_list,
                        $customerEmail,
                        $first_name,
                        $last_name
                    );
                }
                
                //check if mailpoet
                
                if ( $_sc_services == "mailpoet" ) {
                    $mailpoet_list = $intg['mailpoet_list'];
                    sc_add_remove_mailpoet_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $mailpoet_list,
                        $customerEmail,
                        $first_name,
                        $last_name
                    );
                }
                
                // create WP user
                if ( $_sc_services == "create user" ) {
                    $order['user_id'] = sc_create_user(
                        $order_id,
                        $customerEmail,
                        $first_name,
                        $last_name,
                        $intg['user_role']
                    );
                }
                // update WP user
                if ( $_sc_services == "update user" ) {
                    $order['user_id'] = sc_create_user(
                        $order_id,
                        $customerEmail,
                        $first_name,
                        $last_name,
                        $intg['user_role'],
                        null,
                        $intg['previous_user_role']
                    );
                }
                //check if membervault
                if ( $_sc_services == "membervault" && !empty($member_vault_course_id) ) {
                    sc_add_remove_membervault_subscriber(
                        $order_id,
                        $_sc_services,
                        $membervault_action,
                        $member_vault_course_id,
                        $customerEmail,
                        $phone = '',
                        $first_name,
                        $last_name
                    );
                }
                // do 3rd party integrations
                do_action(
                    'studiocart_' . $_sc_services . '_integrations',
                    $intg,
                    $sc_product_id,
                    $order
                );
            }
        
        }
        do_action( 'studiocart_' . $trigger . '_integrations', $sc_product_id, $order );
    }

}

function sc_webhook_order_body( $order, $type = '', $price_in_cents = false )
{
    global  $sc_currency ;
    $keys = array(
        'amount',
        'order_amount',
        'tax_amount',
        'pre_tax_amount',
        'invoice_total',
        'invoice_subtotal',
        'subscription_amount',
        'total_amount',
        'shipping_amount',
        'discount',
        'unit_price',
        'subtotal',
        'discount_amount',
        'sign_up_fee'
    );
    $invoices = false;
    
    if ( is_numeric( $order ) ) {
        
        if ( get_post_type( $order ) == 'sc_subscription' ) {
            $order = new ScrtSubscription( $order );
            $invoices = $order->orders();
        } else {
            $order = new ScrtOrder( $order );
        }
        
        $order = $order->get_data();
    }
    
    $body = array();
    if ( $type ) {
        $body['trigger'] = $type;
    }
    if ( $type != 'lead' ) {
        $body['id'] = $order['id'];
    }
    $body['customer_firstname'] = $order['firstname'];
    $body['customer_lastname'] = $order['lastname'];
    $body['customer_name'] = $order['firstname'] . ' ' . $order['lastname'];
    $body['customer_email'] = $order['email'];
    $body['customer_phone'] = $order['phone'];
    $body['customer_address'] = $order['address1'];
    $body['customer_address_2'] = $order['address2'];
    $body['customer_city'] = $order['city'];
    $body['customer_state'] = $order['state'];
    $body['customer_zip'] = $order['zip'];
    $body['customer_country'] = $order['country'];
    $body['product_id'] = $order['product_id'];
    $body['product_name'] = $order['product_name'];
    $body['gateway'] = $order['pay_method'];
    $body['custom_fields'] = $order['custom_fields'];
    $body['shipping_amount'] = $order['shipping_amount'] ?? '';
    $body['ip_address'] = $order['ip_address'];
    $body['date'] = date( "Y-m-d", strtotime( 'now' ) );
    $body['date_time'] = get_gmt_from_date( date( "Y-m-d H:i:s", strtotime( 'now' ) ) );
    
    if ( $type != 'lead' ) {
        $body['payment_plan'] = $order['item_name'];
        $body['payment_plan_id'] = $order['option_id'];
        $body['order_amount'] = (double) $order['amount'];
        $body['tax_amount'] = (double) $order['tax_amount'];
        $body['pre_tax_amount'] = ( isset( $order['pre_tax_amount'] ) ? (double) $order['pre_tax_amount'] : '' );
        $body['tax_rate'] = $order['tax_rate'];
        $body['tax_type'] = $order['tax_type'];
        $body['tax_desc'] = $order['tax_desc'];
        $body['vat_number'] = $order['vat_number'];
        $body['date'] = get_the_date( 'Y-m-d', $order['id'] );
        $body['date_time'] = get_gmt_from_date( get_the_date( 'Y-m-d H:i:s', $order['id'] ) );
        $body['coupon'] = $order['coupon_id'];
        $body['currency'] = $order['currency'];
        if ( isset( $order['coupon']['amount'] ) ) {
            $body['discount'] = $order['coupon']['amount'];
        }
        
        if ( !isset( $order['sub_amount'] ) ) {
            // orders
            $body['transaction_id'] = $order['transaction_id'];
            if ( isset( $order['subscription_id'] ) ) {
                $body['subscription_id'] = $order['subscription_id'];
            }
            $body['status'] = $order['status'];
            $body['order_status'] = $order['status'];
            // remove
            $body['order_id'] = $order['id'];
            
            if ( $items = sc_get_item_list( $order['id'], false, true ) ) {
                $body['items'] = $items['items'];
                if ( $price_in_cents ) {
                    foreach ( $body['items'] as $i => $item ) {
                        foreach ( $item as $k => $v ) {
                            if ( in_array( $k, $keys ) ) {
                                $body['items'][$i][$k] = sc_price_in_cents( $v, $sc_currency );
                            }
                        }
                    }
                }
            }
            
            if ( get_post_meta( $order['product_id'], '_sc_show_optin_cb', true ) ) {
                $body['signup_consent'] = $order['consent'] ?? 'No';
            }
            $body['invoice_total'] = (double) $order['invoice_total'];
            $body['invoice_subtotal'] = (double) $order['invoice_subtotal'];
        } else {
            // subscriptions
            $body['sub_amount'] = (double) $order['sub_amount'];
            $body['subscription_id'] = $order['subscription_id'];
            $body['status'] = $order['status'];
            $body['amount'] = $order['sub_amount'];
            if ( $order['sub_installments'] > 1 ) {
                $body['installments'] = $order['sub_installments'];
            }
            $body['interval'] = $order['sub_interval'];
            $body['frequency'] = $order['sub_frequency'];
            $body['trial_days'] = $order['free_trial_days'];
            $body['sign_up_fee'] = $order['sign_up_fee'];
            
            if ( $order['sub_next_bill_date'] ) {
                $body['next_bill_date'] = sc_maybe_format_date( $order['sub_next_bill_date'], 'Y-m-d' );
                $body['next_bill_date_time'] = sc_maybe_format_date( $order['sub_next_bill_date'], 'Y-m-d H:i:s' );
            }
            
            
            if ( $order['sub_end_date'] ) {
                $body["end_date"] = sc_maybe_format_date( $order['sub_end_date'], 'Y-m-d' );
                $body["end_date_time"] = sc_maybe_format_date( $order['sub_end_date'], 'Y-m-d H:i:s' );
            }
            
            
            if ( $order['cancel_date'] ) {
                $body["cancel_date"] = sc_maybe_format_date( $order['cancel_date'], 'Y-m-d' );
                $body["cancel_date_time"] = sc_maybe_format_date( $order['cancel_date'], 'Y-m-d H:i:s' );
            }
        
        }
    
    }
    
    $body['website_url'] = get_site_url();
    $body['order_url'] = $order['page_url'];
    $body = array_filter( $body, function ( $v ) {
        return !is_null( $v ) && $v !== '';
    } );
    if ( $price_in_cents ) {
        foreach ( $body as $k => $v ) {
            if ( in_array( $k, $keys ) ) {
                $body[$k] = sc_price_in_cents( $v, $sc_currency );
            }
        }
    }
    
    if ( $invoices ) {
        $invoice_arr = array();
        foreach ( $invoices as $order ) {
            $invoice_arr[] = array(
                'id'           => $order->id,
                'status'       => $order->status,
                'order_amount' => ( $price_in_cents ? sc_price_in_cents( $order->amount, $sc_currency ) : $order->amount ),
                'date'         => get_the_date( 'Y-m-d', $order->id ),
                'date_time'    => get_the_date( "Y-m-d H:i:s", $order->id ),
            );
        }
        $body['invoices'] = $invoice_arr;
    }
    
    return apply_filters( 'sc_webhook_order_data', $body );
}

function sc_maybe_format_date( $str, $format = false )
{
    if ( !$str ) {
        return $str;
    }
    if ( !is_numeric( $str ) ) {
        $str = strtotime( $str );
    }
    
    if ( !$format ) {
        return date_i18n( get_option( 'date_format' ), $str );
    } else {
        return date_i18n( $format, $str );
    }

}

function sc_get_order_user_id( $order, $create = false )
{
    $id = $order['id'] ?? $order['ID'];
    
    if ( $create ) {
        $default_values = array(
            'send_email' => null,
            'user_role'  => 'subscriber',
        );
        
        if ( is_array( $create ) ) {
            $args = wp_parse_args( $create, $default_values );
        } else {
            $args = $default_values;
        }
        
        $user_id = sc_create_user(
            $id,
            $order['email'],
            $order['first_name'],
            $order['last_name'],
            $args['user_role'],
            $args['send_email']
        );
    } else {
        $user_id = email_exists( $order['email'] );
    }
    
    return $user_id;
}

function sc_generate_login_creds( $customerEmail, $password = false )
{
    $pid = intval( $_POST['sc_product_id'] );
    $scp = sc_setup_product( $pid );
    if ( !$password ) {
        $password = wp_generate_password();
    }
    $username = $customerEmail;
    if ( is_countable( $scp->custom_fields ) ) {
        foreach ( $scp->custom_fields as $field ) {
            $key = str_replace( [ ' ', '.' ], [ '_', '_' ], $field['field_id'] );
            if ( isset( $_POST['sc_custom_fields'][$key] ) ) {
                
                if ( $field['field_type'] == 'password' ) {
                    $password = $_POST['sc_custom_fields'][$key];
                } else {
                    if ( isset( $field['field_username'] ) ) {
                        $username = sanitize_text_field( $_POST['sc_custom_fields'][$key] );
                    }
                }
            
            }
        }
    }
    $creds = array(
        'username' => $username,
        'password' => $password,
    );
    return $creds;
}

function sc_create_user(
    $order_id,
    $customerEmail,
    $first_name,
    $last_name,
    $user_role = '',
    $send_email_override = null,
    $change_roles = false
)
{
    $sub_id = false;
    if ( get_post_type( $order_id ) == 'sc_order' ) {
        $sub_id = get_post_meta( $order_id, '_sc_subscription_id', true );
    }
    
    if ( $user_id = email_exists( $customerEmail ) ) {
        $u = new WP_User( $user_id );
        update_post_meta( $order_id, '_sc_user_account', $user_id );
        // add user to subscription if exists
        if ( $sub_id ) {
            update_post_meta( $sub_id, '_sc_user_account', $user_id );
        }
        // Update user role
        
        if ( is_countable( $change_roles ) && is_countable( $u->roles ) ) {
            foreach ( $u->roles as $role ) {
                if ( in_array( $role, $change_roles ) ) {
                    $u->set_role( $user_role );
                }
            }
            sc_log_entry( $order_id, sprintf( __( "Role for User ID: %s updated to %s", 'ncs-cart' ), $user_id, $user_role ) );
        }
    
    } else {
        $creds = sc_generate_login_creds( $customerEmail );
        $user_data = array(
            'user_login' => $creds['username'],
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'user_email' => $customerEmail,
            'user_pass'  => $creds['password'],
            'role'       => $user_role,
        );
        $user_id = wp_insert_user( $user_data );
        // add new user account to order info
        
        if ( $user_id && $order_id ) {
            $msg = sprintf( __( "New user created (ID: %s)", 'ncs-cart' ), $user_id );
            update_post_meta( $order_id, '_sc_user_account', $user_id );
            // add user to subscription if exists
            if ( $sub_id ) {
                update_post_meta( $sub_id, '_sc_user_account', $user_id );
            }
            sc_log_entry( $order_id, $msg );
            do_action( 'sc_after_user_is_created', $user_id, $order_id );
            // send notification email
            $send_email = ( $send_email_override !== null ? $send_email_override : (bool) get_option( '_sc_email_registration_enable' ) );
            $send_email = apply_filters( 'sc_send_new_user_email', $send_email, $order_id );
            if ( $send_email ) {
                sc_new_user_notification( $user_data, $order_id );
            }
        }
    
    }
    
    sc_maybe_auto_login_user( $user_id, $order_id );
    return $user_id;
}

function sc_maybe_auto_login_user( $user_id, $order_id )
{
    if ( current_user_can( 'administrator' ) ) {
        return;
    }
    
    if ( !is_user_logged_in() && isset( $_POST['sc-auto-login'] ) ) {
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
    }

}

function sc_new_user_notification( $user, $order_id, $test = false )
{
    $from_name = get_option( '_sc_email_from_name', '' );
    $from_email = get_option( '_sc_email_from_email', '' );
    $subject = get_option( '_sc_registration_subject', '' );
    $body = get_option( '_sc_registration_email_body', '' );
    
    if ( !$test ) {
        $order_info = (array) sc_setup_order( $order_id );
        $order_info['username'] = $user['user_login'];
        $order_info['password'] = $user['user_pass'];
        $subject = sc_personalize( $subject, $order_info );
        $body = sc_personalize( $body, $order_info );
        $to = $user['user_email'];
    } else {
        $order_info = $order_id;
    }
    
    $atts = array(
        'type'       => 'registration',
        'order_info' => $order_info,
        'headline'   => '',
        'body'       => $body,
    );
    $body = sc_get_email_html( $atts );
    $body = html_entity_decode( $body, ENT_QUOTES, "UTF-8" );
    $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
    if ( !$test ) {
        wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
    }
    
    if ( get_option( '_sc_registration_email_admin' ) || $test ) {
        if ( !($admin_email = get_option( 'sc_admin_email' )) ) {
            $admin_email = get_option( 'admin_email' );
        }
        $to = apply_filters( 'sc_admin_notification_email', $admin_email, $order_info );
        $res = wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
        if ( $test ) {
            return $res;
        }
    }

}

function studiocart_notification_send( $status, $order_info, $test = false )
{
    switch ( $status ) {
        case 'paid':
            $type = 'confirmation';
            break;
        default:
            $type = $status;
            break;
    }
    
    if ( $type && ($test || get_option( '_sc_email_' . $type . '_enable' )) ) {
        $em = '_sc_email_' . $type . '_';
        $from_name = get_option( '_sc_email_from_name', '' );
        $from_email = get_option( '_sc_email_from_email', '' );
        $subject = get_option( $em . 'subject', '' );
        $headline = get_option( $em . 'headline', '' );
        $body = get_option( $em . 'body', '' );
        if ( !$test ) {
            
            if ( is_numeric( $order_info ) ) {
                
                if ( !in_array( $status, array(
                    'completed',
                    'active',
                    'paused',
                    'canceled',
                    'past_due'
                ) ) ) {
                    $order_info = new ScrtOrder( $order_info );
                } else {
                    $order_info = new ScrtSubscription( $order_info );
                }
                
                $order_info = $order_info->get_data();
            }
        
        }
        $subject = sc_personalize( $subject, $order_info );
        $headline = sc_personalize( $headline, $order_info );
        $body = sc_personalize( $body, $order_info );
        $atts = array(
            'type'       => $type,
            'order_info' => $order_info,
            'headline'   => $headline,
            'body'       => $body,
        );
        $to = trim( $order_info['email'] );
        $body = sc_get_email_html( $atts );
        $body = html_entity_decode( $body, ENT_QUOTES, "UTF-8" );
        $attachments = array();
        $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
        $attach = get_option( '_sc_invoice_attach_' . $type . '_email' );
        
        if ( $attach ) {
            $order = new ScrtOrder( $order_info['id'] );
            $attachments[] = $order->get_invoice();
        }
        
        
        if ( !$test ) {
            wp_mail(
                $to,
                $subject,
                $body,
                $headers,
                $attachments
            );
        } else {
            $subject = sprintf( __( 'Test: %s', 'ncs-cart' ), $subject );
        }
        
        
        if ( get_option( $em . 'admin' ) || $test ) {
            if ( !($admin_email = get_option( 'sc_admin_email' )) ) {
                $admin_email = get_option( 'admin_email' );
            }
            $to = apply_filters( 'sc_admin_notification_email', $admin_email, $order_info );
            $res = wp_mail(
                $to,
                $subject,
                $body,
                $headers,
                $attachments
            );
            if ( $test ) {
                return $res;
            }
        }
    
    }

}

function sc_get_email_html( $atts )
{
    ob_start();
    ncs_helper()->renderTemplate( 'email/email-main', $atts );
    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}

function sc_do_notifications( $order_info )
{
    //sc_log_entry($order_info['ID'], "doing notifications for product_id: " . $order_info['product_id']);
    $notifications = get_post_meta( $order_info['product_id'], '_sc_notifications', true );
    //get integration meta mailchimp
    if ( !($admin_email = get_option( 'sc_admin_email' )) ) {
        $admin_email = get_option( 'admin_email' );
    }
    if ( $notifications ) {
        foreach ( $notifications as $k => $n ) {
            switch ( $n['send_to'] ) {
                case 'enter':
                    $to = wp_specialchars_decode( sc_personalize( $n['send_to_email'], $order_info ) );
                    break;
                case 'purchaser':
                    $to = $order_info['email'];
                    break;
                default:
                    $to = $admin_email;
                    break;
            }
            $from_name = ( $n['from_name'] ? $n['from_name'] : get_bloginfo( 'name' ) );
            $from_email = ( $n['from_email'] ? $n['from_email'] : get_option( 'admin_email' ) );
            $subject = wp_specialchars_decode( sc_personalize( $n['subject'], $order_info ) );
            $body = wpautop( wp_specialchars_decode( sc_personalize( $n['message'], $order_info ), 'ENT_QUOTES' ), false );
            $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
            $n['reply_to'] = $n['reply_to'] ?? '';
            $n['bcc'] = $n['bcc'] ?? '';
            if ( $n['reply_to'] ) {
                $headers[] = 'Reply-To: ' . $n['reply_to'];
            }
            if ( $n['bcc'] ) {
                $headers[] = 'Bcc: ' . $n['bcc'];
            }
            wp_mail(
                $to,
                $subject,
                $body,
                $headers
            );
        }
    }
}

function get_sc_currencies()
{
    $currencies = array_unique( apply_filters( 'ncs-cart-currencies', array(
        'AED' => __( 'United Arab Emirates dirham', 'ncs-cart' ),
        'AFN' => __( 'Afghan afghani', 'ncs-cart' ),
        'ALL' => __( 'Albanian lek', 'ncs-cart' ),
        'AMD' => __( 'Armenian dram', 'ncs-cart' ),
        'ANG' => __( 'Netherlands Antillean guilder', 'ncs-cart' ),
        'AOA' => __( 'Angolan kwanza', 'ncs-cart' ),
        'ARS' => __( 'Argentine peso', 'ncs-cart' ),
        'AUD' => __( 'Australian dollar', 'ncs-cart' ),
        'AWG' => __( 'Aruban florin', 'ncs-cart' ),
        'AZN' => __( 'Azerbaijani manat', 'ncs-cart' ),
        'BAM' => __( 'Bosnia and Herzegovina convertible mark', 'ncs-cart' ),
        'BBD' => __( 'Barbadian dollar', 'ncs-cart' ),
        'BDT' => __( 'Bangladeshi taka', 'ncs-cart' ),
        'BGN' => __( 'Bulgarian lev', 'ncs-cart' ),
        'BHD' => __( 'Bahraini dinar', 'ncs-cart' ),
        'BIF' => __( 'Burundian franc', 'ncs-cart' ),
        'BMD' => __( 'Bermudian dollar', 'ncs-cart' ),
        'BND' => __( 'Brunei dollar', 'ncs-cart' ),
        'BOB' => __( 'Bolivian boliviano', 'ncs-cart' ),
        'BRL' => __( 'Brazilian real', 'ncs-cart' ),
        'BSD' => __( 'Bahamian dollar', 'ncs-cart' ),
        'BTC' => __( 'Bitcoin', 'ncs-cart' ),
        'BTN' => __( 'Bhutanese ngultrum', 'ncs-cart' ),
        'BWP' => __( 'Botswana pula', 'ncs-cart' ),
        'BYR' => __( 'Belarusian ruble (old)', 'ncs-cart' ),
        'BYN' => __( 'Belarusian ruble', 'ncs-cart' ),
        'BZD' => __( 'Belize dollar', 'ncs-cart' ),
        'CAD' => __( 'Canadian dollar', 'ncs-cart' ),
        'CDF' => __( 'Congolese franc', 'ncs-cart' ),
        'CHF' => __( 'Swiss franc', 'ncs-cart' ),
        'CLP' => __( 'Chilean peso', 'ncs-cart' ),
        'CNY' => __( 'Chinese yuan', 'ncs-cart' ),
        'COP' => __( 'Colombian peso', 'ncs-cart' ),
        'CRC' => __( 'Costa Rican col&oacute;n', 'ncs-cart' ),
        'CUC' => __( 'Cuban convertible peso', 'ncs-cart' ),
        'CUP' => __( 'Cuban peso', 'ncs-cart' ),
        'CVE' => __( 'Cape Verdean escudo', 'ncs-cart' ),
        'CZK' => __( 'Czech koruna', 'ncs-cart' ),
        'DJF' => __( 'Djiboutian franc', 'ncs-cart' ),
        'DKK' => __( 'Danish krone', 'ncs-cart' ),
        'DOP' => __( 'Dominican peso', 'ncs-cart' ),
        'DZD' => __( 'Algerian dinar', 'ncs-cart' ),
        'EGP' => __( 'Egyptian pound', 'ncs-cart' ),
        'ERN' => __( 'Eritrean nakfa', 'ncs-cart' ),
        'ETB' => __( 'Ethiopian birr', 'ncs-cart' ),
        'EUR' => __( 'Euro', 'ncs-cart' ),
        'FJD' => __( 'Fijian dollar', 'ncs-cart' ),
        'FKP' => __( 'Falkland Islands pound', 'ncs-cart' ),
        'GBP' => __( 'Pound sterling', 'ncs-cart' ),
        'GEL' => __( 'Georgian lari', 'ncs-cart' ),
        'GGP' => __( 'Guernsey pound', 'ncs-cart' ),
        'GHS' => __( 'Ghana cedi', 'ncs-cart' ),
        'GIP' => __( 'Gibraltar pound', 'ncs-cart' ),
        'GMD' => __( 'Gambian dalasi', 'ncs-cart' ),
        'GNF' => __( 'Guinean franc', 'ncs-cart' ),
        'GTQ' => __( 'Guatemalan quetzal', 'ncs-cart' ),
        'GYD' => __( 'Guyanese dollar', 'ncs-cart' ),
        'HKD' => __( 'Hong Kong dollar', 'ncs-cart' ),
        'HNL' => __( 'Honduran lempira', 'ncs-cart' ),
        'HRK' => __( 'Croatian kuna', 'ncs-cart' ),
        'HTG' => __( 'Haitian gourde', 'ncs-cart' ),
        'HUF' => __( 'Hungarian forint', 'ncs-cart' ),
        'IDR' => __( 'Indonesian rupiah', 'ncs-cart' ),
        'ILS' => __( 'Israeli new shekel', 'ncs-cart' ),
        'IMP' => __( 'Manx pound', 'ncs-cart' ),
        'INR' => __( 'Indian rupee', 'ncs-cart' ),
        'IQD' => __( 'Iraqi dinar', 'ncs-cart' ),
        'IRR' => __( 'Iranian rial', 'ncs-cart' ),
        'IRT' => __( 'Iranian toman', 'ncs-cart' ),
        'ISK' => __( 'Icelandic kr&oacute;na', 'ncs-cart' ),
        'JEP' => __( 'Jersey pound', 'ncs-cart' ),
        'JMD' => __( 'Jamaican dollar', 'ncs-cart' ),
        'JOD' => __( 'Jordanian dinar', 'ncs-cart' ),
        'JPY' => __( 'Japanese yen', 'ncs-cart' ),
        'KES' => __( 'Kenyan shilling', 'ncs-cart' ),
        'KGS' => __( 'Kyrgyzstani som', 'ncs-cart' ),
        'KHR' => __( 'Cambodian riel', 'ncs-cart' ),
        'KMF' => __( 'Comorian franc', 'ncs-cart' ),
        'KPW' => __( 'North Korean won', 'ncs-cart' ),
        'KRW' => __( 'South Korean won', 'ncs-cart' ),
        'KWD' => __( 'Kuwaiti dinar', 'ncs-cart' ),
        'KYD' => __( 'Cayman Islands dollar', 'ncs-cart' ),
        'KZT' => __( 'Kazakhstani tenge', 'ncs-cart' ),
        'LAK' => __( 'Lao kip', 'ncs-cart' ),
        'LBP' => __( 'Lebanese pound', 'ncs-cart' ),
        'LKR' => __( 'Sri Lankan rupee', 'ncs-cart' ),
        'LRD' => __( 'Liberian dollar', 'ncs-cart' ),
        'LSL' => __( 'Lesotho loti', 'ncs-cart' ),
        'LYD' => __( 'Libyan dinar', 'ncs-cart' ),
        'MAD' => __( 'Moroccan dirham', 'ncs-cart' ),
        'MDL' => __( 'Moldovan leu', 'ncs-cart' ),
        'MGA' => __( 'Malagasy ariary', 'ncs-cart' ),
        'MKD' => __( 'Macedonian denar', 'ncs-cart' ),
        'MMK' => __( 'Burmese kyat', 'ncs-cart' ),
        'MNT' => __( 'Mongolian t&ouml;gr&ouml;g', 'ncs-cart' ),
        'MOP' => __( 'Macanese pataca', 'ncs-cart' ),
        'MRU' => __( 'Mauritanian ouguiya', 'ncs-cart' ),
        'MUR' => __( 'Mauritian rupee', 'ncs-cart' ),
        'MVR' => __( 'Maldivian rufiyaa', 'ncs-cart' ),
        'MWK' => __( 'Malawian kwacha', 'ncs-cart' ),
        'MXN' => __( 'Mexican peso', 'ncs-cart' ),
        'MYR' => __( 'Malaysian ringgit', 'ncs-cart' ),
        'MZN' => __( 'Mozambican metical', 'ncs-cart' ),
        'NAD' => __( 'Namibian dollar', 'ncs-cart' ),
        'NGN' => __( 'Nigerian naira', 'ncs-cart' ),
        'NIO' => __( 'Nicaraguan c&oacute;rdoba', 'ncs-cart' ),
        'NOK' => __( 'Norwegian krone', 'ncs-cart' ),
        'NPR' => __( 'Nepalese rupee', 'ncs-cart' ),
        'NZD' => __( 'New Zealand dollar', 'ncs-cart' ),
        'OMR' => __( 'Omani rial', 'ncs-cart' ),
        'PAB' => __( 'Panamanian balboa', 'ncs-cart' ),
        'PEN' => __( 'Sol', 'ncs-cart' ),
        'PGK' => __( 'Papua New Guinean kina', 'ncs-cart' ),
        'PHP' => __( 'Philippine peso', 'ncs-cart' ),
        'PKR' => __( 'Pakistani rupee', 'ncs-cart' ),
        'PLN' => __( 'Polish z&#x142;oty', 'ncs-cart' ),
        'PRB' => __( 'Transnistrian ruble', 'ncs-cart' ),
        'PYG' => __( 'Paraguayan guaran&iacute;', 'ncs-cart' ),
        'QAR' => __( 'Qatari riyal', 'ncs-cart' ),
        'RON' => __( 'Romanian leu', 'ncs-cart' ),
        'RSD' => __( 'Serbian dinar', 'ncs-cart' ),
        'RUB' => __( 'Russian ruble', 'ncs-cart' ),
        'RWF' => __( 'Rwandan franc', 'ncs-cart' ),
        'SAR' => __( 'Saudi riyal', 'ncs-cart' ),
        'SBD' => __( 'Solomon Islands dollar', 'ncs-cart' ),
        'SCR' => __( 'Seychellois rupee', 'ncs-cart' ),
        'SDG' => __( 'Sudanese pound', 'ncs-cart' ),
        'SEK' => __( 'Swedish krona', 'ncs-cart' ),
        'SGD' => __( 'Singapore dollar', 'ncs-cart' ),
        'SHP' => __( 'Saint Helena pound', 'ncs-cart' ),
        'SLL' => __( 'Sierra Leonean leone', 'ncs-cart' ),
        'SOS' => __( 'Somali shilling', 'ncs-cart' ),
        'SRD' => __( 'Surinamese dollar', 'ncs-cart' ),
        'SSP' => __( 'South Sudanese pound', 'ncs-cart' ),
        'STN' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe dobra', 'ncs-cart' ),
        'SYP' => __( 'Syrian pound', 'ncs-cart' ),
        'SZL' => __( 'Swazi lilangeni', 'ncs-cart' ),
        'THB' => __( 'Thai baht', 'ncs-cart' ),
        'TJS' => __( 'Tajikistani somoni', 'ncs-cart' ),
        'TMT' => __( 'Turkmenistan manat', 'ncs-cart' ),
        'TND' => __( 'Tunisian dinar', 'ncs-cart' ),
        'TOP' => __( 'Tongan pa&#x2bb;anga', 'ncs-cart' ),
        'TRY' => __( 'Turkish lira', 'ncs-cart' ),
        'TTD' => __( 'Trinidad and Tobago dollar', 'ncs-cart' ),
        'TWD' => __( 'New Taiwan dollar', 'ncs-cart' ),
        'TZS' => __( 'Tanzanian shilling', 'ncs-cart' ),
        'UAH' => __( 'Ukrainian hryvnia', 'ncs-cart' ),
        'UGX' => __( 'Ugandan shilling', 'ncs-cart' ),
        'USD' => __( 'United States (US) dollar', 'ncs-cart' ),
        'UYU' => __( 'Uruguayan peso', 'ncs-cart' ),
        'UZS' => __( 'Uzbekistani som', 'ncs-cart' ),
        'VEF' => __( 'Venezuelan bol&iacute;var', 'ncs-cart' ),
        'VES' => __( 'Bol&iacute;var soberano', 'ncs-cart' ),
        'VND' => __( 'Vietnamese &#x111;&#x1ed3;ng', 'ncs-cart' ),
        'VUV' => __( 'Vanuatu vatu', 'ncs-cart' ),
        'WST' => __( 'Samoan t&#x101;l&#x101;', 'ncs-cart' ),
        'XAF' => __( 'Central African CFA franc', 'ncs-cart' ),
        'XCD' => __( 'East Caribbean dollar', 'ncs-cart' ),
        'XOF' => __( 'West African CFA franc', 'ncs-cart' ),
        'XPF' => __( 'CFP franc', 'ncs-cart' ),
        'YER' => __( 'Yemeni rial', 'ncs-cart' ),
        'ZAR' => __( 'South African rand', 'ncs-cart' ),
        'ZMW' => __( 'Zambian kwacha', 'ncs-cart' ),
    ) ) );
    return $currencies;
}

function get_sc_currency_symbols()
{
    $symbols = apply_filters( 'ncs-cart-currency-symbols', array(
        'AED' => '&#x62f;.&#x625;',
        'AFN' => '&#x60b;',
        'ALL' => 'L',
        'AMD' => 'AMD',
        'ANG' => '&fnof;',
        'AOA' => 'Kz',
        'ARS' => '&#36;',
        'AUD' => '&#36;',
        'AWG' => 'Afl.',
        'AZN' => 'AZN',
        'BAM' => 'KM',
        'BBD' => '&#36;',
        'BDT' => '&#2547;&nbsp;',
        'BGN' => '&#1083;&#1074;.',
        'BHD' => '.&#x62f;.&#x628;',
        'BIF' => 'Fr',
        'BMD' => '&#36;',
        'BND' => '&#36;',
        'BOB' => 'Bs.',
        'BRL' => '&#82;&#36;',
        'BSD' => '&#36;',
        'BTC' => '&#3647;',
        'BTN' => 'Nu.',
        'BWP' => 'P',
        'BYR' => 'Br',
        'BYN' => 'Br',
        'BZD' => '&#36;',
        'CAD' => '&#36;',
        'CDF' => 'Fr',
        'CHF' => '&#67;&#72;&#70;',
        'CLP' => '&#36;',
        'CNY' => '&yen;',
        'COP' => '&#36;',
        'CRC' => '&#x20a1;',
        'CUC' => '&#36;',
        'CUP' => '&#36;',
        'CVE' => '&#36;',
        'CZK' => '&#75;&#269;',
        'DJF' => 'Fr',
        'DKK' => 'DKK',
        'DOP' => 'RD&#36;',
        'DZD' => '&#x62f;.&#x62c;',
        'EGP' => 'EGP',
        'ERN' => 'Nfk',
        'ETB' => 'Br',
        'EUR' => '&euro;',
        'FJD' => '&#36;',
        'FKP' => '&pound;',
        'GBP' => '&pound;',
        'GEL' => '&#x20be;',
        'GGP' => '&pound;',
        'GHS' => '&#x20b5;',
        'GIP' => '&pound;',
        'GMD' => 'D',
        'GNF' => 'Fr',
        'GTQ' => 'Q',
        'GYD' => '&#36;',
        'HKD' => '&#36;',
        'HNL' => 'L',
        'HRK' => 'kn',
        'HTG' => 'G',
        'HUF' => '&#70;&#116;',
        'IDR' => 'Rp',
        'ILS' => '&#8362;',
        'IMP' => '&pound;',
        'INR' => '&#8377;',
        'IQD' => '&#x639;.&#x62f;',
        'IRR' => '&#xfdfc;',
        'IRT' => '&#x062A;&#x0648;&#x0645;&#x0627;&#x0646;',
        'ISK' => 'kr.',
        'JEP' => '&pound;',
        'JMD' => '&#36;',
        'JOD' => '&#x62f;.&#x627;',
        'JPY' => '&yen;',
        'KES' => 'KSh',
        'KGS' => '&#x441;&#x43e;&#x43c;',
        'KHR' => '&#x17db;',
        'KMF' => 'Fr',
        'KPW' => '&#x20a9;',
        'KRW' => '&#8361;',
        'KWD' => '&#x62f;.&#x643;',
        'KYD' => '&#36;',
        'KZT' => '&#8376;',
        'LAK' => '&#8365;',
        'LBP' => '&#x644;.&#x644;',
        'LKR' => '&#xdbb;&#xdd4;',
        'LRD' => '&#36;',
        'LSL' => 'L',
        'LYD' => '&#x644;.&#x62f;',
        'MAD' => '&#x62f;.&#x645;.',
        'MDL' => 'MDL',
        'MGA' => 'Ar',
        'MKD' => '&#x434;&#x435;&#x43d;',
        'MMK' => 'Ks',
        'MNT' => '&#x20ae;',
        'MOP' => 'P',
        'MRU' => 'UM',
        'MUR' => '&#x20a8;',
        'MVR' => '.&#x783;',
        'MWK' => 'MK',
        'MXN' => '&#36;',
        'MYR' => '&#82;&#77;',
        'MZN' => 'MT',
        'NAD' => 'N&#36;',
        'NGN' => '&#8358;',
        'NIO' => 'C&#36;',
        'NOK' => '&#107;&#114;',
        'NPR' => '&#8360;',
        'NZD' => '&#36;',
        'OMR' => '&#x631;.&#x639;.',
        'PAB' => 'B/.',
        'PEN' => 'S/',
        'PGK' => 'K',
        'PHP' => '&#8369;',
        'PKR' => '&#8360;',
        'PLN' => '&#122;&#322;',
        'PRB' => '&#x440;.',
        'PYG' => '&#8370;',
        'QAR' => '&#x631;.&#x642;',
        'RMB' => '&yen;',
        'RON' => 'lei',
        'RSD' => '&#1088;&#1089;&#1076;',
        'RUB' => '&#8381;',
        'RWF' => 'Fr',
        'SAR' => '&#x631;.&#x633;',
        'SBD' => '&#36;',
        'SCR' => '&#x20a8;',
        'SDG' => '&#x62c;.&#x633;.',
        'SEK' => '&#107;&#114;',
        'SGD' => '&#36;',
        'SHP' => '&pound;',
        'SLL' => 'Le',
        'SOS' => 'Sh',
        'SRD' => '&#36;',
        'SSP' => '&pound;',
        'STN' => 'Db',
        'SYP' => '&#x644;.&#x633;',
        'SZL' => 'L',
        'THB' => '&#3647;',
        'TJS' => '&#x405;&#x41c;',
        'TMT' => 'm',
        'TND' => '&#x62f;.&#x62a;',
        'TOP' => 'T&#36;',
        'TRY' => '&#8378;',
        'TTD' => '&#36;',
        'TWD' => '&#78;&#84;&#36;',
        'TZS' => 'Sh',
        'UAH' => '&#8372;',
        'UGX' => 'UGX',
        'USD' => '&#36;',
        'UYU' => '&#36;',
        'UZS' => 'UZS',
        'VEF' => 'Bs F',
        'VES' => 'Bs.S',
        'VND' => '&#8363;',
        'VUV' => 'Vt',
        'WST' => 'T',
        'XAF' => 'CFA',
        'XCD' => '&#36;',
        'XOF' => 'CFA',
        'XPF' => 'Fr',
        'YER' => '&#xfdfc;',
        'ZAR' => '&#82;',
        'ZMW' => 'ZK',
    ) );
    return $symbols;
}

function get_sc_zero_decimal_currency()
{
    $zero_decimal_currency = apply_filters( 'ncs-cart-zero-decimal-currency', array(
        'BIF',
        'CLP',
        'DJF',
        'GNF',
        'JPY',
        'KMF',
        'KRW',
        'MGA',
        'PYG',
        'RWF',
        'UGX',
        'VND',
        'VUV',
        'XAF',
        'XOF',
        'XPF'
    ) );
    return $zero_decimal_currency;
}

function sc_price_in_cents( $amount, $currency = false )
{
    global  $sc_currency ;
    if ( !$currency ) {
        $currency = $sc_currency;
    }
    
    if ( $amount === '' ) {
        $amount = 0;
    } else {
        $amount = (double) $amount;
    }
    
    $zero_decimal_currency = get_sc_zero_decimal_currency();
    if ( !in_array( $currency, $zero_decimal_currency ) ) {
        $amount *= 100;
    }
    return intval( $amount );
}


if ( !function_exists( 'sc_currency' ) ) {
    function sc_currency()
    {
        global  $sc_currency, $sc_currency_symbol ;
        $sc_currency = get_option( '_sc_currency' );
        if ( !$sc_currency ) {
            $sc_currency = 'USD';
        }
        $currency_array = get_sc_currency_symbols();
        $sc_currency_symbol = $currency_array[$sc_currency];
    }
    
    add_action( 'init', 'sc_currency' );
}


if ( !function_exists( 'sc_setup_stripe' ) ) {
    function sc_setup_stripe()
    {
        global  $sc_stripe ;
        $sc_stripe['mode'] = get_option( '_sc_stripe_api' );
        $sc_stripe['sk'] = get_option( '_sc_stripe_' . $sc_stripe['mode'] . '_sk' );
        $sc_stripe['pk'] = get_option( '_sc_stripe_' . $sc_stripe['mode'] . '_pk' );
        $sc_stripe['hook_id'] = get_option( '_sc_stripe_' . $sc_stripe['mode'] . '_webhook_id' );
        foreach ( $sc_stripe as $k => $v ) {
            
            if ( !$v ) {
                $sc_stripe = false;
                break;
            }
        
        }
    }
    
    add_action( 'init', 'sc_setup_stripe' );
}

function sc_sendfox_api_request( $endpoint = 'me', $data = array(), $method = 'GET' )
{
    $result = false;
    $base = 'https://api.sendfox.com/';
    $api_key = get_option( '_sc_sendfox_api_key' );
    if ( empty($api_key) ) {
        return $result;
    }
    // prepare request args
    $args = array(
        'body' => $data,
    );
    $args['headers'] = array(
        'Authorization' => 'Bearer ' . $api_key,
    );
    $args['method'] = $method;
    $args['timeout'] = 30;
    // make request
    $result = wp_remote_request( $base . $endpoint, $args );
    
    if ( !is_wp_error( $result ) && ($result['response']['code'] == 200 || $result['response']['code'] == 201) ) {
        $result = wp_remote_retrieve_body( $result );
        $result = json_decode( $result, TRUE );
        
        if ( !empty($result) ) {
            $result = array(
                'status' => 'success',
                'result' => $result,
            );
        } else {
            $result = array(
                'status'     => 'error',
                'error'      => 'json_parse_error',
                'error_text' => __( 'JSON Parse', 'sf4wp' ),
            );
        }
    
    } else {
        // if WP_Error happened
        
        if ( is_object( $result ) ) {
            $result = array(
                'status'     => 'error',
                'error'      => 'request_error',
                'error_text' => $result->get_error_message(),
            );
        } else {
            $result = wp_remote_retrieve_body( $result );
            $result = array(
                'status'     => 'error',
                'error'      => 'request_error',
                'error_text' => $result,
            );
        }
    
    }
    
    return $result;
}

function get_sc_mailchimp_data()
{
    //$list_id ="1a2aa580c1";
    //$groups_id ="b9a621916f";
    $dataArray = array();
    //$dataArray = array('sc_ajax_url' 	=> admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('sc-ajax-nonce'));
    $mailchimp_apikey = get_option( '_sc_mailchimp_api' );
    if ( $mailchimp_apikey ) {
        try {
            $MailChimp = new \DrewM\MailChimp\MailChimp( $mailchimp_apikey );
        } catch ( \Exception $e ) {
            echo  $e->getMessage() ;
            //add custom message
            return;
        }
    }
    $result = $MailChimp->get( 'lists' );
    
    if ( isset( $result['lists'] ) && !empty($result['lists']) ) {
        foreach ( $result['lists'] as $key => $list ) {
            $mail_chimp_list_id = $list['id'];
            $mail_chimp_list_name = $list['name'];
            $dataArray[$mail_chimp_list_id] = array(
                "mail_chimp_list_name" => $mail_chimp_list_name,
            );
            $tags = $MailChimp->get( 'lists/' . $mail_chimp_list_id . '/segments' );
            if ( isset( $tags['segments'] ) && !empty($tags['segments']) ) {
                foreach ( $tags['segments'] as $key => $tag ) {
                    $mail_chimp_tag_id = $tag['id'];
                    $mail_chimp_tag_name = $tag['name'];
                    $dataArray[$mail_chimp_list_id]['mail_chimp_tags'][] = array(
                        "mail_chimp_tag_id"   => $mail_chimp_tag_id,
                        "mail_chimp_tag_name" => $mail_chimp_tag_name,
                    );
                }
            }
            $parent_groups = $MailChimp->get( 'lists/' . $mail_chimp_list_id . '/interest-categories' );
            if ( isset( $parent_groups['categories'] ) && !empty($parent_groups['categories']) ) {
                foreach ( $parent_groups['categories'] as $key => $parent_group ) {
                    $mail_chimp_parent_groups_id = $parent_group['id'];
                    $mail_chimp_parent_groups_name = $parent_group['title'];
                    $groups = $MailChimp->get( 'lists/' . $mail_chimp_list_id . '/interest-categories/' . $mail_chimp_parent_groups_id . '/interests' );
                    foreach ( $groups['interests'] as $key => $group ) {
                        $mail_chimp_group_id = $group['id'];
                        $mail_chimp_group_name = $group['name'];
                        $dataArray[$mail_chimp_list_id]['mail_chimp_groups'][$mail_chimp_group_id] = array(
                            "mail_chimp_parent_groups_id"   => $mail_chimp_parent_groups_id,
                            "mail_chimp_parent_groups_name" => $mail_chimp_parent_groups_name,
                            "mail_chimp_group_id"           => $mail_chimp_group_id,
                            "mail_chimp_group_name"         => $mail_chimp_group_name,
                        );
                    }
                }
            }
        }
    } else {
        $dataArray = array(
            '' => __( 'No Data Found', 'ncs-cart' ),
        );
    }
    
    //wp_enqueue_script( 'mailchimp_service_data', plugin_dir_url( __FILE__ ) . '../admin/js/sc-admin-mailchimp.js', array());
    //wp_localize_script('mailchimp_service_data' , 'MailchimpServices', $dataArray);
}

if ( !function_exists( 'sc_maybe_update_stock' ) ) {
    function sc_maybe_update_stock( $product_id, $action = 'decrease', $qty = 1 )
    {
        
        if ( get_post_meta( $product_id, '_sc_manage_stock', true ) == '1' ) {
            $limit = get_post_meta( $product_id, '_sc_limit', true );
            switch ( $action ) {
                case 'increase':
                    $limit += $qty;
                    break;
                default:
                    $limit -= $qty;
                    break;
            }
            update_post_meta( $product_id, '_sc_limit', $limit );
            return $limit;
        }
    
    }

}
function sc_setup_upsells( $id )
{
    $id = intval( $id );
    if ( get_post_type( $id ) != 'sc_us_path' ) {
        return false;
    }
    $meta = get_post_custom( $id );
    foreach ( $meta as $k => $v ) {
        
        if ( strpos( $k, '_sc_' ) === 0 ) {
            $v = array_shift( $meta[$k] );
            $k = str_replace( '_sc_', '', $k );
            $arr[$k] = maybe_unserialize( $v );
        }
    
    }
    $arr = apply_filters( 'studiocart_upsell_path', $arr );
    return $arr;
}

function sc_get_upsell( $offer, $path, $type = 'upsell' )
{
    
    if ( isset( $path[$type . '_' . $offer] ) ) {
        $prefix = ( $type == 'upsell' ? 'us_' : 'ds_' );
        $ret = array(
            'product' => $path[$prefix . 'product_' . $offer] ?? '',
            'type'    => $path[$prefix . 'prod_type_' . $offer] ?? '',
            'price'   => $path[$prefix . 'price_' . $offer] ?? '',
            'plan'    => $path[$prefix . 'plan_' . $offer] ?? '',
            'url'     => get_permalink( $path[$prefix . 'page_' . $offer] ),
        );
        
        if ( !$ret['product'] || !$ret['url'] || !$ret['price'] && !$ret['plan'] ) {
            return false;
        } else {
            return $ret;
        }
    
    } else {
        return false;
    }

}

function sc_get_downsell( $offer, $path )
{
    return sc_get_upsell( $offer, $path, $type = 'downsell' );
}

add_filter(
    'studiocart_upsell_urls',
    'sc_upsell_urls',
    10,
    3
);
function sc_upsell_urls(
    $urls,
    $order_id,
    $scp,
    $type = 'upsell'
)
{
    
    if ( $scp->upsell_path ) {
        $path = $scp->upsell_path;
        $offer = ( isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1 );
        $yes_url = $no_url = $scp->thanks_url;
        // yes link
        if ( $upsell = sc_get_upsell( $offer + 1, $path ) ) {
            $yes_url = $upsell['url'];
        }
        if ( $type == 'upsell' ) {
            // point "no" link to downsell if we're on an upsell page
            if ( $downsell = sc_get_downsell( $offer, $path ) ) {
                $no_url = $downsell['url'];
            }
        }
        return array( $yes_url, $no_url );
    }
    
    return $urls;
}

add_filter(
    'studiocart_downsell_urls',
    'sc_downsell_urls',
    10,
    3
);
function sc_downsell_urls( $urls, $order_id, $scp )
{
    if ( $scp->upsell_path ) {
        return sc_upsell_urls(
            $urls,
            $order_id,
            $scp,
            $type = 'downsell'
        );
    }
    return $urls;
}

add_filter(
    'studiocart_show_upsell',
    'sc_maybe_show_upsell',
    10,
    3
);
function sc_maybe_show_upsell( $show_upsell, $order_id, $scp )
{
    
    if ( $scp->upsell_path ) {
        $offer = ( isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1 );
        update_post_meta( $order_id, 'current_upsell_offer', $offer );
        if ( sc_get_upsell( $offer, $scp->upsell_path ) ) {
            return true;
        }
        return false;
    }
    
    return $show_upsell;
}

add_filter(
    'studiocart_show_downsell',
    'sc_maybe_show_downsell',
    10,
    3
);
function sc_maybe_show_downsell( $show_downsell, $order_id, $scp )
{
    if ( isset( $_GET['sc-oto-2'] ) ) {
        return false;
    }
    
    if ( $scp->upsell_path ) {
        $offer = ( isset( $_GET['step'] ) ? intval( $_GET['step'] ) : 1 );
        if ( sc_get_upsell( $offer, $scp->upsell_path, 'downsell' ) ) {
            return true;
        }
        return false;
    }
    
    return $show_downsell;
}

function sc_setup_product( $id )
{
    $post_types = (array) apply_filters( 'sc_product_post_type', 'sc_product' );
    if ( !$id || !in_array( get_post_type( $id ), $post_types ) ) {
        return;
    }
    $default_atts = array(
        'ID'          => 0,
        'pay_options' => '',
        'form_action' => '',
        'thanks_url'  => '',
        'pay_options' => '',
        'ob_cb_label' => '',
    );
    $arr = array(
        'ID' => $id,
    );
    $meta = get_post_custom( $id );
    foreach ( $meta as $k => $v ) {
        
        if ( strpos( $k, '_sc_' ) === 0 ) {
            $v = array_shift( $meta[$k] );
            $k = str_replace( '_sc_', '', $k );
            $arr[$k] = maybe_unserialize( $v );
        }
    
    }
    $arr['single_plan'] = ( count( $arr['pay_options'] ) > 1 ? false : true );
    $arr['confirmation'] = $arr['confirmation'] ?? 'message';
    
    if ( $arr['confirmation'] == 'page' ) {
        $arr['thanks_url'] = get_permalink( $arr['confirmation_page'] );
    } else {
        $arr['thanks_url'] = get_permalink( $id );
    }
    
    // 2-step option now stored in _sc_display meta
    
    if ( get_post_meta( $id, '_sc_show_2_step', true ) ) {
        update_post_meta( $id, '_sc_display', 'two_step' );
        delete_post_meta( $id, '_sc_show_2_step' );
        $arr['display'] = 'two_step';
    }
    
    
    if ( isset( $arr['display'] ) && $arr['display'] == 'two_step' ) {
        $arr['show_2_step'] = true;
    } else {
        if ( isset( $arr['display'] ) && $arr['display'] == 'opt_in' ) {
            $arr['show_optin'] = true;
        }
    }
    
    $arr['form_action'] = $arr['thanks_url'];
    $arr['upsell'] = false;
    // backwards compatibility
    
    if ( isset( $arr['upsell_path'] ) && get_post_type( intval( $arr['upsell_path'] ) ) == 'sc_us_path' ) {
        $arr['upsell'] = true;
        // set upsell links to thank you page by default
        $arr['us_accept_url'] = $arr['us_decline_url'] = $arr['thanks_url'];
        $path = sc_setup_upsells( intval( $arr['upsell_path'] ) );
        $arr['upsell_path'] = $path;
        
        if ( $path && ($upsell = sc_get_upsell( 1, $path )) ) {
            $arr['form_action'] = $upsell['url'];
        } else {
            $arr['upsell_path'] = false;
            $arr['upsell'] = false;
        }
    
    } else {
        $arr['upsell_path'] = false;
    }
    
    if ( $arr['confirmation'] == 'redirect' ) {
        $arr['redirect_url'] = $arr['redirect'];
    }
    $arr['button_icon'] = $arr['button_icon'] ?? false;
    $arr['step1_button_icon'] = $arr['step1_button_icon'] ?? false;
    $arr['button_icon'] = ( $arr['button_icon'] && $arr['button_icon'] == 'none' ? false : $arr['button_icon'] );
    
    if ( $arr['button_icon'] ) {
        $svg = plugin_dir_path( __FILE__ ) . 'vendor/font-awesome/svgs/solid/' . $arr['button_icon'];
        
        if ( file_exists( $svg ) ) {
            $svg_file = file_get_contents( $svg );
            $arr['button_icon'] = $svg_file;
        } else {
            $arr['button_icon'] = false;
        }
    
    }
    
    $arr['step1_button_icon'] = ( $arr['step1_button_icon'] && $arr['step1_button_icon'] == 'none' ? false : $arr['step1_button_icon'] );
    
    if ( $arr['step1_button_icon'] ) {
        $svg = plugin_dir_path( __FILE__ ) . 'vendor/font-awesome/svgs/solid/' . $arr['step1_button_icon'];
        
        if ( file_exists( $svg ) ) {
            $svg_file = file_get_contents( $svg );
            $arr['step1_button_icon'] = $svg_file;
        } else {
            $arr['step1_button_icon'] = false;
        }
    
    }
    
    $arr['product_taxable'] = $arr['product_taxable'] ?? 'tax';
    
    if ( $arr['product_taxable'] == 'non_tax' ) {
        $arr['product_taxable'] = false;
    } else {
        
        if ( get_option( '_sc_tax_enable', false ) && $arr['product_taxable'] == 'tax' ) {
            $arr['tax_type'] = get_option( '_sc_tax_type', 'inclusive_tax' );
            $arr['product_taxable'] = true;
            $arr['price_show_with_tax'] = get_option( '_sc_price_show_with_tax', 'exclude_tax' );
        } else {
            $arr['product_taxable'] = false;
        }
    
    }
    
    $terms = get_option( '_sc_terms_url' );
    
    if ( isset( $arr['terms_setting'] ) && !empty($arr['terms_setting']) ) {
        
        if ( $arr['terms_setting'] == 'off' ) {
            $arr['terms_url'] = false;
        } else {
            $arr['terms_url'] = $arr['terms_url'] ?? $terms;
        }
    
    } else {
        $arr['terms_url'] = $terms;
    }
    
    $privacy = get_option( '_sc_privacy_url' );
    
    if ( isset( $arr['privacy_setting'] ) && !empty($arr['privacy_setting']) ) {
        
        if ( $arr['privacy_setting'] == 'off' ) {
            $arr['privacy_url'] = false;
        } else {
            $arr['privacy_url'] = $arr['privacy_url'] ?? $privacy;
        }
    
    } else {
        $arr['privacy_url'] = $privacy;
    }
    
    $arr['twostep_heading_1'] = ( isset( $arr['twostep_heading_1'] ) ? $arr['twostep_heading_1'] : __( 'Get it Now', 'ncs-cart' ) );
    $arr['twostep_heading_2'] = ( isset( $arr['twostep_heading_2'] ) ? $arr['twostep_heading_2'] : __( 'Payment', 'ncs-cart' ) );
    $arr['twostep_subhead_1'] = ( isset( $arr['twostep_subhead_1'] ) ? $arr['twostep_subhead_1'] : __( 'Your Info', 'ncs-cart' ) );
    $arr['twostep_subhead_2'] = ( isset( $arr['twostep_subhead_2'] ) ? $arr['twostep_subhead_2'] : __( 'of your order', 'ncs-cart' ) );
    $arr['show_optin_cb'] = ( isset( $arr['show_optin_cb'] ) ? $arr['show_optin_cb'] : false );
    // backwards compatibility
    if ( isset( $arr['default_fields'] ) && empty($arr['default_fields']) ) {
        unset( $arr['default_fields'] );
    }
    $arr = apply_filters( 'studiocart_product', wp_parse_args( $arr, $default_atts ) );
    return (object) $arr;
}

function get_tax_data( $item_tax_rate )
{
    $tax_rate = get_option( '_sc_tax_rates', array() );
    
    if ( !empty($tax_rate) ) {
        $fields = array( '_sc_tax_rate_title', '_sc_tax_rate_slug', '_sc_tax_rate' );
        $count = count( $tax_rate['_sc_tax_rate_slug'] );
        for ( $i = 0 ;  $i < $count ;  $i++ ) {
            
            if ( $tax_rate['_sc_tax_rate_slug'][$i] == $item_tax_rate ) {
                $inner_val = array();
                foreach ( $fields as $field ) {
                    $field_title = str_replace( '_sc_', '', $field );
                    $inner_val[$field_title] = $tax_rate[$field][$i];
                }
            }
        
        }
        return $inner_val;
    }

}

function remove_repeater_blank( $value )
{
    if ( is_array( $value ) ) {
        foreach ( $value as $key => $val ) {
            if ( empty($val) ) {
                unset( $value[$key] );
            }
        }
    }
    return $value;
}

function sc_setup_order( $id, $array = false )
{
    if ( get_post_type( $id ) != 'sc_order' && get_post_type( $id ) != 'sc_subscription' ) {
        return;
    }
    $arr = array(
        'ID'            => $id,
        'invoice_total' => 0,
        'date'          => get_the_date( '', $id ),
        'status'        => 'pending',
    );
    $meta = get_post_custom( $id );
    if ( is_array( $meta ) ) {
        foreach ( $meta as $k => $v ) {
            
            if ( strpos( $k, '_sc_' ) === 0 ) {
                
                if ( $k != '_sc_order_child' ) {
                    $v = array_shift( $meta[$k] );
                } else {
                    $orders = [];
                    foreach ( $v as $order ) {
                        $order = maybe_unserialize( $order );
                        $order['product_id'] = get_post_meta( $order['id'], '_sc_product_id', true );
                        $order['amount'] = get_post_meta( $order['id'], '_sc_amount', true );
                        $arr['invoice_total'] += $order['amount'];
                        
                        if ( !isset( $order['product_name'] ) ) {
                            $order['product_name'] = sc_get_public_product_name( $order['product_id'] );
                            update_post_meta( $order['id'], '_sc_product_name', $order['product_name'] );
                        }
                        
                        // see if there are any other orders created under this subscription
                        
                        if ( get_post_type( $order['id'] ) == 'sc_subscription' ) {
                            $args = array(
                                'post_type'   => 'sc_order',
                                'post_status' => 'any',
                                'order'       => 'asc',
                                'meta_query'  => array( array(
                                'key'   => '_sc_subscription_id',
                                'value' => $order['id'],
                            ) ),
                            );
                            $inv = get_posts( $args );
                            $order['id'] = $inv[0]->ID ?? false;
                        }
                        
                        $orders[] = $order;
                    }
                    $v = $orders;
                }
                
                $k = str_replace( '_sc_', '', $k );
                $arr[$k] = maybe_unserialize( $v );
            }
        
        }
    }
    $arr['product_id'] = $arr['product_id'] ?? '';
    // add studiocart plan to order info
    $option_id = $arr['option_id'] ?? $arr['plan_id'] ?? '';
    $arr['plan'] = studiocart_plan( $option_id, isset( $arr['on_sale'] ), $arr['product_id'] );
    if ( get_post_type( $arr['ID'] ) == 'sc_subscription' && $arr['plan'] && $arr['plan']->type == 'recurring' ) {
        if ( !isset( $arr['sub_end_date'] ) && $arr['sub_installments'] > 1 || isset( $arr['sub_end_date'] ) && $arr['sub_end_date'] == '1970-01-01' ) {
            
            if ( $arr['sub_installments'] > 1 ) {
                $duration = $arr['sub_installments'] * $arr['sub_frequency'];
                $cancel_at = $duration . ' ' . $arr['sub_interval'];
                if ( $arr['sub_trial_days'] ) {
                    $cancel_at .= " + " . $arr['sub_trial_days'] . __( " day", 'ncs-cart' );
                }
                $arr['sub_end_date'] = date( "Y-m-d", strtotime( $arr['date'] . ' + ' . $cancel_at ) );
                update_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
            } else {
                unset( $arr['sub_end_date'] );
                delete_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
            }
        
        }
    }
    
    if ( get_post_type( $arr['ID'] ) == 'sc_subscription' && !isset( $arr['subscription_id'] ) ) {
        $arr['subscription_id'] = sc_get_subscription_txn_id( $arr['ID'] );
    } else {
        if ( get_post_type( $arr['ID'] ) == 'sc_order' && !isset( $arr['transaction_id'] ) ) {
            $arr['transaction_id'] = sc_get_transaction_id( $arr['ID'] );
        }
    }
    
    if ( isset( $arr['firstname'] ) && isset( $arr['lastname'] ) ) {
        $arr['customer_name'] = $arr['firstname'] . ' ' . $arr['lastname'];
    }
    
    if ( !isset( $arr['product_name'] ) ) {
        $arr['product_name'] = sc_get_public_product_name( $arr['product_id'] );
        update_post_meta( $arr['ID'], '_sc_product_name', $arr['product_name'] );
    }
    
    
    if ( isset( $arr['sub_end_date'] ) && isset( $arr['product_replaced'] ) ) {
        $arr['product_name'] = sc_get_public_product_name( $arr['bump_id'] );
        $arr['amount'] = $arr['bump_amt'];
        $option_id = $arr['bump_option_id'];
        unset( $arr['item_name'], $arr['bump_id'] );
    } else {
        $option_id = $arr['option_id'] ?? $arr['plan_id'] ?? '';
    }
    
    $arr['product_name_plan'] = $arr['product_name'];
    if ( isset( $arr['item_name'] ) ) {
        $arr['product_name_plan'] .= ' - ' . $arr['item_name'];
    }
    // add studiocart plan to order info
    $arr['plan'] = studiocart_plan( $option_id, isset( $arr['on_sale'] ), $arr['product_id'] );
    if ( get_post_type( $arr['ID'] ) == 'sc_subscription' && $arr['plan'] && $arr['plan']->type == 'recurring' ) {
        
        if ( isset( $arr['sub_end_date'] ) && $arr['sub_end_date'] == '1970-01-01' ) {
            $duration = $arr['sub_installments'] * $arr['sub_frequency'];
            $cancel_at = $duration . ' ' . $arr['sub_interval'];
            if ( $arr['sub_trial_days'] ) {
                $cancel_at .= " + " . $arr['sub_trial_days'] . __( " day", 'ncs-cart' );
            }
            $arr['sub_end_date'] = date( "Y-m-d", strtotime( $arr['date'] . ' + ' . $cancel_at ) );
            update_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
        }
    
    }
    if ( $arr['status'] == 'initiated' || $arr['status'] == 'pending payment' ) {
        $arr['status'] = 'pending';
    }
    $arr['amount'] = ( isset( $arr['amount'] ) && $arr['amount'] ? (double) $arr['amount'] : 0 );
    $arr['invoice_total'] = ( $arr['invoice_total'] ? (double) $arr['invoice_total'] : 0 );
    $arr['main_offer_amt'] = $arr['amount'];
    // amount paid for main offer including discount
    $arr['invoice_total'] += (double) $arr['amount'];
    // total amount paid including child orders and discount
    $arr['invoice_subtotal'] = $arr['invoice_total'];
    if ( isset( $arr['discount_details'] ) ) {
        $arr['invoice_subtotal'] += (double) $arr['discount_details']['discount_amt'];
    }
    if ( isset( $arr['tax_amount'] ) ) {
        $arr['main_offer_amt'] -= $arr['tax_amount'];
    }
    
    if ( !empty($arr['bump_amt']) && !empty($arr['bump_id']) && empty($arr['order_bumps']) ) {
        $arr['order_bumps'] = array();
        
        if ( is_array( $arr['bump_id'] ) ) {
            for ( $j = 0 ;  $j < count( $arr['bump_id'] ) ;  $j++ ) {
                $arr['order_bumps'][] = array(
                    'id'     => $arr['bump_id'][$j],
                    'amount' => $arr['bump_amt'][$j],
                    'name'   => sc_get_public_product_name( $arr['bump_id'][$j] ),
                );
            }
        } else {
            $arr['order_bumps'][] = array(
                'id'     => $arr['bump_id'],
                'amount' => $arr['bump_amt'],
                'name'   => sc_get_public_product_name( $arr['bump_id'] ),
            );
        }
    
    }
    
    if ( isset( $arr['plan_price'] ) && isset( $arr['discount_details']['discount_amt'] ) ) {
        $arr['main_offer_amt'] = floatval( $arr['plan_price'] ) - floatval( $arr['discount_details']['discount_amt'] );
    }
    if ( !empty($arr['order_bumps']) && is_array( $arr['order_bumps'] ) ) {
        foreach ( $arr['order_bumps'] as $order_bump ) {
            $arr['main_offer_amt'] -= floatval( $order_bump['amount'] );
        }
    }
    
    if ( isset( $arr['custom_prices'] ) ) {
        $fields = $arr['custom_fields'];
        foreach ( $arr['custom_prices'] as $k => $v ) {
            $custom[$k]['label'] = $fields[$k]['label'];
            $custom[$k]['qty'] = $fields[$k]['value'];
            $custom[$k]['price'] = $v;
            if ( !isset( $arr['plan_price'] ) ) {
                $arr['main_offer_amt'] -= floatval( $v );
            }
        }
        $arr['custom_prices'] = $custom;
    }
    
    
    if ( !isset( $arr['plan_price'] ) ) {
        $arr['plan_price'] = $arr['main_offer_amt'];
        update_post_meta( $arr['ID'], '_sc_plan_price', $arr['plan_price'] );
    }
    
    
    if ( get_post_type( $id ) == 'sc_subscription' ) {
        $arr['sub_payment'] = '<span class="sc-Price-amount amount">' . sc_format_price( $arr['sub_amount'] ) . '</span> / ';
        // payment without html around currency symbol
        $arr['sub_payment_plain'] = sc_format_price( $arr['sub_amount'], false ) . ' / ';
        
        if ( $arr['sub_frequency'] > 1 ) {
            $arr['sub_payment'] .= $arr['sub_frequency'] . ' ' . sc_pluralize_interval( $arr['sub_interval'] );
            $arr['sub_payment_plain'] .= $arr['sub_frequency'] . ' ' . sc_pluralize_interval( $arr['sub_interval'] );
        } else {
            $arr['sub_payment'] .= __( $arr['sub_interval'], 'ncs-cart' );
            $arr['sub_payment_plain'] .= __( $arr['sub_interval'], 'ncs-cart' );
        }
        
        $arr['sub_payment_terms'] = $arr['sub_payment'];
        if ( $arr['sub_installments'] > 1 ) {
            $arr['sub_payment_terms'] .= ' x ' . $arr['sub_installments'];
        }
        // terms without html around currency symbol
        $arr['sub_payment_terms_plain'] = $arr['sub_payment_plain'];
        if ( $arr['sub_installments'] > 1 ) {
            $arr['sub_payment_terms_plain'] .= ' x ' . $arr['sub_installments'];
        }
    }
    
    
    if ( !$array ) {
        return (object) $arr;
    } else {
        return $arr;
    }

}

function sc_format_order_address( $order )
{
    $address = false;
    
    if ( isset( $order->address1 ) || isset( $order->city ) || isset( $order->state ) || isset( $order->zip ) || isset( $order->country ) ) {
        $address = '';
        if ( $order->address1 ) {
            $address .= $order->address1 . '<br/>';
        }
        if ( $order->address2 ) {
            $address .= $order->address2 . '<br/>';
        }
        
        if ( $order->city || $order->state || $order->zip ) {
            $str = '';
            if ( $order->city ) {
                $str .= $order->city;
            }
            
            if ( $order->state ) {
                if ( $str != '' ) {
                    $str .= ', ';
                }
                $str .= $order->state;
            }
            
            
            if ( $order->zip ) {
                if ( $str != '' ) {
                    $str .= ' ';
                }
                $str .= $order->zip;
            }
            
            if ( $str != '' ) {
                $str .= '<br>';
            }
            $address .= $str;
            if ( $order->country ) {
                $address .= $order->country . '<br/>';
            }
        }
    
    }
    
    return $address;
}

function sc_pluralize_interval( $int )
{
    switch ( $int ) {
        case 'day':
            $return = __( 'days', 'ncs-cart' );
            break;
        case 'week':
            $return = __( 'weeks', 'ncs-cart' );
            break;
        case 'month':
            $return = __( 'months', 'ncs-cart' );
            break;
        case 'year':
            $return = __( 'years', 'ncs-cart' );
            break;
        default:
            $return = false;
    }
    return $return;
}

function sc_next_bill_time( $sub, $date = null )
{
    if ( is_numeric( $sub ) ) {
        $sub = sc_setup_order( $sub );
    }
    $sub = (object) $sub;
    $created = get_the_time( "Y-m-d h:i:s", $sub->ID );
    
    if ( strtotime( $date ) !== false ) {
        $next_bill_date = date( "Y-m-d h:i:s", strtotime( $date ) );
    } else {
        $next_bill_date = date( "Y-m-d h:i:s" );
    }
    
    
    if ( isset( $sub->free_trial_days ) ) {
        $free_trial_days = $sub->free_trial_days;
        $start_date = date( "Y-m-d h:i:s", strtotime( $created . "+" . $free_trial_days . " day" ) );
        if ( date( "Y-m-d h:i:s" ) < $start_date ) {
            return $start_date;
        }
    }
    
    $next = strtotime( $next_bill_date . "+" . $sub->sub_frequency . " " . $sub->sub_interval );
    $old_next = get_post_meta( $sub->ID, '_sc_sub_next_bill_date', true );
    return max( $next, $old_next );
}

function studiocart_plan(
    $option_id,
    $sale = '',
    $product_id = '',
    $array = false
)
{
    global  $scp ;
    if ( !$option_id ) {
        return false;
    }
    
    if ( !$product_id ) {
        if ( !$scp ) {
            return false;
        }
        $plans = $scp->pay_options;
    } else {
        $plans = get_post_meta( $product_id, '_sc_pay_options', true );
    }
    
    if ( !$plans ) {
        return false;
    }
    foreach ( $plans as $val ) {
        $val['stripe_plan_id'] = $val['stripe_plan_id'] ?? '';
        $val['sale_stripe_plan_id'] = $val['sale_stripe_plan_id'] ?? '';
        
        if ( $option_id == $val['option_id'] || $option_id == $val['stripe_plan_id'] || $option_id == $val['sale_stripe_plan_id'] ) {
            $option = $val;
            break;
        }
    
    }
    if ( !isset( $option ) || !$option ) {
        return false;
    }
    $option['product_type'] = $option['product_type'] ?? '';
    if ( $sale === 'current' ) {
        $sale = sc_is_prod_on_sale( $product_id );
    }
    $sale = ( $sale ? 'sale_' : '' );
    $plan = array();
    $plan['type'] = ( $option['product_type'] == '' ? 'one-time' : $option['product_type'] );
    $plan['option_id'] = $option['option_id'];
    $plan['name'] = $option[$sale . 'option_name'] ?? '';
    $plan['stripe_id'] = $option[$sale . 'stripe_plan_id'];
    $plan['price'] = ( $option['product_type'] == 'free' ? 'free' : $option[$sale . 'price'] );
    $plan['initial_payment'] = (double) $plan['price'];
    $plan['cancel_immediately'] = $option['cancel_immediately'] ?? '';
    $plan['tax_rate'] = $option['tax_rate'] ?? '';
    
    if ( $plan['type'] == 'free' ) {
        $plan['initial_payment'] = 0;
    } else {
        
        if ( $plan['type'] == 'recurring' ) {
            $plan['installments'] = $option[$sale . 'installments'];
            $plan['interval'] = $option[$sale . 'interval'];
            $plan['frequency'] = $option[$sale . 'frequency'] ?? 1;
            $plan['trial_days'] = $plan['trial_days'] ?? '';
            $plan['fee'] = $plan['fee'] ?? '';
            
            if ( $plan['trial_days'] ) {
                $plan['next_bill_date'] = strtotime( date( "Y-m-d", strtotime( "+" . $plan['trial_days'] . " day" ) ) );
            } else {
                $plan['next_bill_date'] = strtotime( date( "Y-m-d", strtotime( "+" . $plan['frequency'] . " " . $plan['interval'] ) ) );
            }
            
            
            if ( $plan['installments'] > 1 ) {
                $duration = $plan['installments'] * $plan['frequency'];
                $cancel_at = $duration . ' ' . $plan['interval'];
                if ( $plan['trial_days'] ) {
                    $cancel_at .= " + " . $plan['trial_days'] . " day";
                }
                $plan['cancel_at'] = strtotime( $cancel_at );
                $plan['db_cancel_at'] = date( "Y-m-d", strtotime( $cancel_at ) );
            } else {
                $plan['cancel_at'] = null;
                $plan['db_cancel_at'] = null;
            }
            
            
            if ( $plan['frequency'] > 1 ) {
                $text = sc_format_price( $plan['price'] ) . ' / ' . $plan['frequency'] . ' ' . sc_pluralize_interval( $plan['interval'] );
            } else {
                $text = sc_format_price( $plan['price'] ) . ' / ' . $plan['interval'];
            }
            
            $installments = $plan['installments'];
            if ( $installments > 1 ) {
                $text .= ' x ' . $installments;
            }
            if ( $plan['trial_days'] ) {
                // (e.g. " with a 5-day free trial")
                $text .= ' ' . sprintf( __( 'with a %s-day free trial', 'ncs-cart' ), $plan['trial_days'] );
            }
            if ( $plan['fee'] ) {
                // (e.g. " and a $5 sign-up fee")
                $text .= ' ' . sprintf( __( 'and a %s sign-up fee', 'ncs-cart' ), sc_format_price( $plan['fee'] ) );
            }
            $plan['text'] = $text;
        }
    
    }
    
    $plan = apply_filters(
        '_sc_plan',
        $plan,
        $option,
        $sale
    );
    
    if ( !$array ) {
        return (object) $plan;
    } else {
        return $plan;
    }

}

function sc_maybe_do_subscription_complete( $subscription_id )
{
    $end_date = get_post_meta( $subscription_id, '_sc_sub_end_date', true );
    
    if ( $end_date == date( "Y-m-d" ) ) {
        $order_info = sc_setup_order( $subscription_id, $array = true );
        sc_log_entry( $subscription_id, __( 'Installment plan completed', 'ncs-cart' ) );
        sc_trigger_integrations( 'completed', $order_info );
        wp_update_post( array(
            'ID'          => $subscription_id,
            'post_status' => 'completed',
        ) );
        update_post_meta( $subscription_id, '_sc_sub_status', 'completed' );
    }

}

function sc_redirect( $url )
{
    nocache_headers();
    wp_redirect( $url );
    exit;
}

add_filter(
    'sc_format_subcription_order_detail',
    'sc_filter_format_subcription_terms_text',
    10,
    6
);
if ( !function_exists( 'sc_filter_format_subcription_terms_text' ) ) {
    function sc_filter_format_subcription_terms_text(
        $text,
        $terms,
        $trial_days = false,
        $sign_up_fee = false,
        $discount = false,
        $discount_duration = false
    )
    {
        if ( !$terms ) {
            return $text;
        }
        
        if ( $trial_days && $trial_days > 0 ) {
            // (e.g. "with a 5-day trial")
            $txt = __( 'with a %d-day trial', 'ncs-cart' );
            $txt = apply_filters( 'sc_plan_text_day_free_trial', $txt );
            $terms .= ' ' . sprintf( $txt, $trial_days );
        }
        
        
        if ( $sign_up_fee && floatval( $sign_up_fee ) > 0 ) {
            // (e.g. "and a $5 sign-up fee")
            $txt = __( 'and a %s sign-up fee', 'ncs-cart' );
            $txt = apply_filters( 'sc_plan_text_sign_up_fee', $txt );
            $terms .= ' ' . sprintf( $txt, sc_format_price( $sign_up_fee ) );
        }
        
        
        if ( $discount && $discount_duration && $discount > 0 ) {
            // (e.g. "Coupon: 5% off for 3 months")
            $terms .= '<br><strong>' . __( 'Coupon:', 'ncs-cart' ) . ' </strong> ';
            $terms .= sprintf( __( '%s off for %d months', 'ncs-cart' ), sc_format_price( $discount ), $discount_duration );
        }
        
        return $terms;
    }

}
function sc_get_items_from_legacy_order( $order, $qty_col = true )
{
    $items = array();
    
    if ( $order->plan && $order->main_offer_amt ) {
        $arr = array(
            'product_id'   => $order->product_id,
            'price_id'     => $order->option_id,
            'item_type'    => 'main',
            'product_name' => ( $order->quantity > 1 || $qty_col ? $order->product_name : sprintf( '%s x %s', $order->product_name, $order->quantity ) ),
            'price_name'   => $order->item_name,
            'total_amount' => $order->amount,
            'tax_amount'   => $order->tax_amount,
            'unit_price'   => $order->main_offer_amt / $order->quantity,
            'quantity'     => intval( $order->quantity ),
            'subtotal'     => $order->main_offer_amt,
        );
        
        if ( $order->subscription_id ) {
            $sub = new ScrtSubscription( $order->subscription_id );
            
            if ( $order->product_id == $sub->product_id ) {
                $sub = $sub->get_data();
                $arr['subscription_id'] = $sub['ID'];
                $arr['sub_summary'] = apply_filters(
                    'sc_format_subcription_order_detail',
                    $sub['sub_payment_terms_plain'],
                    $sub['sub_payment_terms_plain'],
                    $sub['free_trial_days'],
                    $sub['sign_up_fee'],
                    $sub['sub_discount'],
                    $sub['sub_discount_duration'],
                    $order->plan
                );
            }
        
        }
        
        if ( $order->purchase_note ) {
            $arr['purchase_note'] = $order->purchase_note;
        }
        $items[] = $arr;
        if ( isset( $order->custom_prices ) ) {
            foreach ( $order->custom_prices as $id => $price ) {
                $field = $order->custom_fields[$id];
                $arr = array(
                    'product_id'   => $order->product_id,
                    'price_id'     => $id,
                    'item_type'    => 'line item',
                    'product_name' => $order->product_name,
                    'price_name'   => ( !$qty_col && $field['value'] > 1 ? $field['label'] : sprintf( '%s x %s', $field['label'], $field['value'] ) ),
                    'unit_price'   => $price / $field['value'],
                    'quantity'     => intval( $field['value'] ),
                    'subtotal'     => $price,
                    'total_amount' => $price,
                );
                $items[] = $arr;
            }
        }
        
        if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
            foreach ( $order->order_bumps as $order_bump ) {
                $arr = array(
                    'product_id'   => $order_bump['id'],
                    'price_id'     => $order_bump['plan']->option_id ?? 'bump',
                    'item_type'    => 'bump',
                    'product_name' => $order_bump['name'],
                    'price_name'   => $order_bump['plan']->name ?? __( 'Order Bump', 'ncs-cart' ),
                    'unit_price'   => $order_bump['amount'],
                    'quantity'     => 1,
                    'subtotal'     => $order_bump['amount'],
                    'total_amount' => $order_bump['amount'],
                );
                
                if ( isset( $order_bump['plan'] ) && isset( $order_bump['plan']->type ) && $order_bump['plan']->type == 'recurring' && $order->subscription_id ) {
                    $sub = new ScrtSubscription( $order->subscription_id );
                    
                    if ( $order_bump['id'] == $sub->product_id ) {
                        $arr['subscription_id'] = $order->subscription_id;
                        $sub = $sub->get_data();
                        $arr['sub_summary'] = apply_filters(
                            'sc_format_subcription_order_detail',
                            $sub['sub_payment_terms_plain'],
                            $sub['sub_payment_terms_plain'],
                            $sub['free_trial_days'],
                            $sub['sign_up_fee'],
                            $sub['sub_discount'],
                            $sub['sub_discount_duration'],
                            $order->plan
                        );
                    }
                
                }
                
                if ( $order_bump['purchase_note'] ) {
                    $arr['purchase_note'] = $order_bump['purchase_note'];
                }
                $items[] = $arr;
            }
        } else {
            
            if ( isset( $order->bump_id ) && $order->bump_id ) {
                $arr = array(
                    'product_id'   => $order->bump_id,
                    'price_id'     => 'bump',
                    'item_type'    => 'bump',
                    'product_name' => sc_get_public_product_name( $order->bump_id ),
                    'price_name'   => __( 'Order Bump', 'ncs-cart' ),
                    'unit_price'   => $order->bump_amt,
                    'quantity'     => 1,
                    'subtotal'     => $order->bump_amt,
                    'total_amount' => $order->bump_amt,
                );
                $items[] = $arr;
            }
        
        }
        
        return $items;
    }
    
    return false;
}

function sc_get_order_items( $order, $qty_col = true, $show_hidden = false )
{
    if ( is_numeric( $order ) ) {
        $order = new ScrtOrder( $order );
    }
    $sub = false;
    
    if ( $order->subscription_id ) {
        $sub = new ScrtSubscription( $order->subscription_id );
        $sub = $sub->get_data();
    }
    
    $itemList = array();
    // add order items
    
    if ( $orderItems = $order->get_items() ) {
        foreach ( $orderItems as $item ) {
            if ( !$show_hidden && $item->item_type == 'bundled' ) {
                continue;
            }
            $item = $item->get_data();
            if ( !$qty_col && $item['quantity'] > 1 ) {
                $item['product_name'] = sprintf( '%s x %s', $item['product_name'], $item['quantity'] );
            }
            
            if ( $sub && $sub['product_id'] == $item['product_id'] && $sub['option_id'] == $item['price_id'] ) {
                $item['subscription_id'] = $sub['id'];
                $item['sub_summary'] = apply_filters(
                    'sc_format_subcription_order_detail',
                    $sub['sub_payment_terms_plain'],
                    $sub['sub_payment_terms_plain'],
                    $sub['free_trial_days'],
                    $sub['sign_up_fee'],
                    $sub['sub_discount'],
                    $sub['sub_discount_duration'],
                    $order->plan
                );
                $sub = false;
            }
            
            $itemList[] = $item;
        }
    } else {
        if ( $arr = sc_get_items_from_legacy_order( $order, $qty_col ) ) {
            // add main product
            $itemList = array_merge( $itemList, $arr );
        }
    }
    
    return $itemList;
}

function sc_get_item_list( $order_id, $full = true, $qty_col = false )
{
    $order = apply_filters( 'studiocart_order', new ScrtOrder( $order_id ) );
    $itemList['items'] = sc_get_order_items( $order, $qty_col );
    $total = $order->amount;
    $subtotal = $order->pre_tax_amount;
    $shipping_amount = $order->shipping_amount;
    if ( $order->coupon && $order->coupon['discount_amount'] ) {
        $itemList['discounts'][] = array(
            'product_name' => sprintf( __( 'Coupon: %s', 'ncs-cart' ), '<span class="sc-badge">' . strtoupper( $order->coupon_id ) . '</span>' ),
            'total_amount' => $order->coupon['discount_amount'],
            'subtotal'     => $order->coupon['discount_amount'],
            'item_type'    => 'discount',
        );
    }
    // child orders
    if ( $full ) {
        
        if ( $children = $order->get_children( true ) ) {
            foreach ( $children as $child ) {
                $list = sc_get_order_items( $child );
                $total += $child->amount;
                $subtotal += $child->pre_tax_amount;
                $shipping_amount += $child->shipping_amount;
                $order->shipping_tax += $child->shipping_tax;
                if ( $child->coupon && $child->coupon['discount_amount'] ) {
                    $itemList['discounts'][] = array(
                        'product_name' => sprintf( __( 'Coupon: %s', 'ncs-cart' ), '<span class="sc-badge">' . strtoupper( $child->coupon_id ) . '</span>' ),
                        'total_amount' => $child->coupon['discount_amount'],
                        'subtotal'     => $child->coupon['discount_amount'],
                        'item_type'    => 'discount',
                    );
                }
            }
            $itemList['items'] = array_merge( $itemList['items'], $list );
            
            if ( !is_object( $order->tax_data ) && is_object( $child->tax_data ) ) {
                $order->tax_data = $child->tax_data;
                $order->tax_desc = $child->tax_desc;
                $order->tax_rate = $child->tax_rate;
            }
        
        }
    
    }
    $itemList['subtotal'] = array(
        'product_name' => __( 'Subtotal', 'ncs-cart' ),
        'total_amount' => $subtotal,
        'subtotal'     => $subtotal,
        'item_type'    => 'subtotal',
    );
    
    if ( $shipping_amount ) {
        $itemList['shipping'] = array(
            'product_name' => __( 'Shipping', 'ncs-cart' ),
            'total_amount' => $shipping_amount,
            'subtotal'     => $shipping_amount,
            'item_type'    => 'shipping',
        );
        if ( $order->shipping_tax ) {
            $itemList['shipping']['tax_amount'] = $order->shipping_tax;
        }
    }
    
    
    if ( $full ) {
        $tax_amount = 0;
        foreach ( $itemList as $key => $group ) {
            
            if ( $key == 'items' ) {
                foreach ( $group as $k => $item ) {
                    if ( isset( $item['tax_amount'] ) ) {
                        $tax_amount += floatval( $item['tax_amount'] );
                    }
                }
            } else {
                if ( method_exists( $order, 'get_items' ) && isset( $group['tax_amount'] ) ) {
                    $tax_amount += floatval( $group['tax_amount'] );
                }
            }
        
        }
    } else {
        $tax_amount = floatval( $order->tax_amount );
    }
    
    
    if ( is_object( $order->tax_data ) ) {
        $redeem_tax = false;
        
        if ( isset( $order->tax_data->redeem_vat ) && $order->tax_data->redeem_vat ) {
            $redeem_tax = true;
            
            if ( $order->tax_data->type != 'inclusive' ) {
                $order->tax_amount = 0;
                $order->tax_rate = 0;
            }
        
        }
        
        $order->tax_rate .= '%';
        if ( $tax_amount && $order->tax_data->type == 'inclusive' ) {
            $order->tax_rate .= ' ' . __( 'incl.', 'ncs-cart' );
        }
        
        if ( $order->tax_data->type == 'inclusive' && $redeem_tax ) {
            $title = __( get_option( '_sc_vat_reverse_charge', "VAT Reversal" ), 'ncs-cart' );
        } else {
            
            if ( $tax_amount ) {
                $title = __( $order->tax_desc . ' (' . $order->tax_rate . ')', 'ncs-cart' );
            } else {
                $title = $order->tax_desc;
            }
        
        }
        
        $itemList['tax'] = array(
            'product_name' => $title,
            'total_amount' => $tax_amount,
            'subtotal'     => $tax_amount,
            'item_type'    => 'tax',
        );
    }
    
    $itemList['total'] = array(
        'product_name' => __( 'Total', 'ncs-cart' ),
        'total_amount' => $total,
        'subtotal'     => $total,
        'item_type'    => 'total',
    );
    return $itemList;
}

if ( !function_exists( 'sc_order_details' ) ) {
    function sc_order_details( $order_id )
    {
        $order = apply_filters( 'studiocart_order', new ScrtOrder( $order_id ) );
        $order = (object) $order->get_data();
        if ( isset( $order->main_offer ) ) {
            // backwards compatibility
            $order->main_offer_amt = $order->main_offer["plan"]->initial_payment;
        }
        $total = 0;
        ?>
    <div id="sc-order-details">
        <h3><?php 
        _e( "Order Details", "ncs-cart" );
        ?></h3>
        <div class="sc-order-table">
            <div class="item sc-heading"><strong><?php 
        _e( "Product", "ncs-cart" );
        ?></strong></div>
            <div class="order-total sc-heading"><strong><?php 
        _e( "Price", "ncs-cart" );
        ?></strong></div>
            
            <?php 
        
        if ( $order->subscription_id ) {
            $sub = new ScrtSubscription( $order->subscription_id );
            $subarr = $sub->get_data();
            $text = $subarr['sub_payment_terms'];
            if ( isset( $sub->free_trial_days ) && $sub->free_trial_days > 0 ) {
                // (e.g. " with a 5-day free trial")
                $text .= ', ' . sprintf( __( '%s-day trial', 'ncs-cart' ), $sub->free_trial_days );
            }
            if ( isset( $sub->sign_up_fee ) && $sub->sign_up_fee > 0 ) {
                // (e.g. " and a $5 sign-up fee")
                $text .= ', ' . sprintf( __( '%s sign-up fee', 'ncs-cart' ), sc_format_price( $sub->sign_up_fee ) );
            }
            
            if ( isset( $sub->sub_discount_duration ) ) {
                // (e.g. "Coupon: 5% off for 3 months")
                $text .= '<br><strong>' . __( 'Coupon:', 'ncs-cart' ) . ' </strong> ';
                $text .= sprintf( __( '%s off for %d months', 'ncs-cart' ), sc_format_price( $sub->sub_discount ), $sub->sub_discount_duration );
                $text = apply_filters(
                    'sc_format_subcription_order_detail',
                    $text,
                    $subarr['sub_payment_terms'],
                    $sub->free_trial_days,
                    $sub->sign_up_fee,
                    $sub->sub_discount,
                    $sub->sub_discount_duration,
                    $order->plan
                );
            }
        
        }
        
        ?>
            
            <div class="item">
                <?php 
        echo  '<strong>' . $order->product_name . '</strong>' ;
        ?>
                <?php 
        if ( $order->plan && $order->plan->type == 'recurring' ) {
            echo  '<br><small>' . $text . '</small>' ;
        }
        ?>
                <?php 
        if ( $order->purchase_note ) {
            echo  '<br><span class="sc-purchase-note">' . $order->purchase_note . '</span>' ;
        }
        ?>
            </div>
            
            <div class="order-total">
                <?php 
        
        if ( $order->main_offer_amt == 0 && !$order->subscription_id ) {
            _e( "Free", "ncs-cart" );
        } else {
            sc_formatted_price( $order->main_offer_amt );
        }
        
        $total += floatval( $order->main_offer_amt );
        ?>
            </div>
            
            
            
            <?php 
        if ( isset( $order->custom_prices ) ) {
            foreach ( $order->custom_prices as $price ) {
                ?>
                <div class="item"><strong><?php 
                echo  $price['label'] . ' x ' . $price['qty'] ;
                ?></strong></div>
                <div class="order-total">
                    <?php 
                sc_formatted_price( $price['price'] );
                ?>
                </div>
                <?php 
                $total += floatval( $price['price'] );
                ?>
            <?php 
            }
        }
        ?>
            
            <?php 
        
        if ( !empty($order->order_bumps) && is_array( $order->order_bumps ) ) {
            ?>
                <?php 
            foreach ( $order->order_bumps as $order_bump ) {
                ?>
                    <div class="item">
                        <?php 
                echo  '<strong>' . $order_bump['name'] . '</strong>' ;
                ?>
                        <?php 
                if ( $order->plan->type != 'recurring' && $order->subscription_id ) {
                    echo  '<br><small>' . $text . '</small>' ;
                }
                ?>
                        <?php 
                if ( $order_bump['purchase_note'] ) {
                    echo  '<br><span class="sc-purchase-note">' . $order_bump['purchase_note'] . '</span>' ;
                }
                ?>
                    </div>
                    <div class="order-total">
                        <?php 
                sc_formatted_price( $order_bump['amount'] );
                ?>
                    </div>
                    <?php 
                $total += floatval( $order_bump['amount'] );
                ?>
                <?php 
            }
            ?>
            <?php 
        }
        
        ?>


            <?php 
        
        if ( isset( $order->order_child ) ) {
            ?>
                <?php 
            foreach ( $order->order_child as $child_order ) {
                $productAmount = floatval( $child_order['amount'] );
                if ( isset( $child_order['tax_amount'] ) && !empty($child_order['tax_amount']) ) {
                    $productAmount -= floatval( $child_order['tax_amount'] );
                }
                ?>
                    <div class="item">
						<?php 
                echo  '<strong>' . $child_order['product_name'] . '</strong>' ;
                
                if ( $child_order['subscription_id'] ) {
                    $sub = new ScrtSubscription( $child_order['subscription_id'] );
                    $sub = $sub->get_data();
                    echo  '<br><small>' . $sub['sub_payment_terms'] . '</small>' ;
                }
                
                ?>
                        <?php 
                if ( $child_order['purchase_note'] ) {
                    echo  '<br><span class="sc-purchase-note">' . $child_order['purchase_note'] . '</span>' ;
                }
                ?>
					</div>
                    <div class="order-total">
                        <?php 
                sc_formatted_price( $productAmount );
                ?>
                    </div>
                    <?php 
                $total += floatval( $productAmount );
                
                if ( isset( $child_order['tax_data'] ) && !empty($child_order['tax_amount']) ) {
                    if ( !$order->tax_amount ) {
                        $order->tax_amount = 0;
                    }
                    $order->tax_amount += $child_order['tax_amount'];
                }
            
            }
            ?>
            <?php 
        }
        
        ?>
            
            <?php 
        
        if ( is_object( $order->tax_data ) || $order->coupon_id && in_array( $order->coupon['type'], array( 'cart-percent', 'cart-fixed' ) ) ) {
            ?>
                
                <div class="item" style="border:0;"><strong><?php 
            _e( "Subtotal", "ncs-cart" );
            ?></strong></div>
                <div class="order-total" style="border:0;"><strong><?php 
            echo  sc_formatted_price( $total ) ;
            ?></strong></div>
                <br><br>
            
                <?php 
            
            if ( $order->coupon_id && in_array( $order->coupon['type'], array( 'cart-percent', 'cart-fixed' ) ) ) {
                ?>
                    <div class="item"><?php 
                _e( "Coupon: ", "ncs-cart" );
                echo  $order->coupon_id ;
                ?></div>
                    <div class="order-total">- <?php 
                echo  sc_formatted_price( $order->coupon['discount_amount'] ) ;
                ?></div>
                    <?php 
                $total -= floatval( $order->coupon['discount_amount'] );
                ?>
                <?php 
            }
            
            ?>
            
                <?php 
            
            if ( is_object( $order->tax_data ) ) {
                $redeem_tax = false;
                
                if ( isset( $order->tax_data->redeem_vat ) && $order->tax_data->redeem_vat ) {
                    $redeem_tax = true;
                    
                    if ( $order->tax_data->type != 'inclusive' ) {
                        $order->tax_amount = 0;
                        $order->tax_rate = "0%";
                    }
                
                }
                
                ?>
                    <div class="item"><?php 
                _e( $order->tax_desc . ' (' . $order->tax_rate . ')', 'ncs-cart' );
                ?></div>
					<div class="order-total">
						<?php 
                sc_formatted_price( $order->tax_amount );
                ?>
					</div>
                    <?php 
                
                if ( is_countable( $order->tax_data ) && $order->tax_data->type == 'inclusive' && $redeem_tax ) {
                    ?>
                        <div class="item"><?php 
                    _e( get_option( '_sc_vat_reverse_charge', "VAT Reversal" ), 'ncs-cart' );
                    ?></div>
                        <div class="order-total">
                            -<?php 
                    sc_formatted_price( $order->tax_amount );
                    ?>
                        </div>
                    <?php 
                }
                
                ?>
					<?php 
                if ( !isset( $order->tax_data->type ) || $order->tax_data->type != 'inclusive' && !$redeem_tax ) {
                    $total += floatval( $order->tax_amount );
                }
                ?>
                    <?php 
                if ( !isset( $order->tax_data->type ) || $order->tax_data->type == 'inclusive' && $redeem_tax ) {
                    $total -= floatval( $order->tax_amount );
                }
                ?>
            	<?php 
            }
            
            ?>
              
            <?php 
        }
        
        ?>

            <?php 
        
        if ( $total ) {
            ?>
            <div class="item" style="border: 0"><strong><?php 
            _e( "Order Total", "ncs-cart" );
            ?></strong></div>
            <div class="order-total" style="border: 0">
                <strong><?php 
            sc_formatted_price( $total );
            ?></strong>
            </div>
            <?php 
        }
        
        ?>
        </div>
    </div>
    <?php 
    }

}
add_shortcode( 'sc_order_detail', 'sc_order_detail' );
function sc_order_detail( $atts )
{
    $oto = intval( $_GET['sc-oto'] ?? 0 );
    $oto2 = intval( $_GET['sc-oto-2'] ?? 0 );
    $step = intval( $_GET['step'] ?? 1 );
    $order = false;
    // main order
    
    if ( isset( $_POST['sc_order_id'] ) || isset( $_GET['sc-order'] ) && !isset( $_GET['sc-oto'] ) ) {
        $order_id = intval( $_POST['sc_order_id'] ?? $_GET['sc-order'] );
        $order = new ScrtOrder( $order_id );
        // downsell
    } else {
        
        if ( $oto2 ) {
            $order = new ScrtOrder( $oto2 );
        } else {
            
            if ( isset( $_GET['sc-oto'] ) && !$oto && $step > 1 ) {
                $downsell = $order->get_downsell( $step );
                if ( $downsell ) {
                    $order = $downsell;
                }
                // upsell
            } else {
                if ( $oto ) {
                    $order = new ScrtOrder( $oto );
                }
            }
        
        }
    
    }
    
    if ( !$order ) {
        return;
    }
    $order_info = $order->get_data();
    
    if ( array_key_exists( $atts['field'], $order_info ) ) {
        return $order_info[$atts['field']];
    } else {
        $str = '{' . $atts['field'] . '}';
        return sc_personalize( $str, $order_info );
    }

}

add_shortcode( 'sc_plan', 'sc_plan_detail' );
function sc_plan_detail( $atts )
{
    global  $post ;
    if ( !isset( $atts['product_id'] ) || !$atts['product_id'] ) {
        $atts['product_id'] = $post->ID;
    }
    extract( shortcode_atts( array(
        'id'      => $atts['product_id'],
        'plan_id' => $atts['plan_id'],
        'field'   => 'name',
    ), $atts ) );
    $plan = studiocart_plan( $plan_id, $on_sale = 'current', $id );
    
    if ( isset( $plan->{$field} ) ) {
        if ( $field == 'price' ) {
            return sc_format_price( $plan->price );
        }
        return $plan->{$field};
    } else {
        return '';
    }

}

add_shortcode( 'sc_product', 'sc_product_detail' );
function sc_product_detail( $atts )
{
    global  $scp ;
    
    if ( isset( $atts['id'] ) && $atts['id'] ) {
        $prod = sc_setup_product( $atts['id'] );
    } else {
        
        if ( $scp ) {
            $prod = $scp;
        } else {
            return;
        }
    
    }
    
    extract( shortcode_atts( array(
        'field' => 'name',
    ), $atts ) );
    
    if ( $prod && $field == 'name' ) {
        return sc_get_public_product_name( $prod->ID );
    } else {
        
        if ( $prod && $field == 'limit' ) {
            return $prod->{$field};
        } else {
            return '';
        }
    
    }

}

function sc_test_order_data()
{
    $order_info = new ScrtOrder();
    $order_info->id = '{order_id}';
    $order_info->status = 'paid';
    $order_info->product_name = '{product_name}';
    $order_info->item_name = '{item_name}';
    $order_info->plan = '{plan}';
    $order_info->plan_id = '{plan_id}';
    $order_info->option_id = '{option_id}';
    $order_info->amount = '10.00';
    $order_info->main_offer_amt = '10.00';
    $order_info->pre_tax_amount = '{}';
    $order_info->tax_amount = '1.00';
    $order_info->subscription_id = 0;
    $order_info->firstname = '{firstname}';
    $order_info->lastname = '{lastname}';
    $order_info->first_name = '{first_name}';
    $order_info->last_name = '{last_name}';
    $order_info->customer_name = '{customer_name}';
    $order_info->email = '{email}';
    $order_info->invoice_link = '{invoice_link}';
    $order_info->phone = '{phone}';
    $order_info->country = '{country}';
    $order_info->address1 = '{address1}';
    $order_info->address2 = '{address2}';
    $order_info->city = '{city}';
    $order_info->state = '{state}';
    $order_info->zip = '{zip}';
    $order_info->tax_desc = 'Tax';
    $order_info->tax_rate = 1;
    $order_info->tax_data = (object) array(
        'type' => 'inclusive',
    );
    $order_info->refund_log = array( array(
        'refundID' => '{last_refund_id}',
        'date'     => date( 'Y-m-d' ),
        'amount'   => '10.00',
    ) );
    $order_info = $order_info->get_data();
    $order_info['date'] = '{date}';
    return $order_info;
}

if ( !function_exists( 'sc_do_order_table' ) ) {
    function sc_do_order_table( $type, $order )
    {
        
        if ( $order['ID'] == '{order_id}' ) {
            //var_dump(sc_get_item_list(773));
            $order = (object) $order;
            $order->id = $order->ID;
            $items = array(
                "items"    => array( array(
                "product_name" => "{product_name}",
                "quantity"     => "1",
                "subtotal"     => "10",
                "total_amount" => "10",
                "tax_amount"   => 0,
                "unit_price"   => 10,
            ) ),
                "subtotal" => array(
                "product_name" => "Subtotal",
                "subtotal"     => "10",
                "total_amount" => "10",
                "item_type"    => 'subtotal',
            ),
                "total"    => array(
                "product_name" => "Total",
                "subtotal"     => "10",
                "total_amount" => "10",
                "item_type"    => 'total',
            ),
            );
            //var_dump($items );
            $args = array(
                'type'  => $type,
                'items' => $items,
                'order' => $order,
                'sub'   => false,
            );
        } else {
            $order = new ScrtOrder( $order['ID'] );
            $order = apply_filters( 'studiocart_order', $order );
            $args = array(
                'type'  => $type,
                'items' => sc_get_item_list( $order->id ),
                'order' => $order,
                'sub'   => $order->get_subscription(),
            );
        }
        
        ncs_helper()->renderTemplate( 'email/order-table', $args );
    }

}
function sc_merge_tag_list()
{
    return array(
        'site_name'           => __( 'Site Name', 'ncs-cart' ),
        'customer_name'       => __( 'Customer Name', 'ncs-cart' ),
        'customer_phone'      => __( 'Customer Phone', 'ncs-cart' ),
        'customer_firstname'  => __( 'Customer First Name', 'ncs-cart' ),
        'customer_lastname'   => __( 'Customer Last Name', 'ncs-cart' ),
        'customer_email'      => __( 'Customer Email', 'ncs-cart' ),
        'customer_address'    => __( 'Customer Address', 'ncs-cart' ),
        'customer_address1'   => __( 'Customer Address Line 1', 'ncs-cart' ),
        'customer_address2'   => __( 'Customer Address Line 2', 'ncs-cart' ),
        'customer_city'       => __( 'Customer City', 'ncs-cart' ),
        'customer_state'      => __( 'Customer State', 'ncs-cart' ),
        'customer_zip'        => __( 'Customer Zip', 'ncs-cart' ),
        'customer_country'    => __( 'Customer Country', 'ncs-cart' ),
        'invoice_link'        => __( 'Invoice Link', 'ncs-cart' ),
        'login'               => __( 'My Account/Login URL', 'ncs-cart' ),
        'password'            => __( 'Customer Password', 'ncs-cart' ),
        'username'            => __( 'Customer Username', 'ncs-cart' ),
        'product_name'        => __( 'Main Product Name', 'ncs-cart' ),
        'product_amount'      => __( 'Main Product Amount', 'ncs-cart' ),
        'plan_name'           => __( 'Plan Name', 'ncs-cart' ),
        'order_id'            => __( 'Order ID', 'ncs-cart' ),
        'order_date'          => __( 'Order Date', 'ncs-cart' ),
        'order_list'          => __( 'Order List', 'ncs-cart' ),
        'order_inline_list'   => __( 'Inline Order List', 'ncs-cart' ),
        'order_amount'        => __( 'Order Amount', 'ncs-cart' ),
        'product_list'        => __( 'Product List', 'ncs-cart' ),
        'product_inline_list' => __( 'Inline Product List', 'ncs-cart' ),
        'custom_fields'       => __( 'Custom Fields', 'ncs-cart' ),
        'refund_log'          => __( 'Refund Log', 'ncs-cart' ),
        'last_refund_id'      => __( 'Last Refund ID', 'ncs-cart' ),
        'last_refund_amount'  => __( 'Last Refund Amount', 'ncs-cart' ),
        'last_refund_date'    => __( 'Last Refund Date', 'ncs-cart' ),
        'next_bill_date'      => __( 'Next Bill Date', 'ncs-cart' ),
    );
}

function sc_merge_tag_select()
{
    ?>
    <select class="sc-insert-merge-tag">
        <option value=''><?php 
    esc_html_e( 'Insert Personalization Tag', 'ncs-cart' );
    ?></option>
        <?php 
    foreach ( sc_merge_tag_list() as $tag => $description ) {
        ?>
        <option value="<?php 
        echo  $tag ;
        ?>"><?php 
        echo  $description ;
        ?></option>
        <?php 
    }
    ?>    
    </select>
    <?php 
}

function sc_personalize( $str, $order_info, $filter = false )
{
    if ( !$str ) {
        return;
    }
    $keys = array(
        'firstname',
        'lastname',
        'email',
        'phone'
    );
    foreach ( $keys as $key ) {
        if ( !isset( $order_info[$key] ) || !$order_info[$key] ) {
            $order_info[$key] = ' ';
        }
    }
    $replacements = array(
        'fname'         => $order_info['firstname'],
        'lname'         => $order_info['lastname'],
        'name'          => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'email'         => $order_info['email'],
        'phone'         => $order_info['phone'],
        'coupon_code'   => $order_info['coupon_id'] ?? '',
        'invoice_link'  => $order_info['invoice_link_html'] ?? '',
        'customer_name' => $order_info['firstname'] . ' ' . $order_info['lastname'],
        'site_name'     => get_bloginfo( 'name' ),
        'studiocart'    => '<a href="https://studiocart.co" target="_blank" rel="noreferrer noopener">Studiocart</a>',
        'Studiocart'    => '<a href="https://studiocart.co" target="_blank" rel="noreferrer noopener">Studiocart</a>',
    );
    $customer_fields = array(
        'customer_phone',
        'customer_firstname',
        'customer_lastname',
        'customer_email',
        'customer_address1',
        'customer_address2',
        'customer_city',
        'customer_state',
        'customer_zip',
        'customer_country'
    );
    foreach ( $customer_fields as $customer_field ) {
        $field = str_replace( 'customer_', '', $customer_field );
        $replacements[$customer_field] = $order_info[$field] ?? "";
    }
    if ( $login = get_option( '_sc_myaccount_page_id' ) ) {
        $replacements['login'] = get_permalink( $login );
    }
    if ( isset( $order_info['password'] ) ) {
        $replacements['password'] = $order_info['password'];
    }
    
    if ( isset( $order_info['custom_fields'] ) ) {
        $cf_data = '';
        foreach ( $order_info['custom_fields'] as $k => $v ) {
            
            if ( is_array( $v['value'] ) ) {
                $value = array();
                for ( $i = 0 ;  $i < count( $v['value'] ) ;  $i++ ) {
                    $value[] = ( isset( $v['value_label'][$i] ) ? $v['value_label'][$i] : $v['value'][$i] );
                }
                $value = implode( ', ', $value );
            } else {
                $value = ( isset( $v['value_label'] ) ? $v['value_label'] : $v['value'] );
            }
            
            $replacements['custom_' . $k] = $value;
            $cf_data .= sprintf( '%s: %s<br><br>', $v['label'], $value );
        }
        $replacements['custom_fields'] = $cf_data;
    }
    
    if ( isset( $order_info['username'] ) ) {
        $replacements['username'] = $order_info['username'];
    }
    
    if ( $order_info['ID'] ) {
        $replacements['product_name'] = $order_info['product_name'] ?? sc_get_public_product_name( $order_info['product_id'] );
        $replacements['plan_name'] = $order_info['item_name'] ?? get_post_meta( $order_info['ID'], '_sc_item_name', true );
        $product_name = ( $replacements['plan_name'] != '' ? sprintf( __( '%s - %s', 'ncs-cart' ), $replacements['product_name'], $replacements['plan_name'] ) : $replacements['product_name'] );
        // with pay plan name
        $replacements['order_list'] = $product_name;
        $replacements['order_inline_list'] = $product_name;
        // without pay plan name
        $replacements['product_list'] = $replacements['product_name'];
        $replacements['product_inline_list'] = $replacements['product_name'];
        $replacements['order_id'] = $order_info['ID'];
        $replacements['order_date'] = $order_info['date'] ?? '';
        $replacements['product_amount'] = sc_format_price( $order_info['amount'] );
        $replacements['order_amount'] = sc_format_price( $order_info['amount'] );
        $replacements['customer_address'] = sc_order_address( $order_info['ID'] );
        
        if ( isset( $order_info['sub_next_bill_date'] ) ) {
            if ( !is_numeric( $order_info['sub_next_bill_date'] ) ) {
                $order_info['sub_next_bill_date'] = strtotime( $order_info['sub_next_bill_date'] );
            }
            $replacements['next_bill_date'] = date_i18n( get_option( 'date_format' ), $order_info['sub_next_bill_date'] );
        }
        
        $replacements['last_refund_id'] = $replacements['last_refund_amount'] = $replacements['last_refund_date'] = $replacements['refund_log'] = '';
        
        if ( isset( $order_info['refund_log'] ) && is_countable( $order_info['refund_log'] ) ) {
            $i = 1;
            foreach ( $order_info['refund_log'] as $log ) {
                $replacements['refund_log'] .= sprintf( __( '%s refunded on %s', 'ncs-cart' ), sc_format_price( $log['amount'] ), sc_maybe_format_date( $log['date'] ) );
                
                if ( $i < count( $order_info['refund_log'] ) ) {
                    $replacements['refund_log'] .= '<br>';
                } else {
                    $replacements['last_refund_id'] = $log['refundID'];
                    $replacements['last_refund_amount'] = sc_format_price( $log['amount'] );
                    $replacements['last_refund_date'] = sc_maybe_format_date( $log['date'] );
                }
                
                $i++;
            }
        }
        
        
        if ( !isset( $replacements['username'] ) && ($user_id = get_post_meta( $order_info['ID'], '_sc_user_account', true )) ) {
            $user = get_user_by( 'id', $user_id );
            $replacements['username'] = $user->user_login;
        }
        
        
        if ( $order_info['bump_id'] = get_post_meta( $order_info['ID'], '_sc_bump_id', true ) ) {
            $replacements['order_list'] .= '<br>' . sc_get_public_product_name( $order_info['bump_id'] );
            $replacements['product_list'] .= '<br>' . sc_get_public_product_name( $order_info['bump_id'] );
            $replacements['order_inline_list'] = sprintf( __( '%s, %s', 'ncs-cart' ), $product_name, sc_get_public_product_name( $order_info['bump_id'] ) );
            $replacements['product_inline_list'] = sprintf( __( '%s, %s', 'ncs-cart' ), $product_name, sc_get_public_product_name( $order_info['bump_id'] ) );
        }
        
        
        if ( $order_info['order_bumps'] = get_post_meta( $order_info['ID'], '_sc_order_bumps', true ) ) {
            $products = array( $product_name );
            $total_bump_amt = 0;
            foreach ( $order_info['order_bumps'] as $bump ) {
                $replacements['order_list'] .= '<br>' . $bump['name'];
                $replacements['product_list'] .= '<br>' . $bump['name'];
                $products[] = $bump['name'];
                $total_bump_amt += floatval( $bump['amount'] );
            }
            $replacements['order_inline_list'] = implode( ', ', $products );
            $replacements['product_inline_list'] = implode( ', ', $products );
            $replacements['bump_amount'] = sc_format_price( $total_bump_amt );
            if ( !isset( $order_info['order_type'] ) || $order_info['order_type'] != 'bump' ) {
                $replacements['product_amount'] = sc_format_price( $order_info['amount'] - floatval( $total_bump_amt ) );
            }
        }
        
        
        if ( isset( $order_info['bump_amt'] ) && is_countable( $order_info['bump_amt'] ) ) {
            $total_bump_amt = 0;
            $all_bump_amt = get_post_meta( $order_info['ID'], '_sc_bump_amt', true );
            if ( is_countable( $all_bump_amt ) ) {
                for ( $j = 0 ;  $j < count( $all_bump_amt ) ;  $j++ ) {
                    $total_bump_amt = floatval( $total_bump_amt ) + floatval( $all_bump_amt[$j] );
                }
            }
            $replacements['bump_amount'] = sc_format_price( floatval( $total_bump_amt ) );
            if ( $order_info['order_type'] != 'bump' ) {
                $replacements['product_amount'] = sc_format_price( $order_info['amount'] - floatval( $total_bump_amt ) );
            }
        }
    
    }
    
    $search = $replace = array();
    foreach ( $replacements as $k => $v ) {
        
        if ( $v ) {
            $search[] = '{' . $k . '}';
            $replace[] = ( $filter ? $filter( $v ) : $v );
        }
    
    }
    return str_replace( $search, $replace, $str );
}

function sc_localize_dt( $date = 'now' )
{
    $timezone = ( get_option( 'timezone_string' ) ?: null );
    
    if ( $timezone ) {
        $now = new DateTime( $date, new DateTimeZone( $timezone ) );
    } else {
        $now = new DateTime( $date, $timezone );
    }
    
    return $now;
}

if ( !function_exists( 'sc_is_cart_closed' ) ) {
    function sc_is_cart_closed( $prod_id = false )
    {
        
        if ( $prod_id ) {
            $scp = sc_setup_product( $prod_id );
        } else {
            global  $scp ;
        }
        
        $cart_closed = false;
        // Check if cart is opened or closed
        $now = sc_localize_dt();
        if ( isset( $scp->cart_open ) && $now < sc_localize_dt( $scp->cart_open ) ) {
            return true;
        }
        if ( isset( $scp->cart_close ) && sc_localize_dt( $scp->cart_close ) < $now ) {
            return true;
        }
        // Check if managing stock levels
        if ( isset( $scp->manage_stock ) && $scp->limit < 1 ) {
            return true;
        }
        return false;
    }

}
if ( !function_exists( 'sc_is_prod_on_sale' ) ) {
    function sc_is_prod_on_sale( $prod_id = false )
    {
        // editing/creating order manually
        if ( isset( $_POST['_sc_item_name'] ) && isset( $_POST['on-sale'] ) && is_admin() && current_user_can( 'sc_manager_option' ) ) {
            return true;
        }
        
        if ( $prod_id ) {
            $scp = sc_setup_product( $prod_id );
        } else {
            global  $scp ;
        }
        
        $now = sc_localize_dt();
        
        if ( isset( $scp->on_sale ) ) {
            return true;
        } else {
            
            if ( isset( $scp->schedule_sale ) && (isset( $scp->sale_start ) || isset( $scp->sale_end )) ) {
                if ( $scp->sale_start && sc_localize_dt( $scp->sale_start ) > $now ) {
                    return false;
                }
                if ( $scp->sale_end && sc_localize_dt( $scp->sale_end ) < $now ) {
                    return false;
                }
                return true;
            }
        
        }
        
        return false;
    }

}
// add
if ( !function_exists( 'sc_get_user_subscriptions' ) ) {
    function sc_get_user_subscriptions(
        $user_id,
        $status = 'any',
        $type = null,
        $plan_id = 0
    )
    {
        $postnum = ( current_user_can( 'administrator' ) ? 15 : -1 );
        $plan_id = intval( $plan_id );
        $user_info = get_userdata( $user_id );
        $args = array(
            'posts_per_page' => $postnum,
            'post_type'      => 'sc_subscription',
            'post_status'    => $status,
            'fields'         => 'ids',
        );
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
            'key'   => '_sc_user_account',
            'value' => $user_id,
        ),
            array(
            'key'   => '_sc_email',
            'value' => $user_info->user_email,
        ),
        );
        
        if ( $type == 'installment' ) {
            $comparetype = '!=';
        } else {
            $comparetype = '=';
        }
        
        $args['meta_query'][] = array(
            'key'     => '_sc_sub_installments',
            'value'   => '-1',
            'compare' => $comparetype,
        );
        if ( $plan_id > 0 ) {
            $args['post__in'] = (array) $plan_id;
        }
        $posts = get_posts( $args );
        $subs = array();
        
        if ( !empty($posts) ) {
            foreach ( $posts as $post ) {
                $sub = new ScrtSubscription( $post );
                $subs[] = (object) $sub->get_data();
            }
            return $subs;
        }
        
        return false;
    }

}
if ( !function_exists( 'sc_get_user_orders' ) ) {
    function sc_get_user_orders(
        $user_id,
        $status = array( 'paid', 'completed', 'refunded' ),
        $order_id = 0,
        $renewals = false,
        $hide_free = false
    )
    {
        $postnum = ( current_user_can( 'administrator' ) ? 15 : -1 );
        $user_info = get_userdata( $user_id );
        $status_query = array(
            'key'     => '_sc_status',
            'value'   => $status,
            'compare' => 'IN',
        );
        if ( $status == 'any' ) {
            $status_query = array(
                'key'     => '_sc_status',
                'compare' => 'EXISTS',
            );
        }
        $args = array(
            'posts_per_page' => $postnum,
            'post_type'      => 'sc_order',
            'post_status'    => 'any',
            'meta_query'     => array(
            'relation' => 'AND',
            array(
            'relation' => 'OR',
            $status_query,
            array(
            'relation' => 'AND',
            array(
            'key'   => '_sc_status',
            'value' => 'pending-payment',
        ),
            array(
            'key'   => '_sc_pay_method',
            'value' => 'cod',
        ),
        ),
        ),
        ),
        );
        $args['meta_query'][] = array(
            'relation' => 'OR',
            array(
            'key'   => '_sc_user_account',
            'value' => $user_id,
        ),
            array(
            'key'   => '_sc_email',
            'value' => $user_info->user_email,
        ),
        );
        if ( $hide_free ) {
            $args['meta_query'][] = array( array(
                'key'     => '_sc_amount',
                'value'   => 0,
                'type'    => 'numeric',
                'compare' => '>',
            ) );
        }
        if ( !$renewals ) {
            $args['meta_query'][] = array(
                'key'     => '_sc_renewal_order',
                'compare' => 'NOT EXISTS',
            );
        }
        $order_id = intval( $order_id );
        if ( $order_id > 0 ) {
            $args['post__in'] = (array) $order_id;
        }
        $posts = get_posts( $args );
        $orders = array();
        
        if ( !empty($posts) ) {
            foreach ( $posts as $post ) {
                $order = new ScrtOrder( $post->ID );
                $orders[] = $order->get_data();
            }
            return $orders;
        }
        
        return false;
    }

}
if ( !function_exists( 'sc_get_orders' ) ) {
    function sc_get_orders( $args )
    {
        $defaults = array(
            'numberposts' => 5,
            'post_type'   => 'sc_order',
            'post_status' => 'paid',
        );
        $parsed_args = wp_parse_args( $args, $defaults );
        $posts = get_posts( $parsed_args );
        $orders = array();
        
        if ( !empty($posts) ) {
            foreach ( $posts as $post ) {
                
                if ( $parsed_args['post_type'] == 'sc_subscription' ) {
                    $order_info = new ScrtSubscription( $post->ID );
                } else {
                    $order_info = new ScrtOrder( $post->ID );
                }
                
                $order_info = $order_info->get_data();
                $orders[] = sc_webhook_order_body( $order_info, $args['post_status'] );
            }
            return $orders;
        }
        
        return $posts;
    }

}
if ( !function_exists( 'sc_get_order' ) ) {
    function sc_get_order( $id )
    {
        
        if ( get_post_type( $id ) == 'sc_order' ) {
            $order_info = new ScrtOrder( $id );
            $order_info = $order_info->get_data();
            return sc_webhook_order_body( $order_info );
        }
        
        return null;
    }

}
//translate string for js
if ( !function_exists( 'sc_translate_js' ) ) {
    function sc_translate_js( $js_script = '' )
    {
        $return_data = array();
        
        if ( $js_script == "ncs-cart-admin.js" ) {
            //admin/js/ncs-cart-admin.js
            $return_data = array(
                'invalid_charge_id'    => __( "Invalid Charge ID", "ncs-cart" ),
                'process_refund'       => __( "Are you sure you wish to process this refund? This action cannot be undone.", "ncs-cart" ),
                'wait'                 => __( "Please wait...", "ncs-cart" ),
                'refund_success'       => __( "Refund Successful", "ncs-cart" ),
                'try_again'            => __( "Error: Please try again.", "ncs-cart" ),
                'invalid_sub_id'       => __( "Invalid Subscriber ID", "ncs-cart" ),
                'sub_cancel'           => __( "This subscription has been canceled.", "ncs-cart" ),
                'sub_started'          => __( "Subscription resumed.", "ncs-cart" ),
                'sub_paused'           => __( "This subscription has been paused.", "ncs-cart" ),
                'confirm_cancel_sub'   => __( "Are you sure you wish to cancel this subscription? This action cannot be undone.", "ncs-cart" ),
                'confirm_pause_sub'    => __( "Are you sure you wish to pause this subscription?", "ncs-cart" ),
                'confirm_activate_sub' => __( "Are you sure you wish to resume this subscription?", "ncs-cart" ),
                'sub_cancel'           => __( "This subscription has been canceled.", "ncs-cart" ),
                'something_went_wrong' => __( "Something went wrong. Please try again.", "ncs-cart" ),
                'list_renewed'         => __( "All lists successfully renewed", "ncs-cart" ),
                'missing_required'     => __( "Required fields missing", "ncs-cart" ),
            );
            foreach ( $return_data as $k => $v ) {
                $return_data[$k] = apply_filters( 'sc_backend_message_' . $k, $v );
            }
        } else {
            
            if ( $js_script == "ncs-cart-public.js" ) {
                //public/js/ncs-cart-public.js
                $return_data = array(
                    'empty_username'          => __( "You need to enter your email address to continue.", "ncs-cart" ),
                    'invalid_email'           => __( "There are no users registered with this username or email address.", "ncs-cart" ),
                    'invalidcombo'            => __( "There are no users registered with this username or email address.", "ncs-cart" ),
                    'expiredkey'              => __( 'The password reset link you used is not valid anymore.', 'ncs-cart' ),
                    'invalidkey'              => __( 'The password reset link you used is not valid anymore.', 'ncs-cart' ),
                    'password_reset_mismatch' => __( "The two passwords you entered don't match.", 'ncs-cart' ),
                    'password_reset_empty'    => __( "Please enter in a password.", 'ncs-cart' ),
                    'empty_username'          => __( 'Please enter a username or password.', 'ncs-cart' ),
                    'empty_password'          => __( 'Please enter a password to login.', 'ncs-cart' ),
                    'invalid_username'        => __( "We don't have any users with that username or email address. Maybe you used a different one when signing up?", 'ncs-cart' ),
                    'incorrect_password'      => __( "The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?", 'ncs-cart' ),
                    'sub_cancel'              => __( "This subscription has been canceled.", "ncs-cart" ),
                    'sub_started'             => __( "Subscription resumed.", "ncs-cart" ),
                    'sub_paused'              => __( "This subscription has been paused.", "ncs-cart" ),
                    'confirm_cancel_sub'      => __( "Are you sure you wish to cancel this subscription? This action cannot be undone.", "ncs-cart" ),
                    'confirm_pause_sub'       => __( "Are you sure you wish to pause this subscription?", "ncs-cart" ),
                    'confirm_activate_sub'    => __( "Are you sure you wish to resume this subscription?", "ncs-cart" ),
                    'invalid_email'           => __( 'Enter a valid email', "ncs-cart" ),
                    'invalid_phone'           => __( 'Enter a valid phone number', "ncs-cart" ),
                    'invalid_pass'            => __( 'Use 8 or more characters with a mix of letters, numbers, and symbols', "ncs-cart" ),
                    'includes'                => __( 'includes', "ncs-cart" ),
                    'included_in_price'       => __( 'Included In Price', "ncs-cart" ),
                    'field_required'          => __( 'This field is required', "ncs-cart" ),
                    'username_exists'         => __( "This username already exists", "ncs-cart" ),
                    'with_a'                  => apply_filters( 'sc_plan_text_with_a', __( 'with a', 'ncs-cart' ) ),
                    'day_free_trial'          => apply_filters( 'sc_plan_text_day_free_trial', __( '-day free trial', 'ncs-cart' ) ),
                    'and'                     => apply_filters( 'sc_plan_text_and_a', __( 'and a', 'ncs-cart' ) ),
                    'sign_up_fee'             => apply_filters( 'sc_plan_text_sign_up_fee', __( 'sign-up fee', 'ncs-cart' ) ),
                    'processing'              => __( 'Processing', 'ncs-cart' ),
                    'you_got'                 => __( 'You got', 'ncs-cart' ),
                    'off'                     => __( 'off!', 'ncs-cart' ),
                    'coupon'                  => __( 'Coupon:', 'ncs-cart' ),
                    'discount_off'            => __( 'off', 'ncs-cart' ),
                    'forever'                 => __( 'forever', 'ncs-cart' ),
                    'expires'                 => __( 'expires', 'ncs-cart' ),
                    'day'                     => __( 'day', 'ncs-cart' ),
                    'days'                    => __( 'days', 'ncs-cart' ),
                    'week'                    => __( 'week', 'ncs-cart' ),
                    'weeks'                   => __( 'weeks', 'ncs-cart' ),
                    'month'                   => __( 'month', 'ncs-cart' ),
                    'months'                  => __( 'months', 'ncs-cart' ),
                    'missing_required'        => __( "Required fields missing", "ncs-cart" ),
                    'year'                    => __( 'year', 'ncs-cart' ),
                    'years'                   => __( 'years', 'ncs-cart' ),
                );
                foreach ( $return_data as $k => $v ) {
                    $return_data[$k] = apply_filters( 'sc_frontend_message_' . $k, $v );
                }
            }
        
        }
        
        return $return_data;
    }

}
function sc_enabled_processors()
{
    $processors = [];
    if ( get_option( '_sc_cashondelivery_enable' ) == '1' ) {
        $processors[] = __( 'Cash on Delivery', 'ncs-cart' );
    }
    if ( get_option( '_sc_stripe_enable' ) == '1' ) {
        $processors[] = 'Stripe';
    }
    if ( get_option( '_sc_paypal_enable' ) == '1' ) {
        $processors[] = 'PayPal';
    }
    $processors = apply_filters( 'sc_enabled_processors', $processors );
    return implode( ', ', $processors );
}

function scrt_payment_methods()
{
    global  $sc_stripe ;
    $payment_methods = [];
    // Stripe
    if ( $option_val = get_option( '_sc_stripe_enable' ) == '1' ) {
        if ( is_array( $sc_stripe ) ) {
            $payment_methods['stripe'] = esc_html__( 'Stripe', 'ncs-cart' );
        }
    }
    // COD
    if ( $option_val = get_option( '_sc_cashondelivery_enable' ) == '1' ) {
        $payment_methods['cashondelivery'] = esc_html__( 'Cash on Delivery', 'ncs-cart' );
    }
    return $payment_methods = apply_filters( 'sc_enabled_payment_gateways', $payment_methods );
}

function sc_states_list( $cc = null )
{
    $states = apply_filters( 'sc_states', require plugin_dir_path( __FILE__ ) . 'ncs-cart-states.php' );
    
    if ( !is_null( $cc ) ) {
        return ( isset( $states[$cc] ) ? $states[$cc] : false );
    } else {
        return $states;
    }

}

function sc_countries_list()
{
    return apply_filters( 'sc_countries', require plugin_dir_path( __FILE__ ) . 'ncs-cart-countries.php' );
}

function sc_vat_countries_list()
{
    return array(
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'BG' => 'Bulgaria',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DE' => 'Germany',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'GR' => 'Greece',
        'ES' => 'Spain',
        'FI' => 'Finland',
        'FR' => 'France',
        'HR' => 'Croatia',
        'GB' => 'United Kingdom',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'LV' => 'Latvia',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SE' => 'Sweden',
        'SI' => 'Slovenia',
        'SK' => 'Slovakia',
    );
}

function sc_states_autocomplte_format_list()
{
    $state_list = array();
    $cstates = sc_states_list();
    foreach ( $cstates as $states ) {
        foreach ( $states as $iso_code => $state ) {
            $state_list[] = array(
                'label' => $state,
                'value' => $iso_code,
            );
        }
    }
    return $state_list;
}

function sc_countries_autocomplte_format_list()
{
    $countries_list = array();
    $countries = sc_countries_list();
    foreach ( $countries as $iso_code => $country ) {
        $countries_list[] = array(
            'label' => $country,
            'value' => $iso_code,
        );
    }
    return $countries_list;
    return apply_filters( 'sc_countries', require plugin_dir_path( __FILE__ ) . 'ncs-cart-countries.php' );
}

function sc_validate_payment_key()
{
    $action_needed = array();
    
    if ( get_option( '_sc_stripe_enable' ) == '1' ) {
        $sc_stripe['mode'] = get_option( '_sc_stripe_api' );
        $sc_stripe['sk'] = get_option( '_sc_stripe_' . $sc_stripe['mode'] . '_sk' );
        $sc_stripe['pk'] = get_option( '_sc_stripe_' . $sc_stripe['mode'] . '_pk' );
        \Stripe\Stripe::setApiKey( $sc_stripe['sk'] );
        try {
            \Stripe\WebhookEndpoint::all( [
                'limit' => 1,
            ] );
        } catch ( \Exception $e ) {
            $action_needed['stripe'] = $e->getMessage();
            //add custom message
        }
    }
    
    return apply_filters( 'sc_integration_validation_error', $action_needed );
}

//UNSUBSCRIBE CUSTOMER
function sc_unsubscribe_customer()
{
    global  $wpdb, $sc_stripe, $current_user ;
    wp_get_current_user();
    // Verify nonce
    
    if ( !isset( $_POST['nonce'] ) ) {
        esc_html_e( 'Oops, something went wrong, please try again later.', 'ncs-cart' );
        die;
    }
    
    $post_id = intval( $_POST['id'] );
    $sub_id = sanitize_text_field( $_POST['subscription_id'] );
    $sub = new ScrtSubscription( $post_id );
    $order = $sub->get_data();
    
    if ( !isset( $order['subscription_id'] ) ) {
        esc_html_e( 'Invalid subscription ID', 'ncs-cart' );
        wp_die();
    }
    
    $plan = studiocart_plan( $order['option_id'], '', $order['product_id'] );
    if ( !$plan ) {
        $plan = studiocart_plan( $order['plan_id'], '', $order['product_id'] );
    }
    
    if ( $plan && isset( $plan->cancel_immediately ) && $plan->cancel_immediately == 'no' ) {
        $now = false;
    } else {
        $now = true;
    }
    
    sc_do_cancel_subscription( $sub, $sub_id, $now );
    wp_die();
}

function sc_do_cancel_subscription(
    $sub,
    $sub_id = false,
    $now = true,
    $echo = true
)
{
    global  $sc_stripe ;
    if ( is_numeric( $sub ) ) {
        $sub = new ScrtSubscription( $sub );
    }
    if ( !$sub_id ) {
        
        if ( isset( $_POST['subscription_id'] ) ) {
            $sub_id = $_POST['subscription_id'];
        } else {
            $sub_id = $sub->subscription_id;
        }
    
    }
    $canceled = false;
    
    if ( $sub->pay_method == 'stripe' ) {
        //stripe
        require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        try {
            $stripesub = \Stripe\Subscription::retrieve( $sub_id );
            $sub->cancel_date = date( 'Y-m-d' );
            
            if ( $now ) {
                // cancel now
                $stripesub->cancel();
                
                if ( $stripesub->status == "canceled" ) {
                    $sub->cancel_date = date( 'Y-m-d' );
                    $sub->status = 'canceled';
                    $sub->sub_status = 'canceled';
                    $sub->store();
                    $canceled = true;
                } else {
                    _e( 'Unable to cancel subscription.', 'ncs-cart' );
                }
            
            } else {
                // cancel later
                $stripe = new \Stripe\StripeClient( $apikey );
                $stripesub = $stripe->subscriptions->update( $sub_id, [
                    'cancel_at_period_end' => true,
                ] );
                
                if ( isset( $stripesub->cancel_at ) && $stripesub->cancel_at ) {
                    $sub->cancel_date = date( 'Y-m-d' );
                    $sub->cancel_at();
                    $sub->store();
                    $canceled = true;
                } else {
                    _e( 'Unable to cancel subscription.', 'ncs-cart' );
                }
            
            }
        
        } catch ( \Exception $e ) {
            
            if ( $echo ) {
                echo  $e->getMessage() ;
                //add custom message
            } else {
                return $e->getMessage();
            }
        
        }
    }
    
    $canceled = apply_filters(
        'sc_cancel_subscription',
        $canceled,
        $sub,
        $sub_id,
        $now
    );
    
    if ( $canceled ) {
        $current_user = wp_get_current_user();
        //update status
        $log_entry = sprintf( __( 'Subscription canceled by %s', 'ncs-cart' ), esc_html( $current_user->user_login ) );
        sc_log_entry( $sub->id, $log_entry );
        
        if ( $echo ) {
            echo  'OK' ;
        } else {
            return 'OK';
        }
    
    }

}

function sc_order_refund( $data )
{
    global  $wpdb ;
    $postID = intval( $data['id'] );
    $order = new ScrtOrder( $postID );
    $prodID = intval( $order->product_id );
    $data['refund_amount'] = $data['refund_amount'] ?? $order->amount;
    do_action( 'before_sc_order_refund', $data );
    
    if ( $order->pay_method == 'free' || $order->pay_method == 'cod' ) {
        $amount = $data['refund_amount'];
        $order->refund_log( $amount, 'manual' );
    } else {
        
        if ( !isset( $order->transaction_id ) ) {
            return esc_html__( 'INVALID CHARGE ID', 'ncs-cart' );
        } else {
            
            if ( $order->pay_method == 'stripe' ) {
                //stripe
                require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
                $gateway_mode = $data['mode'] ?? $order->gateway_mode;
                $apikey = get_option( '_sc_stripe_' . sanitize_text_field( $gateway_mode ) . '_sk' );
                if ( empty($apikey) ) {
                    return esc_html__( 'Oops, Stripe ' . $gateway_mode . ' key missing!', 'ncs-cart' );
                }
                \Stripe\Stripe::setApiKey( $apikey );
                try {
                    $refund_amount = sc_price_in_cents( $data['refund_amount'] );
                    $refund_args = array(
                        'amount' => $refund_amount,
                    );
                    
                    if ( substr( $order->transaction_id, 0, 2 ) == 'pi' ) {
                        $refund_args['payment_intent'] = $order->transaction_id;
                    } else {
                        $refund_args['charge'] = $order->transaction_id;
                    }
                    
                    $refund = \Stripe\Refund::create( $refund_args );
                    
                    if ( isset( $refund->id ) && $refund->status == "succeeded" ) {
                        $order->refund_log( $data['refund_amount'], $refund->id );
                    } else {
                        return sprintf( 'Something went wrong, Stripe refund ID: %s and refund status: %s', $refund->id ?? '', $refund->status ?? '' );
                    }
                
                } catch ( \Exception $e ) {
                    return $e->getMessage();
                    //add custom message
                }
            } else {
                try {
                    do_action( 'sc_order_refund_' . $order->pay_method, $data, $order );
                } catch ( \Exception $e ) {
                    return $e->getMessage();
                    //add custom message
                }
            }
        
        }
    
    }
    
    update_post_meta( $postID, '_sc_refund_amount', $data['refund_amount'] );
    $order->status = 'refunded';
    $order->payment_status = 'refunded';
    $order->store();
    $current_user = wp_get_current_user();
    $log_entry = __( 'Payment refunded by', 'ncs-cart' ) . ' ' . $current_user->user_login;
    sc_log_entry( $postID, $log_entry );
    
    if ( $data['restock'] == 'YSE' ) {
        sc_maybe_update_stock( $prodID, 'increase' );
        update_post_meta( $postID, '_sc_refund_restock', 'YES' );
    }
    
    return 'OK';
}

/**
 * Pause-Restart Subscription
 */
add_action( 'wp_ajax_sc_pause_restart_subscription', 'sc_pause_restart_subscription' );
function sc_pause_restart_subscription()
{
    global  $sc_stripe ;
    $response = false;
    // Verify nonce
    
    if ( !isset( $_POST['nonce'] ) ) {
        esc_html_e( 'Oops, something went wrong, please try again later.', 'ncs-cart' );
        die;
    }
    
    $post_id = intval( $_POST['id'] );
    $sub = new ScrtSubscription( $post_id );
    $type = sanitize_text_field( $_POST['type'] );
    $status = ( $type == 'started' ? 'active' : $type );
    
    if ( $sub->pay_method == 'stripe' ) {
        //stripe
        require_once plugin_dir_path( __FILE__ ) . '../includes/vendor/autoload.php';
        $apikey = $sc_stripe['sk'];
        \Stripe\Stripe::setApiKey( $apikey );
        try {
            $stripe = new \Stripe\StripeClient( $apikey );
            $data = array(
                'pause_collection' => array(
                'behavior' => 'void',
            ),
            );
            if ( $type == 'started' ) {
                $data = array(
                    'pause_collection' => '',
                );
            }
            $stripesub = $stripe->subscriptions->update( $sub->subscription_id, $data );
            $response = $stripesub->current_period_end;
        } catch ( \Exception $e ) {
            echo  $e->getMessage() ;
            //add custom message
        }
    } else {
        $response = apply_filters(
            'sc_subscription_pause_restart',
            $response,
            $sub,
            $type
        );
    }
    
    
    if ( $response ) {
        $current_user = wp_get_current_user();
        //update status
        $sub->status = $status;
        $sub->sub_status = $status;
        
        if ( $type == 'paused' ) {
            $log_entry = sprintf( __( 'Subscription paused by %s', 'ncs-cart' ), esc_html( $current_user->user_login ) );
        } else {
            if ( $sub->pay_method == 'stripe' ) {
                $sub->sub_next_bill_date = $response;
            }
            $log_entry = sprintf( __( 'Subscription started by %s', 'ncs-cart' ), esc_html( $current_user->user_login ) );
        }
        
        $sub->store();
        sc_log_entry( $sub->id, $log_entry );
        echo  'OK' ;
        exit;
    }

}

/**
 * Check if a CSV file is valid.
 *
 * @since 3.6.5
 * @param string $file       File name.
 * @param bool   $check_path If should check for the path.
 * @return bool
 */
function sc_is_file_valid_csv( $file, $check_path = true )
{
    /**
     * Filter check for CSV file path.
     *
     * @since 3.6.4
     * @param bool   $check_import_file_path If requires file path check. Defaults to true.
     * @param string $file                   Path of the file to be checked.
     */
    $check_import_file_path = apply_filters( 'woocommerce_csv_importer_check_import_file_path', true, $file );
    if ( $check_path && $check_import_file_path && false !== stripos( $file, '://' ) ) {
        return false;
    }
    /**
     * Filter CSV valid file types.
     *
     * @since 3.6.5
     * @param array $valid_filetypes List of valid file types.
     */
    $valid_filetypes = apply_filters( 'woocommerce_csv_import_valid_filetypes', array(
        'csv' => 'text/csv',
        'txt' => 'text/plain',
    ) );
    $filetype = wp_check_filetype( $file, $valid_filetypes );
    if ( in_array( $filetype['type'], $valid_filetypes, true ) ) {
        return true;
    }
    return false;
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 2.6.0
 * @param int $limit Time limit.
 */
function sc_set_time_limit( $limit = 0 )
{
    
    if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && !ini_get( 'safe_mode' ) ) {
        // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
        @set_time_limit( $limit );
        // @codingStandardsIgnoreLine
    }

}

add_action( 'wp_ajax_update_user_profile', 'update_user_profile' );
function update_user_profile()
{
    $current_user = wp_get_current_user();
    $response = array();
    parse_str( $_POST['form_data'], $data );
    if ( empty($data['first_name']) ) {
        $response['error'] = __( 'Please enter first name.', "ncs-cart" );
    }
    
    if ( empty($data['email']) ) {
        $response['error'] = __( 'Please enter a valid email.', "ncs-cart" );
    } else {
        if ( !is_email( $data['email'] ) ) {
            $response['error'] = __( 'Enter a valid email', "ncs-cart" );
        }
    }
    
    if ( !empty($data['password']) ) {
        if ( $data['password'] != $data['new_password'] ) {
            $response['error'] = __( 'Password and confirm password should match.', "ncs-cart" );
        }
    }
    if ( isset( $response['error'] ) ) {
        wp_send_json( $response );
    }
    if ( !empty($data['_sc_phone']) ) {
        update_user_meta( $current_user->ID, '_sc_phone', sanitize_text_field( $data['_sc_phone'] ) );
    }
    $address = array(
        'address1',
        'address2',
        'city',
        'state',
        'zip',
        'country'
    );
    $subs = $plans = false;
    
    if ( !empty($data['sc-all-subscription-address']) ) {
        $subs = sc_get_user_subscriptions( $current_user->ID, $status = 'active', $type = null );
        $plans = sc_get_user_subscriptions( $current_user->ID, $status = 'active', $type = 'installment' );
    }
    
    foreach ( $address as $field ) {
        
        if ( !empty($data['_sc_' . $field]) ) {
            $val = sanitize_text_field( $data['_sc_' . $field] );
            if ( $subs ) {
                foreach ( $subs as $sub ) {
                    update_post_meta( $sub->id, '_sc_' . $field, $val );
                }
            }
            if ( $plans ) {
                foreach ( $plans as $plan ) {
                    update_post_meta( $plan->id, '_sc_' . $field, $val );
                }
            }
            $field = ( $field == 'address1' ? 'address_1' : $field );
            $field = ( $field == 'address2' ? 'address_2' : $field );
            update_user_meta( $current_user->ID, '_sc_' . $field, $val );
        } else {
            
            if ( $field == 'address2' ) {
                delete_user_meta( $current_user->ID, '_sc_address_2' );
                if ( $subs ) {
                    foreach ( $subs as $sub ) {
                        delete_post_meta( $sub->id, '_sc_' . $field );
                    }
                }
                if ( $plans ) {
                    foreach ( $plans as $plan ) {
                        delete_post_meta( $plan->id, '_sc_' . $field );
                    }
                }
            }
        
        }
    
    }
    update_user_meta( $current_user->ID, '_sc_address', 1 );
    wp_update_user( [
        'ID'         => $current_user->ID,
        'first_name' => $data['first_name'],
        'last_name'  => $data['last_name'],
        'user_email' => $data['email'],
    ] );
    
    if ( !empty($data['password']) ) {
        // Change password.
        wp_set_password( $data['password'], $current_user->ID );
        // Log-in again.
        wp_set_auth_cookie( $current_user->ID );
        wp_set_current_user( $current_user->ID );
        do_action( 'wp_login', $current_user->user_login, $current_user );
    }
    
    wp_send_json( [
        'success' => true,
        'message' => esc_html__( 'Profile details have been saved.', 'ncs-cart' ),
    ] );
}

function sc_get_webhook_url( $payment_slug )
{
    $webhook_url_type = apply_filters( 'sc_webhook_url_type', '' );
    
    if ( $webhook_url_type == 'plain' ) {
        $url = get_site_url() . '/?sc-api=' . $payment_slug;
    } else {
        $url = get_site_url() . '/sc-webhook/' . $payment_slug;
    }
    
    return $url;
}

function is_studiocart()
{
    if ( get_post_type() == 'sc_product' || isset( $_POST['sc_purchase_amount'] ) || isset( $_GET['sc-plan'] ) || isset( $_GET['sc-order'] ) && isset( $_GET['step'] ) ) {
        return true;
    }
    return false;
}

/**
 * Get Customers
 */
function sc_get_customers( $customer_id = 0 )
{
    global  $wpdb ;
    
    if ( $customer_id > 0 ) {
        $result = $wpdb->get_results( "SELECT {$wpdb->prefix}posts.ID,{$wpdb->prefix}postmeta.meta_value FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key = '_sc_user_account' AND {$wpdb->prefix}postmeta.meta_value = '{$customer_id}') AND {$wpdb->prefix}posts.post_type = 'sc_order' AND (({$wpdb->prefix}posts.post_status <> 'trash' AND {$wpdb->prefix}posts.post_status <> 'auto-draft')) ORDER BY `{$wpdb->prefix}posts`.`post_date` DESC" );
    } else {
        $result = $wpdb->get_results( "SELECT {$wpdb->prefix}posts.ID,{$wpdb->prefix}postmeta.meta_value FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key = '_sc_user_account' ) AND {$wpdb->prefix}posts.post_type = 'sc_order' AND (({$wpdb->prefix}posts.post_status <> 'trash' AND {$wpdb->prefix}posts.post_status <> 'auto-draft')) ORDER BY `{$wpdb->prefix}posts`.`post_date` DESC" );
    }
    
    $customers = array();
    foreach ( $result as $key => $post ) {
        $userdata = get_userdata( $post->meta_value );
        $user_email = $userdata->user_email;
        $status = ( in_array( get_post_status( $post->ID ), [ 'pending-payment', 'initiated' ] ) ? 'pending' : get_post_status( $post->ID ) );
        $refundedarray = array();
        
        if ( get_post_meta( $post->ID, '_sc_payment_status', true ) == 'refunded' ) {
            $refund_logs_entrie = get_post_meta( $post->ID, '_sc_refund_log', true );
            $total_amount = get_post_meta( $post->ID, '_sc_amount', true );
            
            if ( is_array( $refund_logs_entrie ) ) {
                $refund_amount = array_sum( array_column( $refund_logs_entrie, 'amount' ) );
                $total_amount = get_post_meta( $post->ID, '_sc_amount', true ) - $refund_amount;
                $total_amount = $total_amount;
                $refundedarray[] = $refund_amount;
            }
        
        } else {
            if ( $status == 'paid' ) {
                $total_amount = get_post_meta( $post->ID, '_sc_amount', true );
            }
        }
        
        
        if ( $status == 'paid' ) {
            $customers[$user_email][] = array(
                'id'           => $post->ID,
                'total_amount' => $total_amount,
            );
        } else {
            $customers[$user_email][] = array(
                'id'           => $post->ID,
                'total_amount' => 0,
            );
        }
    
    }
    return $customers;
}

function sc_check_currency_setting()
{
    $thousand_sep = get_option( '_sc_thousand_separator' );
    $formatted = get_option( 'sc_price_formatted' );
    
    if ( $thousand_sep && $thousand_sep != ',' && $formatted != 'yes' ) {
        $scheduled_time = wp_next_scheduled( 'nsc_run_price_formatting', array() );
        
        if ( !$scheduled_time ) {
            add_action( 'admin_notices', 'sc_db_update_notice' );
            wp_schedule_single_event(
                time(),
                'nsc_run_price_formatting',
                array(),
                true
            );
        } else {
            if ( time() > $scheduled_time + 60 * 60 ) {
                add_action( 'admin_notices', 'sc_db_update_manually_notice' );
            }
        }
    
    }
    
    if ( $formatted == 'yes' ) {
        add_action( 'admin_notices', 'sc_db_update_complete_notice' );
    }
}

add_action( 'nsc_run_price_formatting', 'sc_run_price_formatting' );
function sc_db_update_manually_notice()
{
    $url = $_SERVER['REQUEST_URI'] . "?price_format=yes";
    if ( !empty($_SERVER['QUERY_STRING']) ) {
        $url = $_SERVER['REQUEST_URI'] . "&price_format=yes";
    }
    ?>
    <div class="notice notice-warning">
        <p><?php 
    _e( '<b>Studiocart Data Updater</b> - The database update is taking longer than expected. Click <a href="' . $url . '">here</a> to run it manually.', 'ncs-cart' );
    ?></p>
    </div>
    <?php 
}

function sc_db_update_complete_notice()
{
    if ( get_option( 'dismissed-sc_price_formatted', false ) ) {
        return;
    }
    ?>
    <div class="notice notice-success notice-sc-db-update is-dismissible" data-notice="sc_price_formatted">
        <p><?php 
    _e( '<b>Studiocart Data Updater</b> - The database update process is complete.', 'ncs-cart' );
    ?></p>
    </div>
    <?php 
}

function sc_db_update_notice()
{
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php 
    _e( '<b>Studiocart Data Updater</b> - Version 2.6 database update is in progress.', 'ncs-cart' );
    ?></p>
    </div>
    <?php 
}

function sc_run_price_formatting()
{
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-price-format.php';
    $priceFormat = new NCS_Price_Format();
}


if ( is_admin() ) {
    
    if ( isset( $_GET['price_format'] ) && $_GET['price_format'] == 'yes' && get_option( 'sc_price_formatted' ) != 'yes' ) {
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/ncs-rest/class-ncs-price-format.php';
        $priceFormat = new NCS_Price_Format();
        // delete scheduled db update since we just ran it manually
        wp_clear_scheduled_hook( 'nsc_run_price_formatting', array() );
    }
    
    sc_check_currency_setting();
    if ( isset( $_GET['format_err'] ) ) {
        add_action( 'admin_notices', 'ncs_price_format_error' );
    }
}

function ncs_price_format_error()
{
    ?>
    <div class="notice notice-error is-dismissible">
        <p><?php 
    _e( 'Error: Invalid price format - ' . $_GET['format_err'] . '. Price format should match price format setting.', 'ncs-cart' );
    ?></p>
    </div>
    <?php 
}

add_action( 'wp_ajax_sc_dismissed_notice_handler', 'sc_ajax_notice_handler' );
function sc_ajax_notice_handler()
{
    $type = sanitize_text_field( $_REQUEST['type'] );
    update_option( 'dismissed-' . $type, true );
}

add_action( 'admin_print_scripts', 'sc_alert_print_scripts', 9999 );
function sc_alert_print_scripts()
{
    ?>
    <script>
        jQuery(function($) {	
            $( document ).on( 'click', '.notice-sc-db-update .notice-dismiss', function () {
                var type = $( this ).closest( '.notice-sc-db-update' ).data( 'notice' );
                $.ajax( ajaxurl,
                {
                    type: 'POST',
                    data: {
                    action: 'sc_dismissed_notice_handler',
                    type: type,
                    }
                } );
            } );
        });
    </script>
    <?php 
}
