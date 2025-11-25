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

        // Get the old block reference.
        $sql = "
            SELECT
                fpi.*
            FROM
                {format_page_items} fpi,
                {format_page} f
            WHERE
                fpi.pageid = f.id AND
                f.courseid = ? AND
                fpi.blockinstance = ?
        ";

        if ($pageitem = $DB->get_record_sql($sql, [$courseid, $oldblockid])) {
            $pageitem->blockinstance = $blockid;
            $oldcmid = $pageitem->cmid;
            if ($oldcmid != 0) {
                // This is a core fault : the backup mapping uses "course_module" and NOT "course_modules" as table reference.
                $newcmid = $this->get_mappingid('course_module', $oldcmid);
                $pageitem->cmid = $newcmid;
                debug_trace("CourseModule remapped pageitem ".json_encode($pageitem)." cmid from {$oldcmid} to {$newcmid} ", TRACE_DEBUG);
                $DB->update_record('format_page_items', $pageitem);

                $bi = $DB->get_record('block_instances', ['id' => $blockid]);

                // Adjust the serialized configdata->cmid to the actualized course module.
                // Get the configdata.

                // Extract configdata.
                $config = unserialize(base64_decode($bi->configdata));
                // Set array of used rss feeds.
                // TODO check this, not sure course modules are stored in backup mapping tables as this.
                $config->cmid = $newcmid;
                // Serialize back the configdata.
                $bi->configdata = base64_encode(serialize($config));

                // Remap the subpage.
                $oldpageid = str_replace('page-', '', $bi->subpagepattern);
                $newpageid = $this->get_mappingid('format_page', $oldpageid);
                $bi->subpagepattern = 'page-'.$newpageid;
                $DB->update_record('block_instances', $bi);
                debug_trace("CourseModule remapped block in new page {$newpageid} ", TRACE_DEBUG);
            }

            $params = ['blockinstanceid' => $blockid, 'contextid' => $bi->parentcontextid];
            if ($DB->get_field('block_positions', 'subpage', $params)) {
                $DB->set_field('block_positions', 'subpage', 'page-'.$newpageid, $params);
                debug_trace("CourseModule remapped block position in new page {$newpageid} ", TRACE_DEBUG);
            }

        } else {
            $this->get_logger()->process("Failed in finding pageitem for block (old) $oldblockid (new: $blockid). ", backup::LOG_ERROR);
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
        $mapping = restore_dbops::get_backup_ids_record($this->plan->get_restoreid(), $itemname, $oldid);
        return $mapping;
    }
}

