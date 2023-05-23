<form action="<?php echo wp_login_url(); ?>" method="post" class="u-clearfix u-form-custom-backend u-form-spacing-10 u-form-vertical u-inner-form" source="custom" name="form" style="padding: 10px;">
        <div class="u-form-group u-form-name">
          <label for="username-a30d" class="u-label" name=""><?php _e( 'Username' ); ?></label>
          <input type="text" placeholder="<?php _e( 'Username' ); ?>" id="username-a30d" name="log" class="u-input u-input-rectangle u-none u-input-1" required="">
        </div>
        <div class="u-form-group u-form-password">
          <label for="password-a30d" class="u-label" name=""><?php _e( 'Password' ); ?></label>
          <input type="password" placeholder="<?php _e( 'Password' ); ?>" id="password-a30d" name="pwd" class="u-input u-input-rectangle u-none u-input-2" required="">
        </div>
        <div class="u-form-checkbox u-form-group">
          <input type="checkbox" id="checkbox-a30d" name="rememberme" value="On" class="u-field-input">
          <label for="checkbox-a30d" class="u-block-3f60-32 u-field-label" style="" name=""><?php _e( 'Remember Me' ); ?></label>
        </div>
        <div class="u-align-left u-form-group u-form-submit">
          <a href="#" class="u-btn u-btn-submit u-button-style u-btn-1"><?php _e( 'Log in' ); ?></a>
          <input type="submit" value="submit" class="u-form-control-hidden">
        </div>
        <input type="hidden" value="" name="recaptchaResponse">
      </form>
<?php
$pathToLinkTemplates = get_template_directory() . '/template-parts/' . $pageLogin_custom_template . '/links/';
if (file_exists($pathToLinkTemplates . 'lostpassword.php')) {
	include_once $pathToLinkTemplates . 'lostpassword.php';
}
if (file_exists($pathToLinkTemplates . 'register.php')) {
	include_once $pathToLinkTemplates . 'register.php';
}