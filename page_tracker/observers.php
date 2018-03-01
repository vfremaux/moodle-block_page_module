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
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');

/**
 * The standard observer object
 */
class block_page_tracker_event_observer {

    /**
     * Triggered when a course is deleted
     * ensure tracking data are removed
     */
    public static function on_course_deleted($e) {
        global $DB;

        $format = $DB->get_field('course', 'format', array('id' => $e->courseid));
        if ($format != 'page') {
            return;
        }

        $DB->delete_records('block_page_tracker', array('courseid' => $e->courseid));
    }

    /**
     * Triggered when a course is starting reset
     * Role assignments are still in DB and can be scanned
     * for tracking preprocessing
     */
    public static function on_course_reset_started($e) {
        global $DB;

        $format = $DB->get_field('course', 'format', array('id' => $e->courseid));
        if ($format != 'page') {
            return;
        }

        $coursecontext = context_course::instance($e->courseid);
        $rolestoreset = @$e->other['reset_options']['unenrol_users'];

        if (!empty($rolestoreset)) {
            foreach ($rolestoreset as $roleid) {
                $role = new StdClass;
                $role->id = $roleid;
                $roleassigns = get_users_from_role_on_context($role, $coursecontext);
                if (!empty($roleassigns)) {
                    foreach ($roleassigns as $ra) {
                        $DB->delete_records('block_page_tracker', array('courseid' => $e->courseid, 'userid' => $ra->userid));
                    }
                }
            }
        }
    }

    /**
     * Triggered when a role is unassigned in the course
     * Should check this is the leader role, and let an associated team unleaded
     */
    public static function on_course_page_viewed($e) {
        punch_track($e->courseid, $e->objectid, $e->userid);
    }
}