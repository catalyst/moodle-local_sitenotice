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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category('sitenotice', get_string('pluginname', 'local_sitenotice')));

    $temp = new admin_settingpage('sitenoticesettings',
        new lang_string('setting:settings', 'local_sitenotice'));

    $temp->add(new admin_setting_configcheckbox('local_sitenotice/enabled',
        new lang_string('setting:enabled', 'local_sitenotice'),
        new lang_string('setting:enableddesc', 'local_sitenotice'), 0));

    $ADMIN->add('sitenotice', $temp);

    $managenotice = new admin_externalpage('local_sitenotice_managenotice',
        get_string('setting:managenotice', 'local_sitenotice', null, true),
        new moodle_url('/local/sitenotice/managenotice.php'));

    $ADMIN->add('sitenotice', $managenotice);

    $settings = null;
}