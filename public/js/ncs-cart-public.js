(function($) {
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
        states = sc_country_select_states.sc_states,
        sc_country = sc_country_select_states.sc_country,
        $select_state,
        $select_country;

    
    function key_value_pair_selectize(json_data) {
        let format_data = [];
        $.each(json_data, function(i, item) {
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

    function country_selectize() {
        $('.sc-form-wrap').each(function(){
            let current_country;
            let current_state;

            let form = $(this);
            let form_id = '#'+form.attr('id');

            if ($('#state', form_id).length) {
                current_state = $('#state', form_id).val();
            }
                
            if ($('#country', form_id).length < 1) {
                current_country = sc_country;
            } else {
                current_country = $('#country', form_id).val();
                $('#country', form_id).selectize()[0].selectize.destroy();
                $select_country = $('#country', form_id).selectize({
                    onChange: function(value) {
                        if (!value.length) return;
                        if ($('#state', form_id).length > 0) {
                            $('#state', form_id).selectize()[0].selectize.clearOptions();
                            $('#state', form_id).selectize()[0].selectize.destroy();
                            let new_state_option = key_value_pair_selectize(states[value]);
                            let state_placeholder = $('#state', form_id).attr('placeholder');
                            let $required = "";
                            if (jQuery('#state', form_id).hasClass('required')) {
                                $required = "required";
                            }
                            if ($('#state option', form_id).length > 0) {
                                state_placeholder = $('#state option[value=""]', form_id).text();
                            }
                            if (new_state_option.length === 0) {
                                $('#state', form_id).replaceWith('<input id="state" name="state" type="text" class="form-control ' + $required + '" placeholder="' + state_placeholder + '" value="" aria-label="State">');
                            } else {
                                $('#state', form_id).replaceWith('<select id="state" name="state" class="form-control ' + $required + '" style="display: block;"><option value="" selected>' + state_placeholder + '</option></select>');
                                $select_state = $('#state', form_id).selectize({
                                    valueField: 'state_key',
                                    labelField: 'state_value',
                                    searchField: 'state_value',
                                    options: new_state_option,
                                    onChange: function(value) {
                                        if (value.length >= 1) {
                                            var $el = $('#state', form_id);
                                            $el.removeClass('invalid').closest('.form-group').find('.error').remove();
                                            $el.closest('.form-group').find('.selectize-input').removeClass('invalid');
                                        }
                                    }
                                });
                            }
                        }

                    }
                });
                $select_country[0].selectize.setValue(current_country);
            }
            if ($('#state', form_id).length > 0) {
                let state_option = key_value_pair_selectize(states[current_country]);
                if (state_option.length === 0) {
                    $('#state', form_id).replaceWith('<input id="state" name="state" type="text" class="form-control  required" placeholder="State" value="'+current_state+'" aria-label="State">');
                } else {
                    $select_state = $('#state', form_id).selectize({
                        valueField: 'state_key',
                        labelField: 'state_value',
                        searchField: 'state_value',
                        options: state_option
                    });
                    $select_state[0].selectize.setValue(current_state);
                }
            }
        });
    }

    $(document).ready(function() {

        var update_form = async function(wrap_id){

            wrap_id = wrap_id.replace('#', '');

            var $item = $('input[name=sc_product_option]:checked', '#'+wrap_id);
            if(!$item.closest('.item').hasClass('sc-selected')) {
                $('input[name=sc_product_option]', '#'+wrap_id).each(function(){
                    $(this).closest('.item').removeClass('sc-selected');
                });
                $item.closest('.item').addClass('sc-selected');
            }

            let form = document.getElementById(wrap_id);
            form = form.getElementsByTagName('form')[0];

            var paramObj = new FormData(form);
            paramObj.set("action", "sc_update_cart_amount");

            if (typeof sc_tax_settings !== 'undefined' && $('select[name="country"]', '#'+wrap_id).val() != '') {
                //$('#sc_card_button').addClass('running').attr("disabled", true);
                var tax_obj = {},
                    tax_rate_data = {};
                tax_obj.country = $('select[name="country"]', '#'+wrap_id).val() ? $('select[name="country"]', '#'+wrap_id).val() : "";
                tax_obj.city = $('input[name="city"]', '#'+wrap_id).val() ? $('input[name="city"]', '#'+wrap_id).val() : "";
                tax_obj.state = $('select[name="state"]', '#'+wrap_id).val() ? $('select[name="state"]', '#'+wrap_id).val() : "";
                tax_obj.zip = $('input[name="zip"]', '#'+wrap_id).val() ? $('input[name="zip"]', '#'+wrap_id).val() : "";
                tax_obj.vat_number = $('input[name="vat-number"]', '#'+wrap_id).val() ? $('input[name="vat-number"]', '#'+wrap_id).val() : "";
                tax_obj.nonce = sc_tax_settings.nonce;
                tax_obj.action = 'get_match_tax_rate';
                $.ajax({
                    type: "post",
                    dataType: "json",
                    url: studiocart.ajax,
                    data: tax_obj,
                    success: function(response) {
                        tax_rate_data = response.rates;
                        $('.vat_container', '#'+wrap_id).hide();
                        let vat_error = "Invalid VAT Number";
                        $('#vat_number', '#'+wrap_id).removeClass('valid invalid');
                        $('#vat_number', '#'+wrap_id).parent().find('.error').remove();
                        if (!response.is_valid_vat) {
                            $('#vat_number', '#'+wrap_id).addClass('invalid');
                            if (response.vat_error != "") {
                                vat_error = response.vat_error;
                            }
                            if ($('#vat_number', '#'+wrap_id).parent().find('.error').length == 0) {
                                $('#vat_number', '#'+wrap_id).parent().append('<div class="error">' + vat_error + '</div>')
                            } else {
                                $('#vat_number', '#'+wrap_id).parent().find('.error').html(vat_error);
                            }
                        } else if(tax_obj.vat_number != '') {
                            $('#vat_number', '#'+wrap_id).addClass('valid');
                        }
                        if (response.is_vat) {
                            $('.vat_container', '#'+wrap_id).show();
                        }
                        
                    }
                });
            }

            try {
				const response = await fetch(studiocart.ajax, {
					method: 'POST',
					body:paramObj
				});
				if (response.ok) {
					const resp = await response.json();
					if (resp.order_summary_items) {
                        var container = $('#'+wrap_id).find('.summary-items'),
                            totalContainer = $('#'+wrap_id).find('.total');

                        container.empty();
                        totalContainer.empty();

                        $('input[name="sc_amount"]', '#'+wrap_id ).val(resp.total);

                        if (resp.total == 0 && typeof resp.sub_summary == 'undefined') {
                            $('.pay-info', '#'+wrap_id).hide();
                        } else {
                            $('.pay-info', '#'+wrap_id).show();
                        }

						if(resp.order_summary_items.length === 0) {
                            $('#sc_card_button', '#'+wrap_id).hide();
                            container.append('<div class="item"><span class="sc-label">'+resp.empty+'</span></div>');
                        } else {
                            $('#sc_card_button', '#'+wrap_id).show();
                            resp.order_summary_items.forEach(item => {
                                if(Number(item.quantity) > 1) {
                                    item.name += ' x ' + item.quantity;
                                }
                                container.append('<div class="item '+item.type+'"><span class="sc-label">'+item.name+'</span><span class="price">'+item.subtotal+'</span></div>');
                            });

                            totalContainer.append(resp.total_html);

                            if (typeof resp.sub_summary != 'undefined') {
                                totalContainer.append('<small>' + resp.sub_summary + '</small>');
                            }
                        }
					}
					return resp.success;
				} else {
					alert('Something went wrong.')
				}
			} catch (error) {
				alert('Something went wrong.')
			}
            return false;

        };

        $(document).on('click', ".studiocart .coupon-code input[type='button']", function(e) {
            e.preventDefault();
            var form = '#'+$(this).closest('form').parent().attr('id');
            $(this).attr('disabled', 'disabled');
            clear_coupon(form);
            if ($('#discount-code', form).val() == '') {
                $(".coupon-code input[type='button']", form).removeAttr('disabled');
            } else {
                try_coupon($('#discount-code', form).val(), form);
            }
        });
        
        $(document).on('change', "#sc-credit", function() {
            var form = '#'+$(this).closest('form').parent().attr('id');
            if(!$(this).is(':checked')){
                clear_coupon(form);
                $(".coupon-code input[type='button']", form).removeAttr('disabled');
            } else {
                try_coupon('sc-credit', form);
            }
        });

        $(document).on('click', ".sc-password-toggle", function() {
            var field = $(this).parent().prev('.sc-password');
            if ($(this).is(':checked')) {
                field.attr('type', 'text');
            } else {
                field.attr('type', 'password');
            }
        });

        $(document).on('change', 'input[name="pay-method"]', function() {
            var form = '#'+$(this).closest('form').parent().attr('id');
            togglePayMethods(form);
        });

        $(document).on('click', '#sc-coupon-toggle', function() {
            var form = '#'+$(this).closest('form').parent().attr('id');
            $('#sc-coupon-form', form).fadeToggle();
            return false;
        });

        //function address_fields() {

        if (typeof sc_popup == 'undefined' || !sc_popup.is_popup || sc_popup.is_popup == "false") {
            country_selectize();
        } else {
            jQuery(document).on('elementor/popup/show', () => {
                country_selectize();
            });
        }
        //address_fields();

        function togglePayMethods(form) {
            var method = $('[name="pay-method"]:checked', form).val();
            if (method != 'stripe') {
                $('.sc-stripe', form).fadeOut();
                //$('#paypal-button-container').fadeIn();
            } else {
                $('.sc-stripe', form).fadeIn();
                $('#paypal-button-container', form).fadeOut();
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

        $(document).on('change', 'input[name=sc_product_option]', function() {
            var form = '#'+$(this).closest('form').parent().attr('id');
            update_pwyw(form);
        });

        function update_pwyw(form) {
            var dataType = $('input[name=sc_product_option]:checked', form).attr('data-val');
            var dataPrice = $('input[name=sc_product_option]:checked', form).attr('data-price');
            var id = $('input[name=sc_product_option]:checked', form).attr('id');
            var value = $('input[name=sc_product_option]:checked', form).val();
            $('.pwyw-input', form).fadeOut();
            if (dataType && dataType == 'pwyw') {
                $('#' + id, form).data('price', dataPrice);
                $('#pwyw-amount-input-' + value, form).val(dataPrice);
                $('#pwyw-input-block-' + value, form).fadeIn().focus();
            } else {
                $('#pwyw-input-block-' + value, form).fadeOut();
            }
        }

        $(document).on('change blur focusout', 'input[name^="pwyw_amount"]', function() {
            var value = $(this).val();
            var minvalue = $(this).attr('min');
            var id = $('input[name=sc_product_option]:checked').attr('id');
            var form = $(this).closest('form').parent().attr('id');
            if (parseFloat(value) < parseFloat(minvalue)) {
                $(this).addClass('invalid');
                if ($(this).closest('.item').find('.error').length == 0)
                    $(this).closest('.item').append('<div class="error">Please enter an amount greater than or equal to ' + format_price(parseFloat(minvalue).toFixed(2)) + '</div>')
            }
            if (value) {
                $(this).closest('.item').remove('.error');
                $("#" + id).attr('data-price', value);
                $("#" + id).data('price', value);
                $("#" + id).parent().find('.price').html('<span class="sc-Price-currencySymbol">$</span>' + parseFloat($("#" + id).attr('data-price')).toFixed(2));
                update_form(form);
            }
        });

        $(document).on('change', '#sc_qty, .sc_qty, input[name=sc_product_option], #sc-orderbump, .sc_orderbump, input[data-scq-price],select[name="country"], select[name="state"], input[name="city"], input[name="zip"], input[name="vat-number"]', function() {
            var form = $(this).closest('form').parent().attr('id');
            update_form(form);
        });

        $("#discount-code").keydown(function() {
            $("#coupon-status").text('').hide();
        });

        $(".coupon-code.applied").on("click", "button.coupon", function(e) {
            var form = $(this).closest('form').parent();
            e.preventDefault();
            form.find(".coupon-code").show();
        });

        function clear_coupon(form) {
            $('.coupon-code.applied, .cart-discount', form).hide();
            $('#sc-coupon-id', form).val('').removeData('type', 'amount', 'plans');
            $("#coupon-status", form).delay(3000).fadeOut();
            $('#sc-coupon-id', form).data('type')

            $('#sc-payment-form .products .item', form).each(function() {
                var $item = $(this).find('input[type="radio"]'),
                    price = $item.data('og-price');

                if (typeof $item.data('og-price') !== 'undefined') {
                    $(this).find('.price').html(format_price(price));
                    $item.data('price', price);
                }
                $(this).find('.coup-confirm').remove();
            });
            update_form(form);
        }

        function try_coupon(coup_code, form) {

            var nonce = $('input[name=sc-nonce]', form).val(),
                prod_id = $('input[name=sc_product_id]', form).val(),
                coupon_applied_text,
                obj = {
                    action: "sc_validate_coupon",
                    prod_id: prod_id,
                    coup_code: coup_code,
                    nonce: nonce,
                    ip: sc_user[0],
                    email: $('#email', form).val(),
                }

            if (!$('#email').val() && typeof sc_user[1] !== 'undefined') {
                obj.email = sc_user[1];
            }

            $.ajax({
                type: "post",
                dataType: "json",
                url: studiocart.ajax,
                data: obj,
                success: function(response) {
                    if (!response.error) {
                        let coupon_applied = true;
                        if( $('#sc-payment-form .products .item', form).length) {
                            let coupon_applied = false;
                            $('#sc-payment-form .products .item', form).each(function(index) {
                                var new_price,
                                    $item = $(this).find('input[type="radio"]'),
                                    price = ($item.data('og-price')) ? $item.data('og-price') : $item.data('price'),
                                    installments = $item.data('installments'),
                                    discount = response.amount;

                                if (response.plan && !response.plan.includes($item.val())) {
                                    return;
                                }

                                coupon_applied_text = format_price(response.amount);

                                if (price <= 0 && (response.type == 'percent' || response.type == 'fixed')) {
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

                                if (response.plan) {
                                    var plan_discount;
                                    if (response.type != 'cart-percent' && response.type != 'percent') {
                                        plan_discount = format_price(discount);
                                    } else {
                                        plan_discount = response.amount + '%';
                                    }
                                    coupon_applied_text = response.code + ' (-' + plan_discount + ')';
                                    $(this).find('coup-confirm').remove();
                                    $(this).find('label').append('<span class="coup-confirm">' + coupon_applied_text + '</span>');
                                } else {
                                    if (typeof response.success_text != 'undefined' && response.success_text != null) {
                                        coupon_applied_text = response.success_text;
                                    } else {
                                        coupon_applied_text = sc_translate_frontend.you_got + ' ' + coupon_applied_text + ' ' + sc_translate_frontend.off;
                                    }
                                    $(".coupon-code.applied .coup-confirm", form).html(coupon_applied_text).parent().fadeIn();
                                }

                            });
                        }
                        
                        if (coupon_applied) {
                            $('#sc-coupon-id', form).val(response.code);
                            if (response.type == 'cart-percent' || response.type == 'cart-fixed') {
                                $('#sc-coupon-id', form).data('type', response.type).data('amount', response.amount);
                            }
                            if (response.plan) {
                                $('#sc-coupon-id', form).data('plans', response.plan);
                            }
                        }
                    } else {
                        $("#coupon-status", form).text(response.error).fadeIn();
                        $('#sc-coupon-id', form).val('');

                        if ($(".coupon-code input[type='button']", form).length > 0) {
                            $(".coupon-code input[type='button']", form).show();
                        } else {
                            $("#coupon-status", form).delay(3000).fadeOut('normal', function() {
                                $(this).remove();
                            });
                        }
                        clear_coupon(form);
                    }
                    $(".coupon-code input[type='button']", form).removeAttr('disabled');
                    update_form(form);
                }
            })
        }

        $(document).on('click', '.qty-dec, .qty-inc', function(e) {
            e.preventDefault();
            var input = $(this).closest('.my-4').find('input'),
                val = input.val(),
                form = $(this).closest('form').parent().attr('id');
                
            if($(this).hasClass('qty-dec') && parseInt(val) > 0) {
                input.val(parseInt(val)-1);
                update_form(form);
            } else if($(this).hasClass('qty-inc')) {
                input.val(parseInt(val)+1);
                update_form(form);
            }
            return false;
        });

        /* Change Vat Customer Type */
        $(document).on('change', 'input[name="vat-number-available"]', function() {
            if (this.checked) {
                $('.vat_number_field').fadeIn(0);
            } else {
                $('.vat_number_field').fadeOut(0);
            }
        });

        // remove error message on focus
        $(document).on('focus', '#sc-payment-form input, #sc-payment-form select', function() {
            $(this).closest('.checkbox-wrap').removeClass('invalid').siblings('.error').remove();
            $(this).removeClass('invalid valid').siblings('.error').remove();

            if ($(this).attr('id') == 'address1') {
                $('.sc-address-2 .error').remove();
            }

            if ($(this).hasClass('selectized')) {
                $(this).next('.selectize-control').find('.selectize-input').removeClass('invalid');
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
                if ($field.hasClass('selectized')) {
                    $field.next('.selectize-control').find('.selectize-input').addClass('invalid');
                }
                if ($field.closest('#customer-details.sc-checkout-step').length > 0 && $('.step-two').hasClass('sc-current')) {
                    $('.steps.step-one a').click();
                }
            }
        }

        // check required on blur
        $(document).on('blur', '#sc-payment-form input.required', function() {
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
        $(document).on('blur', '#sc-wpuserid', function() {
            $(this).removeClass('invalid valid').siblings('.error').remove();

            if (($(this).val().length > 0)) {
                checkUsername($(this));
            }
        });

        $(document).on('click', '.sc-checkout-form-steps .steps', async function(event) {
            event.preventDefault();
            var form_wrapper = $(this).closest('.sc-form-wrap').attr('id');
            if ($(this).hasClass('step-two')) {
                var errors = await sc_validate(form_wrapper);
                if (errors) {
                    return false;
                } else if (!$('[name="sc-lead-captured"]', '#'+form_wrapper).length) {
                    sc_do_lead_capture(form_wrapper);
                }
            }

            if ($(this).hasClass('sc-current')) {
                return false;
            }

            $('#sc-payment-form', '#'+form_wrapper).toggleClass('step-1 step-2');
            $('.sc-checkout-form-steps .steps', '#'+form_wrapper).toggleClass('sc-current');
            return false;

        });

        async function checkUsername($el) {
            $el.removeClass('valid');

            var paramObj = new FormData();
            paramObj.append('name', $el.val());
            paramObj.append('sc-nonce', $('input[name=sc-nonce]').val());
            paramObj.append('action', 'sc_check_username');

            try {
				const response = await fetch(studiocart.ajax, {
					method: 'POST',
					body:paramObj
				});
				if (response.ok) {
					const resp = await response.json();
					if (resp.success) {
						$el.addClass('valid');
						$('#sc_card_button, .sc-next-btn').removeAttr("disabled");
					} else{
						$el.addClass('invalid');
						$el.closest('.form-group').append('<div class="error">' + resp.data.error + '</div>');
						$('#sc_card_button, .sc-next-btn').attr("disabled", true);
					}
					return resp.success;
				} else {
					alert('Something went wrong.')
					$el.addClass('invalid');
					$('#sc_card_button, .sc-next-btn').attr("disabled", true);
				}
			} catch (error) {
				alert('Something went wrong.')
				$el.addClass('invalid');
				$('#sc_card_button, .sc-next-btn').attr("disabled", true);
			}
            return false;
        }

        // check email address
        $(document).on('blur', '#sc-payment-form input[type="email"]', function() {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validEmail = regExEmail.test(val);
            if (!validEmail) {
                $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_email + '</div>');
            }
        });

        // check phone number
        $(document).on('blur', '#sc-payment-form input[type="tel"]', function() {
            if ($(this).val().length < 1) return;

            var val = $(this).val();
            var validPhone = isValidPhonenumber(val);
            if (!validPhone) {
                $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_phone + '</div>');
            }
        });

        // check password
        $(document).on('blur', '#sc-payment-form input.sc-password', function() {
            var val = $(this).val();
            if (val.length) {
                var validPass = isValidPassword(val);
                if (!validPass) {
                    $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_pass + '</div>');
                }
            }
        });

        /* track any change to total amount due
        $(document).on('change', '#sc-payment-form input', function() {
            amount = false;
        });*/

        

        async function sc_validate(form_wrapper) {
            var errors = false;

            if ($('.elementor-editor-active').length > 0) {
                return errors;
            }

            $(".error").remove();
            $('#'+form_wrapper+' input').removeClass('invalid');

            var fields = $('#'+form_wrapper+' input.required:visible, #'+form_wrapper+' select.required:visible, #'+form_wrapper+' select.required.selectized');
            fields.each(function () {
                if ($(this).attr('type') == 'checkbox' && !$(this).is(':checked')) {
                    $(this).closest('.checkbox-wrap').addClass('invalid').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
                    errors = true;
                } else if ($(this).attr('name') == 'pwyw_amount') {
                    var value = $(this).val();
                    var minvalue = $(this).attr('min');
                    if (parseFloat(value) < parseFloat(minvalue)) {
                        $(this).addClass('invalid');
                        if ($(this).closest('.form-group').find('.error').length == 0)
                            $(this).closest('.form-group').append('<div class="error">Please enter an amount greater thank or equal to <span class="sc-Price-currencySymbol">$</span>' + parseFloat(minvalue).toFixed(2) + '</div>')
                        errors = true;
                    }
                } else if ($(this).val().length < 1) {
                    var $el = $(this);
                    if ($(this).attr('id') == 'address1' && $('.single-sc_product').length > 0) {
                        $el = $('#'+form_wrapper+' #address2');
                    }
                    $(this).addClass('invalid');
                    $el.closest('.form-group').append('<div class="error">' + sc_translate_frontend.field_required + '</div>');
                    if ($(this).hasClass('selectized')) {
                        $(this).next('.selectize-control').find('.selectize-input').addClass('invalid');
                    }
                    errors = true;
                }
            });

            // check username 
            if ($('#'+form_wrapper+' #sc-wpuserid').length > 0) {
                var $username = $('#'+form_wrapper+' #sc-wpuserid');
                if (($username.val().length > 0)) {
                    var check = await checkUsername($username);
                    if (check == false) {
                        errors = true;
                    }
                }
            }

            // check email address
            $('#'+form_wrapper+' input[type="email"]').each(function () {
                if ($(this).val().length) {
                    var validEmail = regExEmail.test($(this).val());
                    if (!validEmail) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_email + '</div>');
                        errors = true;
                    }
                }
            });

            // check phone number
            $('#'+form_wrapper+' input[type="phone"]').each(function () {
                if ($(this).val().length) {
                    var validPhone = isValidPhonenumber($(this).val());
                    if (!validPhone) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_phone + '</div>');
                        errors = true;
                    }
                }
            });


            // check password
            $('#'+form_wrapper+' input.sc-password').each(function () {
                if ($(this).val().length) {
                    var validPass = isValidPassword($(this).val());
                    if (!validPass) {
                        $(this).addClass('invalid').closest('.form-group').append('<div class="error">' + sc_translate_frontend.invalid_pass + '</div>');
                        errors = true;
                    }
                }
            });

            if($('#'+form_wrapper+' input').closest('.form-group').find('.error').length>0){
                errors = true;
            }

            return errors;
        }

        // 2 step form
        $(document).on('click', '.sc-next-btn', async function () {
            var form_wrapper = $(this).data('form-wrapper');
            var errors = await sc_validate(form_wrapper);
            if (errors) {
                return;
            } else {
                if (!$('[name="sc-lead-captured"]', '#'+form_wrapper).length) {
                    sc_do_lead_capture(form_wrapper);
                }
                $('#sc-payment-form', '#'+form_wrapper).toggleClass('step-1 step-2');
                $('.sc-checkout-form-steps .steps', '#'+form_wrapper).toggleClass('sc-current');
            }
        });

        function sc_do_lead_capture(wrap_id) {
            var form = '#'+wrap_id;
            var paramObj = {};
            $.each($('#sc-payment-form', form).serializeArray(), function(_, kv) {
                paramObj[kv.name] = kv.value;
            });

            paramObj['action'] = 'sc_capture_lead';

            $.post(studiocart.ajax, paramObj, function(response) {
                //console.log('capturing lead');
                if (response == 'OK') {
                    $('#sc-payment-form', form).trigger('studiocart/orderform/lead_captured');
                    var form = document.getElementById(wrap_id);
                    form = form.getElementsByTagName('form')[0];

                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'sc-lead-captured');
                    hiddenInput.setAttribute('value', 1);
                    form.appendChild(hiddenInput);
                }
            });
            return true;
        }
        
        // check pay info
        var $btn,
            $form,
            clientSecret,
            intent_id,
            amount,
            customer_id,
            sc_temp_order_id,
            prod_id;
            
        if ($('.sc-stripe #card-element').length > 0) {
            // Create a Stripe client.
            var stripe = Stripe(stripe_key[0]);
            var card = {};
        }
        
        $('.sc-form-wrap').each(function(){
            
            let form_id = $(this).attr('id');
            let form = '#'+form_id;

            // toggle pay methods
            togglePayMethods(form);
    
            if (typeof sc_coupon !== 'undefined') {
                try_coupon(sc_coupon[0], form);
            } else {
                update_pwyw(form);
                update_form(form);
            }

            // check pay info
            var $btn = $('#sc_card_button', form);
                $form = $('#sc-payment-form', form);
    
            if ($('.sc-stripe #card-element', form).length > 0) {
                
                $('.sc-stripe #card-element', form).each(function(){
    
                    let form_wrapper = form_id;
                    // Create an instance of Elements.
                    var elements = stripe.elements();
					let $this = "#"+form_wrapper+" #card-element";
    
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
                    card[form_wrapper] = elements.create('card', { style: style });
    
                    // Add an instance of the card Element into the `card-element` <div>.
                    
                    card[form_wrapper].mount($this);
    
                    // Handle real-time validation errors from the card Element.
                    card[form_wrapper].addEventListener('change', function (event) {
                        var displayError = document.querySelector("#"+form_wrapper+" #card-errors");
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
    
                    // Reinitialize Stripe elements for forms inside Elementor popups
                    jQuery(document).on('elementor/popup/show', () => {
    
                        card[form_wrapper].unmount($this);
                        card[form_wrapper].mount($this);
    
                        // Handle real-time validation errors from the card Element.
                        card[form_wrapper].addEventListener('change', function (event) {
                            var displayError = document.querySelector("#"+form_wrapper+" #card-errors");
                            if (event.error) {
                                displayError.textContent = event.error.message;
                            } else {
                                displayError.textContent = '';
                            }
                        });

                        // 100ms delay for open popup animation
                        setTimeout(() => {
                            if (typeof sc_coupon !== 'undefined') {
                                try_coupon(sc_coupon[0], "#"+form_wrapper);
                            } else {
                                update_pwyw("#"+form_wrapper);
                                update_form(form_wrapper);
                            }
                        }, 100);
                    });
                });
            }
    
            function onloadCallback() {
                grecaptcha.ready(function() {
                    var gsitekey = $(".sc-grtoken").attr("data-sitekey");
                    grecaptcha.execute(gsitekey, { action: 'submit' }).then(function(token) {
                        $(".sc-grtoken", form).val(token);
                    });
                });
            }
    
            // Handle form submission.
            $(document).on('click', form + ' #sc_card_button', async function(event) {
    
                $btn = $(this);
                $btn.addClass('running').attr("disabled", true);
    
                event.preventDefault();
                var form_wrapper = $btn.data('form-wrapper');
                var errors = await sc_validate(form_wrapper);
                var form = '#'+form_wrapper;
    
                if (errors) {
                    alert(sc_translate_frontend.missing_required);
                    $btn.removeClass('running').removeAttr("disabled");
                    return false;
                } 
    
                if(jQuery(".g-recaptcha", form).length>0){
                    var gcaptchasize = jQuery(".g-recaptcha", form).attr("data-size");
                    if (gcaptchasize == "invisible") {
                        grecaptcha.ready(function() {
                            grecaptcha.execute();
                        });
                    }
                }
    
                if ($(".sc-grtoken", form).length > 0) {
                    grecaptcha.ready(function() {
                        var gsitekey = $(".sc-grtoken", form).attr("data-sitekey");
                        grecaptcha.execute(gsitekey, { action: 'submit' }).then(function(token) {
                            $(".sc-grtoken", form).val(token);
                        });
                    });
                }
    
                setTimeout(function() {
    
                    var is_subscription = $('.ob-sub:checked', form).length || ($('input[name="sc_product_option"]:checked', form).data('installments') && !$('.ob-replace:checked', form).length);
    
                    if ($('#sc-payment-form [name="pay-method"]', form).length > 0) {
                        if ($('[name="pay-method"]:checked', form).val() != 'stripe' && ($('[name="sc_amount"]', form).val() != 0 || is_subscription) && $('[name="pay-method"]:checked', form).val() != 'cod') {
                            $('#sc-payment-form', form).trigger('studiocart/orderform/submit');
                            return false;
                        }
                    }
    
                    var paramObj = $('#sc-payment-form', form).serializeArray();
    
                    // process free payment, manual payment methods
                    var manual = ($('#sc-payment-form [name="pay-method"]:checked', form).val() == 'cod'),
                        plan = $('input[name=sc_product_option]:checked', form);
    
                    if ($('[name="sc_amount"]', form).val() == 0 && !is_subscription || manual) {
                        paramObj.push(
                            { name: "action", value: "save_order_to_db" }, 
                            { name: "pay-method", value: "cod" } // change method to "COD", otherwise order will get stuck in "pending" status
                        );

                        $.post(studiocart.ajax, paramObj, function(res) {
                            var response = JSON.parse(res);
                            if ('undefined' !== typeof response.error) {
                                alert(response.error);
    
                                if ('undefined' !== typeof response.fields) {
                                    $.each(response.fields, function(i, item) {
                                        invalidate_field($('[name="' + item['field'] + '"]', form), item['message'])
                                    });
                                }
    
                                $btn = $('#sc_card_button', form);
                                $btn.removeClass('running').removeAttr("disabled");
                            } else {
                                if ('undefined' !== typeof response.redirect) {
                                    window.location.href = response.redirect;
                                } else {
                                    // var formEl = document.getElementById(form_wrapper).firstChild;

                                    if ('undefined' !== typeof response.formAction) {
                                        $form = $('#sc-payment-form', form);
                                        $form.attr('action', response.formAction);
                                    }
                                    var hiddenInput = document.createElement('input');
                                    hiddenInput.setAttribute('type', 'hidden');
                                    hiddenInput.setAttribute('name', 'sc_order_id');
                                    hiddenInput.setAttribute('value', response.order_id);
                                    $form.append(hiddenInput);
                                    $form.submit();
                                }
                            }
                        });
                        return false;
                    }
    
                    paramObj.push({name: "action", value: "create_payment_intent"});
    
                    if (!intent_id) {
    
                        // create new intent if none found
                        $.post(studiocart.ajax, paramObj, function(res) {
    
                            var response = JSON.parse(res);
                            if ('undefined' !== typeof response.error) {
                                alert(response.error);
                                if ('undefined' !== typeof response.fields) {
                                    $.each(response.fields, function(i, item) {
                                        invalidate_field($('[name="' + item['field'] + '"]', form), item['message'])
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
                                    prod_id = response.prod_id;
    
                                    if (is_subscription) {
                                        confirmSubscription(response, false, false, form_wrapper);
                                    } else {
                                        // create new order in db then send to stripe
                                        saveThenConfirm(response, form_wrapper);
                                    }
                                }
                            }
                        });
                    } else {
    
                        var intent = {
                            'clientSecret': clientSecret,
                            'intent_id': intent_id,
                            'customer_id': customer_id,
                            'amount': amount,
                            'prod_id':prod_id,
                            'sc_temp_order_id':sc_temp_order_id
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
                                    invoiceId,
                                    form_wrapper
                                );
                            } else {
                                confirmSubscription(intent, false, false, form_wrapper);
                            }
                        } else {
                            if ($('input[name=order_id]').length == 0) {
                                // create new order in db then send to stripe
                                saveThenConfirm(intent, form_wrapper);
                            } else {
                                if (!amount) {
                                    paramObj['action'] = 'update_payment_intent_amt';
                                    paramObj['intent_id'] = intent.intent_id;
                                    $.post(studiocart.ajax, paramObj, function(amt) {
                                        if (!isNaN(amt)) {
                                            amount = amt;
                                            intent.amount = amt;
                                        }
                                    });
                                }
    
                                // create new order in db then send to stripe
                                confirmCardPayment(intent, form_wrapper);
                            }
                        }
                    }
    
                }, 1000);
    
            });
        });

        $('.sc_cancel_sub').click(function() {

            if (confirm(sc_translate_frontend.confirm_cancel_sub)) {
                var ajaxurl = studiocart.ajax;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce = jQuery("#sc_nonce").val();
                var subscriber_id = (_this.data('item-id')) ? _this.data('item-id') : $("#sc_payment_intent").val();
                var payment_method = jQuery("#sc_payment_method").val();
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
                    if (response == 'OK') {
                        alert(sc_translate_frontend.sub_cancel); //custom message
                        location.reload();
                    } else {
                        alert(response);
                    }
                }).fail(function() {
                    alert(response.fail);
                });
            } else {
                return false;
            }
        });

        /**
         * Pause Start Subscription
         */
        $('.sc_pause_restart_sub').click(function(){

            var confirmMessage = sc_translate_frontend.confirm_pause_sub;
            var successMessage = sc_translate_frontend.sub_paused;
            var type = $(this).data('action');

            if(type == 'started'){
                confirmMessage = sc_translate_frontend.confirm_activate_sub;
                successMessage = sc_translate_frontend.sub_started;
            }
            
            if (confirm(confirmMessage)) { 
                var ajaxurl = studiocart.ajax;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce	= jQuery("#sc_nonce").val();
                var payment_method	= jQuery("#sc_payment_method").val();
                var prod_id = jQuery("#sc_product_id").val();
                
                var data = {
                    'action': 'sc_pause_restart_subscription',
                    'prod_id': prod_id,
                    'nonce': wp_nonce,
                    'id': id,
                    'payment_method': payment_method,
                    'type':type,
                };

                // We can also pass the url value separately from ajaxurl for front end AJAX implementations
                jQuery.post(ajaxurl, data, function(response) {
                    console.log(response);
                    if( response == 'OK' ){
                        alert(successMessage); //custom message
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

        function saveThenConfirm(intent, form_wrapper) {
            confirmCardPayment(intent, form_wrapper);
        }

        function createSubscription(customerId, paymentMethodId, invoiceId, wrap_id) {

            var f = document.getElementById(wrap_id);
            f = f.getElementsByTagName('form')[0];

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
                    if ('undefined' !== typeof result.formAction) {
                        f.setAttribute('action', result.formAction);
                    }

                    var hiddenInput = document.createElement('input');
                    hiddenInput.setAttribute('type', 'hidden');
                    hiddenInput.setAttribute('name', 'sc_order_id');
                    hiddenInput.setAttribute('value', result.sc_order_id);
                    f.appendChild(hiddenInput);

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
                .then(function() {
                    //console.log('result: ' + $('input[name=order_id]').val());
                    f.submit();
                })
                .catch((error) => {
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    displayError(error, wrap_id);
                })
            );
        }

        function retryInvoiceWithNewPaymentMethod(
            customerId,
            paymentMethodId,
            invoiceId,
            wrap_id
        ) {

            var f = document.getElementById(wrap_id);
            f = f.getElementsByTagName('form')[0];
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
                .then(function() {
                    //console.log('result: ' + $('input[name=order_id]').val());
                    $form.submit();
                })
                .catch((error) => {
                    // An error has happened. Display the failure to the user here.
                    // We utilize the HTML element we created.
                    displayError(error, wrap_id);
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
            let paymentIntent = invoice ?
                invoice.payment_intent :
                subscription.latest_invoice.payment_intent;

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

        function confirmSubscription(intent, isPaymentRetry = false, invoiceId = false, form_wrapper=false) {

            var customer_name = $('#first_name', '#'.form_wrapper).val() + ' ' + $('#last_name', '#'.form_wrapper).val(),
                customer_email = $('#email', '#'.form_wrapper).val();

            stripe.createPaymentMethod({
                type: 'card',
                card: card[form_wrapper],
                billing_details: {
                    name: customer_name,
                    email: customer_email,
                },
            })
                .then((result) => {
                    if (result.error) {
                        displayError(result, form_wrapper);
                    } else {
                        if (isPaymentRetry) {
                            // Update the payment method and retry invoice payment
                            retryInvoiceWithNewPaymentMethod(
                                intent.customer_id,
                                result.paymentMethod.id,
                                invoiceId,
                                form_wrapper
                            );
                        } else {
                            // Create the subscription
                            createSubscription(
                                intent.customer_id,
                                result.paymentMethod.id,
                                false,
                                form_wrapper
                            );
                        }
                    }
                });
        }

        function displayError(event, form_wrapper) {
            if (event.error) {
                alert(event.error.message);
            } else if (event) {
                alert(event);
            }
            $('#sc_card_button', '#'+form_wrapper).removeClass('running').removeAttr("disabled");

        }

        function confirmCardPayment(intent, wrap_id) {
            var customer_name = $('#first_name').val() + ' ' + $('#last_name').val(),
                customer_email = $('#email').val();
            stripe.confirmCardPayment(intent.clientSecret, {
                payment_method: {
                    card: card[wrap_id],
                    billing_details: {
                        name: customer_name,
                        email: customer_email,
                    },
                },
                setup_future_usage: 'off_session'
            }).then(function(result) {

                if (result.error) {
                    // Display error.message in your UI.
                    displayError(result, wrap_id);
                } else {
                    var paramObj = {};

                    $.each($('#sc-payment-form').serializeArray(), function(_, kv) {
                        if (kv.name != 'sc-orderbump[]') {
                            paramObj[kv.name] = kv.value;
                        }
                    });

                    $('input[name="sc-orderbump[]"]').each(function(key, value) {
                        if (this.checked) {
                            paramObj['sc-orderbump[' + key + ']'] = $(this).val();
                        }
                    });

                    if (intent.sc_temp_order_id) {
                        paramObj['sc_temp_order_id'] = intent.sc_temp_order_id;
                    }

                    paramObj['customer_id'] = intent.customer_id;
                    paramObj['intent_id'] = intent.intent_id;
                    paramObj['amount'] = intent.amount;
                    $.post(studiocart.ajax, paramObj, function(res) {
                        var response = JSON.parse(res);
                        if ('error' in response) {
                            alert(response.error);
                            if ('undefined' !== typeof response.fields) {
                                $.each(response.fields, function(i, item) {
                                    invalidate_field($('[name="' + item['field'] + '"]'), item['message'])
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
                                if (jQuery('input[name="sc-auto-login"]').length == 1) {
                                    data['sc-auto-login'] = 1;
                                }
                                $.post(studiocart.ajax, data, function(res) {
                                    //if (!isNaN(order_id)) {
                                    // Insert the token ID into the form so it gets submitted to the server
                                    var response = JSON.parse(res);
                                    var form = document.getElementById(wrap_id);
                                    form = form.getElementsByTagName('form')[0];
                                    if ('undefined' !== typeof response.formAction) {
                                        form.setAttribute('action', response.formAction);
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
                                    form.submit();
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
            $(document).on('studiocart/upsell/ready/accept_link', '.sc-accept-upsell-link', function() {
                $('a[href*="sc-upsell-offer=yes"],.sc-accept-upsell-link').attr('href', '#');
            });

            $(document).on('studiocart/upsell/ready/decline_link', '.sc-decline-upsell-link', function() {
                $('a[href*="sc-upsell-offer=no"],.sc-decline-upsell-link').attr('href', studiocart.upsell_decline + '&sc-pp=1');
            });
        }

        $(document).on('studiocart/upsell/accept/paypal', '.sc-accept-upsell-link', function(event) {
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

            $.post(studiocart.ajax, us_data, function(res) {
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
        $(document).on('studiocart/orderform/submit', '#sc-payment-form', function(event) {

            var form = '#' + $(this).parent().attr('id');
            if ($('.pay-methods', form).length > 0 && $('[name="pay-method"]:checked', form).val() != 'paypal') {
                return false;
            }
            var paramObj = $('#sc-payment-form', form).serializeArray();

            paramObj.push(
                { name: "sc_page_id", value: studiocart.page_id }, 
                { name: "action", value: "sc_paypal_request" },
                { name: "cancel_url", value: window.location.href } 
            );

            //var is_subscription = $('input[name="sc_product_option"]:checked').data('installments');
            $.post(studiocart.ajax, paramObj, function(res) {
                var response = JSON.parse(res);
                if ('undefined' !== typeof response.error) {
                    alert(response.error);
                    $('#sc_card_button', form).removeClass('running').removeAttr("disabled");
                } else {
                    window.location.href = response.url;
                }
            });
        });
    });

    /**
     * My Account page tabs nested
     */
     jQuery('.ncs-nav-tabs li a').click(function() {
        var tab_id = jQuery(this).attr('href');

        jQuery('.ncs-nav-tabs li a').removeClass('active');
        jQuery('.tabcontent').removeClass('active');

        jQuery(this).addClass('active');
        jQuery("#" + tab_id).addClass('active');
    })

    /**
     * Tabs left aligned on my account page
     */
    jQuery('ul.tabs-left li.tablinks').click(function() {
        var tab_id = jQuery(this).attr('data-tab');

        jQuery('ul.tabs-left li.tablinks').removeClass('active');
        jQuery('.tabcontent').removeClass('active');

        jQuery(this).addClass('active');
        jQuery("#" + tab_id).addClass('active');
    })

    jQuery('.ncs-nav-tabs li a').click(function(event) {
        event.preventDefault();
        var parents = jQuery(this).parents('.ncs-account-list');
        parents.find('.ncs-nav-tabs li a').removeClass('active');
        jQuery(this).addClass('active');
        return false;
    });

    /** Edit profile in my account */

    jQuery("#editProfile").click(function(){
        jQuery(".ep_disabled").prop('disabled',false);
        jQuery(".ep_disabled").addClass('enable-input');
        jQuery("#editProfileCancel, #all-subscription-address-wrap").show();
        jQuery("#newPasswordDiv, #confirmNewPasswordDiv").show();
        jQuery(this).hide();
        jQuery("#updateProfile").show();
    });

    jQuery("#editProfileCancel").click(function(){
        cancelEditProfile();
    });

    jQuery("#updateProfile").click(function(){
        jQuery("#sc-loader").show();
        jQuery(this).prop('disabled',true);
        var formData = jQuery('#updateProfileForm').serialize();

        let paramObj = {
            'action': 'update_user_profile',
            'form_data':formData
        };

        $.post(studiocart.ajax, paramObj, function (res) {
            if ('undefined' !== typeof res.error) {
                jQuery("#p-alert").text(res.error).addClass('alert-error');
            } else {
                jQuery("#p-alert").removeClass('alert-error');
                jQuery("#p-alert").text(res.message).addClass('alert-success');
                cancelEditProfile();
            }
            jQuery("#updateProfile").prop('disabled',false);
            jQuery("#sc-loader").hide();
        });
        
    });

    function cancelEditProfile(){
        jQuery(".ep_disabled").removeClass('enable-input');
        jQuery(".ep_disabled").prop('disabled',true);
        jQuery("#editProfileCancel, #all-subscription-address-wrap").hide();
        jQuery("#editProfile").show();
        jQuery("#newPasswordDiv, #confirmNewPasswordDiv").hide();
        jQuery("#updateProfile").hide();
    }

})(jQuery);