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
require_once($CFG->dirroot.'/lib/completionlib.php');

use \local_sitenotice\persistent\sitenotice;
use \local_sitenotice\persistent\noticelink;
use \local_sitenotice\persistent\linkhistory;
use \local_sitenotice\persistent\acknowledgement;
use \local_sitenotice\persistent\noticeview;

class helper {

    /**
     * Perform all required manipulations with content.
     *
     * @param \local_sitenotice\persistent\sitenotice|\core\persistent $sitenotice
     */
    public static function process_content(sitenotice $sitenotice) {
        $draftitemid = file_get_submitted_draft_itemid('content');
        $content = file_save_draft_area_files(
            $draftitemid,
            \context_system::instance()->id,
            'local_sitenotice', 'content',
            $sitenotice->get('id'),
            self::get_file_editor_options(),
            $sitenotice->get('content')
        );

        $content = self::update_hyperlinks($sitenotice->get('id'), $content);
        $sitenotice->set('content', $content);
    }

    /**
     * Create new notice
     * @param \stdClass $data form data
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     * @throws \required_capability_exception
     */
    public static function create_new_notice($data) {
        self::check_manage_capability();
        // Create new notice.
        self::sanitise_data($data);
        $sitenotice = sitenotice::create_new_notice($data);

        self::process_content($sitenotice);
        sitenotice::update_notice_content($sitenotice, $sitenotice->get('content'));

        // Log created event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $sitenotice->get('id'),
            'relateduserid' => $sitenotice->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_created::create($params);
        $event->trigger();
    }

    /**
     * Update existing notice.
     * @param sitenotice $sitenotice site notice persistent
     * @param \stdClass $data form data
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     * @throws \required_capability_exception
     */
    public static function update_notice(sitenotice $sitenotice, $data) {
        self::check_manage_capability();
        if (!get_config('local_sitenotice', 'allow_update')) {
            return;
        }

        self::sanitise_data($data);
        sitenotice::update_notice_data($sitenotice, $data);

        self::process_content($sitenotice);
        sitenotice::update_notice_content($sitenotice, $sitenotice->get('content'));

        // Log updated event.
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $sitenotice->get('id'),
            'relateduserid' => $sitenotice->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_updated::create($params);
        $event->trigger();
    }

    /**
     * Sanitise submitted data before creating or updating a site notice.
     *
     * @param \stdClass $data
     */
    private static function sanitise_data(\stdClass $data) {
        foreach ((array)$data as $key => $value) {
            if (!key_exists($key, sitenotice::properties_definition())) {
                unset($data->$key);
            }
        }
    }

    /**
     * Extract hyperlink from notice content.
     * @param int $noticeid notice id
     * @param string $content notice content
     * @return string
     */
    private static function update_hyperlinks($noticeid, $content) {
        // Replace file URLs before processing.
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php',
            \context_system::instance()->id, 'local_sitenotice', 'content', $noticeid);

        // Extract hyperlinks from the content of the notice, which is then used for link clicked tracking.
        $dom = new \DOMDocument();
        $content = format_text($content, FORMAT_HTML, ['noclean' => true]);
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
     * @throws \required_capability_exception
     */
    public static function reset_notice($noticeid) {
        self::check_manage_capability();
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
     * @throws \required_capability_exception
     */
    public static function enable_notice($noticeid) {
        self::check_manage_capability();
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
     * @throws \required_capability_exception
     */
    public static function disable_notice($noticeid) {
        self::check_manage_capability();
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
     * @throws \required_capability_exception
     */
    public static function delete_notice($noticeid) {
        self::check_manage_capability();
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
        $cohorts = cohort_get_all_cohorts(0, 0);
        foreach ($cohorts['cohorts'] as $cohort) {
            $option[$cohort->id] = $cohort->name;
        }
        return $option;
    }

    /**
     * Get a notice
     * @param $noticeid notice id
     * @return bool|\stdClass
     */
    public static function retrieve_notice($noticeid) {
        $sitenotice = sitenotice::get_record(['id' => $noticeid]);
        if ($sitenotice) {
            return $sitenotice->to_record();
        } else {
            return false;
        }
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
        global $DB, $USER;

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
            if (
                // Notice has been updated/reset/enabled.
                $data['timeviewed'] < $notice->timemodified
                // The reset interval has been past.
                || (($notice->resetinterval > 0) && ($data['timeviewed'] + $notice->resetinterval < time()))
                // The previous action is 'dismiss', so still require acknowledgement.
                || ($data['action'] === acknowledgement::ACTION_DISMISSED && $notice->reqack == true)) {
                unset($USER->viewednotices[$noticeid]);
            }
        }
        $notices = array_filter(
            array_diff_key($notices, $USER->viewednotices),
            function (\stdClass $notice): bool {
                $now = time();
                $isperpetual = $notice->timestart == 0 && $notice->timeend == 0;
                $isinactivewindow = $now >= $notice->timestart && $now < $notice->timeend;
                return $isperpetual || (!$isperpetual && $isinactivewindow);
            }
        );

        $usernotices = $notices;
        if (!empty($notices)) {
            $checkaudiences = false;
            $checkcompletion = false;

            foreach ($notices as $notice) {
                if ($notice->audience > 0) {
                    $checkaudiences = true;
                }
                if ($notice->reqcourse > 0) {
                    $checkcompletion = true;
                }
            }

            // Filter out notices by cohorts.
            if ($checkaudiences) {
                $usercohorts = cohort_get_user_cohorts($USER->id);
                foreach ($notices as $notice) {
                    if ($notice->audience > 0 && !array_key_exists($notice->audience, $usercohorts)) {
                        unset($usernotices[$notice->id]);
                    }
                }
            }

            // Filter out notices by course completion.
            if ($checkcompletion) {
                foreach ($notices as $notice) {
                    if ($notice->reqcourse > 0) {
                        if ($course = $DB->get_record('course', ['id' => $notice->reqcourse])) {
                            $completion = new \completion_info($course);
                            if ($completion->is_course_complete($USER->id)) {
                                unset($usernotices[$notice->id]);
                            }
                        }
                    }
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
    private static function add_to_viewed_notices($noticeid, $action) {
        global $USER;
        // Add to viewed notices.
        $noticeview = noticeview::add_notice_view($noticeid, $USER->id, $action);
        $USER->viewednotices[$noticeid] = ['timeviewed' => $noticeview->get('timemodified'), 'action' => $action];
    }

    /**
     * Create new acknowledgement record.
     * @param $noticeid notice id
     * @param $action dismissed or acknowledged
     * @return \core\persistent
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    private static function create_new_acknowledge_record($noticeid, $action) {
        global $USER;
        $notice = sitenotice::get_record(['id' => $noticeid]);

        // New record.
        $data = new \stdClass();
        $data->userid = $USER->id;
        $data->username = $USER->username;
        $data->firstname = $USER->firstname;
        $data->lastname = $USER->lastname;
        $data->idnumber = $USER->idnumber;
        $data->noticeid = $noticeid;
        $data->noticetitle = $notice->get('title');
        $data->action = $action;
        $persistent = new acknowledgement(0, $data);
        return $persistent->create();
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
        // Check if require acknowledgement.
        if ($notice && $notice->get('reqack')) {
            // Record dismiss action.
            self::create_new_acknowledge_record($noticeid, acknowledgement::ACTION_DISMISSED);

            // Log user out.
            require_logout();
            $loginpage = new \moodle_url("/login/index.php");
            $result['redirecturl'] = $loginpage->out();

            // Log dismissed event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $noticeid,
                'relateduserid' => $userid,
            );
            $event = \local_sitenotice\event\sitenotice_dismissed::create($params);
            $event->trigger();
        }

        // Mark notice as viewed.
        self::add_to_viewed_notices($noticeid, acknowledgement::ACTION_DISMISSED);

        $result['status'] = true;
        return $result;
    }

    /**
     * Acknowledge the notice.
     * @param $noticeid notice id
     * @return array|bool|int
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function acknowledge_notice($noticeid) {
        global $USER;
        // Check if the notice has been acknowledged by the user in another browser.
        if (self::check_if_already_acknowledged_by_user($noticeid, $USER->id)) {
            return;
        }

        $result = array();
        // Record Acknowledge action.
        $persistent = self::create_new_acknowledge_record($noticeid, acknowledgement::ACTION_ACKNOWLEDGED);
        if ($persistent) {
            // Mark notice as viewed.
            self::add_to_viewed_notices($noticeid, acknowledgement::ACTION_ACKNOWLEDGED);
            // Log acknowledged event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $noticeid,
                'relateduserid' => $persistent->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_acknowledged::create($params);
            $event->trigger();
            $result['status'] = true;
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Track user interaction with the hyperlink
     * @param $linkid link ID
     * @return array
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
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
     * Hyperlink interaction on a notice.
     * @param $userid user id
     * @param $noticeid notice id
     * @param int $linkid hyperlink  id
     * @return array
     * @throws \dml_exception
     */
    public static function count_clicked_notice_links($userid, $noticeid, $linkid = 0) {
        return linkhistory::count_clicked_links($userid, $noticeid, $linkid);
    }

    /**
     * Return links belong to a notice.
     * @param $noticeid
     * @return array
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

    /**
     * Get course name
     * @param int $courseid course id
     * @return mixed
     * @throws \coding_exception
     */
    public static function get_course_name($courseid) {
        global $DB;

        if ($courseid == 0) {
            return 'No';
        }

        $course = $DB->get_record('course', array('id' => $courseid));
        if ($course) {
            return $course->fullname;
        } else {
            return '--';
        }
    }

    /**
     * Check capability.
     * @throws \required_capability_exception
     * @throws \dml_exception
     */
    public static function check_manage_capability() {
        $syscontext = \context_system::instance();
        require_capability('local/sitenotice:manage', $syscontext);
    }

    private static function check_if_already_acknowledged_by_user($noticeid, $userid) {
        global $USER;
        $latestview = noticeview::get_record(['noticeid' => $noticeid, 'userid' => $userid]);
        if (empty($latestview)) {
            return false;
        }

        $notice = sitenotice::get_record(['id' => $noticeid]);

        $latestview = $latestview->to_record();
        $notice = $notice->to_record();
        if (
            // Notice has been updated/reset/enabled.
            $latestview->timemodified < $notice->timemodified
            // The reset interval has been past.
            || (($notice->resetinterval > 0) && ($latestview->timemodified + $notice->resetinterval < time()))
            // The previous action is 'dismiss', so still require acknowledgement.
            || ($latestview->action === acknowledgement::ACTION_DISMISSED && $notice->reqack == true)) {
            return false;
        }
        $USER->viewednotices[$noticeid] = ['timeviewed' => $latestview->timemodified, 'action' => $latestview->action];
        return true;
    }

    /**
     * Return options for file editor.
     * @return array
     */
    public static function get_file_editor_options(): array {
        global $CFG;

        return [
            'subdirs' => true,
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => -1, // Unlimited files.
            'context' => \context_system::instance(),
            'trusttext' => true,
            'class' => 'noticecontent'
        ];
    }
}
