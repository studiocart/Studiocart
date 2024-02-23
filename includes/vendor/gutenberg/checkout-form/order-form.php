<?php 

$defaults = array(
    'pid' => '',
    'coupon' => '',
    'hide_labels' => false,
    'template' => false,
);

$attr = wp_parse_args( $attr, $defaults );

if($attr[ 'hide_labels' ] == 1) {
    $attr[ 'hide_labels' ] = 'hide';
}
$attr[ 'template' ] = $attr[ 'template' ] == "true" ? "2-step" : $attr[ 'template' ];
if($attr[ 'template' ]) {
    echo do_shortcode( "[studiocart-form id='{$attr[ 'pid' ]}' hide_labels='{$attr[ 'hide_labels' ]}' template='{$attr[ 'template' ]}' coupon='{$attr[ 'coupon' ]}']");
} else {
    echo do_shortcode( "[studiocart-form id='{$attr[ 'pid' ]}' hide_labels='{$attr[ 'hide_labels' ]}' coupon='{$attr[ 'coupon' ]}']");
}
?>