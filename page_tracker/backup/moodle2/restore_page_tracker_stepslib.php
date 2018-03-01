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
 * @subpackage backup-moodle2
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/*
 * Define all the restore steps that wll be used by the restore_teams_block_task
 */

/**
 * Define the complete page_tracker tracks structure for restore
 */
class restore_page_tracker_block_structure_step extends restore_structure_step {

    protected function define_structure() {

        $paths = array();

        // TODO : Check how to use userinfo.
        $paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('track', '/block/tracks/track');

        return $paths;
    }

    public function process_block($data) {
        global $DB;

        // Nothing to do yet here.
        assert(true);
    }

    /*
     *
     */
    public function process_track($data) {
        global $DB;

        $data  = (object) $data;
        $oldid = $data->id;

        $data->courseid = $this->task->get_courseid();
        $data->pageid = $this->get_mappingid('format_page', $data->pageid);
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('block_page_tracker', $data);
    }
}
