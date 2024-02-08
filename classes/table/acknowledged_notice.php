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

namespace local_sitenotice\table;

use table_sql;
use renderable;
use local_sitenotice\helper;
use local_sitenotice\persistent\acknowledgement;
use local_sitenotice\persistent\linkhistory;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

/**
 * Table to show list of users who dismissed a notice.
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acknowledged_notice extends table_sql implements renderable {

    /**
     * Notice id.
     * @var string
     */
    protected $noticeid = '';

    /**
     * Table alias.
     */
    const TABLE_ALIAS = 'ack';

    /**
     * Days (in seconds) different between  1 January 1900 and 1 January 1970.
     */
    const DAY_SECS_SPREADSHEET_DIFF = 25569;

    /**
     * To check next user.
     * @var string
     */
    protected $previoususer = '';

    /**
     * Constructor.
     *
     * @param string $uniqueid id of the table.
     * @param \moodle_url $url base url.
     * @param int $noticeid notice id.
     * @param array $filters  filter.
     * @param string $download download file format.
     * @param int $page current page.
     * @param int $perpage  number of record per page.
     */
    public function __construct(string $uniqueid, \moodle_url $url, int $noticeid, array $filters = [],
                                string $download = '', int $page = 0, int $perpage = 20) {
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
            $cols['timecreated'] = get_string('report:timecreated_server', 'local_sitenotice');
            $cols['timecreated_spreadsheet'] = get_string('report:timecreated_spreadsheet', 'local_sitenotice');
        } else {
            $cols = array(
                'noticetitle' => get_string('notice:title', 'local_sitenotice'),
                'username' => get_string('username'),
                'firstname' => get_string('firstname'),
                'lastname' => get_string('lastname'),
                'idnumber' => get_string('idnumber'),
                'hlinkcount' => get_string('notice:hlinkcount', 'local_sitenotice'),
                'timecreated' => get_string('report:timecreated_server', 'local_sitenotice'),
            );
            $this->no_sorting('hlinkcount');
        }

        $this->define_columns(array_keys($cols));
        $this->define_headers(array_values($cols));
    }

    /**
     * Define table configuration.
     *
     * @param \moodle_url $url URL.
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
     *
     * @param bool $count whether count or get records.
     * @return array
     */
    protected function get_sql_and_params(bool $count = false) {
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
     *
     * @param \stdClass $row dismissed notice record
     * @return string
     */
    protected function col_timecreated(\stdClass $row) {
        $this->previoususer = $row->userid;
        if ($row->timecreated) {
            return userdate($row->timecreated, get_string('report:timeformat:sortable', 'local_sitenotice'), null, false);
        } else {
            return '-';
        }
    }

    /**
     * Custom time for spreadsheet date time.
     *
     * @param \stdClass $row dismissed notice record
     * @return string
     */
    protected function col_timecreated_spreadsheet(\stdClass $row) {
        if ($row->timecreated) {
            return self::DAY_SECS_SPREADSHEET_DIFF + ($row->timecreated / DAYSECS);
        } else {
            return '-';
        }
    }

    /**
     * Custom link count column.
     *
     * @param \stdClass $row acknowledged notice record
     * @return string
     */
    protected function col_hlinkcount(\stdClass $row) {
        // Only count on the first record of a user.
        if ($this->previoususer == $row->userid) {
            return '';
        }
        $hlinkcount = '';
        $linkcounts = linkhistory::count_clicked_links($row->userid, $row->noticeid);
        foreach ($linkcounts as $count) {
            $hlinkcount .= "<a href=\"{$count->link}\">{$count->text}</a>: $count->count <br/>";
        }
        return $hlinkcount;
    }

    /**
     * Process hyperlink counts.
     *
     * @param int $column column
     * @param \stdClass $row rows
     *
     * @return string|null
     */
    public function other_cols($column, $row) {
        // Check if the column name is the id of a notice hyperlink.
        if ($column > 0 && is_numeric($column)) {
            // Only do link count on the first record of a user.
            if ($this->previoususer == $row->userid) {
                return '';
            }
            $linkcounts = linkhistory::count_clicked_links($row->userid, $row->noticeid, $column);
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
