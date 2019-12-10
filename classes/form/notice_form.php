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

namespace local_sitenotice\form;

defined('MOODLE_INTERNAL') || die();

use moodleform;
use local_sitenotice\helper;

require_once($CFG->libdir . '/formslib.php');

class notice_form extends moodleform {

    public function definition () {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'noticeid', 0);
        $mform->setType('noticeid', PARAM_INT);

        $readonly = isset($this->_customdata['readonly']) ? $this->_customdata['readonly'] : false;

        $attributes = $readonly ? ['disabled' => ''] : [];

        $mform->addElement('text', 'title', get_string('notice:title', 'local_sitenotice'), $attributes);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        if ($readonly) {
            $mform->addElement('static', 'content', get_string('notice:content', 'local_sitenotice'));
        } else {
            $mform->addElement('htmleditor', 'content', get_string('notice:content', 'local_sitenotice'), ['rows'=> '10', 'cols'=>'30']);
        }
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', get_string('required'), 'required', null, 'client');


        $mform->addElement('selectyesno', 'reqack', get_string('notice:reqack', 'local_sitenotice'), $attributes);
        $mform->setDefault('reqack', 0);

        $audience = helper::built_audience_option();

        $mform->addElement('select', 'audience', get_string('notice:audience', 'local_sitenotice'), $audience, $attributes);
        $mform->setDefault('audience', 0);

        $mform->addElement('selectyesno', 'enabled', get_string('notice:enable', 'local_sitenotice'), $attributes);
        $mform->setDefault('enabled', 1);

        $buttonarray = array();

        if ($readonly) {
            $buttonarray[] = $mform->createElement('cancel');
        } else {
            $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
            $buttonarray[] = $mform->createElement('cancel');
        }

        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }
}
