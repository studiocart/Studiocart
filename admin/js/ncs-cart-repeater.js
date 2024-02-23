/**
 * Repeaters
 */
(function( $ ) {
	'use strict';

	/**
	 * Clones the hidden field to add another repeater.
	 */
	$('.add-repeater').on( 'click', function( e ) {

		e.preventDefault();
        
        var parent = $(this).closest('.repeaters');
        var hidden = parent.find('.repeater.hidden');

		var clone = $('.repeater.hidden', parent).clone(true);

		clone.removeClass('hidden').children('.btn-edit').click();
		clone.insertBefore(hidden);
		clone.find('.btn-edit').click();
		clone.find('.sc-unique').val(uniqid());
		
        updateNames(parent);
        initializeSelect2(clone.find('.sc-selectize'));
        flatpickr(clone.find('.datepicker'), {enableTime: true,dateFormat: "Y-m-d h:i K",allowInput: true});

		var html = '<input class="sc-color-field wp-color-picker" id="_sc_bump_bg_color" name="_sc_bump_bg_color" placeholder="" type="text" autocomplete="new-password" autocorrect="off" autocapitalize="none" value="" data-lpignore="true">';
		clone.find('.wp-picker-container').replaceWith(html)
		clone.find('.sc-color-field').wpColorPicker(); 
		return false;

	});
    
	$('.add-condition').on( 'click', function( e ) {

		e.preventDefault();
        
        var parent = $(this).closest('.conditions');
        var hidden = parent.find('.condition.hidden');

		var clone = $('.condition.hidden', parent).clone(true);
		clone.removeClass('hidden');
		clone.insertBefore(hidden);
        updateConditionNames(parent);
        initializeSelect2(clone.find('.sc-selectize'));
		parent.find('.remove-condition').show();

		return false;

	});
    
    $('.remove-condition').on('click', function() {

		var parents = $(this).parents('li.condition');
        var list = parents.closest('.conditions');
		var children = list.find('li.condition');

		if ( children.length > 2 ) {
			parents.remove();
			updateConditionNames(list);
		} 

		children = list.find('li.condition');
		if ( children.length == 2 ) {
			list.find('.remove-condition').hide();
		}

		return false;

	});

	function uniqid(prefix = "", random = false) {
		const sec = Date.now() * 1000 + Math.random() * 1000;
		const id = sec.toString(16).replace(/\./g, "").padEnd(14, "0");
		return `${prefix}${id}${random ? `.${Math.trunc(Math.random() * 100000000)}`:""}`;
	};
    
    function initializeSelect2(selectElementObj) {
        selectElementObj.selectize({plugins: ['remove_button'],allowEmptyOption: true,items:['']});
    }

	/**
	 * Removes the selected repeater.
	 */
	$('.link-remove').on('click', function() {

		var parents = $(this).parents('li.repeater');
        var list = parents.closest('.repeaters');

		if ( ! parents.hasClass( 'first' ) ) {

			parents.remove();

		}

        updateNames(list);

		return false;

	});

	/**
	 * Shows/hides the selected repeater.
	 */
	$( '.btn-edit' ).on( 'click', function() {

		var repeater = $(this).parents( '.repeater' );

		repeater.children( '.repeater-content' ).slideToggle( '150' );
		$(this).children( '.toggle-arrow' ).toggleClass( 'closed' );
		$(this).parents( '.handle' ).toggleClass( 'closed' );

	});

	/**
	 * Changes the title of the repeater header as you type
	 */
	$( '.repeater-title' ).each(function(){

			var repeater = $(this).parents( '.repeater' );
			var fieldval = $(this).val();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			}
	});
    $(function(){

		$( '.repeater-title' ).on( 'keyup', function(){

			var repeater = $(this).parents( '.repeater' );
			var fieldval = $(this).val();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			} else {

				repeater_title.text( repeater_title.data('title') );

			}

		});

		$( 'select.repeater-title' ).each(function(){

			var repeater = $(this).parents( '.repeater' );
			var fieldval = $(this).find('option:selected').text();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {
				repeater_title.text( fieldval );
			}

		});

		$( 'select.repeater-title' ).on( 'change', function(){

			var repeater = $(this).parents( '.repeater' );
			var fieldval = $(this).find('option:selected').text();
            var repeater_title = repeater.find( '.title-repeater' );

			if ( fieldval.length > 0 ) {

				repeater_title.text( fieldval );

			} else {

				repeater_title.text( repeater_title.data('title') );

			}

		});

	});

	/**
	 * Makes the repeaters sortable.
	 */
	$(function() {
        if($( '.repeaters' ).length > 0){
            $( '.repeaters' ).sortable({
                cursor: 'move',
                handle: '.handle',
                items: '.repeater',
                opacity: 0.6,
                stop: function (event, ui) {
                    updateNames($(this))
                }
            });
        }
	});
    
    function updateNames($list) {
        if($list.attr('id') == 'repeater_sc_default_fields' || $list.attr('id') == 'repeater_sc_address_fields') return;
        $list.find('.repeater:visible').each(function (idx) {
            var $inp = $(this).find(':input');
            $inp.each(function () {
				console.log(this.name);
                var name = updateIndex(this.name, idx);
                this.name = name; 
                this.id = name; 
                $(this).next('label').attr('for',name);
            });
        });
    }

	function updateConditionsIndex(string, idx) {
		// Find the last occurrence of a number enclosed in square brackets
		const regex = /\[conditions\]\[\w*\](?!.*\[conditions\]\[\w+\])/g;
		const matches = string.match(regex);
	  
		if (matches && matches.length > 0) {
		  const lastMatch = matches[matches.length - 1];
	  
		  // Replace the last occurrence with "[x]"
		  const replacedString = string.replace(lastMatch, "[conditions]["+idx+"]");
		  return replacedString;
		}
	  
		return string;
	  }

	function updateIndex(string, idx) {
		// Find the last occurrence of a number enclosed in square brackets

		let regex = /\[\w*\](?!.*\[\w+\])/g;
		
		if(string.includes('[conditions][]')) {
			regex = /\[conditions\]\[\w*\](?!.*\[conditions\]\[\w+\])/g;
		}
		
		const matches = string.match(regex);
	  
		if (matches && matches.length > 0) {
		  const lastMatch = matches[matches.length - 1];
	  
		  // Replace the last occurrence with "[x]"
		  let replacedString = string.replace(lastMatch, "["+idx+"]");
		  if(string.includes('[conditions][]')) {
			replacedString = string.replace(lastMatch, "[conditions]["+idx+"]");
		  }
		  
		  return replacedString;
		}
	  
		return string;
	  }
    
    function updateConditionNames($list) {
        $list.find('.condition:visible').each(function (idx) {
            var $inp = $(this).find(':input');
            $inp.each(function () {
                var name = this.name.replace(/(\[\w*\])$/, '[' + idx + ']');
                this.name = name; 
                this.id = name; 
                $(this).next('label').attr('for',name);
            });
        });
    }

})( jQuery );