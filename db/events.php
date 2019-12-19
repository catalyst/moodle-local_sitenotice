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

$observers = array (
    array(
        'eventname'   => '\local_sitenotice\event\sitenotice_created',
        'callback'    => '\local_sitenotice\eventobservers::sitenotice_created',
    ),

    array(
        'eventname'   => '\local_sitenotice\event\sitenotice_updated',
        'callback'    => '\local_sitenotice\eventobservers::sitenotice_updated',
    ),

    array(
        'eventname'   => '\local_sitenotice\event\sitenotice_dismissed',
        'callback'    => '\local_sitenotice\eventobservers::sitenotice_dismissed',
    ),

    array(
        'eventname'   => '\local_sitenotice\event\sitenotice_acknowledged',
        'callback'    => '\local_sitenotice\eventobservers::sitenotice_acknowledged',
    ),
);
