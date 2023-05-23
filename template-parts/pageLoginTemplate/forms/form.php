<?php global $post; $content = isset($post->post_content) ? do_shortcode($post->post_content) : '';
global $pageLostPassword, $pageLogin, $pageRegister;
if ($pageLostPassword) {
	$template = $pathToFormsTemplates . 'lostpassword.php';
	ob_start();
	if (file_exists($template)) {
		include_once $template;
	}
	$formAndLinks = ob_get_clean(); echo preg_replace('/<form[\s\S]*?\/ul>/', $formAndLinks, $content) ?>
<?php }
else if ($pageRegister) {
	$template = $pathToFormsTemplates . 'register.php';
	ob_start();
	if (file_exists($template)) {
		include_once $template;
	}
	$formAndLinks = ob_get_clean(); echo preg_replace('/<form[\s\S]*?\/ul>/', $formAndLinks, $content) ?>
<?php } else {
	$template = $pathToFormsTemplates . 'login.php';
	ob_start();
	if (file_exists($template)) {
		include_once $template;
	}
	$formAndLinks = ob_get_clean(); echo preg_replace('/<form[\s\S]*?\/ul>/', $formAndLinks, $content);
} ?>