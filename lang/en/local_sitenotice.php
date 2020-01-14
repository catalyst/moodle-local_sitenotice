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
$string['setting:allow_update'] = 'Allow notice update';
$string['setting:allow_updatedesc'] = 'Allow notice to be updated';
$string['setting:allow_delete'] = 'Allow notice deletion';
$string['setting:allow_deletedesc'] = 'Allow notice to be deleted';
$string['setting:cleanup_deleted_notice'] = 'Clean up info related to the deleted notice';
$string['setting:cleanup_deleted_noticedesc'] = 'Requires "Allow notice deletion".
If enabled, other details related to the notice being deleted, such as hyperlinks, hyperlinks history, acknowledgement,
user last view will also be deleted';

// Notice Management.
$string['notice:title'] = 'Title';
$string['notice:content'] = 'Content';
$string['notice:audience'] = 'Audience';
$string['notice:audience:all'] = 'All Users';
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
$string['notice:delete'] = 'Delete notice';
$string['notice:timemodified'] = 'Time Modified';
$string['notice:hlinkcount'] = 'Hyperlink Counts';
$string['notice:resetinterval'] = 'Reset every';
$string['notice:resetinterval_help'] = 'The notice will be displayed to user again once the specified period elapses.';

// Capability.
$string['sitenotice:manage'] = 'Manage Site notice';

// Event.
$string['event:dismiss'] = 'dismiss';
$string['event:acknowledge'] = 'acknowledge';
$string['event:create'] = 'create';
$string['event:update'] = 'update';
$string['event:reset'] = 'reset';
$string['event:enable'] = 'enable';
$string['event:disable'] = 'disable';
$string['event:delete'] = 'delete';
$string['event:timecreated'] = 'Time';

// Time format.
$string['timeformat:resetinterval'] = '%a day(s), %h hour(s), %i minute(s) and %s second(s)';
$string['booleanformat:true'] = 'YES';
$string['booleanformat:false'] = 'NO';

// Privacy.
$string['privacy:metadata:local_sitenotice_ack'] = 'Notice Acknowledgement';
$string['privacy:metadata:local_sitenotice_hlinks_his'] = 'Hyperlink Tracking';
$string['privacy:metadata:local_sitenotice_lastview'] = 'Notice last view';
$string['privacy:metadata:userid'] = 'User ID';
$string['privacy:metadata:username'] = 'Username';
$string['privacy:metadata:firstname'] = 'First name';
$string['privacy:metadata:lastname'] = 'Last name';
$string['privacy:metadata:idnumber'] = 'ID number';

// Notification.
$string['notification:noack'] = 'There is no acknowledgment for this notice';
$string['notification:nodis'] = 'There is no dismission for this notice';
$string['notification:noticedoesnotexist'] = 'The notice does not exist';
$string['notification:nodeleteallowed'] = 'Notice deletion is not allowed';
$string['notification:noupdateallowed'] = 'Notice update is not allowed';

// Confirmation.
$string['confirmation:deletenotice'] = 'Do you really want to delete the notice "{$a}"';

// Modal Buttons.
$string['button:close'] = 'CLOSE';
$string['button:accept'] = 'ACCEPT';

// Report.
$string['report:button:ack'] = 'Notice acknowledgement report';
$string['report:button:dis'] = 'Notice dismiss Report';
$string['report:dismissed'] = 'notice_dismissed_{$a}';
$string['report:dismissed_desc'] = 'List of users who dismissed the notice.';
$string['report:acknowledged'] = 'notice_acknowledged_{$a}';
$string['report:acknowledge_desc'] = 'List of users who acknowledged the notice.';
$string['report:timeformat:sortable'] = '%Y.%m.%d-%H:%M:%S.';
