<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://studiocart.co
 * @since      1.0.0
 *
 * @package    Now Hiring
 * @subpackage Now Hiring/admin/partials
 */
?><h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
<?php
$active_tab = 'general';
if(isset($_REQUEST['tab'])){
	$active_tab = $_REQUEST['tab'];
} ?>
<div class="nav-tab-wrapper">
	<a href="#general" id="settings_tab_general" onclick="ncs_settings('general');" class="settings_tab nav-tab"><?php _e('General', 'sandbox'); ?></a>
	<a href="#payment_methods" id="settings_tab_payment_methods" onclick="ncs_settings('payment_methods');" class="settings_tab nav-tab"><?php _e('Payment Methods', 'sandbox'); ?></a>
	<a href="#integrations" id="settings_tab_integrations" onclick="ncs_settings('integrations');" class="settings_tab nav-tab"><?php _e('Integrations', 'sandbox'); ?></a>
	<a href="#emails" id="settings_tab_emails" onclick="ncs_settings('emails');" class="settings_tab nav-tab"><?php _e('Emails', 'sandbox'); ?></a>
	<a href="#tax" id="settings_tab_tax" onclick="ncs_settings('tax');" class="settings_tab nav-tab"><?php _e('Taxes', 'sandbox'); ?></a>
	<a href="#invoice" id="settings_tab_invoice" onclick="ncs_settings('invoice');" class="settings_tab nav-tab"><?php _e('Invoices', 'sandbox'); ?></a>
</div>
<form method="post" action="options.php" class="settings-options-form"><?php
settings_fields( $this->plugin_name . '-settings' );?>
<div id="content_tab_general" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name ); ?>
</div>
<div id="content_tab_payment_methods" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name.'-payment'); ?>
</div>
<div id="content_tab_integrations" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name.'-integrations'); ?>
</div>
<div id="content_tab_emails" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name.'-email'); ?>
</div>
<div id="content_tab_tax" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name.'-tax'); 
include( plugin_dir_path( __FILE__ ) . 'ncs-cart-admin-page-settings-tax.php' ); ?>
</div>
<div id="content_tab_invoice" class="tab-content" style="display:none">
<?php do_settings_sections( $this->plugin_name.'-invoice'); ?>
</div>
<?php submit_button( __('Save Settings', 'ncs-cart') );
?></form>