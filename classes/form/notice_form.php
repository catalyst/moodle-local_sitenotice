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

use local_sitenotice\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to create new notice
 * @package local_sitenotice
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notice_form extends \core\form\persistent {

    /** @var string Persistent class name. */
    protected static $persistentclass = 'local_sitenotice\persistent\sitenotice';

    /** @var array Fields to remove from the persistent validation. */
    protected static $foreignfields = array('perpetual', 'cohorts');

    /**
     * Form definition.
     */
    public function definition () {
        $mform =& $this->_form;

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('notice:title', 'local_sitenotice'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', get_string('required'), 'required', null, 'client');

        $mform->addElement('editor', 'content',
            get_string('notice:content', 'local_sitenotice'), [], helper::get_file_editor_options());
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', get_string('required'), 'required', null, 'client');

        $mform->addElement('duration', 'resetinterval', get_string('notice:resetinterval', 'local_sitenotice'));
        $mform->addHelpButton('resetinterval', 'notice:resetinterval', 'local_sitenotice');
        $mform->setDefault('resetinterval', 0);

        $mform->addElement('selectyesno', 'reqack', get_string('notice:reqack', 'local_sitenotice'));
        $mform->addHelpButton('reqack', 'notice:reqack', 'local_sitenotice');

        $mform->setDefault('reqack', 0);

        $mform->addElement('selectyesno', 'forcelogout', get_string('notice:forcelogout', 'local_sitenotice'));
        $mform->addHelpButton('forcelogout', 'notice:forcelogout', 'local_sitenotice');

        $mform->setDefault('forcelogout', 0);

        $mform->addElement(
            'autocomplete',
            'cohorts',
            get_string('notice:cohort', 'local_sitenotice'),
            helper::built_cohorts_options(),
            ['noselectionstring' => get_string('notice:cohort:all', 'local_sitenotice'), 'multiple' => true, 'id' => 'id_cohorts']
        );

        $mform->setDefault('cohorts', 0);

        $options = array(
            'multiple' => false,
            'noselectionstring' => 'No',
        );
        $mform->addElement('course', 'reqcourse', get_string('notice:reqcourse', 'local_sitenotice'), $options);
        $mform->setType('reqcourse', PARAM_INT);
        $mform->addHelpButton('reqcourse', 'notice:reqcourse', 'local_sitenotice');
        $mform->setDefault('reqcourse', 0);

        $mform->addElement('selectyesno', 'perpetual', get_string('notice:perpetual', 'local_sitenotice'));
        $mform->setDefault('perpetual', 1);

        $activeoptions = ['startyear' => date("Y"), 'stopyear'  => 2030];
        $mform->addElement('date_time_selector', 'timestart', get_string('notice:activefrom', 'local_sitenotice'), $activeoptions);
        $mform->addHelpButton('timestart', 'notice:activefrom', 'local_sitenotice');
        $mform->hideIf('timestart', 'perpetual', 'eq', 1);

        $expiryoptions = ['startyear' => date("Y"), 'stopyear'  => 2030, 'defaulttime' => time() + HOURSECS];
        $mform->addElement('date_time_selector', 'timeend', get_string('notice:expiry', 'local_sitenotice'), $expiryoptions);
        $mform->addHelpButton('timeend', 'notice:expiry', 'local_sitenotice');
        $mform->hideIf('timeend', 'perpetual', 'eq', 1);

        $mform->addElement('selectyesno', 'enabled', get_string('notice:enable', 'local_sitenotice'));
        $mform->setDefault('enabled', 1);

        $buttonarray = array();

        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        $buttonarray[] = $mform->createElement('cancel');

        $mform->addGroup($buttonarray, 'buttonar', '', ' ', false);
    }

    /**
     * Returns a default data.
     * @return \stdClass
     */
    protected function get_default_data() {
        $data = parent::get_default_data();
        $data->perpetual = $data->timestart == 0 && $data->timeend == 0;

        return $data;
    }
}
