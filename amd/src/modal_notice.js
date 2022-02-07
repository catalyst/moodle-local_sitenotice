/**
 * Notice modal.
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/modal', 'core/modal_registry', 'core/key_codes', 'core/str'],
    function($, Notification, Modal, ModalRegistry, KeyCodes, str) {

        var registered = false;

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

        var ModalNotice = function(root) {
            Modal.call(this, root);

            if (!this.getFooter().find(SELECTORS.CLOSE_BUTTON).length) {
                Notification.exception({message: 'No close button found'});
            }

            if (!this.getFooter().find(SELECTORS.ACCEPT_BUTTON).length) {
                Notification.exception({message: 'No accept button found'});
            }
        };

        ModalNotice.TYPE = 'local_sitenotice';
        ModalNotice.prototype = Object.create(Modal.prototype);
        ModalNotice.prototype.constructor = ModalNotice;

        if (!registered) {
            ModalRegistry.register(ModalNotice.TYPE, ModalNotice, 'local_sitenotice/modal_notice');
            registered = true;
        }

        /**
         * Get ID of close button.
         * @returns {string}
         */
        ModalNotice.prototype.getCloseButtonID = function() {
            return '#' + this.getFooter().find(SELECTORS.CLOSE_BUTTON).attr('id');
        };

        /**
         * Get ID of accept button.
         * @returns {string}
         */
        ModalNotice.prototype.getAcceptButtonID = function() {
            return '#' + this.getFooter().find(SELECTORS.ACCEPT_BUTTON).attr('id');
        };

        /**
         * Get ID of accept button.
         * @returns {string}
         */
        ModalNotice.prototype.getAckCheckboxID = function() {
            return '#' + SELECTORS.ACK_CHECKBOX;
        };

        /**
         * Set Notice ID to the current modal.
         * @param {Integer} noticeid
         */
        ModalNotice.prototype.setNoticeId = function(noticeid) {
            this.getModal().attr(ATTRIBUTE.NOTICE_ID, noticeid);
        };

        /**
         * Get the current notice id.
         * @returns {*}
         */
        ModalNotice.prototype.getNoticeId = function() {
            return this.getModal().attr(ATTRIBUTE.NOTICE_ID);
        };

        /**
         * Add Checkbox if the notice requires acknowledgement.
         * @param {Integer} reqack
         */
        ModalNotice.prototype.setRequiredAcknowledgement = function(reqack) {
            var modal  = this;
            if (reqack == 1) {
                str.get_string('modal:checkboxtext', 'local_sitenotice').then(function(langString) {
                    var body = modal.getBody();
                    var checkboxdiv = $("<div>", {});
                    var ackcheckbox = $("<input>", {type: "checkbox", id: SELECTORS.ACK_CHECKBOX});
                    var labelspan = $("<span>", {class: "checkboxlabel"});
                    labelspan.append(langString);
                    checkboxdiv.append(ackcheckbox);
                    checkboxdiv.append(labelspan);
                    body.append(checkboxdiv);
                    var acceptbutton = modal.getFooter().find(SELECTORS.ACCEPT_BUTTON);
                    acceptbutton.show();
                    acceptbutton.attr('disabled', true);
                    // Tooltip for disabled box.
                    modal.getFooter().find(SELECTORS.TOOL_TIP_WRAPPER).tooltip();
                }).catch(Notification.exception);
            } else {
                this.getFooter().find(SELECTORS.ACCEPT_BUTTON).css('display', 'none');
            }
        };

        /**
         * Turn off tool tip
         */
        ModalNotice.prototype.turnoffToolTip = function() {
            this.getFooter().find(SELECTORS.TOOL_TIP_WRAPPER).tooltip('disable');
        };

        /**
         * Turn on tool tip
         */
        ModalNotice.prototype.turnonToolTip = function() {
            this.getFooter().find(SELECTORS.TOOL_TIP_WRAPPER).tooltip('enable');
        };

        /**
         * Remove escape key event.
         */
        ModalNotice.prototype.registerEventListeners = function() {
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
        ModalNotice.prototype.handleTabLock = function(e) {
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
                        var next = focusableElements.get(previous+1);
                    } else {
                        var next = focusableElements.get(previous-1);
                    }
                    next.focus();
                    e.preventDefault();
                }
            }
        };

        return ModalNotice;
    }
);
