/**
 * User interaction with notice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery', 'core/modal_factory', 'core/str'],
    function ($, ModalFactory, str) {
        var preview = {};

        preview.init = function() {
            $('a.notice-preview').on('click', function(e) {
                var clickedLink = $(e.currentTarget);
                var content = clickedLink.attr('data-noticecontent');
                ModalFactory.create({
                    type: ModalFactory.types.CLOSE,
                    title: str.get_string('notice:content', 'local_sitenotice'),
                    body: content,
                    large: true
                })
                .then(function (modal) {
                    modal.show();
                });
            });
        };

        return preview;
    }
);
