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

use local_sitenotice\helper;


function dismiss_notice_handler($eventdata) {

}

function acknowledge_notice_handler($eventdata) {

}

function local_sitenotice_extend_navigation(global_navigation $navigation) {
    global $CFG, $USER, $PAGE;
    $usernotices = helper::retrieve_user_notices($USER->id);

    if (!empty($usernotices)) {
        $PAGE->requires->css('/local/sitenotice/styles.css');
        $PAGE->requires->js_call_amd('local_sitenotice/notice', 'init', array(json_encode($usernotices)));
    }
}