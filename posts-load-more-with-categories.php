<?php

# Template for posts listing with load more buttonwith categoris tab

$default_posts_per_page = get_option( 'posts_per_page' );

$categories = get_categories(
	array(
		'hide_empty' => true,
		'exclude'    => array( 1 ),
	)
);

if ( ! empty( $categories ) ) :
	?>
	<div class="blog-filter">
		<ul>
			<li><button type="button" class="active get-category-posts" data-term_id="0" data-term_slug="all">All</button></li>
			<?php
			foreach ( $categories as $category ) :
				echo '<li><button class="get-category-posts"  type="button" data-term_id="' . esc_attr( $category->term_id ) . '" data-term_slug="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</button></li>';
			endforeach;
			?>
		</ul>
	</div>
	<?php
endif;
?>

<?php
if ( have_posts() ) :
	?>
	<div class="blog-list">
		<ul id="blog-post-container">
			<?php
			$get_post_img = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			while ( have_posts() ) :
				the_post();
				?>
				<li>
					<div>
						<a href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium', array( 'class' => 'img-fluid' ) ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( $get_post_img ); ?>" alt="Default Image">
							<?php endif; ?>
						</a>								
						<div class="category">
							<ul>
								<?php
								$post_categories = get_the_category();
								foreach ( $post_categories as $category ) :
									?>
									<li><a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 20, '...' ) ); ?></p>
						</div>
					</div>
				</li>
				<?php
			endwhile;
			?>
		</ul>
		<div>
			<a href="javascript:void(0);" data-term_slug="all" data-term_id="" class="custom-load-more-btn" style="<?php echo $wp_query->found_posts > $default_posts_per_page ? '' : 'display:none;'; ?>" >
				<?php esc_html_e( 'Load More', 'text-domain' ); ?>
			</a>
		</div>
		<input type="hidden" class="custom-load-more-page" value="1">
	</div>
	<?php
endif;
?>


<script>

	/*
	* Add blow code to to /assets/js/posts.js file
	* DO NOT ADD SCRIPT TAG
	*/

	
	jQuery(document).ready(function () {

	  jQuery('.get-category-posts').on('click', function (e) {
	      jQuery('.get-category-posts').removeClass('active');
	      jQuery(this).addClass('active');
	      var term_id =  jQuery(this).attr('data-term_id');
	      var term_slug =  jQuery(this).attr('data-term_slug');
	      $('.custom-load-more-btn').hide();
	      jQuery('#blog-post-container').removeClass().addClass(term_slug + '-wrapper');
	      jQuery('#blog-post-container').addClass('row');
	      jQuery('#blog-post-container').html('<div class="section btn-wrap text-center"><span class="btn btn-secondary custom-load-more-btn">Loading...</span></div>');

	      jQuery('.custom-load-more-btn').attr('data-term_id', term_id);
	      jQuery('.custom-load-more-btn').attr('data-term_slug', term_slug);
	      jQuery('.custom-load-more-page').val(parseInt(0));
	      var is_loadmore = false;
	      customPosts(term_slug,term_id, is_loadmore);
	    });

	    $(document).on('click', '.custom-load-more-btn', function (e) {
	      e.preventDefault();
	       $('.custom-load-more-btn').hide();
	      const term_slug = $(this).attr('data-term_slug'); // Get the value from `data-value`
	      const term_id = $(this).attr('data-term_id'); // Get the value from `data-value`
	      //alert(selectedValue);
	      var is_loadmore = true;
	      customPosts(term_slug,term_id,is_loadmore);
	  }); 

	   // Perform AJAX request
	  function customPosts(term_slug = 'all', term_id = '0', is_loadmore = false) {

	    if(is_loadmore === false)
	    {
	      jQuery('#blog-post-container').html('');
	    }
	    const get_current_page = jQuery('.custom-load-more-page').val(); 
	    const get_page =  parseInt(get_current_page) + 1;
	      $.ajax({
	          url: custom_posts_params.ajaxurl, // WordPress AJAX URL
	          type: 'POST',
	          data: {
	              action: 'custom_get_posts', // AJAX action hook
	              get_category: term_id,
	              get_current_page: get_page,
	              posts_nonce: custom_posts_params.nonce,
	          },
	          success: function (response) {
	            if (response.success) {
	              $('.custom-load-more-page').val(parseInt(get_page));
	              jQuery('#blog-post-container').append(response.html);
	              if (get_page >= response.maxPage) {
	                  $('.custom-load-more-btn').hide();
	                }
	                else
	                {
	                   $('.custom-load-more-btn').show();
	                } 
	            }
	            else {
	                $('.custom-load-more-btn').hide();
	            }
	          },
	          error: function () {
	               jQuery('#blog-post-container').append('<p>Something went wrong. Please try again.</p>');
	          }
	      });
	  }
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
		'custom_posts_params',
		array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'posts_nonce' ),
		)
	);

}
add_action( 'wp_enqueue_scripts', 'custom_themes_scripts' );




/**
 * Load more Posts.
 */
function handle_custom_get_posts() {

	// Check if the form was submitted.
	if ( ! isset( $_POST['posts_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['posts_nonce'] ) ), 'posts_nonce' ) ) {
		wp_send_json_error( 'Invalid nonce' );
	}

	$default_posts_per_page = get_option( 'posts_per_page' );
	$get_per_page  = $default_posts_per_page;
	$get_category  = isset( $_POST['get_category'] ) ? sanitize_text_field( wp_unslash( $_POST['get_category'] ) ) : '';

	$get_current_page = isset( $_POST['get_current_page'] ) ? sanitize_text_field( wp_unslash( $_POST['get_current_page'] ) ) : '';

	// Perform actions based on the selected value (query posts, fetch data, etc.).
	if ( $get_current_page ) {
		$args = array(
			'post_type'      => 'post',
			'posts_per_page' => $get_per_page,
			'post_status'    => 'publish',
		);

		if ( ! empty( $get_category ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $get_category,
			);
		}

		if ( ! empty( $get_current_page ) ) {
			$args['paged'] = $get_current_page;
		}

		$query = new WP_Query( $args );

		if ( $query->have_posts() ) {
			ob_start();
			$get_post_img = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			while ( $query->have_posts() ) {
				$query->the_post();
				$get_post_id = get_the_ID();
				?>
				<li>
					<div>
						<a href="<?php the_permalink(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium', array( 'class' => 'img-fluid' ) ); ?>
							<?php else : ?>
								<img src="<?php echo esc_url( $get_post_img ); ?>" alt="Default Image">
							<?php endif; ?>
						</a>								
						<div class="category">
							<ul>
								<?php
								$post_categories = get_the_category();
								foreach ( $post_categories as $category ) :
									?>
									<li><a href="<?php echo esc_url( get_category_link( $category->term_id ) ); ?>"><?php echo esc_html( $category->name ); ?></a></li>
								<?php endforeach; ?>
							</ul>
						</div>
						<div>
							<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
							<p><?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 20, '...' ) ); ?></p>
						</div>
					</div>
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
				'message' => 'No more items available.',
			);
		}
		wp_reset_postdata();
	} else {
		$response = array(
			'success' => false,
			'message' => 'No more items available.',
		);
	}
	wp_send_json( $response );
}
add_action( 'wp_ajax_custom_get_posts', 'handle_custom_get_posts' );
add_action( 'wp_ajax_nopriv_custom_get_posts', 'handle_custom_get_posts' );
