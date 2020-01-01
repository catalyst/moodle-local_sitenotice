<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manage Notices
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_sitenotice_managenotice');

use local_sitenotice\helper;

$thispage = '/local/sitenotice/managenotice.php';
$editnotice = '/local/sitenotice/editnotice.php';

$PAGE->set_url(new moodle_url($thispage));
$PAGE->requires->js_call_amd('local_sitenotice/preview', 'init');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('setting:managenotice', 'local_sitenotice'));

$newnoticeparams = ['noticeid' => 0, 'sesskey' => sesskey()];
$newnoticeurl = new moodle_url($editnotice, $newnoticeparams);
echo $OUTPUT->single_button($newnoticeurl, get_string('notice:create', 'local_sitenotice'));

// Notice table.
$table = new html_table();
$table->attributes['class'] = 'generaltable';
$table->head = array(
    get_string('notice:title', 'local_sitenotice'),
    get_string('notice:resetinterval', 'local_sitenotice'),
    get_string('notice:reqack', 'local_sitenotice'),
    get_string('notice:audience', 'local_sitenotice'),
    get_string('notice:content', 'local_sitenotice'),
    get_string('actions'),
);

$notices = helper::retrieve_all_notices('enabled DESC, timemodified DESC');
foreach ($notices as $notice) {
    $row = array();
    $row[] = $notice->title;
    $row[] = helper::format_interval_time($notice->resetinterval);
    $row[] = helper::format_boolean($notice->reqack);
    $row[] = helper::get_audience_name($notice->audience);

    $row[] = html_writer::link("#", get_string('view'),
        ['class' => 'notice-preview', 'data-noticecontent' => $notice->content]);

    $links = null;
    // Edit.
    if (get_config('local_sitenotice', 'allow_update')) {
        $editparams = ['noticeid' => $notice->id, 'action' => 'edit', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
        $editlink = html_writer::link($editurl, $icon);
        $links .= ' ' . $editlink;
    }

    // Enable/Disable.
    if ($notice->enabled) {
        $editparams = ['noticeid' => $notice->id, 'action' => 'disable', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/hide', get_string('notice:disable', 'local_sitenotice'));
        $editlink = html_writer::link($editurl, $icon);
    } else {
        $editparams = ['noticeid' => $notice->id, 'action' => 'enable', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/show', get_string('notice:enable', 'local_sitenotice'));
        $editlink = html_writer::link($editurl, $icon);
    }
    $links .= ' ' . $editlink;

    // Reset.
    $editparams = ['noticeid' => $notice->id, 'action' => 'reset', 'sesskey' => sesskey()];
    $editurl = new moodle_url($editnotice, $editparams);
    $icon = $OUTPUT->pix_icon('t/reset', get_string('notice:reset', 'local_sitenotice'));
    $editlink = html_writer::link($editurl, $icon);
    $links .= ' ' . $editlink;

    // Delete.
    if (get_config('local_sitenotice', 'allow_delete')) {
        $editparams = ['noticeid' => $notice->id, 'action' => 'unconfirmeddelete', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/delete', get_string('notice:delete', 'local_sitenotice'));
        $editlink = html_writer::link($editurl, $icon);
        $links .= ' ' . $editlink;
    }

    // Report.
    $editparams = ['noticeid' => $notice->id, 'action' => 'report', 'sesskey' => sesskey()];
    $editurl = new moodle_url($editnotice, $editparams);
    $icon = $OUTPUT->pix_icon('i/report', get_string('report'));
    $editlink = html_writer::link($editurl, $icon);
    $links .= ' ' . $editlink;

    $row[] = $links;
    $table->data[] = $row;

}
echo html_writer::table($table);
echo $OUTPUT->footer();
