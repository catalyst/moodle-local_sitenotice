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

require_once("$CFG->libdir/externallib.php");
use local_sitenotice\helper;

class local_sitenotice_external extends external_api {

    public static function dismiss_notice_parameters() {
        return new external_function_parameters (
            array(
                'noticeid' => new external_value(PARAM_INT, 'notice id', VALUE_REQUIRED),
            )
        );
    }

    public static function dismiss_notice($noticeid) {
        $params = self::validate_parameters(self::dismiss_notice_parameters(),
            array('noticeid' => $noticeid));
         return helper::dismiss_notice($params['noticeid']);
    }

    public static function dismiss_notice_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'redirecturl' => new external_value(PARAM_TEXT, 'redirect url', VALUE_DEFAULT, ""),
            )
        );
    }

    public static function acknowledge_notice_parameters() {
        return new external_function_parameters (
            array(
                'noticeid' => new external_value(PARAM_INT, 'notice id', VALUE_REQUIRED),
            )
        );
    }

    public static function acknowledge_notice($noticeid) {
        $params = self::validate_parameters(self::acknowledge_notice_parameters(),
            array('noticeid' => $noticeid));
        return helper::acknowledge_notice($params['noticeid']);
    }

    public static function acknowledge_notice_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_BOOL, 'status: true if success', VALUE_DEFAULT, "0"),
                'redirecturl' => new external_value(PARAM_TEXT, 'redirect url', VALUE_DEFAULT, ""),
            )
        );
    }
}