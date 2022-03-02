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
    
    $('.default_field_disabled').each(function(){
        if($(this).is(':checked')) {
            $(this).closest('.repeater').addClass('disabled');
        } else {
            $(this).closest('.repeater').removeClass('disabled');
        }
    });
    
    $('.default_field_disabled').change(function(){
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
    $("#repeater_sc_coupons [name^=\"coupon_type[\"]").each(function(index){
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
    $("#repeater_sc_coupons [name^=\"coupon_type[\"]").on("change", function(){
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
        $(this).closest('.wrap-field').find('label').append('<span class="req">*</span>')
    });
    
    $('.sc-tab').eq(0).show();
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
    
    jQuery('#_sc_currency, .form-table #_sc_country, #_sc_menu_icon').selectize({
        create: true,
        sortField: 'text'
    }); 
    
    $('.select2').each(function(){
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
		var $editFields = $('#normal-sortables').hide();

        $('#edit-order').click(function(){
            
            // show payment options for selected product
            find_pay_options();
            
			$editFields.show();
			$('.edit-hide').hide();
            
			return false;
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


    $(document).on('change','#_sc_vat_enable',function(){
        if(this.checked){
            $('#_sc_vat_merchant_state').parents('tr').fadeIn(100);
            $('#_sc_vat_all_eu_businesses').parents('tr').fadeIn(200);
            $('#_sc_vat_disable_vies_database_lookup').parents('tr').fadeIn(300);
        }else{
            $('#_sc_vat_merchant_state').parents('tr').fadeOut(300);
            $('#_sc_vat_all_eu_businesses').parents('tr').fadeOut(200);
            $('#_sc_vat_disable_vies_database_lookup').parents('tr').fadeOut(100);
        }
    });

    $('#_sc_vat_enable').trigger('change');

});


