<?php

class Walker_Categories_Widget extends Walker_Category {

    public function start_lvl(&$output, $depth=0, $args=array()) {
        $output .= "\n<ul class=\"u-unstyled\">\n";
    }

    public function end_lvl(&$output, $depth=0, $args=array()) {
        $output .= "</ul>\n";
    }

    public function start_el( &$output, $category, $depth = 0, $args = array(), $id = 0 ) {
        $cat_name = apply_filters(
            'list_cats',
            esc_attr( $category->name ),
            $category
        );
        if ( ! $cat_name ) {
            return;
        }
        $linkClass = '{catLinkClass}';
        $linkStyle = '{catLinkStyle}';
        $link = '<div class="{catItemClass}" style="{catItemStyle}"><a class="' . $linkClass . '" style="' . $linkStyle . '" href="' . esc_url( get_term_link( $category ) ) . '" ';
        if ( $args['use_desc_for_title'] && ! empty( $category->description ) ) {
            $link .= 'title="' . esc_attr( strip_tags( apply_filters( 'category_description', $category->description, $category ) ) ) . '"';
        }
        $link .= '>';
        $link .= '<!--icon-html-->' . $cat_name . '</a></div>';

        if ( 'list' == $args['style'] ) {
            $output .= "\t<li";
            $css_classes = array(
                'cat-item',
                'cat-item-' . $category->term_id,
            );

            $term_childs = get_term_children( $category->term_id, $category->taxonomy );
            if (count($term_childs) > 0) {
                $css_classes[] = 'u-expand-closed';
            } else {
                $css_classes[] = 'u-expand-leaf';
                $link = str_replace('<!--icon-html-->', '<!--icon-html-hide-->', $link);
            }
            if ($category->parent === 0) {
                $css_classes[] = 'u-root';
            }

            if ( ! empty( $args['current_category'] ) ) {
                // 'current_category' can be an array, so we use `get_terms()`.
                $_current_terms = get_terms( $category->taxonomy, array(
                    'include' => $args['current_category'],
                    'hide_empty' => false,
                ) );

                foreach ( $_current_terms as $_current_term ) {
                    if ( $category->term_id == $_current_term->term_id ) {
                        $css_classes[] = 'u-active';
                        $link = str_replace('{catLinkClass}', '{catLinkClass} active', $link);
                    }
                }
            }
            $css_classes = implode( ' ', apply_filters( 'category_css_class', $css_classes, $category, $depth, $args ) );
            $output .=  ' class="' . $css_classes . ' u-categories-item "';
            $output .= ">$link\n";
        } else {
            $output .= "\t$link<br />\n";
        }
    }

    public function end_el(&$output, $item, $depth=0, $args=array()) {
        $output .= "</li>\n";
    }
}

class Theme_Walker_Nav_Menu extends Walker_Nav_Menu {

    public $args = array();
    public static $app_menu;
    public static $countParentMenuItem = 0;
    public static $popup_info;
    public static $location;

    public function display_element($el, &$children, $max_depth, $depth, $args, &$output){
        self::$app_menu = $el->isMegaMenu ? 'mega_menu' : 'base_menu';
        if (false !== $this->args['is_mega_menu']) {
            if (!isset(self::$location)) {
                self::$location = $this->args['theme_location'];
            }
            if (self::$location !== $this->args['theme_location']) {
                //reset counter for another menu
                self::$countParentMenuItem = 0;
                self::$location = $this->args['theme_location'];
            }
            if ($depth === 0) {
                self::$countParentMenuItem++;
            }
        }
        parent::display_element($el, $children, $max_depth, $depth, $args, $output);
    }

    public function start_lvl(&$output, $depth = 0, $args = array()) {
        $base_menu_html = '<div class="u-nav-popup"><ul class="' . $this->args['submenu_class'] . '">';
        if (self::$app_menu === "base_menu") {
            $output .= $base_menu_html;
        } elseif (self::$app_menu === "mega_menu") {
            $level = $depth + 2;
            if ($level > 3) {
                $level = 3;
            }
            $startContainer = '<div class="u-nav-popup level-' . $level . ' u-columns-auto ">';
            $endContainer = '</div>';
            $startPopupContent = '';
            if ($level === 2) {
                if (isset($this->args['mega_menu'][(self::$countParentMenuItem - 1)])) {
                    self::$popup_info = $this->args['mega_menu'][(self::$countParentMenuItem - 1)];
                } else {
                    self::$popup_info = array();
                    // for base menu sub items
                    $output .= $base_menu_html;
                    return;
                }
                $template = isset(self::$popup_info['template']) ? self::$popup_info['template'] : $startContainer . '<!--popup_html-->' . $endContainer;
                $template = renderTemplate($template, '', 'return', 'custom');
                $template = preg_replace('/url\("([\s\S]+?)"\)/', 'url($1)', $template);
                $templateParts = explode('<!--popup_html-->', $template);
                $startContainer = isset($templateParts[0]) ? $templateParts[0] : $startContainer;
                $popup_html = '';
                if (preg_match('/<!--popup_html-->([\s\S]+?)<!--\/popup_html-->/', $template, $matchesControls)) {
                    $popup_html = isset($matchesControls[1]) ? $matchesControls[1] : '';
                }
                $popupContentParts = explode('{megaPopup}', $popup_html);
                $startPopupContent = isset($popupContentParts[0]) ? $popupContentParts[0] : '';
                self::$popup_info['$endPopupContent'] = isset($popupContentParts[1]) ? $popupContentParts[1] : '';
                $menu_classes = isset(self::$popup_info['submenu_class']) ? self::$popup_info['submenu_class'] : '';
            }
            if ($level >= 3) {
                if (empty(self::$popup_info)) {
                    // for base menu sub-sub items
                    $output .= $base_menu_html;
                    return;
                } else {
                    $menu_classes = isset(self::$popup_info['sub_submenu_class']) ? self::$popup_info['sub_submenu_class'] : '';
                }
            }
            $output .= $startContainer . $startPopupContent . '<ul class="' . $menu_classes . '">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = array()) {
        $level = $depth + 2;
        if (self::$app_menu === "mega_menu" && $level === 2) {
            $endPopupContent = isset(self::$popup_info['$endPopupContent']) ? self::$popup_info['$endPopupContent'] : '';
            if ( isset( $args->item_spacing ) && 'discard' === $args->item_spacing ) {
                $t = '';
                $n = '';
            } else {
                $t = "\t";
                $n = "\n";
            }
            $indent = str_repeat( $t, $depth );
            $output .= "$indent</ul>{$n}" . $endPopupContent;
        } else {
            parent::end_lvl($output, $depth, $args);
        }
    }

    function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
        $item = self::processFileLink($item);
        if (self::$app_menu === "base_menu") {
            parent::start_el( $output, $item, $depth, $args, $id = 0);
        } elseif (self::$app_menu === "mega_menu") {
            global $wp_query;
            $indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' ); // code indent

            $depth_class_names = esc_attr( 'u-nav-item' );
            // passed classes
            $classes = empty( $item->classes ) ? array() : (array) $item->classes;
            $class_names = implode( ' ', array_filter( $classes ));

            // build html
            $output .= $indent . '<li id="nav-menu-item-'. esc_attr($item->ID) . '" class="' . esc_attr($depth_class_names) . ' ' . esc_attr($class_names) . '">';
            // link attributes
            $attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
            $attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
            $attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
            $attributes .= ! empty( $item->url )        ? ' href="' . (($item->url[0] == "#" && !is_front_page()) ? home_url() : '') . esc_attr($item->url) .'"' : '';

            //$attributes .= ' class="u-nav-link u-button-style active menu-link '.((strpos($item->url,'#') === false) ? '' : 'scroll').' ' . ( $depth > 0 ? 'sub-menu-link' : 'main-menu-link' ) . '"';

            $atts = array();
            $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
            $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
            $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
            $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
            $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

            $attributes = '';
            foreach ( $atts as $attr => $value ) {
                if ( ! empty( $value ) ) {
                    $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                    $attributes .= ' ' . $attr . '="' . $value . '"';
                }
            }

            $item_output = '<a ' . $attributes . '>' . apply_filters( 'the_title', $item->title, $item->ID ) . '</a>';
            $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
        }
    }

    function end_el( &$output, $item, $depth = 0, $args = array() ) {
        parent::end_el( $output, $item, $depth, $args);
    }

    function processFileLink($el) {
        if (isset($el->url) && strpos($el->url, '[[site_path_live]]') !== false) {
            $linkParts = explode('[[site_path_live]]', $el->url);
            $url = isset($linkParts[1]) ? get_site_url() . $linkParts[1] : $el->url;
            $el->url = $url;
        }
        return $el;
    }

}

class Theme_NavMenu {

    private static $_itemClass;
    private static $_linkClass;
    private static $_linkStyle;
    private static $_submenuItemClass;
    private static $_submenuLinkClass;
    private static $_submenuLinkStyle;
    private static $_themeLocation;
    private static $_sub_submenuLinkClass;
    private static $_sub_submenuLinkStyle;

    /**
     * Build menu items in hierarchical array
     *
     * @param array $items
     * @param int   $parentId
     *
     * @return array
     */
    public static function menuItemsLevelSetter(array $items) {
        $level = 1;
        $stack = array('0');
        foreach($items as $key => $item) {
            while($item->menu_item_parent != array_pop($stack)) {
                $level--;
            }
            $level++;
            $stack[] = $item->menu_item_parent;
            $stack[] = $item->ID;
            $items[$key]->level = ($level - 1);
        }
        return $items;
    }

    public static function isMegaMenuSetter($items) {
        $rootIndx = null;
        $subRootIndx = null;
        foreach ($items as $i => &$item) {
            $level = (int) $item->level;
            if ($level === 1) {
                $rootIndx = $i;
            }

            if ($level === 2) {
                $subRootIndx = $i;
            }

            if ($level > 2) {
                $items[$rootIndx]->isMegaMenu = true;
                $items[$subRootIndx]->isMegaMenu = true;
                $item->isMegaMenu = true;
            }
        }
        return $items;
    }

    public static function menuItemsFilter( $menu_items ) {
        $menu_items = self::menuItemsLevelSetter($menu_items);
        $menu_items = self::isMegaMenuSetter($menu_items);
        return $menu_items;
    }

    /**
     * Filter on nav_menu_css_class
     *
     * @param string[] $classes
     * @param WP_Post  $item
     * @param stdClass $args
     * @param int      $depth
     * @return string[]
     */
    public static function _itemClassFilter($classes, $item, $args, $depth) {
        if ($args->theme_location === self::$_themeLocation) {
            $classes[] = $depth === 0 ? self::$_itemClass : self::$_submenuItemClass;
        }
        return $classes;
    }

    /**
     * Filter on nav_menu_link_attributes
     *
     * @param string[] $atts
     * @param WP_Post  $item
     * @param stdClass $args
     * @param int      $depth
     * @return string[]
     */
    public static function _linkAttrsFilter($atts, $item, $args, $depth) {
        if ($args->theme_location === self::$_themeLocation) {
            if ($depth === 0) {
                $class = self::$_linkClass;
                $style = self::$_linkStyle;
            } elseif ($depth === 1) {
                $class = self::$_submenuLinkClass;
                $style = self::$_submenuLinkStyle;
            } else {
                $class = isset(self::$_sub_submenuLinkClass) ? self::$_sub_submenuLinkClass : self::$_submenuLinkClass;
                $style = isset(self::$_sub_submenuLinkStyle) ? self::$_sub_submenuLinkStyle : self::$_submenuLinkStyle;
            }
            if ($class) {
                $class = str_replace(array('u-dialog-link', 'u-file-link'), array('', ''), $class);
                $atts['class'] = empty($atts['class']) ? $class : $atts['class'] . ' ' . $class;
            }
            if ($item->current) {
                $atts['class'] = empty($atts['class']) ? 'active' : $atts['class'] . ' active';
            }
            if ($style) {
                $atts['style'] = empty($atts['style']) ? $style : $atts['style'] . ';' . $style;
            }
        }
        return $atts;
    }


    public static function getMenuHtml($args) {
        self::$_themeLocation = $args['theme_location'];
        if (theme_get_option('use_default_menu')) {
            return wp_nav_menu(array('theme_location' => self::$_themeLocation, 'echo' => false));
        }

        $locations = get_nav_menu_locations();
        $location = theme_get_array_value($locations, self::$_themeLocation);
        if (!$location && !empty($locations)) {
            $location = max(array_values($locations));
        }
        if (!$location) {
            return '';
        }

        $menu = wp_get_nav_menu_object($location);
        if (!$menu) {
            return '';
        }

        self::$_itemClass = $args['menu']['item_class'];
        self::$_linkClass = $args['menu']['link_class'];
        self::$_linkStyle = $args['menu']['link_style'];
        self::$_submenuItemClass = $args['menu']['submenu_item_class'];
        self::$_submenuLinkClass = $args['menu']['submenu_link_class'];
        self::$_submenuLinkStyle = $args['menu']['submenu_link_style'];
        $args['menu']['mega_menu'] = isset($args['mega_menu']) ? json_decode($args['mega_menu'], true) : array();
        $array_keys = array_keys($args['menu']['mega_menu']);
        $mega_menu_params = isset($array_keys[0]) ? $args['menu']['mega_menu'][$array_keys[0]] : array();
        self::$_sub_submenuLinkClass = isset($mega_menu_params) && isset($mega_menu_params['sub_submenu_link_class']) ? $mega_menu_params['sub_submenu_link_class'] : self::$_submenuLinkClass;
        self::$_sub_submenuLinkStyle = isset($mega_menu_params) && isset($mega_menu_params['sub_submenu_link_style']) ? $mega_menu_params['sub_submenu_link_style'] : self::$_submenuLinkStyle;

        add_filter('nav_menu_css_class', 'Theme_NavMenu::_itemClassFilter', 10, 4);
        add_filter('nav_menu_link_attributes', 'Theme_NavMenu::_linkAttrsFilter', 10, 4);
        if (isset($args['menu']['is_mega_menu']) && false !== $args['menu']['is_mega_menu']) {
            $args['menu']['theme_location'] = $args['theme_location'];
            add_filter( 'wp_nav_menu_objects', 'Theme_NavMenu::menuItemsFilter', 10 );
        }

        $menu_walker = new Theme_Walker_Nav_Menu;
        $menu_walker->args = $args['menu'];

        $menu_html = wp_nav_menu(array(
            'menu' => $menu,
            'menu_class' => $args['menu']['menu_class'],
            'container' => 'nav',
            'container_class' => $args['container_class'],
            'theme_location' => self::$_themeLocation,
            'walker' => $menu_walker,
            'echo' => false,
        ));

        self::$_itemClass = $args['responsive_menu']['item_class'];
        self::$_linkClass = $args['responsive_menu']['link_class'];
        self::$_linkStyle = $args['responsive_menu']['link_style'];
        self::$_submenuItemClass = $args['responsive_menu']['submenu_item_class'];
        self::$_submenuLinkClass = $args['responsive_menu']['submenu_link_class'];
        self::$_submenuLinkStyle = $args['responsive_menu']['submenu_link_style'];

        $responsive_menu_walker = new Theme_Walker_Nav_Menu;
        $responsive_menu_walker->args = $args['responsive_menu'];

        $responsive_menu_html = wp_nav_menu(array(
            'menu' => $menu,
            'menu_class' => $args['responsive_menu']['menu_class'],
            'container' => 'nav',
            'container_class' => $args['container_class'],
            'theme_location' => self::$_themeLocation,
            'walker' => $responsive_menu_walker,
            'echo' => false,
        ));

        if (!preg_match('#<ul[\s\S]*ul>#', $responsive_menu_html, $m)) {
            return '';
        }
        $responsive_nav = $m[0];

        if (!preg_match('#<ul[\s\S]*ul>#', $menu_html, $m)) {
            return '';
        }
        $regular_nav = $m[0];

        $menu_html = strtr($args['template'], array('{menu}' => $regular_nav, '{responsive_menu}' => $responsive_nav));
        $menu_html = preg_replace('#<\/li>\s+<li#', '</li><li', $menu_html); // remove spaces
        return $menu_html;
    }

    public static function menuItemTitleFilter($title, $item, $args, $depth) {
        if (theme_get_option('menu_use_tag_filter')) {
            $allowed_tags = explode(',', str_replace(' ', '', theme_get_option('menu_allowed_tags')));
            $title = strip_tags($title, $allowed_tags ? '<' . implode('><', $allowed_tags) . '>' : '');
        }
        if (theme_get_option('menu_trim_title')) {
            $title = theme_trim_long_str($title, theme_get_option($depth == 0 ? 'menu_trim_len' : 'submenu_trim_len'));
        }
        return $title;
    }
}
add_filter('nav_menu_item_title', 'Theme_NavMenu::menuItemTitleFilter', 10, 4);

register_nav_menus(
    array(
        'primary-navigation-1' => 'Primary Navigation',
    )
);