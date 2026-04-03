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
 * Restore steps library.
 *
 * @package     block_page_module
 * @author      Valery Fremaux (valery@gmail.com)
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Define all the restore steps that wll be used by the restore_page_module_block_task
 */

/**
 * Define the complete page_module structure for restore
 */
class restore_page_module_block_structure_step extends restore_structure_step {

    /**
     * Define structure for restoring
     */
    protected function define_structure() {

        $paths = [];

        $paths[] = new restore_path_element('block', '/block', true);
        $paths[] = new restore_path_element('access', '/block/page_module/grants/access');

        return $paths;
    }

    /**
     * Process the block instance record.
     */
    public function process_block($data) {
        return $data;
        // Nothing to do yet here.
    }

    /**
     * Here we have a precedence impossibility : blocks may be restored before course format
     * additions. We need a after_restore post processing.
     * @param object $data
     */
    public function process_access($data) {
        global $DB;

        $data = (object) $data;

        $data->course = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (empty($data->cmid) && !empty($data->pageitemid)) {
            // Restore from older models.
            $data->cmid = $data->pageitemid;
            unset($data->pageitemid);
        }
        $data->cmid = $this->get_mappingid('course_modules', $data->cmid);
        $data->revealtime = $this->apply_date_offset($data->revealtime);
        $data->hidetime = $this->apply_date_offset($data->hidetime);

        $DB->insert_record('block_page_module_access', $data);
    }
}
