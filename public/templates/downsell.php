<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( __FILE__ ) . 'template-functions.php';

global $scp;

$preview = false;
$sc_order = false;
if ( !isset($_POST['sc_order']) || !wp_verify_nonce( $_POST['sc_downsell_nonce'], 'studiocart_downsell-'.intval($_POST['sc_order']['ID']) ) ) {  
    $preview = true;
} else {
    $sc_order = $_POST['sc_order'];
    personalize_product_info($scp, $sc_order);
}

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo wp_get_document_title(); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
    <style type="text/css">
        .studiocart button,
        .studiocart input[type="button"] {
            background-color: <?php echo esc_html($scp->button_color); ?>
        }
    </style>
</head>
	
<body <?php body_class('sc-upsell'); ?>>
	
	<?php while ( have_posts() ) : the_post(); ?>
  
    <main class="studiocart-page studiocart upsell-page">
        <div id="upsell-notice"><?php echo esc_html($scp->ds_alert); ?></div>
        <div class="container">
            <div class="row">
                <div class="col-sm-6">
                    <h1><?php echo esc_html($scp->ds_headline); ?></h1>
                    <p><?php echo esc_html($scp->ds_description); ?></p>
                    <center>
                        <a href="sc-upsell-offer=yes" class="sc-button"><?php echo esc_html($scp->ds_proceed); ?></a>
                        <a href="sc-upsell-offer=no" class="sc-nope">
                            <?php echo esc_html($scp->ds_decline); ?>
                        </a>
                    </center>
                </div>
                <div class="col-sm-6">
                    <img src="<?php echo esc_url($scp->ds_image); ?>">
                </div>
            </div>
  	    </div>
	</main>
	
	<?php endwhile; ?>
	
	<?php wp_footer(); ?>

</body>
</html>