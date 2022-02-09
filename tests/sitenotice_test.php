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

/**
 * Test cases
 *
 * @package    local_sitenotice
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright  Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sitenotice_test extends \advanced_testcase {

    /**
     * Initial set up.
     */
    protected function setUp(): void {
        parent::setup();
        $this->resetAfterTest(true);
    }

    /**
     * Tear down after tests.
     */
    protected function tearDown(): void {
        global $SESSION;

        parent::tearDown();
        unset($SESSION->viewednotices);
    }

    /**
     * Create sample notice.
     */
    private function create_notice1() {
        $formdata = new \stdClass();
        $formdata->title = "Notice 1";
        $formdata->content = "Notice 1 <a href=\"www.example1.com\">Link 1</a> <a href=\"www.example2.com\">Link 2</a>";
        helper::create_new_notice($formdata);
    }

    /**
     * Create sample notice.
     */
    private function create_notice2() {
        $formdata = new \stdClass();
        $formdata->title = "Notice 2";
        $formdata->content = "Notice 2 <a href=\"www.example3.com\">Link 3</a> <a href=\"www.example4.com\">Link 4</a>";
        helper::create_new_notice($formdata);
    }

    /**
     * Create sample notice (with targeted audience)
     */
    private function create_cohort_notice1() {
        $formdata = new \stdClass();
        $formdata->title = "Cohort Notice 1";
        $formdata->content = "Cohort Notice 1 <a href=\"www.example5.com\">Link 5</a> <a href=\"www.example6.com\">Link 6</a>";
        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata->audience = $cohort->id;
        helper::create_new_notice($formdata);
    }

    /**
     * Create sample notice (with targeted audience)
     */
    private function create_cohort_notice2() {
        $formdata = new \stdClass();
        $formdata->title = "Cohort Notice 2";
        $formdata->content = "Cohort Notice 2 <a href=\"www.example7.com\">Link 7</a> <a href=\"www.example8.com\">Link 8</a>";
        $cohort = $this->getDataGenerator()->create_cohort();
        $formdata->audience = $cohort->id;
        helper::create_new_notice($formdata);
    }

    /**
     * Test notice creation.
     */
    public function test_create_notices() {
        $this->setAdminUser();
        $this->create_notice1();
        $allnotices = helper::retrieve_enabled_notices();
        // There is only one notice.
        $this->assertEquals(1, count($allnotices));
        $this->assertEquals("Notice 1", reset($allnotices)->title);

        $this->create_notice2();
        $allnotices = helper::retrieve_enabled_notices();
        // There are two notices.
        $this->assertEquals(2, count($allnotices));
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);
        $notice2 = array_shift($allnotices);
        $this->assertEquals("Notice 2", $notice2->title);

        // Check notice links.
        $allinks = helper::retrieve_notice_links($notice1->id);
        $this->assertEquals(2, count($allinks));
        $link1 = array_shift($allinks);
        $this->assertEquals('Link 1', $link1->text);
        $this->assertEquals('www.example1.com', $link1->link);
        $link2 = array_shift($allinks);
        $this->assertEquals('Link 2', $link2->text);
        $this->assertEquals('www.example2.com', $link2->link);

        $this->assertStringContainsString('data-linkid', $notice1->content);
        $this->assertStringContainsString('data-linkid', $notice2->content);

        // Do not allow deletion by default.
        helper::delete_notice($notice2->id);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(2, count($allnotices));

        // Delete notice without cleaning up.
        set_config('allow_delete', true, 'local_sitenotice');
        helper::delete_notice($notice2->id);
        $allnotices = helper::retrieve_enabled_notices();
        // There is only one notice.
        $this->assertEquals(1, count($allnotices));
        $this->assertEquals("Notice 1", reset($allnotices)->title);
        // Leftover hyperlinks of notice 2.
        $allinks = helper::retrieve_notice_links($notice2->id);
        $this->assertEquals(2, count($allinks));

        // Allow cleaning up.
        set_config('cleanup_deleted_notice', true, 'local_sitenotice');
        helper::delete_notice($notice1->id);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(0, count($allnotices));
        // Leftover hyperlinks of notice 1.
        $allinks = helper::retrieve_notice_links($notice1->id);
        $this->assertEquals(0, count($allinks));

    }

    /**
     * Test set reset notice.
     */
    public function test_reset_notices() {
        $this->setAdminUser();
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
        $this->assertEquals($newnotice2->timecreated, $oldnotice2->timecreated);
    }

    /**
     * Test enable/disable notice.
     */
    public function test_enable_notices() {
        $this->setAdminUser();
        $this->create_notice1();
        $this->create_notice2();
        $allnotices = helper::retrieve_enabled_notices();
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);

        // Only disable Notice 1.
        helper::disable_notice($notice1->id);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice2 = array_shift($allnotices);
        $this->assertEquals("Notice 2", $notice2->title);

        // Enable Notice 1, disable Notice 2.
        helper::enable_notice($notice1->id);
        helper::disable_notice($notice2->id);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->title);
    }

    /**
     * Test audience options.
     */
    public function test_audience_options() {
        $this->getDataGenerator()->create_cohort();
        $options = helper::built_audience_options();
        $this->assertEquals(2, count($options));

        $this->getDataGenerator()->create_cohort();
        $options = helper::built_audience_options();
        $this->assertEquals(3, count($options));
    }

    /**
     * Test user notice interaction.
     */
    public function test_user_notice() {
        global $SESSION;
        $this->setAdminUser();
        $this->create_notice1();
        $this->create_notice2();
        $this->create_cohort_notice1();
        $this->create_cohort_notice2();
        $user1 = $this->getDataGenerator()->create_user();

        // Four active notices.
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(4, count($allnotices));
        $notice1 = array_shift($allnotices);
        $notice2 = array_shift($allnotices);
        $cohortnotice1 = array_shift($allnotices);
        $cohortnotice2 = array_shift($allnotices);

        // Only notice 1 and notice 2 are applied to user 1.
        $this->setUser($user1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));

        $this->setAdminUser();
        helper::disable_notice($notice2->id);

        // Only Notice 1 applied to user 1.
        $this->setUser($user1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));
        $notice = reset($usernotices);
        $this->assertEquals('Notice 1', $notice->title);

        // Add user 1 to cohorts of cohort notice 1 and cohort notice 2, there will be 3 notices for the user.
        cohort_add_member($cohortnotice1->audience, $user1->id);
        cohort_add_member($cohortnotice2->audience, $user1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(3, count($usernotices));

        // User 1 dismissed notice 1, there will be 2 notices for the user.
        helper::dismiss_notice($notice1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($SESSION->viewednotices));

        // User 1 acknowledged notice 1, there will be 1 notice for the user.
        helper::acknowledge_notice($cohortnotice1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));
        $this->assertEquals(2, count($SESSION->viewednotices));

        // Admin user reset notice 1.
        sleep(1);
        $this->setAdminUser();
        helper::reset_notice($notice1->id);

        // There will be 2 notices for user 1.
        $this->setUser($user1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($SESSION->viewednotices));
    }

    /**
     * Test user link interaction
     */
    public function test_user_hlink_interact() {
        $this->setAdminUser();
        $this->create_notice1();
        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $allnotices = helper::retrieve_enabled_notices();
        $this->assertEquals(1, count($allnotices));
        $notice1 = array_shift($allnotices);

        $links = helper::retrieve_notice_links($notice1->id);
        $this->assertEquals(2, count($links));
        $link1 = array_shift($links);
        $link2 = array_shift($links);

        // Clink on links.
        helper::track_link($link1->id);
        helper::track_link($link2->id);
        $userlinks = helper::count_clicked_notice_links($user1->id, $notice1->id);
        $this->assertEquals(2, count($userlinks));
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
     * Test course completion option.
     */
    public function test_user_required_completion() {
        global $DB;
        $this->setAdminUser();

        $formdata = new \stdClass();
        $formdata->title = "Course Notice 1";
        $formdata->content = "Course Notice 1 <a href=\"www.examplecourse1.com\">Link Course 1</a> <a href=\"www.examplecourse2.com\">Link Course 2</a>";

        // Create a course with completion enabled.
        $course = $this->getDataGenerator()->create_course(array('enablecompletion' => 1));

        // Finish creating the notice.
        $formdata->reqcourse = $course->id;
        helper::create_new_notice($formdata);

        // Enrol a user in the course.
        $user = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $studentrole->id);

        // Add two activities that use completion.
        $assign = $this->getDataGenerator()->create_module('assign', array('course' => $course->id),
            array('completion' => 1));
        $data = $this->getDataGenerator()->create_module('data', array('course' => $course->id),
            array('completion' => 1));

        // Now retrieve all the user notices.
        $this->setUser($user);
        $usernotices = helper::retrieve_user_notices();
        // There is only one notice.
        $this->assertEquals(1, count($usernotices));
        $this->assertEquals("Course Notice 1", reset($usernotices)->title);

        // Mark one of them as completed for a user.
        $cmassign = get_coursemodule_from_id('assign', $assign->cmid);
        $completion = new \completion_info($course);
        $completion->update_state($cmassign, COMPLETION_COMPLETE, $user->id);

        // Now retrieve all the user notices.
        $usernotices = helper::retrieve_user_notices();
        // There should still be one notice.
        $this->assertEquals(1, count($usernotices));

        // Now, mark the course as completed.
        $ccompletion = new \completion_completion(array('course' => $course->id, 'userid' => $user->id));
        $ccompletion->mark_complete();

        // Now retrieve all the user notices.
        $usernotices = helper::retrieve_user_notices();
        // There should not be any user notices.
        $this->assertEquals(0, count($usernotices));
    }
}
