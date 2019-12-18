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

use local_sitenotice\helper;

class eventobservers {
    public static function sitenotice_created(\local_sitenotice\event\sitenotice_created $event) {

    }

    public static function sitenotice_updated(\local_sitenotice\event\sitenotice_updated $event) {

    }

    public static function sitenotice_dismissed(\local_sitenotice\event\sitenotice_dismissed $event) {
        $noticeid = $event->get_data()['objectid'];
        $userid = $event->get_data()['relateduserid'];
        $action = $event->get_data()['action'];
        helper::add_to_viewed_noticed($noticeid, $userid, $action);
    }

    public static function sitenotice_acknowledged(\local_sitenotice\event\sitenotice_acknowledged $event) {
        $noticeid = $event->get_data()['objectid'];
        $userid = $event->get_data()['relateduserid'];
        $action = $event->get_data()['action'];
        helper::add_to_viewed_noticed($noticeid, $userid, $action);
    }
}