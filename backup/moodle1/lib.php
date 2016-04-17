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
 * Provides support for the conversion of moodle1 backup to the moodle2 format
 * Based off of a template @ http://docs.moodle.org/dev/Backup_1.9_conversion_for_developers
 *
 * @package    block_page_module
 * @category   blocks
 * subpackage  backup-moodle1
 * @copyright  2011 Valery Fremaux <valery.fremaux@gmail.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Pagemenu conversion handler
 * there is NO structure change in data storage from 1.9 so parsing flow is linear.
 */
class moodle1_block_page_module_handler extends moodle1_block_handler {

    /** @var moodle1_file_manager */
    protected $fileman = null;

    /** @var int instanceid */
    protected $blockid = null;


    /**
     * Declare the paths in moodle.xml we are able to convert
     *
     * The method returns list of {@link convert_path} instances.
     * For each path returned, the corresponding conversion method must be
     * defined.
     *
     * Note that the path /MOODLE_BACKUP/COURSE/MODULES/MOD/PAGEMENU does not
     * actually exist in the file. The last element with the module name was
     * appended by the moodle1_converter class.
     *
     * @return array of {@link convert_path} instances
     */
    public function get_paths() {
        return array(
            new convert_path(
                'pagemodule', '/MOODLE_BACKUP/BLOCKS/BLOCK/PAGEMODULE',
                array(
                )
            ),
            new convert_path(
                'pagemodule_grants', '/MOODLE_BACKUP/BLOCKS/BLOCK/PAGEMODULE/ACCESSES',
                array(
                )
            ),
            new convert_path(
                'pagemodule_access', '/MOODLE_BACKUP/COURSE/BLOCKS/BLOCK/PAGEMODULE/ACCESSES/ACCESS',
                array(
                )
            ),
       );
    }

    /**
     * This is executed every time we have one /MOODLE_BACKUP/COURSE/MODULES/BLOCKS/PAGEMODULE/ACCESSES
     * data available
     */
    public function process_pagemodule($data) {
        // get the course module id and context id
        $instanceid = $data['id'];

        // create page_module.xml
        $this->open_xml_writer("blocks/blocks/page_module_{$instanceid}/page_module.xml");

        $this->xmlwriter->begin_tag('page_module', array('id' => $instanceid, 'blockname' => 'page_module'));

        foreach ($data as $field => $value) {
            if ($field <> 'id') {
                $this->xmlwriter->full_tag($field, $value);
            }
        }

        return $data;
    }

    /**
     * This is executed when we reach the closing </MOD> tag of our 'pagemenu' path
     */
    public function on_pagemodule_end() {

        // Finish writing page_module.xml and close it.
        $this->xmlwriter->end_tag('page_module');
        $this->close_xml_writer();
    }

    /* Links */
    public function on_pagemodule_grants_start() {
        $this->xmlwriter->begin_tag('grants');
    }

    public function on_page_module_grants_end() {
        $this->xmlwriter->end_tag('grants');
    }

    // Write access record content in one write.
    public function process_pagemodule_access($data) {
        $this->write_xml('access', $data);
    }

}
