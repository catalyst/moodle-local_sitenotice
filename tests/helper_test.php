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

namespace local_sitenotice;

use local_sitenotice\persistent\sitenotice;

/**
 * Test cases
 * @package    local_sitenotice
 * @author     Dmitrii Metelkin <dmitriim@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper_test extends \advanced_testcase {

    /**
     * Test a list of cohorts is built properly.
     */
    public function test_built_cohort_options() {
        $this->resetAfterTest(true);

        $expected = [];
        for ($i = 1; $i <= 50; $i++) {
            $cohort = $this->getDataGenerator()->create_cohort();
            $expected[$cohort->id] = $cohort->name;
        }

        $actual = helper::built_cohorts_options();

        foreach ($expected as $id => $name) {
            $this->assertSame($actual[$id], $name);
        }
    }

    /**
     * Test that we can have full HTML in a notice content.
     */
    public function test_can_have_html_in_notice_content() {
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('allow_update', 1, 'local_sitenotice');

        $formdata = new \stdClass();
        $formdata->title = "What is Moodle?";
        $formdata->content = 'Moodle <iframe width="1280" height="720" src="https://www.youtube.com/embed/3ORsUGVNxGs"></iframe>';
        helper::create_new_notice($formdata);

        $allnotices = sitenotice::get_all_notices();
        $actual = reset($allnotices);
        $this->assertStringContainsString($formdata->content, $actual->get('content'));

        $formdata->title = 'Updated notice';
        $formdata->content = 'Updated  <iframe width="1280" height="720" src="https://www.youtube.com/embed/wop3FMhoLGs"></iframe>';
        $sitenotice = sitenotice::get_record(['id' => $actual->get('id')]);
        helper::update_notice($sitenotice, $formdata);

        $allnotices = sitenotice::get_all_notices();
        $actual = reset($allnotices);
        $this->assertStringContainsString($formdata->content, $actual->get('content'));
    }

    /**
     * Test time interval format.
     */
    public function test_format_interval_time() {
        // The interval is 1 day(s) 2 hour(s) 3 minute(s) 4 second(s).
        $timeinterval = 93784;
        $formatedtime = helper::format_interval_time($timeinterval);
        // Assume the time format is '%a day(s), %h hour(s), %i minute(s) and %s second(s)'.
        $this->assertStringContainsString('1 day(s), 2 hour(s), 3 minute(s) and 4 second(s)', $formatedtime);
    }

    /**
     * Test cohorts options.
     */
    public function test_cohort_options() {
        $this->resetAfterTest();

        $options = helper::built_cohorts_options();
        $this->assertEquals(0, count($options));

        $this->getDataGenerator()->create_cohort();
        $options = helper::built_cohorts_options();
        $this->assertEquals(1, count($options));

        $this->getDataGenerator()->create_cohort();
        $options = helper::built_cohorts_options();
        $this->assertEquals(2, count($options));
    }

}
