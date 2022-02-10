/**
 * User interaction with notice
 * @author     Jwalit Shah <jwalitshah@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {

    /**
     * CSS selectors
     * @type {object}
     */
    var SELECTORS = {
        RESET_NUMBER_SELECT: 'id_resetinterval_number',
        RESET_UNIT_SELECT: 'id_resetinterval_timeunit',
        REQACK_SELECT: 'id_reqack',
        AUDIENCE_SELECT: 'id_audience',
        REQCOURSE_SELECT: 'id_reqcourse'
    };

    /**
     * Default values for the select when course completion is chosen
     * @type {object}
     */
    var DEFAULT_VALUES = {
        RESET_NUMBER_SELECT: 0,
        RESET_UNIT_SELECT: "60",
        REQACK_SELECT: "0",
        AUDIENCE_SELECT: 0,
    };

    /**
     * Initialize the sitenotice edit form.
     * If its a course completion site notice, disable other fields
     */
    var init = function() {

        var reqcourseSelect = document.getElementById(SELECTORS.REQCOURSE_SELECT);
        var resetNumberSelect = document.getElementById(SELECTORS.RESET_NUMBER_SELECT);
        var resetUnitSelect = document.getElementById(SELECTORS.RESET_UNIT_SELECT);
        var reqackSelect = document.getElementById(SELECTORS.REQACK_SELECT);
        var audienceSelect = document.getElementById(SELECTORS.AUDIENCE_SELECT);

        if (reqcourseSelect.value > 0) {
            resetNumberSelect.value = DEFAULT_VALUES.RESET_NUMBER_SELECT;
            resetNumberSelect.disabled = true;
            resetUnitSelect.value = DEFAULT_VALUES.RESET_UNIT_SELECT;
            resetUnitSelect.disabled = true;
            reqackSelect.value = DEFAULT_VALUES.REQACK_SELECT;
            reqackSelect.disabled = true;
            audienceSelect.value = DEFAULT_VALUES.AUDIENCE_SELECT;
            audienceSelect.disabled = true;
        }
        // Add the onchange event as well.
        reqcourseSelect.addEventListener('change', function(event) {

            if (event.target.value > 0) {
                // If course completion is selected, disable other methods.
                resetNumberSelect.value = DEFAULT_VALUES.RESET_NUMBER_SELECT;
                resetNumberSelect.disabled = true;
                resetUnitSelect.value = DEFAULT_VALUES.RESET_UNIT_SELECT;
                resetUnitSelect.disabled = true;
                reqackSelect.value = DEFAULT_VALUES.REQACK_SELECT;
                reqackSelect.disabled = true;
                audienceSelect.value = DEFAULT_VALUES.AUDIENCE_SELECT;
                audienceSelect.disabled = true;
            } else {
                // Else enable other methods.
                resetNumberSelect.disabled = false;
                resetUnitSelect.disabled = false;
                reqackSelect.disabled = false;
                audienceSelect.disabled = false;
            }
        });
    };

    return {
        init : init
    };
});