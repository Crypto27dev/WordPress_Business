<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

add_action('admin_init', 'theme_admin_init', 1000);
add_action('save_post',  'theme_save_post_template', 1000);
add_filter('single_template', 'theme_post_template' );

function theme_admin_init(){
    add_meta_box('theme_select_post_template', __('Post Template', 'default'), 'theme_select_post_template', 'post', 'side', 'default');
}

function theme_select_post_template( $post )
{
    $post_ID = $post->ID;

    $template = get_post_meta( $post_ID, 'theme_post_template', true );
    if (empty($template)) {
        $template = 'templates/post.php';
        //get theme default post template
        global $theme_custom_templates, $theme_options;
        $postTemplateKey = array_search("Post", array_column($theme_options, 'name'));
        $option = $theme_options[$postTemplateKey];
        $id = theme_get_array_value($option, 'id');
        $val = theme_template_get_option($id);
        $name = theme_get_array_value($theme_custom_templates['Post'], $val);
        if ($name) {
            $template = $name;
        }
    }
    // Render the template
?>
    <label class="screen-reader-text" for="theme_post_template"><?php _e('Post Template', 'default') ?></label>
    <select name="theme_post_template" id="theme_post_template">
        <?php page_template_dropdown($template); ?>
    </select>

<?php
}

function is_post_template($template = '') {
    if (!is_single()) {
        return false;
    }

    global $wp_query;

    $post = $wp_query->get_queried_object();
    $post_template = get_post_meta( $post->ID, 'theme_post_template', true );

    // We have no argument passed so just see if a page_template has been specified
    if ( empty( $template ) ) {
        if (!empty( $post_template ) ) {
            return true;
        }
    } elseif ( $template == $post_template) {
        return true;
    }

    return false;
}

function theme_save_post_template($post_ID){
    if (!isset($_POST[ 'theme_post_template' ])) return;

    $template = (string) @ $_POST[ 'theme_post_template' ];

    if (empty($template)) return;

    delete_post_meta( $post_ID, 'theme_post_template' );

    add_post_meta( $post_ID, 'theme_post_template', $template );
}

function theme_post_template($template){
    global $wp_query;
    $post_ID = $wp_query->post->ID;

    $template_file = get_post_meta( $post_ID, 'theme_post_template', true );
    if (!$template_file)
        return $template;

    if ( file_exists( trailingslashit( get_stylesheet_directory() ) . $template_file ) )
        return get_stylesheet_directory() . DIRECTORY_SEPARATOR . $template_file;
    // If there's a tpl in the parent of the current child theme
    else if ( file_exists( get_template_directory() . DIRECTORY_SEPARATOR . $template_file ) )
        return get_template_directory() . DIRECTORY_SEPARATOR . $template_file;

    return $template;

}