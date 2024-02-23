<?php

/**
 * The subscription sync specific functionality of the plugin.
 *
 * @link       https://ncstudio.co
 * @since      2.6
 *
 * @package    NCS_Cart
 * @subpackage NCS_Cart/subscription-sync
 */

 class NCS_Cart_Sub_Sync {

    public $day_allowed_options;

    public function __construct() {

        $this->day_allowed_options = array('1','2','3','4','last','this','every_1_3','every_2_4');

        add_filter('sc_pay_plan_fields', [$this, 'pay_plan_fields']);
        add_filter('sc_plan_at_checkout', [$this, 'check_sub_sync'], 10, 2);
        add_filter('sc_format_subcription_order_detail',[$this, 'sync_order_detail'], 10, 7);
        add_action('sc_product_print_field_scripts', [$this, 'sync_start_scripts']);
    }

    function sync_start_scripts($scripts) { ?>

        <script type="text/javascript">
            jQuery('document').ready(function($){

                var field = '.ridsync_day_of_week select';
                var req_options = ["this", "every_1_3", "every_2_4"];

                var day_allowed = <?php echo json_encode($this->day_allowed_options); ?>;

                $('.ridsync_start_day select').each(function(){
                    $field = $(this).closest(".repeater-content").find(field);
                    var compare = $field.val();
                    if (req_options.includes($(this).val())) {
                        if(!$field.hasClass('required')) {
                            $field.addClass('required');
                            $field.parent().prepend('<span class="req" style="color: #a00;">*</span>');
                        }

                        if($field.val()=='') {
                            $field.addClass('error');
                        }

                    } else {
                        $field.removeClass('required error');
                        $field.parent().find('.req').remove();
                    }
                });

                $('.ridsync_start_day select').on('change', function(){
                    $field = $(this).closest(".repeater-content").find(field);
                    var compare = $field.val();
                    if (req_options.includes($(this).val())) {
                        if(!$field.hasClass('required')) {
                            $field.addClass('required');
                            $field.parent().prepend('<span class="req" style="color: #a00;">*</span>');
                        }

                        if($field.val()=='') {
                            $field.addClass('error');
                        }

                    } else {
                        $field.removeClass('required error');
                        $field.parent().find('.req').remove();

                        // clear DOW field start day val not in "day_allowed" list
                        if(!day_allowed.includes($(this).val())) {
                            $field.val('');
                        }
                    }
                });

                $('.ridsync_day_of_week select').on('change', function(){
                    if($(this).hasClass('required')) {
                        if($(this).val()=='') {
                            $(this).addClass('error');
                        } else {
                            $(this).removeClass('error');
                        }
                    } 
                });
            });
        </script>

        <?php
    }

    function pay_plan_fields($fields) {

        $hidden_field = array_pop($fields);

        $fields[] = array(
            'checkbox' =>array(
                'class'		        => '',
                'description'	    => '',
                'id'			    => 'sync_start',
                'label'		        => __('Synchronize start date' ,'ncs-cart'),
                'placeholder'	    => '',
                'type'		        => 'checkbox',
                'value'		        => '',
                'class_size'        => '',
                'conditional_logic' => array(
                    array(
                        'field' => 'product_type',
                        'value' => 'recurring',
                    )
                )
            ));
            
        $fields[] = array(
                'select' =>array(
                    'class'		    => '',
                    'description'	=> __('Shown by month','ncs-cart'),
                    'id'			=> 'sync_start_day',
                    'label'	    	=> __('Payments start on','ncs-cart'),
                    'placeholder'	=> '',
                    'type'		    => 'select',
                    'value'		    => '',
                    'selections'    => $this->days_dropdown(),
                    'class_size'=> 'one-third first',
                    'conditional_logic' => array(
                            array(
                                'field' => 'product_type',
                                'value' => 'recurring',
                            ),
                            array(
                                'field' => 'sync_start',
                                'value' => true,
                            )
                        )
            ));

            $fields[] = array(
                'select' =>array(
                    'class'		    => '',
                    'description'	=> '',
                    'id'			=> 'sync_day_of_week',
                    'label'	    	=> '',
                    'placeholder'	=> '',
                    'type'		    => 'select',
                    'value'		    => '',
                    'selections'    => $this->dow_dropdown(),
                    'class_size'=> 'one-third',
                    'conditional_logic' => array(
                            array(
                                'field' => 'product_type',
                                'value' => 'recurring',
                            ),
                            array(
                                'field' => 'sync_start',
                                'value' => true,
                            ),
                            array(
                                'field' => 'sync_start_day',
                                'value' => $this->day_allowed_options,
                                'compare' => 'IN'
                            )
                        )
            ));

            $fields[] = array(
                'select' =>array(
                    'class'		    => '',
                    'description'	=> '',
                    'id'			=> 'sync_month',
                    'label'	    	=> '',
                    'placeholder'	=> '',
                    'type'		    => 'select',
                    'value'		    => '',
                    'selections'    => $this->month_dropdown(),
                    'class_size'=> 'one-third',
                    'conditional_logic' => array(
                            array(
                                'field' => 'product_type',
                                'value' => 'recurring',
                            ),
                            array(
                                'field' => 'sync_start',
                                'value' => true,
                            ),
                        )
            ));

        $fields[] = $hidden_field;

        return $fields;
    }

    function days_dropdown() {
        $options = array();
        for ($i = 1; $i <= 27; $i++){
            $options[$i] = sprintf(esc_html__('The %s', 'ncs-cart'), $this->addOrdinalNumberSuffix($i));
        }
        $options['last'] = esc_html__('the last', 'ncs-cart');
        $options['this'] = esc_html__('every', 'ncs-cart');
        $options['every_1_3'] = esc_html__('the 1st or the 3rd', 'ncs-cart');
        $options['every_2_4'] = esc_html__('the 2nd or the 4th', 'ncs-cart');
        $options['every_1_15'] = esc_html__('the 1st or the 15th', 'ncs-cart');

        return $options; 
    }

    function dow_dropdown() {

        $options = [
            '' => esc_html__('day', 'ncs-cart'),
            'Sunday'  => esc_html__('Sunday', 'ncs-cart'),
            'Monday'  => esc_html__('Monday', 'ncs-cart'),
            'Tuesday' => esc_html__('Tuesday', 'ncs-cart'),
            'Wednesday' => esc_html__('Wednesday', 'ncs-cart'),
            'Thursday' => esc_html__('Thursday', 'ncs-cart'),
            'Friday'  => esc_html__('Friday', 'ncs-cart'),
            'Saturday' => esc_html__('Saturday', 'ncs-cart'),
          ];
        
        return $options; 
    }

    function month_dropdown() {

        $options = [
            '' => esc_html__('of every month', 'ncs-cart'),
            'January' => esc_html__('of January', 'ncs-cart'),
            'February' => esc_html__('of February', 'ncs-cart'),
            'March' => esc_html__('of March', 'ncs-cart'),
            'April' => esc_html__('of April', 'ncs-cart'),
            'May' => esc_html__('of May', 'ncs-cart'),
            'June' => esc_html__('of June', 'ncs-cart'),
            'July' => esc_html__('of July', 'ncs-cart'),
            'August' => esc_html__('of August', 'ncs-cart'),
            'September' => esc_html__('of September', 'ncs-cart'),
            'October' => esc_html__('of October', 'ncs-cart'),
            'November' => esc_html__('of November', 'ncs-cart'),
            'December' => esc_html__('of December', 'ncs-cart'),
          ];
        
        return $options; 
    }

    function check_sub_sync($plan, $product_id, $sale='') {
        if($plan->type != 'recurring') {
            return $plan;
        }

        $plans = get_post_meta($product_id, '_sc_pay_options', true);
        $option = '';

        foreach ( $plans as $val ) {
            $val['stripe_plan_id'] = $val['stripe_plan_id'] ?? '';
            $val['sale_stripe_plan_id'] = $val['sale_stripe_plan_id'] ?? '';
            if ( $plan->option_id == $val['option_id'] ||  $plan->option_id == $val['stripe_plan_id'] ||  $plan->option_id == $val['sale_stripe_plan_id'] ) {
                $option = $val;
                break;
            }
        } 
        
        if(!isset($option) || !$option) {
            return $plan;
        }

        if(!isset($option['sync_start']) || !$option['sync_start']) {
            return $plan;
        }

        $day_of_week = (isset($option['sync_day_of_week']) && in_array($option['sync_start'], $this->day_allowed_options)) ? $option['sync_day_of_week'] : false;
        $sync_month = $option['sync_month'] ?? false;
        
        $plan->initial_payment = (float) $option['price'];
        $plan->trial_days = false;
        $plan->next_bill_date = '';

        // calculate days until start
        $now = sc_localize_dt();
        $modifier = ($sync_month) ? $sync_month : 'this month';
        $modifier = ' of ' . $modifier . ' ' . $now->format('H:i');

        if($day_of_week) {

            $day_arr = ['','first','second','third','fourth'];
            
            if ($option['sync_start_day'] == 'every_1_3' || $option['sync_start_day'] == 'every_2_4') {
                if ($option['sync_start_day'] == 'every_1_3') {

                    $start_on = sc_localize_dt('third ' . $day_of_week . ' ' . $modifier);

                    if($start_on->format('Y-m-d') < $now->format('Y-m-d')) {

                        $modifier = ($sync_month) ? $sync_month . ' ' . (date('Y') + 1) : 'next month';
                        $start_on = sc_localize_dt('first ' . $day_of_week . ' of ' .$modifier. ' ' . $now->format('H:i'));
                    } else if($start_on->format('Y-m-d') > $now->format('Y-m-d')) {
                        // test earlier date
                        $new_start_on = sc_localize_dt('first ' . $day_of_week . $modifier);
                        if($new_start_on->format('Y-m-d') >= $now->format('Y-m-d')) {
                            $start_on = $new_start_on;
                        }
                    }

                } else if ($option['sync_start_day'] == 'every_2_4') {
                    $start_on = sc_localize_dt('fourth ' . $day_of_week . ' ' . $modifier);

                    if($start_on->format('Y-m-d') < $now->format('Y-m-d')) {
                        $modifier = ($sync_month) ? $sync_month . ' ' . (date('Y') + 1) : 'next month';
                        $start_on = sc_localize_dt('second ' . $day_of_week . ' of ' .$modifier. ' ' . $now->format('H:i'));
                    } else if($start_on->format('Y-m-d') > $now->format('Y-m-d')) {
                        // test earlier date
                        $new_start_on = sc_localize_dt('second ' . $day_of_week . $modifier);
                        if($new_start_on->format('Y-m-d') >= $now->format('Y-m-d')) {
                            $start_on = $new_start_on;
                        }
                    }
                } 
            } else {
                if (is_numeric($option['sync_start_day'])) {
                    $start = $day_arr[intval($option['sync_start_day'])];
                } else {
                    // this (each), last
                    $modifier = ' ' . $now->format('H:i');
                    $start = $option['sync_start_day'];
                }

                $date_string = $start . ' ' . $day_of_week . ' ' . $modifier;
                $start_on = sc_localize_dt($date_string );
            }
            
            if($start_on->format('Y-m-d') != $now->format('Y-m-d')) {

                $plan->sync_start = 1;

                // get next day if start is in past (should only be possible with numeric start days)
                if ($start_on < $now) {
                    if (is_numeric($option['sync_start_day'])) {
                        $modifier = ($sync_month) ? $sync_month . ' ' . (date('Y') + 1) : 'next month';
                        $modifier = ' of ' .$modifier. ' ' . $now->format('H:i');
                        $date_string = $start . ' ' . $day_of_week . ' ' . $modifier;
                        $start_on = sc_localize_dt($date_string );
                    }
                }

                $now->modify('midnight');
                $start_on->modify('midnight');

                $diff = $now->diff($start_on);
                $plan->trial_days = $diff->days;
                $plan->initial_payment = 0;
                //var_dump($now->format('Y-m-d H:i:a'), $start_on->format('Y-m-d H:i:a'), $plan->trial_days);
            }

        } else {

            // get last day of month if start day = "last"
            if($option['sync_start_day'] == 'last') {
                $option['sync_start_day'] = $now->format("t");
            }
            
            // get day of the month
            if ($option['sync_start_day'] != $now->format("d")) {

                $now_time = $now->format("H:i:s");
                $plan->initial_payment = 0;
                $plan->sync_start = 1;

                if ($option['sync_start_day'] == 'every_1_15') {
                    if (($sync_month && $now->format("F") == $sync_month && ($now->format("d") == 1 || $now->format("d") == 15)) ||
                        (!$sync_month && ($now->format("d") == 1 || $now->format("d") == 15))) {
                        $start_on = sc_localize_dt($now->format('Y-m-d').' '.$now_time);
                    } else {
                        if(!$sync_month) {
                            if ($now->format("d") > 15) {
                                $start_on = sc_localize_dt($now->format('Y-m-').'1'.' '.$now_time);
                                $start_on->modify('+1 month');
                            } else {
                                $start_on = sc_localize_dt($now->format('Y-m-').'15'.' '.$now_time);
                            }
                        } else {
                            $start_on = sc_localize_dt($sync_month . ' 15 ' . $now->format('Y') .' '.$now_time);
                            if($start_on->format('Y-m-d') > $now->format('Y-m-d')) {
                                // test earlier date
                                $new_start_on = sc_localize_dt($sync_month . ' 1 ' . $now->format('Y') .' '.$now_time);
                                if($new_start_on->format('Y-m-d') >= $now->format('Y-m-d')) {
                                    $start_on = $new_start_on;
                                }
                            } else if($start_on->format('Y-m-d') < $now->format('Y-m-d')) {
                                $start_on = sc_localize_dt($sync_month . ' 1 ' . (date('Y') + 1) .' '.$now_time);
                            }
                        }

                        $now->modify('midnight');
                        $start_on->modify('midnight');

                        $diff = $now->diff($start_on);
                        $plan->trial_days = $diff->days;
                    }
                } else {
                    if($sync_month) {
                        $start_on = sc_localize_dt($sync_month . ' ' . $option['sync_start_day'] . ' ' . $now->format('Y') .' '.$now_time);
                    } else {
                        $start_on = sc_localize_dt($now->format('Y-m-').$option['sync_start_day'].' '.$now_time);
                    }

                    // use next month/year if current start day is in the past
                    if($option['sync_start_day'] < $now->format("d")) {
                        if($sync_month) {
                            $start_on->modify('+1 year');
                        } else {
                            $start_on->modify('+1 month');
                        }
                    } 

                    $now->modify('midnight');
                    $start_on->modify('midnight');

                    $diff = $now->diff($start_on);
                    $plan->trial_days = $diff->days;
                }
            }
        }

        if ($plan->fee) {
            $plan->initial_payment += $plan->fee;
        }

        // reset all recurring payment info from function studiocart_plan

        if ($plan->trial_days) {
            $plan->next_bill_date = $now->modify('+'.$plan->trial_days." day");
        } else {
            $plan->next_bill_date = $now->modify('+'.$plan->frequency." " . $plan->interval);
        }

        $plan->next_bill_date = $plan->next_bill_date->format("Y-m-d");

        if ($plan->installments > 1) {
            
            $duration = $plan->installments * $plan->frequency;
            $cancel_at = $duration.' '.$plan->interval;

            if ($plan->trial_days) {
                $cancel_at .= " + " . $plan->trial_days . " day";
            }
            
            $plan->cancel_at = strtotime($cancel_at);
            $plan->db_cancel_at = date("Y-m-d", strtotime($cancel_at));
        } else {
            $plan->cancel_at = null;
            $plan->db_cancel_at = null;
        }
        
        if($plan->frequency > 1) {
            $text = sc_format_price($plan->price) . ' / ' . $plan->frequency . ' ' . sc_pluralize_interval($plan->interval);
            $text_plain = sc_format_price($plan->price, false) . ' / ' . $plan->frequency . ' ' . sc_pluralize_interval($plan->interval);
        } else {
            $text = sc_format_price($plan->price) . ' / ' . $plan->interval; 
            $text_plain = sc_format_price($plan->price, false) . ' / ' . $plan->interval; 
        }

        $text_terms = '';
        
        $installments = $plan->installments;
        if ($installments > 1) {
            $text_terms .=  ' x ' . $installments;
        }
                    
        if ($plan->trial_days) {
            // (e.g. " starting on")
            $text_terms .= ' ' . sprintf(__('starting on %s','ncs-cart'), sc_maybe_format_date($plan->next_bill_date));
        }

        $text .= $text_terms;
        $text_plain .= $text_terms;

        if ($plan->fee) {
            // (e.g. ", $5 sign-up fee")
            $text = sprintf(__('%s today, then','ncs-cart'), sc_format_price($plan->initial_payment)) . ' ' . $text;
            $text_plain = sprintf(__('%s today, then','ncs-cart'), sc_format_price($plan->initial_payment)) . ' ' . $text_plain;
        }
        
        $plan->text = $text;
        $plan->text_plain = $text_plain;
       
        return $plan;
    }

    function addOrdinalNumberSuffix($num) {
        if (!in_array(($num % 100),array(11,12,13))){
          switch ($num % 10) {
            // Handle 1st, 2nd, 3rd
            case 1:  return $num.'st';
            case 2:  return $num.'nd';
            case 3:  return $num.'rd';
          }
        }
        return $num.'th';
    }

    function sync_order_detail($text, $terms, $trial_days=false, $sign_up_fee=false, $discount=false, $discount_duration=false, $plan='') {
        if(is_object($plan) && isset($plan->sync_start)) {
            $text = $plan->text_plain;
        }
        return $text;
    }

}

$sc_sub_sync = new NCS_Cart_Sub_Sync();