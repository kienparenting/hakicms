<?php
/**
 * File: includes/class-hakicms-shortcodes.php
 * Description: Version 5.7.3 - Fixed Security, I18n Placeholders, and Output Escaping
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HAKICMS_Shortcodes {

	public function __construct() {
		// Đăng ký Shortcodes
		add_shortcode( 'hakicms_course', [ $this, 'render_course_details' ] );
		add_shortcode( 'hakicms_dashboard', [ $this, 'render_student_dashboard' ] );
		add_shortcode( 'hakicms_course_list', [ $this, 'render_course_list' ] );
		add_shortcode( 'hakicms_lesson_list', [ $this, 'render_lesson_list_sidebar' ] );

		add_filter( 'the_content', [ $this, 'clean_legacy_content_for_theme_builder' ], 1 );

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
		add_action( 'wp_ajax_hakicms_load_lesson', [ $this, 'ajax_load_lesson' ] );
		add_action( 'wp_ajax_nopriv_hakicms_load_lesson', [ $this, 'ajax_load_lesson' ] );
	}

	public function clean_legacy_content_for_theme_builder( $content ) {
		if ( ! is_singular( 'post' ) || is_admin() ) {
			return $content;
		}
		$patterns = [
			'/\[mycam_lesson_list\s*.*?\]/i',
			'/\[hakicms_lesson_list\s*.*?\]/i',
			'/\[kien_course_list\s*.*?\]/i'
		];
		return preg_replace( $patterns, '', $content );
	}

	public static function get_smart_course_category( $post_id = 0 ) {
		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$cats = get_the_category( $post_id );
		if ( empty( $cats ) ) {
			return 0;
		}
		foreach ( $cats as $cat ) {
			if ( get_term_meta( $cat->term_id, '_hakicms_is_course', true ) === 'yes' ) {
				return $cat->term_id;
			}
		}
		return $cats[0]->term_id;
	}

	public function render_lesson_list_sidebar() {
		$post_id = get_the_ID();
		$course_cat_id = self::get_smart_course_category( $post_id );
		if ( ! $course_cat_id ) {
			return '';
		}

		$lessons = get_posts( [
			'category'       => $course_cat_id,
			'posts_per_page' => -1,
			'orderby'        => 'menu_order',
			'order'          => 'ASC',
			'post_status'    => 'publish'
		] );

		if ( empty( $lessons ) ) {
			return '';
		}

		$has_access = false;
		$gatekeeper = new HAKICMS_Gatekeeper();
		
		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$template = get_posts( [
			'post_type'      => 'course_template',
			'meta_query'     => [ [ 'key' => '_hakicms_template_settings', 'value' => '"category_id";i:' . $course_cat_id . ';', 'compare' => 'LIKE' ] ],
			'posts_per_page' => 1
		] );
		// phpcs:enable
		
		if ( ! empty( $template ) ) {
			$has_access = $gatekeeper->user_has_access( $template[0]->ID );
		}

		$settings = get_option( 'hakicms_settings' );
		$free_label = ! empty( $settings['free_label'] ) ? $settings['free_label'] : 'FREE';

		ob_start();
		?>
		<div class="hakicms-sidebar-curriculum" style="margin-bottom: 25px; border: 1px solid #e1e8ed; border-radius: 12px; overflow: hidden; font-family: 'Be Vietnam Pro', sans-serif;">
			<div style="background: #f8f9fa; padding: 15px; border-bottom: 1px solid #e1e8ed;">
				<h4 style="margin:0; font-size: 15px; color: #1a2b3c; font-weight: 700; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-format-aside" style="font-size: 18px; width: 18px; height: 18px;"></span>
					<?php esc_html_e( 'DASHBOARD BÀI HỌC', 'hakicms' ); ?>
				</h4>
			</div>
			<div class="hakicms-lessons-grid" style="padding: 12px; background: #fff; max-height: 500px; overflow-y: auto;">
				<?php 
				$count = 1;
				foreach ( $lessons as $lesson ) : 
					$can_view = apply_filters( 'hakicms_can_view_lesson', $has_access, $lesson->ID );
					$is_preview = ( ! $has_access && $can_view );
					$ajax_class = $is_preview ? 'hakicms-ajax-lesson' : '';
					$is_active = ( $lesson->ID == $post_id );
					$active_style = $is_active ? 'background: #f0f7ff; border-color: #3498db; box-shadow: inset 3px 0 0 #3498db;' : '';
					?>
					<a href="<?php echo esc_url( get_permalink( $lesson->ID ) ); ?>" 
					   class="hakicms-lesson-card <?php echo esc_attr( $can_view ? 'unlocked' : 'locked' ); ?> <?php echo esc_attr( $ajax_class ); ?>" 
					   data-id="<?php echo absint( $lesson->ID ); ?>" 
					   style="display: block; text-decoration: none; padding: 10px 12px; margin-bottom: 6px; border-radius: 8px; border: 1px solid #f0f0f0; <?php echo esc_attr( $active_style ); ?>">
						<div style="display: flex; justify-content: space-between; align-items: center; gap: 10px;">
							<span style="font-size: 13px; color: #444; line-height: 1.4; font-weight: <?php echo $is_active ? '600' : '400'; ?>;">
								<span style="color: #abb8c3; margin-right: 4px;"><?php echo esc_html( str_pad( $count++, 2, '0', STR_PAD_LEFT ) ); ?>.</span> 
								<?php echo esc_html( get_the_title( $lesson->ID ) ); ?>
							</span>
							<div style="flex-shrink: 0;">
								<?php if ( ! $can_view ) : ?>
									<span class="dashicons dashicons-lock" style="font-size: 14px; color: #d1d5db;"></span>
								<?php elseif ( $is_preview ) : ?>
									<span style="font-size: 8px; background: #27ae60; color: #fff; padding: 1px 4px; border-radius: 4px; font-weight: 800;"><?php echo esc_html( $free_label ); ?></span>
								<?php endif; ?>
							</div>
						</div>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_frontend_assets() {
		wp_enqueue_style( 'hakicms-fonts', 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap', [], HAKICMS_VERSION );
		wp_enqueue_style( 'hakicms-main-style', HAKICMS_URL . 'assets/css/dashboard.css', [], HAKICMS_VERSION );
		wp_enqueue_script( 'hakicms-progress-js', HAKICMS_URL . 'assets/js/progress.js', [ 'jquery' ], HAKICMS_VERSION, true );
		wp_localize_script( 'hakicms-progress-js', 'hakicms_vars', [
			'ajax_url'        => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'hakicms_front_nonce' ),
			'current_post_id' => is_singular( 'post' ) ? get_the_ID() : 0
		] );
	}

	public function ajax_load_lesson() {
		check_ajax_referer( 'hakicms_front_nonce', 'security' );
		
		$pid = isset( $_POST['pid'] ) ? absint( wp_unslash( $_POST['pid'] ) ) : 0;
		if ( ! $pid ) {
			wp_send_json_error();
		}

		$post_obj = get_post( $pid );
		if ( ! $post_obj ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		$content = apply_filters( 'the_content', $post_obj->post_content );
		
		wp_send_json_success( [
			'title'   => esc_html( get_the_title( $pid ) ),
			'content' => wp_kses_post( $content )
		] );
	}

	public function render_course_details( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'hakicms_course' );
		$course_id = absint( $atts['id'] ) ?: get_the_ID();
		
		$meta = get_post_meta( $course_id, '_hakicms_template_settings', true );
		$global_settings = get_option( 'hakicms_settings' );
		$cat_id = ! empty( $meta['category_id'] ) ? absint( $meta['category_id'] ) : 0;
		
		$button_label = ! empty( $meta['button_label'] ) ? $meta['button_label'] : ( $global_settings['default_button_label'] ?? 'GET STARTED' );
		$enrolled_label = ! empty( $meta['enrolled_label'] ) ? $meta['enrolled_label'] : ( $global_settings['default_enrolled_label'] ?? 'ENROLLED' );

		$gatekeeper = new HAKICMS_Gatekeeper();
		$has_access = $gatekeeper->user_has_access( $course_id );
		
		$product_id = get_post_meta( $course_id, '_hakicms_linked_product_id', true );
		$price_html = '';
		$purchase_url = '#';

		if ( class_exists( 'WooCommerce' ) && $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$price_html = $product->get_price_html();
				$purchase_url = add_query_arg( 'add-to-cart', absint( $product_id ), wc_get_checkout_url() );
			}
		}

		ob_start();
		?>
		<div class="hakicms-main-wrapper">
			<div class="hakicms-white-card">
				<h2 class="hakicms-title-main"><?php echo esc_html( get_the_title( $course_id ) ); ?></h2>
				<?php if ( ! empty( $meta['subtitle'] ) ) : ?>
					<h3 class="hakicms-subtitle"><?php echo esc_html( $meta['subtitle'] ); ?></h3>
				<?php endif; ?>
				
				<div class="hakicms-course-desc">
					<?php 
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					echo wp_kses_post( apply_filters( 'the_content', get_post( $course_id )->post_content ) ); 
					?>
				</div>

				<div class="hakicms-hero-action">
					<?php if ( $has_access ) : ?>
						<span class="hakicms-cta-button enrolled"><?php echo esc_html( $enrolled_label ); ?></span>
					<?php else : ?>
						<?php if ( $price_html ) : ?>
							<div class="hakicms-price-stack"><?php echo wp_kses_post( $price_html ); ?></div>
						<?php endif; ?>
						<a href="<?php echo esc_url( $purchase_url ); ?>" class="hakicms-cta-button"><?php echo esc_html( $button_label ); ?></a>
					<?php endif; ?>
				</div>
			</div>
			
			<div class="hakicms-blue-card">
				<h2 class="hakicms-section-title"><?php esc_html_e( 'COURSE CURRICULUM', 'hakicms' ); ?></h2>
				<div class="hakicms-lessons-grid">
					<?php $this->render_lessons_list_html( $cat_id, $has_access ); ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	private function render_lessons_list_html( $cat_id, $has_access ) {
		if ( ! $cat_id ) return;
		$lessons = get_posts( [ 'category' => $cat_id, 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC', 'post_status' => 'publish' ] );
		$settings = get_option( 'hakicms_settings' );
		$free_label = ! empty( $settings['free_label'] ) ? $settings['free_label'] : 'FREE';

		$count = 1;
		foreach ( $lessons as $post ) {
			$can_view = apply_filters( 'hakicms_can_view_lesson', $has_access, $post->ID );
			$is_preview = ( ! $has_access && $can_view );
			?>
			<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" class="hakicms-lesson-card <?php echo esc_attr( $can_view ? 'unlocked' : 'locked' ); ?>" data-id="<?php echo absint( $post->ID ); ?>">
				<div class="lesson-content-flex" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
					<div class="lesson-main-info">
						<span class="lesson-label"><?php esc_html_e( 'Lesson', 'hakicms' ); ?> <?php echo absint( $count++ ); ?>: </span>
						<span class="lesson-title"><?php echo esc_html( get_the_title( $post->ID ) ); ?></span>
						<?php if ( $is_preview ) : ?>
							<span class="badge-free" style="background: #27ae60; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 4px; margin-left:10px;"><?php echo esc_html( $free_label ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! $can_view ) : ?>
						<span class="dashicons dashicons-lock" style="color: #bbb;"></span>
					<?php endif; ?>
				</div>
			</a>
			<?php
		}
	}

	public function render_course_list( $atts ) {
		$atts = shortcode_atts( [
			'columns'        => '2',
			'layout'         => 'grid',
			'excerpt_length' => '25',
			'mode'           => 'all',    
			'include'        => '',       
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			'exclude'        => ''        
		], $atts, 'hakicms_course_list' );

		$args = [ 'post_type' => 'course_template', 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC' ];

		if ( $atts['mode'] === 'include' && ! empty( $atts['include'] ) ) {
			$args['post__in'] = array_map( 'absint', explode( ',', $atts['include'] ) );
		} elseif ( $atts['mode'] === 'exclude' && ! empty( $atts['exclude'] ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			$args['post__not_in'] = array_map( 'absint', explode( ',', $atts['exclude'] ) );
		}

		$query = new WP_Query( $args );
		if ( ! $query->have_posts() ) return '';

		ob_start();
		?>
		<div class="haki-v6-grid-container" style="display: grid; grid-template-columns: repeat(<?php echo absint( $atts['columns'] ); ?>, 1fr); gap: 20px;">
			<?php while ( $query->have_posts() ) : $query->the_post(); ?>
				<div class="haki-v6-card" style="border: 1px solid #eee; padding: 20px; border-radius: 15px;">
					<h3 class="haki-v6-title"><?php the_title(); ?></h3>
					<div class="haki-v6-body"><?php echo esc_html( wp_trim_words( get_the_content(), absint( $atts['excerpt_length'] ) ) ); ?></div>
					<div class="haki-v6-footer" style="margin-top:15px;">
						<a href="<?php the_permalink(); ?>" class="button"><?php esc_html_e( 'VIEW COURSE', 'hakicms' ); ?></a>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_student_dashboard() {
		if ( ! is_user_logged_in() ) {
			return sprintf( 
				/* translators: %s: URL đăng nhập */
				esc_html__( 'Please %s to view dashboard.', 'hakicms' ), 
				'<a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'login', 'hakicms' ) . '</a>' 
			);
		}

		$user_id = get_current_user_id();
		$package = get_user_meta( $user_id, '_hakicms_package_type', true );
		$purchased_courses = get_user_meta( $user_id, '_hakicms_purchased_course_ids', true ) ?: [];

		ob_start();
		?>
		<div class="hakicms-dashboard">
			<div class="hakicms-user-info-header">
				<strong><?php esc_html_e( 'Membership Tier:', 'hakicms' ); ?></strong> 
				<span class="tier-badge"><?php echo esc_html( strtoupper( $package ?: __( 'Standard User', 'hakicms' ) ) ); ?></span>
			</div>
			<div class="hakicms-dashboard-grid" style="margin-top:20px;">
				<?php 
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$courses = get_terms( [ 'taxonomy' => 'category', 'hide_empty' => false, 'meta_query' => [ [ 'key' => '_hakicms_is_course', 'value' => 'yes' ] ] ] );
				// phpcs:enable
				foreach ( $courses as $course ) {
					$all_lessons = get_posts( [ 'category' => $course->term_id, 'fields' => 'ids', 'numberposts' => -1 ] );
					if ( empty( $all_lessons ) ) continue;
					
					$completed_data = get_user_meta( $user_id, 'hakicms_completed_posts', true ) ?: [];
					$done = 0;
					foreach ( $all_lessons as $id ) { if ( array_key_exists( $id, $completed_data ) ) $done++; }
					
					$has_access = ( in_array( $course->term_id, (array) $purchased_courses ) || current_user_can( 'manage_options' ) );
					$percent = ( count( $all_lessons ) > 0 ) ? round( ( $done / count( $all_lessons ) ) * 100 ) : 0;
					?>
					<div class="hakicms-course-progress-card" style="border:1px solid #eee; padding:15px; border-radius:10px; margin-bottom:10px;">
						<h4><?php echo esc_html( $course->name ); ?></h4>
						<div class="progress-bar-bg" style="background:#eee; height:10px; border-radius:5px;">
							<div style="background:#3498db; width:<?php echo absint( $percent ); ?>%; height:10px; border-radius:5px;"></div>
						</div>
						<p style="font-size:12px; margin-top:5px;">
							<?php 
							/* translators: 1: Số bài đã xong, 2: Tổng số bài, 3: Phần trăm */
							printf( esc_html__( '%1$d/%2$d lessons (%3$d%%)', 'hakicms' ), absint( $done ), absint( count( $all_lessons ) ), absint( $percent ) ); 
							?>
						</p>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}