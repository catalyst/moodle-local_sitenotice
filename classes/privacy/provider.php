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
 * Privacy Subsystem implementation.
 *
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitenotice\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\context;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * @inheritDoc
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT DISTINCT c.id
                  FROM {local_sitenotice_lastview} lw
                  JOIN {context} c ON c.instanceid = lw.userid AND c.contextlevel = :contextuser
                 WHERE lw.userid = :userid";

        $params = [
            'contextuser'   => CONTEXT_USER,
            'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * @inheritDoc
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            $user = $contextlist->get_user();

            $sql1 = "SELECT lv.*
                       FROM {local_sitenotice_lastview} lv
                      WHERE lv.userid = :userid";

            $sql2 = "SELECT ack.*
                       FROM {local_sitenotice_ack} ack
                      WHERE ack.userid = :userid";

            $sql3 = "SELECT his.*
                       FROM {local_sitenotice_hlinks_his} his
                      WHERE his.userid = :userid";

            $params = [
                'userid' => $user->id,
            ];

            $lastview = $DB->get_records_sql($sql1, $params);
            $acknowlegement = $DB->get_records_sql($sql2, $params);
            $linktracking = $DB->get_records_sql($sql3, $params);

            $data = (object)[
                'lastview' => $lastview,
                'acknowledgement' => $acknowlegement,
                'linktracking' => $linktracking,
            ];

            $subcontext = [
                get_string('pluginname', 'local_sitenotice'),
            ];

            writer::with_context($context)->export_data($subcontext, $data);
        }

    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $userid = $context->instanceid;

        $DB->delete_records('local_sitenotice_lastview', ['userid' => $userid]);
        $DB->delete_records('local_sitenotice_hlinks_his', ['userid' => $userid]);
        $DB->delete_records('local_sitenotice_ack', ['userid' => $userid]);
    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $contexts = $contextlist->get_contexts();
        if (count($contexts) == 0) {
            return;
        }
        $context = reset($contexts);

        if ($context->contextlevel !== CONTEXT_USER) {
            return;
        }
        $userid = $context->instanceid;

        $DB->delete_records('local_sitenotice_lastview', ['userid' => $userid]);
        $DB->delete_records('local_sitenotice_hlinks_his', ['userid' => $userid]);
        $DB->delete_records('local_sitenotice_ack', ['userid' => $userid]);
    }

    /**
     * @inheritDoc
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_user) {
            return;
        }

        $params = ['contextid' => $context->id, 'contextlevel' => CONTEXT_USER];

        $sql = "SELECT lv.userid
                  FROM {local_sitenotice_lastview} lv
                  JOIN {context} c ON c.contextlevel = :contextlevel
                   AND c.instanceid = lv.userid
                 WHERE c.id = :contextid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * @inheritDoc
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context instanceof \context_user) {
            $userid = $context->instanceid;
            $DB->delete_records('local_sitenotice_lastview', ['userid' => $userid]);
            $DB->delete_records('local_sitenotice_hlinks_his', ['userid' => $userid]);
            $DB->delete_records('local_sitenotice_ack', ['userid' => $userid]);
        }
    }

    /**
     * @inheritDoc
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_sitenotice_ack', [
            'userid' => 'privacy:metadata:userid',
            'userid' => 'privacy:metadata:username',
            'userid' => 'privacy:metadata:firstname',
            'userid' => 'privacy:metadata:lastname',
            'userid' => 'privacy:metadata:idnumber',
        ],
            'privacy:metadata:local_sitenotice_ack'
        );

        $collection->add_database_table(
            'local_sitenotice_hlinks_his', [
            'userid' => 'privacy:metadata:userid',
        ],
            'privacy:metadata:local_sitenotice_hlinks_his'
        );

        $collection->add_database_table(
            'local_sitenotice_lastview', [
            'userid' => 'privacy:metadata:userid',
        ],
            'privacy:metadata:local_sitenotice_lastview'
        );

        return $collection;
    }
}
