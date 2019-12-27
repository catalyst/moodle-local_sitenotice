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
 * Test cases
 * @package package
 * @author  Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_sitenotice\helper;

class local_sitenotice_test extends advanced_testcase {

    /**
     * Initial set up.
     */
    protected function setUp() {
        parent::setup();
        $this->resetAfterTest(true);
    }

    private function create_notice1() {
        $formdata = new stdClass();
        $formdata->title = "Notice 1";
        $formdata->content = "Notice 1 <a href=\"www.example1.com\">Link 1</a> <a href=\"www.example2.com\">Link 2</a>";
        helper::create_new_notice($formdata);
    }

    private function create_notice2() {
        $formdata = new stdClass();
        $formdata->title = "Notice 2";
        $formdata->content = "Notice 2 <a href=\"www.example3.com\">Link 3</a> <a href=\"www.example4.com\">Link 4</a>";
        helper::create_new_notice($formdata);
    }

    private function create_cohort_notice1() {
        $formdata = new stdClass();
        $formdata->title = "Cohort Notice 1";
        $formdata->content = "Cohort Notice 1 <a href=\"www.example5.com\">Link 5</a> <a href=\"www.example6.com\">Link 6</a>";
        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata->audience = $cohort->id;
        helper::create_new_notice($formdata);
    }

    private function create_cohort_notice2() {
        $formdata = new stdClass();
        $formdata->title = "Cohort Notice 2";
        $formdata->content = "Cohort Notice 2 <a href=\"www.example7.com\">Link 7</a> <a href=\"www.example8.com\">Link 8</a>";
        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata->audience = $cohort->id;
        helper::create_new_notice($formdata);
    }

    public function test_create_notices() {
        $this->create_notice1();
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $this->assertEquals("Notice 1", reset($allnotices)->title);

        $this->create_notice2();
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(2, count($allnotices));
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);
        $notice2 = array_shift($allnotices);
        $this->assertEquals("Notice 2", $notice2->title);

        $allinks = helper::retrieve_notice_hlinks($notice1->id);
        $this->assertEquals(2, count($allinks));
        $link1 = array_shift($allinks);
        $this->assertEquals('Link 1', $link1->text);
        $this->assertEquals('www.example1.com', $link1->link);
        $link2 = array_shift($allinks);
        $this->assertEquals('Link 2', $link2->text);
        $this->assertEquals('www.example2.com', $link2->link);
    }

    public function test_reset_notices() {
        $this->create_notice1();
        $this->create_notice2();
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(2, count($allnotices));
        $oldnotice1 = array_shift($allnotices);
        $oldnotice2 = array_shift($allnotices);
        // Only reset Notice 1.
        sleep(1);
        helper::reset_notice($oldnotice1->id);
        $allnotices = helper::retrieve_enabled_notices();
        $newnotice1 = array_shift($allnotices);
        $newnotice2 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $newnotice1->title);
        $this->assertGreaterThan($oldnotice1->timemodified, $newnotice1->timemodified);
        $this->assertEquals($oldnotice1->timecreated, $newnotice1->timecreated);
        $this->assertEquals($newnotice2->timemodified, $oldnotice2->timemodified);
    }

    public function test_enable_notices() {
        $this->create_notice1();
        $this->create_notice2();
        $allnotices = helper::retrieve_enabled_notices();
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);

        // Only disable Notice 1.
        helper::reset_notice($notice1->id, 0);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice2 = array_shift($allnotices);
        $this->assertEquals("Notice 2", $notice2->title);

        // Enable Notice 1, disable Notice 2.
        helper::reset_notice($notice1->id, 1);
        helper::reset_notice($notice2->id, 0);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);
    }

    public function test_audience_options() {
        $this->getDataGenerator()->create_cohort();
        $options = helper::built_audience_option();
        $this->assertEquals(2, count($options));

        $this->getDataGenerator()->create_cohort();
        $options = helper::built_audience_option();
        $this->assertEquals(3, count($options));
    }

    public function test_user_notice() {
        global $USER;
        $this->create_notice1();
        $this->create_notice2();
        $this->create_cohort_notice1();
        $this->create_cohort_notice2();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);

        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(4, count($allnotices));
        $notice1 = array_shift($allnotices);
        $notice2 = array_shift($allnotices);
        $cohortnotice1 = array_shift($allnotices);
        $cohortnotice2 = array_shift($allnotices);

        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));

        helper::reset_notice($notice2->id, 0);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));

        cohort_add_member($cohortnotice1->audience, $user1->id);
        cohort_add_member($cohortnotice2->audience, $user1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(3, count($usernotices));

        helper::dismiss_notice($notice1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($USER->viewednotices));

        helper::acknowledge_notice($cohortnotice1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));
        $this->assertEquals(2, count($USER->viewednotices));

        sleep(1);
        helper::reset_notice($notice1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($USER->viewednotices));
    }

    public function test_user_hlink_interact() {
        $this->create_notice1();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice1 = array_shift($allnotices);

        $links = helper::retrieve_notice_hlinks($notice1->id);
        $this->assertEquals(2, count($links));
        $link1 = array_shift($links);
        $link2 = array_shift($links);

        // Clink on links.
        helper::track_link($link1->id);
        helper::track_link($link2->id);
        $userlinks = helper::retrieve_hlink_count($user1->id, $notice1->id);
        $this->assertEquals(2, count($userlinks));
    }

    public function test_format_interval_time() {
        // 1 day(s) 2 hour(s) 3 minute(s) 4 second(s)
        $timeinterval = 93784;
        $formatedtime = helper::format_interval_time($timeinterval);
        // Assume the time format is '%a day(s), %h hour(s), %i minute(s) and %s second(s)'.
        $this->assertContains('1 day(s), 2 hour(s), 3 minute(s) and 4 second(s)', $formatedtime);
    }
}
