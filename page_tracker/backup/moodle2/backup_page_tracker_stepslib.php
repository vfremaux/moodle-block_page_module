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
 * @package block_page_tracker
 * @category blocks
 * @subpackage backup-moodle2
 * @copyright 2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Define all the backup steps that wll be used by the backup_page_tracker_block_task
 */

/**
 * Define the complete page_tracker tracks structure.
 */
class backup_page_tracker_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // TODO : check how to get userinfo information.

        // Get the block.
        $block = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));

        // Extract configdata.
        $config = unserialize(base64_decode($block->configdata));

        // Define each element separated.

        $tracks = new backup_nested_element('tracks');
        $track = new backup_nested_element('track', array('id'), array('courseid', 'pageid', 'userid', 'firsttimeviewed',
                                          'lasttimeviewed', 'views'));

        // Build the tree.

        $tracks->add_child($track);

        // Define sources.

        // TODO : check if user info is required or not.
        $track->set_source_table('block_page_tracker', array('courseid' => backup::VAR_COURSEID));

        // ID Annotations (none).
        $track->annotate_ids('user', 'userid');

        // Annotations (files).

        // Return the root element (page_module), wrapped into standard block structure.
        return $this->prepare_block_structure($tracks);
    }
}
