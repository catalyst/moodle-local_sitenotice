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
 * Report of users who dismissed a notice.
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(__DIR__.'/../../../config.php');

use local_sitenotice\helper;
use local_sitenotice\report_filter;
use local_sitenotice\table\dismissed_notice;

require_login();
helper::check_manage_capability();

// Parameters.
$noticeid = required_param('noticeid', PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);

// Set up page.
$thispage = new moodle_url('/local/sitenotice/report/dismissed_report.php', ['noticeid' => $noticeid]);

$PAGE->set_url($thispage);
$PAGE->set_context(context_system::instance());

$managenoticepage = new moodle_url('/local/sitenotice/managenotice.php');
$PAGE->navbar->add(get_string('setting:managenotice', 'local_sitenotice'), $managenoticepage);
$PAGE->requires->css('/local/sitenotice/styles.css');

$output = $PAGE->get_renderer('local_sitenotice');
$notice = helper::retrieve_notice($noticeid);

if (empty($notice)) {
    echo $output->header();
    echo $OUTPUT->notification(get_string('notification:nodis', 'local_sitenotice'), 'notifyinfo');
    echo $output->footer();
}
// Get current filter for the report.
$filter = new report_filter($thispage, dismissed_notice::TABLE_ALIAS);
list($filtersql, $params) = $filter->get_sql_filter();

$table = new dismissed_notice('dismissed_notice_table', $thispage, $notice->id, ['filtersql' => $filtersql, 'params' => $params],
    $download, $page, 20);
if ($table->is_downloading()) {
    \core\session\manager::write_close();
    echo $output->render($table);
    die();
}

echo $output->header();
echo $output->heading($notice->title);
echo $output->heading(get_string('report:dismissed_desc', 'local_sitenotice'), 4);
$filter->display_forms();
echo $output->render($table);
echo $output->footer();
