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

require_once($CFG->dirroot . '/blocks/page_module/backup/moodle2/restore_page_module_stepslib.php'); // We have structure steps

/**
 * Specialised restore task for the page_module block
 * (has own DB structures to backup)
 *
 * TODO: Finish phpdocs
 */
class restore_page_module_block_task extends restore_block_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        // block page_module has one structure step
        $this->add_step(new restore_page_module_block_structure_step('page_module_structure', 'page_module.xml'));
    }

    public function get_fileareas() {
        return array(); // No associated fileareas
    }

    public function get_configdata_encoded_attributes() {
        return array(); // No special handling of configdata
    }

    static public function define_decode_contents() {
        return array();
    }

    static public function define_decode_rules() {
        return array();
    }
    
	// each block will be responsible for his own remapping in is associated pageid    	    	
    public function after_restore(){
		global $DB;
		
    	$courseid = $this->get_courseid();
    	$blockid = $this->get_blockid();
    	$oldblockid = $this->get_old_blockid();

		// these are fake blocks that can be cached in backup
		if (!$blockid) return; 

		// get the old block reference    	
    	$sql = "
    		SELECT
    			fpi.*
    		FROM
    			{format_page_items} fpi,
    			{format_page} f
    		WHERE
    			f.courseid = ? AND
    			fpi.pageid = f.id AND
    			fpi.blockinstance = ?
    	";

		if ($pageitem = $DB->get_record_sql($sql, array($courseid, $oldblockid))){
			$pageitem->blockinstance = $blockid;
			if (@$pageitem->cmid){
        		$pageitem->cmid = $this->get_mappingid('course_module', $pageitem->cmid);
			}
			$DB->update_record('format_page_items', $pageitem);
			
			$bi = $DB->get_record('block_instances', array('id' => $blockid));

	        // Adjust the serialized configdata->cmid to the actualized course module
	        // Get the configdata

	        // Extract configdata
	        $config = unserialize(base64_decode($bi->configdata));
	        // Set array of used rss feeds
	        // TODO check this, not sure course modules are stored in backup mapping tables as this
	        $config->cmid = $this->get_mappingid('course_module', $config->cmid);
	        // Serialize back the configdata
	        $bi->configdata = base64_encode(serialize($config));
	        
	        // remap the subpage
	        $oldpageid = str_replace('page-', '', $bi->subpagepattern);
	        $newpageid = $this->get_mappingid('format_page', $oldpageid);
	        $bi->subpagepattern = 'page-'.$newpageid;
	        $DB->update_record('block_instances', $bi);

			if ($subpage = $DB->get_field('block_positions', 'subpage', array('blockinstanceid' => $blockid, 'contextid' => $bi->parentcontextid))){
				$DB->set_field('block_positions', 'subpage', 'page-'.$newpageid, array('blockinstanceid' => $blockid, 'contextid' => $bi->parentcontextid));
			}

		} else {
			$this->plan->log("Failed in finding pageitem for page_module block $oldblockid. ", backup::LOG_ERROR);    			
		}		
    }

    /**
     * Return the new id of a mapping for the given itemname
     *
     * @param string $itemname the type of item
     * @param int $oldid the item ID from the backup
     * @param mixed $ifnotfound what to return if $oldid wasnt found. Defaults to false
     */
    public function get_mappingid($itemname, $oldid, $ifnotfound = false) {
        $mapping = $this->get_mapping($itemname, $oldid);
        return $mapping ? $mapping->newitemid : $ifnotfound;
    }

    /**
     * Return the complete mapping from the given itemname, itemid
     */
    public function get_mapping($itemname, $oldid) {
        return restore_dbops::get_backup_ids_record($this->plan->get_restoreid(), $itemname, $oldid);
    }

}

