<?php
/**
 * The template for displaying comments
 *
 * The area of the page that contains both current comments
 * and the comment form.
 *
 * @package WordPress
 * @subpackage Twenty_Sixteen
 * @since Twenty Sixteen 1.0
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>

<section class="u-align-center u-clearfix u-section-1" id="sec-a69d">
  <div class="u-clearfix u-sheet u-valign-middle-md u-valign-middle-sm u-valign-middle-xs u-sheet-1" style="min-height: auto;">
	<div id="comments" class="comments-area">
		<?php if ( have_comments() ) : ?>
			<h3 class="comments-title">
				<?php
				$comments_number = get_comments_number();
				if ( 1 === $comments_number ) {
					/* translators: %s: post title */
					printf( _x( 'One thought on &ldquo;%s&rdquo;', 'comments title', 'website4829605' ), get_the_title() );
				} else {
					printf(
					/* translators: 1: number of comments, 2: post title */
						_nx(
							'%1$s thought on &ldquo;%2$s&rdquo;',
							'%1$s thoughts on &ldquo;%2$s&rdquo;',
							$comments_number,
							'comments title',
							'website4829605'
						),
						number_format_i18n( $comments_number ),
						get_the_title()
					);
				}
				?>
			</h3>

			<?php the_comments_navigation(); ?>

			<ol class="comment-list">
				<?php
				$comments_html = str_replace(
					array(
						'comment-reply-link'
					),
					array(
						'comment-reply-link u-link'
					),
					wp_list_comments(array(
						'style'       => 'ol',
						'short_ping'  => true,
						'avatar_size' => 42,
						'reply_text'  => '' . __('Reply', 'website4829605') . '',
						'echo'        => false,
					))
				);
				$comments_html = preg_replace_callback('#class="comment-metadata([\s\S]*?)<\/div>#', function ($m) {
					$result = $m[0];
					$result = str_replace(
						array(
							'<a href',
							'</a>',
							'comment-metadata',
							'comment-edit-link',
							'edit-link',
						),
						array(
							'<span class="u-meta-date u-meta-icon"><a class="u-textlink" href',
							'</a></span>',
							'comment-metadata u-blog-control u-metadata u-metadata-1',
							'comment-edit-link u-textlink',
							'edit-link u-meta-date u-meta-icon',
						),
						$result
					);
					return $result;
				}, $comments_html);
				echo $comments_html;
				?>
			</ol><!-- .comment-list -->

			<?php the_comments_navigation(); ?>

		<?php endif; // Check for have_comments(). ?>

		<?php
		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) :
			?>
			<p class="no-comments "><?php _e( 'Comments are closed.', 'website4829605' ); ?></p>
		<?php endif; ?>

		<?php
		comment_form(array(
			'title_reply_before' => '<h3 id="reply-title" class="comment-reply-title">',
			'title_reply_after'  => '</h3>',
			'submit_button'      => '<button name="%1$s" type="submit" id="%2$s" class="%3$s">%4$s</button>',
			'class_submit'       => 'u-btn',
		));
		?>
	</div>
</div>
</section>