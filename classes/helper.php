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
 * Helper class to create, retrieve, manage notices
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_sitenotice;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');
use \local_sitenotice\persistent\sitenotice;
use \local_sitenotice\persistent\noticelink;
use \local_sitenotice\persistent\linkhistory;
use \local_sitenotice\persistent\acknowledgement;
use \local_sitenotice\persistent\noticeview;

class helper {

    /**
     * Create new notice
     * @param $data form data
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function create_new_notice($data) {
        // Create new notice.
        $sitenotice = sitenotice::create_new_notice($data);
        // Extract hyperlinks and set ids for them.
        $noticeid = $sitenotice->get('id');
        $content = $sitenotice->get('content');
        $newcontent = self::update_hyperlinks($noticeid, $content);
        sitenotice::update_notice_content($sitenotice, $newcontent);

        // Log created event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $noticeid,
            'relateduserid' => $sitenotice->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_created::create($params);
        $event->trigger();
    }

    /**
     * Update existing notice.
     * @param sitenotice $sitenotice site notice persistent
     * @param $data form data
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function update_notice(sitenotice $sitenotice, $data) {
        if (!get_config('local_sitenotice', 'allow_update')) {
            return;
        }
        // Check if there is any changes in the hyperlinks.
        $noticeid = $sitenotice->get('id');
        $data->content = self::update_hyperlinks($noticeid, $data->content);
        // Update notice.
        sitenotice::update_notice_data($sitenotice, $data);

        // Log updated event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $noticeid,
            'relateduserid' => $sitenotice->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_updated::create($params);
        $event->trigger();
    }

    /**
     * Extract hyperlink from notice content.
     * @param $noticeid notice id
     * @param $content notice content
     * @return string
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    private static function update_hyperlinks($noticeid, $content) {
        // Extract hyperlinks from the content of the notice, which is then used for link clicked tracking.
        $dom = new \DOMDocument();
        $content = format_text($content, FORMAT_HTML);
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8' );
        $dom->loadHTML($content);
        // Current links in the notice.
        $currentlinks = self::retrieve_notice_links($noticeid);
        $newlinks = [];

        foreach ($dom->getElementsByTagName('a') as $node) {
            $link = new \stdClass();
            $link->noticeid = $noticeid;
            $link->text = trim($node->nodeValue);
            $link->link = trim($node->getAttribute("href"));

            // Create new or reuse link.
            $linkpersistent = noticelink::create_new_link($link);
            $linkid = $linkpersistent->get('id');
            $newlinks[$linkid] = $linkpersistent;

            // ID to use for link tracking in javascript.
            $node->setAttribute('data-linkid', $linkid);
            $node->setAttribute('target', '_blank');
        }

        // Clean up unused links.
        $unusedlinks = array_diff_key($currentlinks, $newlinks);
        noticelink::delete_links(array_keys($unusedlinks));

        // New content of the notice (included link ids).
        $newcontent = $dom->saveHTML();
        return $newcontent;
    }

    /**
     * Reset a notice
     * @param $noticeid notice id
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function reset_notice($noticeid) {
        try {
            $sitenotice = sitenotice::reset($noticeid);
            // Log reset event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $sitenotice->get('id'),
                'relateduserid' => $sitenotice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_reset::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Enable a notice
     * @param $noticeid notice id
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function enable_notice($noticeid) {
        try {
            $sitenotice = sitenotice::enable($noticeid);
            // Log enabled event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $sitenotice->get('id'),
                'relateduserid' => $sitenotice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_updated::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Disable a notice
     * @param $noticeid notice id
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function disable_notice($noticeid) {
        try {
            $sitenotice = sitenotice::disable($noticeid);
            // Log disable event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $sitenotice->get('id'),
                'relateduserid' => $sitenotice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_updated::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Delete a notice
     * @param $noticeid notice id
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public static function delete_notice($noticeid) {
        if (!get_config('local_sitenotice', 'allow_delete')) {
            return;
        }
        $sitenotice = sitenotice::get_record(['id' => $noticeid]);
        if ($sitenotice) {
            $sitenotice->delete();

            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $sitenotice->get('id'),
                'relateduserid' => $sitenotice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_deleted::create($params);
            $event->trigger();

            if (!get_config('local_sitenotice', 'cleanup_deleted_notice')) {
                return;
            }
            acknowledgement::delete_notice_acknowledgement($noticeid);
            noticeview::delete_notice_view($noticeid);
            $noticelinks = self::retrieve_notice_links($noticeid);
            if (!empty($noticelinks)) {
                linkhistory::delete_link_history(array_keys($noticelinks));
                noticelink::delete_notice_links($noticeid);
            }
        }
    }

    /**
     * Built Audience options based on site cohorts.
     * @return array
     * @throws \coding_exception
     */
    public static function built_audience_options() {
        $option = ['0' => get_string('notice:audience:all', 'local_sitenotice')];
        $cohorts = cohort_get_all_cohorts();
        foreach ($cohorts['cohorts'] as $cohort) {
            $option[$cohort->id] = $cohort->name;
        }
        return $option;
    }

    /**
     * Retrieve all notices
     * @return array
     * @throws \dml_exception
     */
    public static function retrieve_all_notices() {
        return sitenotice::get_all_notice_records();
    }

    /**
     * Retrieve active notices
     * @return array
     * @throws \dml_exception
     */
    public static function retrieve_enabled_notices() {
        return sitenotice::get_enabled_notices();
    }

    /**
     * Retrieve notices applied to user.
     * @return array
     * @throws \dml_exception
     */
    public static function retrieve_user_notices() {
        global $USER;
        $notices = self::retrieve_enabled_notices();

        if (empty($notices)) {
            return [];
        }

        // Only load at login time.
        if (!isset($USER->viewednotices)) {
            self::load_viewed_notices();
        }
        /*
         * Check for updated notice
         * Exclude it from viewed notices if it is updated (based on timemodified)
         */
        $viewednotices = $USER->viewednotices;
        foreach ($viewednotices as $noticeid => $data) {
            // The notice is disabled during the current session.
            if (!isset($notices[$noticeid])) {
                continue;
            }
            $notice = $notices[$noticeid];
            if ($data['timeviewed'] < $notice->timemodified
                || (($notice->resetinterval > 0) && ($data['timeviewed'] + $notice->resetinterval < time()))
                || ($data['action'] === 'dismissed' && $notice->reqack == true)) {
                unset($USER->viewednotices[$noticeid]);
            }
        }
        $notices = array_diff_key($notices, $USER->viewednotices);

        // Check if user is in the targeted audience.
        $usernotices = $notices;
        if (!empty($notices)) {
            $usercohort = cohort_get_user_cohorts($USER->id);
            foreach ($notices as $notice) {
                if ($notice->audience > 0 && !array_key_exists($notice->audience, $usercohort)) {
                    unset($usernotices[$notice->id]);
                }
            }
        }

        return $usernotices;
    }

    /**
     * Load viewed notices of current user.
     * @throws \dml_exception
     */
    private static function load_viewed_notices() {
        global $USER;
        $records = noticeview::get_user_viewed_notice_records();
        $USER->viewednotices = [];
        foreach ($records as $record) {
            $USER->viewednotices[$record->id] = ["timeviewed" => $record->timemodified, 'action' => $record->action];
        }
    }

    /**
     * Record the latest interaction with the notice of a user.
     * @param $noticeid notice id
     * @param $action dismissed or acknowledged
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function add_to_viewed_notices($noticeid, $userid, $action) {
        global $USER;
        // Add to viewed notices.
        $noticeview = noticeview::add_notice_view($noticeid, $userid, $action);
        $USER->viewednotices[$noticeid] = ['timeviewed' => $noticeview->get('timemodified'), 'action' => $action];
    }

    /**
     * Dismiss the notice
     * @param $noticeid notice id
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function dismiss_notice($noticeid) {
        global $USER;

        $userid = $USER->id;

        $result = array();
        $notice = sitenotice::get_record(['id' => $noticeid]);
        if ($notice && $notice->get('reqack')) {
            require_logout();
            $loginpage = new \moodle_url("/login/index.php");
            $result['redirecturl'] = $loginpage->out();
        }

        // Log dismissed event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $noticeid,
            'relateduserid' => $userid,
        );
        $event = \local_sitenotice\event\sitenotice_dismissed::create($params);
        $event->trigger();

        $result['status'] = true;
        return $result;
    }

    /**
     * Acknowledge the notice
     * @param $noticeid notice id
     * @return array|bool|int
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function acknowledge_notice($noticeid) {
        global $USER;
        // Acknowledgement Record.
        $notice = sitenotice::get_record(['id' => $noticeid]);

        $data = new \stdClass();
        $data->userid = $USER->id;
        $data->username = $USER->username;
        $data->firstname = $USER->firstname;
        $data->lastname = $USER->lastname;
        $data->idnumber = $USER->idnumber;
        $data->noticeid = $noticeid;
        $data->noticetitle = $notice->get('title');
        $persistent = new acknowledgement(0, $data);
        $persistent = $persistent->create();

        // Log acknowledged event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $noticeid,
            'relateduserid' => $persistent->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_acknowledged::create($params);
        $event->trigger();

        $result = array();
        $result['status'] = true;
        return $result;
    }

    /**
     * Track user interaction with the hyperlink
     * @param $linkid link ID
     * @return array
     * @throws \dml_exception
     */
    public static function track_link($linkid) {
        global $USER;
        $data = new \stdClass();
        $data->hlinkid = $linkid;
        $data->userid = $USER->id;
        $persistent = new linkhistory(0, $data);
        $persistent->create();

        $result = array();
        $result['status'] = true;
        return $result;
    }

    /**
     * Get acknowledgement records based on current filter sql
     * @param $userid user id
     * @param $noticeid notice id
     * @param null $filtersql filter sql
     * @param null $params parameter
     * @return array
     * @throws \dml_exception
     */
    public static function retrieve_acknowlegement($userid, $noticeid, $filtersql = null, $params = null) {
        if (empty($filtersql)) {
            $filtersql = " userid = :userid AND noticeid = :noticeid ";
        } else {
            $filtersql = " $filtersql AND userid = :userid AND noticeid = :noticeid ";
        }
        $params = array_merge($params, ['userid' => $userid, 'noticeid' => $noticeid]);
        return acknowledgement::get_notice_acknowledgement($filtersql, $params);
    }

    /**
     * Hyperlink interaction on a notice.
     * @param $userid user id
     * @param $noticeid notice id
     * @return array
     * @throws \dml_exception
     */
    public static function count_clicked_notice_links($userid, $noticeid) {
        return linkhistory::count_user_notice_clicked_link($userid, $noticeid);
    }

    /**
     * Return links belong to a notice.
     * @param $noticeid
     */
    public static function retrieve_notice_links($noticeid) {
        return noticelink::get_notice_link_records($noticeid);
    }

    /**
     * Format date interval.
     * @param $time
     * @return string
     * @throws \coding_exception
     */
    public static function format_interval_time($time) {
        // Datetime for 01/01/1970.
        $datefrom = new \DateTime("@0");
        // Datetime for 01/01/1970 after the specified time (in seconds).
        $dateto = new \DateTime("@$time");
        // Format the date interval.
        return $datefrom->diff($dateto)->format(get_string('timeformat:resetinterval', 'local_sitenotice'));
    }

    /**
     * Format boolean value
     * @param $value boolean
     * @return string
     * @throws \coding_exception
     */
    public static function format_boolean($value) {
        if ($value) {
            return get_string('booleanformat:true', 'local_sitenotice');
        } else {
            return get_string('booleanformat:false', 'local_sitenotice');
        }
    }

    /**
     * Get audience name from the audience options.
     * @param $audienceid audience id
     * @return mixed
     * @throws \coding_exception
     */
    public static function get_audience_name($audienceid) {
        $audiences = self::built_audience_options();
        return $audiences[$audienceid];
    }
}
