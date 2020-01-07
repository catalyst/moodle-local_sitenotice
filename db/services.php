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
 * Webservice function registry
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_sitenotice_dismiss' => array(
        'classname' => 'local_sitenotice_external',
        'methodname' => 'dismiss_notice',
        'classpath' => 'local/sitenotice/classes/external.php',
        'description' => 'Dismiss a notice',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),

    'local_sitenotice_acknowledge' => array(
        'classname' => 'local_sitenotice_external',
        'methodname' => 'acknowledge_notice',
        'classpath' => 'local/sitenotice/classes/external.php',
        'description' => 'Acknowledge a notice',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),

    'local_sitenotice_tracklink' => array(
        'classname' => 'local_sitenotice_external',
        'methodname' => 'track_link',
        'classpath' => 'local/sitenotice/classes/external.php',
        'description' => 'Record link clicks',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),

    'local_sitenotice_getnotices' => array(
        'classname' => 'local_sitenotice_external',
        'methodname' => 'get_notices',
        'classpath' => 'local/sitenotice/classes/external.php',
        'description' => 'Record link clicks',
        'type' => 'write',
        'loginrequired' => true,
        'ajax' => true,
    ),
);
