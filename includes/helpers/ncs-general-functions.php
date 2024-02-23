<?php

/**
 * Return the template path
 * @param $template_name Name of the template
 * @param $slug path to the directory of the template location
 */

function ncs_get_template_path($slug, $name = ''){
    
    $template_path = $slug;
        
    if($name){
        $template_path .= '-'.$name;
    }
    
    $template_path .= '.php';
    
    $template = get_stylesheet_directory() . '/studiocart/'.$template_path;
    if(!file_exists($template)){
        $template = NCS_CART_BASE_DIR . 'public/templates/'.$template_path;
        
        if(!file_exists($template)){
            return false;
        }
    }

    return $template;
}

/**
 * Returns the template
 * @param $name|String Name of the template
 * @param $path|String path to the directory of the template location
 * @param $attr|array Data variables
 * @param $canOverride|boolean To determine if template can be overridden in theme
 */
function ncs_get_template( $slug, $name = '', $attr = []){
    
    ob_start();
    do_action( 'sc_template_before_' . $slug );

    $template = ncs_get_template_path( $slug, $name);

    do_action( 'sc_template_after_' . $slug );

    if($template) {
        require( $template );
    }

    $html = ob_get_contents();
    ob_end_clean();
    return $html;

}

/**
 * Includes the template
 * @param $template_name|String Name of the template
 * @param $path|String path to the directory of the template location
 */
function ncs_template($slug, $name = '', $attr = []){
    $template = ncs_get_template_path( $slug, $name, $attr);
    require( $template );
}


/**
 * Display tabs on my account page
 */
function ncs_account_tabs(){

    $tabs = [

        [
            'id' => 'tab-orders',
            'title' => 'Orders',
            'content' => 'my-account/tabs/order-history',
            'active' => 1,
        ],

        [
            'id' => 'tab-subscriptions',
            'title' => 'Subscriptions',
            'content' => 'my-account/tabs/subscriptions',
        ],

        [
            'id' => 'tab-plans',
            'title' => 'Payment Plans',
            'content' => 'my-account/tabs/plans',
        ],
        
        [
            'id' => 'tab-profile',
            'title' => __('My Profile', 'ncs-cart'),
            'content' => 'my-account/tabs/user-profile',
        ],
    ]; 


    $filteredTabs = apply_filters( 'sc_account_tabs', $tabs);
    
     foreach($filteredTabs as $key => $tab){
         
        if(isset($tab['is_active'])){

            unset($filteredTabs[$key]['is_active']);
            unset($filteredTabs[0]['active']);
            $filteredTabs[$key]['active'] = 1;

        }

        if(isset($tab['order'])){
            $filteredTabs[$key]['order'] = $tab['order']-1;
        }else{
            $filteredTabs[$key]['order'] = $key+1;
        }
     }

    $keys = array_column($filteredTabs, 'order');
    array_multisort($keys, SORT_ASC, $filteredTabs);
    return $filteredTabs;
}

?>