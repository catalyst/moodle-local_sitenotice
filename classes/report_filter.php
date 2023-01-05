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


namespace local_sitenotice;

use local_sitenotice\form\active_filter_form;
use local_sitenotice\form\add_filter_form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/user/filters/date.php');

/**
 * For filtering the notice report
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_filter {

    /**
     * User data.
     * @var \user_filter_date[]
     */
    private $filterfields;

    /**
     * Custom data.
     * @var \user_filter_date[][]
     */
    private $customdata;

    /**
     * Base URL.
     * @var
     */
    private $baseurl;

    /**
     * Filter form.
     * @var \local_sitenotice\form\add_filter_form
     */
    private $addfilterform;

    /**
     * Active filter form.
     * @var \local_sitenotice\form\active_filter_form
     */
    private $activefilterform;

    /**
     * Constructor.
     *
     * @param string $baseurl Base URL.
     * @param string $tablealias Table alias.
     *
     * @throws \coding_exception
     */
    public function __construct($baseurl, $tablealias = '') {
        $tablealias = $tablealias ? $tablealias . "." : '';
        $this->filterfields = [
            "timecreated" => new \user_filter_date('time', get_string('time'), false, "{$tablealias}timecreated"),
        ];
        $this->baseurl = $baseurl;
        $this->customdata = ['fields' => $this->filterfields];
        $this->addfilterform = new add_filter_form($this->baseurl, $this->customdata);
        $this->activefilterform = new active_filter_form($this->baseurl, $this->customdata);
    }

    /**
     * Add filter to the $SESSION.
     * @throws \moodle_exception
     */
    private function add_filters() {
        global $SESSION;
        if ($adddata = $this->addfilterform->get_data()) {
            if (!isset($SESSION->noticereportfilter)) {
                $SESSION->noticereportfilter = [];
            }
            foreach ($this->filterfields as $fname => $field) {
                $data = $field->check_data($adddata);
                if ($data !== false) {
                    $SESSION->noticereportfilter[$fname] = [];
                    $SESSION->noticereportfilter[$fname][] = $data;
                }
            }
            // Reload page. Otherwise, the noticeid will disappear in the address url due to POST method.
            redirect($this->baseurl);
        }
    }

    /**
     * Remove all filters from the $SESSION.
     * @throws \moodle_exception
     */
    private function remove_filters() {
        global $SESSION;
        if ($activedata = $this->activefilterform->get_data()) {
            if (!empty($activedata->removeall)) {
                $SESSION->noticereportfilter = [];
            }
            // Reload page. Otherwise, the noticeid will disappear in the address url due to POST method.
            redirect($this->baseurl);
        }
    }

    /**
     * Get filter sql from active filters.
     * @return array
     */
    private function process_filter() {
        global $SESSION;
        $filtersql = [];
        $params = [];
        if (isset($SESSION->noticereportfilter)) {
            $filters = $SESSION->noticereportfilter;
            foreach ($filters as $fname => $data) {
                if (isset($this->filterfields[$fname])) {
                    list($s, $p) = $this->filterfields[$fname]->get_sql_filter(reset($data));
                    $filtersql[] = $s;
                    $params = $params + $p;
                }
            }

            if (!empty($filtersql)) {
                $filtersql = implode(' AND ', $filtersql);
            }
        }
        return [$filtersql, $params];
    }

    /**
     * Steps involved to get the final filter sql
     * @return array filter sql and its params
     * @throws \moodle_exception
     */
    public function get_sql_filter() {
        // Add new filters if any.
        $this->add_filters();
        // Remove filters if any.
        $this->remove_filters();
        // Process current filter.
        return $this->process_filter();
    }

    /**
     * Display add filter and active filter from.
     */
    public function display_forms() {
        $this->addfilterform->display();
        $this->activefilterform->display();
    }
}
