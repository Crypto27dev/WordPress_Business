<?php

function theme_breadcrumbs($args) {
    $items = theme_breadcrumbs_items($args);
    $items_count = count($items);
    if ($items_count > 0) {
        echo '<ul class="' . $args['class'] . '">';
        for ($i = 0; $i < $items_count; $i++ ) {
            $li_class = $args['item_class'];
            echo '<li class="' . $li_class . '">';

            if ($i < $items_count/* - 1*/) {
                echo trim($items[$i]);
            } else {
                echo preg_replace("/<[\/]*a[^>]*>/", "", trim($items[$i]));
            }
            echo '</li>';

            if ($i < $items_count - 1) {
                echo $args['separator'];
            }

        }
        echo $args['separator_icon'];
        echo '</ul>';
    }
}

function theme_breadcrumbs_items($args) {
    global $post;
    $items = array();

    if (!is_front_page()) {
        $items[] = theme_breadcrumbs_link($args, get_home_url(), '', __('Home', 'website4829605'));
    }

    if (class_exists('WC_Breadcrumb') && function_exists('is_product_category') && is_product_category() && function_exists('is_product') && !is_product() || function_exists('is_shop') && is_shop()) {
        $breadcrumbs = new WC_Breadcrumb();
        $args['breadcrumb'] = $breadcrumbs->generate();
        foreach ($args['breadcrumb'] as $term) {
            $href = $term[1];
            $product_name = $term[0];
            $link = theme_breadcrumbs_link($args, $href, $product_name, $product_name);
            $items[] = $link ? $link : 'Uncategorized';
        }
    }

    if (is_category()) {
        $thisCat = get_category(get_query_var('cat'), false);
        $cats = explode('|', get_category_parents($thisCat->cat_ID, TRUE, '|'));
        foreach ($cats as $cat) {
            if ($cat) {
                $href = '#';
                if (preg_match('#href="([^"]*)"#', $cat, $m)) {
                    $href = $m[1];
                }
                $items[] = theme_breadcrumbs_link($args, $href, '', strip_tags($cat));
            }
        }
    }

    if (is_home()) {
        if (is_front_page())
            $items[] = theme_breadcrumbs_text($args, get_bloginfo('name'));
        else
            $items[] = theme_breadcrumbs_text($args, single_post_title('', false));
    }

    if (is_page() && !is_front_page()) {
        $parents = array();
        $parent_id = $post->post_parent;
        while ($parent_id) {
            $page = get_post($parent_id);
            if ($parent_id != get_option('page_on_front')) {
                $parents[] = theme_breadcrumbs_link($args, get_permalink($page->ID), get_the_title($page->ID), get_the_title($page->ID));
            }
            $parent_id = $page->post_parent;
        }
        $parents = array_reverse($parents);
        foreach ($parents as $p) {
            if ($p) {
                $items[] = $p;
            }
        }
        $items[] = theme_breadcrumbs_text($args, get_the_title());
    }


    if (is_single()) {

        if (get_post_type() !== 'post') {
            if (get_post_type() === 'product') {
                global $post;
                $terms = get_the_terms($post->ID, 'product_cat');
                if ($terms) {
                    foreach (array_reverse($terms) as $term) {
                        $product_cat_id = $term->term_id;
                        $product_name_options = get_term_by('id', $product_cat_id, 'product_cat', 'ARRAY_A');
                        $product_name = $product_name_options['name'];
                        $href = get_term_link($product_cat_id, 'product_cat');
                        $link = theme_breadcrumbs_link($args, $href, $product_name, $product_name);
                        $items[] = $link ? $link : 'Uncategorized';
                    }
                }
            } else {
                $post_type = get_post_type_object(get_post_type());
                $items[] = theme_breadcrumbs_link($args, get_post_type_archive_link(get_post_type()), $post_type->labels->singular_name, $post_type->labels->singular_name);
            }
            $items[] = theme_breadcrumbs_text($args, get_the_title());

        } else {
            $categories_1 = get_the_category();
            if ($categories_1):
                foreach ($categories_1 as $cat_1) {
                    $cat_1_ids[] = $cat_1->term_id;
                }
                $cat_1_line = implode(',', $cat_1_ids);
            endif;
            $categories = get_categories(array(
                'include' => $cat_1_line,
                'orderby' => 'id'
            ));
            if ($categories) {
                foreach ($categories as $cat) {
                    $cats[] = theme_breadcrumbs_link($args, get_category_link($cat->term_id), $cat->name, $cat->name);
                }
                foreach ($cats as $cat) {
                    if ($cat) {
                        $items[] = $cat;
                    }
                }
            }
            $items[] = theme_breadcrumbs_text($args, get_the_title());
        }
    }

    if (is_tag()) {
        $items[] = theme_breadcrumbs_text($args, __("Tag: ", 'website4829605') . single_tag_title('', FALSE));
    }
    if (is_404()) {
        $items[] = theme_breadcrumbs_text($args, __("404 - Page not Found", 'website4829605'));
    }
    if (is_search()) {
        $items[] = theme_breadcrumbs_text($args, __("Search", 'website4829605'));
    }
    if (is_year()) {
        $items[] = theme_breadcrumbs_text($args, get_the_time('Y'));
    }
    if (is_author()) {
        $items[] = theme_breadcrumbs_text($args, sprintf(esc_attr(__('View all posts by %s', 'website4829605')), get_the_author()));
    }

    if (count($items) == 0) {
        $items[] = theme_breadcrumbs_text($args, get_bloginfo('name'));
    }
    return $items;
}

function theme_breadcrumbs_link(&$args, $href = '#', $title = '', $text = '') {
    return '<a href="' . $href . '" title="' . $title . '" class="' . $args['link_class'] . '" style="' . $args['link_style'] . '">' . $text . '</a>';
}

function theme_breadcrumbs_text(&$args, $text = '') {
    return theme_breadcrumbs_link($args, '#', '', $text);
}