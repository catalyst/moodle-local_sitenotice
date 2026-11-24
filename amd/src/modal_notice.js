/**
 * Notice modal.
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/modal', 'core/key_codes', 'core/str'],
    function($, Notification, Modal, KeyCodes, str) {

        var SELECTORS = {
            CLOSE_BUTTON: '[data-action="close"]',
            ACCEPT_BUTTON: '[data-action="accept"]',
            ACK_CHECKBOX: 'sitenotice-modal-ackcheckbox',
            CAN_RECEIVE_FOCUS: 'input:not([type="hidden"]), a[href], button:not([disabled])',
            TOOL_TIP_WRAPPER: '#tooltip-wrapper',
        };

        var ATTRIBUTE = {
            NOTICE_ID: 'data-noticeid',
            REQUIRED_ACKNOWLEDGE: 'data-noticereqack',
        };

        class ModalNotice extends Modal {

            static TYPE = 'local_sitenotice';

            static TEMPLATE = 'local_sitenotice/modal_notice';

            constructor(root) {
                super(root);

                if (!this.getFooter().find(SELECTORS.CLOSE_BUTTON).length) {
                    Notification.exception({message: 'No close button found'});
                }

                if (!this.getFooter().find(SELECTORS.ACCEPT_BUTTON).length) {
                    Notification.exception({message: 'No accept button found'});
                }
            }

            /**
             * Get ID of close button.
             * @returns {string}
             */
            getCloseButtonID = function() {
                return '#' + this.getFooter().find(SELECTORS.CLOSE_BUTTON).attr('id');
            };

            /**
             * Get ID of accept button.
             * @returns {string}
             */
            getAcceptButtonID = function() {
                return '#' + this.getFooter().find(SELECTORS.ACCEPT_BUTTON).attr('id');
            };

            /**
             * Get ID of accept button.
             * @returns {string}
             */
            getAckCheckboxID = function() {
                return '#' + SELECTORS.ACK_CHECKBOX;
            };

            /**
             * Set Notice ID to the current modal.
             * @param {Integer} noticeid
             */
            setNoticeId = function(noticeid) {
                this.getModal().attr(ATTRIBUTE.NOTICE_ID, noticeid);
            };

            /**
             * Get the current notice id.
             * @returns {*}
             */
            getNoticeId = function() {
                return this.getModal().attr(ATTRIBUTE.NOTICE_ID);
            };

            /**
             * Add Checkbox if the notice requires acknowledgement.
             * @param {Integer} reqack
             */
            setRequiredAcknowledgement = function(reqack) {
                var modal = this;
                if (reqack == 1) {
                    str.getString('modal:checkboxtext', 'local_sitenotice').then(function(langString) {
                        var body = modal.getBody();
                        var checkboxdiv = $("<div>", {});
                        var ackcheckbox = $("<input>", {type: "checkbox", id: SELECTORS.ACK_CHECKBOX});
                        var labelspan = $("<span>", {"class": "checkboxlabel"});
                        labelspan.append(langString);
                        checkboxdiv.append(ackcheckbox);
                        checkboxdiv.append(labelspan);
                        body.append(checkboxdiv);
                        var acceptbutton = modal.getFooter().find(SELECTORS.ACCEPT_BUTTON);
                        acceptbutton.show();
                        acceptbutton.attr('disabled', true);
                        // Tooltip for disabled box.
                        modal.turnonToolTip();
                    }).catch(Notification.exception);
                } else {
                    this.getFooter().find(SELECTORS.ACCEPT_BUTTON).css('display', 'none');
                }
            };

            /**
             * Turn off tool tip.
             *
             * A native title attribute is used rather than the Bootstrap tooltip jQuery plugin,
             * because Moodle 5.0+ ships Bootstrap 5 which no longer provides the Bootstrap 4 jQuery API.
             */
            turnoffToolTip = function() {
                this.getFooter().find(SELECTORS.TOOL_TIP_WRAPPER).removeAttr('title');
            };

            /**
             * Turn on tool tip.
             */
            turnonToolTip = function() {
                var wrapper = this.getFooter().find(SELECTORS.TOOL_TIP_WRAPPER);
                wrapper.attr('title', wrapper.attr('data-title'));
            };

            /**
             * Remove escape key event.
             */
            registerEventListeners = function() {
                $(document).on('keydown', function(e) {
                    if (!this.isVisible()) {
                        return;
                    }

                    if (e.keyCode == KeyCodes.tab) {
                        this.handleTabLock(e);
                    }

                }.bind(this));

                $(document).on('mousedown', function(e) {
                    if (!this.isVisible()) {
                        return;
                    }
                    e.preventDefault();

                }.bind(this));
            };

            /**
             * CAN_RECEIVE_FOCUS in modal.js does not check if the disabled or hidden button
             * @param {Event} e
             */
            handleTabLock = function(e) {
                var target = $(document.activeElement);

                var focusableElements = this.modal.find(SELECTORS.CAN_RECEIVE_FOCUS).filter(":visible");
                var firstFocusable = focusableElements.first();
                var lastFocusable = focusableElements.last();

                var focusable = false;
                var previous = 0;
                focusableElements.each(function(index) {
                    if (target.is(this)) {
                        focusable = true;
                        previous = index;
                    }
                });

                // Focus to first element.
                if (focusable == false) {
                    e.preventDefault();
                    firstFocusable.focus();
                    return;
                } else {
                    if (target.is(firstFocusable) && e.shiftKey) {
                        lastFocusable.focus();
                        e.preventDefault();
                    } else if (target.is(lastFocusable) && !e.shiftKey) {
                        firstFocusable.focus();
                        e.preventDefault();
                    } else {
                        if (!e.shiftKey) {
                            var next = focusableElements.get(previous + 1);
                        } else {
                            var next = focusableElements.get(previous - 1);
                        }
                        next.focus();
                        e.preventDefault();
                    }
                }
            };
        }

        return ModalNotice;
    }
);
