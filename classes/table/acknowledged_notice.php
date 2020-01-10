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
 * Table to show list of users who dismissed a notice.
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sitenotice\table;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use local_sitenotice\persistent\acknowledgement;
use table_sql;
use renderable;
use local_sitenotice\helper;

class acknowledged_notice extends table_sql implements renderable {
    // Notice id.
    protected $noticeid = '';

    // Table alias for standard log.
    const TABLE_ALIAS = 'ack';

    // To check next user.
    protected $previoususer = '';

    /**
     * Constructor.
     * @param $uniqueid unique id of the table
     * @param \moodle_url $url base url
     * @param $noticeid notice id
     * @param array $filters filter
     * @param string $download download file format
     * @param int $page current page
     * @param int $perpage number of record per page
     * @throws \coding_exception
     */
    public function __construct($uniqueid, \moodle_url $url, $filters = [],
                                $download = '', $page = 0, $perpage = 20, $noticeid) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'local_sitenotice acknowledged_notices');

        // Set protected properties.
        $this->pagesize = $perpage;
        $this->page = $page;
        $this->filters = (object)$filters;
        $this->noticeid = $noticeid;

        // Set download status.
        $currenttime = userdate(time(), get_string('report:timeformat:sortable', 'local_sitenotice'), null, false);
        $this->is_downloading($download, get_string('report:acknowledged', 'local_sitenotice', $currenttime));

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
        if ($this->is_downloading()) {
            $cols = array(
                'noticetitle' => get_string('notice:title', 'local_sitenotice'),
                'username' => get_string('username'),
                'firstname' => get_string('firstname'),
                'lastname' => get_string('lastname'),
                'idnumber' => get_string('idnumber'),
            );
            // Add each hyperlink as a header.
            $hlinks = helper::retrieve_notice_links($this->noticeid);
            foreach ($hlinks as $link) {
                $cols[$link->id] = "$link->text ($link->link)";
            }
            $cols['timecreated'] = get_string('event:timecreated', 'local_sitenotice');
        } else {
            $cols = array(
                'noticetitle' => get_string('notice:title', 'local_sitenotice'),
                'username' => get_string('username'),
                'firstname' => get_string('firstname'),
                'lastname' => get_string('lastname'),
                'idnumber' => get_string('idnumber'),
                'hlinkcount' => get_string('notice:hlinkcount', 'local_sitenotice'),
                'timecreated' => get_string('event:timecreated', 'local_sitenotice'),
            );
        }

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
        $urlparams = $this->filters->params;
        unset($urlparams['submitbutton']);
        $url->params($urlparams);
        $this->define_baseurl($url);

        // Set table configs.
        $this->collapsible(false);
        $this->sortable(true, 'username', SORT_DESC);
        $this->pageable(true);
    }

    /**
     * Get sql query
     * @param bool $count whether count or get records.
     * @return array
     */
    protected function get_sql_and_params($count = false) {
        $alias = self::TABLE_ALIAS;
        if ($count) {
            $select = "COUNT(1)";
        } else {
            $select = "{$alias}.*";
        }

        list($where, $params) = $this->get_filters_sql_and_params();

        $sql = "SELECT {$select}
                  FROM {local_sitenotice_ack} {$alias}
                 WHERE {$where}";

        // Add order by if needed.
        if (!$count && $sqlsort = $this->get_sql_sort()) {
            $sql .= " ORDER BY " . $sqlsort;
        }

        return array($sql, $params);
    }

    /**
     * Get filter sql
     * @return array
     */
    protected function get_filters_sql_and_params() {
        $filter = "noticeid = :noticeid AND action = :ackaction";
        $params = ['noticeid' => $this->noticeid,
            'ackaction' => acknowledgement::ACTION_ACKNOWLEDGED];
        if (!empty($this->filters->filtersql)) {
            $filter .= " AND {$this->filters->filtersql}";
            $params = array_merge($this->filters->params, $params);
        }
        return array($filter, $params);
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
        if ($this->is_downloading()) {
            $records = $DB->get_records_sql($sql, $params);
        } else {
            $records = $DB->get_records_sql($sql, $params, $this->pagesize * $this->page, $this->pagesize);
        }
        foreach ($records as $history) {
            $this->rawdata[] = $history;
        }
        // Set initial bars.
        if ($useinitialsbar) {
            $this->initialbars($total > $pagesize);
        }
    }

    /**
     * Custom time column.
     * @param $row dismissed notice record
     * @return string
     */
    protected function col_timecreated($row) {
        $this->previoususer = $row->userid;
        if ($row->timecreated) {
            return userdate($row->timecreated);
        } else {
            return '-';
        }
    }

    /**
     * Custom link count column.
     * @param $row acknowledged notice record
     * @return string
     * @throws \dml_exception
     */
    protected function col_hlinkcount($row) {
        // Only count on the first record of a user.
        if ($this->previoususer == $row->userid) {
            return '';
        }
        $hlinkcount = '';
        $linkcounts = helper::count_clicked_notice_links($row->userid, $row->noticeid);
        foreach ($linkcounts as $count) {
            $hlinkcount .= "<a href=\"{$count->link}\">{$count->text}</a>: $count->count <br/>";
        }
        return $hlinkcount;
    }

    /**
     * Process hyperlink counts.
     * @param $column column
     * @param $row rows
     * @return string|null
     * @throws \dml_exception
     */
    public function other_cols($column, $row) {
        // Check if the column name is the id of a notice hyperlink.
        if ($column > 0) {
            // Only do link count on the first record of a user.
            if ($this->previoususer == $row->userid) {
                return '';
            }
            $linkcounts = helper::count_clicked_notice_links($row->userid, $row->noticeid, $column);
            if (isset($linkcounts[$column])) {
                return $linkcounts[$column]->count;
            } else {
                return '0';
            }
        } else {
            return null;
        }
    }
}
