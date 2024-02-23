<?php

add_action('studiocart_subscription_reminder_event', 'studiocart_notification_send', 10, 2);

/**
 * Schedule daily event for checking next bill dates
 * @param $type|string Either renewal or trial ending
 */

function sc_maybe_schedule_reminders($type) {
    if ( get_option('_sc_email_'.$type.'_enable') && !wp_next_scheduled( 'studiocart_daily_events', array($type) ) ) {
        wp_schedule_event( strtotime('+1 hour'), 'daily', 'studiocart_daily_events', array($type) );
    }
}

/**
 * Remove daily event for checking next bill dates
 * @param $type|string Either renewal or trial ending
 */

function sc_clear_reminders($type) {
    if ( wp_next_scheduled( 'studiocart_daily_events', array($type) ) ) {
        wp_clear_scheduled_hook( 'studiocart_daily_events', array($type) );
    }
}

/**
 * Determine if we should schedule a daily event hook for reminders based on option value
 */

add_action( 'update_option__sc_email_reminder_enable', 'sc_daily_event_reminder_activation', 10, 3 );
function sc_daily_event_reminder_activation( $old_value, $value, $option ) {
    if($value) {
        sc_maybe_schedule_reminders('reminder');
    } else {
        sc_clear_reminders('reminder');
    }
}

/**
 * Determine if we should schedule a daily event hook for trial ending reminders based on option value
 */

add_action( 'update_option__sc_email_trial_ending_enable', 'sc_daily_event_trial_reminder_activation', 10, 3 );
function sc_daily_event_trial_reminder_activation( $old_value, $value, $option ) {
    if($value) {
        sc_maybe_schedule_reminders('trial_ending');
    } else {
        sc_clear_reminders('trial_ending');
    }
}

/**
 *  * Find subscriptions with upcoming renewals or trials ending and schedule a reminder
 * @param $type|string Either renewal or trial ending email
 * 
 * Eg: reminder days:  4
 * renewal:            4th
 * email scheduled:    30th
 * email sends:        31st
 * 
 */

add_action( 'studiocart_daily_events', 'sc_find_upcoming_renewals');
function sc_find_upcoming_renewals($type) {

    if (!get_option('_sc_email_'.$type.'_enable')) {
        return;
    }

    $days = get_option('_sc_email_'.$type.'_days', 1);

    $date = new DateTime();
    $date->modify('+'.($days + 1).' day'); // lookup and schedule 1 day early in case of renewal times that might miss the cutoff

    $timestamp  = $date->format('U');
    $beginOfDay = strtotime("today", $timestamp);
    $endOfDay   = strtotime("tomorrow", $beginOfDay) - 1;

    $status = $type == 'reminder' ? 'active' : 'trialing';
    
    $args = array(
        'post_type' => 'sc_subscription',
        'post_status' => $status,
        'posts_per_page' => -1,
        'meta_query' => array(
            'relation' => 'AND', // "OR" or "AND" (default)
            array(
                'key' => '_sc_sub_next_bill_date',
                'value' => $beginOfDay,
                'type' => 'numeric',
                'compare' => '>='
            ),
            array(
                'key' => '_sc_sub_next_bill_date',
                'value' => $endOfDay,
                'type' => 'numeric',
                'compare' => '<='
            )
        )
    );
     
    $posts = get_posts($args);
    if (!empty($posts)) {
        foreach($posts as $post) {
            $sub = new ScrtSubscription($post->ID);
            schedule_reminder($sub, $days);
        }
    }

    $args['meta_query'] = array(
            array(
                'key' => '_sc_sub_next_bill_date',
                'value' => $date->format('Y-m-d'),
            ),
        );
     
    $posts = get_posts($args);
    if (!empty($posts)) {
        foreach($posts as $post) {
            $sub = new ScrtSubscription($post->ID);
            schedule_reminder($sub, $days);
        }
    }
}

/**
 * Schedule a subscription reminder
 * @param $sub|Object ScrtSubscription object
 * @param $days|String Number of days before next bill date to send reminder
 */

function schedule_reminder($sub, $days) {
      
    $time = $sub->sub_next_bill_date;
    $type = $sub->status == 'trialing' ? 'trial_ending' : 'reminder';
      
    if (!is_numeric($time)) {
        $date = new DateTime($time);
        $date = $date->format('U');
    } else {
        $date = new DateTime();
        $date->setTimestamp($time);
    }
        
    $date->modify("-{$days} day");
    
    if ( ! wp_next_scheduled( 'studiocart_subscription_reminder_event', array($type, $sub->get_data()) ) ) {
        wp_schedule_single_event($date->format('U'), 'studiocart_subscription_reminder_event', array($type, $sub->get_data()));
    }
  
  }

/**
 * Set subscription status to 'canceled'
 * @param $sub|Object ScrtSubscription object
 */

add_action( 'sc_cancel_subscription_event', 'sc_run_scheduled_cancellation', 10, 1);
function sc_run_scheduled_cancellation($sub){
    $sub->status = 'canceled';
    $sub->sub_status = 'canceled';
    $sub->store();
}