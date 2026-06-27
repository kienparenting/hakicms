<?php
/**
 * File: includes/class-hakicms-post-types.php
 * Description: Register Custom Post Types, Taxonomies and manage Meta Boxes for HakiCMS.
 * Version: 5.4.4 - Added Enrolled Button Label support
 */

if (!defined('ABSPATH')) exit;

class HAKICMS_Post_Types {

    public function __construct() {
        // Register Post Type and Taxonomy
        add_action('init', [$this, 'register_course_template_cpt']);
        add_action('init', [$this, 'register_course_section_taxonomy']);

        // Register Meta Boxes
        add_action('add_meta_boxes', [$this, 'add_all_meta_boxes']);
        
        // Save Meta Data
        add_action('save_post_course_template', [$this, 'save_all_course_meta']);
        
        // Admin UI Scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * 1. Register Course Template CPT
     */
    public function register_course_template_cpt() {
        $labels = [
            'name'               => _x('Course Templates', 'Post Type General Name', 'hakicms'),
            'singular_name'      => _x('Course Template', 'Post Type Singular Name', 'hakicms'),
            'menu_name'          => __('Course Templates', 'hakicms'),
            'add_new_item'       => __('Add New Template', 'hakicms'),
            'edit_item'          => __('Edit Template', 'hakicms'),
        ];
        $args = [
            'label'               => __('Course Template', 'hakicms'),
            'labels'              => $labels,
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => 'hakicms-dashboard', 
            'show_in_rest'        => true, // Enable Gutenberg support
            'rewrite'             => ['slug' => 'course'],
            'capability_type'     => 'post',
            'has_archive'         => true,
        ];
        register_post_type('course_template', $args);
    }

    /**
     * 2. Register Course Section Taxonomy
     */
    public function register_course_section_taxonomy() {
        $labels = [
            'name'              => _x('Course Sections', 'taxonomy general name', 'hakicms'),
            'singular_name'     => _x('Course Section', 'taxonomy singular name', 'hakicms'),
            'menu_name'         => __('Course Sections', 'hakicms'),
        ];
        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'section'],
        ];
        register_taxonomy('course_section', ['post'], $args);
    }

    /**
     * 3. Register All Meta Boxes
     */
    public function add_all_meta_boxes() {
        // Sidebar Meta Box for Quick Links
        add_meta_box(
            'hakicms_link_config',
            __('Course Links Configuration', 'hakicms'),
            [$this, 'render_link_config_metabox'],
            'course_template',
            'side',
            'high'
        );

        // Main Meta Box for Detailed Settings
        add_meta_box(
            'hakicms_template_settings',
            __('Course Settings', 'hakicms'),
            [$this, 'render_settings_metabox'],
            'course_template',
            'normal',
            'high'
        );
    }

    /**
     * SIDEBAR METABOX: Linked Product and Custom URLs
     */
    public function render_link_config_metabox($post) {
        wp_nonce_field('hakicms_save_links', 'hakicms_links_nonce');
        
        $linked_product = get_post_meta($post->ID, '_hakicms_linked_product_id', true);
        $learning_url   = get_post_meta($post->ID, '_hakicms_learning_url', true);
        $sales_url      = get_post_meta($post->ID, '_hakicms_sales_url', true);

        echo '<p><strong>'.esc_html__('Linked WooCommerce Product:', 'hakicms').'</strong></p>';
        echo '<select name="hakicms_product_id" style="width:100%;">';
        echo '<option value="">'.esc_html__('— Select Product —', 'hakicms').'</option>';
        if (class_exists('WooCommerce')) {
            $products = wc_get_products(['limit' => -1, 'status' => 'publish']);
            foreach ($products as $p) {
                echo '<option value="'.esc_attr($p->get_id()).'" '.selected($linked_product, $p->get_id(), false).'>'.esc_html($p->get_name()).'</option>';
            }
        }
        echo '</select>';

        echo '<p><strong>'.esc_html__('Custom Learning URL (Enter):', 'hakicms').'</strong></p>';
        echo '<input type="url" name="hakicms_learning_url" value="'.esc_attr(esc_url($learning_url)).'" class="widefat" placeholder="https://...">';

        echo '<p><strong>'.esc_html__('Custom Sales URL (Buy):', 'hakicms').'</strong></p>';
        echo '<input type="url" name="hakicms_sales_url" value="'.esc_attr(esc_url($sales_url)).'" class="widefat" placeholder="https://...">';
        
        echo '<p class="description" style="margin-top:10px;">'.esc_html__('If URLs are left blank, the course template permalink will be used.', 'hakicms').'</p>';
    }

    /**
     * MAIN METABOX: Course Logic and Display
     */
    public function render_settings_metabox($post) {
        wp_nonce_field('hakicms_save_meta_action', 'hakicms_meta_nonce');
        
        $global_settings = get_option('hakicms_settings');
        
        // DECODE JSON DATA FROM GLOBAL SETTINGS
        $instructors_list = json_decode($global_settings['instructors_list'] ?? '[]', true);
        $tags_list        = json_decode($global_settings['tags_list'] ?? '[]', true);

        $meta = get_post_meta($post->ID, '_hakicms_template_settings', true);
        if (!is_array($meta)) $meta = [];

        $selected_instructors = isset($meta['instructor_ids']) ? (array)$meta['instructor_ids'] : [];
        $selected_tag         = isset($meta['tag_id']) ? $meta['tag_id'] : '';
        $tag_align            = isset($meta['tag_align']) ? $meta['tag_align'] : 'left';

        $categories = get_categories(['hide_empty' => false]);
        $selected_cat = isset($meta['category_id']) ? absint($meta['category_id']) : 0;
        $sales_elementor_id = isset($meta['sales_elementor_id']) ? absint($meta['sales_elementor_id']) : '';
        $subtitle = isset($meta['subtitle']) ? $meta['subtitle'] : '';
        $button_label = isset($meta['button_label']) ? $meta['button_label'] : 'GET STARTED';

        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label><?php esc_html_e('Course Tag & Alignment', 'hakicms'); ?></label></th>
                <td>
                    <select name="hakicms_meta[tag_id]" style="min-width:200px;">
                        <option value=""><?php esc_html_e('— No Tag —', 'hakicms'); ?></option>
                        <?php if(!empty($tags_list)): ?>
                            <?php foreach($tags_list as $tag): ?>
                                <option value="<?php echo esc_attr($tag); ?>" <?php selected($selected_tag, $tag); ?>><?php echo esc_html($tag); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <select name="hakicms_meta[tag_align]">
                        <option value="left" <?php selected($tag_align, 'left'); ?>><?php esc_html_e('Left', 'hakicms'); ?></option>
                        <option value="center" <?php selected($tag_align, 'center'); ?>><?php esc_html_e('Center', 'hakicms'); ?></option>
                        <option value="right" <?php selected($tag_align, 'right'); ?>><?php esc_html_e('Right', 'hakicms'); ?></option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row"><label><?php esc_html_e('Assigned Instructors', 'hakicms'); ?></label></th>
                <td>
                    <div style="max-height:150px; overflow-y:auto; border:1px solid #ccd0d4; padding:10px; background:#fff; border-radius:4px;">
                        <?php if(empty($instructors_list)): ?>
                            <p class="description">
                                <?php
                                $message = sprintf(
                                    /* translators: %s: Settings page URL */
                                    __('No instructors found. Add them in <a href="%s">General Settings</a>.', 'hakicms'),
                                    esc_url(admin_url('admin.php?page=hakicms-settings'))
                                );
                                echo wp_kses_post($message);
                                ?>
                            </p>
                        <?php else: ?>
                            <?php foreach($instructors_list as $ins): ?>
                                <label style="display:block; margin-bottom:5px;">
                                    <input type="checkbox" name="hakicms_meta[instructor_ids][]" value="<?php echo esc_attr($ins['name']); ?>" <?php checked(in_array($ins['name'], $selected_instructors)); ?>> 
                                    <strong><?php echo esc_html($ins['name']); ?></strong>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>

            <tr>
                <th scope="row"><label><?php esc_html_e('Course Subtitle', 'hakicms'); ?></label></th>
                <td><input type="text" name="hakicms_meta[subtitle]" value="<?php echo esc_attr($subtitle); ?>" class="large-text" placeholder="e.g. Master this skill in 7 days"></td>
            </tr>
            
            <tr>
                <th scope="row"><label><?php esc_html_e('Custom Button Label', 'hakicms'); ?></label></th>
                <td><input type="text" name="hakicms_meta[button_label]" value="<?php echo esc_attr($button_label); ?>" class="large-text" placeholder="e.g. ENROLL NOW, BUY THIS COURSE..."></td>
            </tr>

            <tr>
                <th scope="row"><label><?php esc_html_e('Enrolled Button Label', 'hakicms'); ?></label></th>
                <td><input type="text" name="hakicms_meta[enrolled_label]" value="<?php echo isset($meta['enrolled_label']) ? esc_attr($meta['enrolled_label']) : 'ENROLLED'; ?>" class="large-text"></td>
            </tr>

            <tr>
                <th scope="row"><label><?php esc_html_e('Price Label', 'hakicms'); ?></label></th>
                <td><input type="text" name="hakicms_meta[price_label]" value="<?php echo isset($meta['price_label']) ? esc_attr($meta['price_label']) : 'Price:'; ?>" class="large-text"></td>
            </tr>

            <tr>
                <th scope="row"><label><?php esc_html_e('Linked Lesson Category', 'hakicms'); ?></label></th>
                <td>
                    <select name="hakicms_meta[category_id]" class="regular-text">
                        <option value="0"><?php esc_html_e('— Select Category —', 'hakicms'); ?></option>
                        <?php foreach ($categories as $cat) : ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($selected_cat, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            
            <tr style="background:#f9f9f9;">
                <th scope="row"><label><?php esc_html_e('Sales Page Template (Elementor ID)', 'hakicms'); ?></label></th>
                <td><input type="number" name="hakicms_meta[sales_elementor_id]" value="<?php echo esc_attr($sales_elementor_id); ?>" class="small-text"></td>
            </tr>
        </table>
        <?php
    }

    /**
     * 4. Save All Course Meta
     */
    public function save_all_course_meta($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        // Save Sidebar Links
        $links_nonce = isset($_POST['hakicms_links_nonce']) ? sanitize_text_field(wp_unslash($_POST['hakicms_links_nonce'])) : '';
        if ($links_nonce && wp_verify_nonce($links_nonce, 'hakicms_save_links')) {
            if (isset($_POST['hakicms_product_id'])) {
                update_post_meta($post_id, '_hakicms_linked_product_id', absint(wp_unslash($_POST['hakicms_product_id'])));
            }
            if (isset($_POST['hakicms_learning_url'])) {
                update_post_meta($post_id, '_hakicms_learning_url', esc_url_raw(wp_unslash($_POST['hakicms_learning_url'])));
            }
            if (isset($_POST['hakicms_sales_url'])) {
                update_post_meta($post_id, '_hakicms_sales_url', esc_url_raw(wp_unslash($_POST['hakicms_sales_url'])));
            }
        }

        // Save Main Settings
        $meta_nonce = isset($_POST['hakicms_meta_nonce']) ? sanitize_text_field(wp_unslash($_POST['hakicms_meta_nonce'])) : '';
        if ($meta_nonce && wp_verify_nonce($meta_nonce, 'hakicms_save_meta_action')) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $raw_data = isset($_POST['hakicms_meta']) ? wp_unslash($_POST['hakicms_meta']) : [];
            
            if (is_array($raw_data) && !empty($raw_data)) {
                $sanitized_data = [
                    'subtitle'           => sanitize_text_field($raw_data['subtitle'] ?? ''),
                    'button_label'       => sanitize_text_field($raw_data['button_label'] ?? 'GET STARTED'),
                    'enrolled_label'     => sanitize_text_field($raw_data['enrolled_label'] ?? 'ENROLLED'), // Added line
                    'price_label'        => sanitize_text_field($raw_data['price_label'] ?? 'Price:'),
                    'category_id'        => absint($raw_data['category_id'] ?? 0),
                    'sales_elementor_id' => absint($raw_data['sales_elementor_id'] ?? 0),
                    'tag_id'             => sanitize_text_field($raw_data['tag_id'] ?? ''),
                    'tag_align'          => sanitize_text_field($raw_data['tag_align'] ?? 'left'),
                    'instructor_ids'     => isset($raw_data['instructor_ids']) && is_array($raw_data['instructor_ids']) ? array_map('sanitize_text_field', $raw_data['instructor_ids']) : []
                ];

                update_post_meta($post_id, '_hakicms_template_settings', $sanitized_data);

                if ($sanitized_data['category_id'] > 0) {
                    update_term_meta($sanitized_data['category_id'], '_hakicms_is_course', 'yes');
                }
            }
        }
    }

    /**
     * 5. Enqueue Admin Assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        if ('course_template' !== $post_type) return;
        if (in_array($hook, ['post.php', 'post-new.php'])) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('hakicms-admin-js', HAKICMS_URL . 'assets/js/admin-template.js', ['jquery', 'wp-color-picker'], HAKICMS_VERSION, true);
        }
    }
}