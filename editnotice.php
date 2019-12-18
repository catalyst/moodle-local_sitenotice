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
 *
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

$thispage = '/local/sitenotice/editnotice.php';
$managenoticepage = '/local/sitenotice/managenotice.php';
$reportpage = '/local/sitenotice/report.php';
$PAGE->set_url(new moodle_url($thispage));

$noticeid = optional_param('noticeid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

if (empty($noticeid)) {
    $mform = new notice_form();
    if ($formdata = $mform->get_data()) {
        helper::create_new_notice($formdata);
        redirect(new moodle_url($managenoticepage));
    } else if ($mform->is_cancelled()) {
        redirect(new moodle_url($managenoticepage));
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('notice:create', 'local_sitenotice'));
        $mform->display();
        echo $OUTPUT->footer();
    }
} else {
    switch ($action) {
        case 'report':
            redirect(new moodle_url($reportpage, ["noticeid" => $noticeid]));
            break;
        case 'reset':
            helper::reset_notice($noticeid);
            redirect(new moodle_url($managenoticepage));
            break;
        case 'disable':
            helper::reset_notice($noticeid, 0);
            redirect(new moodle_url($managenoticepage));
            break;
        case 'enable':
            helper::reset_notice($noticeid, 1);
            redirect(new moodle_url($managenoticepage));
            break;
        case 'view':
            $notice = helper::retrieve_notice($noticeid);
            $notice->noticeid = $noticeid;
            $mform = new notice_form(null, ['readonly' => true]);
            $mform->set_data($notice);
            echo $OUTPUT->header();
            echo $OUTPUT->heading(get_string('notice:view', 'local_sitenotice'));
            $mform->display();
            echo $OUTPUT->footer();
            break;
        default:
            redirect(new moodle_url($managenoticepage));
    }
}


