/**
 * User interaction with notice
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery', 'core/ajax'],
    function ($, ajax) {
        var notice = {};
        var notices = {};
        var viewednotices = [];

        function getNotice() {
            for (var i in notices) {
                if (!viewednotices.includes(i)) {
                    viewednotices.push(i);
                    return notices[i];
                }
            }
            return false;
        }

        function buildModal(userid) {
            var notice = getNotice();

            if (notice == false) {
                return false;
            }

            var $modal = $("<div>", {id: "sitenotice-modal", "tabindex": "0"});
            var $content = $("<div>", {id: "sitenotice-modal-content"});
            var $header = $("<div>", {id: "sitenotice-modal-content-header"});
            var $body = $("<div>", {id: "sitenotice-modal-content-body"});
            var $footer = $("<div>", {id: "sitenotice-modal-content-footer"});

            $modal.attr("data-userid", userid);

            $content.append($header);
            $content.append($body);
            $content.append($footer);

            $modal.append($content);
            $("body").append($modal);

            buildContent(notice);

            $modal.on('click', '#sitenotice-modal-content-footer-closebutton', function() {
                var noticeid = $("#sitenotice-modal").attr('data-noticeid');
                dismissNotice(noticeid);
                nextNotice();
            });

            $modal.on('click', '#sitenotice-modal-content-footer-ackbutton', function() {
                var noticeid = $("#sitenotice-modal").attr('data-noticeid');
                acknowledgeNotice(noticeid);
                nextNotice();
            });

            $modal.on('click', '#sitenotice-modal-content-body-ackcheckbox', function() {
                toogleAcceptButton();
            });

            $modal.on('click', 'a', function() {
                var linkid = $(this).attr("data-linkid");
                trackLink(linkid);
            });
        }

        function buildContent(notice) {
            var $modal = $("#sitenotice-modal");
            var $header = $("#sitenotice-modal-content-header");
            var $body = $("#sitenotice-modal-content-body");
            var $footer = $("#sitenotice-modal-content-footer");

            $modal.attr("data-noticeid", notice.id);
            $header.html("<h2>" + notice.title + "</h2>");
            $body.html(notice.content);
            $footer.empty();

            var $paragraph = $("<p>");
            // Close button.
            var $closebutton = $("<button>", {id: "sitenotice-modal-content-footer-closebutton"});
            $closebutton.html('CLOSE');
            $paragraph.append($closebutton);
            if (notice.reqack == 1) {
                // Checkbox.
                var $ackcheckbox = $("<input>", {type: "checkbox", id: "sitenotice-modal-content-body-ackcheckbox"});
                var labeltext = "I have read and understand the notice. Closing this notice will log you off this site.";
                var $ackcheckboxlabel = $("<label>", { for: "sitenotice-modal-content-body-ackcheckboxlabel", text: labeltext});
                $body.append($ackcheckbox);
                $body.append($ackcheckboxlabel);
                // Acknowledge button.
                var $ackbutton = $("<button>", {id: "sitenotice-modal-content-footer-ackbutton"});
                $ackbutton.html('ACCEPT');
                $ackbutton.attr('disabled', true);
                $paragraph.append($ackbutton);
            }
            $footer.append($paragraph);
        }

        function toogleAcceptButton() {
            $("#sitenotice-modal-content-footer-ackbutton").attr('disabled',
                !$("#sitenotice-modal-content-body-ackcheckbox").is(":checked"));
        }

        function nextNotice() {
            $("#sitenotice-modal").fadeOut("slow", function() {
                var notice = getNotice();
                if (notice != false) {
                    buildContent(notice);
                    $("#sitenotice-modal").fadeIn("slow");
                }
            });
        }

        function dismissNotice(noticeid) {
            var promises = ajax.call([
                { methodname: 'local_sitenotice_dismiss', args: { noticeid: noticeid} }
            ]);

            promises[0].done(function(response) {
                if(response.redirecturl) {
                    window.open(response.redirecturl,"_parent", "");
                }
            }).fail(function(ex) {
                // TODO: Log fail event.
                this.console.log(ex);
            });
        }

        function acknowledgeNotice(noticeid) {
            var promises = ajax.call([
                { methodname: 'local_sitenotice_acknowledge', args: { noticeid: noticeid} }
            ]);

            promises[0].done(function(response) {
                if(response.redirecturl) {
                    window.open(response.redirecturl,"_parent", "");
                }
            }).fail(function(ex) {
                // TODO: Log fail event.
                this.console.log(ex);
            });
        }

        function trackLink(linkid) {
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
        }

        notice.init = function(jsnotices, userid) {
            notices = JSON.parse(jsnotices);
            buildModal(userid);
            $(document).ready(function() {
                $("#sitenotice-modal").fadeIn("slow");
                $("#sitenotice-modal").focus();
            });
        };

        return notice;
    }
);