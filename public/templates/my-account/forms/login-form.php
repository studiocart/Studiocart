<?php
/**
 * The Template for displaying login/password-reset page
 * This template can be overridden by copying it to yourtheme/studiocart/my-account/forms/login-form.php
 */
?>

<?php

if ( $attr['action'] == 'reset' ) : ?>

<div id="sc-password-reset-form" class="sc-account-form widecolumn">
    <h3><?php _e( 'Pick a New Password', 'ncs-cart' ); ?></h3>

    <form name="resetpassform" id="resetpassform" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>"
        method="post" autocomplete="off">
        <input type="hidden" id="user_login" name="rp_login" value="<?php echo esc_attr( $attr['login'] ); ?>"
            autocomplete="off" />
        <input type="hidden" name="rp_key" value="<?php echo esc_attr( $attr['key'] ); ?>" />

        <?php if ( count( $attr['errors'] ) > 0 ) : ?>
        <?php foreach ( $errors as $error ) : ?>
        <p>
            <?php echo $error; ?>
        </p>
        <?php endforeach; ?>
        <?php endif; ?>

        <p>
            <label for="pass1"><?php _e( 'New password', 'ncs-cart' ) ?></label>
            <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
        </p>
        <p>
            <label for="pass2"><?php _e( 'Repeat new password', 'ncs-cart' ) ?></label>
            <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
        </p>

        <p class="description"><?php echo wp_get_password_hint(); ?></p>

        <p class="resetpass-submit">
            <input type="submit" name="submit" id="resetpass-button" class="button"
                value="<?php _e( 'Reset Password', 'ncs-cart' ); ?>" />
        </p>
    </form>
</div>

<?php elseif ($attr['action'] == 'lostpassword') : ?>

<div id="sc-password-lost-form" class="widecolumn">
    <h3><?php _e( 'Forgot Your Password?', 'ncs-cart' ); ?></h3>

    <?php if ( $attr['lost_password_sent'] ) : ?>
    <p class="login-info">
        <?php _e( 'Check your email for a link to reset your password.', 'ncs-cart' ); ?>
    </p>
    <p><a class="button" href="<?php echo $attr['login_url']; ?>"><?php _e( 'Back to Login', 'ncs-cart' ); ?></a></p>
    <?php else: ?>

    <p>
        <?php _e("Please enter your username or email address. You will receive an email message with instructions on how to reset your password.",'ncs-cart');?>
    </p>

    <?php if ( count( $attr['errors'] ) > 0 ) : ?>
    <?php foreach ( $attr['errors'] as $error ) : ?>
    <p class="sc-account-error"><?php echo $error; ?></p>
    <?php endforeach; ?>
    <?php endif; ?>


    <form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
        <p class="form-row">
            <label for="user_login"><?php _e( 'Username or Email Address', 'ncs-cart' ); ?>
                <input type="text" name="user_login" id="user_login">
        </p>

        <p class="lostpassword-submit">
            <input type="submit" name="submit" class="lostpassword-button"
                value="<?php _e( 'Reset Password', 'ncs-cart' ); ?>" />
        </p>
    </form>
    <?php endif; ?>
</div>

<?php else: ?>

<div id="sc-login">
    <?php if ( $attr['password_updated'] ) : ?>
    <p class="login-info">
        <?php _e( 'Your password has been changed. Please sign in below.', 'ncs-cart' ); ?>
    </p>
    <?php endif; ?>

    <!-- Show errors if there are any -->
    <?php if ( count( $attr['errors'] ) > 0 ) : ?>
    <?php foreach ( $attr['errors'] as $error ) : ?>
    <p class="sc-account-error">
        <?php echo $error; ?>
    </p>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php
    $args = array(
        'redirect' => $attr['login_url'], 
        'form_id' => 'sc-login-form',
        'label_username' => __( 'Username' ),
        'label_password' => __( 'Password' ),
        'label_remember' => __( 'Remember Me' ),
        'label_log_in' => __( 'Log In' ),
        'remember' => true
    );
    echo wp_login_form($args); 
    ?>
</div>

<?php endif; ?>