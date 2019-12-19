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
 * @package local_sitenotice;

 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitenotice;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/user/filters/date.php');

use local_sitenotice\form\active_filter_form;
use local_sitenotice\form\add_filter_form;

class report_filter {
    private $filterfields;
    private $customdata;
    private $baseurl;
    private $addfilterform;
    private $activefilterform;

    public function __construct($baseurl){
        $this->filterfields = [
            'timecreated' => new \user_filter_date('time', get_string('time'), false, 'timecreated'),
        ];
        $this->baseurl = $baseurl;
        $this->customdata = ['fields' => $this->filterfields];
        $this->addfilterform = new add_filter_form($this->baseurl, $this->customdata);
        $this->activefilterform = new active_filter_form($this->baseurl, $this->customdata);
    }

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
            // Reload page
            redirect($this->baseurl);
        }
    }

    private function remove_filters() {
        global $SESSION;
        if ($activedata = $this->activefilterform->get_data()) {
            if (!empty($activedata->removeall)) {
                $SESSION->noticereportfilter = [];
            }
            // Reload page
            redirect($this->baseurl);
        }
    }

    private function process_filter(){
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

    public function get_sql_filter() {
        // Add new filters if any.
        $this->add_filters();
        // Remove filters if any.
        $this->remove_filters();
        // Proccess current filter.
        return $this->process_filter();
    }

    public function display_forms() {
        $this->addfilterform->display();
        $this->activefilterform->display();
    }
}