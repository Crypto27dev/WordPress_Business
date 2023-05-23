<?php
/**
 * Theme only works in WordPress 4.4 or later.
 */
if ( version_compare( $GLOBALS['wp_version'], '4.4-alpha', '<' ) ) {
	require get_template_directory() . '/inc/back-compat.php';
}

locate_template(array('library/options.php'), true);
locate_template(array('library/navigation.php'), true);
locate_template(array('library/sidebars.php'), true);
locate_template(array('library/widgets.php'), true);
locate_template(array('library/seo.php'), true);
locate_template(array('library/app.php'), true);
locate_template(array('library/breadcrumbs.php'), true);
locate_template(array('library/theme-wizard/config.php'), true);
locate_template(array('library/translations/class-theme-multi-languages.php'), true);

if (!function_exists('theme_setup')) {
    /**
     * Sets up theme defaults and registers support for various WordPress features.
     *
     * Note that this function is hooked into the after_setup_theme hook, which
     * runs before the init hook. The init hook is too late for some features, such
     * as indicating support for post thumbnails.
     *
     * Create your own theme_setup() function to override in a child theme.
     */
    function theme_setup() {
        // Add default posts and comments RSS feed links to head.
        add_theme_support('automatic-feed-links');

        /*
         * Let WordPress manage the document title.
         * By adding theme support, we declare that this theme does not use a
         * hard-coded <title> tag in the document head, and expect WordPress to
         * provide it for us.
         */
        add_theme_support('title-tag');

        /*
         * Enable support for custom logo.
         */
        add_theme_support('custom-logo', array(
            'height' => 240,
            'width' => 240,
            'flex-height' => true,
        ));

        /*
         * Enable support for Post Thumbnails on posts and pages.
         *
         * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
         */
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(1200, 9999);

        /*
         * Switch default core markup for search form, comment form, and comments
         * to output valid HTML5.
         */
        add_theme_support('html5', array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ));

        /*
         * Enable support for Post Formats.
         *
         * See: https://codex.wordpress.org/Post_Formats
         */
        add_theme_support('post-formats', array(
            'aside',
            'image',
            'video',
            'quote',
            'link',
            'gallery',
            'status',
            'audio',
            'chat',
        ));

        // Indicate widget sidebars can use selective refresh in the Customizer.
        add_theme_support('customize-selective-refresh-widgets');

        // Add theme version for compatibility with plugin
        $GLOBALS['npThemeVersion'] = '5.9.14';
    }

    add_action('after_setup_theme', 'theme_setup');
}


/**
 * Handles JavaScript detection.
 *
 * Adds a `js` class to the root `<html>` element when JavaScript is detected.
 */
function theme_javascript_detection() {
    echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action('wp_head', 'theme_javascript_detection', 0);

/**
 * Enqueues scripts and styles.
 */
function theme_scripts() {
    $version = wp_get_theme()->get('Version');

    $post = get_post();
    if (!$post) {
        global $post;
    }
    $post_id = isset($post->ID) ? $post->ID : 0;
    if (function_exists('np_data_provider') && class_exists('NpMetaOptions')) {
        $data_provider = np_data_provider($post_id);
        $is_np_page = method_exists('NpDataProvider','isNp') ? $data_provider->isNp() : $data_provider->isNicepage();
        $np_header_footer = NpMetaOptions::get($post_id, 'np_template') === 'html' ? true : false;
        if ($np_header_footer && (is_search() || is_single() || is_home() || is_category() || is_tag() || is_archive())) {
            $np_header_footer = false;
        }
    } else {
        $is_np_page = false;
        $np_header_footer = false;
    }

    if (!$is_np_page || $is_np_page && !$np_header_footer) {
        // Theme stylesheet.
        wp_enqueue_style('theme-style', get_stylesheet_uri(), array(), $version);
        wp_enqueue_style('theme-media', get_template_directory_uri() . '/css/media.css', array('theme-style'), $version);

        if (theme_get_option('include_jquery')) {
            wp_dequeue_script("nicepage-jquery");
            wp_register_script('theme-jquery', get_template_directory_uri() . '/js/jquery.js', array(), '1.9.1');
            wp_enqueue_script('theme-jquery');
            wp_enqueue_script('theme-script', get_template_directory_uri() . '/js/script.js', array('theme-jquery'), $version);
        } else {
            wp_enqueue_script('theme-script', get_template_directory_uri() . '/js/script.js', array('jquery'), $version);
        }

        // remove plugin's scripts and styles
        wp_dequeue_style("nicepage-style");
        wp_dequeue_style("nicepage-responsive");
        wp_dequeue_style("nicepage-media");
        wp_dequeue_script("nicepage-script");
    }

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    if (is_singular() && wp_attachment_is_image()) {
        wp_enqueue_script('theme-keyboard-image-navigation', get_template_directory_uri() . '/js/keyboard-image-navigation.js', array('jquery'), '20160816');
    }

    wp_localize_script('theme-script', 'screenReaderText', array(
        'expand' => __('expand child menu', 'website4829605'),
        'collapse' => __('collapse child menu', 'website4829605'),
    ));
}
add_action('wp_enqueue_scripts', 'theme_scripts', 1002);


/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 *
 * @return array (Maybe) filtered body classes.
 */
function theme_body_classes($classes) {
    // Adds a class of custom-background-image to sites with a custom background image.
    if (get_background_image()) {
        $classes[] = 'custom-background-image';
    }

    // Adds a class of group-blog to sites with more than 1 published author.
    if (is_multi_author()) {
        $classes[] = 'group-blog';
    }

    // Adds a class of no-sidebar to sites without active sidebar.
    $classes[] = 'no-sidebar';

    // Adds a class of hfeed to non-singular pages.
    if (!is_singular()) {
        $classes[] = 'hfeed';
    }
    global $post;
    if (isset($post) && $post && !is_base_template($post)) {
        $classes[] = 'u-body u-xl-mode'; // need for typography in other pages
    }
    return $classes;
}
add_filter('body_class', 'theme_body_classes');

/**
 * Converts a HEX value to RGB.
 *
 * @param string $color The original color, in 3- or 6-digit hexadecimal form.
 *
 * @return array Array containing RGB (red, green, and blue) values for the given
 *               HEX code, empty array otherwise.
 */
function theme_hex2rgb($color) {
    $color = trim($color, '#');

    if (strlen($color) === 3) {
        $r = hexdec(substr($color, 0, 1) . substr($color, 0, 1));
        $g = hexdec(substr($color, 1, 1) . substr($color, 1, 1));
        $b = hexdec(substr($color, 2, 1) . substr($color, 2, 1));
    } else if (strlen($color) === 6) {
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
    } else {
        return array();
    }

    return array('red' => $r, 'green' => $g, 'blue' => $b);
}

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Add custom image sizes attribute to enhance responsive image functionality
 * for content images
 *
 * @param string $sizes A source size value for use in a 'sizes' attribute.
 * @param array  $size  Image size. Accepts an array of width and height
 *                      values in pixels (in that order).
 *
 * @return string A source size value for use in a content image 'sizes' attribute.
 */
function theme_content_image_sizes_attr($sizes, $size) {
    $width = $size[0];

    840 <= $width && $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 62vw, 840px';

    if ('page' === get_post_type()) {
        840 > $width && $sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
    } else {
        840 > $width && 600 <= $width && $sizes = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 984px) 61vw, (max-width: 1362px) 45vw, 600px';
        600 > $width && $sizes = '(max-width: ' . $width . 'px) 85vw, ' . $width . 'px';
    }

    return $sizes;
}
add_filter('wp_calculate_image_sizes', 'theme_content_image_sizes_attr', 10, 2);

/**
 * Add custom image sizes attribute to enhance responsive image functionality
 * for post thumbnails
 *
 * @param array $attr Attributes for the image markup.
 * @param int   $attachment Image attachment ID.
 * @param array $size Registered image size or flat array of height and width dimensions.
 *
 * @return string A source size value for use in a post thumbnail 'sizes' attribute.
 */
function theme_post_thumbnail_sizes_attr($attr, $attachment, $size) {
    if ('post-thumbnail' === $size) {
        $attr['sizes'] = '(max-width: 709px) 85vw, (max-width: 909px) 67vw, (max-width: 1362px) 88vw, 1200px';
    }
    return $attr;
}
add_filter('wp_get_attachment_image_attributes', 'theme_post_thumbnail_sizes_attr', 10, 3);

/**
 * Modifies tag cloud widget arguments to have all tags in the widget same font size.
 *
 * @param array $args Arguments for tag cloud widget.
 *
 * @return array A new modified arguments.
 */
function theme_widget_tag_cloud_args($args) {
    $args['largest'] = 1;
    $args['smallest'] = 1;
    $args['unit'] = 'em';
    return $args;
}
add_filter('widget_tag_cloud_args', 'theme_widget_tag_cloud_args');


/**
 * Sets the content width in pixels, based on the theme's design and stylesheet.
 *
 * @global int $content_width
 */
function theme_content_width() {
	$GLOBALS['content_width'] = apply_filters('theme_content_width', 1140);
}
add_action('after_setup_theme', 'theme_content_width');

function theme_widgets_init2() {
    register_sidebar(array(
        'name'          => __('Primary Widget Area', 'website4829605'),
        'id'            => 'primary',
        'description'   => __('Add widgets here to appear in your sidebar on blog posts and archive pages.', 'website4829605'),
        'before_widget' => '<widget id="%1$s" name="%1$s" class="widget %2$s">',
        'after_widget'  => '</widget>',
        'after_title'   => '</title>',
        'before_title'  => '<title>',
    ));
}
add_action('widgets_init', 'theme_widgets_init2');

function theme_sidebar($args) {
    ob_start();
    dynamic_sidebar($args['id']);
    $content = ob_get_clean();

    $data = explode('</widget>', $content);
    $widgets = array();
    foreach ($data as $widget) {
        if (!$widget) {
            continue;
        }

        $id = null;
        $name = null;
        $class = null;
        $title = null;

        //for icons and images in the widget content
        if (preg_match('/<\!--np_position_icons_json-->([\s\S]+?)<\!--\/np_position_icons_json-->/', $widget, $iconsProps)) {
            $iconsProps = json_decode(trim(html_entity_decode($iconsProps[1])), true);
            if ($iconsProps && count($iconsProps) > 0) {
                for ($i = 0; $i < count($iconsProps); $i++) {
                    $widget = preg_replace('/{image_src_' . $i . '}/', get_template_directory_uri() . '/images/' . $iconsProps[$i], $widget);
                }
            }
            $widget = preg_replace('/<\!--np_position_icons_json-->([\s\S]+?)<\!--\/np_position_icons_json-->/', '', $widget);
        }

        if (preg_match('/<widget(.*?)>/', $widget, $matches)) {
            if (preg_match('/id="(.*?)"/', $matches[1], $ids)) {
                $id = $ids[1];
            }
            if (preg_match('/name="(.*?)"/', $matches[1], $names)) {
                $name = $names[1];
            }
            if (preg_match('/class="(.*?)"/', $matches[1], $classes)) {
                $class = $classes[1];
            }
            $widget = preg_replace('/<widget[^>]+>/', '', $widget);

            if (preg_match('/<title>(.*)<\/title>/', $widget, $matches)) {
                $title = $matches[1];
                $widget = preg_replace('/<title>.*?<\/title>/', '', $widget);
            }
        }
        $widget = str_replace('<ul class="product-categories">', '<ul>', $widget);

        if (version_compare($GLOBALS['wp_version'], '5.8', '>=') && preg_match('/<\!--widget_title-->([\s\S]+?)<\!--\/widget_title-->/', $widget, $matchesTitle)) {
            $title = $matchesTitle[1];
            $widget = preg_replace('/<\!--widget_title-->.*?<\!--\/widget_title-->/', '', $widget);
        }

        $widgets[] = array(
            'id' => $id,
            'name' => $name,
            'class' => $class,
            'title' => $title,
            'content' => $widget
        );
    }

    $result = '';
    foreach ($widgets as $widget) {
        if (false !== strpos($widget['content'], '{catItemClass}')) {
            $widget['content'] = str_replace('<ul>', '<ul class="u-unstyled">', $widget['content']);
            $widget['content'] = preg_replace('/{catItemClass}/', 'u-categories-item-content ', $widget['content']);
            $widget['content'] = preg_replace('/{catItemStyle}/', 'padding: 10px 0px; margin-bottom: 0px; margin-right: 0px; font-size: 0.875rem;', $widget['content']);
            $widget['content'] = preg_replace('/{catLinkClass}/', 'u-category-link u-button-style u-nav-link ', $widget['content']);
            $widget['content'] = preg_replace('/{catLinkStyle}/', '', $widget['content']);
            $iconHtml = str_replace(array('#icon-categories-open'), array('#icon-categories-closed'), '<span class="u-icon">
                        <svg x="0px" y="0px" style="width: 1em; height: 1em;" viewBox="0 0 16 16" fill-opacity="1">
                            <use xlink:href="#icon-categories-open"></use>
                        </svg>
                    </span>');
            $iconHtmlHide = str_replace(array('fill-opacity="1"'), array('fill-opacity="0"'), $iconHtml);
            $widget['content'] = preg_replace('/<!--icon-html-->/', $iconHtml, $widget['content']);
            $widget['content'] = preg_replace('/<!--icon-html-hide-->/', $iconHtmlHide, $widget['content']);
            $widgetTemplate = str_replace('{block_content}', '<div class="u-categories u-categories-vertical u-block-c0dc-5">{block_content}' . '<svg style="display:none;"><g id="icon-categories-open"><polygon points="12,10 9,6 15,6 "></polygon>
</g><g id="icon-categories-closed"><polygon points="14,8 10,11 10,5 "></polygon>
</g><g id="icon-categories-leaf"><polygon points="14,8 10,11 10,5 "></polygon>
</g></svg>' . '</div>', $args['template']);
        } else {
            $widgetTemplate = $args['template'];
        }
        $widgetHtml = strtr($widgetTemplate, array(
            '{block_content}' => $widget['content'],
            '{block_header}' => $widget['title'],
        ));
        if (!$widget['title']) {
            $widgetHtml = str_replace('u-block-header', 'u-block-header u-hidden', $widgetHtml);
        }
        $result .= $widgetHtml;
    }
    return $result;
}

/**
 * @param  array      $array
 * @param  string|int $key
 * @param  mixed      $default
 * @return mixed
 */
function theme_get_array_value(&$array, $key, $default = false) {
	if (isset($array[$key])) {
		return $array[$key];
	}
	return $default;
}

require_once dirname(__FILE__) . '/library/tgm-activation.php';

if (is_admin()) {
	locate_template(array('library/content-import.php'), true);
}

theme_include_lib('post_templates.php');

function theme_include_lib($name, $dir = 'library') {
    locate_template(array($dir . '/' . $name), true);
}

global $theme_custom_templates;
$theme_custom_templates = array();

theme_include_lib('templates.php', 'templates');

/*
 * Include the template depends on value stored in database
 *
 * @global array $theme_custom_templates
 *
 * @param string $type The type of template (Home, Products, 404, ect)
 * @param string $default_name Name of the template
 */
function theme_load_template($type, $default_name) {
    global $theme_custom_templates;
    $name = theme_get_selected_template($type);
    if (!$name)
        $name = $default_name;

    $path = theme_get_array_value($theme_custom_templates[$type], $name, $theme_custom_templates[$type][$default_name]);
    require_once(get_template_directory() . '/' . $path);
}

function theme_register_template($type, $name, $path) {
    global $theme_custom_templates;
    if (!isset($theme_custom_templates[$type]))
        $theme_custom_templates[$type] = array();
    $theme_custom_templates[$type][$name] = $path;
}

function theme_get_selected_template($type) {
    $type = sanitize_title_with_dashes($type);
    if (isset($_GET['custom_template'])) {
        return $_GET['custom_template'];
    }
    return theme_template_get_option('theme_template_' . get_option('stylesheet') . '_' . $type);
}


function theme_is_empty_html($str) {
	return (!is_string($str) || strlen(str_replace(array('&nbsp;', ' ', "\n", "\r", "\t"), '', $str)) == 0);
}

define('THEME_MORE_TOKEN', '%%theme_more%%');
define('THEME_TAG_TOKEN', '%%theme_tag_token%%');
define('THEME_TOKEN_TYPE_WORD', 0);
define('THEME_TOKEN_TYPE_TAG', 1);
define('THEME_TOKEN_TYPE_SPACE', 2);
define('THEME_TOKEN_TYPE_IGNORE', 3);

if (theme_get_option('excerpt_strip_shortcodes')) {
    add_filter('get_the_excerpt', 'strip_shortcodes');
}

function theme_trim_long_str($str, $len = 50) {
    $excerpt = theme_create_excerpt($str, $len, 0, true);
    if ($excerpt) {
        return force_balance_tags($excerpt . '&hellip;');
    }
    return $str;
}

function theme_create_excerpt($excerpt, $max_tokens_count, $min_remainder, $count_symbols = false) {
	$content_parts = explode(THEME_TAG_TOKEN, str_replace(array('<', '>'), array(THEME_TAG_TOKEN . '<', '>' . THEME_TAG_TOKEN), $excerpt));
	$content = array();
	$tokens_count = 0;
	$style_balance = 0;
	$script_balance = 0;
	foreach ($content_parts as $part) {
		if (strpos($part, '<') !== false || strpos($part, '>') !== false) {
			if ($part === '<style>') {
				$style_balance++;
			} else if ($part === '</style>') {
				$style_balance--;
			} else if ($part === '<script>') {
				$script_balance++;
			} else if ($part === '</script>') {
				$script_balance--;
			}
			$content[] = array(THEME_TOKEN_TYPE_TAG, $part);
		} else {
			$all_chunks = preg_split('/([\s])/u', $part, -1, PREG_SPLIT_DELIM_CAPTURE);
			foreach ($all_chunks as $chunk) {
				if ('' != trim($chunk)) {
					if ($style_balance > 0 || $script_balance > 0) {
						$content[] = array(THEME_TOKEN_TYPE_IGNORE, $chunk);
					} else {
						$content[] = array(THEME_TOKEN_TYPE_WORD, $chunk);
						$tokens_count += $count_symbols ? mb_strlen($chunk) : 1;
					}
				} elseif ($chunk != '') {
                    $tokens_count += $count_symbols ? 1 : 0;
					$content[] = array(THEME_TOKEN_TYPE_SPACE, $chunk);
				}
			}
		}
	}

	if ($max_tokens_count < $tokens_count && $max_tokens_count + $min_remainder <= $tokens_count) {
		$current_count = 0;
		$excerpt = '';
		foreach ($content as $node) {
			if ($node[0] === THEME_TOKEN_TYPE_WORD) {
				$current_count += $count_symbols ? mb_strlen($node[1]) : 1;
			} else {
                $current_count += $count_symbols ? 1 : 0;
            }
            if ($current_count >= $max_tokens_count && $excerpt) { // leave at least 1 token
                break;
            }
			$excerpt .= $node[1];
		}
		return $excerpt;
	}
	return false;
}

function theme_get_excerpt($args = array()) {
	$excerpt = _theme_get_excerpt($args);
	return $excerpt;
}

function _theme_get_excerpt($args = array()) {
	global $post;
    $count_symbols                = isset($args['count_symbols'])                ? $args['count_symbols']                : false;
	$more_tag                     = isset($args['more_tag'])                     ? $args['more_tag']                     : __('Read more', 'website4829605');
	$auto                         = isset($args['auto'])                         ? $args['auto']                         : theme_get_option('excerpt_auto');
	$units_count                  = isset($args['units_count'])                  ? $args['units_count']                  : theme_get_option('excerpt_words');

	$min_remainder = isset($args['min_remainder']) ? $args['min_remainder'] : theme_get_option('excerpt_min_remainder');
	$allowed_tags  = isset($args['allowed_tags'])  ? $args['allowed_tags']  :
		(theme_get_option('excerpt_use_tag_filter')
			? explode(',', str_replace(' ', '', theme_get_option('excerpt_allowed_tags')))
			: null);

	$permalink = get_permalink($post->ID);
	$show_more_tag = false;
	$tag_disbalance = false;
	if (post_password_required($post)) {
		return get_the_excerpt();
	}
	if ($auto && has_excerpt($post->ID)) {
		$excerpt = get_the_excerpt();
		$show_more_tag = strlen($post->post_content) > 0;
	} else {
        if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
            $excerpt = get_the_content_php_7_2();
        } else {
            $excerpt = get_the_content(THEME_MORE_TOKEN);
        }
		if (theme_get_option('excerpt_strip_shortcodes')) {
			$excerpt = strip_shortcodes($excerpt);
		}
        $excerpt = apply_filters('the_content', $excerpt);

		global $multipage;
		if ($multipage && strpos($excerpt, THEME_MORE_TOKEN) === false) {
			$show_more_tag = true;
		}
		if (theme_is_empty_html($excerpt)) {
            return $excerpt;
        }
		if (get_post_meta($post->ID, '_np_html', true)) {
            $excerpt = apply_filters('np_create_excerpt', $excerpt);
        } else if ($allowed_tags !== null) {
			$allowed_tags = '<' . implode('><', $allowed_tags) . '>';
			$excerpt = strip_tags($excerpt, $allowed_tags . '<style><script>');
		}

		if (strpos($excerpt, THEME_MORE_TOKEN) !== false) {
			$excerpt = str_replace(THEME_MORE_TOKEN, '', $excerpt);
			$show_more_tag = true;
		} else if ($auto && is_numeric($units_count)) {
			$units_count = intval($units_count);
			$min_remainder = intval($min_remainder);

			$new_excerpt = $units_count > 0
                ?   theme_create_excerpt($excerpt, $units_count, $min_remainder, $count_symbols)
                : '';

			if (is_string($new_excerpt)) {
				$excerpt = $new_excerpt;
				$show_more_tag = true;
				$tag_disbalance = true;
			}
		}
	}
    if ($excerpt && $show_more_tag && theme_get_option('show_morelink')) {
        $excerpt = $excerpt . ' ' . str_replace(array('[url]', '[text]'), array($permalink, $more_tag), theme_get_option('morelink_template'));
    }
	if ($tag_disbalance) {
		$excerpt = force_balance_tags($excerpt);
	}
	return $excerpt;
}

function get_the_content_php_7_2() {
    global $post;
    return apply_filters('the_content', get_post_field('post_content', $post->id));
}

function theme_get_content($args = array()) {
	ob_start();
	the_content();
	return ob_get_clean();
}

function theme_get_category_list() {
	return str_replace(
		'<a',
		'<a class="u-textlink"',
		get_the_category_list(_x(', ', 'Used between list items, there is a space after the comma.', 'website4829605'))
	);
}

function theme_get_tags_list() {
    return get_the_tag_list('', _x( ', ', 'Used between list items, there is a space after the comma.', 'website4829605'));
}

function theme_print_background_images_styles() {
    global $theme_background_images_styles;
    if ($theme_background_images_styles) {
        echo '<style>' . implode("\n", $theme_background_images_styles) . '</style>';
    }
}
add_action('wp_footer', 'theme_print_background_images_styles');

function theme_print_logo_size_styles() {
    $logo_width = trim(get_theme_mod('logo_width'));
    $logo_height = trim(get_theme_mod('logo_height'));
    $style = '';
    if ($logo_width) {
        if (is_numeric($logo_width)) {
            $logo_width .= 'px';
        }
        $style .= "max-width: $logo_width !important;\n";
    }
    if ($logo_height) {
        if (is_numeric($logo_height)) {
            $logo_height .= 'px';
        }
        $style .= "max-height: $logo_height !important;\n";
    }
    if ($style) {
        echo '<style>.u-logo img {' . $style . '}</style>';
    }
}
add_action('wp_head', 'theme_print_logo_size_styles');

function theme_get_logo($args) {
    $logo_src = '';
    $logo_width = '';
    $logo_height = '';
    $default_logo_src = '';
    $default_logo_width = '';
    $default_logo_height = '';

    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        @list($logo_src, $logo_width, $logo_height) = wp_get_attachment_image_src($custom_logo_id, 'full');
    }
    if (!empty($args['default_src'])) {
        if (preg_match('#^(http:|https:|//)#', $args['default_src'])) {
            $default_logo_src = $args['default_src'];
        } else {
            $default_logo_src = get_template_directory_uri() . $args['default_src'];
            @list($default_logo_width, $default_logo_height) = getimagesize(get_template_directory() . $args['default_src']);
        }
    }

    if ($logo_src !== '') {
        $args['default_src'] = $logo_src;
    }
    $parts = explode(".", $args['default_src']);
    $extension = end($parts);
    $isSvgFile = strtolower($extension) == 'svg' ? true : false;

    if ($isSvgFile) {
        $logo_width = '';
        $logo_height = '';
        if ($default_logo_width > $default_logo_height && $default_logo_width) {
            $logo_width = $default_logo_width;
        }
        if ($default_logo_width <= $default_logo_height && $default_logo_height) {
            $logo_height = $default_logo_height;
        }
    }

    if (!$logo_src) {
        $logo_src = $default_logo_src;
        $logo_width = $default_logo_width;
        $logo_height = $default_logo_height;
    }

    $logo_url = theme_get_option('logo_link');
    if (!$logo_url) {
        $logo_url = $args['default_url'];
    }

    return array(
        'src' => $logo_src,
        'url' => $logo_url,
        'default_src' => $default_logo_src,
        'width' => $logo_width,
        'height' => $logo_height,
        'svg' => $isSvgFile,
    );
}

function theme_get_post_image($args) {
    $args = wp_parse_args($args, array(
        'default' => '',
        'class' => '',
        'style' => '',
        'background' => false,
    ));
    $class = $args['class'];
    $link_class = str_replace('u-preserve-proportions', '', $class);
    $style = $args['style'];

    $img_html = get_the_post_thumbnail(null, 'full', array('class' => $class));

    global $theme_background_images_styles;
    $theme_background_images_styles = $theme_background_images_styles ? $theme_background_images_styles : array();

    if (!$img_html) {
        if ($args['background']) {
            $theme_background_images_styles[] = '';
        }
        return '';
    }

    if ($args['background']) {
        $src = '';
        $srcset = '';
        if (preg_match('#\sdata-lazy-src="([^"]*?)"#', $img_html, $m)) {
            $src = $m[1];
        } else if (preg_match('#\ssrc="([^"]*?)"#', $img_html, $m)) {
            $src = $m[1];
        }
        if (preg_match('#\sdata-lazy-srcset="([^"]*?)"#', $img_html, $m)) {
            $srcset = $m[1];
        } else if (preg_match('#\ssrcset="([^"]*?)"#', $img_html, $m)) {
            $srcset = $m[1];
        }

        $selector = '.post-background-image-' . (count($theme_background_images_styles) + 1);
        $items = array();
        foreach (explode(',', $srcset) as $item) {
            $item = trim($item);
            if (preg_match('#^(.*) (\d+)w$#', $item, $m)) {
                $items[] = array((int) $m[2], sprintf('@media (max-width: %spx) {%s{background-image: %surl("%s") !important}}' . "\n", $m[2], $selector, $style, $m[1]));
            }
        }
        rsort($items);

        $background_css = sprintf('%s{background-image: %surl("%s") !important}' . "\n", $selector, $style, $src);
        foreach ($items as $item) {
            $background_css .= $item[1];
        }
        $theme_background_images_styles[] = $background_css;
        return '';
    }
    if (!is_singular()) {
        $link = get_permalink();
        return '<a class="' . $link_class . '" href="' . $link . '">' . $img_html . '</a>';
    } else {
        return $img_html;
    }
}

function theme_switch_post($post) {
	setup_postdata($post);
	$GLOBALS['post'] = $post;
}

function theme_the_post() {
    if (is_singular()) {
        return true;
    }
    global $post;
    @the_post();
    if ($post === null) {
        //if section in the blog have grid with 2 or more elements we need return to render all posts
        return true;
    }
    return !!$post;
}

function theme_print_template_styles($template, $shop = false, $custom_template_number = '') {
    $general_templates = array("header-styles", "footer-styles", "content-none", "content", "biography");
    $custom_template_number = in_array($template, $general_templates) ? '' : $custom_template_number . '/';
    $path = $shop ? "/woocommerce/template-parts/" . $custom_template_number . "$template.php" : "/template-parts/" . $custom_template_number . "$template.php";
    if (file_exists(get_template_directory() . $path)) {
        require get_template_directory() . $path;
    }
}

function theme_blog_content_styles($custom_template_number) {
    $is_shop = false;
    theme_print_template_styles('blog-content-styles', $is_shop, $custom_template_number);
}

function theme_single_content_styles($custom_template_number) {
    $is_shop = false;
    theme_print_template_styles('single-content-styles', $is_shop, $custom_template_number);
}

function theme_product_content_styles($custom_template_number) {
    $is_shop = true;
    theme_print_template_styles('product-content-styles', $is_shop, $custom_template_number);
}

function theme_products_content_styles($custom_template_number) {
    $is_shop = true;
    theme_print_template_styles('products-content-styles', $is_shop, $custom_template_number);
}

function theme_cart_content_styles($custom_template_number) {
    $is_shop = true;
    theme_print_template_styles('cart-content-styles', $is_shop, $custom_template_number);
}

function theme_checkout_content_styles($custom_template_number) {
    $is_shop = true;
    theme_print_template_styles('checkout-content-styles', $is_shop, $custom_template_number);
}

function theme_page_content_styles() {
    theme_print_template_styles('page-content-styles');
}

function theme_header_styles() {
    ob_start();
    theme_print_template_styles('header-styles');
    if (is_customize_preview()) {
        include get_template_directory() . '/css/customizer-site-style.php';
        echo '<style>';
        include get_template_directory() . '/css/customizer-style.css';
        echo '</style>';
    }
    echo strtr(ob_get_clean(), array(
        '.u-header .u-search' => '.u-search',
        'id="color-scheme"' => 'id="theme-color-scheme"',
        'id="typography"' => 'id="theme-typography"',
        'id="font-scheme"' => 'id="theme-font-scheme"',
    ));
}
add_action('theme_header_styles', 'theme_header_styles');

function theme_footer_styles() {
	theme_print_template_styles('footer-styles');
}
add_action('theme_footer_styles', 'theme_footer_styles');


function theme_head_styles() {
    global $post;
    $post_id = isset($post->ID) ? $post->ID : 0;
    $template_page = get_post_meta($post_id, '_np_template', true);
    if ($template_page !== "html" || $template_page === "html" && (is_search() || is_single() || is_home() || is_category() || is_tag() || is_archive())) {
        do_action('theme_header_styles');
        do_action('theme_content_styles');
        do_action('theme_footer_styles');
    }
}
add_action('wp_head', 'theme_head_styles');

remove_action('wp_footer', 'Nicepage::wpFooterAction', 1001);

function theme_app_template($template) {
    if (!$template) {
        $template = 'html-header-footer';
    }
    return $template;
}
add_filter('nicepage_template', 'theme_app_template');

function theme_layout_before($template_key, $default_layout = '', $custom_template_number = '') {
    if (is_singular('post')) {
        $opt = 'post';
    } else if (is_category() || is_search() || is_home()) {
        $opt = 'blog';
    } else if (function_exists('wc_get_product') && is_product()) {
        $opt = 'product';
    } else if (function_exists('wc_get_product') && (is_shop() || is_product_category())) {
        $opt = 'products';
    } else if (function_exists('wc_get_product') && is_cart()) {
        $opt = 'cart';
    } else if (function_exists('wc_get_product') && is_checkout()) {
        $opt = 'checkout';
    } else {
        $opt = 'default';
    }
    $layout = theme_get_option("sidebars_layout_$opt");
    if (!$layout) {
        $layout = $default_layout;
    }
    global $theme_current_layout;
    $theme_current_layout = $template_key . ($layout ? '-' : '') . $layout;
    global $theme_custom_template_number;
    $theme_custom_template_number= $custom_template_number;
    if ($opt === 'product' || $opt === 'products' || $opt === 'cart' || $opt === 'checkout') {
        $path = "woocommerce/template-parts/". $theme_custom_template_number . "/layouts/$theme_current_layout-before";
    } else {
        $path = "template-parts/". $theme_custom_template_number . "/layouts/$theme_current_layout-before";
    }
    get_template_part($path);
}

function theme_layout_after() {
    global $theme_current_layout, $theme_custom_template_number;
    if (function_exists('wc_get_product') && (is_product() || (is_shop() || is_product_category()) || is_cart() || is_checkout())) {
        $path = "woocommerce/template-parts/". $theme_custom_template_number . "/layouts/$theme_current_layout-after";
    } else {
        $path = "template-parts/". $theme_custom_template_number . "/layouts/$theme_current_layout-after";
    }
    get_template_part($path);
}

function body_style_attribute() {
    $style = apply_filters('add_body_style_attribute', '');
    echo 'style="' . $style . '"';
}

function body_data_attributes() {
    $data_atributes = apply_filters('add_body_data_attributes', '');
    echo $data_atributes;
}

function back_to_top() {
    echo apply_filters('add_back_to_top', '');
}
function woocommerce_init_np() {
    include_once get_template_directory() . '/library/class-wc-np.php';
}
if (function_exists('wc_get_product')) {
    woocommerce_init_np();
}

function inputs_in_string($inputs_in_string) {
    if (stripos($inputs_in_string[0], 'class') == true && stripos($inputs_in_string[0], 'u-input') == false && stripos($inputs_in_string[0], 'u-btn') == false) {
        if (stripos($inputs_in_string[0], 'button') == false && stripos($inputs_in_string[0], 'submit') == false) {
            $inputs_in_string[0] = str_replace('class="', 'class="u-input ', $inputs_in_string[0]);
        }
        else {
            $inputs_in_string[0] = str_replace('class="', 'class="u-btn u-button-style ', $inputs_in_string[0]);
        }
    }
    elseif (stripos($inputs_in_string[0], 'class') == false) {
        if (stripos($inputs_in_string[0], 'button') == true || stripos($inputs_in_string[0], 'submit') == true) {
            $inputs_in_string[0] = str_replace('>', 'class="u-btn u-button-style">', $inputs_in_string[0]);
        }
        else {
            $inputs_in_string[0] = str_replace('>', ' class="u-input">', $inputs_in_string[0]);
        }
    }
    return $inputs_in_string[0];
}

function buttons_in_string($buttons_in_string) {
    if (stripos($buttons_in_string[0], 'class') == true) {
        $buttons_in_string[0] = str_replace('class="', 'class="u-btn u-button-style ', $buttons_in_string[0]);
    }
    else {
        $buttons_in_string[0] = str_replace('>', ' class="u-btn u-button-style">', $buttons_in_string[0]);
    }
    return $buttons_in_string[0];
}

function textarea_in_string($textarea_in_string) {
    if (stripos($textarea_in_string[0], 'class') == true) {
        $textarea_in_string[0] = str_replace('class="', 'class="u-input ', $textarea_in_string[0]);
    }
    else {
        $textarea_in_string[0] = str_replace('>', ' class="u-input">', $textarea_in_string[0]);
    }
    return $textarea_in_string[0];
}

function stylingDefaultControls($content) {
    $content = preg_replace_callback('/<input[^>]+>/', 'inputs_in_string', $content);
    $content = preg_replace_callback('/<button[^>]+>/', 'buttons_in_string', $content);
    $content = preg_replace_callback('/<textarea[^>]+>/', 'textarea_in_string', $content);
    return $content;
}

require_once get_template_directory() . '/library/class-cforms.php';

function processForms($content, $template) {
    preg_match_all('#<form\s[^>]*?[\s]source="contact7"[\s\S]*?<\/form>#', $content, $matches);
    $created_forms = CForms::_updateForms($matches[0], $template);
    $count_forms = count($created_forms);
    for ($i = 0; $i < $count_forms; $i++) {
        if($created_forms[$i]['id']) {
            $content = str_replace($matches[0][$i], CForms::getHtml($created_forms[$i]['id'], $matches[0][$i]), $content);
        }
    }
    return $content;
}

function renderTemplate($templateHtml, $template, $return, $templateName) {
    $templateHtml = processForms($templateHtml, $templateName);
    $params = get_option('np_page_ids');
    if ($params) {
        foreach($params as $key => $value) {
            if ($template === "edit") {
                $templateHtml = str_replace($key, get_permalink($value), $templateHtml);
            } else {
                $templateHtml = str_replace("[page_" . $key . "]", get_permalink($value), $templateHtml);
            }
        }
    }

    $blogUrl = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : get_home_url();
    $templateHtml = preg_replace('/\[blog_[0-9]*?\]/', $blogUrl, $templateHtml);

    if ($return === 'return') {
        return $templateHtml;
    } else {
        echo $templateHtml;
    }
}

function theme_woocommerce_mini_cart() {
    if (function_exists('woocommerce_mini_cart')) {
        woocommerce_mini_cart();
    } else {
        echo '[woocommerce_cart]';
    }
}

function getProductVariationOptionTitle($variation_option) {
    if (is_string($variation_option)) {
        return $variation_option;
    }
    return $variation_option_title = $variation_option->name ? strtolower($variation_option->name) : '';
}

function getProductVariationOptionSlug($variation_option) {
    if (is_string($variation_option)) {
        return $variation_option;
    }
    return $variation_option_title = $variation_option->slug ? $variation_option->slug : '';
}

function getProductButtonHtml ($button_html, $product, $clickTypeProductbutton = 'add-to-cart', $contentProductbutton = '') {
    if ($clickTypeProductbutton === 'go-to-page') {
        $button_html = str_replace('<a', '<a href="' . get_permalink($product->get_id()) .'"', $button_html);
    } else {
        $button_html = str_replace('<a', '<a type="submit" value="' . get_the_ID() . '" name="add-to-cart"', $button_html);
    }
    $buttonText = sprintf(__('Add to cart', 'website4829605'));
    if ($contentProductbutton !== '') {
        $buttonText =  sprintf(__($contentProductbutton, 'website4829605'));
    }
    $button_html = str_replace('{AddToCartText}', $buttonText, $button_html);
    return $button_html;
}

function getProductDesc($product) {
    $product_id  = $product->get_id();
    return $desc = theme_trim_long_str(getTheExcerpt($product_id), 150);
}

function getTheExcerpt($post_id) {
    global $post;
    $save_post = $post;
    $post = get_post($post_id);
    if (has_excerpt($post_id)) {
        $output = get_the_excerpt();
    } else {
        $output = getExcerptById($post_id, 150);
    }
    $post = $save_post;
    return $output;
}

function getExcerptById($post_id, $length) {
    $the_post = get_post($post_id); //Gets post ID
    $the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
    $excerpt_length = $length; //Sets excerpt length by word count
    $the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
    $words = explode(' ', $the_excerpt, $excerpt_length + 1);

    if(count($words) > $excerpt_length) :
        array_pop($words);
        array_push($words, '&hellip;');
        $the_excerpt = implode(' ', $words);
    endif;

    $the_excerpt = '<p>' . $the_excerpt . '</p>';

    return $the_excerpt;
}
add_action('pre_get_posts',  'set_posts_per_page');
function set_posts_per_page( $query ) {
    global $wp_the_query;
    $count_posts = '{count_posts}';
    if (!is_numeric($count_posts)) {
        return $query;
    }
    if ((!is_admin())
        && ($query === $wp_the_query)
        && ($query->is_home() || is_category() || is_tag() || is_archive())
        && !isset($query->query['post_type']) && !isset($query->query['product_cat'])) {
        $query->set('posts_per_page', $count_posts);
    }
    return $query;
}

function getGridAutoRowsStyles($json, $itemsCount) {
    $options = json_decode($json, true);
    $gridProps = isset($options['gridProps']) ? $options['gridProps'] : array();
    return buildGridAutoRowsStyles($gridProps, $itemsCount);
}

function buildGridAutoRowsStyles($props, $itemsCount) {
    $stylesResult = '';
    foreach ($props as $prop) {
        $autoRows = calcGridAutoRows(
            array(
                'items' => $itemsCount,
                'columns' => $prop['columns'],
                'gap' => $prop['gap']
            )
        );
        $stylesResult .= str_replace('[[' . $prop['mode'] . '_VALUE]]', $autoRows, $prop['styles']);
    }
    return $stylesResult ? '<style>' . $stylesResult . '</style>' : '';
}

function calcGridAutoRows($params = array()) {
    $rows = isset($params['rows']) ? $params['rows'] : null;
    $columns = isset($params['columns']) ? $params['columns'] : 1;

    if (!$rows) {
        $rows = ceil($params['items'] / $columns);
    }

    $gap = floatval($params['gap']);
    $gapMultiplier = $gap * ($rows - 1) / $rows;
    $autoRowsValue = (floor(100 / $rows * 100) / 100) . '%';

    return $gapMultiplier > 0 ? 'calc(' . $autoRowsValue . ' - ' . $gapMultiplier . 'px)' : $autoRowsValue;
}

function theme_404_content($args = '') {
    $args = wp_parse_args($args, array(
        'error_title' => __('Nothing here', 'website4829605'),
        'error_message' => __('It looks like nothing was found at this location. Maybe try a search?', 'website4829605')
    ));
    extract($args);
    echo '<p>' . $args['error_title'] . '</p>'; ?>
    <div class="page404text" style="font-size: 1rem;"> <?php
        echo '<p class="center">' . $args['error_message'] . '</p>';
        if (theme_get_option('theme_show_random_posts_on_404_page')) {
            ob_start();
            echo '<h4 class="box-title">' . theme_get_option('theme_show_random_posts_title_on_404_page') . '</h4>';
            ?>
            <ul>
                <?php
                global $post;
                $rand_posts = get_posts('numberposts=5&orderby=rand');
                foreach ($rand_posts as $post) :
                    ?>
                    <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
                <?php endforeach; ?>
            </ul>
            <?php
            echo ob_get_clean();
        }
        if (theme_get_option('theme_show_tags_on_404_page')) {
            ob_start();
            echo '<h4 class="box-title">' . theme_get_option('theme_show_tags_title_on_404_page') . '</h4>';
            wp_tag_cloud('smallest=9&largest=22&unit=pt&number=200&format=flat&orderby=name&order=ASC');
            echo ob_get_clean();
        } ?>
        <style>
            .page404text .search-field {
                width: 80%;
            }
        </style>
        <?php get_search_form(); ?>
    </div>
    <?php
}

add_filter('lostpassword_url', 'default_lostpassword_url', 10, 0);
function default_lostpassword_url() {
    return site_url('wp-login.php?action=lostpassword');
}

function is_wplogin () {
    $objectQuery = get_queried_object();
    if (!is_user_logged_in() && $objectQuery && isset($objectQuery->post_name) && in_array($objectQuery->post_name, array('login', 'lostpassword', 'register'))) {
        return true;
    }
    return false;
}

function is_not_post_template($post) {
    $result = false;
    if (function_exists('wc_get_product') && (is_cart() || is_shop() || is_product() || is_checkout())
        || isset($post) && post_password_required($post->ID)) {
        $result = true;
    }
    return $result;
}

function is_base_template($post) {
    $result = false;
    if (function_exists('wc_get_product') && (is_cart() || is_shop() || is_product_category() || is_product() || is_checkout())
        || isset($post) && post_password_required($post->ID) || is_category() || is_search() || is_home() || is_singular('post')) {
        $result = true;
    }
    return $result;
}

/**
 * Add recaptcha script to theme
 */
function add_recaptcha_script() {
    ob_start(); ?>
    
    <?php $recaptcha_script = trim(ob_get_clean());
    if ($recaptcha_script) {
        $GLOBALS['npThemeRecaptcha'] = true;
        echo $recaptcha_script;
    }
}
add_action('wp_head', 'add_recaptcha_script');

/**
 * Get categories html
 *
 * @param $args
 * @return string
 */
function get_categories_html($args) {
    $categories_html = '';
    $category = get_queried_object();
    $category_id = isset($category->term_id) ? $category->term_id : 0;
    $showIcon = 'fill-opacity="1"';
    $hideIcon = 'fill-opacity="0"';
    $linkTitle = '{content}';
    $linkUrl = '{url}';
    $isActiveLi = '{activeLi}';
    $isActiveLink = '{activeLink}';
    $iconOpen = '#icon-categories-open';
    $iconClosed = '#icon-categories-closed';
    $liOpen = 'u-expand-open';
    $liClosed = 'u-expand-closed';
    $categoryCheck = $args['cmsTemplate'] === 'blog' ? is_category() : is_product_category();
    if ($category_id) {
        if ($categoryCheck) {
            // add back link
            if ($category->parent) {
                // parent cat url
                $blogCatUrl = esc_url(get_category_link($category->parent));
                $shopCatUrl = get_term_link($category->parent, 'product_cat');
                $linkHref = $args['cmsTemplate'] === 'blog' ? $blogCatUrl : $shopCatUrl;
            } else {
                //blog url or shop url
                $blogUrl = get_option('page_for_posts') ? get_permalink(get_option('page_for_posts')) : get_home_url();
                $shopUrl = get_permalink( wc_get_page_id( 'shop' ) );
                $linkHref = $args['cmsTemplate'] === 'blog' ? $blogUrl : $shopUrl;
            }
            $backIcon = $args['type'] === 'horizontal' ? '&#10094; &nbsp;' : '';
            $categories_html .= str_replace(
                array($linkTitle, $linkUrl, $isActiveLi, $isActiveLink, $showIcon),
                array($backIcon . __( 'Back' ), $linkHref, '', '', $hideIcon),
                $args['itemTemplate']
            );
        }
        $subCats_html = getSubCatHtml($category_id, $args);
        // add current cat link
        $needShowIcon = is_category_has_child($category);
        $catName = esc_attr($category->name);
        $catLink = esc_url(get_term_link($category));
        $categories_html .= str_replace(
            array($linkTitle, $linkUrl, $isActiveLi, $isActiveLink, $iconOpen, $liOpen, $showIcon, '</li>'),
            array($catName, $catLink, 'u-active', 'active', $iconClosed, $liClosed, $needShowIcon,  $subCats_html . '</li>'),
            $args['itemTemplate']
        );
        if ($args['type'] === 'horizontal') {
            $categories_html .= getSubCatHtml($category_id, $args, true);
        }
    } else {
        $template_cats = np_get_categories($category_id, $args['cmsTemplate']);
        if($template_cats) {
            foreach($template_cats as $template_category) {
                $needShowIcon = is_category_has_child($template_category);
                $catName = esc_attr($template_category->name);
                $catLink = esc_url(get_term_link($template_category));
                if ($needShowIcon === $showIcon) {
                    $subCats_html = getSubCatHtml($template_category->term_id, $args);
                } else {
                    $subCats_html = '';
                }
                $categories_html .= str_replace(
                    array($linkTitle, $linkUrl, $isActiveLi, $isActiveLink, $showIcon, $iconOpen, $liOpen, '</li>'),
                    array($catName, $catLink, '', '', $needShowIcon, $iconClosed, $liClosed, $subCats_html . '</li>'),
                    $args['itemTemplate']
                );
            }
        }
    }
    $categories_html = strtr($args['template'], array('{categories}' => $categories_html));
    return $categories_html;
}

function getSubCatHtml($catId, $args, $onlyItems=false) {
    $subCats_html = '';
    $sub_cats = np_get_categories($catId, $args['cmsTemplate']);
    if (count($sub_cats) > 0) {
        foreach($sub_cats as $sub_category) {
            $subCats_html .= str_replace(
                array('{content}', '{url}', '{activeLi}', '{activeLink}', 'fill-opacity="1"'),
                array(esc_attr($sub_category->name), esc_url(get_term_link($sub_category)), '', '', 'fill-opacity="0"'),
                $args['itemTemplate']
            );
        }
        if (!$onlyItems) {
            $subCats_html = '<ul class="u-unstyled">' . $subCats_html . '</ul>';
        }
    }
    return $subCats_html;
}

function is_category_has_child($category) {
    $term_childs = get_term_children( $category->term_id, $category->taxonomy );
    return count($term_childs) > 0 ? 'fill-opacity="1"' : 'fill-opacity="0"';
}

function np_get_categories($category_id, $cmsTemplate='blog') {
    $sub_args = array(
        'orderby'      => 'id',
        'taxonomy'     => $cmsTemplate === 'blog' ? 'category' : 'product_cat',
        'parent'       => $category_id,
        'child_of'     => 0,
        'show_count'   => 0,
        'pad_counts'   => 0,
        'hierarchical' => 0,
        'title_li'     => '',
        'hide_empty'   => 1
    );
    return get_categories( $sub_args );
}

add_filter( 'widget_categories_args', 'widget_categories_args_filter', 10, 1 );
if (class_exists('woocommerce')) {
    add_filter( 'woocommerce_product_categories_widget_args', 'widget_categories_args_filter', 10, 1 );
}
function widget_categories_args_filter($args) {
    return array_merge( $args, array(
        'walker' => new Walker_Categories_Widget(),
    ));
}