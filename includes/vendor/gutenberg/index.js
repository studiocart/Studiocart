const {registerBlockType} = wp.blocks; //Blocks API
const {createElement} = wp.element; //React.createElement
const {__} = wp.i18n; //translation functions
const {InspectorControls} = wp.editor; //Block inspector wrapper
const {TextControl,SelectControl,ServerSideRender, ToggleControl} = wp.components; //WordPress form inputs and server-side renderer
var withSelect = wp.data.withSelect;
	registerBlockType( 'sc-products-shortcode/product-shortcode', {
		title: __( sc_product_shortcode_script_gb.may_white_label_title+' Order Form' ), // Block title.
		category:  __( 'widgets' ), //category
		attributes:  {
			id : {
				default: 0,
			},
			pid : {
				default: 0,
			},
			hide_labels: {
				default: true
			},
			template: {
				default: false
			},
			coupon: {
				default: ''
			}
		},
		//display the post title
		edit:withSelect( function( select ) {			
				return {
					//get products
					products: select( 'core' ).getEntityRecords( 'postType', 'sc_product' , {per_page : -1}  ),
				};
			})(function (props){
				var products_data = [{value: 0, label: 'Dynamic'}];
			   if ( ! props.products ) {
					//console.log("loading...")
				} else if ( props.products.length === 0 ) {
					//no products found
				} else {
					var products = props.products;
					products.forEach(element => {
						var product_id = element.id;
						var product_title = element.title != undefined ? element.title.rendered : '';
						products_data.push({ value: product_id, label: product_title });
					});
				}
				
			const attributes =  props.attributes;
			const setAttributes =  props.setAttributes;

			//Function to update id attribute
			function changeId(id){			
				setAttributes({id});
			}
			function changeIdpid(pid){			
				setAttributes({pid});
				setAttributes({id : pid});
			}

			//Function to update label
			function changeLabel(hide_labels){			
				setAttributes({hide_labels});				
			}
			
			//Function to update template
			function changeTemplate(template){					
				setAttributes({template});
			}
			
			function changeCoupon(coupon){					
				setAttributes({coupon});
			}

			//Display block preview and UI
			return createElement('div', {}, [
				//Preview a block with a PHP render callback
				createElement( ServerSideRender, {
					block: 'sc-products-shortcode/product-shortcode',
					attributes: attributes,
					'className' : 'sc_gutenberg_block'
				}),
				//Block inspector
				createElement( InspectorControls, {'className' : 'sc_gutenberg_block_settings'},
					[
						//A simple text control for post id
						createElement(SelectControl, {
							className : 'sc_gutenberg_select_setting',
							value: attributes.id,
							label: __( 'Select product' ),
							onChange: changeId,
							options: products_data
						}),
						
						createElement(TextControl, {
							className : 'sc_gutenberg_product_setting',
							value: attributes.pid,
							hideLabelFromVision : true,
							label: "HIDDENLABEL",
							onChange: changeIdpid,						
						}),
							
						
						//Select heading level
						createElement(ToggleControl, {
							className : 'sc_gutenberg_hl_setting',
							checked: attributes.hide_labels,
							label: __( 'Hide Labels' ),
							onChange: changeLabel,						
						}),
						
						createElement(SelectControl, {
							className : 'sc_gutenberg_step_setting',
							checked: attributes.template,
							label: __( 'Skin' ),
							onChange: changeTemplate,						
							options: [{value: '', label: 'Default'},{value: '2-step', label: '2-Step'},{value: 'opt-in', label: 'Opt-in'}]
						}),
						createElement(TextControl, {
							className : 'sc_gutenberg_coupon_code',
							value: attributes.coupon,
							label: __( 'Coupon Code' ),
							onChange: changeCoupon,						
						})
					]
				)
			] )
			
			
			
		}),
		save(){
			return null;//save has to exist. This all we need
		}
	});
	jQuery(document).ready(function($){	
		$.fn.changeVal = function (v) {
			return this.val(v).trigger("change");
		}
		
		$(document).on("click", ".sc_gutenberg_select_setting select", function(e){					
			var _this = $(this);
			//add select2	
			var $eventSelect = _this.select2(); 
			$eventSelect.select2("open"); //open select2
			
			var productVal = $(".interface-interface-skeleton__sidebar .components-text-control__input");		
			// Dispatch it.
			$eventSelect.on("change", function (e) { 			
				var _val = $(this).val()			
				let input = document.querySelector(".interface-interface-skeleton__sidebar .components-text-control__input"); 
				let lastValue = input.value;
				input.value = _val;
				let event = new Event('input', { bubbles: true });			
				event.simulated = true;			
				let tracker = input._valueTracker;
				if (tracker) {
				  tracker.setValue(lastValue);
				}
				input.dispatchEvent(event);
			});
		})
	})
