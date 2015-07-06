<?php
/*
	Section: Masonic Gallery
	Author: Jorge Rodriguez (jycr753)
	Author URI: http://www.blog.jorgerodriguez.dk
	Description: A robust gallery section that includes sorting and lightboxing.
	Class Name: PLMasonic
	Filter: format, dual-width
*/

class PLMasonic extends PageLinesSection {

	const POST_TYPE = 'product';
	const POST_STATUS = 'publish';
	const POST_CUSTOM_TAX = 'product_cat';

	private $order_by_menu;
	private $remove_order_by = false;
	private $current_post_type;
	private $internal_post_type;

	private $co = 0;
	var $default_limit = 3;


	function section_persistent(){}

	function section_styles(){
		wp_enqueue_script( 'isotope', PL_JS . '/utils.isotope.min.js', array('jquery'), pl_get_cache_key(), true);
		wp_enqueue_script( 'pl-masonic', $this->base_url.'/pl.masonic.js', array( 'jquery' ), pl_get_cache_key(), true );
	}

	function section_opts(){


		$options = array();

		$options[] = array(

			'title' => __( 'Config', 'pagelines' ),
			'type'	=> 'multi',
			'opts'	=> array(
				array(
					'key'		=> $this->id.'_format',
					'type'		=> 'select',
					'label'		=> __( 'Gallery Format', 'pagelines' ),
					'opts'			=> array(
						'grid'		=> array('name' => __( 'Grid Mode', 'pagelines' ) ),
						'masonry'	=> array('name' => __( 'Image/Masonry', 'pagelines' ) )
					)
				),
				array(
					'key'			=> $this->id.'_mansory_post_type_display',
					'type' 			=> 'select',
					'opts'			=> $this->getAllPossiblePostTypes(),
					'default'		=> 'post',
					'label' 	=> __( 'Select Post Type', 'pagelines' ),
					'help'		=> __( '<strong>Note</strong><br/> Post types for this section must have "featured images" enabled and be public.<br/><strong>Tip</strong><br/> Use a plugin to create custom post types for use.', 'pagelines' ),
				),
				array(
					'key'			=> $this->id.'_sizes',
					'type' 			=> 'select_imagesizes',
					'label' 		=> __( 'Select Thumb Size', 'pagelines' ),
					'help'			=> __( 'For best results use large or full image sizes.', 'pagelines' )
				),

				array(
					'key'			=> $this->id.'_total',
					'type' 			=> 'count_select',
					'count_start'	=> 1,
					'count_number'	=> $this->countAllPost(),
					'default'		=> $this->countAllPost(),
					'label' 		=> __( 'Total Posts Loaded', 'pagelines' ),
				),
				array(
					'key'			=> $this->id.'_excerpt',
					'type' 			=> 'check',
					'default'		=> false,
					'label' 		=> __( 'Display Post Excerpt In Grid Mode', 'pagelines' ),
				)


			)

		);

		$options[] = array(

			'title' => __( 'Masonic Content', 'pagelines' ),
			'type'	=> 'multi',
			'col'	=> 3,
			'help'		=> __( 'Options to control the text and link in the Masonic title.', 'pagelines' ),
			'opts'	=> array(
				array(
					'key'			=> 'default_title',
					'type' 			=> 'text',
					'label' 		=> __( 'Default Title', 'pagelines' ),
				),
				array(
					'key'			=> $this->id.'_meta',
					'type' 			=> 'text',
					'label' 		=> __( 'Masonic Meta', 'pagelines' ),
					'ref'			=> __( 'Use shortcodes to control the dynamic meta info. Example shortcodes you can use are: <ul><li><strong>[post_categories]</strong> - List of categories</li><li><strong>[post_edit]</strong> - Link for admins to edit the post</li><li><strong>[post_tags]</strong> - List of post tags</li><li><strong>[post_comments]</strong> - Link to post comments</li><li><strong>[post_author_posts_link]</strong> - Author and link to archive</li><li><strong>[post_author_link]</strong> - Link to author URL</li><li><strong>[post_author]</strong> - Post author with no link</li><li><strong>[post_time]</strong> - Time of post</li><li><strong>[post_date]</strong> - Date of post</li><li><strong>[post_type]</strong> - Type of post</li></ul>', 'pagelines' ),
				),



			)

		);


		$options[] = array(
			'key'		=> $this->id.'_post_sort',
			'col'		=> 3,
			'type'		=> 'select',
			'label'		=> __( 'Sort elements by postdate', 'pagelines' ),
			'default'	=> 'DESC',
			'opts'			=> array(
				'DESC'		=> array('name' => __( 'Date Descending (default)', 'pagelines' ) ),
				'ASC'		=> array('name' => __( 'Date Ascending', 'pagelines' ) ),
				'rand'		=> array('name'	=> __( 'Random', 'pagelines' ) )
			)
		);

		$options[] = array(
			'key' => $this->id . 'bool_sort_masonic_by_menu_id',
			'type' => 'select',
			'label' => 	__('Sort by menu_order'),
			'default' => 'No',
			'opts' => array(
				'No' => array('name' => 'No (default)'),
				'Yes' => array('name' => 'Yes'),	
			),
		); 


		$selection_opts = array(
			array(
				'key'			=> $this->id.'_meta_key',
				'type' 			=> 'text',

				'label' 	=> __( 'Meta Key', 'pagelines' ),
				'help'		=> __( 'Select only posts which have a certain meta key and corresponding meta value. Useful for featured posts, or similar.', 'pagelines' ),
			),
			array(
				'key'			=> $this->id.'_meta_value',
				'type' 			=> 'text',

				'label' 	=> __( 'Meta Key Value', 'pagelines' ),
			),
		);

		$selection_opts[] = array(
			'label'			=> 'Post Category',
			'key'			=> $this->id.'_category',
			'type'			=> 'select_wp_tax',
			'post_type'		=> $this->opt($this->id.'_post_type'),
			'help'		=> __( 'Only applies for standard blog posts.', 'pagelines' ),
		);



		$options[] = array(

			'title' => __( 'Additional Post Selection', 'pagelines' ),
			'type'	=> 'multi',
			'col'		=> 3,
			'opts'	=> $selection_opts
		);

		return $options;
	}

	function get_masonry_image_size(){

		$n = rand(1, 12);

		$image_sizes = array(
			'basic-thumb',
			'landscape-thumb',
			'tall-thumb',
			'big-thumb'
		);

		if( $n == 1 ){
			return 'big-thumb';
		} elseif ( $n <= 3){
			return 'landscape-thumb';
		} elseif ( $n <= 5){
			return 'tall-thumb';
		} else
			return 'basic-thumb';

	}
	
	function section_template() {

		global $post;
		
		$this->co++;

		$the_query = "";
		$show_excerpt = $this->opt( $this->id . '_excerpt', array( 'default' => false ) );
		$format = $this->opt( $this->id.'_format', array( 'default' => 'masonry' ) );
		$gutter_class = ( $format == 'grid' ) ? 'with-gutter' : '';
			
		$this->current_post_type = $this->opt($this->id . '_mansory_post_type_display', array('default' => 0));

		$pt = get_post_type_object( $this->current_post_type );

		$total = $this->opt($this->id.'_total', array( 'default' => $this->countAllPost() ) );
		$meta = $this->opt($this->id.'_meta', array( 'default' => '[post_date] [post_edit]', 'shortcode' => false ) );

		if( $this->opt($this->id.'_sizes') && $this->opt($this->id.'_sizes') != '' )
			$sizes = $this->opt($this->id.'_sizes');
		elseif( $format == 'masonry' )
			$sizes = $this->get_masonry_image_size();
		else
			$sizes = 'aspect-thumb';


		$sorting = $this->opt($this->id.'_post_sort', array( 'default' => 'DESC' ) );
		$orderby = ( 'rand' == $sorting ) ? 'rand' : 'date';

		$this->order_by_menu = $this->opt($this->id . 'bool_sort_masonic_by_menu_id', array('default' => false));
		
		if($this->order_by_menu == 'Yes'){
			$orderby = 'menu_order';
			$this->remove_order_by = true;
		}

		if($this->remove_order_by){
		
			$the_query = array(
				
				'post_type' 	   => $this->current_post_type,
				'orderby'          => $orderby,
				'order'            => $sorting,
				'_ignore_default_menu_order' => $this->remove_order_by,
				'posts_per_page'   => $total,
			);
		
		}else{

			$the_query = array(
				'post_type' 		=> $this->current_post_type,
				'orderby'           => $orderby,
				'order'             => $sorting,
				'posts_per_page' 	=> $total,
			);
		}
		
		if( $this->opt($this->id.'_meta_key') && $this->opt($this->id.'_meta_key') != '' && $this->opt($this->id.'_meta_value') ){
			$the_query['meta_key'] = $this->opt($this->id.'_meta_key');
			$the_query['meta_value'] = $this->opt($this->id.'_meta_value');
		}


		$filter_tax = $this->opt($this->id.'_category', array( 'default' => 'category' ) );

		$posts =  new WP_Query($the_query);

		$filters = array();
		
		foreach( $posts as $post ){
			$terms = wp_get_post_terms( $post->ID, $filter_tax );
			foreach( $terms as $t ){
				$filters[ $t->slug ] = $t->name;
			}
		}

		$args = array(
			'taxonomy' => $filter_tax
		);
		$list = get_categories( $args );

		if( is_array( $list ) && ! empty( $list ) ){
			foreach( $list as $key => $l ){
				if( ! isset( $filters[$l->slug] ) )
					unset( $list[$key] );
			}
		}
		$default_title = ( $this->opt('default_title') ) ? $this->opt('default_title') : 'All';
		if(!empty($posts)) { ?>
			<div class="masonic-wrap">
				<div class="masonic-header pl-area-ui-element">

					<div class="masonic-header-wrap">
						<div class="masonic-header-content-pad fix">
							<div class="masonic-title"><?php echo $default_title; ?></div>
							<ul class="masonic-nav inline-list">
								<li class="lh"><?php _e( 'Sort', 'pagelines' ); ?>:</li>
								<li class="pl-link"><a href="#" data-filter="*"><?php _e( 'All', 'pagelines' ); ?></a></li>
								<?php
								if( is_array( $list ) && ! empty( $list ) ){
									foreach( $list as $key => $l ){
										printf('<li><a href="#" data-filter=".%s">%s</a></li>', $l->slug, ucwords($l->name) );
									}
								}

								?>
							</ul>
						</div>
					</div>
				</div>
				<ul class="masonic-gallery row row-set <?php echo $gutter_class;?> no-transition"  data-format="<?php echo $format;?>">
		<?php } ?>

			<?php

			if(!empty($posts)):
				$item_cols = 3;
				$count = 1;
				$total = count($posts);
				
				if ( $posts->have_posts() ): 
					while ( $posts->have_posts() ): 
						$posts->the_post();
						$filters = wp_get_post_terms( $post->ID, $filter_tax );
						$filter_classes = array();
						if( is_array($filters) && ! empty($filters) ){
							foreach( $filters as $f ){
								$filter_classes[] = $f->slug;
							}
						}
						?>
						<li class="span3 <?php echo join( ' ', $filter_classes);?>">
							<div class="span-wrap pl-grid-wrap">
								<div class="pl-grid-image fix">
									<?php
									if ( has_post_thumbnail() )
										echo get_the_post_thumbnail( $post->ID, $sizes	, array('title' => ''));
									else
										printf('<img src="%s" alt="no image added yet." />', pl_default_image());


										 ?>

									<div class="pl-grid-image-hover"></div>

									<a class="pl-grid-image-info" href="<?php echo get_permalink();?>">

										<div class="pl-center-table"><div class="pl-center-cell">

											<?php if( $format == 'masonry' ): ?>
												<h4>
													<?php the_title(); ?>
												</h4>
												<div class="metabar">
													<?php  echo do_shortcode( '[post_date]' ); ?>
												</div>
											<?php else: ?>
												<div class="info-text"><i class="icon icon-link"></i></div>
											<?php endif;?>
										</div></div>

									</a>
								</div><!--work-item-->

								<?php if( $format == 'grid' ) : ?>
									<div class="pl-grid-content fix">
										<div class="fix">
											<div class="pl-grid-meta">
												<?php echo (is_object($post) ? do_shortcode( sprintf( '[pl_karma post="%s"]', $post->ID ) ) : " " );?>
											</div>
											<div class="pl-grid-text">
												<h4>
													<a href="<?php echo get_permalink();?>">
													<?php the_title(); ?>
													</a>
												</h4>
												<div class="pl-grid-metabar">
													<?php echo do_shortcode( $meta ); ?>
												</div>

											</div>
										</div>
										<?php if( $show_excerpt ): ?>
										<div class="pl-grid-excerpt pl-border">
											<?php the_excerpt();?>
										</div>
										<?php endif;?>

									</div>
								<?php endif;?>

								<div class="clear"></div>
							</div>
						</li>
						<?php
						$count++;
					endwhile;
				endif;
			endif;
			if(!empty($posts))
		 		echo '</ul></div>';
	}

	private function getAllPossiblePostTypes(){
		$ars = array();
		$counter = 0;
		$args = array(
			'public' => true		
		);
		$output = 'names';
		$operator = 'and';
		$post_types = get_post_types($args, $output, $operator);
		
		foreach($post_types as $post_type){
			$ars[$post_type] = array('name' => $this->getCategoryNameBySlug($post_type));

			$counter ++;
		}
	
		return (!empty($ars) ? $ars : false);
	}

	private function getCategoryNameBySlug($post_type){
		$post_object = get_post_type_object( $post_type );
		return $post_object->labels->singular_name;
	}

	private function countAllPost(){
		$count_post = wp_count_posts($this->current_post_type);
		return ($count_post->publish > 0 ? $count_post->publish : 0);
	}

}

class Walker_Masonic_Filter extends Walker_Category {

   function start_el(&$output, $category, $depth = 0, $args = array(), $current_object_id = 0) {

      extract($args);
      $cat_slug = esc_attr( $category->slug );
      $cat_slug = apply_filters( 'list_cats', $cat_slug, $category );

      $link = '<li><a href="#" data-filter=".'.strtolower(preg_replace('/\s+/', '-', $cat_slug)).'">';

	  $cat_name = esc_attr( $category->name );
      $cat_name = apply_filters( 'list_cats', $cat_name, $category );

      $link .= $cat_name;

      if(!empty($category->description)) {
         $link .= ' <span>'.$category->description.'</span>';
      }

      $link .= '</a>';

      $output .= $link;

   }
}
