<?php
# Template for posts listing with load more button

$default_posts_per_page = get_option( 'posts_per_page' );
$post_per_page          = ! empty( $default_posts_per_page ) ? $default_posts_per_page : 5;

$get_post_query = new WP_Query(
	array(
		'post_type'      => 'POST_TYPE',
		'post_status'    => 'publish',
		'posts_per_page' => $post_per_page,
	)
);

	if ( $get_post_query->have_posts() ) :
		?>
		<ul class="posts-listing-wrap">
			<?php
			$get_post_img = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			while ( $get_post_query->have_posts() ) :
				$get_post_query->the_post();
				$get_blog_id = get_the_ID();
				$post_categories = get_the_terms( $get_blog_id, 'CATEGORY_SLUG' );	// UPDATE A SLUG OF CATEGORY FOR POST TYPE		
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
		<?php
		if ( $get_post_query->found_posts > $post_per_page ) :
			?>
			<div class="posts-load-more-wrap">
				<a href="javascript:void(0);" class="posts-load-more-items-btn">
				<?php echo esc_html__( 'Load More', 'text-domain' ); ?>
				</a>
				<input type="hidden" class="posts-load-more-page" value="1">
				<input type="hidden" class="posts-load-per-page" value="<?php echo esc_attr( $post_per_page ); ?>">
				<input type="hidden" class="posts-load-categoty" value="<?php echo esc_attr( get_queried_object_id() ); ?>">
				<input type="hidden" class="posts-load-post_type" value="<?php echo esc_attr( POST_TYPE ); ?>">
			</div>
			<?php
		endif;			
	else :
		?>
		<div class="">
			<p class=""><?php esc_html_e( 'Posts not available.', 'text-domain' ); ?></p>
		</div>
		<?php
	endif;
	wp_reset_postdata();
?>


<script>
	/*
	* Add blow code to to /assets/js/posts.js file
	* DO NOT ADD SCRIPT TAG
	*/

	jQuery(document).ready(function ($) {
	  jQuery(document).on('click', '.posts-load-more-items-btn', function (e) { 
	      e.preventDefault();
	      $('.posts-load-more-wrap').find('.posts-load-more-items-btn').hide();
	      const get_current_page = jQuery('.posts-load-more-wrap').find('.posts-load-more-page').val(); 
	      const get_per_page = jQuery('.posts-load-more-wrap').find('.posts-load-per-page').val(); 
	      const get_category = jQuery('.posts-load-more-wrap').find('.posts-load-categoty').val(); 
	      const get_post_type = jQuery('.posts-load-more-wrap').find('.posts-load-post_type').val(); 
	      const get_page =  parseInt(get_current_page) + 1;
	        $.ajax({
	            url: posts_ajaxurl.ajaxurl, // WordPress AJAX URL
	            type: 'POST',
	            data: {
	                action: 'custom_load_more_posts',
	                get_current_page: get_page,
	                get_per_page: get_per_page,
	                get_category: get_category,
	                get_post_type: get_post_type,
	                posts_nonce: posts_ajaxurl.nonce,
	            },
	            success: function (response) {
	              $('.posts-load-more-wrap').find('.posts-load-more-items-btn').show();
	              if (response.success) {
	                $('.posts-load-more-wrap').find('.posts-load-more-page').val(parseInt(get_page));
	                $('.posts-listing-wrap').append(response.html);
	                if (get_page >= response.maxPage) {
	                    $('.posts-load-more-wrap').find('.posts-load-more-items-btn').hide();
	                  } 
	              }
	              else {
	                  $('.posts-load-more-wrap').find('.posts-load-more-items-btn').hide();
	              }
	            },
	            error: function () {
	                $('.posts-listing-wrap').append('<p>Something went wrong. Please try again.</p>');
	            }
	        });
		 });  
	});
</script>


<?php
# Add below code to functions.php file
function custom_themes_scripts() {

	wp_register_script(
			'posts-listing-js',
			get_template_directory_uri() . '/assets/js/posts.js',
			array( 'jquery' ),
			true,
			array(
				'strategy'  => 'defer',
				'in_footer' => true,
			)
		);

	wp_enqueue_script( 'posts-listing-js' );
	wp_localize_script(
		'posts-listing-js',
		'posts_ajaxurl',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'posts_nonce' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'custom_themes_scripts' );
?>


<?php
/**
 * Load more Posts.
 */
function custom_load_more_posts_callback() {

	// Check if the form was submitted.
	if ( ! isset( $_POST['posts_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['posts_nonce'] ) ), 'posts_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	// Sanitize the input.
	$get_per_page     = isset( $_POST['get_per_page'] ) ? sanitize_text_field( wp_unslash( $_POST['get_per_page'] ) ) : '';
	$get_post_type    = isset( $_POST['get_post_type'] ) ? sanitize_text_field( wp_unslash( $_POST['get_post_type'] ) ) : 'post';
	$get_category     = isset( $_POST['get_category'] ) ? sanitize_text_field( wp_unslash( $_POST['get_category'] ) ) : '';
	$get_current_page = isset( $_POST['get_current_page'] ) ? sanitize_text_field( wp_unslash( $_POST['get_current_page'] ) ) : '';

	// Perform actions based on the selected value (query posts, fetch data, etc.).
	if ( $get_current_page ) {
		$args = array(
			'post_type'      => $get_post_type,
			'posts_per_page' => $get_per_page,
			'post_status'    => 'publish',
		);

		if ( ! empty( $get_current_page ) ) {
			$args['paged'] = $get_current_page;
		}

		if ( ! empty( $get_category ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'CATEGORY_SLUG',
				'field'    => 'term_id',
				'terms'    => $get_category,
			);
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			ob_start();
			$get_post_img = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			while ( $query->have_posts() ) {
				$get_post_query->the_post();
				$get_blog_id = get_the_ID();
				$post_categories = get_the_terms( $get_blog_id, 'CATEGORY_SLUG' );	// UPDATE A SLUG OF CATEGORY FOR POST TYPE		
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
			}
			$output   = ob_get_clean();
			$response = array(
				'success' => true,
				'html'    => $output,
				'maxPage' => $query->max_num_pages,
			);
		} else {
			$response = array(
				'success' => false,
				'message' => 'No more Posts available.',
			);
		}
		wp_reset_postdata();
	}
	wp_send_json( $response );
}
add_action( 'wp_ajax_custom_load_more_posts', 'custom_load_more_posts_callback' );
add_action( 'wp_ajax_nopriv_custom_load_more_posts', 'custom_load_more_posts_callback' );
?>