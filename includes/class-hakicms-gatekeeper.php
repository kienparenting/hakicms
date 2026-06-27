<?php
/**
 * File: includes/class-hakicms-gatekeeper.php
 * Description: Kiểm soát quyền truy cập nội dung. Tích hợp Hooks cho Addons.
 * Version: 4.1.0
 */

if (!defined('ABSPATH')) exit;

class HAKICMS_Gatekeeper {

    private static $user_access_cache = [];

    public function __construct() {
        // Chặn toàn bộ nội dung hoặc một phần qua shortcode
        add_filter('the_content', [$this, 'handle_protection'], 20);
    }

    /**
     * Lấy dữ liệu quyền truy cập của User (Có Caching để tối ưu)
     */
    private function get_user_access_info($user_id) {
        if (!$user_id) return null;

        if (isset(self::$user_access_cache[$user_id])) {
            return self::$user_access_cache[$user_id];
        }

        $data = [
            'package'   => get_user_meta($user_id, '_hakicms_package_type', true),
            'expiry'    => (int) get_user_meta($user_id, '_hakicms_package_expiry_date', true),
            'courses'   => get_user_meta($user_id, '_hakicms_purchased_course_ids', true) ?: [],
            'levels'    => get_user_meta($user_id, 'hakicms_user_access_levels', true) ?: [],
        ];

        $data['is_expired'] = ($data['expiry'] > 0 && time() > $data['expiry']);
        
        self::$user_access_cache[$user_id] = $data;
        return $data;
    }

    /**
     * CORE LOGIC: Kiểm tra User có quyền xem bài học này không?
     */
    public function user_has_access($post_id, $user_id = null) {
        if (is_null($user_id)) $user_id = get_current_user_id();

        // 1. Admin/Editor luôn được xem
        if (user_can($user_id, 'manage_options')) return true;

        // 2. Bài học đánh dấu MIỄN PHÍ
        if (get_post_meta($post_id, '_hakicms_is_free', true) === 'yes') return true;

        if (!$user_id) return false;

        $access = $this->get_user_access_info($user_id);
        if (!$access) return false;

        $has_access = false;

        // 3. Quyền theo Gói VIP/SVIP (Còn hạn)
        if (in_array($access['package'], ['svip_pack', 'vip_pack']) && !$access['is_expired']) {
            $has_access = true;
        }

        // 4. Quyền theo Tầng (Access Levels)
        if (!$has_access && !empty($access['levels'])) {
            if (in_array('all_access', $access['levels'])) {
                $has_access = true;
            } else {
                $required_level = get_post_meta($post_id, '_hakicms_required_access_level', true);
                if (!empty($required_level) && in_array($required_level, $access['levels'])) {
                    $has_access = true;
                }
            }
        }

        // 5. FIX LOGIC: Kiểm tra quyền theo Khóa học (Category)
        if (!$has_access && !empty($access['courses'])) {
            // Nếu post_id là một Course Template
            if (get_post_type($post_id) === 'course_template') {
                $meta = get_post_meta($post_id, '_hakicms_template_settings', true);
                $cat_id = isset($meta['category_id']) ? absint($meta['category_id']) : 0;
                if ($cat_id && in_array($cat_id, $access['courses'])) {
                    $has_access = true;
                }
            } else {
                // Nếu là Post (Lesson) bình thường
                $post_categories = wp_get_post_categories($post_id);
                if (array_intersect($post_categories, $access['courses'])) {
                    $has_access = true;
                }
            }
        }

        return apply_filters('hakicms_user_has_access', $has_access, $post_id, $user_id);
    }

    /**
     * Xử lý Filter nội dung bài viết
     */
    public function handle_protection($content) {
        $post_id = get_the_ID();
        
        // Chỉ xử lý post type là 'post' (hoặc bạn có thể bổ sung 'course_template' nếu muốn bảo vệ nội dung template)
        if (!$post_id || get_post_type($post_id) !== 'post') return $content;

        // Trường hợp 1: Có quyền truy cập
        if ($this->user_has_access($post_id)) {
            // Hiển thị toàn bộ và gỡ bỏ shortcode sell_content (nếu có) để lấy nội dung bên trong
            return preg_replace('/\[hakicms_sell_content.*?\](.*?)\[\/hakicms_sell_content\]/s', '$1', $content);
        }

        // Trường hợp 2: KHÔNG có quyền truy cập
        
        // A. Nếu bài viết có chứa shortcode chặn một phần (Teaser mode)
        if (has_shortcode($content, 'hakicms_sell_content')) {
            return preg_replace('/\[hakicms_sell_content.*?\](.*?)\[\/hakicms_sell_content\]/s', $this->render_locked_message(), $content);
        }

        // B. Chặn toàn bộ nội dung bài viết
        return $this->render_locked_message();
    }

    /**
     * Render Giao diện thông báo bị khóa
     */
    private function render_locked_message() {
        $settings = get_option('hakicms_settings', []);
        
        // Link đích khi bấm nút (Trang mua hàng hoặc Membership)
        $target_id = isset($settings['membership_page_id']) ? absint($settings['membership_page_id']) : 0;
        $btn_url = ($target_id) ? get_permalink($target_id) : home_url();

        // Thông báo tùy chỉnh
        $msg = !empty($settings['locked_msg']) 
               ? wp_kses_post($settings['locked_msg']) 
               : esc_html__('This content is reserved for enrolled students. Please purchase the course to continue.', 'hakicms');

        ob_start();
        ?>
        <div class="hakicms-locked-notice" style="background:#fef2f2; border:2px dashed #f87171; padding:30px; border-radius:12px; text-align:center; margin:20px 0;">
            <div style="font-size:40px; margin-bottom:15px;">🔒</div>
            <div style="color:#1f2937; font-size:16px; margin-bottom:20px;">
                <?php echo wp_kses_post(wpautop($msg)); ?>
            </div>
            <a href="<?php echo esc_url($btn_url); ?>" class="button button-primary" style="padding:10px 25px; font-weight:bold; border-radius:50px; text-decoration:none;">
                <?php esc_html_e('Enroll / Upgrade Access', 'hakicms'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}