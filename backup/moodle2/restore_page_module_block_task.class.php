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
 * Restore task.
 *
 * @package     block_page_module
 * @author      Valery Fremaux (valery@gmail.com)
 * @copyright   2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/page_module/backup/moodle2/restore_page_module_stepslib.php'); // We have structure steps.

/**
 * Specialised restore task for the page_module block
 * (has own DB structures to backup)
 *
 * phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class restore_page_module_block_task extends restore_block_task {

    /**
     * No settings.
     */
    protected function define_my_settings() {
        return [];
    }

    /**
     * Block page_module has one structure step.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_page_module_block_structure_step('page_module_structure', 'page_module.xml'));
    }

    /**
     * No associated fileareas.
     */
    public function get_fileareas() {
        return [];
    }

    /**
     * No special handling of configdata.
     */
    public function get_configdata_encoded_attributes() {
        return [];
    }

    /**
     * No content decoding.
     */
    public static function define_decode_contents() {
        return [];
    }

    /**
     * No decoding rules.
     */
    public static function define_decode_rules() {
        return [];
    }

    /**
     * Each block will be responsible for his own remapping in is associated pageid.
     */
    public function after_restore() {
        global $DB;

        $courseid = $this->get_courseid();
        $blockid = $this->get_blockid();
        $oldblockid = $this->get_old_blockid();

        // These are fake blocks that can be cached in backup.
        if (!$blockid) {
            return;
        }

        // Get the page item restored for the old block reference (mapping provided by the format_page restore).
        $pageitem = false;
        if ($itemid = $this->get_mappingid('format_page_item_block', $oldblockid)) {
            $pageitem = $DB->get_record('format_page_items', ['id' => $itemid]);
        }

        if (!$pageitem) {
            // Fallback : search in the restored pages only, in items not yet remapped.
            $sql = "
                SELECT
                    fpi.*
                FROM
                    {format_page_items} fpi
                JOIN
                    {format_page} f
                ON
                    fpi.pageid = f.id
                JOIN
                    {backup_ids_temp} bi
                ON
                    bi.newitemid = f.id AND
                    bi.backupid = ? AND
                    bi.itemname = 'format_page'
                WHERE
                    f.courseid = ? AND
                    fpi.blockinstance = ? AND
                    fpi.cmid != 0
            ";
            $params = [$this->get_restoreid(), $courseid, $oldblockid];
            $pageitem = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        }

        if ($pageitem) {
            $pageitem->blockinstance = $blockid;
            // Cmid is NOT NULL in DB : 0 marks a failed remapping (course module not restored).
            $pageitem->cmid = empty($pageitem->cmid) ? 0 : $this->get_mappingid('course_module', $pageitem->cmid, 0);
            $DB->update_record('format_page_items', $pageitem);

            $bi = $DB->get_record('block_instances', ['id' => $blockid]);

            // Adjust the serialized configdata->cmid to the actualized course module.
            // Get the configdata.

            // Extract configdata.
            $config = unserialize(base64_decode($bi->configdata ?? ''));
            if (is_object($config) && !empty($config->cmid)) {
                $config->cmid = $this->get_mappingid('course_module', $config->cmid);
                // Serialize back the configdata.
                $bi->configdata = base64_encode(serialize($config));
            }

            // Remap the subpage.
            $subpagepattern = $bi->subpagepattern ?? '';
            if (strpos($subpagepattern, 'page-') === 0) {
                $oldpageid = str_replace('page-', '', $subpagepattern);
                $newpageid = $this->get_mappingid('format_page', $oldpageid, $pageitem->pageid);
                $bi->subpagepattern = 'page-'.$newpageid;
                $DB->update_record('block_instances', $bi);
            } else if (strpos($subpagepattern, 'allpages-') === 0) {
                $oldpageid = str_replace('allpages-', '', $subpagepattern);
                $newpageid = $this->get_mappingid('format_page', $oldpageid, $pageitem->pageid);
                $bi->subpagepattern = 'allpages-'.$newpageid;
                $DB->update_record('block_instances', $bi);
            } else {
                // Old all pages case. Do not let NULL subpagepatterns in the DB.
                $bi->subpagepattern = 'allpages-'.$pageitem->pageid;
                $DB->update_record('block_instances', $bi);
            }

            // Get old positions and update default region wich is the only positionning
            // reference.
            $params = ['blockinstanceid' => $blockid];
            $positions = $DB->get_records('block_positions', $params);
            // Should be essentially be one...
            if ($positions) {
                $position = array_shift($positions);
                $DB->set_field('block_instances', 'defaultregion', $position->region, ['id' => $blockid]);
                $DB->set_field('block_instances', 'defaultweight', $position->weight, ['id' => $blockid]);
            }

            // Remove all positions for paged blocks.
            $DB->delete_records('block_positions', ['blockinstanceid' => $blockid]);

        } else {
            $this->get_logger()->process("Failed in finding pageitem for block $oldblockid. ", backup::LOG_ERROR);
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

