<?php
/*
Template Name: The template for displaying 404 pages (not found)
*/
global $page404_custom_template;
$page404_custom_template = 'page404Template';
$language = isset($_GET['lang']) ? $_GET['lang'] : '';

add_action(
    'theme_content_styles',
    function () use ($page404_custom_template) {
        theme_single_content_styles($page404_custom_template);
    }
);

function theme_single_body_class_filter($classes) {
    $classes[] = 'u-body u-xl-mode';
    return $classes;
}
add_filter('body_class', 'theme_single_body_class_filter');

function theme_single_body_style_attribute() {
    return "";
}
add_filter('add_body_style_attribute', 'theme_single_body_style_attribute');

function theme_single_body_back_to_top() {
    ob_start(); ?>
    
    <?php
    return ob_get_clean();
}
add_filter('add_back_to_top', 'theme_single_body_back_to_top');


function theme_single_get_local_fonts() {
    return '';
}
add_filter('get_local_fonts', 'theme_single_get_local_fonts');

ob_start();
get_header();
$header = ob_get_clean();
if (function_exists('renderHeader')) {
    renderHeader($header, '', 'echo');
} else {
    echo $header;
}

theme_layout_before('page404', '', $page404_custom_template);
$translations = '';
if ($language) {
    if (file_exists(get_stylesheet_directory() . '/' . 'template-parts/'. $page404_custom_template . '/translations/' . $language .'/single-content' . '.php')) {
        $translations = '/translations/' . $language;
    }
}
ob_start();
get_template_part('template-parts/' . $page404_custom_template . $translations .'/single-content');
$content = ob_get_clean();
if (function_exists('renderTemplate')) {
    renderTemplate($content, '', 'echo', 'custom');
} else {
    echo $content;
}
theme_layout_after('page404');

ob_start();
get_footer();
$footer = ob_get_clean();
if (function_exists('renderFooter')) {
    renderFooter($footer, '', 'echo');
} else {
    echo $footer;
}

remove_filter('body_class', 'theme_single_body_class_filter');