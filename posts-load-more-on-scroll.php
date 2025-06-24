<?php

# Template for posts listing with load more button	
$args = array(
	'post_type'      => 'POST_TYPE',
	'post_status'    => 'publish',
	'posts_per_page' => get_option( 'posts_per_page' ),
);

$post_items_query = new WP_Query(
	$args
);


if ( $post_items_query->have_posts() ) :
	?>
	<ul class="posts-listing-wrap" id="custom-post-container">
		<?php
		while ( $post_items_query->have_posts() ) :
			$post_items_query->the_post();
			$get_post_id      = get_the_ID();
			$get_post_title   = get_the_title( $get_post_id );
			$get_post_link    = get_the_permalink( $get_post_id );
			$get_post_date    = get_the_date( 'd-m-Y', $get_post_id );
			$get_post_excerpt = get_the_excerpt( $get_post_id );
			$get_post_img     = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			$photo            = wp_get_attachment_url( get_post_thumbnail_id( $get_post_id, 'full' ) );
			if ( ! empty( $photo ) ) :
				$get_post_img = $photo;
			endif;

			$category_name = '';
			$category_link = '';

			$categories = get_the_category( $get_post_id );
			if ( ! empty( $categories ) ) :
				$first_category = $categories[0];
				if ( 'uncategorized' !== $first_category->slug ) :
					$category_name = $first_category->name;
					$category_link = get_category_link( $first_category->term_id );
				endif;
			endif;
			?>

			<li>
				<a href="<?php echo esc_url( $get_post_link ); ?>">					
					<div class="">
						<div class="">
							<img src="<?php echo esc_url( $get_post_img ); ?>" alt="" width="" height="">
						</div>
						<div class="">
								<span class="date"><?php echo wp_kses_post( $get_post_date ); ?></span>
								<h3><?php echo wp_kses_post( $get_post_title ); ?></h3>
							<div class="description">
								<p><?php echo wp_kses_post( $get_post_excerpt ); ?></p>
							</div>							
						</div>
					</div>
				</a>
			</li>
			<?php
		endwhile;
		?>
	</ul>	
	<div class="blog-loader"></div>
	<?php
else :
	?>
	<div class="">
		<p><?php esc_html_e( 'Posts not available.', 'text-domain' ); ?></p>
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


	var PostPerPage = infinite_scroll_params.PostPerPage;
	var pageNumber = 1;
	var ajaxurl = infinite_scroll_params.ajaxurl;
	var load_more = true;
	var tagid = '' //infinite_scroll_params.tagid;
	var catid = infinite_scroll_params.catid;
	var sync_call = true;

	if(document.getElementsByClassName('blog-loader').length)
	{
	    document.getElementsByClassName('blog-loader')[0].style.visibility = 'hidden';
	}

	function custom_load_posts(catid,tagid, pageNumber) {
	    var str = '&pageNumber=' + pageNumber + '&PostPerPage=' + PostPerPage + '&action=custom_load_more_posts&nonce='+ infinite_scroll_params.nonce+ '&catid=' + catid;
	    var request = new XMLHttpRequest();
	    request.onreadystatechange = function() {
	        
	        if (this.readyState == 4 && this.status == 200) {
	            document.getElementsByClassName('blog-loader')[0].style.visibility = 'hidden';
	            var response = JSON.parse(request.response);
	            var response_html = response.html;
	            load_more = response.load_more;
	            document.getElementById("custom-post-container").innerHTML = document.getElementById("custom-post-container").innerHTML + response_html;

	            sync_call = true;
	        }
	        else
	        {
	            document.getElementsByClassName('blog-loader')[0].style.visibility = 'visible';
	            sync_call = false;
	        }
	    };
	    request.overrideMimeType("application/json");
	    request.open("POST", ajaxurl, true);
	    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	    request.send(str);
	};


	document.addEventListener('scroll', function() {
	    if(document.getElementById('custom-post-container'))
	    {
	        var wrap = document.getElementById('custom-post-container');
	        var contentHeight = wrap.offsetHeight;
	        var yOffset = window.pageYOffset; 
	        var y = yOffset + window.innerHeight;

	        if(y >= (contentHeight))
	        {
	            if(load_more === true )
	            {
	                if(sync_call == true)
	                {
	                    pageNumber = pageNumber + 1;
	                    custom_load_posts(catid ,tagid, pageNumber);        
	                }
	            }
	        }
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

	$cate_id = '';
	if ( is_category() ) {
		$cate_id = get_queried_object_id();
	}

	wp_localize_script(
		'posts-listing-js',
		'infinite_scroll_params',
		array(
			'ajaxurl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'load_more_posts' ),
			'PostPerPage' => get_option( 'posts_per_page' ),
			'catid'       => esc_attr( $cate_id ),
		)
	);

}
add_action( 'wp_enqueue_scripts', 'custom_themes_scripts' );



/**
 * Load more Posts.
 */
function custom_load_more_posts_ajax_handler() {

	check_ajax_referer( 'load_more_posts', 'nonce' );

	$load_more = false;

	$ost_per_page = isset( $_POST['PostPerPage'] ) ? intval( $_POST['PostPerPage'] ) : 3;
	$paged        = isset( $_POST['pageNumber'] ) ? intval( $_POST['pageNumber'] ) : 1;
	$catid        = isset( $_POST['catid'] ) ? intval( $_POST['catid'] ) : '';

	$args = array(
		'post_type'      => 'POST_TYPE',
		'paged'          => $paged,
		'post_status'    => 'publish',
		'posts_per_page' => $ost_per_page,
	);

	if ( ! empty( $catid ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'category',
			'field'    => 'term_id',
			'terms'    => $catid,
		);
	}

	$query = new WP_Query( $args );

	$found_posts = $query->found_posts;
	$post_count  = $query->post_count;
	if ( ! empty( $found_posts ) ) {
		$load_more = true;
	}
	if ( $found_posts === $post_count ) {
		$load_more = false;
	} elseif ( $post_count < $ost_per_page ) {
		$load_more = false;
	}

	$html = '';

	if ( $query->have_posts() ) :
		ob_start();
		while ( $query->have_posts() ) :
			$query->the_post();
			$get_post_id      = get_the_ID();
			$get_post_title   = get_the_title( $get_post_id );
			$get_post_link    = get_the_permalink( $get_post_id );
			$get_post_date    = get_the_date( 'd-m-Y', $get_post_id );
			$get_post_excerpt = get_the_excerpt( $get_post_id );
			$get_post_img     = get_template_directory_uri() . '/assets/images/placeholder/placeholder.webp';
			$photo            = wp_get_attachment_url( get_post_thumbnail_id( $get_post_id, 'full' ) );
			if ( ! empty( $photo ) ) :
				$get_post_img = $photo;
			endif;

			$category_name = '';
			$category_link = '';

			$categories = get_the_category( $get_post_id );
			if ( ! empty( $categories ) ) :
				$first_category = $categories[0];
				if ( 'uncategorized' !== $first_category->slug ) :
					$category_name = $first_category->name;
					$category_link = get_category_link( $first_category->term_id );
				endif;
			endif;
			?>

			<li>
				<a href="<?php echo esc_url( $get_post_link ); ?>">					
					<div class="">
						<div class="">
							<img src="<?php echo esc_url( $get_post_img ); ?>" alt="" width="" height="">
						</div>
						<div class="">
								<span class="date"><?php echo wp_kses_post( $get_post_date ); ?></span>
								<h3><?php echo wp_kses_post( $get_post_title ); ?></h3>
							<div class="description">
								<p><?php echo wp_kses_post( $get_post_excerpt ); ?></p>
							</div>							
						</div>
					</div>
				</a>
			</li>
			<?php
		endwhile;

		$html = ob_get_contents();
		ob_end_clean();
	endif;

	$return = array(
		'html'        => $html,
		'load_more'   => $load_more,
		'post_count'  => $post_count,
		'found_posts' => $found_posts,
	);
	echo wp_json_encode( $return );
	die();
}
add_action( 'wp_ajax_custom_load_more_posts', 'custom_load_more_posts_ajax_handler' );
add_action( 'wp_ajax_nopriv_custom_load_more_posts', 'custom_load_more_posts_ajax_handler' );
