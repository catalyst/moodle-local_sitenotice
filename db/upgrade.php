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
 * Upgrade code.
 *
 * @package    local_sitenotice
 * @author     Jwalit Shah <jwalitshah@catalyst-au.net>
 * @copyright  2021 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade local_sitenotice.
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_sitenotice_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021021300) {

        if (!$dbman->field_exists('local_sitenotice', 'reqcourse')) {

            $table = new xmldb_table('local_sitenotice');
            $field = new xmldb_field('reqcourse', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021021300, 'local', 'sitenotice');
    }

    if ($oldversion < 2022061300) {

        if (!$dbman->field_exists('local_sitenotice', 'timestart')) {

            $table = new xmldb_table('local_sitenotice');
            $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
        }

        if (!$dbman->field_exists('local_sitenotice', 'timeend')) {

            $table = new xmldb_table('local_sitenotice');
            $field = new xmldb_field('timeend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022061300, 'local', 'sitenotice');
    }

    if ($oldversion < 2022062000) {

        if (!$dbman->field_exists('local_sitenotice', 'forcelogout')) {

            $table = new xmldb_table('local_sitenotice');
            $field = new xmldb_field('forcelogout', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022062000, 'local', 'sitenotice');
    }

    if ($oldversion < 2022071200) {
        $notices = $DB->get_records('local_sitenotice');

        $table = new xmldb_table('local_sitenotice');

        // Define field cohorts to be added to local_sitenotice.
        $field = new xmldb_field('cohorts', XMLDB_TYPE_TEXT, null, null, null, null, null, 'content');

        // Conditionally launch add field audience.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Populate with old values.
        if (!empty($notices)) {
            foreach ($notices as $notice) {
                $notice->cohorts = !empty($notice->audience) ? $notice->audience : '';
                $DB->update_record('local_sitenotice', $notice);
            }
        }

        // Define index en_au (not unique) to be dropped form local_sitenotice.
        $index = new xmldb_index('en_au', XMLDB_INDEX_NOTUNIQUE, ['enabled', 'audience']);

        // Conditionally launch drop index en_au.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define field audience to be dropped from local_sitenotice.
        $field = new xmldb_field('audience');

        // Conditionally launch drop field audience.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Sitenotice savepoint reached.
        upgrade_plugin_savepoint(true, 2022071200, 'local', 'sitenotice');
    }

    if ($oldversion < 2022071201) {
        $table = new xmldb_table('local_sitenotice');
        if ($dbman->field_exists('local_sitenotice', 'timestart')) {
            $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->change_field_default($table, $field);
        }

        if ($dbman->field_exists('local_sitenotice', 'timeend')) {
            $field = new xmldb_field('timeend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $dbman->change_field_default($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022071201, 'local', 'sitenotice');
    }

    return true;
}
