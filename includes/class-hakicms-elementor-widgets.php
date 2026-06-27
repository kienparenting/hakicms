<?php
/**
 * File: includes/class-hakicms-elementor-widgets.php
 * Description: Elementor Widgets for HakiCMS Shortcodes.
 * Version: 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

/**
 * Widget 1: [hakicms_course id="XX"]
 */
class HAKICMS_Course_Elementor_Widget extends Widget_Base {
	public function get_name() {
		return 'hakicms_course';
	}
	public function get_title() {
		return 'HakiCMS - Single Course';
	}
	public function get_icon() {
		return 'eicon-single-page';
	}
	public function get_categories() {
		return [ 'hakicms-category' ];
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => 'Settings' ] );

		// Tối ưu hóa truy vấn lấy danh sách template để làm dropdown
		$courses = get_posts( [
			'post_type'              => 'course_template',
			'numberposts'            => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		] );

		$options = [ 0 => '— Select Course Template —' ];
		if ( ! empty( $courses ) ) {
			foreach ( $courses as $course_id ) {
				$options[ $course_id ] = get_the_title( $course_id );
			}
		}

		$this->add_control(
			'course_id',
			[
				'label'   => 'Select Course',
				'type'    => Controls_Manager::SELECT,
				'options' => $options,
				'default' => 0,
			]
		);
		$this->end_controls_section();

		// --- TAB STYLE ---
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => 'Title Style',
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'title_align',
			[
				'label'     => 'Title Alignment',
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'    => [
						'title' => 'Left',
						'icon'  => 'eicon-text-align-left',
					],
					'center'  => [
						'title' => 'Center',
						'icon'  => 'eicon-text-align-center',
					],
					'right'   => [
						'title' => 'Right',
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => 'Justified',
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .hakicms-course-header h2' => 'text-align: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => 'Title Color',
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .hakicms-title-main'    => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .hakicms-section-title' => 'color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'label'    => 'Title Typography',
				'selector' => '{{WRAPPER}} .hakicms-title-main, {{WRAPPER}} .hakicms-section-title',
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();
		if ( ! $settings['course_id'] ) {
			echo 'Please select a course template.';
			return;
		}

		echo do_shortcode( '[hakicms_course id="' . absint( $settings['course_id'] ) . '"]' );
	}
}

/**
 * Widget 2: [hakicms_dashboard]
 */
class HAKICMS_Dashboard_Elementor_Widget extends Widget_Base {
	public function get_name() {
		return 'hakicms_dashboard';
	}
	public function get_title() {
		return 'HakiCMS - Student Dashboard';
	}
	public function get_icon() {
		return 'eicon-dashboard';
	}
	public function get_categories() {
		return [ 'hakicms-category' ];
	}

	protected function render() {
		echo do_shortcode( '[hakicms_dashboard]' );
	}
}

/**
 * Widget 3: [hakicms_course_list]
 */
class HAKICMS_Course_List_Elementor_Widget extends Widget_Base {
	public function get_name() {
		return 'hakicms_course_list';
	}
	public function get_title() {
		return 'HakiCMS - Course List Grid';
	}
	public function get_icon() {
		return 'eicon-post-list';
	}
	public function get_categories() {
		return [ 'hakicms-category' ];
	}

	protected function register_controls() {
		$this->start_controls_section( 'section_content', [ 'label' => 'Display Settings' ] );

		$this->add_control(
			'layout',
			[
				'label'   => 'Layout',
				'type'    => Controls_Manager::SELECT,
				'options' => [
					'grid'     => 'Grid',
					'carousel' => 'Carousel',
				],
				'default' => 'grid',
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => 'Columns',
				'type'    => Controls_Manager::NUMBER,
				'min'     => 1,
				'max'     => 4,
				'default' => 2,
			]
		);

		$this->add_control(
			'filter_mode',
			[
				'label'     => 'Filter Mode',
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'all'     => 'Show All',
					'include' => 'Include Specific',
                    // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- This is a UI label for Elementor, not a database query parameter.
					'exclude' => 'Exclude Specific',
				],
				'default'   => 'all',
				'separator' => 'before',
			]
		);

		// Lấy danh sách ID an toàn cho dropdown Selection
		$courses_list = get_posts( [
			'post_type'              => 'course_template',
			'numberposts'            => -1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
		] );

		$course_options = [];
		if ( ! empty( $courses_list ) ) {
			foreach ( $courses_list as $course_id ) {
				$course_options[ $course_id ] = get_the_title( $course_id );
			}
		}

		$this->add_control(
			'include_ids',
			[
				'label'       => 'Select Courses to Include',
				'type'        => Controls_Manager::SELECT2,
				'options'     => $course_options,
				'multiple'    => true,
				'label_block' => true,
				'condition'   => [ 'filter_mode' => 'include' ],
			]
		);

		$this->add_control(
			'exclude_ids',
			[
				'label'       => 'Select Courses to Exclude',
				'type'        => Controls_Manager::SELECT2,
				'options'     => $course_options,
				'multiple'    => true,
				'label_block' => true,
				'condition'   => [ 'filter_mode' => 'exclude' ],
			]
		);

		$this->add_control(
			'excerpt_length',
			[
				'label'   => 'Excerpt Length (Words)',
				'type'    => Controls_Manager::SLIDER,
				'range'   => [ 'px' => [ 'min' => 0, 'max' => 100, 'step' => 1 ] ],
				'default' => [ 'size' => 25 ],
			]
		);

		$this->add_control(
			'autoplay_speed',
			[
				'label'     => 'Autoplay Speed (ms)',
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3000,
				'condition' => [ 'layout' => 'carousel' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$settings = $this->get_settings_for_display();

		$include_str = ! empty( $settings['include_ids'] ) ? implode( ',', array_map( 'absint', $settings['include_ids'] ) ) : '';
		$omitted_str = ! empty( $settings['exclude_ids'] ) ? implode( ',', array_map( 'absint', $settings['exclude_ids'] ) ) : '';

		// Cảnh báo WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Shortcode parameter 'exclude' is used here, not a direct meta/DB query.
		$shortcode = sprintf(
			'[hakicms_course_list layout="%s" columns="%s" excerpt_length="%s" speed="%s" mode="%s" include="%s" exclude="%s"]',
			esc_attr( $settings['layout'] ),
			esc_attr( $settings['columns'] ),
			esc_attr( $settings['excerpt_length']['size'] ),
			esc_attr( $settings['autoplay_speed'] ),
			esc_attr( $settings['filter_mode'] ),
			esc_attr( $include_str ),
			esc_attr( $omitted_str )
		);

		echo do_shortcode( $shortcode );
	}
}