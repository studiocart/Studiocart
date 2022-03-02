// External Dependencies
import React, { Component } from 'react';
import $ from 'jquery'; 

// Internal Dependencies
import './style.css';
class StudioCartOrderForm extends Component {
  static slug = 'stof_studiocart_order_form';
	constructor(props) {
		super(props);
		this.state = {
			contentData: ''
		}				
	}  
	_reload_data(){		
		var $request;
		if ($request != null){ 
			$request.abort();
			$request = null;
		}
		const postId 		= this.props.sc_product;
		const template 		= this.props.sc_template;
		const coupon 		= this.props.sc_coupon;		
		const hideLabels 	= this.props.sc_hide_labels;		
		var isHideLabel = hideLabels === "no" ? "no" : "yes";
		var data = {postId: postId, template: template, coupon: coupon, label: isHideLabel, type: "et_pb_wpt_contact_form_7", 'action': 'pm_et_request_request', et_pb_render_shortcode_nonce: window.ETBuilderBackend.nonces.renderShortcode}
		$request = $.ajax({
			type: 'POST',
			url: window.et_fb_options.ajaxurl,
			dataType: 'json',
			data: data,
			success: function(response) {				
				this.setState({contentData: response.data})
			}.bind(this)
		});
		
	}
	
	componentDidMount(props) {
		this._reload_data()
	}
	 
   
	componentDidUpdate(prevProps) {
		if (prevProps.sc_product !== this.props.sc_product || prevProps.sc_hide_labels !== this.props.sc_hide_labels || prevProps.sc_template !== this.props.sc_template || prevProps.sc_coupon !== this.props.sc_coupon) {
			this._reload_data()			
		}
	}

  render() {	  
    return <div>
			<div dangerouslySetInnerHTML={{__html: this.state.contentData }}></div>
		</div>
  }
}
export default StudioCartOrderForm;
