<?php

function theme_get_post_short_description($post) {
    $description = wp_strip_all_tags(theme_create_excerpt($post->post_content, 55, 1));
    if (!$description) {
        $description = wp_strip_all_tags($post->post_content);
    }
    return str_replace(array("\r", "\n"), ' ', $description);
}

function theme_og_meta_tags() {
    if (!theme_get_option('seo_og')) {
        return;
    }

    global $post;

    if (is_front_page() || is_home()) {
        $type = 'website';
    } else if (is_singular()) {
        $type = $post->post_type === 'product' ? 'product' : 'article';
    } else {
        $type = 'object';
    }
    if (is_singular()) {
        $title = $post->post_title;
        if (function_exists('np_data_provider')) {
            $data_provider = np_data_provider($post->ID);
            $description = $data_provider->getPageDescription();
        }
        if (empty($description)) {
            $description = theme_get_post_short_description($post);
        }
        $url = get_permalink();
    }
    if (is_front_page()) {
        $url = home_url();
    }

    if (empty($title)) {
        $title = wp_get_document_title();
    }
    if (empty($description)) {
        $description = get_bloginfo('description', 'display');
    }

    if (empty($url)) {
        global $wp;
        $url = add_query_arg($wp->query_string, '', home_url($wp->request));
    }

    ?>
    <meta property="og:title" content="<?php echo esc_attr($title); ?>"/>
    <meta property="og:type" content="<?php echo $type; ?>"/>
    <meta property="og:url" content="<?php echo esc_attr($url); ?>"/>
    <meta property="og:site_name" content="<?php echo get_bloginfo('name'); ?>"/>
    <meta property="og:description" content="<?php echo esc_attr($description); ?>"/>

<?php
    if (is_singular() && has_post_thumbnail($post->ID)) {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        $thumbnail_src = wp_get_attachment_image_src($thumbnail_id, 'full');

        $alt = trim(strip_tags(get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true)));
        if (isset($thumbnail_src[0])) {
            echo '<meta property="og:image" content="' . esc_url($thumbnail_src[0]) . '">' . "\n";
        }
        if (isset($thumbnail_src[1])) {
            echo '<meta property="og:image:width" content="' . $thumbnail_src[1] . '">' . "\n";
        }
        if (isset($thumbnail_src[2])) {
            echo '<meta property="og:image:height" content="' . $thumbnail_src[2] . '">' . "\n";
        }
        if ($alt) {
            echo '<meta property="og:image:alt" content="' . $alt . '">' . "\n";
        }
    }

    $twitter_account = apply_filters('theme_twitter_account', '');
    if ($twitter_account) {
?>
        <meta name="twitter:site" content="<?php echo esc_attr($twitter_account); ?>">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="<?php echo esc_attr($title); ?>">
        <meta name="twitter:description" content="<?php echo esc_attr($description); ?>">
<?php
    }
}
add_action('wp_head', 'theme_og_meta_tags', 5);

function theme_ld_meta_tags() {
    if (!theme_get_option('seo_ld')) {
        return;
    }

    $jsons = array();

    if (is_front_page() || is_home()) {
        $jsons[] = array(
            "@context" => "http://schema.org",
            "@type" => "WebSite",
            "name" => get_bloginfo('name'),
            "potentialAction" => array(
                "@type" => "SearchAction",
                "target" => add_query_arg(array('s' => '{search_term_string}'), home_url('/')),
                "query-input" => "required name=search_term_string",
            ),
            "url" => home_url('/'),
        );
    }

    $social_links = apply_filters('theme_social_links', array());

    $common_json = array(
        "@context" => "http://schema.org",
        "@type" => "Organization",
        "name" => get_bloginfo('name'),
        "sameAs" => $social_links,
        "url" => home_url('/'),
    );
    $logo = theme_get_logo(array('default_src' => "", 'default_url' => ''));
    if (!empty($logo['src'])) {
        $common_json['logo'] = $logo['src'];
    }
    $jsons[] = $common_json;

    if (is_singular()) {
        global $post;
        $article_json = array(
            "@context" => "http://schema.org",
            "@type" => "Article",
            "mainEntityOfPage" => array(
                "@type" => "WebPage",
                "@id" => get_permalink($post->ID)
            ),
            "headline" => mb_substr(esc_html($post->post_title), 0, 110),
            "datePublished" => get_the_time(DATE_ISO8601, $post->ID),
            "dateModified" => get_post_modified_time(DATE_ISO8601, false, $post->ID),
            "author" => array(
                "@type" => "Person",
                "name" => esc_html(get_the_author_meta('display_name', $post->post_author))
            ),
            "description" => theme_get_post_short_description($post),
        );

        if (has_post_thumbnail($post->ID)) {
            $images = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
            $images_args = array(
                "image" => array(
                    "@type" => "ImageObject",
                    "url" => isset($images[0]) ? $images[0] : '',
                    "width" => isset($images[1]) ? $images[1] : 0,
                    "height" => isset($images[2]) ? $images[2] : 0
                )
            );
            $article_json = array_merge($article_json, $images_args);
        }

        $publisher_args = array(
            "publisher" => array(
                "@type" => "Organization",
                "name" => get_bloginfo('name'),
            )
        );

        if (!empty($logo['src'])) {
            $publisher_args['publisher']['logo'] = array(
                "@type" => "ImageObject",
                "url" => $logo['src'],
                "width" => $logo['width'],
                "height" => $logo['height'],
            );
        }
        $article_json = array_merge($article_json, $publisher_args);
        $jsons[] = $article_json;
    }

    if (isset($product)){
        $meta = get_post_meta($post->ID);
        $stock = isset($meta['_stock_status'][0]) && $meta['_stock_status'][0] == 'instock' ? 'InStock' : 'OutOfStock';
        $_product = new WC_Product($post->ID);
        if ($_product->regular_price!= null) {
            $price = $_product->regular_price;
        } elseif ($_product->price!= null) {
            $price = $_product->price;
        }
        if (($price > $_product->sale_price) && ($_product->sale_price!= null)) {
            $price = $_product->sale_price;
        }
        $product_description = $_product->description != null ? $_product->description : '';
        $images = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'single-post-thumbnail');
        $image = isset($images[0]) ? $images[0] : '';

        $product_json = array(
            "@context" => "http://schema.org",
            "@type" => "Product",
            "name" => get_the_title($post->ID),
            "description" => $product_description,
            "image" => $image,
            "offers" => array(
                "@type" => "Offer",
                "priceCurrency" => get_woocommerce_currency(),
                "price" => $price,
                "itemCondition" => "http://schema.org/NewCondition",
                "availability" => "http://schema.org/" . $stock,
                "url" => get_permalink(),
            ),
        );
        $jsons[] = $product_json;
    }

    foreach ($jsons as $json) {
        echo '<script type="application/ld+json">' . json_encode($json, JSON_UNESCAPED_UNICODE) . "</script>\n";
    }
}
add_action('wp_head', 'theme_ld_meta_tags', 5);

function theme_color_meta_tags() {
    $theme_color = apply_filters('theme_color', '#478ac9');
    echo '<meta name="theme-color" content="' . $theme_color . '">' . "\n";
}
add_action('wp_head', 'theme_color_meta_tags', 5);