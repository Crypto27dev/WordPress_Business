<?php if (is_not_post_template($post)) { the_content(); } else { ?><?php global $wp_query, $post;
 ?>
<?php $skip_min_height = false; ?><section class="u-align-center u-clearfix u-section-1" id="sec-a69d">
  <div class="u-clearfix u-sheet u-valign-middle-md u-valign-middle-sm u-valign-middle-xs u-sheet-1"><!--post_details--><!--post_details_options_json--><!--{"source":""}--><!--/post_details_options_json--><!--blog_post-->
    <div class="u-container-style u-expanded-width u-post-details u-post-details-1">
      <div class="u-container-layout u-valign-middle u-container-layout-1"><!--blog_post_image-->
        <?php
                            $post_image = theme_get_post_image(array('class' => 'u-blog-control u-expanded-width u-image u-image-default u-image-1', 'default' => '/images/0fd3416c.jpeg'));
                            if ($post_image) echo $post_image; else { echo '<div class="none-post-image" style="display: none;"></div>'; $skip_min_height = true; } ?><!--/blog_post_image--><!--blog_post_header-->
        <h2 class="u-blog-control u-text u-text-1">
          <?php if (!is_singular()): ?><a class="u-post-header-link" href="<?php the_permalink(); ?>"><?php endif; ?><?php the_title(); ?><?php if (!is_singular()): ?></a><?php endif; ?>
        </h2><!--/blog_post_header--><!--blog_post_metadata-->
        <div class="u-blog-control u-metadata u-metadata-1"><!--blog_post_metadata_date-->
          <span class="u-meta-date u-meta-icon"><?php if(is_search() || $post->post_type !== "page"){ echo get_the_date(); }?></span><!--/blog_post_metadata_date--><!--blog_post_metadata_category-->
          <?php $categories = theme_get_category_list(); if ($categories):?><span class="u-meta-category u-meta-icon"><?php echo $categories; ?></span><?php endif; ?><!--/blog_post_metadata_category--><!--blog_post_metadata_comments-->
          <?php if (comments_open()): ?><span class="u-meta-comments u-meta-icon"><a class="u-textlink" href="<?php echo get_comments_link(); ?>">
                                        <?php _e(sprintf(__('Comments (%d)', 'website4829605'), (int)get_comments_number())); ?>
                                   </a></span><?php endif; ?><!--/blog_post_metadata_comments-->
        </div><!--/blog_post_metadata--><!--blog_post_content-->
        <div class="u-align-justify u-blog-control u-post-content u-text u-text-2"><?php echo !is_search() && (is_singular() || $post->post_type !== 'post') ? theme_get_content() : theme_get_excerpt(); ?></div><!--/blog_post_content-->
      </div>
    </div><!--/blog_post--><!--/post_details-->
  </div>
</section><?php if ($skip_min_height) { echo "<style> .u-section-1, .u-section-1 .u-sheet {min-height: auto;}</style>"; } ?>
<?php } ?>