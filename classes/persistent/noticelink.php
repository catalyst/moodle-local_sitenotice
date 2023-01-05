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
 * Notice link class.
 *
 * @package    local_sitenotice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class noticelink extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'local_sitenotice_hlinks';

    /**
     * Returns a list of properties.
     * @return array[]
     */
    protected static function define_properties() {
        return [
            'text' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'link' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'noticeid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }

    /**
     * Get links belong to the notice
     *
     * @param int $noticeid notice ID
     * @param string $sort field to sort
     * @param string $order sort order
     *
     * @return array
     */
    public static function get_notice_link_records($noticeid, $sort = 'id', $order = 'ASC') {
        $persistents = self::get_records(['noticeid' => $noticeid], $sort, $order);
        $result = [];
        foreach ($persistents as $persistent) {
            $record = $persistent->to_record();
            $result[$record->id] = $record;
        }
        return $result;
    }

    /**
     * Delete a list of hyperlinks.
     *
     * @param array $linkids array of link ids
     */
    public static function delete_links($linkids) {
        global $DB;
        if (!empty($linkids)) {
            list($linkidssql, $param) = $DB->get_in_or_equal($linkids, SQL_PARAMS_NAMED);
            $DB->delete_records_select(static::TABLE, " id $linkidssql", $param);
        }
    }

    /**
     * Delete links belong to a notice.
     *
     * @param int $noticeid notice id
     */
    public static function delete_notice_links($noticeid) {
        global $DB;
        $DB->delete_records(static::TABLE, ['noticeid' => $noticeid]);
    }

    /**
     * Create new link
     *
     * @param \stdClass $data link data
     * @return persistent
     */
    public static function create_new_link($data) {
        $linkpersistent = self::get_record([
            'noticeid' => $data->noticeid,
            'text' => $data->text,
            'link' => $data->link]);
        if (empty($linkpersistent)) {
            // Create new link.
            $persistent = new noticelink(0, $data);
            return $persistent->create();
        } else {
            // Reuse old link.
            return $linkpersistent;
        }

    }
}
