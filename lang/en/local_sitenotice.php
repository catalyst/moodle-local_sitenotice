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
 * English language file
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Site Notice';

// Settings.
$string['setting:settings'] = 'Settings';
$string['setting:managenotice'] = 'Manage Notice';
$string['setting:enabled'] = 'Enabled';
$string['setting:enableddesc'] = 'Enable site notice';

// Notice Management.
$string['notice:title'] = 'Title';
$string['notice:content'] = 'Content';
$string['notice:audience'] = 'Audience';
$string['notice:enable'] = 'Enable notice';
$string['notice:reqack'] = 'Requires Acknowledgement';
$string['notice:reqack_help'] = 'If enabled, the user will need to accept the notice before they can continue to use the LMS site.
If the user does not accept the notice, he/she will be logged out of the site.';
$string['notice:disable'] = 'Disable notice';
$string['notice:create'] = 'Create new notice';
$string['notice:view'] = 'View notice';
$string['notice:info'] = 'Notice information';
$string['notice:report'] = 'View report';
$string['notice:reset'] = 'Reset notice';
$string['notice:hlinkcount'] = 'Hyperlink Counts';
$string['notice:resetinterval'] = 'Reset every';
$string['notice:resetinterval_help'] = 'The notice will be displayed to user again once the specified period elapses.';

// Event.
$string['event:dismiss'] = 'Notice dismission';
$string['event:acknowledgement'] = 'Notice acknowledgement';
$string['event:create'] = 'Notice creation';
$string['event:update'] = 'Notice update';

// Report.
$string['report:name'] = 'Notice Report';

// Time format.
$string['timeformat:resetinterval'] = '%a days, %h hours, %i minutes and %s seconds';

// Privacy.
$string['privacy:metadata:local_sitenotice_ack'] = 'Notice Acknowledgement';
$string['privacy:metadata:local_sitenotice_hlinks_his'] = 'Hyperlink Tracking';
$string['privacy:metadata:local_sitenotice_lastview'] = 'Notice last view';
$string['privacy:metadata:userid'] = 'User ID';

// Notification.
$string['notification:noack'] = 'There is no acknowledgment for this notice';
$string['notification:noticedoesnotexist'] = 'The notice does not exist';
