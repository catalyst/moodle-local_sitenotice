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
 * Notice Updated event
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sitenotice\event;

defined('MOODLE_INTERNAL') || die();

class sitenotice_reset extends \core\event\base {

    protected function init() {
        $this->data['objecttable'] = 'local_sitenotice';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public function get_description() {
        return "The user with id '$this->relateduserid' reset the notice with id '$this->objectid'";
    }

    public static function get_name() {
        return get_string('event:reset', 'local_sitenotice');
    }

    public function get_url() {
        return new \moodle_url('/local/sitenotice/editnotice.php',
            array('noticeid' => $this->objectid, 'action' => 'view', 'sesskey' => sesskey()));
    }
}