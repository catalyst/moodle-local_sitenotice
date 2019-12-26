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
 * View report
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__.'/../../config.php');

use local_sitenotice\helper;
use local_sitenotice\report_filter;

require_login();
require_capability('moodle/site:config', context_system::instance());

// Parameters.
$noticeid = required_param('noticeid', PARAM_INT);
$download = optional_param('download', false, PARAM_BOOL);

// Set up page.
$thispage = '/local/sitenotice/report.php';
$managenoticepage = '/local/sitenotice/managenotice.php';
$url = new moodle_url($thispage, ['noticeid' => $noticeid]);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->navbar->add(get_string('report:name', 'local_sitenotice'));
$PAGE->requires->css('/local/sitenotice/styles.css');

// Get current filter for the report.
$filter = new report_filter($url);
list($filtersql, $params) = $filter->get_sql_filter();

$notice = helper::retrieve_notice($noticeid);
$records = helper::retrieve_acknowlegement($filtersql, $params);

// Do not show header if downloading.
if (!$download) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading($notice->title);
    $filter->display_forms();
}

if (!empty($records)) {
    // Display Table.
    if (!$download) {
        $button = $OUTPUT->single_button(new moodle_url($thispage, ['noticeid' => $noticeid, 'download' => true]),
            get_string("downloadtext"));
        echo html_writer::tag('div', $button, array('class' => 'noticereport'));

        // Notice table.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable';
        $table->head = array(
            get_string('notice:title', 'local_sitenotice'),
            get_string('username'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('idnumber'),
            get_string('notice:hlinkcount', 'local_sitenotice'),
            get_string('time'),
        );
        /*
         * To check if the record is next user
         * As to show the hlink count in the first row of a user.
         */
        $currentuserid = 0;
        foreach ($records as $record) {
            $row = array();
            $row[] = $record->noticetitle;
            $row[] = $record->username;
            $row[] = $record->firstname;
            $row[] = $record->lastname;
            $row[] = $record->idnumber;
            // Show all link counts in the same column.
            $hlinkcount = '';
            if ($currentuserid != $record->userid) {
                $currentuserid = $record->userid;
                $linkcounts = helper::retrieve_hlink_count($record->userid, $record->noticeid);
                foreach ($linkcounts as $count) {
                    $hlinkcount .= "<a href=\"{$count->link}\">{$count->text}</a>: $count->count <br/>";
                }
            }
            $row[] = $hlinkcount;

            $row[] = userdate($record->timecreated);
            $table->data[] = $row;
        }

        echo html_writer::table($table);
        echo $OUTPUT->footer();
    } else {
        // Download report.
        $filename = clean_filename(strip_tags(format_string($notice->title, true)).'.csv');
        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate,post-check=0,pre-check=0");
        header("Pragma: public");

        // Headers.
        $header = array(
            get_string('notice:title', 'local_sitenotice'),
            get_string('username'),
            get_string('firstname'),
            get_string('lastname'),
            get_string('idnumber'),
            get_string('time'),
        );
        // Add each hyperlink as a header.
        $hlinks = helper::retrieve_notice_hlinks($noticeid);
        $hlinkheaders = [];
        foreach ($hlinks as $link) {
            $hlinkheaders[$link->id] = "$link->text ($link->link)";
        }
        $header = array_merge($header, $hlinkheaders);

        echo implode("\t", $header) . "\n";

        // Rows.
        $currentuserid = 0;
        foreach ($records as $record) {
            $row = array();
            $row[] = $record->noticetitle;
            $row[] = $record->username;
            $row[] = $record->firstname;
            $row[] = $record->lastname;
            $row[] = $record->idnumber;
            $row[] = userdate($record->timecreated);

            if ($currentuserid != $record->userid) {
                $currentuserid = $record->userid;
                $linkcounts = helper::retrieve_hlink_count($record->userid, $record->noticeid);
                foreach (array_keys($hlinkheaders) as $linkid) {
                    $row[] = $linkcounts[$linkid]->count;
                }
            }
            echo implode("\t", $row) . "\n";
        }
    }
} else {
    echo $OUTPUT->notification(get_string('notification:noack', 'local_sitenotice'), 'notifyinfo');
    echo $OUTPUT->footer();
}
