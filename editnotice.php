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

require_once(__DIR__.'/../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();
$PAGE->set_context(context_system::instance());
$PAGE->requires->css('/local/sitenotice/styles.css');

$thispage = new moodle_url('/local/sitenotice/editnotice.php');
$PAGE->set_url($thispage);

$managenoticepage = new moodle_url('/local/sitenotice/managenotice.php');
$PAGE->navbar->add(get_string('setting:managenotice', 'local_sitenotice'), $managenoticepage);

$noticeid = optional_param('noticeid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

if (empty($noticeid)) {
    $mform = new notice_form();
    if ($formdata = $mform->get_data()) {
        helper::create_new_notice($formdata);
        redirect($managenoticepage);
    } else if ($mform->is_cancelled()) {
        redirect($managenoticepage);
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('notice:create', 'local_sitenotice'));
        $mform->display();
        echo $OUTPUT->footer();
    }
} else {
    $notice = helper::retrieve_notice($noticeid);
    if (empty($notice)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('notice:info', 'local_sitenotice'));
        echo $OUTPUT->notification(get_string('notification:noticedoesnotexist', 'local_sitenotice'), 'notifyinfo');
        echo $OUTPUT->footer();
        die;
    }

    switch ($action) {
        case 'report':
            $reportpage = new moodle_url('/local/sitenotice/report.php', ["noticeid" => $noticeid]);
            redirect($reportpage);
            break;
        case 'reset':
            helper::reset_notice($noticeid);
            redirect($managenoticepage);
            break;
        case 'disable':
            helper::reset_notice($noticeid, 0);
            redirect($managenoticepage);
            break;
        case 'enable':
            helper::reset_notice($noticeid, 1);
            redirect($managenoticepage);
            break;
        case 'view':
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('notice:view', 'local_sitenotice'));
            $notice->noticeid = $noticeid;
            $mform = new notice_form(null, ['readonly' => true]);
            $notice->resetinterval = helper::format_time($notice->resetinterval);
            $mform->set_data($notice);
            $mform->display();
            echo $OUTPUT->footer();
            break;
        default:
            redirect($managenoticepage);
    }
}
