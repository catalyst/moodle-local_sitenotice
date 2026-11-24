/**
 * User interaction with notice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_cancel', 'core/str'],
    function($, ModalCancel, str) {
        var preview = {};

        preview.init = function() {
            $('a.notice-preview').on('click', function(e) {
                var clickedLink = $(e.currentTarget);
                var content = clickedLink.attr('data-noticecontent');
                return ModalCancel.create({
                    title: str.getString('notice:content', 'local_sitenotice'),
                    body: content,
                    large: true
                })
                .then(function(modal) {
                    return modal.show();
                });
            });
        };

        return preview;
    }
);
