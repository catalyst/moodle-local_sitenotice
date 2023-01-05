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


defined('MOODLE_INTERNAL') || die();

use local_sitenotice\table\dismissed_notice;;
use local_sitenotice\table\acknowledged_notice;;
use local_sitenotice\table\all_notices;;

/**
 * Plugin's renderer.
 *
 * @package   local_sitenotice
 * @author    Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_sitenotice_renderer extends plugin_renderer_base {

    /**
     * Render table.
     * @param dismissed_notice $table dismissed notice table
     * @return false|string
     */
    public function render_dismissed_notice(dismissed_notice $table) {
        ob_start();
        $table->out($table->pagesize, false);
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
    }

    /**
     * Render table.
     * @param acknowledged_notice $table acknowledged notice table
     * @return false|string
     */
    public function render_acknowledged_notice(acknowledged_notice $table) {
        ob_start();
        $table->out($table->pagesize, false);
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
    }

    /**
     * Render table.
     * @param all_notices $table all notice table
     * @return false|string
     */
    public function render_all_notices(all_notices $table) {
        ob_start();
        $table->out($table->pagesize, false);
        $o = ob_get_contents();
        ob_end_clean();
        return $o;
    }

}
