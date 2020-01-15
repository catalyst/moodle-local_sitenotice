/**
 * Notice modal.
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/modal', 'core/modal_registry'],
    function($, Notification, Modal, ModalRegistry) {

        var registered = false;

        var SELECTORS = {
            CLOSE_BUTTON: '[data-action="close"]',
            ACCEPT_BUTTON: '[data-action="accept"]',
            ACK_CHECKBOX: 'sitenotice-modal-ackcheckbox',
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
         * @param noticeid
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
         * @param reqack
         */
        ModalNotice.prototype.setRequiredAcknowledgement = function(reqack) {
            if (reqack == 1) {
                var body = this.getBody();
                var ackcheckbox = $("<input>", {type: "checkbox", id: SELECTORS.ACK_CHECKBOX});
                var labeltext = "I have read and understand the notice (Closing this notice will log you off this site).";
                var ackcheckboxlabel = $("<label>", {for: SELECTORS.ACK_CHECKBOX, text: labeltext});
                body.append(ackcheckbox);
                body.append(ackcheckboxlabel);
                this.getFooter().find(SELECTORS.ACCEPT_BUTTON).attr('disabled', true);
            } else {
                this.getFooter().find(SELECTORS.ACCEPT_BUTTON).css('display', 'none');
            }
        };

        return ModalNotice;
    }
);
