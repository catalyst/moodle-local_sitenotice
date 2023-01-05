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
 * Links history class.
 *
 * @package    local_sitenotice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class linkhistory extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'local_sitenotice_hlinks_his';

    /**
     * Returns a list of properties.
     * @return array[]
     */
    protected static function define_properties() {
        return [
            'hlinkid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
            'userid' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
            ],
        ];
    }

    /**
     * Delete history of the links
     *
     * @param array $linkids array of link ids
     */
    public static function delete_link_history($linkids) {
        global $DB;
        if (!empty($linkids)) {
            list($linkidssql, $param) = $DB->get_in_or_equal($linkids, SQL_PARAMS_NAMED);
            $DB->delete_records_select(static::TABLE, " hlinkid $linkidssql", $param);
        }
    }

    /**
     * Count how many time a user click on a link.
     *
     * @param int $userid user ID.
     * @param int $noticeid notice ID.
     * @param int $linkid Link id.
     *
     * @return array
     */
    public static function count_clicked_links($userid, $noticeid, $linkid = 0) {
        global $DB;
        $params = [];
        if ($linkid > 0) {
            $wheresql = "WHERE h.userid = :userid AND l.noticeid = :noticeid AND h.hlinkid = :hlinkid";
            $params = ['hlinkid' => $linkid];
        } else {
            $wheresql = "WHERE h.userid = :userid AND l.noticeid = :noticeid";
        }
        $sql = "SELECT h.hlinkid, l.text, l.link, COUNT(h.hlinkid)
                  FROM {local_sitenotice_hlinks_his} h
                  JOIN {local_sitenotice_hlinks} l on h.hlinkid = l.id
                  $wheresql
              GROUP BY h.hlinkid, l.text, l.link";

        $params = array_merge($params, ['userid' => $userid, 'noticeid' => $noticeid]);
        return $DB->get_records_sql($sql, $params);
    }
}
