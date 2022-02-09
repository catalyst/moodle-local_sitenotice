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
     * Tear down after tests.
     */
    protected function tearDown(): void {
        global $SESSION;

        parent::tearDown();
        unset($SESSION->viewednotices);
    }

    /**
     * Test a list of cohorts is built properly.
     */
    public function test_built_audience_options() {
        $this->resetAfterTest(true);

        $expected = ['0' => get_string('notice:audience:all', 'local_sitenotice')];
        for ($i = 1; $i <= 50; $i++) {
            $cohort = $this->getDataGenerator()->create_cohort();
            $expected[$cohort->id] = $cohort->name;
        }

        $actual = helper::built_audience_options();

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

        $allnotices = helper::retrieve_all_notices();
        $actual = reset($allnotices);
        $this->assertStringContainsString($formdata->content, $actual->content);

        $formdata->title = 'Updated notice';
        $formdata->content = 'Updated  <iframe width="1280" height="720" src="https://www.youtube.com/embed/wop3FMhoLGs"></iframe>';
        $sitenotice = sitenotice::get_record(['id' => $actual->id]);
        helper::update_notice($sitenotice, $formdata);

        $allnotices = helper::retrieve_all_notices();
        $actual = reset($allnotices);
        $this->assertStringContainsString($formdata->content, $actual->content);
    }

}
