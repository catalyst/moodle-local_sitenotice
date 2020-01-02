/**
 * User interaction with notice
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery', 'core/ajax', 'core/modal_factory', 'local_sitenotice/modal_notice'],
    function ($, ajax, ModalFactory, ModalNotice) {

        var notices = {};
        var modal;
        var viewednotices = [];

        var SiteNotice = {};

        /**
         * Retrieved notice which has not been viewwed.
         * @returns {boolean|*}
         */
        var getNotice= function() {
            for (var i in notices) {
                // Check the notice has been viewed.
                if (!viewednotices.includes(i)) {
                    viewednotices.push(i);
                    return notices[i];
                }
            }
            return false;
        };

        /**
         * Show next notice in the modal.
         */
        var nextNotice = function () {
            var nextnotice = getNotice();
            if (nextnotice == false) {
                return;
            }
            if (typeof modal === 'undefined') {
                ModalFactory.create({
                    type: ModalNotice.TYPE,
                    title: nextnotice.title,
                    body: nextnotice.content,
                    large: true,
                })
                .then(function (newmodal) {
                    modal = newmodal;

                    modal.setNoticeId(nextnotice.id);
                    modal.setRequiredAcknoledgement(nextnotice.reqack);

                    //Event listener for close button.
                    modal.getModal().on('click', modal.getCloseButtonID(), function() {
                        dismissNotice();
                        modal.hide();
                    });
                    //Event listener for accept button.
                    modal.getModal().on('click', modal.getAcceptButtonID(), function() {
                        acknowledgeNotice();
                        modal.hide();
                    });
                    //Event listener for link tracking.
                    modal.getModal().on('click', 'a', function() {
                        var linkid = $(this).attr("data-linkid");
                        trackLink(linkid);
                    });
                    //Event listener for ack checkbox.
                    modal.getModal().on('click', modal.getAckCheckboxID(), function() {
                        $(modal.getAcceptButtonID()).attr('disabled', !$(modal.getAckCheckboxID()).is(":checked"));
                    });

                    modal.show();
                });
            } else {
                // Update with new details.
                modal.setTitle(nextnotice.title);
                modal.setBody(nextnotice.content);
                modal.setNoticeId(nextnotice.id);
                modal.setRequiredAcknoledgement(nextnotice.reqack);
                modal.show();
            }

        };

        /**
         * Dismiss Notice.
         */
        var dismissNotice = function () {
            var noticeid = modal.getNoticeId();
            var promises = ajax.call([
                { methodname: 'local_sitenotice_dismiss', args: { noticeid: noticeid} }
            ]);

            promises[0].done(function(response) {
                if(response.redirecturl) {
                    window.open(response.redirecturl,"_parent", "");
                } else {
                    nextNotice();
                }
            }).fail(function(ex) {
                // TODO: Log fail event.
                this.console.log(ex);
            });
        };

        /**
         * Acknowledge notice.
         */
        var acknowledgeNotice = function () {
            var noticeid = modal.getNoticeId();
            var promises = ajax.call([
                { methodname: 'local_sitenotice_acknowledge', args: { noticeid: noticeid} }
            ]);

            promises[0].done(function(response) {
                if(response.redirecturl) {
                    window.open(response.redirecturl,"_parent", "");
                } else {
                    nextNotice();
                }
            }).fail(function(ex) {
                // TODO: Log fail event.
                this.console.log(ex);
            });
        };

        /**
         * Link tracking.
         * @param linkid
         */
        var trackLink = function (linkid) {
            var promises = ajax.call([
                { methodname: 'local_sitenotice_tracklink', args: {linkid: linkid} }
            ]);

            promises[0].done(function(response) {
                if(response.redirecturl) {
                    window.open(response.redirecturl,"_parent", "");
                }
            }).fail(function(ex) {
                this.console.log(ex);
            });
        };

        /**
         * Initial Modal with user notices.
         * @param jsnotices
         */
        SiteNotice.init = function(jsnotices) {
            notices = JSON.parse(jsnotices);
            $(document).ready(function() {
                nextNotice();
            });
        };

        return SiteNotice;
    }
);