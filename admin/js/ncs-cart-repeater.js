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
        updateNames(parent);
        initializeSelect2(clone.find('.select2'));
        flatpickr(clone.find('.datepicker'), {enableTime: true,dateFormat: "Y-m-d h:i K",allowInput: true});

		return false;

	});
    
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
        $list.find('.repeater:visible').each(function (idx) {
            var $inp = $(this).find(':input');
            $inp.each(function () {
                var name = this.name.replace(/(\[\d*\])/, '[' + idx + ']')
                this.name = name; 
                this.id = name; 
                $(this).next('label').attr('for',name);
            });
        });
    }

})( jQuery );