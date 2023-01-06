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
use local_sitenotice\persistent\noticelink;
use local_sitenotice\persistent\linkhistory;

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
     * Test notice creation.
     *
     * @dataProvider create_notices_provider
     * @param array $formdata Array of form data to create notices
     * @param bool $allowdeletion Whether or not to allow deletion of notices
     * @param bool $cleanup Whether or not to clean up extra link data after notice deletion
     * @param array $expected Array of expected testcase results
     */
    public function test_create_notices(array $formdata, bool $allowdeletion, bool $cleanup, array $expected) {
        $this->setAdminUser();
        set_config('allow_delete', $allowdeletion, 'local_sitenotice');
        set_config('cleanup_deleted_notice', $cleanup, 'local_sitenotice');

        foreach ($formdata as $data) {
            if (property_exists($data, 'cohorts')) {
                $data->cohorts = $this->getDataGenerator()->create_cohort()->id;
            }
            helper::create_new_notice($data);
        }

        $allnotices = array_values(sitenotice::get_enabled_notices());
        $this->assertEquals($expected['noticecount'], count($allnotices));

        foreach ($allnotices as $noticeindex => $notice) {
            $this->assertEquals($expected['titles'][$noticeindex], $notice->get('title'));

            $allinks = noticelink::get_notice_link_records($notice->get('id'));
            $this->assertEquals($expected['linkcounts'][$noticeindex], count($allinks));
            $this->assertStringContainsString('data-linkid', $notice->get('content'));
            $this->assertEquals($expected['linktexts'][$noticeindex], array_column($allinks, 'text'));
            $this->assertEquals($expected['linkurls'][$noticeindex], array_column($allinks, 'link'));
        }

        $idtodelete = $allnotices[0]->get('id');
        helper::delete_notice($allnotices[0]);
        $allnotices = sitenotice::get_enabled_notices();
        $this->assertEquals($expected['noticecount'] - (int)$allowdeletion, count($allnotices));

        $allinks = noticelink::get_notice_link_records($idtodelete);
        $this->assertEquals($cleanup ? 0 : $expected['linkcounts'][0], count($allinks));
    }

    /**
     * Test set reset notice.
     *
     * @dataProvider generic_provider()
     * @param array $formdata Array of form data to create notices
     */
    public function test_reset_notices(array $formdata) {
        $this->setAdminUser();

        foreach ($formdata as $data) {
            if (property_exists($data, 'cohorts')) {
                $data->cohorts = [$this->getDataGenerator()->create_cohort()->id];
            }
            helper::create_new_notice($data);
        }

        $allnotices = sitenotice::get_enabled_notices();
        $this->assertEquals(4, count($allnotices));
        $oldnotice1 = array_shift($allnotices);
        $oldnotice2 = array_shift($allnotices);
        // Only reset Notice 1.
        sleep(1);
        helper::reset_notice($oldnotice1);
        $allnotices = sitenotice::get_enabled_notices();
        $newnotice1 = array_shift($allnotices);
        $newnotice2 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $newnotice1->get('title'));
        $this->assertGreaterThan($oldnotice1->get('timemodified'), $newnotice1->get('timemodified'));
        $this->assertEquals($oldnotice1->get('timecreated'), $newnotice1->get('timecreated'));
        $this->assertEquals($newnotice2->get('timemodified'), $oldnotice2->get('timemodified'));
        $this->assertEquals($newnotice2->get('timecreated'), $oldnotice2->get('timecreated'));
    }

    /**
     * Test enable/disable notice.
     *
     * @dataProvider generic_provider()
     * @param array $formdata Array of form data to create notices
     */
    public function test_enable_notices(array $formdata) {
        $this->setAdminUser();

        foreach ($formdata as $data) {
            if (property_exists($data, 'cohorts')) {
                $data->cohorts = [$this->getDataGenerator()->create_cohort()->id];
            }
            helper::create_new_notice($data);
        }

        $allnotices = sitenotice::get_enabled_notices();
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->get('title'));

        // Only disable Notice 1.
        helper::disable_notice($notice1);
        $allnotices = sitenotice::get_enabled_notices();
        $this->assertEquals(3, count($allnotices));
        $notice2 = array_shift($allnotices);
        $this->assertEquals("Notice 2", $notice2->get('title'));

        // Enable Notice 1, disable Notice 2.
        helper::enable_notice($notice1);
        helper::disable_notice($notice2);
        $allnotices = sitenotice::get_enabled_notices();
        $this->assertEquals(3, count($allnotices));
        $notice1 = array_shift($allnotices);
        $this->assertEquals("Notice 1", $notice1->get('title'));
    }

    /**
     * Test user notice interaction.
     *
     * @dataProvider generic_provider()
     * @param array $formdata Data to test on.
     */
    public function test_user_notice($formdata) {
        global $USER;

        $this->setAdminUser();
        foreach ($formdata as $data) {
            if (property_exists($data, 'cohorts')) {
                $data->cohorts = [$this->getDataGenerator()->create_cohort()->id];
            }
            helper::create_new_notice($data);
        }

        $user1 = $this->getDataGenerator()->create_user();
        $allnotices = sitenotice::get_enabled_notices();
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
        helper::disable_notice($notice2);

        // Only Notice 1 applied to user 1.
        $this->setUser($user1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));
        $notice = reset($usernotices);
        $this->assertEquals('Notice 1', $notice->get('title'));

        $cohorts1 = $cohortnotice1->get('cohorts');
        $cohorts2 = $cohortnotice2->get('cohorts');

        // Add user 1 to cohorts of cohort notice 1 and cohort notice 2, there will be 3 notices for the user.
        cohort_add_member(reset($cohorts1), $user1->id);
        cohort_add_member(reset($cohorts2), $user1->id);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(3, count($usernotices));

        // User 1 dismissed notice 1, there will be 2 notices for the user.
        helper::dismiss_notice($notice1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($USER->viewednotices));

        // User 1 acknowledged notice 1, there will be 1 notice for the user.
        helper::acknowledge_notice($cohortnotice1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(1, count($usernotices));
        $this->assertEquals(2, count($USER->viewednotices));

        // Admin user reset notice 1.
        sleep(1);
        $this->setAdminUser();
        helper::reset_notice($notice1);

        // There will be 2 notices for user 1.
        $this->setUser($user1);
        $usernotices = helper::retrieve_user_notices();
        $this->assertEquals(2, count($usernotices));
        $this->assertEquals(1, count($USER->viewednotices));
    }

    /**
     * Test user link interaction
     *
     * @dataProvider generic_provider()
     * @param array $formdata Data to test on.
     */
    public function test_user_hlink_interact($formdata) {
        $this->setAdminUser();
        foreach ($formdata as $data) {
            if (property_exists($data, 'cohorts')) {
                $data->cohorts = [$this->getDataGenerator()->create_cohort()->id];
            }
            helper::create_new_notice($data);
        }

        $user1 = $this->getDataGenerator()->create_user();
        $this->setUser($user1);
        $allnotices = sitenotice::get_enabled_notices();
        $this->assertEquals(4, count($allnotices));
        $notice1 = array_shift($allnotices);

        $links = noticelink::get_notice_link_records($notice1->get('id'));
        $this->assertEquals(2, count($links));
        $link1 = array_shift($links);
        $link2 = array_shift($links);

        // Clink on links.
        helper::track_link($link1->id);
        helper::track_link($link2->id);
        $userlinks = linkhistory::count_clicked_links($user1->id, $notice1->get('id'));
        $this->assertEquals(2, count($userlinks));
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
        $this->assertEquals("Course Notice 1", reset($usernotices)->get('title'));

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

    /**
     * Test user see required notice after dismissing it.
     */
    public function test_retrieve_user_notices_when_dismissed_one_that_requires_acknowledgement() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $formdata = (object)[
            'title' => 'Notice 1',
            'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
            'perpetual' => 1,
            'reqack' => 1,
        ];

        helper::create_new_notice($formdata);
        $allnotices = sitenotice::get_all_notices();
        $notice = array_shift($allnotices);

        // Must see 1 notice.
        $this->assertCount(1, helper::retrieve_user_notices());

        // After notice is dismissed, should still see 1 as it's required.
        $this->setAdminUser();
        helper::dismiss_notice($notice);
        $this->assertCount(1, helper::retrieve_user_notices());
    }

    /**
     * Test user see required notice after dismissing, and then acknowledged it.
     */
    public function test_retrieve_user_notices_when_dismiss_and_then_acknowledged() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $formdata = (object)[
            'title' => 'Notice 1',
            'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
            'perpetual' => 1,
            'reqack' => 1,
        ];

        helper::create_new_notice($formdata);
        $allnotices = sitenotice::get_all_notices();
        $notice = array_shift($allnotices);

        // Must see 1 notice.
        $this->assertCount(1, helper::retrieve_user_notices());

        // After notice is dismissed, should still see 1 as it's required.
        helper::dismiss_notice($notice);
        // User should be logged out after dismissing.
        $this->setAdminUser();
        $this->assertCount(1, helper::retrieve_user_notices());

        // After notice is acknowledged, should still see 0.
        helper::acknowledge_notice($notice);
        $this->assertCount(0, helper::retrieve_user_notices());

        // Logout user and log in again. Still shouldn't require to see the notice.
        $this->setUser();
        $this->setAdminUser();
        $this->assertCount(0, helper::retrieve_user_notices());
    }

    /**
     * Test user see required notice when forcelogout logout.
     */
    public function test_retrieve_user_notices_when_force_logout() {
        global $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $formdata = (object)[
            'title' => 'Notice 1',
            'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
            'perpetual' => 1,
            'reqack' => 0,
            'forcelogout' => 1,
        ];

        helper::create_new_notice($formdata);
        $allnotices = sitenotice::get_all_notices();
        $notice = array_shift($allnotices);

        // Admin must see 1 notice.
        $this->assertCount(1, helper::retrieve_user_notices());
        helper::dismiss_notice($notice);
        // Admin shouldn't be logged out.
        $this->assertNotEmpty($USER->username);
        // After notice is dismissed, admin shouldb't see it anymore.
        $this->assertCount(0, helper::retrieve_user_notices());

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // After notice is dismissed, should still see 1 as it's required.
        helper::dismiss_notice($notice);
        // User should be logged out.
        $this->assertTrue(!isset($USER->username));

        // Login again and check we still see the notice.
        $this->setUser($user);
        $this->assertCount(1, helper::retrieve_user_notices());
    }

    /**
     * Generic data provider to set up multiple tests.
     *
     * @return array
     */
    public function generic_provider() {
        return [
            'formdata' => [
                [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example3.com">Link 3</a> <a href="www.example4.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Cohort Notice 1',
                        'content' => 'Cohort Notice 1 <a href="www.example5.com">Link 5</a> <a href="www.example6.com">Link 6</a>',
                        'cohorts' => '',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Cohort Notice 2',
                        'content' => 'Cohort Notice 2 <a href="www.example7.com">Link 7</a> <a href="www.example8.com">Link 8</a>',
                        'cohorts' => '',
                        'perpetual' => 1,
                    ]
                ]
            ]
        ];
    }

    /**
     * Data provider for test_create_notices
     *
     * @return array
     */
    public function create_notices_provider(): array {
        return [
            'one basic notice with deletion not allowed' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeltion' => false,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 1,
                    'titles' => ['Notice 1'],
                    'linkcounts' => [2],
                    'linktexts' => [['Link 1', 'Link 2']],
                    'linkurls' => [['www.example1.com', 'www.example2.com']]
                ]
            ],
            'two basic notices with deletion not allowed' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeletion' => false,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 2,
                    'titles' => ['Notice 1', 'Notice 2'],
                    'linkcounts' => [2, 2],
                    'linktexts' => [['Link 1', 'Link 2'], ['Link 1', 'Link 4']],
                    'linkurls' => [['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com']]
                ]
            ],
            'two basic notices and one notice with expiry in the future' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 3',
                        'content' => 'Notice 3 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 0,
                        'timestart' => time() + HOURSECS,
                        'timeend' => time() + DAYSECS
                    ],
                ],
                'allowdeletion' => false,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 3,
                    'titles' => ['Notice 1', 'Notice 2', 'Notice 3'],
                    'linkcounts' => [2, 2, 2],
                    'linktexts' => [['Link 1', 'Link 2'], ['Link 1', 'Link 4'], ['Link 1', 'Link 4']],
                    'linkurls' => [['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com']]
                ]
            ],
            'two basic notices and one notice with expiry in the past' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 3',
                        'content' => 'Notice 3 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 0,
                        'timestart' => time() - DAYSECS,
                        'timeend' => time() - HOURSECS
                    ],
                ],
                'allowdeletion' => false,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 2,
                    'titles' => ['Notice 1', 'Notice 2'],
                    'linkcounts' => [2, 2],
                    'linktexts' => [['Link 1', 'Link 2'], ['Link 1', 'Link 4']],
                    'linkurls' => [['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com']]
                ]
            ],
            'one basic notice with deletion allowed and cleanup disabled' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeltion' => true,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 1,
                    'titles' => ['Notice 1'],
                    'linkcounts' => [2],
                    'linktexts' => [['Link 1', 'Link 2']],
                    'linkurls' => [['www.example1.com', 'www.example2.com']]
                ]
            ],
            'two basic notices with deletion allowed and cleanup disabled' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeletion' => true,
                'cleanup' => false,
                'expected' => [
                    'noticecount' => 2,
                    'titles' => ['Notice 1', 'Notice 2'],
                    'linkcounts' => [2, 2],
                    'linktexts' => [['Link 1', 'Link 2'], ['Link 1', 'Link 4']],
                    'linkurls' => [['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com']]
                ]
            ],
            'one basic notice with deletion allowed and cleanup enabled' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeltion' => true,
                'cleanup' => true,
                'expected' => [
                    'noticecount' => 1,
                    'titles' => ['Notice 1'],
                    'linkcounts' => [2],
                    'linktexts' => [['Link 1', 'Link 2']],
                    'linkurls' => [['www.example1.com', 'www.example2.com']]
                ]
            ],
            'two basic notices with deletion allowed and cleanup enabled' => [
                'formdata' => [
                    (object)[
                        'title' => 'Notice 1',
                        'content' => 'Notice 1 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 2</a>',
                        'perpetual' => 1,
                    ],
                    (object)[
                        'title' => 'Notice 2',
                        'content' => 'Notice 2 <a href="www.example1.com">Link 1</a> <a href="www.example2.com">Link 4</a>',
                        'perpetual' => 1,
                    ],
                ],
                'allowdeletion' => true,
                'cleanup' => true,
                'expected' => [
                    'noticecount' => 2,
                    'titles' => ['Notice 1', 'Notice 2'],
                    'linkcounts' => [2, 2],
                    'linktexts' => [['Link 1', 'Link 2'], ['Link 1', 'Link 4']],
                    'linkurls' => [['www.example1.com', 'www.example2.com'], ['www.example1.com', 'www.example2.com']]
                ]
            ]
        ];
    }
}
