<?php
/**
 * File: admin/class-hakicms-user-manager.php
 * Description: Quản lý quyền truy cập khóa học thủ công trong trang Edit User.
 * Version: 4.0.1 - Fixed Security (Escaping, Sanitization, Unslashing)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class HAKICMS_User_Manager {

	public function __construct() {
		add_action( 'show_user_profile', [ $this, 'render_enrollment_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'render_enrollment_fields' ] );
		add_action( 'personal_options_update', [ $this, 'save_enrollment_fields' ] );
		add_action( 'edit_user_profile_update', [ $this, 'save_enrollment_fields' ] );
	}

	public function render_enrollment_fields( $user ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$current_courses = get_user_meta( $user->ID, '_hakicms_purchased_course_ids', true ) ?: [];
		$categories      = get_categories( [ 'hide_empty' => false ] );

		wp_nonce_field( 'hakicms_user_enrollment_nonce', 'hakicms_enroll_nonce' );
		?>
		<hr>
		<h2><?php esc_html_e( 'HakiCMS - Manual Enrollment', 'hakicms' ); ?></h2>
		<table class="form-table">
			
			<?php do_action( 'hakicms_admin_user_fields_top', $user ); ?>

			<tr>
				<th><?php esc_html_e( 'Enrolled Courses (Categories)', 'hakicms' ); ?></th>
				<td>
					<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 15px; background: #fff; border-radius: 4px; border-left: 4px solid #2271b1;">
						<?php if ( empty( $categories ) ) : ?>
							<p><?php esc_html_e( 'No categories found.', 'hakicms' ); ?></p>
						<?php else : ?>
							<?php foreach ( $categories as $cat ) : ?>
								<label style="display: block; margin-bottom: 8px;">
									<input type="checkbox" name="hakicms_courses[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $current_courses ) ); ?>>
									<strong><?php echo esc_html( $cat->name ); ?></strong> 
									<small>(ID: <?php echo absint( $cat->term_id ); ?>)</small>
								</label>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</td>
			</tr>

			<?php do_action( 'hakicms_admin_user_fields_bottom', $user ); ?>

		</table>
		<?php
	}

	public function save_enrollment_fields( $user_id ) {
		// 1. Kiểm tra quyền
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// 2. Kiểm tra Nonce (Sửa lỗi Missing Unslash & Sanitization)
		if ( ! isset( $_POST['hakicms_enroll_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['hakicms_enroll_nonce'] ) ), 'hakicms_user_enrollment_nonce' ) ) {
			return;
		}

		// 3. Lưu dữ liệu khóa học (Sửa lỗi Unslashing & Mapping ID)
		if ( isset( $_POST['hakicms_courses'] ) && is_array( $_POST['hakicms_courses'] ) ) {
			$enrolled_ids = array_map( 'absint', wp_unslash( $_POST['hakicms_courses'] ) );
			update_user_meta( $user_id, '_hakicms_purchased_course_ids', $enrolled_ids );
		} else {
			update_user_meta( $user_id, '_hakicms_purchased_course_ids', [] );
		}

		do_action( 'hakicms_admin_user_fields_save', $user_id );
	}
}