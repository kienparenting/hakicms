<?php
/**
 * Plugin Name:       HakiCMS
 * Plugin URI:        https://hakicms.com/
 * Description:       A professional Learning Management System (LMS) for course creators. Manage courses, track student progress, and deliver content with ease.
 * Version:           4.0.0
 * Author:            Hakicms Team
 * Author URI:        https://hakicms.com
 * License:           GPL v2 or later
 * Text Domain:       hakicms
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define system constants
define( 'HAKICMS_VERSION', '4.0.0' );
define( 'HAKICMS_PATH', plugin_dir_path( __FILE__ ) );
define( 'HAKICMS_URL', plugin_dir_url( __FILE__ ) );
define( 'HAKICMS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main HakiCMS Class
 */
final class HakiCMS_Free_Core {
	
	private static $instance;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
	}

	public function init() {
		$this->includes();
		$this->init_hooks();
	}

	private function includes() {
		require_once HAKICMS_PATH . 'includes/class-hakicms-post-types.php';
		require_once HAKICMS_PATH . 'includes/class-hakicms-gatekeeper.php';
		require_once HAKICMS_PATH . 'includes/class-hakicms-progress-tracker.php';
		require_once HAKICMS_PATH . 'includes/class-hakicms-shortcodes.php';

		if ( is_admin() ) {
			require_once HAKICMS_PATH . 'admin/class-hakicms-admin-settings.php';
			require_once HAKICMS_PATH . 'admin/class-hakicms-user-manager.php';
            // Đã gỡ bỏ file class-hakicms-migration.php
		}
	}

	private function init_hooks() {
		new HAKICMS_Post_Types();
		new HAKICMS_Gatekeeper();
		new HAKICMS_Progress_Tracker();
		new HAKICMS_Shortcodes();

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_global_elementor_category' ], 1 );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_global_elementor_icons_css' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_hakicms_elementor_widgets' ] );

		if ( is_admin() ) {
			new HAKICMS_Admin_Settings();
			new HAKICMS_User_Manager();
            // Đã gỡ bỏ khởi tạo class HAKICMS_Migration

			add_filter( 'plugin_action_links_' . HAKICMS_BASENAME, [ $this, 'add_action_links' ] );
			add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_meta' ], 10, 2 );
		}
	}

	public function register_global_elementor_category( $elements_manager ) {
		$category_id = 'hakicms-category';
		$elements_manager->add_category( $category_id, [ 'title' => 'HAKICMS', 'icon' => 'hakicms-category-icon' ] );

		$reorder_cats = function() use ( $category_id ) {
			if ( isset( $this->categories[ $category_id ] ) ) {
				$haki_cat = [ $category_id => $this->categories[ $category_id ] ];
				unset( $this->categories[ $category_id ] );
				$this->categories = $haki_cat + $this->categories;
			}
		};
		$reorder_cats->call( $elements_manager );
	}

	public function enqueue_global_elementor_icons_css() {
		$coffee_svg = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA1MTIgNTEyIj48cGF0aCBmaWxsPSIjMjdBRTYwIiBkPSJNMzY4IDY0SDE0NGE0OCA0OCAwIDAgMC00OCA0OHYyMjRhMTYwIDE2MCAwIDAgMCAxNjAgMTYwaDY0YTE2MCAxNjAgMCAwIDAgMTYwLTE2MFYxMTJhNDggNDggMCAwIDAtNDgtNDh6bTQ4IDI3MmExMTIgMTEyIDAgMCAxLTExMiAxMTJIMjU2YTExMiAxMTIgMCAwIDEtMTEyLTExMlYxMTJoMjI0em0xNi0xNjBoLTY0VjE2aDY0YTgwIDgwIDAgMCAxIDgwIDgwIDgwIDgwIDAgMCAxLTgwIDgwek02NCAzMmgyMjR2MzJINjR6bTM4NCAzMkg2NHYzMmgzODR6Ii8+PC9zdmc+';
		$css = ".hakicms-category-icon { background-image: url('" . esc_url( $coffee_svg ) . "') !important; background-repeat: no-repeat; background-position: center; background-size: 18px !important; } .hakicms-category-icon:before { content: \"\" !important; }";
		
		wp_register_style( 'hakicms-elementor-icon', false, [], HAKICMS_VERSION );
		wp_enqueue_style( 'hakicms-elementor-icon' );
		wp_add_inline_style( 'hakicms-elementor-icon', $css );
	}

	public function register_hakicms_elementor_widgets( $widgets_manager ) {
		if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

		$widget_file = HAKICMS_PATH . 'includes/class-hakicms-elementor-widgets.php';
		if ( file_exists( $widget_file ) ) {
			require_once $widget_file;
			$widgets_manager->register( new \HAKICMS_Course_Elementor_Widget() );
			$widgets_manager->register( new \HAKICMS_Dashboard_Elementor_Widget() );
			$widgets_manager->register( new \HAKICMS_Course_List_Elementor_Widget() );
		}
	}

	public function add_action_links( $links ) {
		$custom_links = [ '<a href="' . esc_url( admin_url( 'admin.php?page=hakicms-settings' ) ) . '">' . esc_html__( 'Settings', 'hakicms' ) . '</a>' ];
		$custom_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=hakicms-pro' ) ) . '" style="color: #b13327; font-weight: bold;">' . esc_html__( 'Get Add-ons', 'hakicms' ) . '</a>';
		return array_merge( $custom_links, $links );
	}

	public function add_plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( HAKICMS_BASENAME === $plugin_file ) {
			$new_meta = [
				'docs'   => '<a href="https://hakicms.com/docs/" target="_blank">' . esc_html__( 'Docs & FAQs', 'hakicms' ) . '</a>',
				'videos' => '<a href="https://hakicms.com/videos/" target="_blank">' . esc_html__( 'Video Tutorials', 'hakicms' ) . '</a>',
			];
			$plugin_meta = array_merge( $plugin_meta, $new_meta );
		}
		return $plugin_meta;
	}
}

function hakicms() {
	return HakiCMS_Free_Core::instance();
}

hakicms();

register_activation_hook( __FILE__, 'hakicms_activation_logic' );
function hakicms_activation_logic() {
	flush_rewrite_rules();
}