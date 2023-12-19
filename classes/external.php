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

use local_sitenotice\helper;
use local_sitenotice\persistent\sitenotice;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Webservice functions
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_sitenotice_external extends external_api {

    /**
     * Parameters.
     *
     * @return \external_function_parameters
     */
    public static function dismiss_notice_parameters() {
        return new external_function_parameters (
            array(
                'noticeid' => new external_value(PARAM_INT, 'notice id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Dismisses notice.
     *
     * @param int $noticeid Notice ID.
     * @return array
     */
    public static function dismiss_notice($noticeid) {
        $params = self::validate_parameters(self::dismiss_notice_parameters(),
            array('noticeid' => $noticeid));

        $result = [
            'status' => 0,
            'redirecturl' => '',
        ];

        if ($notice = sitenotice::get_record(['id' => $params['noticeid']])) {
            $result = helper::dismiss_notice($notice);
        }

        return $result;
    }

    /**
     * Return parameters.
     *
     * @return \external_single_structure
     */
    public static function dismiss_notice_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'redirecturl' => new external_value(PARAM_TEXT, 'redirect url', VALUE_DEFAULT, ""),
            )
        );
    }

    /**
     * Parameters.
     *
     * @return \external_function_parameters
     */
    public static function acknowledge_notice_parameters() {
        return new external_function_parameters (
            array(
                'noticeid' => new external_value(PARAM_INT, 'notice id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Acknowledge notice.
     *
     * @param int $noticeid Notice ID.
     * @return []
     */
    public static function acknowledge_notice($noticeid) {
        $params = self::validate_parameters(self::acknowledge_notice_parameters(),
            array('noticeid' => $noticeid));

        $result = [
            'status' => 0,
            'redirecturl' => '',
        ];

        if ($notice = sitenotice::get_record(['id' => $params['noticeid']])) {
            $result = helper::acknowledge_notice($notice);
        }

        return $result;
    }

    /**
     * Return parameters.
     *
     * @return \external_single_structure
     */
    public static function acknowledge_notice_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'redirecturl' => new external_value(PARAM_TEXT, 'redirect url', VALUE_DEFAULT, ""),
            )
        );
    }

    /**
     * Incoming params.
     * @return \external_function_parameters
     */
    public static function track_link_parameters() {
        return new external_function_parameters (
            array(
                'linkid' => new external_value(PARAM_INT, 'link id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Track link.
     *
     * @param int $linkid Link ID.
     * @return array
     */
    public static function track_link($linkid) {
        $params = self::validate_parameters(self::track_link_parameters(), array('linkid' => $linkid));
        return helper::track_link($params['linkid']);
    }

    /**
     * Return parameters.
     *
     * @return \external_single_structure
     */
    public static function track_link_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'redirecturl' => new external_value(PARAM_TEXT, 'redirect url', VALUE_DEFAULT, ""),
            )
        );
    }

    /**
     * Incoming params.
     *
     * @return \external_function_parameters
     */
    public static function get_notices_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Gets a list of notices.
     *
     * @return array
     */
    public static function get_notices() {
        $result = array();
        $result['status'] = true;
        $result['notices'] = json_encode(
            array_map(
                function(sitenotice $notice): \stdClass {
                    return $notice->to_record();
                },
                helper::retrieve_user_notices()
            )
        );

        return $result;
    }

    /**
     * Return parameters.
     *
     * @return \external_single_structure
     */
    public static function get_notices_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'notices' => new external_value(PARAM_RAW, 'json of notices', VALUE_DEFAULT, ""),
            )
        );
    }
}
