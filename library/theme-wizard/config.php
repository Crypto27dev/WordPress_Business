<?php
/**
 * Define constants
 **/
if (! defined('NPWIZARD_DIR')) {
	define('NPWIZARD_DIR', dirname(__FILE__));
}
require trailingslashit(NPWIZARD_DIR) . 'npwizard.php';
$options['page_slug'] 	= 'theme-wizard';
$options['page_title']	= 'Theme Wizard';

if(class_exists('Npwizard')) {
	$Npwizard = new Npwizard($options);
}