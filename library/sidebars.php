<?php
/**
 * Registers a widget area.
 *
 * @link https://developer.wordpress.org/reference/functions/register_sidebar/
 */
function theme_widgets_init() {
    
}
add_action('widgets_init', 'theme_widgets_init');



function theme_register_unregister_widget_filters($sidebar_id, $add = true) {
    $widget_filters = array(
        
    );
    if (isset($widget_filters[$sidebar_id])) {
        if ($add) {
            add_filter('widget_text', $widget_filters[$sidebar_id], 1000);
        } else {
            remove_filter('widget_text', $widget_filters[$sidebar_id], 1000);
        }
    }
}

function theme_register_widget_filters($sidebar_id) {
    theme_register_unregister_widget_filters($sidebar_id, true);
}

function theme_unregister_widget_filters($sidebar_id) {
    theme_register_unregister_widget_filters($sidebar_id, false);
}

add_action('dynamic_sidebar_before', 'theme_register_widget_filters');
add_action('dynamic_sidebar_after', 'theme_unregister_widget_filters');