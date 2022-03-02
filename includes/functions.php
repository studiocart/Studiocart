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
                        $field['choices'] = array(
                            '' => $field['label'],
                        );
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
                            
                            if ( $v && preg_match( '/^"([^ ].*[^ ])"$/', html_entity_decode( $v ), $val ) ) {
                                $map[$k] = $val[1];
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
if ( !function_exists( 'sc_add_remove_kajabi_subscriber' ) ) {
    function sc_add_remove_kajabi_subscriber(
        $order_id,
        $service_id,
        $action_name,
        $kajabi_email_confirmation,
        $kajabi_url,
        $email,
        $phone,
        $fname,
        $lname
    )
    {
        global  $wpdb ;
        if ( empty($service_id) || empty($email) || empty($kajabi_url) ) {
            return;
        }
        $kajabi_add = array(
            'email'            => $email,
            'name'             => $fname . ' ' . $lname,
            'external_user_id' => $email,
        );
        $url = esc_url_raw( $kajabi_url );
        if ( !empty($kajabi_email_confirmation) ) {
            $url .= '?send_offer_grant_email=true';
        }
        $request = wp_remote_post( $url, array(
            'method'      => 'POST',
            'body'        => json_encode( $kajabi_add ),
            'timeout'     => 0,
            'sslverify'   => false,
            'redirection' => 10,
            'httpversion' => '1.0',
            'headers'     => array(
            'Content-Type' => 'application/json',
        ),
        ) );
        sc_log_entry( $order_id, __( ' Kajabi webhook response: ', 'ncs-cart' ) . $request['body'] );
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
                    $mailpoet_api->subscribeToLists( $subscriber['email'], $mailpoet_lists );
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
        update_post_meta( $order_id, '_sc_subscription_id', $arr['subscription_id'] );
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
                    'SendMail'            => $SendMail,
                    'SendMailPerLevel'    => array( $wlm_level ),
                    'wpm_registration_ip' => get_post_meta( $order_id, '_sc_ip_address', true ),
                    'custom_sc_order_id'  => $order_id,
                );
                
                if ( $SendMail ) {
                    
                    if ( $SendMail == 'level' ) {
                        $args['SendMailPerLevel'] = array( $wlm_level );
                    } else {
                        $args['SendMail'] = true;
                    }
                
                } else {
                    $args['SendMail'] = false;
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
                    $val = get_post_meta( $order, '_sc_' . $info, true );
                    if ( $val ) {
                        $args[$info] = $val;
                    }
                }
                $member = wlmapi_add_member( $args );
                $user_id = $member['member'][0]['ID'];
                sc_maybe_auto_login_user( $user_id, $order_id );
                update_post_meta( $order_id, '_sc_user_account', $user_id );
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
                        'Users'     => array( $user_id ),
                        'Cancelled' => true,
                    );
                    $members = wlmapi_add_member_to_level( $wlm_level, $args );
                    $msg = __( 'Contact successfully cancelled from Wishlist Level ID: ', 'ncs-cart' ) . $wlm_level;
                }
            
            }
        
        }
        
        sc_log_entry( $order_id, $msg );
        return;
    }

}
if ( !function_exists( 'sc_add_remove_to_rcp_level' ) ) {
    function sc_add_remove_to_rcp_level(
        $order_id,
        $_sc_services,
        $level,
        $status,
        $customerEmail,
        $first_name,
        $last_name
    )
    {
        if ( empty($level) || !function_exists( 'rcp_add_membership' ) ) {
            return;
        }
        $user_id = email_exists( $customerEmail );
        // no user id: create customer if status is pending or active, otherwise do nothing and return
        
        if ( !$user_id ) {
            $creds = sc_generate_login_creds( $customerEmail );
            
            if ( in_array( $status, [ 'pending', 'active' ] ) ) {
                $user_id = rcp_add_customer( array(
                    'user_args' => array(
                    'user_email' => $customerEmail,
                    'user_login' => $creds['username'],
                    'user_pass'  => $creds['password'],
                    'first_name' => $first_name,
                    'last_name'  => $last_name,
                ),
                ) );
                sc_maybe_auto_login_user( $user_id, $order_id );
            } else {
                $msg = sprintf( __( 'Unable to change Restrict Content Pro membership status to %s because no user ID was found.', 'ncs-cart' ), $status );
                sc_log_entry( $order_id, $msg );
                return;
            }
        
        }
        
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
            return;
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
                'customer_id' => $customer_id,
                'object_id'   => $level,
                'status'      => $status,
            );
            $membership_id = rcp_update_membership( $memberships[0]->get_id(), $args );
            
            if ( $membership_id ) {
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
                
                if ( !empty($_sc_mail_forms) ) {
                    $url = "https://api.convertkit.com/v3/forms/{$_sc_mail_forms}/subscribe";
                    $data = array(
                        'api_key'    => $apikey,
                        'first_name' => $fname,
                        'email'      => $email,
                        'fields'     => array(
                        'name'  => $full_name,
                        'phone' => $phone,
                    ),
                    );
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
                    
                    if ( !empty($_sc_mail_tags) ) {
                        $url = "https://api.convertkit.com/v3/tags/{$_sc_mail_tags}/subscribe";
                        $data = array(
                            'api_key' => $apikey,
                            'email'   => $email,
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
                            $log_entry = __( 'ConvertKit subscriber tagged', 'ncs-cart' );
                            sc_log_entry( $order_id, $log_entry );
                        }
                    
                    }
                
                }
            
            } else {
                //remove contact
                
                if ( !empty($_sc_mail_tags) ) {
                    $url = "https://api.convertkit.com/v3/tags/{$_sc_mail_tags}/unsubscribe";
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
                        sc_log_entry( $order_id, "Something went wrong with removing ConvertKit tag: {$error_message}" );
                    } else {
                        sc_log_entry( $order_id, "ConvertKit tag removed from subscriber." );
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
        if ( empty($service_id) || empty($action_name) || empty($list_id || empty($email)) ) {
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
                        $groups = explode( ',', $_sc_mail_groups );
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
                        $tags = explode( ',', $_sc_mail_tags );
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

function sc_format_number( $amt, $string = false )
{
    $decisep = get_option( '_sc_decimal_separator' );
    $decinum = intval( get_option( '_sc_decimal_number' ) );
    if ( !$decisep ) {
        $decisep = '.';
    }
    
    if ( $string ) {
        $thousep = get_option( '_sc_thousand_separator' );
        if ( !$thousep ) {
            $thousep = ',';
        }
    } else {
        $thousep = '';
    }
    
    // format price
    return number_format(
        floatval( $amt ),
        $decinum,
        $decisep,
        $thousep
    );
}

function sc_format_price( $amt, $html = true )
{
    global  $sc_currency_symbol ;
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
    $price .= sc_format_number( $amt, true );
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

function sc_order_address( $id )
{
    $order = sc_setup_order( $id );
    $str = ' ';
    
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
        $sub_id = get_post_meta( $sub_id, '_sc_subscription_id', true );
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
    
    if ( $status == 'lead' ) {
        do_action(
            'sc_order_lead',
            $status,
            $order_info,
            $order_info['product_id']
        );
    } else {
        // setup order object
        
        if ( is_numeric( $order_info ) || is_array( $order_info ) && !isset( $order_info['option_id'] ) ) {
            // reset array to just the ID
            if ( is_array( $order_info ) ) {
                $order_info = $order_info['ID'];
            }
            
            if ( get_post_type( $order_info ) == 'sc_order' ) {
                $order_info = new ScrtOrder( $order_info );
            } else {
                $order_info = new ScrtSubscription( $order_info );
            }
            
            $order_info = $order_info->get_data();
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
        
        if ( (!isset( $order_info['order_type'] ) || $order_info['order_type'] == 'main') && $order_info['transaction_id'] && $order_info['subscription_id'] ) {
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
                
                if ( get_post_meta( $order_info['ID'], '_sc_renewal_order', true ) ) {
                    $action = 'sc_renewal_payment';
                } else {
                    $action = 'sc_order_complete';
                }
                
                break;
            case 'completed':
                
                if ( get_post_type( $order_info['ID'] ) == 'sc_subscription' ) {
                    $action = 'sc_subscription_completed';
                } else {
                    $action = 'sc_order_complete';
                    $status = 'paid';
                }
                
                break;
            case 'active':
                $action = 'sc_subscription_active';
                break;
            case 'canceled':
                $action = 'sc_subscription_canceled';
                break;
            case 'past_due':
                $action = 'sc_subscription_past_due';
                break;
            case 'renewal':
                $action = 'sc_renewal_payment';
                break;
            case 'failed':
                $action = 'sc_renewal_failed';
                break;
            case 'uncollectible':
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

function sc_do_integrations( $sc_product_id, $order, $trigger = 'purchased' )
{
    
    if ( $trigger != 'lead' ) {
        $order_id = $order['id'];
        $customerEmail = $order['email'];
        $phone = $order['phone'];
        $first_name = $order['first_name'];
        $last_name = $order['last_name'];
        $plan_id = $order['plan_id'];
        $option_id = ( isset( $order['option_id'] ) ? $order['option_id'] : get_post_meta( $order_id, '_sc_option_id', true ) );
        $order_type = $order['order_type'];
        if ( !isset( $order_type ) ) {
            $order_type = 'main';
        }
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
                $consent = ( $order['consent'] == 'yes' ? true : false );
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
            $kajabi_email_confirmation = ( isset( $intg['kajabi_email_confirmation'] ) ? $intg['kajabi_email_confirmation'] : "" );
            //kajabi_email_confirmation
            $kajabi_url = ( isset( $intg['kajabi_url'] ) ? $intg['kajabi_url'] : "" );
            //kajabi_url
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
                $fieldmap = $intg['activecampaign_field_map'];
                if ( $_sc_services == "activecampaign" ) {
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
                
                //check if kajabi
                if ( $_sc_services == "kajabi" && !empty($kajabi_url) ) {
                    sc_add_remove_kajabi_subscriber(
                        $order_id,
                        $_sc_services,
                        $_sc_service_action,
                        $kajabi_email_confirmation,
                        $kajabi_url,
                        $customerEmail,
                        $phone = '',
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

function sc_webhook_order_body( $order, $type = '' )
{
    
    if ( is_numeric( $order ) ) {
        $order = new ScrtOrder( $order );
        $order = $order->get_data();
    }
    
    $body = array();
    // backwards compatibility
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
    $body['custom_fields'] = $order['custom_fields'];
    
    if ( $type != 'lead' ) {
        $body['payment_plan'] = $order['item_name'];
        $body['payment_plan_id'] = $order['option_id'];
        $body['order_id'] = $order['ID'];
        $body['order_amount'] = (double) $order['amount'];
        $body['order_status'] = $order['status'];
        $body['signup_consent'] = $order['consent'];
        $body['invoice_total'] = (double) $order['invoice_total'];
        $body['invoice_subtotal'] = (double) $order['invoice_subtotal'];
        $body['tax_amount'] = (double) $order['tax_amount'];
        $body['tax_rate'] = $order['tax_rate'];
        $body['tax_type'] = $order['tax_type'];
        $body['tax_desc'] = $order['tax_desc'];
        $body['vat_number'] = $order['vat_number'];
        
        if ( is_array( $order['order_bumps'] ) ) {
            $i = 1;
            foreach ( $order['order_bumps'] as $i => $bump ) {
                $body['bump_' . $i . '_amount'] = (double) $bump['amount'];
                $body['bump_' . $i . '_id'] = $bump['id'];
                $body['bump_' . $i . '_name'] = $bump['name'];
                $i++;
            }
        }
        
        $body['coupon'] = $order['coupon_id'];
        $body['currency'] = $order['currency'];
    }
    
    $body['website_url'] = get_site_url();
    $body['order_url'] = $order['page_url'];
    $body['studiocart_secret'] = get_option( '_sc_api_key' );
    $body = array_filter( $body, function ( $v ) {
        return !is_null( $v ) && $v !== '';
    } );
    return apply_filters( 'sc_webhook_order_data', $body );
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
    $send_email_override = null
)
{
    
    if ( $user_id = email_exists( $customerEmail ) ) {
        update_post_meta( $order_id, '_sc_user_account', $user_id );
        sc_log_entry( $order_id, sprintf( __( "A user with this email address already exists (User ID: %s)", 'ncs-cart' ), $user_id ) );
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
    
    if ( isset( $_POST['sc-auto-login'] ) || isset( $_POST['sc_process_payment'] ) ) {
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
    }

}

function sc_new_user_notification( $user, $order_id )
{
    $from_name = get_option( '_sc_email_from_name' );
    $from_email = get_option( '_sc_email_from_email' );
    $login = get_option( '_my_account' );
    $order_info = (array) sc_setup_order( $order_id );
    $order_info['username'] = $user['user_login'];
    $order_info['password'] = $user['user_pass'];
    $subject = sc_personalize( get_option( '_sc_registration_subject' ), $order_info );
    $body = sc_personalize( get_option( '_sc_registration_email_body' ), $order_info );
    $body = html_entity_decode( $body, ENT_QUOTES, "UTF-8" );
    $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
    $to = $user['user_email'];
    wp_mail(
        $to,
        $subject,
        $body,
        $headers
    );
    
    if ( get_option( '_sc_registration_email_admin' ) ) {
        $to = get_option( 'admin_email' );
        wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
    }

}

function studiocart_notification_send( $status, $order_info )
{
    switch ( $status ) {
        case 'paid':
            $type = 'confirmation';
            break;
        default:
            $type = $status;
            break;
    }
    
    if ( $type && get_option( '_sc_email_' . $type . '_enable' ) ) {
        
        if ( is_numeric( $order_info ) ) {
            $order_info = new ScrtOrder( $order_info );
            $order_info = $order_info->get_data();
        }
        
        $em = '_sc_email_' . $type . '_';
        $from_name = get_option( '_sc_email_from_name' );
        $from_email = get_option( '_sc_email_from_email' );
        $subject = sc_personalize( get_option( $em . 'subject' ), $order_info );
        $headline = get_option( $em . 'headline' ) ?? '';
        $body = get_option( $em . 'body' ) ?? '';
        $atts = array(
            'type'       => $type,
            'order_info' => $order_info,
            'headline'   => sc_personalize( $headline, $order_info ),
            'body'       => sc_personalize( $body, $order_info ),
        );
        $body = sc_get_email_html( $atts );
        $to = trim( $order_info['email'] );
        $firstname = trim( $order_info['firstname'] );
        $lastname = trim( $order_info['lastname'] );
        $body = html_entity_decode( $body, ENT_QUOTES, "UTF-8" );
        $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
        wp_mail(
            $to,
            $subject,
            $body,
            $headers
        );
        
        if ( get_option( $em . 'admin' ) ) {
            $to = get_option( 'admin_email' );
            wp_mail(
                $to,
                $subject,
                $body,
                $headers
            );
        }
    
    }

}

function sc_get_email_html( $atts )
{
    ob_start();
    include_once dirname( __FILE__ ) . '/../public/templates/email/email-main.php';
    $output_string = ob_get_contents();
    ob_end_clean();
    return $output_string;
}

function sc_do_notifications( $order_info )
{
    //sc_log_entry($order_info['ID'], "doing notifications for product_id: " . $order_info['product_id']);
    $notifications = get_post_meta( $order_info['product_id'], '_sc_notifications', true );
    //get integration meta mailchimp
    if ( $notifications ) {
        foreach ( $notifications as $k => $n ) {
            switch ( $n['send_to'] ) {
                case 'enter':
                    $to = $n['send_to_email'];
                    break;
                case 'purchaser':
                    $to = $order_info['email'];
                    break;
                default:
                    $to = get_option( 'admin_email' );
                    break;
            }
            $from_name = ( $n['from_name'] ? $n['from_name'] : get_bloginfo( 'name' ) );
            $from_email = ( $n['from_email'] ? $n['from_email'] : get_option( 'admin_email' ) );
            $subject = wp_specialchars_decode( sc_personalize( $n['subject'], $order_info ) );
            $body = wpautop( wp_specialchars_decode( sc_personalize( $n['message'], $order_info ), 'ENT_QUOTES' ), false );
            $headers = array( 'Content-Type: text/html; charset=UTF-8', 'From: ' . $from_name . ' <' . $from_email . '>' );
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

function get_sc_price( $amount, $sc_currency = 'USD' )
{
    $zero_decimal_currency = get_sc_zero_decimal_currency();
    if ( !in_array( $sc_currency, $zero_decimal_currency ) ) {
        $amount = $amount * 100;
    }
    return $amount;
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
function sc_setup_product( $id )
{
    if ( !$id || get_post_type( $id ) != 'sc_product' ) {
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
    
    // set upsell links to thank you page by default
    $arr['us_accept_url'] = $arr['us_decline_url'] = $arr['thanks_url'];
    
    if ( isset( $arr['downsell'] ) ) {
        /* 
        upsell 2 visibility rules
        always:       yes = downsell, no = downsell
        us1 accepted: yes = downsell, no = thanks
        us1 declined: yes = thanks, no = downsell
        */
        // set decline link to something else if downsell is display always or upsell declined
        if ( !isset( $arr['ds_display'] ) || $arr['ds_display'] == 'declined' ) {
            
            if ( !isset( $arr['ds_type'] ) ) {
                $arr['us_decline_url'] = get_permalink( $arr['ID'] );
            } else {
                switch ( $arr['ds_type'] ) {
                    case 'template':
                        $arr['us_decline_url'] = get_permalink( $arr['ds_template'] );
                        break;
                    case 'page':
                        $arr['us_decline_url'] = get_permalink( $arr['ds_page'] );
                        break;
                    default:
                        $arr['us_decline_url'] = get_permalink( $arr['ID'] );
                        break;
                }
            }
        
        }
        // set accept link to something else if downsell is display always or upsell accepted
        if ( !isset( $arr['ds_display'] ) || $arr['ds_display'] == 'accepted' ) {
            
            if ( !isset( $arr['ds_type'] ) ) {
                $arr['us_decline_url'] = get_permalink( $arr['ID'] );
            } else {
                switch ( $arr['ds_type'] ) {
                    case 'template':
                        $arr['us_accept_url'] = get_permalink( $arr['ds_template'] );
                        break;
                    case 'page':
                        $arr['us_accept_url'] = get_permalink( $arr['ds_page'] );
                        break;
                    default:
                        $arr['us_accept_url'] = get_permalink( $arr['ID'] );
                        break;
                }
            }
        
        }
    }
    
    $arr['form_action'] = $arr['thanks_url'];
    if ( isset( $arr['upsell'] ) ) {
        
        if ( !isset( $arr['us_type'] ) ) {
            $arr['form_action'] = get_permalink( $arr['ID'] );
        } else {
            switch ( $arr['us_type'] ) {
                case 'template':
                    $arr['form_action'] = get_permalink( $arr['us_template'] );
                    break;
                case 'page':
                    $arr['form_action'] = get_permalink( $arr['us_page'] );
                    break;
                default:
                    $arr['form_action'] = get_permalink( $arr['ID'] );
                    break;
            }
        }
    
    }
    if ( $arr['confirmation'] == 'redirect' ) {
        $arr['redirect_url'] = $arr['redirect'];
    }
    $arr['product_taxable'] = false;
    
    if ( get_option( '_sc_tax_enable', false ) ) {
        $arr['tax_type'] = get_option( '_sc_tax_type', 'inclusive_tax' );
        
        if ( $arr['tax_type'] != 'non_tax' ) {
            $arr['product_taxable'] = true;
            $arr['price_show_with_tax'] = get_option( '_sc_price_show_with_tax', 'exclude_tax' );
        }
    
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
    $arr = wp_parse_args( $arr, $default_atts );
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
    // add studiocart plan to order info
    $option_id = $arr['option_id'] ?? $arr['plan_id'];
    $arr['plan'] = studiocart_plan( $option_id, $arr['on_sale'], $arr['product_id'] );
    if ( !isset( $arr['sub_end_date'] ) && $arr['sub_installments'] > 1 || $arr['sub_end_date'] == '1970-01-01' ) {
        
        if ( $arr['sub_installments'] > 1 ) {
            $duration = $arr['sub_installments'] * $arr['sub_frequency'];
            $cancel_at = $duration . ' ' . $arr['sub_interval'];
            if ( $arr['sub_trial_days'] ) {
                $cancel_at .= " + " . $arr['sub_trial_days'] . " day";
            }
            $arr['sub_end_date'] = date( "Y-m-d", strtotime( $arr['date'] . ' + ' . $cancel_at ) );
            update_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
        } else {
            unset( $arr['sub_end_date'] );
            delete_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
        }
    
    }
    
    if ( get_post_type( $arr['ID'] ) == 'sc_subscription' && !isset( $arr['subscription_id'] ) ) {
        $arr['subscription_id'] = sc_get_subscription_txn_id( $arr['ID'] );
    } else {
        if ( get_post_type( $arr['ID'] ) == 'sc_order' && !isset( $arr['transaction_id'] ) ) {
            $arr['transaction_id'] = sc_get_transaction_id( $arr['ID'] );
        }
    }
    
    $arr['customer_name'] = $arr['firstname'] . ' ' . $arr['lastname'];
    
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
        $option_id = $arr['option_id'] ?? $arr['plan_id'];
    }
    
    $arr['product_name_plan'] = $arr['product_name'];
    if ( isset( $arr['item_name'] ) ) {
        $arr['product_name_plan'] .= ' - ' . $arr['item_name'];
    }
    // add studiocart plan to order info
    $arr['plan'] = studiocart_plan( $option_id, $arr['on_sale'], $arr['product_id'] );
    
    if ( $arr['sub_end_date'] == '1970-01-01' ) {
        $duration = $arr['sub_installments'] * $arr['sub_frequency'];
        $cancel_at = $duration . ' ' . $arr['sub_interval'];
        if ( $arr['sub_trial_days'] ) {
            $cancel_at .= " + " . $arr['sub_trial_days'] . " day";
        }
        $arr['sub_end_date'] = date( "Y-m-d", strtotime( $arr['date'] . ' + ' . $cancel_at ) );
        update_post_meta( $arr['ID'], '_sc_sub_end_date', $arr['sub_end_date'] );
    }
    
    if ( $arr['status'] == 'initiated' || $arr['status'] == 'pending payment' ) {
        $arr['status'] = 'pending';
    }
    $arr['main_offer_amt'] = $arr['amount'];
    // amount paid for main offer including discount
    $arr['invoice_total'] += $arr['amount'];
    // total amount paid including child orders and discount
    $arr['invoice_subtotal'] = $arr['invoice_total'];
    if ( isset( $arr['discount_details'] ) ) {
        $arr['invoice_subtotal'] += $arr['discount_details']['discount_amt'];
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
        $arr['main_offer_amt'] = $arr['plan_price'] - $arr['discount_details']['discount_amt'];
    }
    if ( !empty($arr['order_bumps']) && is_array( $arr['order_bumps'] ) ) {
        foreach ( $arr['order_bumps'] as $order_bump ) {
            $arr['main_offer_amt'] -= $order_bump['amount'];
        }
    }
    
    if ( isset( $arr['custom_prices'] ) ) {
        $fields = $arr['custom_fields'];
        foreach ( $arr['custom_prices'] as $k => $v ) {
            $custom[$k]['label'] = $fields[$k]['label'];
            $custom[$k]['qty'] = $fields[$k]['value'];
            $custom[$k]['price'] = $v;
            if ( !isset( $arr['plan_price'] ) ) {
                $arr['main_offer_amt'] -= $v;
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
            $arr['sub_payment'] .= $arr['sub_interval'];
            $arr['sub_payment_plain'] .= $arr['sub_interval'];
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
    $old_next = get_post_meta( $post_id, '_sc_sub_next_bill_date', true );
    return max( $next, $old_next );
}

function studiocart_plan(
    $option_id,
    $sale = '',
    $product_id = '',
    $array = false
)
{
    
    if ( !$product_id ) {
        global  $scp ;
        $plans = $scp->pay_options;
    } else {
        $plans = get_post_meta( $product_id, '_sc_pay_options', true );
    }
    
    if ( !$plans ) {
        return false;
    }
    foreach ( $plans as $val ) {
        if ( $option_id == $val['option_id'] || $option_id == $val['stripe_plan_id'] || $option_id == $val['sale_stripe_plan_id'] ) {
            $option = $val;
        }
    }
    if ( !isset( $option ) || !$option ) {
        return false;
    }
    if ( $sale == 'current' ) {
        $sale = sc_is_prod_on_sale( $product_id );
    }
    $sale = ( $sale ? 'sale_' : '' );
    $plan = array();
    $plan['type'] = ( $option['product_type'] == '' ? 'one-time' : $option['product_type'] );
    $plan['option_id'] = $option['option_id'];
    $plan['name'] = $option[$sale . 'option_name'];
    $plan['stripe_id'] = $option[$sale . 'stripe_plan_id'];
    $plan['price'] = ( $option['product_type'] == 'free' ? 'free' : $option[$sale . 'price'] );
    $plan['initial_payment'] = $plan['price'];
    $plan['cancel_immediately'] = $option['cancel_immediately'];
    $plan['tax_rate'] = $option['tax_rate'] ?? '';
    
    if ( $option['product_type'] == 'free' ) {
        $plan['price'] = 'free';
    } elseif ( $option['product_type'] == 'pwyw' ) {
        $plan['price'] = $option['pwyw_amount'];
    } else {
        $plan['price'] = $option[$sale . 'price'];
    }
    
    
    if ( $plan['type'] == 'recurring' ) {
        $plan['installments'] = $option[$sale . 'installments'];
        $plan['interval'] = $option[$sale . 'interval'];
        $plan['frequency'] = $option[$sale . 'frequency'] ?? 1;
        
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
        sc_log_entry( $subscription_id, __( 'Subscription completed', 'ncs-cart' ) );
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
            // (e.g. "5-day trial")
            $terms .= ', ' . $trial_days . apply_filters( 'sc_plan_text_day_free_trial', __( '-day trial', 'ncs-cart' ) );
        }
        if ( $sign_up_fee && $sign_up_fee > 0 ) {
            // (e.g. "$5 sign-up fee")
            $terms .= ', ' . sc_format_price( $sign_up_fee ) . apply_filters( 'sc_plan_text_sign_up_fee', __( ' sign-up fee', 'ncs-cart' ) );
        }
        
        if ( $discount && $discount_duration && $discount > 0 ) {
            // (e.g. "Discount: 5% off for 3 months")
            $terms .= '<br><strong>' . __( 'Discount:', 'ncs-cart' ) . ' </strong> ';
            $terms .= sprintf( __( '%s off for %d months', 'ncs-cart' ), sc_format_price( $discount ), $discount_duration );
        }
        
        echo  $terms ;
    }

}
if ( !function_exists( 'sc_order_details' ) ) {
    function sc_order_details( $order_id )
    {
        $order = new ScrtOrder( $order_id );
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
            <div class="item"><strong><?php 
        _e( "Product", "ncs-cart" );
        ?></strong></div>
            <div class="order-total"><strong><?php 
        _e( "Price", "ncs-cart" );
        ?></strong></div>

            <div class="item"><?php 
        echo  $order->product_name ;
        ?>
            <?php 
        
        if ( $order->subscription_id && $order->plan->type == 'recurring' ) {
            echo  '<br><small>' ;
            $sub = new ScrtSubscription( $order->subscription_id );
            $terms = '';
            //var_dump($sub);
            
            if ( $sub->sub_frequency > 1 ) {
                $terms .= sprintf(
                    __( '%s every %s %s', 'ncs-cart' ),
                    sc_format_price( $sub->sub_amount ),
                    $sub->sub_frequency,
                    sc_pluralize_interval( $sub->sub_interval )
                );
            } else {
                $terms .= sc_format_price( $sub->sub_amount ) . ' / ' . $sub->sub_interval;
            }
            
            $installments = $sub->sub_installments;
            if ( $installments > 1 ) {
                $terms .= ' x ' . $installments;
            }
            $text = $terms;
            if ( isset( $sub->free_trial_days ) && $sub->free_trial_days > 0 ) {
                // (e.g. " with a 5-day free trial")
                $text .= ', ' . sprintf( __( '%s-day trial', 'ncs-cart' ), $sub->free_trial_days );
            }
            if ( isset( $sub->sign_up_fee ) && $sub->sign_up_fee > 0 ) {
                //$total += $sub->sign_up_fee;
                // (e.g. " and a $5 sign-up fee")
                $text .= ', ' . sprintf( __( '%s sign-up fee', 'ncs-cart' ), sc_format_price( $sub->sign_up_fee ) );
            }
            
            if ( isset( $sub->sub_discount_duration ) ) {
                // (e.g. "Discount: 5% off for 3 months")
                $text .= '<br><strong>' . __( 'Discount:', 'ncs-cart' ) . ' </strong> ';
                $text .= sprintf( __( '%s off for %d months', 'ncs-cart' ), sc_format_price( $sub->sub_discount ), $sub->sub_discount_duration );
            }
            
            echo  apply_filters(
                'sc_format_subcription_order_detail',
                $text,
                $terms,
                $sub->free_trial_days,
                $sub->sign_up_fee,
                $sub->sub_discount,
                $sub->sub_discount_duration
            ) ;
        }
        
        echo  '</small>' ;
        ?>
            </div>
            <div class="order-total">
                <?php 
        
        if ( $order->main_offer_amt == 0 && !isset( $order->subscription_id ) ) {
            _e( "Free", "ncs-cart" );
        } else {
            sc_formatted_price( $order->main_offer_amt );
        }
        
        $total += $order->main_offer_amt;
        ?>
            </div>
            
            
            <?php 
        if ( isset( $order->custom_prices ) ) {
            foreach ( $order->custom_prices as $price ) {
                ?>
                <div class="item"><?php 
                echo  $price['qty'] . ' ' . $price['label'] ;
                ?></div>
                <div class="order-total">
                    <?php 
                sc_formatted_price( $price['price'] );
                ?>
                </div>
                <?php 
                $total += $price['price'];
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
                    <div class="item"><?php 
                echo  $order_bump['name'] ;
                ?></div>
                    <div class="order-total">
                        <?php 
                sc_formatted_price( $order_bump['amount'] );
                ?>
                    </div>
                    <?php 
                $total += $order_bump['amount'];
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
                //if(!isset($child_order['subscription_id'])){
                if ( isset( $child_order['tax_amount'] ) && !empty($child_order['tax_amount']) ) {
                    $productAmount -= floatval( $child_order['tax_amount'] );
                }
                //}
                ?>
                    <div class="item"><?php 
                echo  get_the_title( $child_order['product_id'] ) ;
                ?></div>
                    <div class="order-total">
                        <?php 
                sc_formatted_price( $productAmount );
                ?>
                    </div>
                    <?php 
                $total += $productAmount;
                
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
        
        if ( $order->tax_amount ) {
            ?>
                
                <div class="item" style="border:0;"><strong><?php 
            _e( "Subtotal", "ncs-cart" );
            ?></strong></div>
                <div class="order-total" style="border:0;"><strong><?php 
            echo  sc_formatted_price( $total ) ;
            ?></strong></div>
                 <br><br>
                <div class="item"><?php 
            _e( $order->tax_desc . ' (' . $order->tax_rate . '%)', 'ncs-cart' );
            ?></div>
                <div class="order-total">
                    <?php 
            sc_formatted_price( $order->tax_amount );
            ?>
                </div>
                <?php 
            if ( !isset( $order->tax_data->type ) || $order->tax_data->type != 'inclusive' ) {
                $total += $order->tax_amount;
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
    
    if ( isset( $_GET['sc-order'] ) ) {
        $id = intval( $_GET['sc-order'] );
    } else {
        
        if ( isset( $_POST['sc_order_id'] ) ) {
            $id = intval( $_POST['sc_order_id'] );
        } else {
            return false;
        }
    
    }
    
    $order_info = (array) sc_setup_order( $id );
    $str = '{' . $atts['field'] . '}';
    return sc_personalize( $str, $order_info );
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
    if ( $login = get_option( '_my_account' ) ) {
        $replacements['login'] = $login;
    }
    if ( isset( $order_info['password'] ) ) {
        $replacements['password'] = $order_info['password'];
    }
    if ( isset( $order_info['custom_fields'] ) ) {
        foreach ( $order_info['custom_fields'] as $k => $v ) {
            $val = ( isset( $v['value'] ) ? $v['value'] : ' ' );
            $replacements['custom_' . $k] = $v['value'];
        }
    }
    if ( isset( $order_info['username'] ) ) {
        $replacements['username'] = $order_info['username'];
    }
    
    if ( $order_info['ID'] ) {
        $replacements['product_name'] = sc_get_public_product_name( $order_info['product_id'] );
        $replacements['plan_name'] = ( ($order_info['item_name'] = get_post_meta( $order_info['ID'], '_sc_item_name', true )) ? $order_info['item_name'] : '' );
        $product_name = ( $order_info['item_name'] != '' ? sprintf( __( '%s - %s', 'ncs-cart' ), $replacements['product_name'], $order_info['item_name'] ) : $replacements['product_name'] );
        // with pay plan name
        $replacements['order_list'] = $product_name;
        $replacements['order_inline_list'] = $product_name;
        // without pay plan name
        $replacements['product_list'] = $replacements['product_name'];
        $replacements['product_inline_list'] = $replacements['product_name'];
        $replacements['order_id'] = $order_info['ID'];
        $replacements['product_amount'] = sc_format_price( $order_info['amount'] );
        $replacements['order_amount'] = sc_format_price( $order_info['amount'] );
        $replacements['customer_address'] = sc_order_address( $order_info['ID'] );
        
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
            $replacements['bump_amt'] = sc_format_price( $total_bump_amt );
            if ( $order_info['order_type'] != 'bump' ) {
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
            $replacements['bump_amt'] = sc_format_price( floatval( $total_bump_amt ) );
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

function sc_localize_dt( $date = '' )
{
    $timezone = ( get_option( 'timezone_string' ) ?: '' );
    return new DateTime( $date . ' ' . $timezone );
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
            'meta_query'     => array( array(
            'relation' => 'OR',
            array(
            'key'   => '_sc_user_account',
            'value' => $user_id,
        ),
            array(
            'key'   => '_sc_email',
            'value' => $user_info->user_email,
        ),
        ) ),
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
    function sc_get_user_orders( $user_id, $status = 'any', $order_id = 0 )
    {
        $postnum = ( current_user_can( 'administrator' ) ? 15 : -1 );
        $user_info = get_userdata( $user_id );
        $args = array(
            'posts_per_page' => $postnum,
            'post_type'      => 'sc_order',
            'post_status'    => 'paid',
            'meta_query'     => array( array(
            'relation' => 'OR',
            array(
            'key'   => '_sc_user_account',
            'value' => $user_id,
        ),
            array(
            'key'   => '_sc_email',
            'value' => $user_info->user_email,
        ),
        ), array(
            'key'     => '_sc_renewal_order',
            'compare' => 'NOT EXISTS',
        ) ),
        );
        $order_id = intval( $order_id );
        if ( $order_id > 0 ) {
            $args['post__in'] = (array) $order_id;
        }
        $posts = get_posts( $args );
        $orders = array();
        
        if ( !empty($posts) ) {
            foreach ( $posts as $post ) {
                $orders[] = sc_setup_order( $post->ID, true );
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
                $orders[] = array(
                    'order_id'           => $post->ID,
                    'order_status'       => get_post_meta( $post->ID, '_sc_status', true ),
                    'order_amount'       => get_post_meta( $post->ID, '_sc_amount', true ),
                    'customer_firstname' => get_post_meta( $post->ID, '_sc_firstname', true ),
                    'customer_lastname'  => get_post_meta( $post->ID, '_sc_lastname', true ),
                    'customer_email'     => get_post_meta( $post->ID, '_sc_email', true ),
                    'product_id'         => get_post_meta( $post->ID, '_sc_product_id', true ),
                );
            }
            return $orders;
        }
        
        return $posts;
    }

}
if ( !function_exists( 'sc_get_order' ) ) {
    function sc_get_order( $id )
    {
        $order = get_post( $id );
        if ( !empty($order) && (get_post_type( $id ) == 'sc_order' || get_post_type( $id ) == 'sc_subscription') ) {
            return [
                'id'                 => $id,
                'order_status'       => get_post_meta( $id, '_sc_status', true ),
                'order_amt'          => get_post_meta( $id, '_sc_amount', true ),
                'customer_firstname' => get_post_meta( $id, '_sc_firstname', true ),
                'customer_lastname'  => get_post_meta( $id, '_sc_lastname', true ),
                'customer_email'     => get_post_meta( $id, '_sc_email', true ),
                'product_id'         => get_post_meta( $id, '_sc_product_id', true ),
            ];
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
                'confirm_cancel_sub'   => __( "Are you sure you wish to cancel this subscription? This action cannot be undone.", "ncs-cart" ),
                'sub_cancel'           => __( "This subscription has been canceled.", "ncs-cart" ),
                'something_went_wrong' => __( "Something went wrong. Please try again.", "ncs-cart" ),
                'list_renewed'         => __( "All lists successfully renewed", "ncs-cart" ),
            );
        } else {
            if ( $js_script == "ncs-cart-public.js" ) {
                //public/js/ncs-cart-public.js
                $return_data = array(
                    'sub_cancel'         => __( "This subscription has been canceled.", "ncs-cart" ),
                    'confirm_cancel_sub' => __( "Are you sure you wish to cancel this subscription? This action cannot be undone.", "ncs-cart" ),
                    'invalid_email'      => __( 'Enter a valid email', "ncs-cart" ),
                    'invalid_phone'      => __( 'Enter a valid phone number', "ncs-cart" ),
                    'invalid_pass'       => __( 'Use 8 or more characters with a mix of letters, numbers, and symbols', "ncs-cart" ),
                    'field_required'     => __( 'This field is required', "ncs-cart" ),
                    'username_exists'    => __( "This username already exists", "ncs-cart" ),
                    'with_a'             => apply_filters( 'sc_plan_text_with_a', __( 'with a', 'ncs-cart' ) ),
                    'day_free_trial'     => apply_filters( 'sc_plan_text_day_free_trial', __( '-day free trial', 'ncs-cart' ) ),
                    'and'                => apply_filters( 'sc_plan_text_and_a', __( 'and a', 'ncs-cart' ) ),
                    'sign_up_fee'        => apply_filters( 'sc_plan_text_sign_up_fee', __( 'sign-up fee', 'ncs-cart' ) ),
                    'you_got'            => __( 'You got', 'ncs-cart' ),
                    'off'                => __( 'off!', 'ncs-cart' ),
                    'discount'           => __( 'Discount:', 'ncs-cart' ),
                    'discount_off'       => __( 'off', 'ncs-cart' ),
                    'forever'            => __( 'forever', 'ncs-cart' ),
                    'expires'            => __( 'expires', 'ncs-cart' ),
                    'day'                => __( 'day', 'ncs-cart' ),
                    'days'               => __( 'days', 'ncs-cart' ),
                    'week'               => __( 'week', 'ncs-cart' ),
                    'weeks'              => __( 'weeks', 'ncs-cart' ),
                    'month'              => __( 'month', 'ncs-cart' ),
                    'months'             => __( 'months', 'ncs-cart' ),
                    'year'               => __( 'year', 'ncs-cart' ),
                    'years'              => __( 'years', 'ncs-cart' ),
                );
            }
        }
        
        return $return_data;
    }

}
function sc_show_downsell( $scp, $oto )
{
    if ( isset( $_GET['sc-oto-2'] ) || !isset( $scp ) ) {
        return false;
    }
    
    if ( !isset( $scp->ds_display ) || $scp->ds_display == 'declined' && $oto == 0 || $scp->ds_display == 'accepted' && $oto > 0 ) {
        return true;
    } else {
        return false;
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

function sc_payment_methods()
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

add_action(
    'sc_cancel_subscription_event',
    'sc_run_scheduled_cancellation',
    10,
    1
);
function sc_run_scheduled_cancellation( $sub )
{
    $sub->status = 'canceled';
    $sub->sub_status = 'canceled';
    $sub->store();
}

function sc_do_cancel_subscription( $sub, $sub_id = false, $now = true )
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
            echo  $e->getMessage() ;
            //add custom message
        }
    }
    
    $canceled = apply_filters(
        'sc_cancel_subscription',
        $canceled,
        $sub,
        $sub_id,
        $now
    );
    extract( $cancel_sub_data );
    
    if ( $canceled ) {
        $current_user = wp_get_current_user();
        //update status
        $log_entry = sprintf( __( 'Subscription cancelled by %s', 'ncs-cart' ), esc_html( $current_user->user_login ) );
        sc_log_entry( $sub->id, $log_entry );
        echo  'OK' ;
    }

}

function sc_do_subscription_row( $subscription )
{
    $status = ( in_array( get_post_status( $subscription->ID ), [ 'pending-payment', 'initiated' ] ) ? 'pending' : get_post_status( $subscription->ID ) );
    
    if ( $status == 'completed' || $status == 'pending' || $status == 'canceled' ) {
        $next = "--";
    } else {
        if ( !is_numeric( $subscription->sub_next_bill_date ) ) {
            $subscription->sub_next_bill_date = strtotime( $subscription->sub_next_bill_date );
        }
        $next = date_i18n( get_option( 'date_format' ), $subscription->sub_next_bill_date );
    }
    
    ?>
    <tr>
        <td><?php 
    echo  $subscription->ID ;
    ?></td>
        <td><?php 
    echo  $subscription->product_name ;
    ?></td>
        <td><?php 
    echo  ucwords( $status ) ;
    ?>
            <?php 
    
    if ( $subscription->cancel_date && $status != 'canceled' && $next != '--' ) {
        ?>
                <br><small><?php 
        printf( esc_html__( 'Cancels %s', 'ncs-cart' ), date( "m/d/y", strtotime( $next ) ) );
        ?></small>
            <?php 
    }
    
    ?>    
        </td>
        <td><?php 
    echo  ( $subscription->cancel_date ? '--' : $next ) ;
    ?></td>
        <td><?php 
    echo  $subscription->sub_payment ;
    ?></td>
        
        <td>
            <?php 
    
    if ( $status == 'pending' || $status == 'past_due' ) {
        ?>
            <?php 
        /*<a href="javascript:void(0);" onclick="sc_payment_pay('<?php echo $subscription->ID; ?>','subscription');" >Pay</a>*/
        ?>
            <?php 
    } else {
        ?>
            <a href="?sc-plan=<?php 
        echo  $subscription->ID ;
        ?>">Manage</a>
            <?php 
    }
    
    ?>
        </td>
    </tr> 
    
    <?php 
    
    if ( isset( $subscription->order_bump_subs ) ) {
        ?>
        <?php 
        foreach ( $subscription->order_bump_subs as $k => $bump ) {
            $bump = (array) $bump;
            ?>

            <tr>
                <td><?php 
            echo  $subscription->ID ;
            ?></td>
                <td><?php 
            echo  $bump['name'] ;
            ?></td>
                <td><?php 
            echo  ( isset( $bump['status'] ) ? ucwords( $bump['status'] ) : ucwords( $status ) ) ;
            ?></td>
                <td><?php 
            echo  $next ;
            ?></td>
                <td><?php 
            echo  sc_format_price( $bump['plan']->price ) ;
            ?> / 
                    <?php 
            
            if ( $bump['plan']->frequency > 1 ) {
                echo  esc_html( $bump['plan']->frequency . ' ' . sc_pluralize_interval( $bump['plan']->interval ) ) ;
            } else {
                echo  esc_html( $bump['plan']->interval ) ;
            }
            
            ?>
                </td>
                
                <td>
                    <?php 
            
            if ( $status == 'pending' || $status == 'past_due' ) {
                ?>
                    <?php 
                /*<a href="javascript:void(0);" onclick="sc_payment_pay('<?php echo $subscription->ID; ?>','subscription');" >Pay</a>*/
                ?>
                    <?php 
            } else {
                ?>
                    <a href="?sc-plan=<?php 
                echo  $subscription->ID ;
                ?>">Manage</a>
                    <?php 
            }
            
            ?>
                </td>

            </tr>
        <?php 
        }
        ?>
    <?php 
    }

}
