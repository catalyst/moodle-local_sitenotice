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
 * Site notice class.
 *
 * @package    local_sitenotice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
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
            'contentformat' => array(
                'type' => PARAM_INT,
                'default' => FORMAT_HTML
            ),
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
     * Get cache instance.
     *
     * @return \cache
     */
    private static function get_enabled_notices_cache(): \cache {
        return \cache::make('local_sitenotice', 'enabled_notices');
    }

    /**
     * Purge related caches.
     */
    protected function purge_caches() {
        self::get_enabled_notices_cache()->purge();
    }

    /**
     * Run after update.
     *
     * @param bool $result Result of update.
     */
    protected function after_update($result) {
        if ($result) {
            self::purge_caches();
        }
    }

    /**
     * Run after created.
     */
    protected function after_create() {
        self::purge_caches();
    }

    /**
     * Run after deleted.
     *
     * @param bool $result Result of delete.
     */
    protected function after_delete($result) {
        self::purge_caches();
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
     * Get enabled notices.
     *
     * @return \stdClass[]
     */
    public static function get_enabled_notices(): array {
        if (!$result = self::get_enabled_notices_cache()->get('records')) {
            $persistents = self::get_records(['enabled' => 1], 'id');
            $result = [];
            foreach ($persistents as $persistent) {
                $record = $persistent->to_record();
                $result[$record->id] = $record;
            }

            self::get_enabled_notices_cache()->set('records', $result);
        }

        return $result;
    }

    /**
     * Get all notices
     *
     * @return \stdClass[]
     */
    public static function get_all_notice_records(): array {
        $persistents = self::get_records([], 'timemodified', 'DESC');
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
