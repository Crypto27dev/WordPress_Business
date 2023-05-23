<?php
defined('ABSPATH') or die;

global $theme_options, $theme_templates_options, $theme_template_query, $theme_template_type_priority, $theme_default_options;

$theme_templates_options = array();
$theme_selectable_templates = array();
$theme_template_type_priority = array();
$theme_template_query = array();

if(!function_exists('theme_woocommerce_enabled')) {
    function theme_woocommerce_enabled() {
        global $woocommerce;
        return $woocommerce != null;
    }
}

function theme_add_template_option($type, $name, $caption, $type_priority = 10) {
    global $theme_templates_options, $theme_template_type_priority;
    $theme_template_type_priority[$type] = $type_priority;
    $theme_templates_options[$type][$name] = esc_attr(urldecode($caption));
}

function theme_add_template_query_option($type, $name, $caption) {
    global $theme_template_query;
    $theme_template_query[$name] = esc_attr(urldecode($caption));
}

theme_include_lib('templates_options.php');

$theme_options = array(
    array(
        'name' => __('Templates', 'default'),
        'type' => 'heading'
    )
);

function theme_compare_template_names($a, $b) {
    global $theme_template_type_priority;
    if ($theme_template_type_priority[$a] === $theme_template_type_priority[$b])
        return strnatcasecmp($a, $b);
    return $theme_template_type_priority[$b] - $theme_template_type_priority[$a];
}
uksort($theme_templates_options, 'theme_compare_template_names');

foreach($theme_templates_options as $template => $list) {
    ksort($list);
    $theme_options[] = array(
        'id'      => 'theme_template_' . get_option('stylesheet') . '_' . sanitize_title_with_dashes($template),
        'name'    => $template,
        'type'    => 'select',
        'options' => $list
    );
}

function theme_template_get_option($name) {
    global $theme_default_options;
    $result = theme_get_option($name);
    if ($result === false) {
        $result = theme_get_array_value($theme_default_options, $name);
    }
    return $result;
}

$theme_name = get_option('stylesheet');

$theme_default_options = array(
    'colors_css' => '',

    'fonts_css' => '',
    'fonts_link' => '',

    'typography_css' => '',

    'logo_width' => '',
    'logo_height' => '',
    'logo_link' => '',

    'menu_trim_title' => 1,
    'menu_trim_len' => 45,
    'submenu_trim_len' => 40,
    'menu_use_tag_filter' => 1,
    'menu_allowed_tags' => 'span, img',
    'use_default_menu' => '',

    'excerpt_auto' => 1,
    'excerpt_words' => 40,
    'excerpt_min_remainder' => 5,
    'excerpt_strip_shortcodes' => '',
    'excerpt_use_tag_filter' => 1,
    'excerpt_allowed_tags' => 'a, abbr, blockquote, b, cite, pre, code, em, label, i, p, strong, ul, ol, li, h1, h2, h3, h4, h5, h6, object, param, embed',
    'show_morelink' => 1,
    'morelink_template' => '<a href="[url]">[text]</a>',

    'include_jquery' => '',

    'seo_og' => 1,
    'seo_ld' => 1,

    'sidebars_layout_blog' => '',
    'sidebars_layout_post' => '',
    'sidebars_layout_default' => '',

    'theme_template_' . $theme_name . '_blog-template' => 'blogTemplate',
    'theme_template_' . $theme_name . '_post-template' => 'postTemplate',
    'theme_template_' . $theme_name . '_products-template' => 'productsTemplate',
    'theme_template_' . $theme_name . '_product-template' => 'productTemplate',
    'theme_template_' . $theme_name . '_shopping-cart-template' => 'shoppingCartTemplate',
    'theme_template_' . $theme_name . '_checkout-template' => 'checkoutTemplate',
    'theme_template_' . $theme_name . '_404-template' => 'page404Template',
    'theme_template_' . $theme_name . '_login-template' => 'pageLoginTemplate',
);

function theme_get_option($name) {
    $result = get_theme_mod($name);
    if ($result === false) {
        global $theme_default_options;
        $result = isset($theme_default_options[$name]) ? $theme_default_options[$name] : false;
    }
    return $result;
}