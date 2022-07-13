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
 * Steps definitions related to local_sitenotice.
 *
 * @package    local_sitenotice
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Site notice step definitions.
 *
 * @package    local_sitenotice
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Cameron Ball <cameron@cameron1729.xyz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_sitenotice extends behat_base {

    /**
     * Creates new notices.
     *
     * @Given the following site notices exist
     * @param TableNode $noticedata The notices to be created.
     */
    public function the_following_site_notices_exist(TableNode $noticedata) {
        global $DB;

        // Add the discussions to the relevant forum.
        foreach ($noticedata->getHash() as $noticeinfo) {
            $now = time();
            $noticeinfo['cohorts'] = $noticeinfo['cohorts'] ?? 0;
            $noticeinfo['reqack'] = $noticeinfo['reqack'] ?? 0;
            $noticeinfo['enabled'] = $noticeinfo['enabled'] ?? 1;
            $noticeinfo['resetinterval'] = $noticeinfo['resetinterval'] ?? 0;
            $noticeinfo['usermodified'] = $noticeinfo['usermodified'] ?? 2;
            $noticeinfo['timecreated'] = $noticeinfo['timecreated'] ?? $now;
            $noticeinfo['timemodified'] = $noticeinfo['timemodified'] ?? $now;
            $noticeinfo['timestart'] = $noticeinfo['timestart'] ?? 0;
            $noticeinfo['timeend'] = $noticeinfo['timeend'] ?? 0;
            $noticeinfo['forcelogout'] = $noticeinfo['forcelogout'] ?? 0;
            $DB->insert_record('local_sitenotice', $noticeinfo);
        }
    }
}
