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
use table_sql;
use renderable;
use local_sitenotice\helper;
use moodle_url;
use html_writer;

class all_notices extends table_sql implements renderable {

    /**
     * all_notices constructor.
     * @param $uniqueid table unique id
     * @param \moodle_url $url base url
     * @param int $page current page
     * @param int $perpage number of records per page
     * @throws \coding_exception
     * @throws \coding_exception
     */
    public function __construct($uniqueid, \moodle_url $url, $page = 0, $perpage = 20) {
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
     * @throws \coding_exception
     */
    protected function define_table_columns() {
        $cols = array(
            'title' => get_string('notice:title', 'local_sitenotice'),
            'resetinterval' => get_string('notice:resetinterval', 'local_sitenotice'),
            'reqack' => get_string('notice:reqack', 'local_sitenotice'),
            'audience' => get_string('notice:audience', 'local_sitenotice'),
            'content' => get_string('notice:content', 'local_sitenotice'),
            'actions' => get_string('actions'),
            'timemodified' => get_string('notice:timemodified', 'local_sitenotice'),
        );

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }

    /**
     * Define table configuration.
     * @param \moodle_url $url
     * @throws \coding_exception
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
     * Get sql query
     * @param bool $count whether count or get records.
     * @return array
     */
    protected function get_sql_and_params($count = false) {
        if ($count) {
            $select = "COUNT(1)";
        } else {
            $select = "*";
        }

        $sql = "SELECT {$select}
                  FROM {local_sitenotice}";

        if (!$count ) {
            $sql .= "  ORDER BY enabled DESC, timemodified DESC";
        }

        return array($sql, []);
    }

    /**
     * Get data.
     * @param int $pagesize number of records to fetch
     * @param bool $useinitialsbar initial bar
     * @throws \dml_exception
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        list($countsql, $countparams) = $this->get_sql_and_params(true);
        list($sql, $params) = $this->get_sql_and_params();
        $total = $DB->count_records_sql($countsql, $countparams);
        $this->pagesize($pagesize, $total);
        $records = $DB->get_records_sql($sql, $params, $this->pagesize * $this->page, $this->pagesize);
        foreach ($records as $history) {
            $this->rawdata[] = $history;
        }
        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

    /**
     * Custom actions column.
     * @param $row a notice record.
     * @return string
     */
    protected function col_actions($row) {
        global $OUTPUT;
        $links = null;
        $editnotice = '/local/sitenotice/editnotice.php';
        // Edit.
        if (get_config('local_sitenotice', 'allow_update')) {
            $editparams = ['noticeid' => $row->id, 'action' => 'edit', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/edit', get_string('edit'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        // Enable/Disable.
        if ($row->enabled) {
            $editparams = ['noticeid' => $row->id, 'action' => 'disable', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/hide', get_string('notice:disable', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
        } else {
            $editparams = ['noticeid' => $row->id, 'action' => 'enable', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/show', get_string('notice:enable', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
        }
        $links .= ' ' . $editlink;

        // Reset.
        $editparams = ['noticeid' => $row->id, 'action' => 'reset', 'sesskey' => sesskey()];
        $editurl = new moodle_url($editnotice, $editparams);
        $icon = $OUTPUT->pix_icon('t/reset', get_string('notice:reset', 'local_sitenotice'));
        $editlink = html_writer::link($editurl, $icon);
        $links .= ' ' . $editlink;

        // Delete.
        if (get_config('local_sitenotice', 'allow_delete')) {
            $editparams = ['noticeid' => $row->id, 'action' => 'unconfirmeddelete', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('t/delete', get_string('notice:delete', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        if ($row->reqack) {
            // Acknowledge Report.
            $editparams = ['noticeid' => $row->id, 'action' => 'acknowledged_report', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('i/report', get_string('report:button:ack', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;

            // Dismiss Report.
            $editparams = ['noticeid' => $row->id, 'action' => 'dismissed_report', 'sesskey' => sesskey()];
            $editurl = new moodle_url($editnotice, $editparams);
            $icon = $OUTPUT->pix_icon('i/risk_xss', get_string('report:button:dis', 'local_sitenotice'));
            $editlink = html_writer::link($editurl, $icon);
            $links .= ' ' . $editlink;
        }

        return $links;
    }

    /**
     * Custom reset interval column.
     * @param $row a notice record.
     * @return string
     * @throws \coding_exception
     */
    protected function col_resetinterval($row) {
        return helper::format_interval_time($row->resetinterval);
    }

    /**
     * Custom reset audience column.
     * @param $row a notice record.
     * @return mixed
     * @throws \coding_exception
     */
    protected function col_audience($row) {
        return helper::get_audience_name($row->audience);
    }

    /**
     * Custom reset require acknowledge column.
     * @param $row a notice record.
     * @return mixed
     * @throws \coding_exception
     */
    protected function col_reqack($row) {
        return helper::format_boolean($row->reqack);
    }

    /**
     * Custom reset time modified column.
     * @param $row a notice record.
     * @return mixed
     * @throws \coding_exception
     */
    protected function col_timemodified($row) {
        if ($row->timemodified) {
            return userdate($row->timemodified);
        } else {
            return '-';
        }
    }

    /**
     * Custome content column.
     * @param $row $row a notice record.
     * @return string
     * @throws \coding_exception
     */
    protected function col_content($row) {
        return html_writer::link("#", get_string('view'),
            ['class' => 'notice-preview', 'data-noticecontent' => $row->content]);
    }
}
