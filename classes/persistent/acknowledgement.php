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

namespace local_sitenotice\persistent;

use core\persistent;

/**
 * Acknowledgement class.
 *
 * @package    local_sitenotice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class acknowledgement extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'local_sitenotice_ack';

    /** Dismiss Action */
    const ACTION_DISMISSED = 0;

    /** Acknowledge Action */
    const ACTION_ACKNOWLEDGED = 1;

    /**
     * Returns a list of properties.
     * @return array[]
     */
    protected static function define_properties() {
        return [
            'userid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'noticeid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'username' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'firstname' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
            ],
            'lastname' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
            ],
            'idnumber' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_ALLOWED,
            ],
            'noticetitle' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'action' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => self::ACTION_DISMISSED,
            ],
        ];
    }

    /**
     * Delete acknowledgement related to a notice.
     *
     * @param int $noticeid notice id
     */
    public static function delete_notice_acknowledgement(int $noticeid) {
        global $DB;
        $DB->delete_records(static::TABLE, ['noticeid' => $noticeid]);
    }

}
