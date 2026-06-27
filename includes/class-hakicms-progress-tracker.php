<?php
/**
 * File: includes/class-hakicms-progress-tracker.php
 * Description: Xử lý logic đánh dấu hoàn thành và tính toán tiến độ học tập.
 * Version: 4.1.0
 */

if (!defined('ABSPATH')) exit;

class HAKICMS_Progress_Tracker {

    public function __construct() {
        add_action('wp_ajax_hakicms_mark_lesson_completed', [$this, 'handle_ajax_completion']);
    }

    public function handle_ajax_completion() {
        // PHẢI KHỚP VỚI NONCE Ở FILE SHORTCODE
        check_ajax_referer('hakicms_front_nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('You must be logged in.', 'hakicms')]);
        }

        $user_id = get_current_user_id();
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(['message' => __('Invalid Lesson ID.', 'hakicms')]);
        }

        $completed_posts = get_user_meta($user_id, 'hakicms_completed_posts', true);
        if (!is_array($completed_posts)) $completed_posts = [];

        if (!array_key_exists($post_id, $completed_posts)) {
            $now = current_time('timestamp');
            $completed_posts[$post_id] = $now;

            update_user_meta($user_id, 'hakicms_completed_posts', $completed_posts);
            update_user_meta($user_id, '_hakicms_last_activity', $now);

            $this->update_progress_overview($user_id, $post_id);

            wp_send_json_success([
                'message' => __('Progress updated.', 'hakicms'),
                'time'    => $now
            ]);
        }

        wp_send_json_success(['message' => __('Already completed.', 'hakicms')]);
    }

    private function update_progress_overview($user_id, $post_id) {
        $categories = wp_get_post_categories($post_id);
        if (empty($categories)) return;

        $overview = get_user_meta($user_id, 'hakicms_progress_overview', true) ?: [];

        foreach ($categories as $cat_id) {
            $is_course = get_term_meta($cat_id, '_hakicms_is_course', true);
            if ($is_course !== 'yes') continue;

            // Đếm lại thực tế để chính xác tuyệt đối
            $all_lessons = get_posts(['category' => $cat_id, 'fields' => 'ids', 'numberposts' => -1]);
            $completed_all = get_user_meta($user_id, 'hakicms_completed_posts', true) ?: [];
            
            $done = 0;
            foreach ($all_lessons as $l_id) {
                if (array_key_exists($l_id, $completed_all)) $done++;
            }

            $overview[$cat_id] = [
                'completed_count' => $done,
                'last_updated'    => current_time('timestamp')
            ];
        }

        update_user_meta($user_id, 'hakicms_progress_overview', $overview);
    }
}