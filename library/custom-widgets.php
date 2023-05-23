<?php
// Add categories widget for WP >= 5.8
class WP_Categories_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'WP_Categories_Widget',
            __('Categories', 'default'),
            array( 'description' => __( 'A list of categories.', 'default' ), )
        );
    }

    public function widget( $args, $instance ) {
        render_custom_widget($args, $instance , 'WP_Widget_Categories');
    }

    public function form( $instance ) {
        render_widget_form($instance, $this->get_field_id( 'title' ), $this->get_field_name( 'title' ), 'Categories');
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

// Add product categories widget for WP >= 5.8
class WP_ProductCategories_Widget extends WP_Widget {

    function __construct() {
        parent::__construct(
            'WP_ProductCategories_Widget',
            __('Product Categories', 'default'),
            array( 'description' => __( 'A list of product categories.', 'default' ), )
        );
    }

    public function widget( $args, $instance ) {
        render_custom_widget($args, $instance , 'WC_Widget_Product_Categories');
    }

    public function form( $instance ) {
        render_widget_form($instance, $this->get_field_id( 'title' ), $this->get_field_name( 'title' ), 'Product Categories');
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        return $instance;
    }
}

function render_custom_widget($args, $instance, $class) {
    $title = apply_filters( 'widget_title', $instance['title'] );
    if (isset($args['before_widget'])) {
        echo $args['before_widget'];
    }
    if ( ! empty( $title ) )
        echo $args['before_title'] . $title . $args['after_title'];

    $widget_args = array(
        'hierarchical' => 'show-hierarchy',
        'dropdown' => 0,
        'before_title' => '<div style="display:none">',
        'after_title' => '</div>',
        'before_widget' => '<div class="%s">',
        'after_widget'  => '</div>',
    );
    the_widget($class, array(), $widget_args);
    if (isset($args['after_widget'])) {
        echo $args['after_widget'];
    }
}

function render_widget_form($instance, $fieldId, $fieldName, $name) {
    if ( isset( $instance[ 'title' ] ) ) {
        $title = $instance[ 'title' ];
    }
    else {
        $title = __( $name, 'default' );
    } ?>
    <p>
        <label for="<?php echo $fieldId; ?>"><?php _e( 'Title:' ); ?></label>
        <input class="widefat" id="<?php echo $fieldId; ?>" name="<?php echo $fieldName; ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
}

function load_categories_widget() {
    register_widget( 'WP_Categories_Widget' );
}

function load_product_categories_widget() {
    if (class_exists('WooCommerce')) {
        register_widget( 'WP_ProductCategories_Widget' );
    }
}

add_action( 'widgets_init', 'load_categories_widget' );
add_action( 'widgets_init', 'load_product_categories_widget' );