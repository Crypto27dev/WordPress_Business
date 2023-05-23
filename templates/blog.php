<?php
global $blog_custom_template;
$blog_custom_template = 'blogTemplate';
$language = isset($_GET['lang']) ? $_GET['lang'] : '';

add_action(
    'theme_content_styles',
    function () use ($blog_custom_template) {
        theme_blog_content_styles($blog_custom_template);
    }
);

function theme_index_body_class_filter($classes) {
    $classes[] = 'u-body u-xl-mode';
    return $classes;
}
add_filter('body_class', 'theme_index_body_class_filter');

function theme_index_body_style_attribute() {
    return "";
}
add_filter('add_body_style_attribute', 'theme_index_body_style_attribute');

function theme_index_body_back_to_top() {
    ob_start(); ?>
    
    <?php
    return ob_get_clean();
}
add_filter('add_back_to_top', 'theme_index_body_back_to_top');

function theme_index_get_local_fonts() {
    return '';
}
add_filter('get_local_fonts', 'theme_index_get_local_fonts');

get_header();

theme_layout_before('blog', '', $blog_custom_template);
?>

<?php if (is_home() && ! is_front_page()) : ?>
    <section>
        <div class="u-sheet">
            <header>
                <h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
            </header>
        </div>
    </section>
<?php endif; ?>

<?php if (is_search()) : ?>
    <section>
        <div class="u-sheet">
            <header class="page-header">
                <h1 class="page-title"><?php printf(__('Search results for %s', 'website4829605'), '<span>' . esc_html(get_search_query()) . '</span>'); ?></h1>
            </header>
        </div>
    </section>
<?php endif; ?>

<?php
if (have_posts()) {

    global $wp_query;
    $first_repeatable = 0;
    $last_repeatable = 0;

    $template_used = array();
    $templates_count = 1;

    $blog_sections_count = $last_repeatable + 1;

    if ($blog_sections_count) {
        for ($template_idx = 0; $template_idx < $templates_count; $template_idx++) {
            if ($template_idx < $first_repeatable && !empty($template_used[$template_idx])) {
                if ($blog_sections_count == $first_repeatable) {
                    break;
                } else {
                    continue;
                }
            }
            $template_used[$template_idx] = true;

            $is_singular = is_singular();
            if ($is_singular) {
                the_post();
            }
            $translations = '';
            if ($language) {
                if (file_exists(get_stylesheet_directory() . '/template-parts/'. $blog_custom_template . '/translations/' . $language .'/post-content-' . ($template_idx + 1) . '.php')) {
                    $translations = '/translations/' . $language;
                }
            }
            $blog_content_path = get_stylesheet_directory() . '/template-parts/'. $blog_custom_template . $translations . '/post-content-' . ($template_idx + 1) . '.php';
            ob_start();
            if (file_exists($blog_content_path)) {
                include $blog_content_path;
            }
            $content = ob_get_clean();
            if (function_exists('renderTemplate')) {
                renderTemplate($content, '', 'echo', 'custom');
            } else {
                echo $content;
            }

            if ($is_singular && (comments_open() || get_comments_number())) {
                comments_template();
            }
        }
    }
    // If no content, include the "No posts found" template.
} else {
    get_template_part('template-parts/'. $blog_custom_template . '/content', 'none');
}

theme_layout_after('blog'); ?>

<?php get_footer();
remove_action('theme_content_styles', 'theme_blog_content_styles');
remove_filter('body_class', 'theme_index_body_class_filter');
