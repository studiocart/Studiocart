(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
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
	 
		

})( jQuery );

function ncs_settings(id){
	jQuery(".tab-content").hide();
	jQuery('.settings_tab').removeClass('nav-tab-active');
	jQuery("#content_tab_"+id).slideDown();
    jQuery("#settings_tab_"+id).addClass('nav-tab-active');
}

jQuery(document).on('change','.price',function(){
    let re = /^[0-9.,]+$/;
    let price = jQuery(this).val();
    if (re.test(price)) {
        
    } else {
        jQuery(this).val("");
    }
});
jQuery(document).ready(function($){
    jQuery('#content_tab_emails h2:not(:first)').addClass('email_title_trigger');
    jQuery('.email_title_trigger').next('table').hide();
    jQuery("#_sc_product_fresh_setup a").click(function(){
        var post_id     = $(this).data('id'),
            ajax_url 	= sc_reg_vars.sc_ajax_url,
            wp_nonce	= sc_reg_vars.nonce;
            
        var data = {
            'action': 'sc_fresh_product',
            'nonce': wp_nonce,
            'post_id': post_id
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            //console.log(response);
            if( response =='success'){
                jQuery("#_sc_product_fresh_setup").remove();
            }
        }).fail(function() {
            alert(sc_translate_backend.try_again);
        });
    });
    
    $('#sc-email-type').change(function(){
        var email = $(this).val(),
            link = $('#sc-preview-email').attr('href').replace(/\[[a-z]+\]/, '['+email+']');
        $('#sc-preview-email').attr('href',link);        
    });
    
    $("#sc-email-send").click(function(){
        var wp_nonce    = sc_reg_vars.nonce,
            ajax_url    = sc_reg_vars.sc_ajax_url,
            type        = $('#sc-email-type').val();
            
        var data = {
            'action': 'sc_send_email_test',
            'nonce': wp_nonce,
            'type': type
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            //console.log(response);
            if(response){
                alert(response);
            }
        }).fail(function() {
            alert(sc_translate_backend.try_again);
        });
        return false;
    });
    
    function addTextIntoEditor(myText){
        tinymce.activeEditor.execCommand('mceInsertContent', false, myText);
    }
    
    $('.sc-insert-merge-tag').change(function(){
        if($(this).val() == '') {
            return;
        }
        
        var text = '{'+$(this).val()+'}';
        addTextIntoEditor(text);
        $(this).val('');
    });
    
    $('.ridoption_id input, .ridfield_id input, .ridurl_slug input').keyup(function(){
        var val = $(this).val().replace(/\s+/g, '-');
        $(this).val(val);
    });
    
    $('.ridfield_id input').keyup(function(){
        var val = $(this).val().replace(/[^a-zA-Z0-9\-_]/g, "").toLowerCase();
        $(this).val(val);
    });
    
    $('.default_field_disabled, .file_hide').each(function(){
        if($(this).is(':checked')) {
            $(this).closest('.repeater').addClass('disabled');
        } else {
            $(this).closest('.repeater').removeClass('disabled');
        }
    });
    
    $('.default_field_disabled, .file_hide').change(function(){
        if($(this).is(':checked')) {
            $(this).closest('.repeater').addClass('disabled');
        } else {
            $(this).closest('.repeater').removeClass('disabled');
        }
    });
	
    if(jQuery(".settings-options-form table").length) {
        var hash = window.location.hash;
        if(hash) {
            hash = hash.replace('#','');
            ncs_settings(hash);
        } else {
            ncs_settings('general');
        }
    }
    jQuery('.email_title_trigger').on('click',function(){
        if(!jQuery(this).hasClass('active')){
            jQuery('.email_title_trigger').removeClass('active');
            jQuery('.email_title_trigger').next('table').slideUp('slow');
        }
        jQuery(this).toggleClass('active');
        jQuery(this).next('table').slideToggle('slow');
    });
    $( '.repeaters .btn-edit' ).click();
    
    $('.sc-color-field').wpColorPicker();
    flatpickr('.datepicker', {enableTime: true,dateFormat: "Y-m-d h:i K",allowInput: true});
    
    // hide amount recurring field if coupon type = fixed
    $("#repeater_sc_coupons [name^=\"_sc_coupons[type][\"]").each(function(index){
        if ( $(this).val() == "fixed" ) { 
            $(this).closest(".repeater-content").find(".ridamount_recurring").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400); 
        } else { 
            $(this).closest(".repeater-content").find(".ridamount_recurring").hide() 
        }
        
        if ( $(this).val() == "fixed" || $(this).val() == "percent" ) {
            $(this).closest(".repeater-content").find(".ridduration").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400) 
        } else { 
            $(this).closest(".repeater-content").find(".ridduration").hide() 
        }
    });
    $("#repeater_sc_coupons [name^=\"_sc_coupons[type][\"]").on("change", function(){
        if ( $(this).val() == "fixed" ) { 
            $(this).closest(".repeater-content").find(".ridamount_recurring").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400); 
        } else { 
            $(this).closest(".repeater-content").find(".ridamount_recurring").hide() 
        }
        
        if ( $(this).val() == "fixed" || $(this).val() == "percent" ) {
            $(this).closest(".repeater-content").find(".ridduration").css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400) 
        } else { 
            $(this).closest(".repeater-content").find(".ridduration").hide() 
        }
    });      
    
    $('.datepicker').change(function(){
        if($(this).val()) {
            $(this).next('.clear-date').show();
        } else {
            $(this).next('.clear-date').hide();
        }
    }).each(function(){
        if($(this).val()) {
            $(this).next('.clear-date').show();
        } else {
            $(this).next('.clear-date').hide();
        }
    });
    
    $('.datepicker + .clear-date').click(function(){
        $(this).hide().closest('.field-text').find('.datepicker').val('');
        return false;
    });
    
    $('.sc-settings-tabs .required').each(function(){
        $(this).closest('.wrap-field, .sc-field.sc-row ').find('label').append('<span class="req">*</span>')
    });
    
    $('.sc-tab').eq(0).css({'display':'flex',opacity: 1});
    $('.sc-tab-nav a').click(function(){
        
        var errors = false;
        $('.sc-settings-tabs .required:visible').each(function(){
            if ($(this).val() == '') {
                $(this).addClass('error');
                errors = true;
            }
        });
        
        if (errors) {
            alert('Required fields missing');
            return false;
        }
    
        $('.sc-tab-nav, .sc-tab').removeClass('active');
        $('.sc-tab').hide();
        $(this).parent().addClass('active');
        $($(this).attr('href')).css({opacity: 0, display: "flex"}).animate({opacity: 1}, 400);
        return false;
    });
    
    // remove error message on keyup
    $('.sc-settings-tabs').on('keyup', '.required', function(){
        if ($(this).val() != '') {
            $(this).removeClass('error')
        }
    });
    $('.sc-settings-tabs').on('change', '.required', function(){
        if ($(this).val() != '') {
            $(this).removeClass('error')
        }
    });
    
    jQuery('#_sc_currency, .form-table #_sc_country, #_sc_menu_icon').selectize({
        create: true,
        sortField: 'text'
    }); 
    
    $('.sc-selectize').each(function(){
        $(this).find('option[value=""]').remove();
        var hidden = $(this).closest('.repeater.hidden');
        if(hidden.length < 1) {
            var def = $(this).data('placeholder');
            if($(this).hasClass('multiple')) {
                $(this).selectize({plugins: ['remove_button'],allowEmptyOption: true, placeholder: def});
            } else {
                $(this).selectize({allowEmptyOption: true, placeholder: def});
            }
        }
    });
    
    // mailchimp dropdowns
    if('undefined' !== typeof sc_mc_tags) {
        $('.mail_chimp_list_name').each(function(){
            var listId = $(this).val(),
                tags = sc_mc_tags[listId],
                $tag_options = $(this).closest('.repeater').find('.mail_chimp_list_tags option'),
                $group_options = $(this).closest('.repeater').find('.mail_chimp_list_groups option');

            $tag_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (tags === undefined) return;
                var tag_id = $(this).attr('value');
                if(tag_id in tags){
                    $(this).show();
                }
            });

            if('undefined' !== typeof sc_mc_groups) { 
				var groups = sc_mc_groups[listId];
				$group_options.each(function(index){
					if ($(this).val()=='') return;

					$(this).hide();

					if (groups === undefined) return;
					var group_id = $(this).attr('value');
					if(group_id in groups){
						$(this).show();
					}
				});
			}

        });

        $('.mail_chimp_list_name').change(function(){
            var listId = $(this).val(),
                tags = sc_mc_tags[listId],
                groups = sc_mc_groups[listId],
                $tag_options = $(this).closest('.repeater').find('.mail_chimp_list_tags option'),
                $group_options = $(this).closest('.repeater').find('.mail_chimp_list_groups option');

            $tag_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (tags === undefined) return;
                var tag_id = $(this).attr('value');
                if(tag_id in tags){
                    $(this).show();
                }
            });

            $group_options.each(function(index){
                if ($(this).val()=='') return;

                $(this).hide();

                if (groups === undefined) return;
                var group_id = $(this).attr('value');
                if(group_id in groups){
                    $(this).show();
                }
            });

        });
    }

    var search_sc_user = null;
    jQuery(document).on('keyup', '.sc-user-search-custom', function(){

        let term = jQuery(this).find('input').val();
    
        let selectize_cont = jQuery(this).prev('select');
    
        let data = {
            'action': 'sc_json_search_user',
            'term':term,
            //'security': sc_woo_inti.search_products_nonce,
        };
    
        search_sc_user = jQuery.ajax({
            type: 'GET',
            data: data,
            url: sc_reg_vars.sc_ajax_url,
            beforeSend : function()    {           
                if(search_sc_user != null) {
                    search_sc_user.abort();
                }
            },
            success: function(response) {
                let $select = selectize_cont.selectize();
                let selectize = $select[0].selectize;
                selectize.clearOptions();
                let terms = [];
                if ( response ) {
                    jQuery.each( response, function( id, text ) {
                        terms.push( { id: id, text: text } );
                        selectize.addOption({text: text, value: id});
                        selectize.refreshOptions();
                    });
                }
            },
            error:function(e){
              // Error
            }
        });
    });

	$('#sc_refund_items_btn').click(function(){	
		$(".refund_amount_tr").show();	
		return false;	
	});	
	$('#sc_cancel_refund_btn').click(function(){	
		$(".refund_amount_tr").hide();	
		return false;	
	});
	
	// show edit order fields
	if ( $('#edit-order').length > 0 ) {
		var $editFields = $('#normal-sortables, #edit-disabled').hide();

        $('#edit-order').click(function(){
            
            // show payment options for selected product
            find_pay_options();
            
			$editFields.show();
			$('.edit-hide').hide();
            
			return false;
		});
	}
    
    if($('.repeater-content .update-plan').length) {
        $('.repeater-content .update-plan').each(function(){
            var val = $(this).attr('class').split(" ");
            $.each(val, function (k,e) {
                if(e.startsWith("ob-")) {
                    val = e.replace('ob-', '');
                }
                $(this).val(val);
                return;
            });
        });
    }
    
    $('.update-plan-product').on('change', function(){
        var post_id     = $(this).closest('.sc-tab, .repeater').find('.update-plan-product').eq(0).val(),
            $select     = $(this).closest('.sc-tab, .repeater').find('.update-plan').eq(0),
            ajax_url 	= sc_reg_vars.sc_ajax_url,
            wp_nonce	= sc_reg_vars.nonce,
            selected    = $(this).val();

        $select.html('<option>...</option>');
        
        var data = {
            'action': 'sc_product_plans',
            'nonce': wp_nonce,
            'post_id': post_id,
            'selected' : selected
        };

        if($(this).hasClass('recurring')) {
            data.type = 'recurring'
        }

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            console.log(response);
            if( response ){
                $select.html(response);
            }
        }).fail(function() {
            alert(sc_translate_backend.try_again);
        });
    });
    
	//.sc_refund_btn 
	//REFUND
	$(document).on('click', '.sc_refund_btn', function(e){

		e.preventDefault();
		var _this 		= $(this);		
		var ajax_url 	= sc_reg_vars.sc_ajax_url;
		var wp_nonce	= sc_reg_vars.nonce;
		var ordertId	= $("#post_ID").val();
        var amount      = $("#sc_refund_amount").val();
        if ($('#sc_restock_refunded').is(':checked')) {
		 	var restock = 'YSE';
		} else {
		  	var restock = 'No';
		}
        
		var anchor_text = _this.text();
		
		//console.log( ajax_url );
		if ( confirm(sc_translate_backend.process_refund) == true ) {
			_this.html("<strong> " + sc_translate_backend.wait + "</strong>");
			_this.addClass('disabled');
			
			var data = {
				'action': 'sc_order_refund',
				'nonce': wp_nonce,
				'id': ordertId,
				'refund_amount': amount,
				'restock': restock,
			};
			
			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ajax_url, data, function(response) {
				console.log(response);
				if( response == 'OK' ){
					alert(sc_translate_backend.refund_success); //custom message
					location.reload();
				}else{
					_this.html(anchor_text);
					alert(response);
					_this.removeClass('disabled');
				}
			}).fail(function() {
				_this.html(anchor_text);
				alert(sc_translate_backend.try_again);
				_this.removeClass('disabled');
			});
		}
	});
    
    /**
         * Pause Start Subscription
         */
        $('.sc_pause_restart').click(function(){

            var confirmMessage = sc_translate_backend.confirm_pause_sub;
            var successMessage = sc_translate_backend.sub_paused;
            var type = $(this).data('action');

            if(type == 'started'){
                confirmMessage = sc_translate_backend.confirm_activate_sub;
                successMessage = sc_translate_backend.sub_started;
            }

            if (confirm(confirmMessage)) { 
                var ajaxurl = sc_reg_vars.sc_ajax_url;
                var _this = $(this);
                var id = _this.data('id');
                var wp_nonce	= sc_reg_vars.nonce;
                var payment_method	= $("#sc_payment_method").val();

                var data = {
                    'action': 'sc_pause_restart_subscription',
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
	
	//.sc_unsubscribe_btn
	//UNSUBSCRIBE
	$(document).on('click', '.sc_unsubscribe_btn', function(e){
		e.preventDefault();
		
		var _this = $(this);
		var ajax_url 	= sc_reg_vars.sc_ajax_url;
		var wp_nonce	= sc_reg_vars.nonce;
		var ordertId	= $("#post_ID").val();
		var anchor_text = _this.text();
		var stripe_subscriber_id =  $("#stripe_sc_payment_intent").val();
		var payment_method	= $("#sc_payment_method").val();
		
		var prod_id = $("input[name='_sc_product_id']").val();
		//var stripe_subscriber_id = $("#stripe-id").text();
		//if charge id does not exists
		//alert(stripe_subscriber_id);
		if( stripe_subscriber_id == "" || stripe_subscriber_id == undefined ){
			alert(sc_translate_backend.invalid_sub_id);
			return false;
		}
		
		//console.log( ajax_url );
		if ( confirm(sc_translate_backend.confirm_cancel_sub) == true ) {
			_this.html("<strong> " + sc_translate_backend.wait + "</strong>");
			_this.addClass('disabled');
			
			var data = {
				'action': 'sc_unsubscribe_customer',
				'prod_id': prod_id,
				'subscription_id': stripe_subscriber_id,
				'nonce': wp_nonce,
				'id': ordertId,
				'payment_method': payment_method,
			};
			
			// We can also pass the url value separately from ajaxurl for front end AJAX implementations
			jQuery.post(ajax_url, data, function(response) {
				console.log(response);
				if( response == 'OK' ){
					alert(sc_translate_backend.sub_cancel); //custom message
					location.reload();
				}else{
					_this.html(anchor_text);
					alert(response);
					_this.removeClass('disabled');
				}
			}).fail(function() {
				_this.html(anchor_text);
				alert(sc_translate_backend.try_again);
				_this.removeClass('disabled');
			});
		}
	});
	
	//PAYMENT OPTIONS
    
	//dropdown product change
    $(document).on('change', '#_sc_product_id', function(){
        find_pay_options();
    });
    
    var find_pay_options = function(){
        var _pay_option_div = $("#rid_sc_item_name"),
            _pay_option = $("#_sc_item_name"),
            _this 		= $('#_sc_product_id'),
            _selected 	= _this.find("option:selected"),
            _selected_val  = _selected.val(),
            _selected_option  = _pay_option.val();
		
		if( _selected_val == undefined ||  _selected_val == null || _selected_val == '' || _selected_val < 1 ){
			return;
		}
		
		console.log( _selected_val );
		//AJAX REQUEST TO GET PAYMENT OPTIONS
		var ajax_url 	= sc_reg_vars.sc_ajax_url;
		var wp_nonce	= sc_reg_vars.nonce;
		var data = {
			'action': 'sc_get_payment_options',
			'nonce': wp_nonce,
			'productId': _selected_val,
		};
		
		// We can also pass the url value separately from ajaxurl for front end AJAX implementations
		jQuery.post(ajax_url, data, function(response) {
			console.log(response);
			if( response == 'error' ){
				_this.val('');
				_pay_option.val(0);
				alert(sc_translate_backend.something_went_wrong);
			}else if( response == 'no_data' || response == '' ){
				_pay_option.val(0);
			}else{
				_pay_option.html(response.data);
                if( _pay_option.find('option[value="'+_selected_option+'"]').length > 0 )
                    _pay_option.val(_selected_option);
			}
		}).fail(function() {
			_pay_option.val(0);
			alert(sc_translate_backend.try_again);
		});
	}
    
    
    $(document).on('click', '.sc-renew-lists', function(e){
		e.preventDefault();
        
        var _this 		= $(this);
		var ajax_url 	= sc_reg_vars.sc_ajax_url;
		var wp_nonce	= sc_reg_vars.nonce;
		var anchor_text = _this.text();
        
        _this.html("<strong>" + sc_translate_backend.wait + "</strong>");
        _this.addClass('disabled');

        var data = {
            'action': 'sc_renew_integrations_lists',
            'nonce': wp_nonce,
        };

        // We can also pass the url value separately from ajaxurl for front end AJAX implementations
        jQuery.post(ajax_url, data, function(response) {
            console.log(response);
            if( response == 'OK' ){
                alert(sc_translate_backend.list_renewed); //custom message
                location.reload();
            }else{
                _this.html(anchor_text);
                alert(response);
                _this.removeClass('disabled');
            }
        }).fail(function() {
            _this.html(anchor_text);
            alert(sc_translate_backend.try_again);
            _this.removeClass('disabled');
        });
	});
	
	
	function isEmpty(str) {
		return (!str || 0 === str.length || undefined === str);
	}
    
    // coupon symbol toggle on amount off input
    $("[name^=\"_sc_coupons[type][\"]").each(function(){
        var $symbol = $(this).closest('.repeater-content').find('.input-prepend,.input-append').eq(0);
        $(this).data('symbol', $symbol.text());
        $(this).data('class', $symbol.attr('class'));
        
        if($(this).val().includes("percent")) {
            $symbol.text('%').attr('class', 'input-append').next('input').addClass('right-currency');
        }
    });
    
    $(document).on('change', "[name^=\"_sc_coupons[type][\"]", function(){
        var $symbol = $(this).closest('.repeater-content').find('.input-prepend,.input-append').eq(0);
        if($(this).val().includes("percent")) {
            $symbol.text('%').attr('class', 'input-append').next('input').addClass('right-currency');
        } else {
            $symbol.text($(this).data('symbol')).attr('class', $(this).data('class'));
            if($(this).data('class') == 'input-prepend') {
                $symbol.next('input').removeClass('right-currency');
            }
        }
    });
    
    $('input[type=checkbox][name^=_sc_upsell],input[type=checkbox][name^=_sc_downsell]').each(function(){
        if(!$(this).is(':checked')) {
            $(this).closest('.sc-tab').find('.sc-field').hide();
            $(this).closest('.sc-tab').find('.sc-field:first-child').show();
        }
    });
    $('input[type=checkbox][name^=_sc_upsell],input[type=checkbox][name^=_sc_downsell]').change(function(){
        if(!$(this).is(':checked')) {
            $(this).closest('.sc-tab').find('.sc-field:not(:first-child)').fadeOut();
            $(this).closest('.sc-tab').find('.sc-field:first-child').show();
        } else {
            $(this).closest('.sc-tab').find('.sc-field').fadeIn();
            var type = $(this).closest('.sc-tab').find('input[name^=_sc_us_prod_type],input[name^=_sc_ds_prod_type]').eq(0);
            if(type.is(':checked')) {
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').hide();
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').show();
            } else {
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').show();
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').hide();
            }
        }
    });

    $('input[name^=_sc_us_prod_type],input[name^=_sc_ds_prod_type]').each(function(){
        var enabled = $(this).closest('.sc-tab').find('input[type=checkbox][name^=_sc_upsell],input[type=checkbox][name^=_sc_downsell]').is(':checked');
        if($(this).is(':checked')) {
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').hide();
            if(enabled) {
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').show();
            }
        } else {
            if(enabled) {
                $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').show();
            }
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').hide();
        }
    });

    $('input[name^=_sc_us_prod_type],input[name^=_sc_ds_prod_type]').change(function(){
        if($(this).is(':checked')) {
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').hide();
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').show();
        } else {
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_price], [id^=rid_sc_us_price]').show();
            $(this).closest('.sc-tab').find('[id^=rid_sc_ds_plan], [id^=rid_sc_us_plan]').hide();
        }
    });

    $('.ridbump,.ridupsell').hide();
    $('.cinput-action').change(function(){
        var $row = $(this).closest('.wrap-fields');
        if($(this).val()=='') {
            $row.find('.wrap-field').not('.ridaction').hide();
        } else {
            $row.find('.ridproduct_type').show();
            $row.find('.cinput-product_type').each(function(){
                if(!$(this).is(":hidden")){
                    var $parent = $(this).closest('.condition'),
                        $plans = $parent.find('.ridplan'),
                        $bumps = $parent.find('.ridbump'),
                        $upsells = $parent.find('.ridupsell');

                    if($(this).val()=='plan') {
                        $bumps.hide();
                        $upsells.hide();
                        $plans.fadeIn(300);
                    } else if($(this).val()=='bump') {
                        $bumps.fadeIn(300);
                        $upsells.hide();
                        $plans.hide();
                    } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
                        $bumps.hide();
                        $upsells.fadeIn(300);
                        $plans.hide();
                    } else {
                        $bumps.hide();
                        $upsells.hide();
                        $plans.hide();
                    }
                }
            });
        }
    });
    
    // Conditional confirmations
    
    $('.condition-content .cinput-product_type').each(function(){
        var $parent = $(this).closest('.condition'),
            $plans = $parent.find('.ridplan'),
            $bumps = $parent.find('.ridbump'),
            $upsells = $parent.find('.ridupsell');

        if($(this).val()=='plan') {
            $bumps.hide();
            $upsells.hide();
            $plans.fadeIn(300);
        } else if($(this).val()=='bump') {
            $bumps.fadeIn(300);
            $upsells.hide();
            $plans.hide();
        } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
            $bumps.hide();
            $upsells.fadeIn(300);
            $plans.hide();
        } else {
            $bumps.hide();
            $upsells.hide();
            $plans.hide();
        }
    });
    
    $(document).on('change', '.condition-content .cinput-product_type', function(){
        var $parent = $(this).closest('.condition'),
            $plans = $parent.find('.ridplan'),
            $bumps = $parent.find('.ridbump'),
            $upsells = $parent.find('.ridupsell');
        
        if($(this).val()=='plan') {
            $bumps.hide();
            $upsells.hide();
            $plans.fadeIn(300);
        } else if($(this).val()=='bump') {
            $bumps.fadeIn(300);
            $upsells.hide();
            $plans.hide();
        } else if($(this).val()=='upsell' || $(this).val()=='downsell') {
            $bumps.hide();
            $upsells.fadeIn(300);
            $plans.hide();
        } else {
            $bumps.hide();
            $upsells.hide();
            $plans.hide();
        }
    });
    
    $('.ridcfield_value input').each(function(){
        var value = $(this).closest('.condition-content').find('.ridcfield select').val();
        var descriptions = $(this).next('.description').find('span');
        descriptions.hide();
                
        if(value=='country') {
            $(this).parent().find('.description .country').show();
        } else if(value=='state') {
            $(this).parent().find('.description .state').show();           
        }
    });
    $(document).on('change', '.ridcfield select', function(){
        var value = $(this).val();
        var descriptions = $(this).closest('.condition-content').find('.ridcfield_value .description span');
        descriptions.hide();
                
        if(value=='country' || value=='state') {
            $(this).closest('.condition-content').find('.ridcfield_value .description span.'+value).show();
        }
    });
    
    $('select.condition-type').each(function(){
        var rows = $(this).closest('.repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='and') {
            rows.removeClass('condition-type-or').addClass('condition-type-and');
        } else {
            rows.removeClass('condition-type-and').addClass('condition-type-or');            
        }
    });
    $(document).on('change', '.condition-type', function(){
        var rows = $(this).closest('.repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='and') {
            rows.removeClass('condition-type-or').addClass('condition-type-and').fadeIn();
        } else {
            rows.removeClass('condition-type-and').addClass('condition-type-or').fadeIn();            
        }
    });
    
    $('.cinput-action').each(function(){
        var rows = $(this).closest('.repeater-content').find('.conditions .wrap-fields');
        if($(this).val()=='') {
            $(this).closest('.wrap-fields').find('.wrap-field').not('.ridaction').hide();
        }
    });
    
    $('.condition-content .cinput-action').each(function(){
        var $parent = $(this).closest('.condition'),
            $fields = $parent.find('[class^=ridcfield]'),
            $plans = $parent.find('.ridplan'),
            $type = $parent.find('.ridproduct_type');
        if($(this).val()=='field-value') {
            $type.hide().find('.cinput-product_type').val('plan');
            $fields.show();
            $plans.hide();
        } else {
            $fields.hide();
            $type.show();
        }
    });
    
    $(document).on('change', '.condition-content .cinput-action', function(){    
        var $parent = $(this).closest('.condition'),
            $fields = $parent.find('[class^=ridcfield]'),
            $plans = $parent.find('.ridplan'),
            $type = $parent.find('.ridproduct_type');
        if($(this).val()=='field-value') {
            $type.hide().find('.cinput-product_type').val('plan').trigger('change');
            $fields.show();
            $plans.hide();
        } else {
            $fields.hide();
            $type.show();
        }
    });

    $(document).on('change','#_sc_vat_enable',function(){
        if(this.checked){
            $('#_sc_vat_reverse_charge').parents('tr').fadeIn(100);
            $('#_sc_vat_merchant_state').parents('tr').fadeIn(100);
            $('#_sc_vat_all_eu_businesses').parents('tr').fadeIn(200);
            $('#_sc_vat_disable_vies_database_lookup').parents('tr').fadeIn(300);
        }else{
            $('#_sc_vat_reverse_charge').parents('tr').fadeOut(300);
            $('#_sc_vat_merchant_state').parents('tr').fadeOut(300);
            $('#_sc_vat_all_eu_businesses').parents('tr').fadeOut(200);
            $('#_sc_vat_disable_vies_database_lookup').parents('tr').fadeOut(100);
        }
    });

    $('#_sc_vat_enable').trigger('change');

});


