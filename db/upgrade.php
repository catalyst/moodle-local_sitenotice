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
    global $CFG, $DB;

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

    return true;

}
