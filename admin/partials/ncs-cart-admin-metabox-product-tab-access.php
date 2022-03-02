<?php

/**
 * Provide the view for a metabox
 *
 * @link 		https://studiocart.co
 * @since 		1.0.0
 *
 * @package 	Studiocart
 * @subpackage 	Studiocart/admin/partials
 */


$fields = array(
    array(
        'class'		=> 'widefat',
        'description'	=> '',
        'id'			=> '_sc_manage_stock',
        'label'		=> __('Limit product sales', 'ncs-cart'),
        'name'		=> 'sc-manage-stock',
        'placeholder'	=> '',
        'type'		=> 'checkbox',
        'value'		=> '',
    ),
    array(
        'class'		=> 'widefat',
        'description'	=> __('Total available # of this product. Once this reaches 0, the cart will close.', 'ncs-cart'),
        'id'			=> '_sc_limit',
        'label'		=> __('Amount Remaining', 'ncs-cart'),
        'name'		=> '_sc_limit',
        'placeholder'	=> '',
        'type'		=> 'text',
        'value'		=> '',
    ),
);

$this->metabox_fields($fields);
