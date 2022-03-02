(function ($) {
    'use strict';

    /**
     * All of the code for your public-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */
    var regExPhone = /^\(?([0-9]{3})\)?[-. ]?([0-9]{3})[-. ]?([0-9]{4})$/,
        regExEmail = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/,
        states = sc_country_select_states,
        select_state,
        $select_state,
        select_country,
        $select_country;

    
    function key_value_pair_selectize(json_data) {
        let format_data = [];
        $.each(json_data, function (i, item) {
            let json_state = { 'state_key': i, 'state_value': item };
            format_data.push(json_state);
        });
        return format_data;
    }
    function isValidPhonenumber(value) {
        return (/^\d{7,}$/).test(value.replace(/[\s()+\-\.]|ext/gi, ''));
    }
    function isValidPassword(value) {
        var lowerCaseLetters = /[a-z]/g;
        if (!value.match(lowerCaseLetters)) {
            return false;
        }

        // Validate numbers
        var numbers = /[0-9]/g;
        if (!value.match(numbers)) {
            return false;
        }

        // Validate length
        if (!value.length >= 8) {
            return false;
        }
        return true;
    }
    $(document).ready(function () {

        $(document).on('click', ".studiocart .coupon-code input[type='button']", function (e) {
            e.preventDefault();
            $(this).attr('disabled', 'disabled');
            clear_coupon();
            if($('#discount-code').val()==''){
                $(".studiocart .coupon-code input[type='button']").removeAttr('disabled');
            } else {
                try_coupon($('#discount-code').val());
            }
        });

        $(document).on('click', ".sc-password-toggle", function () {
            var field = $(this).parent().prev('.sc-password');
            if ($(this).is(':checked')) {
                field.attr('type', 'text');
            } else {
                field.attr('type', 'password');
            }
        });

        // toggle pay methods
        togglePayMethods();

        $(document).on('change', 'input[name="pay-method"]', function () {
            togglePayMethods();
        });

        $(document).on('click', '#sc-coupon-toggle', function () {
            $('#sc-coupon-form').fadeToggle();
            return false;
        });

        //function address_fields() {

        let current_country = $('#country').val();

        $select_country = $('#country').selectize({
            onChange: function (value) {
                if (!value.length) return;
                if($('#state').length>0){
                    $('#state').selectize()[0].selectize.clearOptions();
                    $('#state').selectize()[0].selectize.destroy();
                    let new_state_option = key_value_pair_selectize(states[value]);
                    if (new_state_option.length === 0) {
                        $('#state').replaceWith('<input id="state" name="state" type="text" class="form-control  required" placeholder="State" value="" aria-label="State">');
                    } else {
                        $('#state').replaceWith('<select id="state" name="state" class="form-control  required" style="display: block;"><option value="" selected>Select State</option></select>');
                        $select_state = $('#state').selectize({
                            valueField: 'state_key',
                            labelField: 'state_value',
                            searchField: 'state_value',
                            options: new_state_option
                        });
                    }
                }
                

            }
        });
        if($('#state').length>0){
            let state_option = key_value_pair_selectize(states[current_country]);

            if (state_option.length === 0) {
                $('#state').replaceWith('<input id="state" name="state" type="text" class="form-control  required" placeholder="State" value="" aria-label="State">');
            } else {
                $select_state = $('#state').selectize({
                    valueField: 'state_key',
                    labelField: 'state_value',
                    searchField: 'state_value',
                    options: state_option
                });
            }
        }
        //address_fields();

        function togglePayMethods() {
            var method = $('[name="pay-method"]:checked').val();
            if (method != 'stripe') {
                $('.sc-stripe').fadeOut();
                //$('#paypal-button-container').fadeIn();
            } else {
                $('.sc-stripe').fadeIn();
                $('#paypal-button-container').fadeOut();
            }
        }

        function numberWithThousandsSep(x) {
            var parts = x.toString().split(".");
            parts[0] = parts[0].replace(/\B(?=(\d{3)+(?!\d))/g, sc_currency.thousep);
            return parts.join(sc_currency.decisep);
        }

        function format_price(amt) {
            var price;

            // left positioned currency
            if (sc_currency.position != 'right' && sc_currency.position != 'right-space') {
                price = sc_currency.symbol;
                if (sc_currency.position == 'left-space') {
                    // with space
                    price += ' ';
                }

                // format price
                price += numberWithThousandsSep(parseFloat(amt).toFixed(sc_currency.decinum));
            }

            // right positioned currency
            if (sc_currency.position == 'right' || sc_currency.position == 'right-space') {

                // format price
                price = numberWithThousandsSep(parseFloat(amt).toFixed(sc_currency.decinum));

                if (sc_currency.position == 'right-space') {

                    // with space
                    price += ' ';

                }

                // left positioned currency
                price += sc_currency.symbol;

            }

            // return price
            return price;
        }

        function update() {
            var $item = $('input[name=sc_product_option]:checked'),
                installments = $item.data('installments'),
                trial = false,
                fee = false,
                cart_total = Number(parseFloat($item.data('price')).toFixed(2)),

                price = format_price(cart_total);

            // set main offer price to 0 if bump is an upgrade
            if ( $('#sc-orderbump.ob-replace:checked').length || ($('.ob-sub:checked').length &&   ($('input[name="sc_product_option"]:checked').data('installments')))) {
                $('#subtotal').parent().hide();
                cart_total = Number(parseFloat(0).toFixed(2));
                price = format_price(cart_total);
                installments = false;
            } else {
                $('#subtotal').parent().show();
                if (typeof $item.data('trial-days') !== 'undefined') {
                    trial = $item.data('trial-days');
                    cart_total = 0;
                    price = format_price('0');
                }

                if (typeof $item.data('signup-fee') !== 'undefined') {
                    fee = $item.data('signup-fee');
                    cart_total += Number(parseFloat(fee).toFixed(2));
                    price = format_price(cart_total);
                }
            }

            update_subscription_text($item,installments,0,"");

            $('input[name="sc_amount"]').val(cart_total);
            $('.total .price').html(price);

            $('.orderbump-item-row').fadeOut();
            $('.summary-items #subtotal').html(price);

            $('input[data-scq-price]').each(function () {
                var qty = Number($(this).val());
                $('#row-' + $(this).attr('id')).remove();
                if (qty > 0) {
                    var qp = Number(parseFloat($(this).data('scq-price')).toFixed(2));
                    var qtotal = qty * qp;
                    var new_price = (qtotal + parseFloat(cart_total)).toFixed(2);
                    var label = $(this).prev('label');

                    cart_total = new_price;

                    var new_total = format_price(new_price);
                    $('.total .price').html(new_total);
                    $('input[name="sc_amount"]').val(new_price);

                    $('.sc-order-summary .summary-items .orderbump-item-row').before('<div id="row-' + $(this).attr('id') + '" class="item addon-item-row"><span class="label">' + label.text() + ' x ' + qty + '</span> <span class="ob-price">' + format_price(qtotal) + '</div>');
                }
            });
            
            if ($('#sc-orderbump').is(':checked')) {
                $('#orderbump-item-row').fadeIn();
                var orderbump = Number(parseFloat($('#orderbump-item-row .ob-price').data('price'))),
                    new_price = (orderbump + parseFloat(cart_total)).toFixed(2),
                    new_total = format_price(new_price);
                $('.total .price').html(new_total);
                $('input[name="sc_amount"]').val(new_price);
                cart_total = new_price;
            }

            $('.sc_orderbump').each(function(){
                if ($(this).is(':checked')) {
                    var value=$(this).val();
                    $('.orderbump-item-row-'+value).fadeIn();
                    var orderbump = Number(parseFloat($('.orderbump-item-row-'+value+' .ob-price').data('price'))),
                        new_price = (orderbump + parseFloat(cart_total)).toFixed(2);
                    $('.total .price').html(format_price(new_price));
                    $('input[name="sc_amount"]').val(new_price);
                    cart_total = new_price;
                }
            });
            
            if ($('#sc-coupon-id').data('type')) {
                
                var discount_type = $('#sc-coupon-id').data('type'),
                    discount_amount = $('#sc-coupon-id').data('amount'),
                    plans = $('#sc-coupon-id').data('plans');
                
                if (typeof plans == 'undefined' || $('#sc-coupon-id').data('plans') && plans.includes($item.val()) ){
                    if (discount_type == 'cart-percent') {
                        discount_amount = cart_total * (discount_amount / 100);
                    }

                    new_price = (parseFloat(cart_total) - discount_amount).toFixed(2);

                    if(new_price < 0) {
                        new_price = (parseFloat(0)).toFixed(2);
                    }

                    $('.total .price').html(format_price(new_price));
                    $('input[name="sc_amount"]').val(new_price);
                    cart_total = new_price;

                    discount_amount = (parseFloat(discount_amount)).toFixed(2);

                    $('.cart-discount .sc-label').html('Discount: ' + $('#sc-coupon-id').val().toUpperCase());
                    $('.cart-discount .price').html('- ' + format_price(discount_amount));

                    $('.cart-discount').fadeIn();
                }
            }

            if($item.data('taxable') === 'yes' && $('select[name="country"]').val()!=''){
                $('.tax').hide();
                if(typeof sc_tax_settings !== 'undefined') {
                    $('#sc_card_button').addClass('running').attr("disabled", true);
                    var tax_obj = {},
                    tax_rate_data = {};
                    tax_obj.country = $('select[name="country"]').val()??"";
                    tax_obj.city = $('input[name="city"]').val()??"";
                    tax_obj.state = $('select[name="state"]').val()??"";
                    tax_obj.zip = $('input[name="zip"]').val()??"";
                    tax_obj.vat_number = $('input[name="vat-number"]').val()??"";
                    tax_obj.nonce = sc_tax_settings.nonce;
                    tax_obj.action = 'get_match_tax_rate';
                    $.ajax({
                        type: "post",
                        dataType: "json",
                        url: studiocart.ajax,
                        data: tax_obj,
                        success: function (response) {
                            tax_rate_data = response.rates;
                            $('.vat_container').hide();
                            if(!response.is_Valid_vat){
                                $('#vat_number').addClass('invalid');
                                if($('#vat_number').parent().find('.error').length==0)
                                    $('#vat_number').parent().append('<div class="error">Invalid Vat Number</div>')
                            } else {
                                $('#vat_number').addClass('valid');
                            }
                            if(response.is_vat){
                                $('.vat_container').show();
                            }
                            if(!$.isEmptyObject(tax_rate_data)){
                                var tax_data = {
                                    tax_rate : Number(parseFloat(tax_rate_data.rate).toFixed(2)),
                                    tax_type : $item.data('tax-type'),
                                    tax_format : $item.data('tax-price-format'),
                                    cart_total : cart_total,
                                    tax_title : tax_rate_data.title==''?tax_rate_data.rate+'% Tax':tax_rate_data.title,
                                };
                                apply_tax(tax_data);
                                update_subscription_text($item,installments,tax_data.tax_rate,tax_data.tax_type);
                            }
                            $('#sc_card_button').removeClass('running').removeAttr("disabled");
                        }
                    });
                    
                }
            } else {
                $('.tax').hide();
            }

            

            if (cart_total == 0 && !installments) {
                $('.pay-info').hide();
            } else {
                $('.pay-info').show();
            }
        }

        function update_subscription_text($item,installments,tax_rate,tax_type){
            if (installments > 1 || installments == -1) {
                var item_price = Number(parseFloat($item.data('price')).toFixed(2)),
                interval = $item.data('interval'),
                trial = false,
                fee = false,
                recurringText = '';
                if(tax_rate>0 && tax_type!='inclusive_tax'){
                    item_price = item_price + (item_price*tax_rate/100);
                }
                interval = interval.length > 0 ? interval.toLowerCase() : '';
                interval = sc_pluralize_interval_js( interval ); //translate
                if (typeof $item.data('trial-days') !== 'undefined') {
                    trial = $item.data('trial-days');
                    // (e.g. " with a 5-day free trial")
                    recurringText += ' ' + sc_translate_frontend.with_a + ' ' + trial + sc_translate_frontend.day_free_trial;
                }
                if (typeof $item.data('signup-fee') !== 'undefined') {
                    fee = $item.data('signup-fee');
                    if(tax_rate>0 && tax_type!='inclusive_tax'){
                            fee = fee + (fee*tax_rate/100);
                    }
                    // (e.g. " and a $5 sign-up fee")
                    recurringText += ' ' + sc_translate_frontend.and + ' ' + format_price(fee) + ' ' + sc_translate_frontend.sign_up_fee;
                }
                var str = format_price(item_price);
                if (typeof $item.data('duration') !== 'undefined' && $item.data('duration') != null) {
                    var og_price = $item.data('og-price');
                    if(tax_rate>0 && tax_type!='inclusive_tax'){
                            og_price = og_price + (og_price*tax_rate/100);
                    }
                    str = format_price(og_price);
                }
                str += ' / ';
                if (typeof $item.data('frequency') === 'undefined' || $item.data('frequency') == 1) {
                    // (e.g. "$5 / week")
                    str += interval;
                } else {
                    // (e.g. "$5 / 2 weeks")
                    str += $item.data('frequency') + ' ' + interval;
                }
                // (e.g. " x 5")
                if (installments > 1) {
                    str += ' x ' + installments;
                }
                if (recurringText != '') {
                    // (e.g. " with a 5-day free trial and a $5 sign-up fee")
                    str += recurringText;
                }
                if (typeof $item.data('duration') !== 'undefined' && $item.data('duration') != null) {
                    // (e.g. " $10 off for 3 months")
                    str += '<br><strong>Discount:</strong> ' + format_price($item.data('discount')) + ' off';
                    if ($item.data('duration') != null) {
                        var d = new Date();
                        d.setMonth(d.getMonth() + parseInt($item.data('duration')));
                        str += ' (expires ' + d.toLocaleDateString() + ')';
                    } else {
                        str += ' forever';
                    }
                }
                $('.total small').html(str).show();
            } else {
                $('.total small').hide();
            }
        }
        var price_tax_text = '',
        tax = 0;
        function apply_tax(tax_data){
            var tax_text = '',
            price = format_price(tax_data.cart_total);
           
            if(tax_data.tax_type=='inclusive_tax'){
                tax_text = '(Included In Price)';
                tax = tax_data.tax_rate*tax_data.cart_total/(100+tax_data.tax_rate);
                price = format_price(tax_data.cart_total);
            } else {
                tax = tax_data.tax_rate*tax_data.cart_total/100;
                tax_data.cart_total = Number((parseFloat(tax_data.cart_total) + parseFloat(tax)).toFixed(2));
                price = format_price(tax_data.cart_total);
            }
            tax = format_price(tax);
            if(tax_data.tax_format=='exclude_tax'){
                $('.tax').show();
                $('.tax .price').html(tax+" "+tax_text);
                $('.tax .sc-label').html((tax_data.tax_title??'Tax')+' ('+tax_data.tax_rate+'%)');
                $('.total .price').html(price);
            } else {
                price_tax_text = "<span class='tax_extra'>(includes "+tax+" "+tax_data.tax_title+")</span>"
                $('.total .price').html(price+price_tax_text);
            }
        }

        function sc_pluralize_interval_js(interval){
            var str = interval;
            switch (interval) {
                case "week":
                   str = sc_translate_frontend.week;
                  break;
                case "weeks":
                    str = sc_translate_frontend.weeks;
                  break;
                  case "day":
                   str = sc_translate_frontend.day;
                  break;
                case "days":
                    str = sc_translate_frontend.days;
                  break;
                  case "month":
                   str = sc_translate_frontend.month;
                  break;
                case "months":
                    str = sc_translate_frontend.months;
                  break;
                  case "year":
                   str = sc_translate_frontend.year;
                  break;
                case "years":
                    str = sc_translate_frontend.years;
                  break;                      
            }
            return str;
        }

        update();
        update_pwyw();
        $(document).on('change', 'input[name=sc_product_option]', function () {
            update_pwyw();
        });

        function update_pwyw(){
            var dataType = $('input[name=sc_product_option]:checked').attr('data-val');
            var dataPrice = $('input[name=sc_product_option]:checked').attr('data-price');
            var id=$('input[name=sc_product_option]:checked').attr('id');
            var value=$('input[name=sc_product_option]:checked').val();
            $('.pwyw-input').fadeOut();
            if(dataType && dataType=='pwyw'){
                $('#'+id).data('price',dataPrice);
                $('#pwyw-amount-input-'+value).val(dataPrice);
                $('#'+id).parent().find('.price').html('<span class="sc-Price-currencySymbol">$</span>'+parseFloat(dataPrice).toFixed(2));
                $('#pwyw-input-block-'+value).fadeIn();
            }else{
                $('#pwyw-input-block-'+value).fadeOut();
            }
        }

        $(document).on('change blur focusout', 'input[name="pwyw_amount"]', function(){
            var value=$(this).val();
            var minvalue=$(this).attr('min');
            var id=$('input[name=sc_product_option]:checked').attr('id');
            if(parseFloat(value) < parseFloat(minvalue)){
                $(this).addClass('invalid');
                if($(this).parent().find('.error').length==0)
                    $(this).parent().append('<div class="error">Please enter an amount greater thank or equal to <span class="sc-Price-currencySymbol">$</span>'+parseFloat(minvalue).toFixed(2)+'</div>')
            }
            if(value){
                $(this).parent().remove('.error');
                $("#"+id).attr('data-price',value);
                $("#"+id).data('price',value);
                $("#"+id).parent().find('.price').html('<span class="sc-Price-currencySymbol">$</span>'+parseFloat($("#"+id).attr('data-price')).toFixed(2));
                update();
            }
        });

        $(document).on('change', 'input[name=sc_product_option], #sc-orderbump, .sc_orderbump, input[data-scq-price],select[name="country"], select[name="state"], input[name="city"], input[name="zip"], input[name="vat-number"]', function () {
            update();
        });

        $("#discount-code").keydown(function () {
            $("#coupon-status").text('').hide();
        });

        $(".coupon-code.applied").on("click", "button.coupon", function (e) {
            e.preventDefault();
            clear_coupon();
            $(".coupon-code").show();
        });

        function clear_coupon() {
            $('.coupon-code.applied, .cart-discount').hide();
            $('#sc-coupon-id').val('').removeData('type', 'amount', 'plans');
            $("#coupon-status").delay(3000).fadeOut();
            $('#sc-coupon-id').data('type')
            
            $('#sc-payment-form .products .item').each(function () {
                var $item = $(this).find('input[type="radio"]'),
                    price = $item.data('og-price');

                if (typeof $item.data('og-price') !== 'undefined') {
                    $(this).find('.price').html(format_price(price));
                    $item.data('price', price);
                }
                $(this).find('.coup-confirm').remove();
            });
            update();
        }

        function try_coupon(coup_code) {

            var nonce = $('#sc-coupon-nonce').val(),
                prod_id = $('#sc-prod-id').val(),
                coupon_applied_text,
                obj = {
                    action: "sc_validate_coupon",
                    prod_id: prod_id,
                    coup_code: coup_code,
                    nonce: nonce,
                    ip: sc_user[0]
                }

            if (typeof sc_user[1] !== 'undefined') {
                obj.email = sc_user[1];
            }

            $.ajax({
                type: "post",
                dataType: "json",
                url: studiocart.ajax,
                data: obj,
                success: function (response) {
                    if (!response.error) {
                        let coupon_applied = false;
                        $('#sc-payment-form .products .item').each(function (index) {
                            var new_price,
                                $item = $(this).find('input[type="radio"]'),
                                price = ($item.data('og-price')) ? $item.data('og-price') : $item.data('price'),
                                installments = $item.data('installments'),
                                discount = response.amount;
                            
                            if(response.plan && !response.plan.includes($item.val()) ){
                                return;
                            }

                            coupon_applied_text = format_price(response.amount);

                            if (price < 1) {
                                return;
                            }
                            
                            coupon_applied = true;
                            
                            if (response.type == 'percent' || response.type == 'cart-percent') {
                                coupon_applied_text = response.amount + '%';
                            }
                            
                            if (response.type == 'percent') {
                                discount = price * (discount / 100);
                            } else if (installments && response.amount_recurring) {
                                discount = response.amount_recurring;
                            }
                            
                            new_price = (price - discount).toFixed(2);
                            if (new_price < 0) {
                                new_price = (0).toFixed(2);
                            }

                            if (response.type != 'cart-percent' && response.type != 'cart-fixed') {
                                if (typeof response.duration !== 'undefined' && installments) {
                                    $item.data('duration', response.duration);
                                    $item.data('discount', discount);
                                } else {
                                    $item.removeData('duration');
                                }

                                $(this).find('.price').html('<s>' + format_price(price) + '</s> ' + format_price(new_price));
                                $item.data('price', new_price).data('og-price', price);
                            }

                            if(response.plan){
                                var plan_discount;
                                if (response.type != 'cart-percent' && response.type != 'percent') {
                                    plan_discount = format_price(discount);
                                } else {
                                    plan_discount = response.amount + '%';
                                }
                                coupon_applied_text = response.code + ' (-' + plan_discount + ')';
                                $(this).find('coup-confirm').remove();
                                $(this).find('label').append('<span class="coup-confirm">'+coupon_applied_text+'</span>');
                            } else {
                                if (typeof response.success_text != 'undefined' && response.success_text != null) {
                                    coupon_applied_text = response.success_text;
                                } else {
                                    coupon_applied_text = sc_translate_frontend.you_got + ' ' + coupon_applied_text + ' ' + sc_translate_frontend.off;
                                } 
                                $(".coupon-code.applied .coup-confirm").html(coupon_applied_text).parent().fadeIn();
                            }

                        });
                        if(coupon_applied){
                            $('#sc-coupon-id').val(response.code);
                            if (response.type == 'cart-percent' || response.type == 'cart-fixed') {
                                $('#sc-coupon-id').data('type', response.type).data('amount', response.amount);
                            }
                            if(response.plan){
                                $('#sc-coupon-id').data('plans', response.plan);
                            }
                        }
                        update();
                    }
                    else {
                        clear_coupon();
                        $("#coupon-status").text(response.error).fadeIn();
                        $('#sc-coupon-id').val('');

                        if ($(".studiocart .coupon-code input[type='button']").length > 0) {
                            $(".studiocart .coupon-code input[type='button']").show();
                        } else {
                            $("#coupon-status").delay(3000).fadeOut('normal', function () {
                                $(this).remove();
                            });
                        }
                    }
                    $(".studiocart .coupon-code input[type='button']").removeAttr('disabled');
                }
            })
        }

        if (typeof sc_coupon !== 'undefined') {
            try_coupon(sc_coupon[0]);
        }

        /* Change Vat Customer Type */
        $(document).on('change','input[name="vat-number-available"]',function(){
            if(this.checked){
                $('.vat_number_field').fadeIn(0);
            }else{
                $('.vat_number_field').fadeOut(0);
            }
        });

        // remove error message on focus
        $(document).on('focus', '#sc-payment-form input, #sc-payment-form select', function () {
            $(this).closest('.checkbox-wrap').removeClass('invalid').siblings('.error').remove();
            $(this).removeClass('invalid valid').siblings('.error').remove();

            if ($(this).attr('id') == 'address1') {
                $('.sc-address-2 .error').remove();
            }
        });
        
        function invalidate_field($field, message) {
            $field.removeClass('invalid').siblings('.error').remove();

            if (($field.attr('type') == 'checkbox' && !$field.is(':checked'))) {
                $field.closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + message + '</div>');
            } else {
                var $el = $field;
                if ($field.attr('id') == 'address1' && $('.single-sc_product').length > 0) {
                    $el = $('#sc-payment-form #address2');
                }
                $field.addClass('invalid');
                $el.closest('.form-group').append('<div class="error">' + message + '</div>');
            }
        }

        // check required on blur
        $(document).on('blur', '#sc-payment-form input.required', function () {
            $(this).removeClass('invalid').siblings('.error').remove();

            if (($(this).attr('type') == 'checkbox' && !$(this).is(':checked'))) {
                $(this).closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
            } else if (($(this).val().length < 1)) {
                var $el = $(this);
                if ($(this).attr('id') == 'address1' && $('.single-sc_product').length > 0) {
                    $el = $('#sc-payment-form #address2');
                }
                $(this).addClass('invalid');
                $el.closest('.form-group').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
            }
        });

        // check username on blur
        $(document).on('blur', '#sc-wpuserid', function () {
            $(this).removeClass('invalid valid').siblings('.error').remove();

            if (($(this).val().length > 0)) {
                checkUsername($(this));
            }
        });

        function checkUsername($el) {
            $el.removeClass('valid');

            var paramObj = {};
            paramObj['name'] = $el.val();
            paramObj['sc-nonce'] = $('input[name=sc-nonce]').val();
            paramObj['action'] = 'sc_check_username';

            $.post(studiocart.ajax, paramObj, function (response) {

                if (response == 'found') {
                    $el.addClass('invalid');
                    $el.closest('.form-group').append('<div class="error">' + sc_translate_frontend.username_exists + '</div>');
                    $('#sc_card_button, .sc-next-btn').attr("disabled", true);
                    return false;
                } else if (response == '') {
                    $el.addClass('valid');
                    $('#sc_card_button, .sc-next-btn').removeAttr("disabled");
                } else {
                    var res = JSON.parse(response);
                    if ('undefined' !== typeof res.error) {
                        $el.addClass('invalid');
                        $el.closest('.form-group').append('<div class="error">' + res.error + '</div>');
                        $('#sc_card_button, .sc-next-btn').attr("disabled", true);
                        return false;
                    }
                }
            });
        }

        // check email address
        $(document).on('blur', '#sc-payment-form input[type="email"]', function () {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validEmail = regExEmail.test(val);
            if (!validEmail) {
                $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_email + '</div>');
            }
        });

        // check phone number
        $(document).on('blur', '#sc-payment-form input[type="tel"]', function () {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validPhone = isValidPhonenumber(val);
            if (!validPhone) {
                $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_phone + '</div>');
            }
        });

        // check password
        $(document).on('blur', '#sc-payment-form input.sc-password', function () {
            var val = $(this).val();
            if (val.length) {
                var validPass = isValidPassword(val);
                if (!validPass) {
                    $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_pass + '</div>');
                }
            }
        });

        // track any change to total amount due
        $(document).on('change', '#sc-payment-form input', function () {
            amount = false;
        });

        

        function sc_validate() {
            var errors = false;

            $(".error").remove();
            $('#sc-payment-form input').removeClass('invalid');

            var fields = $('#sc-payment-form input.required:visible, #sc-payment-form select.required:visible');
            fields.each(function () {
                if ($(this).attr('type') == 'checkbox' && !$(this).is(':checked')) {
                    $(this).closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
                    errors = true;
                }else if($(this).attr('name') == 'pwyw_amount'){
                    var value=$(this).val();
                    var minvalue=$(this).attr('min');
                    if(parseFloat(value) < parseFloat(minvalue)){
                        $(this).addClass('invalid');
                        if($(this).closest('.form-group').find('.error').length==0)
                            $(this).closest('.form-group').append('<div class="error">Please enter an amount greater thank or equal to <span class="sc-Price-currencySymbol">$</span>'+parseFloat(minvalue).toFixed(2)+'</div>')
                        errors = true;
                    }
                } else if ($(this).val().length < 1) {
                    var $el = $(this);
                    if ($(this).attr('id') == 'address1' && $('.single-sc_product').length > 0) {
                        $el = $('#sc-payment-form #address2');
                    }
                    $(this).addClass('invalid');
                    $el.closest('.form-group').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
                    errors = true;
                }
            });

            // check username 
            if ($('#sc-wpuserid').length > 0) {
                var $username = $('#sc-wpuserid');
                if (($username.val().length > 0)) {
                    var check = checkUsername($username);
                    if (check == false) {
                        errors = true;
                    }
                }
            }

            // check email address
            $('#sc-payment-form input[type="email"]').each(function () {
                if ($(this).val().length) {
                    var validEmail = regExEmail.test($(this).val());
                    if (!validEmail) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_email + '</div>');
                        errors = true;
                    }
                }
            });

            // check phone number
            $('#sc-payment-form input[type="phone"]').each(function () {
                if ($(this).val().length) {
                    var validPhone = isValidPhonenumber($(this).val());
                    if (!validPhone) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_phone + '</div>');
                        errors = true;
                    }
                }
            });


            // check password
            $('#sc-payment-form input.sc-password').each(function () {
                if ($(this).val().length) {
                    var validPass = isValidPassword($(this).val());
                    if (!validPass) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_pass + '</div>');
                        errors = true;
                    }
                }
            });

            if($('#sc-payment-form input').closest('.form-group').find('.error').length>0){
                errors = true;
            }

            return errors;
        }

        // 2 step form
        $(document).on('click', '.sc-next-btn', function () {
            var errors = sc_validate();
            if (errors) {
                return;
            } else {
                if (!$('[name="sc-lead-captured"]').length) {
                    sc_do_lead_capture();
                }
                $('#sc-payment-form').toggleClass('step-1 step-2');
                $('.sc-checkout-form-steps .steps').toggleClass('sc-current');
            }
        });

        function sc_do_lead_capture() {
            var paramObj = {};
            $.each($('#sc-payment-form').serializeArray(), function (_, kv) {
                paramObj[kv.name] = kv.value;
            });

            paramObj['action'] = 'sc_capture_lead';

            $.post(studiocart.ajax, paramObj, function (response) {
                //console.log('capturing lead');
                if (response == 'OK') {
                    $('#sc-payment-form').trigger('studiocart/orderform/lead_captured');
                    var form = document.getElementById('sc-payment-form');
                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'sc-lead-captured');
                    hiddenInput.setAttribute('value', 1);
                    form.appendChild(hiddenInput);
                }
            });
            return true;
        }

        $(document).on('click', '.sc-checkout-form-steps .steps', function () {
            if ($(this).hasClass('step-two')) {
                var errors = sc_validate();
                if (errors) {
                    return false;
                } else if (!$('[name="sc-lead-captured"]').length) {
                    sc_do_lead_capture();
                }
            }

            if ($(this).hasClass('sc-current')) {
                return false;
            }

            $('#sc-payment-form').toggleClass('step-1 step-2');
            $('.sc-checkout-form-steps .steps').toggleClass('sc-current');
            return false;

        });

        // check pay info
        var $btn = $('#sc_card_button'),
            $form = $('#sc-payment-form'),
            clientSecret,
            intent_id,
            amount,
            customer_id,
            sc_temp_order_id;

        if ($('.sc-stripe #card-element').length > 0) {
            // Create a Stripe client.
            var stripe = Stripe(stripe_key[0]);

            // Create an instance of Elements.
            var elements = stripe.elements();

            // Custom styling can be passed to options when creating an Element.
            // (Note that this demo uses a wider set of styles than the guide below.)
            var style = {
                base: {
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };

            // Create an instance of the card Element.
            var card = elements.create('card', { style: style });

            // Add an instance of the card Element into the `card-element` <div>.
            card.mount('.sc-stripe #card-element');

            // Handle real-time validation errors from the card Element.
            card.addEventListener('change', function (event) {
                var displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Reinitialize Stripe elements for forms inside Elementor popups
            jQuery(document).on('elementor/popup/show', () => {

                card.unmount('.sc-stripe #card-element');
                card.mount('.sc-stripe #card-element');

                // Handle real-time validation errors from the card Element.
                card.addEventListener('change', function (event) {
                    var displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });
            });
        }

        if ($(".sc-grtoken").length > 0) {
            grecaptcha.ready(function () {
                var gsitekey = $(".sc-grtoken").attr("data-sitekey");
                grecaptcha.execute(gsitekey, { action: 'submit' }).then(function (token) {
                    $(".sc-grtoken").val(token);
                });
            });
        }

        // Handle form submission.
        $(document).on('click', '#sc_card_button', function (event) {

            $(this).addClass('running').attr("disabled", true);

            event.preventDefault();

            var errors = sc_validate();

            if (errors) {
                alert('Required fields missing');
                $btn = $('#sc_card_button');
                $btn.removeClass('running').removeAttr("disabled");
                return false;
            } else {
                var gcaptchasize = jQuery(".g-recaptcha").attr("data-size");
                if (gcaptchasize == "invisible") {
                    grecaptcha.ready(function () {
                        grecaptcha.execute();
                        console.log("It works");
                    });
                }
            }

            if ($(".sc-grtoken").length > 0) {
                grecaptcha.ready(function () {
                    var gsitekey = $(".sc-grtoken").attr("data-sitekey");
                    grecaptcha.execute(gsitekey, { action: 'submit' }).then(function (token) {
                        $(".sc-grtoken").val(token);
                    });
                });
            }

            setTimeout(function () {

                var is_subscription = $('.ob-sub:checked').length || ($('input[name="sc_product_option"]:checked').data('installments') && !$('.ob-replace:checked').length);

                if ($('#sc-payment-form [name="pay-method"]').length > 0) {
                    if ($('[name="pay-method"]:checked').val() != 'stripe' && ($('[name="sc_amount"]').val() != 0 || is_subscription) && $('[name="pay-method"]:checked').val() != 'cod') {
                        $('#sc-payment-form').trigger('studiocart/orderform/submit');
                        return false;
                    }
                }

                var paramObj = {};
                $.each($('#sc-payment-form').serializeArray(), function (_, kv) {
                    if(kv.name != 'sc-orderbump[]'){
                        paramObj[kv.name] = kv.value;
                    }
                });

                $('input[name="sc-orderbump[]"]').each(function (key,value) {
                    if(this.checked){
                        paramObj['sc-orderbump['+key+']'] = $(this).val();
                    }
                });

                // process free payment, manual payment methods
                var manual = ($('#sc-payment-form [name="pay-method"]:checked').val() == 'cod'),
                    plan = $('input[name=sc_product_option]:checked');

                if ($('[name="sc_amount"]').val() == 0 && !is_subscription || manual) {
                    paramObj['action'] = 'save_order_to_db';
                    paramObj['pay-method'] = 'cod'; // change method to "COD", otherwise order will get stuck in "pending" status
                    console.log(paramObj);
                    $.post(studiocart.ajax, paramObj, function (res) {
                        var response = JSON.parse(res);
                        if ('undefined' !== typeof response.error) {
                            alert(response.error);
                            
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function (i, item) {
                                    invalidate_field($('[name="'+item['field']+'"]'), item['message'])
                                });
                            }
                            
                            $btn = $('#sc_card_button');
                            $btn.removeClass('running').removeAttr("disabled");
                        } else {
                            var form = document.getElementById('sc-payment-form');

                            if ('undefined' !== typeof response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                if ('undefined' !== typeof response.formAction) {
                                    $form = $('#sc-payment-form');
                                    $form.attr('action', response.formAction);
                                }
                                var hiddenInput = document.createElement('input');
                                hiddenInput.setAttribute('type', 'hidden');
                                hiddenInput.setAttribute('name', 'sc_order_id');
                                hiddenInput.setAttribute('value', response.order_id);
                                form.appendChild(hiddenInput);
                                $form = $('#sc-payment-form');
                                $form.submit();
                            }
                        }
                    });
                    return false;
                }

                paramObj['action'] = 'create_payment_intent';

                if (!intent_id) {

                    // create new intent if none found
                    $.post(studiocart.ajax, paramObj, function (res) {

                        var response = JSON.parse(res);
                        if ('undefined' !== typeof response.error) {
                            alert(response.error);
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function (i, item) {
                                    invalidate_field($('[name="'+item['field']+'"]'), item['message'])
                                });
                            }
                            $btn.removeClass('running').removeAttr("disabled");
                            return false;
                        } else {
                            if ('undefined' !== typeof response.redirect) {
                                window.location.href = response.redirect;
                            } else {
                                clientSecret = response.clientSecret;
                                intent_id = response.intent_id;
                                customer_id = response.customer_id;
                                amount = response.amount;
                                sc_temp_order_id = response.sc_temp_order_id;

                                if (is_subscription) {
                                    confirmSubscription(response);
                                } else {
                                    // create new order in db then send to stripe
                                    saveThenConfirm(response);
                                }
                            }
                        }
                    });
                } else {

                    var intent = {
                        'clientSecret': clientSecret,
                        'intent_id': intent_id,
                        'customer_id': customer_id,
                        'amount': amount
                    };

                    if (is_subscription) {
                        // If a previous payment was attempted, get the lastest invoice
                        const latestInvoicePaymentIntentStatus = localStorage.getItem(
                            'latestInvoicePaymentIntentStatus'
                        );

                        if (
                            latestInvoicePaymentIntentStatus === 'requires_payment_method' ||
                            latestInvoicePaymentIntentStatus === 'requires_action'
                        ) {
                            const invoiceId = localStorage.getItem('latestInvoiceId');
                            const isPaymentRetry = true;
                            // create new payment method & retry payment on invoice with new payment method
                            confirmSubscription(
                                intent,
                                isPaymentRetry,
                                invoiceId
                            );
                        } else {
                            confirmSubscription(intent);
                        }
                    } else {
                        if ($('input[name=order_id]').length == 0) {
                            // create new order in db then send to stripe
                            saveThenConfirm(intent);
                        } else {
                            if (!amount) {
                                paramObj['action'] = 'update_payment_intent_amt';
                                paramObj['intent_id'] = intent.intent_id;
                                $.post(studiocart.ajax, paramObj, function (amt) {
                                    if (!isNaN(amt)) {
                                        amount = amt;
                                        intent.amount = amt;
                                    }
                                });
                            }

                            // create new order in db then send to stripe
                            confirmCardPayment(intent);
                        }
                    }
                }

            }, 1000);

        });
        
        $('.sc_cancel_sub').click(function(){
            
            if (confirm(sc_translate_frontend.confirm_cancel_sub)) { 
                var ajaxurl = studiocart.ajax;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce	= jQuery("#sc_nonce").val();
                var subscriber_id = (_this.data('item-id')) ? _this.data('item-id') : $("#sc_payment_intent").val();
                var payment_method	= jQuery("#sc_payment_method").val();
                var prod_id = jQuery("#sc_product_id").val();

                var data = {
                    'action': 'sc_unsubscribe_customer',
                    'prod_id': prod_id,
                    'subscription_id': subscriber_id,
                    'nonce': wp_nonce,
                    'id': id,
                    'payment_method': payment_method,
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                jQuery.post(ajaxurl, data, function(response) {
                    console.log(response);
                    if( response == 'OK' ){
                        alert(sc_translate_frontend.sub_cancel); //custom message
                        location.reload();
                    }else{
                        alert(response);
                    }
                }).fail(function() {
                    alert(response.fail);  
                });
            } else {
              return false;
            } 
        });

        function saveThenConfirm(intent) {
            confirmCardPayment(intent);
        }

        function createSubscription(customerId, paymentMethodId, invoiceId) {
            var f = document.getElementById('sc-payment-form');
            const form = new FormData(f);
            form.set('action', 'create_subscription');
            form.set('customerId', customerId);
            form.set('paymentMethodId', paymentMethodId);

            if (invoiceId) {
                form.set('invoiceId', invoiceId);
            }

            const params = new URLSearchParams(form);

            return (
                fetch(studiocart.ajax, {
                    method: 'post',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                    },
                    body: params
                })
                    .then((response) => {
                        return response.json();
                    })
                    // If the card is declined, display an error to the user.
                    .then((result) => {
                        if (result.error) {
                            // The card had an error when trying to attach it to a customer
                            throw result.error;
                        }
                        return result;
                    })
                    // Normalize the result to contain the object returned
                    // by Stripe. Add the addional details we need.
                    .then((result) => {

                        // Insert the token ID into the form so it gets submitted to the server
                        var form = document.getElementById('sc-payment-form');

                        if ('undefined' !== typeof result.formAction) {
                            $form.attr('action', result.formAction);
                        }

                        var hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'sc_order_id');
                        hiddenInput.setAttribute('value', result.sc_order_id);
                        form.appendChild(hiddenInput);

                        return {
                            // Use the Stripe 'object' property on the
                            // returned result to understand what object is returned.
                            subscription: result,
                            paymentMethodId: paymentMethodId,
                        };
                    })
                    // Some payment methods require a customer to do additional
                    // authentication with their financial institution.
                    // Eg: 2FA for cards.
                    .then(handlePaymentThatRequiresCustomerAction)
                    // If attaching this card to a Customer object succeeds,
                    // but attempts to charge the customer fail. You will
                    // get a requires_payment_method error.
                    .then(handleRequiresPaymentMethod)
                    // No more actions required. Provision your service for the user.
                    .then(function () {
                        //console.log('result: ' + $('input[name=order_id]').val());
                        $form.submit();
                    })
                    .catch((error) => {
                        // An error has happened. Display the failure to the user here.
                        // We utilize the HTML element we created.
                        displayError(error);
                    })
            );
        }

        function retryInvoiceWithNewPaymentMethod(
            customerId,
            paymentMethodId,
            invoiceId
        ) {

            var f = document.getElementById('sc-payment-form');
            const form = new FormData(f);
            form.set('action', 'create_subscription');
            form.set('customerId', customerId);
            form.set('paymentMethodId', paymentMethodId);

            if (invoiceId) {
                form.set('invoiceId', invoiceId);
            }

            const params = new URLSearchParams(form);

            return (
                fetch(studiocart.ajax, {
                    method: 'post',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8',
                    },
                    body: params
                })
                    .then((response) => {
                        return response.json();
                    })
                    // If the card is declined, display an error to the user.
                    .then((result) => {
                        if (result.error) {
                            // The card had an error when trying to attach it to a customer
                            throw result;
                        }
                        return result;
                    })
                    // Normalize the result to contain the object returned
                    // by Stripe. Add the addional details we need.
                    .then((result) => {
                        return {
                            // Use the Stripe 'object' property on the
                            // returned result to understand what object is returned.
                            invoice: result,
                            paymentMethodId: paymentMethodId,
                            isRetry: true,
                        };
                    })
                    // Some payment methods require a customer to be on session
                    // to complete the payment process. Check the status of the
                    // payment intent to handle these actions.
                    .then(handlePaymentThatRequiresCustomerAction)
                    // No more actions required. Provision your service for the user.
                    .then(function () {
                        //console.log('result: ' + $('input[name=order_id]').val());
                        $form.submit();
                    })
                    .catch((error) => {
                        // An error has happened. Display the failure to the user here.
                        // We utilize the HTML element we created.
                        displayError(error);
                    })
            );
        }

        function handlePaymentThatRequiresCustomerAction({
            subscription,
            invoice,
            priceId,
            paymentMethodId,
            isRetry,
        }) {
            // If it's a first payment attempt, the payment intent is on the subscription latest invoice.
            // If it's a retry, the payment intent will be on the invoice itself.
            let paymentIntent = invoice
                ? invoice.payment_intent
                : subscription.latest_invoice.payment_intent;

            if (!paymentIntent)
                return { subscription, priceId, paymentMethodId };

            if (
                paymentIntent.status === 'requires_action' ||
                (isRetry === true && paymentIntent.status === 'requires_payment_method')
            ) {
                return stripe
                    .confirmCardPayment(paymentIntent.client_secret, {
                        payment_method: paymentMethodId,
                    })
                    .then((result) => {
                        if (result.error) {
                            // start code flow to handle updating the payment details
                            // Display error message in your UI.
                            // The card was declined (i.e. insufficient funds, card has expired, etc)
                            throw result;
                        } else {
                            if (result.paymentIntent.status === 'succeeded') {
                                // There's a risk of the customer closing the window before callback
                                // execution. To handle this case, set up a webhook endpoint and
                                // listen to invoice.paid. This webhook endpoint returns an Invoice.
                                return {
                                    priceId: priceId,
                                    subscription: subscription,
                                    invoice: invoice,
                                    paymentMethodId: paymentMethodId,
                                };
                            }
                        }
                    });
            } else {
                // No customer action needed
                return { subscription, priceId, paymentMethodId };
            }
        }

        function handleRequiresPaymentMethod({
            subscription,
            paymentMethodId,
            priceId,
        }) {
            if (subscription.status === 'active' || subscription.status === 'trialing') {
                // subscription is active, no customer actions required.
                return { subscription, priceId, paymentMethodId };
            } else if (
                subscription.latest_invoice.payment_intent.status ===
                'requires_payment_method'
            ) {
                // Using localStorage to store the state of the retry here
                // (feel free to replace with what you prefer)
                // Store the latest invoice ID and status
                localStorage.setItem('latestInvoiceId', subscription.latest_invoice.id);
                localStorage.setItem(
                    'latestInvoicePaymentIntentStatus',
                    subscription.latest_invoice.payment_intent.status
                );
                throw { error: { message: 'Your card was declined.' } };
            } else {
                return { subscription, priceId, paymentMethodId };
            }
        }



        function confirmSubscription(intent, isPaymentRetry = false, invoiceId = false) {

            var customer_name = $('#first_name').val() + ' ' + $('#last_name').val(),
                customer_email = $('#email').val();

            stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    name: customer_name,
                    email: customer_email,
                },
            })
                .then((result) => {
                    if (result.error) {
                        displayError(result);
                    } else {
                        if (isPaymentRetry) {
                            // Update the payment method and retry invoice payment
                            retryInvoiceWithNewPaymentMethod(
                                intent.customer_id,
                                result.paymentMethod.id,
                                invoiceId
                            );
                        } else {
                            // Create the subscription
                            createSubscription(
                                intent.customer_id,
                                result.paymentMethod.id
                            );
                        }
                    }
                });
        }

        function displayError(event) {
            if (event.error) {
                alert(event.error.message);
            } else if (event) {
                alert(event);
            }
            $btn.removeClass('running').removeAttr("disabled");

        }

        function confirmCardPayment(intent) {
            var customer_name = $('#first_name').val() + ' ' + $('#last_name').val(),
                customer_email = $('#email').val();
            stripe.confirmCardPayment(intent.clientSecret, {
                payment_method: {
                    card: card,
                    billing_details: {
                        name: customer_name,
                        email: customer_email,
                    },
                },
                setup_future_usage: 'off_session'
            }).then(function (result) {

                if (result.error) {
                    // Display error.message in your UI.
                    displayError(result);
                } else {
                    var paramObj = {};

                    $.each($('#sc-payment-form').serializeArray(), function (_, kv) {
                        if(kv.name != 'sc-orderbump[]'){
                            paramObj[kv.name] = kv.value;
                        }
                    });
    
                    $('input[name="sc-orderbump[]"]').each(function (key,value) {
                        if(this.checked){
                            paramObj['sc-orderbump['+key+']'] = $(this).val();
                        }
                    });

                    if(intent.sc_temp_order_id){
                        paramObj['sc_temp_order_id'] = intent.sc_temp_order_id;
                    }

                    paramObj['customer_id'] = intent.customer_id;
                    paramObj['intent_id'] = intent.intent_id;
                    paramObj['amount'] = intent.amount;
                    $.post(studiocart.ajax, paramObj, function (res) {
                        var response = JSON.parse(res);
                        if ('error' in response) {
                            alert(response.error);
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function (i, item) {
                                    invalidate_field($('[name="'+item['field']+'"]'), item['message'])
                                });
                            }
                            $btn.removeClass('running').removeAttr("disabled");
                            return false;
                        } else {
                            if (result.paymentIntent) {
                                var data = {
                                    'paymentIntent': result.paymentIntent,
                                    'action': 'update_stripe_order_status',
                                    'response': response,
                                    'intent_id': intent.intent_id,
                                    'sc-nonce': $('input[name=sc-nonce]').val(),
                                };
                                if(jQuery('input[name="sc-auto-login"]').length==1){
                                    data['sc-auto-login'] = 1;
                                }
                                $.post(studiocart.ajax, data, function (res) {
                                    //if (!isNaN(order_id)) {
                                    // Insert the token ID into the form so it gets submitted to the server
                                    var response = JSON.parse(res);
                                    var form = document.getElementById('sc-payment-form');
                                    if ('undefined' !== typeof response.formAction) {
                                        $form.attr('action', response.formAction);
                                    }
                                    var hiddenInput = document.createElement('input');
                                    hiddenInput.setAttribute('type', 'hidden');
                                    hiddenInput.setAttribute('name', 'sc_order_id');
                                    hiddenInput.setAttribute('value', response.order_id);
                                    form.appendChild(hiddenInput);
                                    var hiddenInput2 = document.createElement('input');
                                    hiddenInput2.setAttribute('type', 'hidden');
                                    hiddenInput2.setAttribute('name', 'intent_id');
                                    hiddenInput2.setAttribute('value', intent.intent_id);
                                    form.appendChild(hiddenInput2);
                                    if (intent.amount == false) {
                                        intent.amount = response.amount
                                    }
                                    $form.submit();
                                    //}
                                });
                            }



                        }
                    });
                }
            });
        }

        /*--Added paypal Scripts--*/
        if (studiocart.pay_method == 'paypal') {
            $(document).on('studiocart/upsell/ready/accept_link', '.sc-accept-upsell-link', function () {
                $('a[href*="sc-upsell-offer=yes"],.sc-accept-upsell-link').attr('href', '#');
            });

            $(document).on('studiocart/upsell/ready/decline_link', '.sc-decline-upsell-link', function () {
                $('a[href*="sc-upsell-offer=no"],.sc-decline-upsell-link').attr('href', studiocart.upsell_decline + '&sc-pp=1');
            });
        }

        $(document).on('studiocart/upsell/accept/paypal', '.sc-accept-upsell-link', function (event) {
            let us_data = {
                'action': 'paypal_process_upsell',
                'sc-order': studiocart.sc_order,
                'sc-nonce': studiocart.sc_nonce,
                'cancel_url': window.location.href,
                'return_url': studiocart.upsell_accept,
            };
            // flag for downsells
            if ('undefined' !== typeof studiocart.is_downsell && '1' == studiocart.is_downsell) {
                us_data['downsell'] = 1;
            }

            $.post(studiocart.ajax, us_data, function (res) {
                var response = JSON.parse(res);
                if ('undefined' !== typeof response.error) {
                    alert(response.error);
                    window.location.href = studiocart.upsell_decline + '&sc-pp=1';
                } else {
                    window.location.href = response.url;
                }
            });
            return false;
        });

        // Handle paypal form submission.
        $(document).on('studiocart/orderform/submit', '#sc-payment-form', function (event) {
            if ($('.pay-methods').length > 0 && $('[name="pay-method"]:checked').val() != 'paypal') {
                return false;
            }
            var paramObj = {};
            $.each($('#sc-payment-form').serializeArray(), function (_, kv) {
                if(kv.name != 'sc-orderbump[]'){
                    paramObj[kv.name] = kv.value;
                }
            });

            $('input[name="sc-orderbump[]"]').each(function (key,value) {
                if(this.checked){
                    paramObj['sc-orderbump['+key+']'] = $(this).val();
                }
            });

            paramObj['sc_page_id'] = studiocart.page_id;

            paramObj['action'] = 'sc_paypal_request';
            paramObj['cancel_url'] = window.location.href;
            //var is_subscription = $('input[name="sc_product_option"]:checked').data('installments');
            $.post(studiocart.ajax, paramObj, function (res) {
                var response = JSON.parse(res);
                if ('undefined' !== typeof response.error) {
                    alert(response.error);
                    $('#sc_card_button').removeClass('running').removeAttr("disabled");
                } else {
                    window.location.href = response.url;
                }
            });
        });
    });
})(jQuery);
