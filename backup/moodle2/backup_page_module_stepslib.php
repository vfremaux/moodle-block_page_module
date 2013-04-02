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
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright 2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Define all the backup steps that wll be used by the backup_page_module_block_task
 */

/**
 * Define the complete forum structure for backup, with file and id annotations
 */
class backup_page_module_block_structure_step extends backup_block_structure_step {

    protected function define_structure() {
        global $DB;

        // Get the block
        $block = $DB->get_record('block_instances', array('id' => $this->task->get_blockid()));
        // Extract configdata
        $config = unserialize(base64_decode($block->configdata));

        // Define each element separated

        $pagemodule = new backup_nested_element('pagemodule', array('id'), array('pageid', 'cmid', 'blockinstance'));

        $grants = new backup_nested_element('grants');

        $access = new backup_nested_element('access', array('id'), array(
            'userid', 'pageitemid', 'hidden', 'revealtime', 'hidetime'));

        // Build the tree

        $pagemodule->add_child($grants);
        $grants->add_child($access);

        // Define sources

		$instances = $DB->get_records('format_page_items', array('blockinstance' => $block->id));
        $pagemodule->set_source_array($instances);
        $access->set_source_table('block_page_module_access', array('pageitemid' => backup::VAR_PARENTID));

        // Annotations (none)
        $access->annotate_ids('user', 'userid');

        // Return the root element (page_module), wrapped into standard block structure
        return $this->prepare_block_structure($pagemodule);
    }
}
