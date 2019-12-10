/**
 *
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

        function first(p) {
            for (let i in p) {
                return p[i];
            }
        };

        function buildModal(notices) {
            var modal = document.createElement("DIV");
            var modalcontent = document.createElement("DIV");
            var modalcontentheadder = document.createElement("DIV");
            var modalcontentbody = document.createElement("DIV");
            var modalcontentfooter = document.createElement("DIV");
            var closebutton = document.createElement("BUTTON");
            var ackbutton = document.createElement("BUTTON");
            var paragraph = document.createElement("p");

            modal.setAttribute("id", "sitenotice-modal");

            modalcontent.setAttribute("id", "sitenotice-modal-content");
            modalcontentheadder.setAttribute("id", "sitenotice-modal-content-header");
            modalcontentbody.setAttribute("id", "sitenotice-modal-content-body");
            modalcontentfooter.setAttribute("id", "sitenotice-modal-content-footer");

            closebutton.innerHTML = "Close";
            ackbutton.innerHTML = "I acknowledge";
            closebutton.setAttribute("id", "sitenotice-modal-content-footer-closebutton");
            ackbutton.setAttribute("id", "sitenotice-modal-content-footer-ackbutton");

            modalcontentheadder.innerHTML = "<h2>" + notices.title + "</h2>";
            modalcontentbody.innerHTML = notices.content;

            paragraph.appendChild(closebutton);
            paragraph.appendChild(ackbutton);
            modalcontentfooter.appendChild(paragraph);

            modalcontent.appendChild(modalcontentheadder);
            modalcontent.appendChild(modalcontentbody);
            modalcontent.appendChild(modalcontentfooter);

            modal.appendChild(modalcontent);
            document.getElementsByTagName("BODY")[0].appendChild(modal);

            $('body').on('click', '#sitenotice-modal-content-footer-closebutton', function() {
                modal.style.display = "none";
                dismissNotice();
            });

            $('body').on('click', '#sitenotice-modal-content-footer-ackbutton', function() {
                modal.style.display = "none";
            });
        };

        function dismissNotice() {
            var promises = ajax.call([
                { methodname: 'core_get_string', args: { component: 'local_sitenotice', stringid: 'pluginname' } }
            ]);

            promises[0].done(function(response) {
                console.log('Test: ' + response);
            }).fail(function(ex) {

            });
        }

        notice.init = function(data) {
            notices = JSON.parse(data);
            buildModal(first(notices));
            $(document).ready(function() {
                var modal = document.getElementById("sitenotice-modal");
                modal.style.display = "block";
            });
        };

        return notice;
    }
);