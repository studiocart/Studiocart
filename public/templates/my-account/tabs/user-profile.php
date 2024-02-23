<?php
$current_user = wp_get_current_user();
$user_phone = sc_get_user_phone($current_user->ID);
$address = sc_get_user_address($current_user->ID);
?>
<div class="profile-wrapper studiocart">
    <form method="post" id="updateProfileForm">
        
        <div class="form-group form-field">
            <label><?php esc_html_e('First Name', 'ncs-cart'); ?>: </label>
            <input type="text" name="first_name" value="<?php echo get_user_meta($current_user->ID, 'first_name',true) ?>" class="ep_disabled" disabled="disabled"/>
        </div>

        <div class="form-group form-field">
            <label><?php esc_html_e('Last Name', 'ncs-cart'); ?>: </label>
            <input type="text" name="last_name" value="<?php echo get_user_meta($current_user->ID, 'last_name',true); ?>" class="ep_disabled" disabled="disabled"/>
        </div>

        <div class="form-group form-field">
            <label><?php esc_html_e('Email', 'ncs-cart'); ?>: </label>
            <input type="text" name="email" value="<?php echo $current_user->user_email; ?>"  class="ep_disabled" disabled="disabled"/>
        </div>

        <div class="form-group form-field">
            <label><?php esc_html_e('Phone', 'ncs-cart'); ?>: </label>
            <input type="text" name="_sc_phone" placeholder="<?php esc_html_e('Phone', 'ncs-cart'); ?>" value="<?php echo $user_phone; ?>" class="ep_disabled" disabled="disabled"/>
        </div>
        
        <div class="ep-edit-address">
            <div class="form-group form-field">
                <label><?php esc_html_e('Address', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_address1" placeholder="<?php esc_html_e('Address', 'ncs-cart'); ?>" value="<?php echo $address['address_1']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>

            <div class="form-group form-field">
                <label><?php esc_html_e('Address 2', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_address2" placeholder="<?php esc_html_e('Address 2', 'ncs-cart'); ?>" value="<?php echo $address['address_2']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>

            <div class="form-group form-field">
                <label><?php esc_html_e('City', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_city" placeholder="<?php esc_html_e('City', 'ncs-cart'); ?>" value="<?php echo $address['city']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>

            <div class="form-group form-field">
                <label><?php esc_html_e('State', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_state" placeholder="<?php esc_html_e('State', 'ncs-cart'); ?>" value="<?php echo $address['state']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>

            <div class="form-group form-field">
                <label><?php esc_html_e('Zip', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_zip" placeholder="<?php esc_html_e('Zip', 'ncs-cart'); ?>" value="<?php echo $address['zip']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>

            <div class="form-group form-field">
                <label><?php esc_html_e('Country', 'ncs-cart'); ?>: </label>
                <input type="text" name="_sc_country" placeholder="<?php esc_html_e('Country', 'ncs-cart'); ?>" value="<?php echo $address['country']; ?>" class="ep_disabled" disabled="disabled"/>
            </div>
        </div>
        
        <div id="all-subscription-address-wrap" class="form-group form-field" style="display: none">
            <input type="checkbox" id="all-subscription-address" name="sc-all-subscription-address" class="ep_disabled" disabled="disabled">
            <label for="sc-all-subscription-address"><?php esc_html_e('Set default address for all active subscriptions', 'ncs-cart'); ?></label>
        </div>

        <div class="form-group form-field" id="newPasswordDiv" style="display:none">
            <label><?php esc_html_e('New Password', 'ncs-cart'); ?>: </label>
            <input type="password" name="password" placeholder="**********" value="" class="ep_disabled" disabled="disabled"/>
        </div>

        <div class="form-group form-field" id="confirmNewPasswordDiv" style="display:none">
            <label><?php esc_html_e('Confirm New Password', 'ncs-cart'); ?>: </label>
            <input type="password" name="new_password" placeholder="**********" value="" class="ep_disabled" disabled="disabled"/>
        </div>

        <div class="form-group">
            <div id="p-alert"></div>
            <input type="button" value="<?php esc_html_e('Update Profile', 'ncs-cart'); ?>" id="updateProfile"/>
            <input type="button" value="<?php esc_html_e('Edit Profile', 'ncs-cart'); ?>" id="editProfile"/>
            <input type="button" value="<?php esc_html_e('Cancel', 'ncs-cart'); ?>" id="editProfileCancel"/>
            <span id="sc-loader"><img src="<?php echo NCS_CART_BASE_URL ?>public/images/spinner.gif"></span>
        </div>
    </form>
</div>