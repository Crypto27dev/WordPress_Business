<?php

function theme_nosidebar_widget($props) {
    global $theme_nosidebar_widgets;

    $type = _arr($props, 'type', 'text');
    $data = $theme_nosidebar_widgets[$type];
    $class = $data[0];
    $args = _arr($data, 2, array());
    foreach ($data[1] as $args_key => $source_key) {
        if (is_string($source_key)) {
            $args[$args_key] = _arr($props, $source_key);
        } else if (is_array($source_key)) {
            $args[$args_key] = _arr($props, $source_key[0], $source_key[1]);
        }
    }
    the_widget($class, $args);
}

global $theme_nosidebar_widgets;
$theme_nosidebar_widgets = array(
    'text' => array(
        'WP_Widget_Text',
        array(
            'title' => 'title',
            'text' => 'content',
        ),
        array(
            'filter' => true,
        )
    ),
    'calendar' => array(
        'WP_Widget_Calendar',
        array(
            'title' => 'title',
        )
    ),
    'searchWidget' => array(
        'WP_Widget_Search',
        array(
            'title' => 'title',
        )
    ),
    'meta' => array(
        'WP_Widget_Meta',
        array(
            'title' => 'title',
        )
    ),
    'pages' => array(
        'WP_Widget_Pages',
        array(
            'title' => 'title',
            'exclude' => array('excludes', ''),
            'sortby' => array('order-by', 'ID'),
        )
    ),
    'tag-cloud' => array(
        'WP_Widget_Tag_Cloud',
        array(
            'title' => 'title',
            'taxonomy' => 'taxonomy',
            'count' => 'tag-cloud-counts',
        )
    ),
    'menuWidget' => array(
        'WP_Nav_Menu_Widget',
        array(
            'title' => 'title',
            'nav_menu' => 'menu',
        )
    ),
    'categories' => array(
        'WP_Widget_Categories',
        array(
            'title' => 'title',
            'count' => 'show-post-counts',
            'hierarchical' => 'show-hierarchy',
            'dropdown' => 'display-as-dropdown',
        )
    ),
    'archives' =>array(
        'WP_Widget_Archives',
        array(
            'title' => 'title',
            'count' => 'show-post-counts',
            'dropdown' => 'display-as-dropdown',
        )
    ),
    'comments' => array(
        'WP_Widget_Recent_Comments',
        array(
            'title' => 'title',
            'number' => 'posts-count',
        )
    ),
    'posts' => array(
        'WP_Widget_Recent_Posts',
        array(
            'title' => 'title',
            'number' => 'posts-count',
            'show_date' => 'display-post-date',
        )
    ),
    'rss' => array(
        'WP_Widget_RSS',
        array(
            'title' => 'title',
            'url' => 'feed-url',
            'items' => 'posts-count',
            'show_summary' => 'display-item-content',
            'show_author' => 'display-item-author',
            'show_date' => 'display-item-date',
        )
    ),
);

if (version_compare($GLOBALS['wp_version'], '5.8', '>=')) {
    locate_template(array('library/custom-widgets.php'), true);
}
