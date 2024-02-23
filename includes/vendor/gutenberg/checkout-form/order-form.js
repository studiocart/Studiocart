import { TextControl, PanelBody, PanelRow, SelectControl, ToggleControl } from "@wordpress/components";
import { InspectorControls } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";

const {__} = wp.i18n; //translation functions

wp.blocks.registerBlockType("sc-products-shortcode/product-shortcode", {
    title: __( sc_product_shortcode_script_gb.may_white_label_title+' Order Form' ), // Block title.
	category: 'common',
    attributes: {
        pid: {type: "string", default: ''},
        hide_labels: {type: "boolean", default: false},
        template: {type: "string", default: false},
        coupon: {type: "string", default: ''},
    },
	icon: 'cart',
	edit: EditForm,
	save: function(props) {
		return null;
	}
});

function EditForm (props) {

    const [thePreview, setThePreview] = useState("")

    useEffect(()=>{
        async function go() {
            const response = await apiFetch({
                path: `/sc-order-form/v1/getHTML?pid=${props.attributes.pid}`,
                method: 'GET'
            })
            setThePreview(response)
        }
        go()
    }, [props.attributes.pid]);

    const allProds = useSelect(select =>{
        return select("core").getEntityRecords('postType', 'sc_product', {per_page: -1})
    })

    console.log(allProds);

    let options = [{ label: __( 'Loading...', 'ncs-cart' ), value: '' }];
    if (allProds != undefined) {
        options = [
            { label: __( 'Dynamic', 'ncs-cart' ), value: '' },
        ]

        for (var i = 0; i < allProds.length; i++) {
            options[i+1] = {value: allProds[i].id, label: allProds[i].title.rendered};
        }
    }

    return (
        <div>
            <InspectorControls>
                <PanelBody title={__( 'Order Form', 'ncs-cart' )}>
                    <PanelRow>
                        <SelectControl isBlock
                            label={__( 'Product', 'ncs-cart' )}
                            value={props.attributes.pid} 
                            options={options}
                            onChange={(value) => {props.setAttributes({pid: value})}} />
                    </PanelRow>
                    
                    <ToggleControl
                                label={ __( 'Hide Labels', 'ncs-cart' ) }
                                /*help={
                                    hideLabels
                                        ? 'Hide labels.'
                                        : 'No hide labels.'
                                }*/
                                checked={props.attributes.hide_labels}
                                onChange={ (value) => {props.setAttributes({hide_labels: value })} }
                            />

                    <PanelRow>
                        <SelectControl isBlock
                            label={ __( 'Skin', 'ncs-cart' ) } 
                            value={props.attributes.template} 
                            options={ [
                                { label: 'Default', value: '' },
                                { label: 'Normal', value: 'normal' },
                                { label: '2-Step', value: 'true' },
                                { label: 'Opt-in', value: 'opt-in' },
                            ] }
                            onChange={(value) => {props.setAttributes({template: value})}} />
                    </PanelRow>
                    <PanelRow>
                        <TextControl 
                            label="Coupon" 
                            labelPosition="side" 
                            value={props.attributes.coupon} 
                            onChange={(value) => {props.setAttributes({coupon: value})}} />
                    </PanelRow> 
                </PanelBody>
            </InspectorControls>
            
            <button style={{border: "none"}} class="editor-post-featured-image__toggle" dangerouslySetInnerHTML={{__html: thePreview}}></button>

        </div>
    );
}