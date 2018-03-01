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
 * @package    block_page_tracker
 * @category   blocks
 * @copyright  2003 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');

function xmldb_block_page_tracker_upgrade($oldversion = 0) {
    global $DB;

    $result = true;

    $dbman = $DB->get_manager();

    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one. Please, delete
     * this comment lines once this file start handling proper
     * upgrade code.
     */

    if ($oldversion < 2016101105) {
        // Define table block_page_tracker to be created.
        $table = new xmldb_table('block_page_tracker');

        // Adding fields to table.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('pageid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, '0');
        $table->add_field('firsttimeviewed', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('lasttimeviewed', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        $table->add_field('views', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');

        // Adding keys to table.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table.
        $table->add_index('ix_unique', XMLDB_INDEX_UNIQUE, array('courseid', 'pageid', 'userid'));

        // Conditionally launch create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Processes logs once to mark students tracks.
        // This can raise load hugely.
        // Let cli script do catch_tracks on big sites.

        // Page_module savepoint reached.
        upgrade_block_savepoint(true, 2016101105, 'page_tracker');
    }

    return $result;
}

function catch_tracks($verbose = false) {
    global $DB;

    $logmanager = get_log_manager();
    $readers = $logmanager->get_readers('\core\log\sql_select_reader');
    $reader = reset($readers);

    if ($reader instanceof \logstore_standard\log\store) {
        $courseparm = 'courseid';
        $fields = 'CONCAT('.$courseparm.', \':\', objectid, \':\', userid) as trackid';
        $params = array('component' => 'format_page' , 'action' => 'viewed');
        $count = $DB->count_records('logstore_standard_log', $params);
        $counter = 0;
        $donemem = 0;
        if ($verbose) {
            echo "\nStarting:\n";
        }
        if ($rs = $DB->get_recordset('logstore_standard_log', $params, 'id', $fields)) {
            foreach ($rs as $record) {
                list($courseid, $pageid, $userid) = explode(':', $record->trackid);
                punch_track($courseid, $pageid, $userid);
                $counter++;
                if ($verbose) {
                    $done = ($count) ? round($counter * 100 / $count) : 100;
                    if ($done != $donemem) {
                        echo "\r{$done} %  ";
                        $donemem = $done;
                    }
                }
            }
            if ($verbose) {
                echo "\n";
            }
            $rs->close();
        }
    } else if ($reader instanceof \logstore_legacy\log\store) {
        $courseparm = 'course';
        $fields = 'CONCAT(info, \':\', userid) as trackid';
        $params = array('action' => 'viewpage');
        $count = $DB->count_records('log', $params);
        $counter = 0;
        if ($verbose) {
            echo "\nStarting:\n";
        }
        if ($DB->get_recordset('log', $params, 'id', $fields)) {
            foreach ($rs as $record) {
                list($courseid, $pageid, $userid) = explode(':', $record->trackid);
                punch_track($courseid, $pageid, $userid);
                $counter++;
                if ($verbose) {
                    $done = ($count) ? round($counter * 100 / $count) : 100;
                    if ($done != $donemem) {
                        echo "\r{$done} %  ";
                        $donemem = $done;
                    }
                }
            }
            if ($verbose) {
                echo "\n";
            }
            $rs->close();
        }
    }
}
