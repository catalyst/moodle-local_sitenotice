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
namespace local_sitenotice;

use core_competency\url;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');

class helper {

    public static function create_new_notice($data) {
        global $DB;
        $data->timecreated = time();
        $data->timemodified = time();
        $transaction = $DB->start_delegated_transaction();
        $noticeid = $DB->insert_record('local_sitenotice', $data);
        if (!empty($noticeid)) {
            $dom = new \DOMDocument();
            $dom->loadHTML($data->content);
            foreach ($dom->getElementsByTagName('a') as $node) {
                $link = new \stdClass();
                $link->noticeid = $noticeid;
                $link->text = trim($node->nodeValue);
                $link->link = trim($node->getAttribute("href"));
                $DB->insert_record('local_sitenotice_hlinks', $link);
            }
        }
        $transaction->allow_commit();
    }

    public static function retrieve_notice($noticeid) {
        global $DB;
        return $DB->get_record('local_sitenotice', ['id' => $noticeid]);
    }

    public static function retrieve_all_notices($sort = '') {
        global $DB;
        return $DB->get_records('local_sitenotice', null, $sort);
    }

    public static function retrieve_enabled_notices($sort = '') {
        global $DB;
        return $DB->get_records('local_sitenotice', ['enabled' => 1], $sort);
    }

    public static function retrieve_user_notices($userid) {
        global $USER;
        if (!$userid) {
            return [];
        }
        $notices = self::retrieve_enabled_notices();
        if (isset($USER->viewednotices)) {
            $notices = array_diff_key($notices, $USER->viewednotices);
        }
        return $notices;
    }

    public static function disable_notice($noticeid) {
        global $DB;
        $notice = self::retrieve_notice($noticeid);
        $notice->enabled = 0;
        $notice->timemodified = time();
        return $DB->update_record('local_sitenotice', $notice);
    }

    public static function enable_notice($noticeid) {
        global $DB;
        $notice = self::retrieve_notice($noticeid);
        $notice->enabled = 1;
        $notice->timemodified = time();
        return $DB->update_record('local_sitenotice', $notice);
    }

    public static function built_audience_option() {
        $option = ['0' => 'All Users'];
        $cohorts = cohort_get_all_cohorts();
        foreach ($cohorts['cohorts'] as $cohort) {
            $option[$cohort->id] = $cohort->name;
        }
        return $option;
    }

    public static function dismiss_notice($noticeid) {
        global $USER;
        $USER->viewednotices[$noticeid] = $noticeid;

        $result = array();

        $notice = self::retrieve_notice($noticeid);
        if ($notice && $notice->reqack) {
            require_logout();
            $loginpage = new \moodle_url("/login/index.php");
            $result['redirecturl'] = $loginpage->out();
        }
        // TODO: Log dismiss event.

        $result['status'] = true;
        return $result;
    }

    public static function acknowledge_notice($noticeid) {
        global $USER;
        $USER->viewednotices[$noticeid] = $noticeid;
        // TODO: ACK DB
        // TODO: Log ack event.
        $result = array();
        $result['status'] = true;
        return $result;
    }
}