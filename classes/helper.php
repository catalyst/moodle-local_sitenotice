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

use \local_sitenotice\persistent\sitenotice;
use \local_sitenotice\persistent\noticelink;
use \local_sitenotice\persistent\linkhistory;
use \local_sitenotice\persistent\acknowledgement;
use \local_sitenotice\persistent\noticeview;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

/**
 * Helper class to create, retrieve, manage notices
 *
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Perform all required manipulations with content.
     *
     * @param \local_sitenotice\persistent\sitenotice $sitenotice Notice.
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

        $content = self::update_hyperlinks($sitenotice, $content);
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
    public static function create_new_notice(\stdClass $data) {
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
    public static function update_notice(sitenotice $sitenotice, \stdClass $data) {
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
     *
     * @param sitenotice $notice
     * @param string $content notice content
     * @return string
     */
    private static function update_hyperlinks(sitenotice $notice, string $content): string {
        // Replace file URLs before processing.
        $content = file_rewrite_pluginfile_urls($content, 'pluginfile.php',
            \context_system::instance()->id, 'local_sitenotice', 'content', $notice->get('id'));

        // Extract hyperlinks from the content of the notice, which is then used for link clicked tracking.
        $dom = new \DOMDocument();
        $content = format_text($content, FORMAT_HTML, ['noclean' => true]);
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8' );
        $dom->loadHTML($content);
        // Current links in the notice.
        $currentlinks = noticelink::get_notice_link_records($notice->get('id'));
        $newlinks = [];

        foreach ($dom->getElementsByTagName('a') as $node) {
            $link = new \stdClass();
            $link->noticeid = $notice->get('id');
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
     *
     * @param sitenotice $notice
     * @return void
     */
    public static function reset_notice(sitenotice $notice): void {
        self::check_manage_capability();
        try {
            $notice = new sitenotice($notice->get('id'));
            $notice->update();

            // Log reset event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $notice->get('id'),
                'relateduserid' => $notice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_reset::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Enable a notice
     *
     * @param sitenotice $notice
     * @return void
     */
    public static function enable_notice(sitenotice $notice): void {
        self::check_manage_capability();
        try {
            $notice->set('enabled', 1);
            $notice->update();

            // Log enabled event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $notice->get('id'),
                'relateduserid' => $notice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_updated::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Disable a notice
     *
     * @param sitenotice $notice
     * @return void
     */
    public static function disable_notice(sitenotice $notice): void {
        self::check_manage_capability();
        try {
            $notice->set('enabled', 0);
            $notice->update();

            // Log disable event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $notice->get('id'),
                'relateduserid' => $notice->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_updated::create($params);
            $event->trigger();
        } catch (Exception $e) {
            \core\notification::error($e->getMessage());
        }
    }

    /**
     * Delete a notice
     *
     * @param sitenotice $notice
     * @return void
     */
    public static function delete_notice(sitenotice $notice): void {
        self::check_manage_capability();
        if (!get_config('local_sitenotice', 'allow_delete')) {
            return;
        }

        $oldid = $notice->get('id');
        $notice->delete();
        $params = array(
            'context' => \context_system::instance(),
            'objectid' => $oldid,
            'relateduserid' => $notice->get('usermodified'),
        );
        $event = \local_sitenotice\event\sitenotice_deleted::create($params);
        $event->trigger();

        if (!get_config('local_sitenotice', 'cleanup_deleted_notice')) {
            return;
        }
        acknowledgement::delete_notice_acknowledgement($oldid);
        noticeview::delete_notice_view($oldid);
        $noticelinks = noticelink::get_notice_link_records($oldid);
        if (!empty($noticelinks)) {
            linkhistory::delete_link_history(array_keys($noticelinks));
            noticelink::delete_notice_links($oldid);
        }
    }

    /**
     * Built Audience options based on site cohorts.
     * @return array
     * @throws \coding_exception
     */
    public static function built_cohorts_options() {
        $options = [];
        $cohorts = cohort_get_all_cohorts(0, 0);
        foreach ($cohorts['cohorts'] as $cohort) {
            $options[$cohort->id] = $cohort->name;
        }
        return $options;
    }

    /**
     * Get a notice
     *
     * @param int $noticeid notice id
     * @return bool|\stdClass
     */
    public static function retrieve_notice(int $noticeid) {
        $sitenotice = sitenotice::get_record(['id' => $noticeid]);
        if ($sitenotice) {
            return $sitenotice->to_record();
        } else {
            return false;
        }
    }

    /**
     * Retrieve notices applied to user.
     * @return sitenotice[] Array of sitenotice instances
     * @throws \dml_exception
     */
    public static function retrieve_user_notices(): array {
        global $DB, $USER;

        $notices = sitenotice::get_enabled_notices();

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
            $dissmised = $data['action'] == acknowledgement::ACTION_DISMISSED;
            if (
                // Notice has been updated/reset/enabled.
                $data['timeviewed'] < $notice->get('timemodified')
                // The reset interval has been past.
                || (($notice->get('resetinterval') > 0) && ($data['timeviewed'] + $notice->get('resetinterval') < time()))
                // The previous action is 'dismiss', so still require acknowledgement.
                || ($dissmised && $notice->get('reqack') == true)
                // The action is 'dismiss' and forced to be logged out, still show it (admins are special).
                || ($dissmised && $notice->get('forcelogout') == true) && !is_siteadmin()) {
                unset($USER->viewednotices[$noticeid]);
            }
        }
        $notices = array_filter(
            array_diff_key($notices, $USER->viewednotices),
            function (sitenotice $notice): bool {
                $now = time();
                $isperpetual = $notice->get('timestart') == 0 && $notice->get('timeend') == 0;
                $isinactivewindow = $now >= $notice->get('timestart') && $now < $notice->get('timeend');
                return $isperpetual || (!$isperpetual && $isinactivewindow);
            }
        );

        $usernotices = $notices;
        if (!empty($notices)) {
            $checkcohorts = false;
            $checkcompletion = false;

            foreach ($notices as $notice) {
                if (!empty($notice->get('cohorts'))) {
                    $checkcohorts = true;
                }
                if ($notice->get('reqcourse') > 0) {
                    $checkcompletion = true;
                }
            }

            // Filter out notices by cohorts.
            if ($checkcohorts) {
                $usercohorts = cohort_get_user_cohorts($USER->id);
                foreach ($notices as $notice) {
                    $cohorts = $notice->get('cohorts');
                    if (!empty($cohorts) && !array_intersect($cohorts, array_keys($usercohorts))) {
                        unset($usernotices[$notice->get('id')]);
                    }
                }
            }

            // Filter out notices by course completion.
            if ($checkcompletion) {
                foreach ($notices as $notice) {
                    if ($notice->get('reqcourse') > 0) {
                        if ($course = $DB->get_record('course', ['id' => $notice->get('reqcourse')])) {
                            $completion = new \completion_info($course);
                            if ($completion->is_course_complete($USER->id)) {
                                unset($usernotices[$notice->get('id')]);
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
     *
     * @param \local_sitenotice\persistent\sitenotice $notice Notice instance.
     * @param string $action Action.
     */
    private static function add_to_viewed_notices(sitenotice $notice, string $action) {
        global $USER;
        // Add to viewed notices.
        $noticeview = noticeview::add_notice_view($notice->get('id'), $USER->id, $action);
        $USER->viewednotices[$notice->get('id')] = ['timeviewed' => $noticeview->get('timemodified'), 'action' => $action];
    }

    /**
     * Create new acknowledgement record.
     *
     * @param sitenotice $notice
     * @param string $action dismissed or acknowledged
     *
     * @return \core\persistent
     */
    private static function create_new_acknowledge_record(sitenotice $notice, string $action) {
        global $USER;

        // New record.
        $data = new \stdClass();
        $data->userid = $USER->id;
        $data->username = $USER->username;
        $data->firstname = $USER->firstname;
        $data->lastname = $USER->lastname;
        $data->idnumber = $USER->idnumber;
        $data->noticeid = $notice->get('id');
        $data->noticetitle = $notice->get('title');
        $data->action = $action;
        $persistent = new acknowledgement(0, $data);
        return $persistent->create();
    }

    /**
     * Dismiss the notice
     *
     * @param sitenotice $notice
     * @return array
     */
    public static function dismiss_notice(sitenotice $notice): array {
        global $USER;

        $userid = $USER->id;

        $result = array();
        // Check if require acknowledgement.
        if ($notice->get('reqack')) {
            // Record dismiss action.
            self::create_new_acknowledge_record($notice, acknowledgement::ACTION_DISMISSED);

            // Log dismissed event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $notice->get('id'),
                'relateduserid' => $userid,
            );
            $event = \local_sitenotice\event\sitenotice_dismissed::create($params);
            $event->trigger();
        }

        // Mark notice as viewed.
        self::add_to_viewed_notices($notice, acknowledgement::ACTION_DISMISSED);

        if ((!is_siteadmin() && $notice->get('forcelogout')) || $notice->get('reqack')) {
            require_logout();
            $loginpage = new \moodle_url("/login/index.php");
            $result['redirecturl'] = $loginpage->out();
        }

        $result['status'] = true;
        return $result;
    }

    /**
     * Acknowledge the notice.
     *
     * @param sitenotice $notice
     * @return array
     */
    public static function acknowledge_notice(sitenotice $notice): array {
        global $USER;

        $result = ['status' => true];
        // Check if the notice has been acknowledged by the user in another browser.
        if (self::check_if_already_acknowledged_by_user($notice, $USER->id)) {
            return $result;
        }

        // Record Acknowledge action.
        $persistent = self::create_new_acknowledge_record($notice, acknowledgement::ACTION_ACKNOWLEDGED);
        if ($persistent) {
            // Mark notice as viewed.
            self::add_to_viewed_notices($notice, acknowledgement::ACTION_ACKNOWLEDGED);
            // Log acknowledged event.
            $params = array(
                'context' => \context_system::instance(),
                'objectid' => $notice->get('id'),
                'relateduserid' => $persistent->get('usermodified'),
            );
            $event = \local_sitenotice\event\sitenotice_acknowledged::create($params);
            $event->trigger();
        } else {
            $result['status'] = false;
        }

        if (!is_siteadmin() && $notice->get('forcelogout')) {
            require_logout();
            $loginpage = new \moodle_url("/login/index.php");
            $result['redirecturl'] = $loginpage->out();
        }

        return $result;
    }

    /**
     * Track user interaction with the hyperlink
     * @param int $linkid link ID
     * @return array
     */
    public static function track_link(int $linkid) {
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
     * Format date interval.
     *
     * @param string $time Time.
     * @return string
     */
    public static function format_interval_time(string $time) {
        // Datetime for 01/01/1970.
        $datefrom = new \DateTime("@0");
        // Datetime for 01/01/1970 after the specified time (in seconds).
        $dateto = new \DateTime("@$time");
        // Format the date interval.
        return $datefrom->diff($dateto)->format(get_string('timeformat:resetinterval', 'local_sitenotice'));
    }

    /**
     * Format boolean value
     *
     * @param bool $value boolean
     * @return string
     */
    public static function format_boolean(bool $value) {
        if ($value) {
            return get_string('booleanformat:true', 'local_sitenotice');
        } else {
            return get_string('booleanformat:false', 'local_sitenotice');
        }
    }

    /**
     * Get audience name from the audience options.
     *
     * @param int $cohortid Cohort id
     * @return mixed
     */
    public static function get_cohort_name(int $cohortid) {
        if ($cohortid == 0) {
            return get_string('notice:cohort:all', 'local_sitenotice');
        }

        $cohorts = self::built_cohorts_options();
        return $cohorts[$cohortid];
    }

    /**
     * Get course name
     * @param int $courseid course id
     * @return mixed
     * @throws \coding_exception
     */
    public static function get_course_name(int $courseid) {
        global $DB;

        if ($courseid == 0) {
            return get_string('booleanformat:false', 'local_sitenotice');
        }

        $course = $DB->get_record('course', array('id' => $courseid));
        if ($course) {
            return $course->fullname;
        } else {
            return '-';
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

    /**
     * Check if notice has already been acknowledged by a user.
     *
     * @param sitenotice $notice
     * @param int $userid
     *
     * @return bool
     */
    private static function check_if_already_acknowledged_by_user(sitenotice $notice, int $userid): bool {
        global $USER;
        $latestview = noticeview::get_record(['noticeid' => $notice->get('id'), 'userid' => $userid]);
        if (empty($latestview)) {
            return false;
        }

        $latestview = $latestview->to_record();
        $notice = $notice->to_record();
        if (
            // Notice has been updated/reset/enabled.
            $latestview->timemodified < $notice->timemodified
            // The reset interval has been past.
            || (($notice->resetinterval > 0) && ($latestview->timemodified + $notice->resetinterval < time()))
            // The previous action is 'dismiss', so still require acknowledgement.
            || ($latestview->action == acknowledgement::ACTION_DISMISSED && $notice->reqack == true)) {
            return false;
        }
        $USER->viewednotices[$notice->id] = ['timeviewed' => $latestview->timemodified, 'action' => $latestview->action];
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
