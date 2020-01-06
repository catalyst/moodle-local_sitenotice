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
namespace local_sitenotice\persistent;
use core\persistent;

defined('MOODLE_INTERNAL') || die();

class sitenotice extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'local_sitenotice';

    /**
     * @inheritdoc
     */
    protected static function define_properties() {
        return [
            'title' => [
                'type' => PARAM_RAW_TRIMMED,
                'null' => NULL_NOT_ALLOWED,
            ],
            'content' => [
                'type' => PARAM_RAW,
                'null' => NULL_NOT_ALLOWED,
            ],
            'audience' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'reqack' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
            'enabled' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 1,
            ],
            'resetinterval' => [
                'type' => PARAM_INT,
                'null' => NULL_NOT_ALLOWED,
                'default' => 0,
            ],
        ];
    }

    /**
     * Enable a notice.
     * @param $noticeid notice id
     * @return sitenotice
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function enable($noticeid) {
        $persistent = new sitenotice($noticeid);
        $persistent->set('enabled', 1);
        $persistent->update();
        return $persistent;
    }

    /**
     * Disable a notice.
     * @param $noticeid notice id
     * @return sitenotice
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function disable($noticeid) {
        $persistent = new sitenotice($noticeid);
        $persistent->set('enabled', 0);
        $persistent->update();
        return $persistent;
    }

    /**
     * Reset a notice.
     * @param $noticeid notice id
     * @return sitenotice
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function reset($noticeid) {
        $persistent = new sitenotice($noticeid);
        $persistent->update();
        return $persistent;
    }

    /**
     * Get enabled notices
     * @param string $sort field to sort
     * @param string $order sort order
     * @return persistent[]
     */
    public static function get_enabled_notices($sort = 'id', $order = 'ASC') {
        $persistents = self::get_records(['enabled' => 1], $sort, $order);
        $result = [];
        foreach ($persistents as $persistent) {
            $record = $persistent->to_record();
            $result[$record->id] = $record;
        }
        return $result;
    }

    /**
     * Get all notices
     * @param string $sort field to sort
     * @param string $order sort order
     * @return persistent[]
     */
    public static function get_all_notice_records($sort = 'timemodified', $order = 'DESC') {
        $persistents = self::get_records([], $sort, $order);
        $result = [];
        foreach ($persistents as $persistent) {
            $record = $persistent->to_record();
            $result[$record->id] = $record;
        }
        return $result;
    }

    /**
     * Create new notice
     * @param $data
     * @return persistent
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function create_new_notice($data) {
        $persistent = new self(0, $data);
        return $persistent->create();
    }

    /**
     * Update content of the notice
     * @param sitenotice $persistent site notice persistent object
     * @param $content new content
     * @return bool
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function update_notice_content(sitenotice $persistent, $content) {
        $persistent->set('content', $content);
        return $persistent->update();
    }

    /**
     * Update data of the notice
     * @param sitenotice $persistent site notice persistent object
     * @param $data new data
     * @return bool
     * @throws \coding_exception
     * @throws \core\invalid_persistent_exception
     */
    public static function update_notice_data(sitenotice $persistent, $data) {
        $persistent->from_record($data);
        return $persistent->update();
    }
}
