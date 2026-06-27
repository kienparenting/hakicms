=== HakiCMS ===
Contributors: kienparenting, trungkienvu
Tags: lms, course manager, education, learning management system, online courses
Requires at least: 5.8
Tested up to: 7.0
Stable tag: 4.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A professional Learning Management System (LMS) for course creators. Manage courses, track student progress, and deliver content with ease.

== External services ==

This plugin connects to our external API (HakiCMS Cloud) to fetch the latest list of available premium add-ons for the Marketplace page.
- What data is sent: No user data or site data is sent. It is a simple GET request.
- Service provided by HakiCMS: [Terms of Use](https://hakicms.com/terms/) | [Privacy Policy](https://hakicms.com/privacy/)

= Key Features =
* **Course Management:** Create professional course landing pages using Course Templates.
* **Student Dashboard:** A dedicated area for students to track their learning progress with beautiful progress bars.
* **Content Protection:** Protect your premium lessons from unauthorized access.
* **Manual Enrollment:** Easily enroll students into specific courses from the WordPress admin.
* **Smart Migration:** One-click tool to migrate data from legacy course managers.
* **Elementor Ready:** Seamlessly integrate your Elementor-designed landing pages into your courses.
* **Modular Design:** Extend functionality anytime with HakiCMS Add-ons.

= Free Version Limit =
The HakiCMS FREE version supports up to **10 active courses**. For unlimited courses and advanced features like WooCommerce automation, visit [HakiCMS.com](https://hakicms.com).

== Installation ==

1. Upload the `hakicms` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. (Optional) If you were using MyCAM, go to **HakiCMS > Settings > Data Migration** to convert your data.
4. Create your first category for lessons, then link it to a new **Course Template**.
5. Use the shortcode `[hakicms_dashboard]` on a page to create your student area.

== Frequently Asked Questions ==

= How do I add lessons to a course? =
Simply create standard WordPress Posts and assign them to the Category you linked in your Course Template.

= Can I sell courses with the FREE version? =
The FREE version supports manual enrollment. To automate sales with WooCommerce, you need the **HakiCMS WooCommerce Bridge** add-on.

= What happens when I reach the 10-course limit? =
You can still manage your existing 10 courses, but you won't be able to create new ones cho đến khi bạn nâng cấp.

== Shortcodes ==

* `[hakicms_course id="XX"]`: Display a specific course (XX is the Template ID).
* `[hakicms_dashboard]`: Display the student personal area.
* `[hakicms_course_list]`: Display a grid/carousel of all available courses.
* `[hakicms_lesson_list]`: Display a sidebar list of lessons in a course.

== Screenshots ==

1. The professional Admin Dashboard with course statistics.
2. Student Dashboard with visual progress tracking.
3. Course landing page with curriculum accordion.

== Changelog ==

= 4.0.0 =
* Initial official release of HakiCMS FREE for WordPress.org.
* Standardized security protocols (Escaping, Sanitization, Nonces).
* Improved migration engine for legacy data.
* Fixed Elementor widget compatibility.