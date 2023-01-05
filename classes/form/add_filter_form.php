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

namespace local_sitenotice\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir.'/formslib.php');

/**
 * Form to add filter to the report
 *
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_filter_form extends moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        $mform =& $this->_form;
        $fields = $this->_customdata['fields'];

        $mform->addElement('header', 'newfilter', get_string('newfilter', 'filters'));

        foreach ($fields as $field) {
            $field->setupForm($mform);
        }

        // Add button.
        $mform->addElement('submit', 'addfilter', get_string('addfilter', 'filters'));
    }
}
