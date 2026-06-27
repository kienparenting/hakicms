/**
 * File: assets/js/admin-template.js
 * Description: Handles Admin UI logic for HakiCMS Course Templates.
 * Version: 4.0.0
 */

(function($) {
    'use strict';

    $(function() {
        /**
         * Initialize WordPress Color Picker
         * This targets any input field with the class .hakicms-color-picker
         */
        const initColorPicker = () => {
            if ($.fn.wpColorPicker) {
                $('.hakicms-color-picker').wpColorPicker();
            }
        };

        /**
         * Category Selection Helper
         * Provides a console warning if the user forgets to link a category.
         */
        const monitorCategoryLink = () => {
            const $catSelect = $('select[name="hakicms_meta[category_id]"]');
            
            if ($catSelect.length) {
                $catSelect.on('change', function() {
                    const selectedVal = $(this).val();
                    if (selectedVal === '0') {
                        // Highlight or warn the user if needed
                        $(this).css('border-color', '#d63638');
                    } else {
                        $(this).css('border-color', '');
                    }
                });
            }
        };

        // Run initializations
        initColorPicker();
        monitorCategoryLink();

        /**
         * Placeholder for future Logic: 
         * If you add tabs or conditional fields to the Post Type settings,
         * you can handle the show/hide logic here.
         */
    });

})(jQuery);