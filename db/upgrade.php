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
 * @package    block_page_module
 * @category   blocks
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

function xmldb_block_page_module_upgrade($oldversion = 0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one. Please, delete
     * this comment lines once this file start handling proper
     * upgrade code.
     */

    if ($oldversion < 2013020700) {
        // Define table block_page_module_access to be renamed to block_page_module_access.
        $table = new xmldb_table('page_module_access');

        // Launch rename table for block_page_module_access.
        $dbman->rename_table($table, 'block_page_module_access');

        // page_module savepoint reached.
        upgrade_block_savepoint(true, 2013020700, 'page_module');
    }

    if ($oldversion < 2016100500) {
        // Transfer settings from global to block's scope.
        if (isset($CFG->showactivityname)) {
            set_config('showactivityname', $CFG->showactivityname, 'block_page_module');
            set_config('pageindividualisationfeature', $CFG->pageindividualisationfeature, 'block_page_module');
            set_config('individualizewithtimes', $CFG->showactivityname, 'block_page_module');
            // Remove keys from global config.
            set_config('showactivityname', null);
            set_config('pageindividualisationfeature', null);
            set_config('individualizewithtimes', null);
        }

        // page_module savepoint reached.
        upgrade_block_savepoint(true, 2016100500, 'page_module');
    }

    return $result;
}