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
 * To create, view notice
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_sitenotice\form\notice_form;
use local_sitenotice\helper;
use local_sitenotice\persistent\sitenotice;

require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('local_sitenotice_managenotice');
helper::check_manage_capability();

require_sesskey();
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('notice:notice', 'local_sitenotice'));

$noticeid = optional_param('noticeid', 0, PARAM_INT);
$action = optional_param('action', 'create', PARAM_TEXT);

$managenoticepage = new moodle_url('/local/sitenotice/managenotice.php');
$thispage = new moodle_url('/local/sitenotice/editnotice.php', ['noticeid' => $noticeid]);
$PAGE->set_url($thispage);
$PAGE->requires->js_call_amd('local_sitenotice/notice_form', 'init', array());

$sitenotice = sitenotice::get_record(['id' => $noticeid]);
$customdata = [
    'persistent' => $sitenotice,
    'id' => $noticeid
];
$mform = new notice_form($thispage, $customdata);

// Proccess form data.
if ($formdata = $mform->get_data()) {
    if (!$sitenotice) {
        // Create new notice.
        helper::create_new_notice($formdata);
        redirect($managenoticepage);
    } else {
        // Update notice.
        helper::update_notice($sitenotice, $formdata);
        redirect($managenoticepage);
    }
} else if ($mform->is_cancelled()) {
    redirect($managenoticepage);
}

// Display form for new notice.
if ($noticeid == 0 && $action == 'create') {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('notice:create', 'local_sitenotice'));
    $mform->display();
    echo $OUTPUT->footer();
    die;
}

// Check notice existence.
if (!$sitenotice) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('notice:info', 'local_sitenotice'));
    echo $OUTPUT->notification(get_string('notification:noticedoesnotexist', 'local_sitenotice'), 'notifyinfo');
    echo $OUTPUT->footer();
    die;
}

switch ($action) {
    case 'dismissed_report':
        $reportpage = new moodle_url('/local/sitenotice/report/dismissed_report.php', ["noticeid" => $noticeid]);
        redirect($reportpage);
        break;
    case 'acknowledged_report':
        $reportpage = new moodle_url('/local/sitenotice/report/acknowledged_report.php', ["noticeid" => $noticeid]);
        redirect($reportpage);
        break;
    case 'reset':
        helper::reset_notice($noticeid);
        redirect($managenoticepage);
        break;
    case 'disable':
        helper::disable_notice($noticeid);
        redirect($managenoticepage);
        break;
    case 'enable':
        helper::enable_notice($noticeid);
        redirect($managenoticepage);
        break;
    case 'unconfirmeddelete':
        if (get_config('local_sitenotice', 'allow_delete')) {
            echo $OUTPUT->header();
            echo $OUTPUT->box_start();
            $thispage->params(array('sesskey' => sesskey(), 'action' => 'confirmeddelete', 'noticeid' => $noticeid));
            $confirmeddelete = new single_button($thispage, get_string('delete'), 'post');
            $cancel = new single_button($managenoticepage, get_string('cancel'), 'get');
            echo $OUTPUT->confirm(get_string('confirmation:deletenotice', 'local_sitenotice', $sitenotice->get('title')),
                $confirmeddelete, $cancel);
            echo $OUTPUT->box_end();
            echo $OUTPUT->footer();
        } else {
            redirect($managenoticepage, get_string('notification:nodeleteallowed', 'local_sitenotice'));
        }
        break;
    case 'confirmeddelete':
        if (get_config('local_sitenotice', 'allow_delete')) {
            helper::delete_notice($noticeid);
            redirect($managenoticepage);
        } else {
            redirect($managenoticepage, get_string('notification:nodeleteallowed', 'local_sitenotice'));
        }
        break;
    case 'edit':
        if (get_config('local_sitenotice', 'allow_update')) {
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('notice:view', 'local_sitenotice'));
            $mform = new notice_form($thispage, $customdata);
            $mform->display();
            echo $OUTPUT->footer();
        } else {
            redirect($managenoticepage, get_string('notification:noupdateallowed', 'local_sitenotice'));
        }
        break;
    default:
        redirect($managenoticepage);
}
