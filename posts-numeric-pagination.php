<?php

# Add below code to functions.php file

/**
 * Custom Pagination.
 *
 * @param Object $cquery widget id.
 * @param string $classname class name.
 */
function custom_pagination_numeric( $cquery = array(), $classname = '' ) {

	add_filter( 'wp_kses_allowed_html', 'steun_allow_svg_in_wp_kses_post', 10, 1 );

	global $wp_query;
	$cquery = ( ! empty( $cquery ) ) ? $cquery : $wp_query;
	if ( ! empty( $cquery ) ) :
		$big      = 999999999;
		$paginate = paginate_links(
			array(
				'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
				'format'    => '?paged=%#%',
				'current'   => max( 1, get_query_var( 'paged' ) ),
				'total'     => $cquery->max_num_pages,
				'type'      => 'list',
				'mid_size'  => 1,
				'prev_text' => '<svg class="icon text-primary-800" width="24" height="24"> <use xlink:href="' . esc_url(  get_template_directory_uri() . '/assets/images/sprite.svg#prev-arrow' ) . '"> </use></svg>',
				'next_text' => '<svg class="icon text-primary-800" width="24" height="24"> <use xlink:href="' . esc_url(  get_template_directory_uri() . '/assets/images/sprite.svg#next-arrow' ) . '"> </use></svg>',
				'end_size'  => 1,
			)
		);

		if ( ! empty( $paginate ) ) :
			echo '<div class="pagination-wrap ' . esc_attr( $classname ) . '"><nav>';
			$paginate = str_replace( "<ul class='page-numbers'>", '<ul class="pagination">', $paginate );
			$paginate = str_replace( '<li>', '<li class="page-item">', $paginate );
			$paginate = str_replace( 'page-numbers', 'page-link', $paginate );
			$paginate = str_replace(
				'<li class="page-item"><span aria-current="page" class="page-link current">',
				'<li class="page-item active"><span aria-current="page" class="page-link">',
				$paginate
			);
			echo wp_kses_post( $paginate );
			echo '</nav></div>';
		endif;
	endif;
}
?>


<?php
# Add below code to show pagination for post listing of posts

$get_post_query = new WP_Query(
	array(
		'post_type'   => 'POST_TYPE',
		'post_status' => 'publish',
		'paged'       => get_query_var( 'paged', 1 ),
	)
);


if ( $get_post_query->have_posts() ) :
	?>
	<ul>
		<?php
		$get_post_img = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
		while ( $get_post_query->have_posts() ) :
			$get_post_query->the_post();
			$get_blog_id = get_the_ID();
			$post_categories = get_the_terms( $get_blog_id, 'CATEGORY_SLUG' ); // UPDATE A SLUG OF CATEGORY FOR POST TYPE		
			?>
			<li>
				<?php
				if ( ! empty( $post_categories ) ) :
					?>
					<div class="category">
						<ul>
							<?php
							foreach ( $post_categories as $category ) :
								?>
								<li><a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php
				endif;
				?>
				<a href="<?php echo esc_url( get_the_permalink( $get_blog_id ) ); ?>">
					<?php if ( has_post_thumbnail( $get_blog_id ) ) : ?>
						<?php
						$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id( $get_blog_id ), 'large' );
						?>
						<img src="<?php echo esc_url( $large_image_url[0] ); ?>" alt="Default Image">
					<?php else : ?>
						<img src="<?php echo esc_url( $get_post_img ); ?>" alt="Default Image">
					<?php endif; ?>
				</a>
				<div>
					<h3>
						<a href="<?php echo esc_url( get_the_permalink( $get_blog_id ) ); ?>">
							<?php echo wp_kses_post( get_the_title( $get_blog_id ) ); ?>
						</a>
					</h3>
					<p><?php echo wp_kses_post( wp_trim_words( get_the_excerpt( $get_blog_id ), 20, '...' ) ); ?></p>
				</div>
				<a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php esc_html_e( 'View', 'text-domain' ); ?></a>
			</li>
			<?php
		endwhile;
		?>
	</ul>
	<?php custom_pagination_numeric( $get_post_query ); ?>
	<?php
else :
	?>
		<div class="container">
			<p class=""><?php esc_html_e( 'Posts not available.', 'text-domain' ); ?></p>
		</div>
	<?php
endif;
wp_reset_postdata();
?>