/**
 * File: assets/js/progress.js
 * Description: Xử lý tiến độ học tập, mở bài học qua AJAX và Sticky CTA.
 * Version: 4.6.1
 */

(function($) {
    'use strict';

    $(function() {
        const hakicmsData = window.hakicms_vars || {};
        const ajaxUrl = hakicmsData.ajax_url;
        const nonce = hakicmsData.nonce;
        
        // ---------------------------------------------------------
        // 1. LOGIC THEO DÕI TIẾN ĐỘ (CUỘN TRANG)
        // ---------------------------------------------------------
        const postId = hakicmsData.current_post_id || 0;
        let isRequestSent = false;

        const markLessonAsCompleted = () => {
            if (isRequestSent || !postId || postId == 0) return;
            isRequestSent = true;

            $.post(ajaxUrl, {
                action: 'hakicms_mark_lesson_completed',
                security: nonce,
                post_id: postId
            }, function(response) {
                if (response.success) {
                    console.log('HakiCMS: Progress updated.');
                    $(document.body).trigger('hakicms_lesson_completed', [postId]);
                } else {
                    isRequestSent = false; // Reset để thử lại nếu lỗi mạng
                }
            });
        };

        const handleScroll = () => {
            const windowHeight = $(window).height();
            const scrollTop = $(window).scrollTop();
            const docHeight = $(document).height();
            
            // Nếu trang ngắn hoặc cuộn tới 85%
            if (docHeight < windowHeight + 200 || (scrollTop + windowHeight) >= docHeight * 0.85) {
                markLessonAsCompleted();
            }
        };

        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        // Chỉ chạy scroll tracker ở trang bài viết đơn lẻ
        if (postId > 0) {
            $(window).on('scroll', debounce(handleScroll, 250));
            setTimeout(handleScroll, 1500); // Check ngay sau khi load
        }

        // ---------------------------------------------------------
        // 2. LOGIC MỞ BÀI HỌC QUA AJAX
        // ---------------------------------------------------------
        $(document).on('click', '.hakicms-ajax-lesson', function(e) {
            if (e.ctrlKey || e.metaKey) return; // Cho phép mở tab mới
            
            e.preventDefault();
            const pid = $(this).data('id');
            const $viewer = $('#hakicms-lesson-viewer');
            const $content = $('#hakicms-viewer-content');
            const $title = $('#hakicms-viewer-title');

            if (!pid || !$viewer.length) {
                window.location.href = $(this).attr('href');
                return;
            }

            $viewer.slideDown();
            $title.text('Loading...');
            $content.html('<div style="padding:50px; text-align:center;">⏳ Loading lesson content...</div>');

            $('html, body').animate({ scrollTop: $viewer.offset().top - 50 }, 400);

            $.post(ajaxUrl, {
                action: 'hakicms_load_lesson',
                security: nonce,
                pid: pid
            }, function(res) {
                if (res.success) {
                    $title.text(res.data.title);
                    $content.html(res.data.content);
                } else {
                    $content.html('<p style="color:red; text-align:center; padding:20px;">' + (res.data.message || 'Error') + '</p>');
                }
            });
        });

        $(document).on('click', '#hakicms-close-viewer', function() {
            $('#hakicms-lesson-viewer').slideUp();
        });

        // ---------------------------------------------------------
        // 3. LOGIC CUỘN MƯỢT TRANG MY ACCOUNT
        // ---------------------------------------------------------
        const accountNode = $('#hakicms-profile-node');
        
        if (accountNode.length) {
            $('.woocommerce-MyAccount-navigation-link a').each(function() {
                let href = $(this).attr('href');
                if (href && !href.includes('#hakicms-profile-node')) {
                    $(this).attr('href', href.split('#')[0] + '#hakicms-profile-node');
                }
            });

            if (window.location.hash === '#hakicms-profile-node') {
                $('html, body').animate({
                    scrollTop: accountNode.offset().top - 100
                }, 600);
            }
        }

        // ---------------------------------------------------------
        // 4. LOGIC STICKY CTA BAR (MỚI)
        // ---------------------------------------------------------
        const mainCta = document.getElementById('hakicms-main-cta');
        const stickyCta = document.getElementById('hakicms-sticky-cta');

        if (mainCta && stickyCta) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (!entry.isIntersecting) {
                        // Khi nút chính không còn nằm trong màn hình
                        stickyCta.classList.add('is-visible');
                    } else {
                        // Khi nút chính xuất hiện lại
                        stickyCta.classList.remove('is-visible');
                    }
                });
            }, { threshold: 0 });

            observer.observe(mainCta);
        }
    });

})(jQuery);