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
 * @package package
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitenotice;

defined('MOODLE_INTERNAL') || die();

class eventobservers {
    public static function sitenotice_created(\local_sitenotice\event\sitenotice_created $event) {
        global $DB, $USER;
        $tmp = $USER;
    }

    public static function sitenotice_updated(\local_sitenotice\event\sitenotice_updated $event) {
        global $DB, $USER;
        $tmp = $USER;

    }

    public static function sitenotice_dismissed(\local_sitenotice\event\sitenotice_dismissed $event) {
        global $DB, $USER;
        $tmp = $USER;
    }

    public static function sitenotice_acknowledged(\local_sitenotice\event\sitenotice_acknowledged $event) {
        global $DB, $USER;
        $tmp = $USER;
    }
}