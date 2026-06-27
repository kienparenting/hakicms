<?php
/**
 * File: admin/class-hakicms-admin-settings.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HAKICMS_Admin_Settings {

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_menu_pages' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_init', [ $this, 'handle_clear_cache' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
	}

	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		if ( 'hakicms_clear_stats' === $action ) {
			check_admin_referer( 'hakicms_admin_action' );
			delete_transient( 'hakicms_admin_stats' );
			delete_transient( 'hakicms_market_data' );
			wp_safe_redirect( admin_url( 'admin.php?page=hakicms-dashboard&cache=cleared' ) );
			exit;
		}

		if ( 'force_update_check' === $action ) {
			check_admin_referer( 'hakicms_admin_action' );
			delete_site_transient( 'update_plugins' );
			wp_safe_redirect( admin_url( 'admin.php?page=hakicms-dashboard&update_checked=1' ) );
			exit;
		}
	}

	public function add_menu_pages() {
		// Đổi tên thành Marketplace và trỏ tới đúng trang
		$pro_menu_label = '<span style="color:#f39c12; font-weight:bold;">🔥 ' . __( 'Marketplace', 'hakicms' ) . '</span>';
		$pro_page_title = __( 'HakiCMS Marketplace', 'hakicms' );

		// Đổi position thành 55 để không chiếm vị trí quá cao (luật WP.org)
		add_menu_page( 'HakiCMS', 'HakiCMS', 'manage_options', 'hakicms-dashboard', [ $this, 'render_dashboard_page' ], 'dashicons-welcome-learn-more', 55 );
		add_submenu_page( 'hakicms-dashboard', __( 'Dashboard', 'hakicms' ), __( 'Dashboard', 'hakicms' ), 'manage_options', 'hakicms-dashboard', [ $this, 'render_dashboard_page' ] );
		add_submenu_page( 'hakicms-dashboard', __( 'Settings', 'hakicms' ), __( 'Settings', 'hakicms' ), 'manage_options', 'hakicms-settings', [ $this, 'render_settings_page' ] );
		add_submenu_page( 'hakicms-dashboard', $pro_page_title, $pro_menu_label, 'manage_options', 'hakicms-pro', [ $this, 'render_pro_page' ] );
	}

	public function register_settings() {
		register_setting( 'hakicms_settings_group', 'hakicms_settings', [ $this, 'sanitize_settings' ] );
		
		add_settings_section( 'hakicms_core_section', __( 'General Access Settings', 'hakicms' ), null, 'hakicms-settings' );
		add_settings_field( 'default_button_label', __( 'Default Button Label', 'hakicms' ), [ $this, 'render_text_input' ], 'hakicms-settings', 'hakicms_core_section', [ 'id' => 'default_button_label', 'default' => 'GET STARTED' ] );
		add_settings_field( 'default_enrolled_label', __( 'Default Enrolled Label', 'hakicms' ), [ $this, 'render_text_input' ], 'hakicms-settings', 'hakicms_core_section', [ 'id' => 'default_enrolled_label', 'default' => 'ENROLLED' ] );
		add_settings_field( 'free_label', __( 'Free Badge Label', 'hakicms' ), [ $this, 'render_text_input' ], 'hakicms-settings', 'hakicms_core_section', [ 'id' => 'free_label', 'default' => 'FREE' ] );
		add_settings_field( 'redirect_page_id', __( 'Access Denied Redirect', 'hakicms' ), [ $this, 'render_dropdown_pages' ], 'hakicms-settings', 'hakicms_core_section', [ 'id' => 'redirect_page_id', 'desc' => __( 'Redirect non-enrolled users to this page.', 'hakicms' ) ] );
		add_settings_field( 'locked_msg', __( 'Locked Content Message', 'hakicms' ), [ $this, 'render_textarea' ], 'hakicms-settings', 'hakicms_core_section', [ 'id' => 'locked_msg', 'desc' => __( 'Message shown when content is restricted. Supports HTML.', 'hakicms' ) ] );

		add_settings_section( 'hakicms_taxonomy_section', __( 'Instructors & Tags Management', 'hakicms' ), null, 'hakicms-settings' );
		add_settings_field( 'instructor_section_title', __( 'Instructors Section Title', 'hakicms' ), [ $this, 'render_text_input' ], 'hakicms-settings', 'hakicms_taxonomy_section', [ 'id' => 'instructor_section_title', 'default' => 'Instructors' ] );
		add_settings_field( 'instructors_list', __( 'Instructors List', 'hakicms' ), [ $this, 'render_instructors_manager' ], 'hakicms-settings', 'hakicms_taxonomy_section' );
		add_settings_field( 'tags_list', __( 'Course Tags List', 'hakicms' ), [ $this, 'render_tags_manager' ], 'hakicms-settings', 'hakicms_taxonomy_section' );
	}

	public function sanitize_settings( $input ) {
		$sanitized = [];
		if ( is_array( $input ) ) {
			foreach ( $input as $key => $val ) {
				// Sửa lỗi Sanitization theo đúng kiểu dữ liệu
				if ( 'redirect_page_id' === $key ) {
					$sanitized[ $key ] = absint( $val );
				} elseif ( 'locked_msg' === $key ) {
					$sanitized[ $key ] = wp_kses_post( $val );
				} elseif ( 'instructors_list' === $key || 'tags_list' === $key ) {
					$sanitized[ $key ] = sanitize_textarea_field( $val );
				} else {
					$sanitized[ $key ] = sanitize_text_field( $val );
				}
			}
		}
		return $sanitized;
	}

	public function render_instructors_manager() {
		$options = get_option( 'hakicms_settings' );
		$val = isset( $options['instructors_list'] ) ? $options['instructors_list'] : '[]';
		wp_enqueue_media();
		?>
		<div id="haki-instructors-app">
			<div id="haki-instructors-list" style="margin-bottom: 15px;"></div>
			<button type="button" class="button haki-add-instructor" style="border-color: #3498db; color: #3498db;">+ <?php esc_html_e( 'Add New Instructor', 'hakicms' ); ?></button>
			<input type="hidden" name="hakicms_settings[instructors_list]" id="haki-instructors-data" value="<?php echo esc_attr( $val ); ?>">
		</div>
		<?php
	}

	public function render_tags_manager() {
		$options = get_option( 'hakicms_settings' );
		$val = isset( $options['tags_list'] ) ? $options['tags_list'] : '[]';
		?>
		<div id="haki-tags-app">
			<div id="haki-tags-container" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px;"></div>
			<div style="display: flex; gap: 10px;">
				<input type="text" id="haki-new-tag" class="regular-text" placeholder="e.g. Online 100%">
				<button type="button" class="button haki-add-tag"><?php esc_html_e( 'Add Tag', 'hakicms' ); ?></button>
			</div>
			<input type="hidden" name="hakicms_settings[tags_list]" id="haki-tags-data" value="<?php echo esc_attr( $val ); ?>">
		</div>
		<?php
	}

	public function render_text_input( $args ) {
		$options = get_option( 'hakicms_settings' );
		$val = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : ( $args['default'] ?? '' );
		echo '<input type="text" name="hakicms_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $val ) . '" class="regular-text">';
	}

	public function render_textarea( $args ) {
		$options = get_option( 'hakicms_settings' );
		$val = isset( $options[ $args['id'] ] ) ? $options[ $args['id'] ] : '';
		echo '<textarea name="hakicms_settings[' . esc_attr( $args['id'] ) . ']" rows="5" class="large-text">' . esc_textarea( $val ) . '</textarea>';
		echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
	}

	public function render_dropdown_pages( $args ) {
		$options = get_option( 'hakicms_settings' );
		$selected_val = isset( $options[ $args['id'] ] ) ? absint( $options[ $args['id'] ] ) : 0;
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		wp_dropdown_pages( [ 'name' => 'hakicms_settings[' . esc_attr( $args['id'] ) . ']', 'selected' => esc_attr( $selected_val ), 'show_option_none' => esc_html__( '— Select Page —', 'hakicms' ) ] );
		echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
	}

	public function render_dashboard_page() {
		$stats = $this->get_dashboard_stats();
		$nonce = wp_create_nonce( 'hakicms_admin_action' );
		?>
		<div class="wrap haki-dashboard-wrapper">
			<div class="haki-header-flex">
				<h1 style="margin:0;"><?php esc_html_e( 'HakiCMS Command Center', 'hakicms' ); ?></h1>
				<div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hakicms-dashboard&action=force_update_check&_wpnonce=' . $nonce ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Check for Updates', 'hakicms' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=hakicms-dashboard&action=hakicms_clear_stats&_wpnonce=' . $nonce ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Refresh Stats', 'hakicms' ); ?></a>
				</div>
			</div>

			<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
			<?php if ( isset( $_GET['update_checked'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Checked for updates successfully!', 'hakicms' ); ?></p></div>
			<?php endif; ?>

			<div class="haki-stats-grid">
				<div class="haki-stat-card"><span class="val"><?php echo esc_html( $stats['total_students'] ); ?></span><span class="lbl"><?php esc_html_e( 'Total Enrolled', 'hakicms' ); ?></span></div>
				<div class="haki-stat-card"><span class="val"><?php echo esc_html( $stats['lessons_count'] ); ?></span><span class="lbl"><?php esc_html_e( 'Published Lessons', 'hakicms' ); ?></span></div>
			</div>

			<h2><?php esc_html_e( 'Active Courses Performance', 'hakicms' ); ?></h2>
			<table class="haki-course-table wp-list-table widefat fixed striped">
				<thead><tr><th><?php esc_html_e( 'Course Name', 'hakicms' ); ?></th><th><?php esc_html_e( 'Students', 'hakicms' ); ?></th><th style="text-align:right;"><?php esc_html_e( 'Actions', 'hakicms' ); ?></th></tr></thead>
				<tbody>
					<?php if ( empty( $stats['courses_detailed'] ) ) : ?>
						<tr><td colspan="3" style="text-align:center; padding: 30px; color:#999;"><?php esc_html_e( 'No courses found. Start creating!', 'hakicms' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $stats['courses_detailed'] as $c ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $c['title'] ); ?></strong></td>
								<td><span class="student-badge"><?php echo absint( $c['students'] ); ?> <?php esc_html_e( 'Students', 'hakicms' ); ?></span></td>
								<td style="text-align:right;"><a href="<?php echo esc_url( get_edit_post_link( $c['id'] ) ); ?>" class="button button-small"><?php esc_html_e( 'Manage', 'hakicms' ); ?></a></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'HakiCMS Settings', 'hakicms' ); ?></h1>
			<div id="tab-general" class="haki-tab-content" style="margin-top:20px;">
				<form action="options.php" method="post" style="background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:4px;">
					<?php settings_fields( 'hakicms_settings_group' ); do_settings_sections( 'hakicms-settings' ); submit_button(); ?>
				</form>
			</div>
		</div>
		<?php
	}

	public function render_pro_page() {
		$remote_data = $this->fetch_remote_addons();
		echo '<div class="wrap haki-marketplace-container"><h1>' . esc_html__( 'HakiCMS Marketplace', 'hakicms' ) . '</h1>';
		
		if ( empty( $remote_data ) ) {
			echo '<p>' . esc_html__( 'Connecting to HakiCMS Cloud...', 'hakicms' ) . '</p>';
		} else {
			foreach ( $remote_data as $cat_name => $addons ) {
				echo '<h2 class="haki-cat-title">' . esc_html( $cat_name ) . '</h2>';
				echo '<div class="haki-addon-grid">';
				foreach ( $addons as $addon ) {
					// Hiển thị icon thay thế nếu không có ảnh
					$icon_html = '<div class="haki-addon-icon"><span class="dashicons dashicons-admin-plugins"></span></div>';
					?>
					<div class="haki-addon-card">
						<?php echo wp_kses_post($icon_html); ?>
						<div class="haki-addon-body">
							<h3 class="haki-addon-name"><?php echo esc_html( $addon['title'] ); ?></h3>
							<p><?php echo esc_html( $addon['excerpt'] ?? '' ); ?></p>
						</div>
						<div class="haki-addon-footer">
							<span style="font-weight:bold; color:#d63638;"><?php echo esc_html( $addon['price'] ?? 'Free' ); ?></span>
							<a href="<?php echo esc_url( $addon['url'] ?? '#' ); ?>" target="_blank" class="button button-primary"><?php esc_html_e( 'GET IT', 'hakicms' ); ?></a>
						</div>
					</div>
					<?php
				}
				echo '</div>';
			}
		}
		echo '</div>';
	}

	private function get_dashboard_stats() {
		$cached = get_transient( 'hakicms_admin_stats' );
		if ( false !== $cached ) return $cached;
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total_students = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM $wpdb->usermeta WHERE meta_key = '_hakicms_purchased_course_ids' AND meta_value != 'a:0:{}'" );

		$stats = [
			'course_count'    => count( get_posts( [ 'post_type' => 'course_template', 'numberposts' => -1 ] ) ),
			'total_students'  => $total_students,
			'lessons_count'   => wp_count_posts( 'post' )->publish,
			'courses_detailed'=> []
		];

		$templates = get_posts( [ 'post_type' => 'course_template', 'numberposts' => -1 ] );
		foreach ( $templates as $t ) {
			$meta = get_post_meta( $t->ID, '_hakicms_template_settings', true );
			$cat_id = isset( $meta['category_id'] ) ? absint( $meta['category_id'] ) : 0;
			$count = 0;
			if ( $cat_id ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT user_id) FROM $wpdb->usermeta WHERE meta_key = '_hakicms_purchased_course_ids' AND meta_value LIKE %s", '%' . 'i:' . $cat_id . ';' . '%' ) );
			}
			$stats['courses_detailed'][] = [ 'id' => $t->ID, 'title' => $t->post_title, 'students' => $count ];
		}
		set_transient( 'hakicms_admin_stats', $stats, HOUR_IN_SECONDS );
		return $stats;
	}

	private function fetch_remote_addons() {
		$cache = get_transient( 'hakicms_market_data' ); 
		if ( $cache ) return $cache;
		$response = wp_remote_get( 'https://hakicms.com/wp-json/hakicms/v1/addons-directory', [ 'timeout' => 15 ] );
		if ( is_wp_error( $response ) ) return [];
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $data && is_array( $data ) ) set_transient( 'hakicms_market_data', $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * NẠP CSS/JS CHO GIAO DIỆN ADMIN CHUẨN WP.ORG
	 */
	public function enqueue_admin_assets( $hook ) {
		// Chỉ load trên trang của HakiCMS
		if ( strpos( $hook, 'hakicms' ) === false ) return;

		wp_register_style( 'hakicms-admin-style', false, [], HAKICMS_VERSION );
		wp_enqueue_style( 'hakicms-admin-style' );
		
		// CSS Sửa lỗi mất thẻ và Layout Marketplace
		$css = "
			.haki-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
			.haki-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
			.haki-stat-card { background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
			.haki-stat-card .val { display: block; font-size: 32px; font-weight: bold; color: #2271b1; }
			.haki-stat-card .lbl { color: #646970; font-size: 14px; font-weight: 500; text-transform: uppercase; }
			.haki-addon-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; margin-bottom: 40px; }
			.haki-addon-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
			.haki-addon-icon { background: #f0f0f1; text-align: center; padding: 30px 0; border-bottom: 1px solid #ccd0d4; }
			.haki-addon-icon .dashicons { font-size: 40px; width: 40px; height: 40px; color: #a7aaad; }
			.haki-addon-body { padding: 20px; flex-grow: 1; }
			.haki-addon-name { margin: 0 0 10px 0; font-size: 16px; color: #1d2327; }
			.haki-addon-footer { padding: 15px 20px; background: #f9f9f9; border-top: 1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center; }
			.haki-tag-item { display: inline-block; background: #e0e0e0; padding: 5px 10px; border-radius: 4px; font-size: 13px; }
			.haki-tag-item span.remove-tag { margin-left: 8px; color: red; cursor: pointer; font-weight: bold; }
		";
		wp_add_inline_style( 'hakicms-admin-style', $css );

		// JS Kích hoạt nút Add Tag & Add Instructor
		wp_register_script( 'hakicms-admin-script', '', [], HAKICMS_VERSION, true );
		wp_enqueue_script( 'hakicms-admin-script' );
		$js = "
			jQuery(document).ready(function($) {
				// TAGS LOGIC
				var tagsData = $('#haki-tags-data').val();
				var tagsArray = tagsData ? JSON.parse(tagsData) : [];
				function renderTags() {
					$('#haki-tags-container').empty();
					tagsArray.forEach(function(tag, index) {
						$('#haki-tags-container').append('<div class=\"haki-tag-item\">' + tag + ' <span class=\"remove-tag\" data-index=\"' + index + '\">x</span></div>');
					});
					$('#haki-tags-data').val(JSON.stringify(tagsArray));
				}
				renderTags();
				$('.haki-add-tag').on('click', function() {
					var newTag = $('#haki-new-tag').val().trim();
					if(newTag && !tagsArray.includes(newTag)) { tagsArray.push(newTag); $('#haki-new-tag').val(''); renderTags(); }
				});
				$(document).on('click', '.remove-tag', function() {
					tagsArray.splice($(this).data('index'), 1); renderTags();
				});

				// INSTRUCTORS LOGIC
				var insData = $('#haki-instructors-data').val();
				var insArray = insData ? JSON.parse(insData) : [];
				function renderInstructors() {
					$('#haki-instructors-list').empty();
					insArray.forEach(function(ins, index) {
						$('#haki-instructors-list').append('<div style=\"margin-bottom:8px; background:#f0f0f1; padding:10px; border-radius:4px;\"><strong>' + ins.name + '</strong> <span style=\"color:red; cursor:pointer; float:right;\" class=\"remove-ins\" data-index=\"' + index + '\">Xóa</span></div>');
					});
					$('#haki-instructors-data').val(JSON.stringify(insArray));
				}
				renderInstructors();
				$('.haki-add-instructor').on('click', function() {
					var name = prompt('Nhập tên giảng viên:');
					if(name) { insArray.push({name: name}); renderInstructors(); }
				});
				$(document).on('click', '.remove-ins', function() {
					insArray.splice($(this).data('index'), 1); renderInstructors();
				});
			});
		";
		wp_add_inline_script( 'hakicms-admin-script', $js );
	}
}