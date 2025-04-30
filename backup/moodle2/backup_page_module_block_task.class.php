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
 * Backup task.
 *
 * @package     block_page_module
 * @author      Valery Fremaux (valery@gmail.com)
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/page_module/backup/moodle2/backup_page_module_stepslib.php'); // We have structure steps.

/**
 * Specialised backup task for the page_module block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class backup_page_module_block_task extends backup_block_task {

    /**
     * Settings for backup.
     */
    protected function define_my_settings() {
        return;
    }

    /**
     * backup steps
     */
    protected function define_my_steps() {
        // Page_module has one structure step.
        $this->add_step(new backup_page_module_block_structure_step('page_module_structure', 'page_module.xml'));
    }

    /**
     * No associated fileareas.
     */
    public function get_fileareas() {
        return [];
    }

    /**
     *  No special handling of configdata.
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * No special encoding of links.
     */
    public static function encode_content_links($content) {
        return $content;
    }
}
