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
 * Table to show list of existing notices.
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sitenotice\table;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use local_sitenotice\persistent\sitenotice;
use table_sql;
use renderable;
use local_sitenotice\helper;
use moodle_url;
use html_writer;

class all_notices extends table_sql implements renderable {

    /**
     * all_notices constructor.
     * @param string$uniqueid table unique id
     * @param \moodle_url $url base url
     * @param int $page current page
     * @param int $perpage number of records per page
     */
    public function __construct(string $uniqueid, \moodle_url $url, int $page = 0, int $perpage = 20) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'local_sitenotice sitenotices');

        // Set protected properties.
        $this->pagesize = $perpage;
        $this->page = $page;

        // Define columns in the table.
        $this->define_table_columns();

        // Define configs.
        $this->define_table_configs($url);
    }

    /**
     * Table columns and corresponding headers.
     */
    protected function define_table_columns() {
        $cols = array(
            'title' => get_string('notice:title', 'local_sitenotice'),
            'resetinterval' => get_string('notice:resetinterval', 'local_sitenotice'),
            'reqack' => get_string('notice:reqack', 'local_sitenotice'),
            'forcelogout' => get_string('notice:forcelogout', 'local_sitenotice'),
            'reqcourse' => get_string('notice:reqcourse', 'local_sitenotice'),
            'timestart' => get_string('notice:activefrom', 'local_sitenotice'),
            'timeend' => get_string('notice:expiry', 'local_sitenotice'),
            'cohort' => get_string('notice:cohort', 'local_sitenotice'),
            'content' => get_string('notice:content', 'local_sitenotice'),
            'actions' => get_string('actions'),
            'timemodified' => get_string('notice:timemodified', 'local_sitenotice'),
        );

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }

    /**
     * Define table configuration.
     *
     * @param \moodle_url $url
     */
    protected function define_table_configs(\moodle_url $url) {
        // Set table url.
        $this->define_baseurl($url);

        // Set table configs.
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
    }

    /**
     * Get data.
     *
     * @param int $pagesize number of records to fetch
     * @param bool $useinitialsbar initial bar
     */
    public function query_db($pagesize, $useinitialsbar = true): void {
        $records = sitenotice::get_records([], 'enabled, timemodified', 'DESC', $this->pagesize * $this->page, $this->pagesize);
        $total = count($records);
        $this->pagesize($pagesize, $total);

        foreach ($records as $record) {
            $this->rawdata[] = $record;
        }

        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

    /**
     * Custom actions column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_actions(sitenotice $sitenotice): string {
        global $OUTPUT;
        $links = null;
        $editnotice = '/local/sitenotice/editnotice.php';
        // Edit.
        if (get_config('local_sitenotice', 'allow_update')) {
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'edit', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        // Enable/Disable.
        if ($sitenotice->get('enabled')) {
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'disable', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/hide', get_string('notice:disable', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
        } else {
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'enable', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/show', get_string('notice:enable', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
        }
        $links .= ' ' . $editlink;

        // Reset.
        $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'reset', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/reset', get_string('notice:reset', 'local_sitenotice'));
        $editlink = html_writer::link($editurl, $icon);
        $links .= ' ' . $editlink;

        // Delete.
        if (get_config('local_sitenotice', 'allow_delete')) {
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'unconfirmeddelete', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/delete', get_string('notice:delete', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        if ($sitenotice->get('reqack')) {
            // Acknowledge Report.
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'acknowledged_report', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('i/report', get_string('report:button:ack', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;

            // Dismiss Report.
            $editparams = ['noticeid' => $sitenotice->get('id'), 'action' => 'dismissed_report', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('i/risk_xss', get_string('report:button:dis', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        return $links;
    }

    /**
     * Custom reset title column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_title(sitenotice $sitenotice): string {
        return $sitenotice->get('title');
    }

    /**
     * Custom reset interval column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_resetinterval(sitenotice $sitenotice): string {
        return helper::format_interval_time($sitenotice->get('resetinterval'));
    }

    /**
     * Custom reset cohort column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_cohort(sitenotice $sitenotice): string {
        if (empty($sitenotice->get('cohorts'))) {
            $cohort = get_string('notice:cohort:all', 'local_sitenotice');
        } else {
            $cohorts = array_map(function ($cohortid) {
                return helper::get_cohort_name($cohortid);
            }, $sitenotice->get('cohorts'));

            $cohort = implode(', ', $cohorts);
        }

        return $cohort;
    }

    /**
     * Custom reset require acknowledge column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_reqack(sitenotice $sitenotice): string {
        return helper::format_boolean($sitenotice->get('reqack'));
    }

    /**
     * The force logout column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_forcelogout(sitenotice $sitenotice): string {
        return helper::format_boolean($sitenotice->get('forcelogout'));
    }

    /**
     * The timestart column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_timestart(sitenotice $sitenotice): string {
        return $sitenotice->get('timestart') == 0 ? "-" : userdate($sitenotice->get('timestart'));
    }

    /**
     * The timeend column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_timeend(sitenotice $sitenotice): string {
        return $sitenotice->get('timeend') == 0 ? '-' : userdate($sitenotice->get('timeend'));
    }

    /**
     * Custom require course completion column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_reqcourse(sitenotice $sitenotice): string {
        return helper::get_course_name($sitenotice->get('reqcourse'));
    }

    /**
     * Custom reset time modified column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_timemodified(sitenotice $sitenotice): string {
        if ($sitenotice->get('timemodified')) {
            return userdate($sitenotice->get('timemodified'));
        } else {
            return '-';
        }
    }

    /**
     * Custom content column.
     *
     * @param sitenotice $sitenotice a notice record.
     * @return string
     */
    protected function col_content(sitenotice $sitenotice): string {
        return html_writer::link("#", get_string('view'),
            ['class' => 'notice-preview', 'data-noticecontent' => $sitenotice->get('content')]);
    }

}
