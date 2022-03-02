/* global ncsLocalizeTaxSettings */

/**
 * Used by StudioCart-wp/admin/partials/ncs-cart-admin-page-settings-tax.php
 */
 ( function( $, data, wp ) {
	$( function() {

		if ( ! String.prototype.trim ) {
			String.prototype.trim = function () {
				return this.replace( /^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '' );
			};
		}

		var rowTemplate        	= wp.template( 'ncs-tax-table-row' ),
			rowTemplateEmpty   	= wp.template( 'ncs-tax-table-row-empty' ),
			paginationTemplate 	= wp.template( 'ncs-tax-table-pagination' ),
			$table             	= $( '.nsc_tax_rate_table' ),
			$tbody             	= $( '#rates' ),
			$save_button       	= $( ':input[name="save"]' ),
			$pagination        	= $( '#rates-pagination' ),
			$search_field      	= $( '#rates-search .ncs-tax-rates-search-input' ),
			hasFocus			= false,
			controlled 			= false,
			shifted    			= false,
			changes 			= {},
			
			NCSTaxTable = class {
				constructor(data, elm) {
					this.data = data;
					this.elm = elm;
				}
                render(){
					var rates       = this.getRatesWithFilter(),
						total_rates   = _.size( rates ),
						total_pages   = Math.ceil( total_rates / this.data.limit ),
						first_index = 0 === total_rates ? 0 : this.data.limit * ( this.data.page - 1 ),
						last_index  = this.data.limit * this.data.page,
						paged_rates = _.toArray( rates ).slice( first_index, last_index ),
						ncs        = this;

					// Blank out the contents.
					this.elm.empty();
					if ( paged_rates.length ) {
						// Populate $tbody with the current page of results.
						$.each( paged_rates, function( id, rowData ) {
							ncs.elm.append( rowTemplate( rowData ) );
						} );
					} else {
						ncs.elm.append( rowTemplateEmpty() );
					}

					// Initialize autocomplete for countries.
					this.elm.find( 'td.tax-country input' ).autocomplete({
					 	source: this.data.sc_countries,
					 	minLength: 2
					});

					// Initialize autocomplete for states.
					ncs.elm.find( 'td.tax-state input' ).autocomplete({
						source: this.data.sc_states,
						minLength: 3
					});

					// Postcode and city don't have `name` values by default.
					// They're only created if the contents changes, to save on database queries (I think)
					// this.$el.find( 'td.postcode input, td.city input' ).change( function() {
					// 	$( this ).attr( 'name', $( this ).data( 'name' ) );
					// });

					if ( total_pages > 1 ) {
						// We've now displayed our initial page, time to render the pagination box.
						$pagination.html( paginationTemplate( {
							qty_rates:    total_rates,
							current_page: this.data.page,
							qty_pages:    total_pages
						} ) );
					} else {
						$pagination.empty();
						ncs.page = 1;
					}
				}
                getRatesWithFilter(){
                    var rates  = this.data.tax_rates,
                    search = $search_field.val().toLowerCase();

                    if ( search.length ) {
                        rates = _.filter( rates, function( rate ) {
                            var search_text = _.toArray( rate ).join( ' ' ).toLowerCase();
                            return ( -1 !== search_text.indexOf( search ) );
                        } );
                    }

                    rates = _.sortBy( rates, function( rate ) {
                        return parseInt( rate.tax_rate_order, 10 );
                    } );

                    return rates;
                }
                onAddNewRow() {
					var rates   = _.indexBy( this.data.tax_rates, 'tax_rate_id' ),
						
						size    = _.size( rates ),
						newRow  = _.extend( {}, this.data.default_rate, {
							tax_rate_id: 'new-' + size + '-' + Date.now(),
							newRow:      true
						} ),
						$current, current_id, current_order, rates_to_reorder, reordered_rates;

					$current = $tbody.children( '.current' );

					if ( $current.length ) {
						current_id            = $current.last().data( 'id' );
						current_order         = parseInt( rates[ current_id ].tax_rate_order, 10 );
						newRow.tax_rate_order = 1 + current_order;

						rates_to_reorder = _.filter( rates, function( rate ) {
							if ( parseInt( rate.tax_rate_order, 10 ) > current_order ) {
								return true;
							}
							return false;
						} );

						reordered_rates = _.map( rates_to_reorder, function( rate ) {
							rate.tax_rate_order++;
							changes[ rate.tax_rate_id ] = _.extend(
								changes[ rate.tax_rate_id ] || {}, { tax_rate_order : rate.tax_rate_order }
							);
							return rate;
						} );
					} else {
						newRow.tax_rate_order = 1 + _.max(
							_.pluck( rates, 'tax_rate_order' ),
							function ( val ) {
								// Cast them all to integers, because strings compare funky. Sighhh.
								return parseInt( val, 10 );
							}
						);
						// Move the last page
						//view.page = view.qty_pages;
					}

					rates[ newRow.tax_rate_id ]   = newRow;
					changes[ newRow.tax_rate_id ] = newRow;

					this.data.tax_rates = rates;

					this.render();
				}

				onChange( event ) {
					var $target   = $( event.target ),
						id        = $target.closest( 'tr' ).data( 'id' ),
						attribute = $target.data( 'attribute' ),
						val       = $target.val();

					if ( 'city' === attribute || 'postcode' === attribute ) {
						val = val.split( ';' );
						val = $.map( val, function( thing ) {
							return thing.trim();
						});
					}

					this.setRateAttribute( id, attribute, val );
				}

				setRateAttribute( rateID, attribute, value ) {
					var rates   = _.indexBy( this.data.tax_rates, 'tax_rate_id' );

					if ( rates[ rateID ][ attribute ] !== value ) {
						if(!changes[ rateID ])
							changes[ rateID ] = {};
							
						changes[ rateID ][ attribute ] = value;
						rates[ rateID ][ attribute ]   = value;
					}
				}

				onSelectRow(event,$this){
					var $this_table = $this.closest( 'table, tbody' );
					var $this_row   = $this.closest( 'tr' );
		
					if ( ( (event.type === 'focus'  || event.type === 'focusin') && hasFocus !== $this_row.index() ) || ( event.type === 'click' && $( this ).is( ':focus' ) ) ) {
						hasFocus = $this_row.index();
		
						if ( ! shifted && ! controlled ) {
							$( 'tr', $this_table ).removeClass( 'current' ).removeClass( 'last_selected' );
							$this_row.addClass( 'current' ).addClass( 'last_selected' );
						} else if ( shifted ) {
							$( 'tr', $this_table ).removeClass( 'current' );
							$this_row.addClass( 'selected_now' ).addClass( 'current' );
		
							if ( $( 'tr.last_selected', $this_table ).length > 0 ) {
								if ( $this_row.index() > $( 'tr.last_selected', $this_table ).index() ) {
									$( 'tr', $this_table )
										.slice( $( 'tr.last_selected', $this_table ).index(), $this_row.index() )
										.addClass( 'current' );
								} else {
									$( 'tr', $this_table )
										.slice( $this_row.index(), $( 'tr.last_selected', $this_table ).index() + 1 )
										.addClass( 'current' );
								}
							}
		
							$( 'tr', $this_table ).removeClass( 'last_selected' );
							$this_row.addClass( 'last_selected' );
						} else {
							$( 'tr', $this_table ).removeClass( 'last_selected' );
							if ( controlled && $( this ).closest( 'tr' ).is( '.current' ) ) {
								$this_row.removeClass( 'current' );
							} else {
								$this_row.addClass( 'current' ).addClass( 'last_selected' );
							}
						}
		
						$( 'tr', $this_table ).removeClass( 'selected_now' );
					}
				}
				onSave(event, $save_button){
					var self = this;
                    $save_button.attr('disabled', 'disabled');

					//self.block();

					$.ajax({
						method: 'POST',
						dataType: 'json',
						url: this.data.ajaxurl + ( this.data.ajaxurl.indexOf( '?' ) > 0 ? '&' : '?' ) + 'action=ncs_ajax_action',
						data: {
							ncs_ajax_nonce: this.data.ncs_ajax_nonce,
							ncs_tax_nonce: this.data.ncs_tax_nonce,
							changes: changes,
							ncs_action:'save_table_tax'
						},
						success: function( response, textStatus ) {
							if ( 'success' === textStatus && response.rates ) {
								self.data.tax_rates = response.rates;

								self.render();
								changes={};
                                
							}
                            alert('Tax rates saved.');
                            $save_button.removeAttr('disabled');
							//self.unblock();
						},
                        error:function(response, textStatus, errorThrown){
							alert('Something Went Wrong:'+response.responseJSON.message);
                            $save_button.removeAttr('disabled');
						}
					});
				}
				onDeleteRow(event) {
					var rates   = _.indexBy( this.data.tax_rates, 'tax_rate_id' ),
						$current, current_id;

					event.preventDefault();

					if ( $current = $tbody.children( '.current' ) ) {
						$current.each(function(){
							current_id    = $( this ).data('id');

							delete rates[ current_id ];

							changes[ current_id ] = _.extend( changes[ current_id ] || {}, { deleted : 'deleted' } );
						});

						this.data.tax_rates =  rates;
						
						this.render();
					} else {
						window.alert( data.strings.no_rows_selected );
					}
				}
			}

			let NcsTaxTable = new NCSTaxTable(data,$tbody);

		    NcsTaxTable.render();
			$table.find( '.add_new_tax_rate' ).on( 'click', function( e ){
				NcsTaxTable.onAddNewRow();
                return false;
			} );
			$table.find( '.remove_selected_tax_rates' ).on( 'click', function( e ){
				NcsTaxTable.onDeleteRow(e);
                return false;
			} );
			$table.on( 'focus click', 'input', function( e ) {
				NcsTaxTable.onSelectRow(e,$(this));
			}).on( 'blur', 'input', function() {
				hasFocus = false;
			});
			$save_button.on( 'click', function( e ){
				e.preventDefault();
				NcsTaxTable.onSave(e, $save_button);
			} );
			$tbody.on( 'change autocompletechange', 'input', function(e){
				NcsTaxTable.onChange(e);
			});
			$( document.body ).bind( 'keyup keydown', function( e ) {
				shifted    = e.shiftKey;
				controlled = e.ctrlKey || e.metaKey;
			});
	});
	$('.sc_tax_global').change(function(){
		$('.sc_tax_global').not(this).prop('checked',false);
	});
})( jQuery, ncsLocalizeTaxSettings, wp );
