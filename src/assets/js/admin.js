/**
 * WP Admin JS.
 *
 * You may copy, distribute and modify the software as long as you track
 * changes/dates in source files. Any modifications to or software including
 * (via compiler) GPL-licensed code must also be made available under the GPL
 * along with build & install instructions.
 *
 * @package   WPS\Plugins\Fundraising
 * @author    Travis Smith <t@wpsmith.net>
 * @copyright 2018 Travis Smith; 2018 Akamai
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License v2
 * @link      https://github.com/akamai/wp-akamai
 * @since     0.2.0
 */

/* global wps, wpsL10n, wps_toggles, confirm */

/**
 * Holds wps values in an object to avoid polluting global namespace.
 *
 * @since 1.8.0
 *
 * @constructor
 */
(function ($, window) {
    window['wps'] = {

        settingsChanged: false,

        /**
         * Inserts a category checklist toggle button and binds the behaviour.
         *
         * @since 1.8.0
         *
         * @function
         */
        categoryChecklistToggleInit: function () {
            'use strict';

            // Insert toggle button into DOM wherever there is a category checklist.
            $('<p><span id="wps-category-checklist-toggle" class="button">' + wpsL10n.categoryChecklistToggle + '</span></p>')
                .insertBefore('ul.categorychecklist');

            // Bind the behaviour to click.
            $(document).on('click.wps.wps_category_checklist_toggle', '#wps-category-checklist-toggle', wps.categoryChecklistToggle);
        },

        /**
         * Provides the behaviour for the category checklist toggle button.
         *
         * On the first click, it checks all checkboxes, and on subsequent clicks it
         * toggles the checked status of the checkboxes.
         *
         * @since 1.8.0
         *
         * @function
         *
         * @param {jQuery.event} event
         */
        categoryChecklistToggle: function (event) {
            'use strict';

            // Cache the selectors.
            var $this = $(event.target),
                checkboxes = $this.parent().next().find(':checkbox');

            // If the button has already been clicked once, clear the checkboxes and remove the flag.
            if ($this.data('clicked')) {
                checkboxes.removeAttr('checked');
                $this.data('clicked', false);
            } else { // Mark the checkboxes and add a flag.
                checkboxes.attr('checked', 'checked');
                $this.data('clicked', true);
            }
        },

        /**
         * Grabs the array of toggle settings and loops through them to hook in
         * the behaviour.
         *
         * The wps_toggles array is filterable in load-scripts.php before being
         * passed over to JS via wp_localize_script().
         *
         * @since 1.8.0
         *
         * @function
         */
        toggleSettingsInit: function () {
            'use strict';

            $.each(wps_toggles, function (k, v) {

                // Prepare data.
                var data = {selector: v[0], showSelector: v[1], checkValue: v[2]};

                // Setup toggle binding.
                $('div.wps-metaboxes')
                    .on('change.wps.wps_toggle', v[0], data, wps.toggleSettings);

                // Trigger the check when page loads too.
                // Can't use triggerHandler here, as that doesn't bubble the event up to div.wps-metaboxes.
                // We namespace it, so that it doesn't conflict with any other change event attached that
                // we don't want triggered on document ready.
                $(v[0]).trigger('change.wps_toggle', data);
            });

        },

        /**
         * Provides the behaviour for the change event for certain settings.
         *
         * Three bits of event data is passed - the jQuery selector which has the
         * behaviour attached, the jQuery selector which to toggle, and the value to
         * check against.
         *
         * The checkValue can be a single string or an array (for checking against
         * multiple values in a dropdown) or a null value (when checking if a checkbox
         * has been marked).
         *
         * @since 1.8.0
         *
         * @function
         *
         * @param {jQuery.event} event
         */
        toggleSettings: function (event) {
            'use strict';

            // Cache selectors.
            var $selector = $(event.data.selector),
                $showSelector = $(event.data.showSelector),
                checkValue = event.data.checkValue;

            // Compare if a checkValue is an array, and one of them matches the value of the selected option
            // OR the checkValue is _unchecked, but the checkbox is not marked
            // OR the checkValue is _checked, but the checkbox is marked
            // OR it's a string, and that matches the value of the selected option.
            if (
                ($.isArray(checkValue) && $.inArray($selector.val(), checkValue) > -1) ||
                ('_unchecked' === checkValue && $selector.is(':not(:checked)')) ||
                ('_checked' === checkValue && $selector.is(':checked')) ||
                ('_unchecked' !== checkValue && '_checked' !== checkValue && $selector.val() === checkValue)
            ) {
                $($showSelector).slideDown('fast');
            } else {
                $($showSelector).slideUp('fast');
            }

        },

        /**
         * When a input or textarea field field is updated, update the character counter.
         *
         * For now, we can assume that the counter has the same ID as the field, with a _chars
         * suffix. In the future, when the counter is added to the DOM with JS, we can add
         * a data( 'counter', 'counter_id_here' ) property to the field element at the same time.
         *
         * @since 1.8.0
         *
         * @function
         *
         * @param {jQuery.event} event
         */
        updateCharacterCount: function (event) {
            'use strict';
            $('#' + event.target.id + '_chars').html($(event.target).val().length.toString());
        },

        /**
         * Provides the behaviour for the layout selector.
         *
         * When a layout is selected, the all layout labels get the selected class
         * removed, and then it is added to the label that was selected.
         *
         * @since 1.8.0
         *
         * @function
         *
         * @param {jQuery.event} event
         */
        layoutHighlighter: function (event) {
            'use strict';

            // Cache class name.
            var selectedClass = 'selected';

            // Remove class from all labels.
            $('input[name="' + $(event.target).attr('name') + '"]').parent('label').removeClass(selectedClass);

            // Add class to selected layout.
            $(event.currentTarget).addClass(selectedClass);

        },

        /**
         * Helper function for confirming a user action.
         *
         * @since 1.8.0
         *
         * @function
         *
         * @param {String} text The text to display.
         * @returns {Boolean}
         */
        confirm: function (text) {
            'use strict';

            return confirm(text);

        },

        /**
         * Have all form fields in wps meta boxes set a dirty flag when changed.
         *
         * @since 2.0.0
         *
         * @function
         */
        attachUnsavedChangesListener: function () {
            'use strict';

            $('div.wps-metaboxes :input').change(function () {
                wps.registerChange();
            });
            window.onbeforeunload = function () {
                if (wps.settingsChanged) {
                    return wpsL10n.saveAlert;
                }
            };
            $('div.wps-metaboxes input[type="submit"]').click(function () {
                window.onbeforeunload = null;
            });
        },

        /**
         * Set a flag, to indicate form fields have changed.
         *
         * @since 2.0.0
         *
         * @function
         */
        registerChange: function () {
            'use strict';

            wps.settingsChanged = true;
        },

        /**
         * Ask user to confirm that a new version of wps should now be installed.
         *
         * @since 2.0.0
         *
         * @function
         *
         * @return {Boolean} True if upgrade should occur, false if not.
         */
        confirmUpgrade: function () {
            'use strict';

            return confirm(wpsL10n.confirmUpgrade);
        },

        /**
         * Ask user to confirm that settings should now be reset.
         *
         * @since 2.0.0
         *
         * @function
         *
         * @return {Boolean} True if reset should occur, false if not.
         */
        confirmReset: function () {
            'use strict';

            return confirm(wpsL10n.confirmReset);
        },

        /**
         * Initialises all aspects of the scripts.
         *
         * Generally ordered with stuff that inserts new elements into the DOM first,
         * then stuff that triggers an event on existing DOM elements when ready,
         * followed by stuff that triggers an event only on user interaction. This
         * keeps any screen jumping from occurring later on.
         *
         * @since 1.8.0
         *
         * @function
         */
        ready: function () {
            'use strict';

            // Initialise category checklist toggle button.
            wps.categoryChecklistToggleInit();

            // Initialise settings that can toggle the display of other settings.
            wps.toggleSettingsInit();

            // Initialise form field changing flag.
            wps.attachUnsavedChangesListener();

            // Bind character counters.
            $('#wps_title, #wps_description').on('keyup.wps.wps_character_count', wps.updateCharacterCount);

            // Bind layout highlighter behaviour.
            $('.wps-layout-selector').on('change.wps.wps_layout_selector', 'label', wps.layoutHighlighter);

            // Bind upgrade confirmation.
            $('.wps-js-confirm-upgrade').on('click.wps.wps_confirm_upgrade', wps.confirmUpgrade);

            // Bind reset confirmation.
            $('.wps-js-confirm-reset').on('click.wps.wps_confirm_reset', wps.confirmReset);

        }

    };

    $(wps.ready);
})(jQuery, window);

/* jshint ignore:start */
/**
 * Helper function for confirming a user action.
 *
 * This function is deprecated in favour of wps.confirm( text ) which provides
 * the same functionality.
 *
 * @since 1.0.0
 * @deprecated 1.8.0
 */
function wps_confirm(text) {
    'use strict';
    return wps.confirm(text);
}

/* jshint ignore:end */