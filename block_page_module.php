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
 * Page Module block
 *
 * @package    block_page_module
 * @author Mark Nielsen, Valery Fremaux
 * @copyright       2016 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @todo Could have external methods for caching cm, module, module instace records
 * Warning: $this->instance->id is actually a
 * format_page_item record ID, so DO NOT USE
 * unless you know what your doing.
 */
defined('MOODLE_INTERNAL') || die();

use format_page\course_page;

require_once($CFG->dirroot.'/blocks/page_module/lib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once($CFG->dirroot.'/course/format/page/classes/page.class.php');

/**
 * Block class definition
 * phpcs:disable moodle.Commenting.ValidTags.Invalid
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
 */
class block_page_module extends block_base {

    /** @var Hide block header or not */
    public $hideheader = true;

    /** @var a cache for course modinfo */
    protected $modinfo;

    /** @var a cache for coursemodinfo */
    protected $coursemodinfo;

    /** @var a local cache for completion info */
    protected $completioninfo;

    /**
     * Sets default title and version.
     *
     * @return void
     */
    public function init() {
        global $COURSE, $CFG;

        $this->completioninfo = new completion_info($COURSE);

        $this->title = get_string('blockname', 'block_page_module');

        if (empty($CFG->upgraderunning)) {
            $this->coursemodinfo = get_fast_modinfo($COURSE);
        }
    }

    /**
     * Block instance specialisation.
     */
    public function specialization() {
        global $DB;

        if (empty($this->config->cmid) ||
                !$DB->record_exists('course_modules', ['id' => $this->config->cmid])) {
            if (!isset($this->config)) {
                $this->config = new StdClass();
            }
            $this->config->cmid = 0;
        } else {
            $result = block_page_module_init($this->config->cmid);

            if ($result !== false && is_array($result)) {

                // Get all of the variables out.
                list($this->cm,     $this->module, $this->moduleinstance,
                     $this->course, $this->coursepage,   $this->baseurl) = $result;

                if (!empty($this->config->showactivityname)) {
                    $this->title = format_string($this->moduleinstance->name);
                }
            }
        }
    }

    /**
     * Defines which page formats can host a block instance
     */
    public function applicable_formats() {
        // Default case: the block can be used in page format courses only.
        return ['all' => false, 'course-view-page' => true];
    }

    /**
     *
     */
    public function has_config() {
        return true;
    }

    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {

        if (!isset($data->showactivityname)) {
            $data->showactivityname = 0;
        }
        $config = clone($data);
        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * Overrides core one to add completion data in content structures.
     * The page module adds some specific block control.
     * @param object $output
     */
    public function get_content_for_output($output) {
        global $COURSE, $SESSION;

        $coursecontext = context_course::instance($COURSE->id);

        $result = block_page_module_init($this->config->cmid);

        if ($result !== false && is_array($result)) {
            // Get all of the variables out.
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->coursepage,   $this->baseurl) = $result;
        }

        $debug = optional_param('debug', '', PARAM_INT);
        if (empty($this->cm)) {
            if ($debug) {
                debug_trace("Lost module. empty CM from id [$this->config->cmid} ", TRACE_DEBUG);
                echo "Lost module. empty CM from id [$this->config->cmid} ";
            }
            $SESSION->mayneedpagesectionfix = $COURSE->id;
            // Lost module.
            return;
        }

        $bc = parent::get_content_for_output($output);

        if (empty($bc)) {
            if ($debug) {
                debug_trace("Lost module. empty \$bc ", TRACE_DEBUG_FINE);
                echo "Lost module. empty \$bc ";
            }
            $SESSION->mayneedpagesectionfix = $COURSE->id;
            return;
        }

        if (array_key_exists($this->cm->id, $this->coursemodinfo->cms)) {
            $this->modinfo = $this->coursemodinfo->cms[$this->cm->id];
            $bc->completion = new StdClass();
            $bc->completion->mod = $this->modinfo;
            $bc->completion->completioninfo = $this->completioninfo;
        }

        $bc->modname = $this->module->name;

        // Mark alternate view in block's classes.
        $view = $this->config->view ?? '';
        if (!empty($view) && $view != 'default') {
            $bc->add_class('is-alternate-view');
            $bc->add_class('alternate-view-'.$this->config->view);
        }

        /*
         * $subpagepattern may hold the pageid
         * Bloc protected pages for page module editing extensions here
         */
        if ($COURSE->format == 'page') {
            $pageid = str_replace('page-', '', $this->instance->subpagepattern);
            $page = course_page::get($pageid);
            // Let unpaged pass as "all pages blocks".
            if (empty($page)) {
                $page = course_page::get_current_page($COURSE->id);
            }
            $context = context::instance_by_id($this->instance->parentcontextid);
            if ($page->protected && !has_capability('format/page:editprotectedpages', $context)) {
                return $bc;
            }
        }

        // Add some additional controls.
        if ($this->page->user_is_editing() && has_capability('moodle/course:manageactivities', $coursecontext)) {
            $str = get_string('editmodule', 'block_page_module');
            $url = new moodle_url('/course/modedit.php', ['update' => $this->config->cmid]);
            $icon = new pix_icon('t/edit', $str, 'moodle', ['class' => 'iconsmall', 'title' => '']);
            $attributes = ['class' => 'editing_edit'];
            $bc->controls[] = new action_menu_link_secondary($url, $icon, $str, $attributes);

            $str = get_string('copymodule', 'block_page_module');
            $params = [
                'id' => $COURSE->id,
                'sesskey' => sesskey(),
                'duplicate' => $this->config->cmid,
                'section' => $page->get_section(), // Carefull to that.
                'insertinpage' => $page->id,
            ];
            $url = new moodle_url('/course/format/page/mod.php', $params);
            $icon = new pix_icon('t/copy', $str, 'moodle', ['class' => 'iconsmall', 'title' => '']);
            $attributes = ['class' => 'editing_edit'];
            $bc->controls[] = new action_menu_link_secondary($url, $icon, $str, $attributes);

            $views = $this->get_views();
            if (count($views) > 1) {
                $str = get_string('changeview', 'block_page_module');
                $params = ['id' => $COURSE->id, 'instance' => $this->instance->id];
                $url = new moodle_url('/blocks/page_module/chooseview.php', $params);
                $icon = new pix_icon('chooseview', $str, 'block_page_module', ['class' => 'iconsmall', 'title' => '']);
                $attributes = ['class' => 'editing_changeview'];
                $bc->controls[] = new action_menu_link_secondary($url, $icon, $str, $attributes);
            }
        }

        return $bc;
    }

    /**
     * Given a course module ID, this block
     * will display the module's pageitem hook.
     *
     * @return object
     */
    public function get_content() {
        global $USER, $COURSE, $CFG;

        // This contains an alterated course renderer embedded.
        $renderer = $this->page->get_renderer('format_page');
        $debug = optional_param('debug', false, PARAM_BOOL);

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance) || !$this->config->cmid) {
            return $this->content;
        }

        // Gets all of our variables and caches result.
        $result = block_page_module_init($this->config->cmid);

        if ($result !== false && is_array($result)) {

            // Get all of the variables out.
            list($this->cm,
                 $this->module,
                 $this->moduleinstance,
                 $this->course,
                 $this->coursepage,
                 $this->baseurl) = $result;

            $coursemodinfo = get_fast_modinfo($this->course);
            try {
                $mod = $coursemodinfo->get_cm($this->config->cmid);
            } catch (Exception $ex) {
                // Second try, after course modinfo rebuild.
                rebuild_course_cache($COURSE->id);
                try {
                    $mod = $coursemodinfo->get_cm($this->config->cmid);
                } catch (Exception $ex) {
                    $context = context_course::instance($COURSE->id);
                    if (has_capability('moodle/course:manageactivities', $context)) {
                        $this->content->text = get_string('internalerrorlostmodule', 'block_page_module');
                    } else {
                        $msg = "course module not found {$this->config->cmid} when getting page_module content ";
                        // debug_trace($msg, TRACE_DEBUG_FINE);
                        $this->content->text = null;
                    }
                    return $this->content;
                }
            }

            /*
             * Check module visibility.
             * @see patch in course/format/page/__patch/lib/modinfolib.php
             *
             * Dynamically set the operational section id in the module in the context
             * it is used in the page module instance.
             */
            $pagesection = $this->coursepage->get_pagesection();
            if ($pagesection) {
                $mod->section = $pagesection->id;
            }

            /*
            $modulevisible = $this->instance->visible
                    && $mod->uservisible
                            && $this->has_user_access($USER->id, $this->cm)
                                    && empty($mod->availableinfo);
            */

            $modulevisible = $this->instance->visible;

            $modulevisiblestatic = $this->instance->visible && $mod->visible;
            $debug = optional_param('debug', false, PARAM_BOOL);

            if ($debug && $CFG->debug > DEBUG_NORMAL) {
                echo '<pre>';
                echo "Block instance {$this->instance->id} is visible : {$this->instance->visible}\n";
                echo "Module instance {$this->cm->id} is visible (dynamic) : {$mod->uservisible}\n";
                echo "Module instance {$this->cm->id} is visible (static) : {$modulevisiblestatic}\n";
                echo "User has access (format page specific) : ".$this->has_user_access($USER->id, $this->cm)."\n";
                echo "Availability restrictions : " . $mod->availableinfo."\n";
                echo "<b>Resulting :</b> " . $modulevisible."\n";
                echo '</pre>';
            }

            $coursecontext = context_course::instance($this->course->id);

            if (!$modulevisiblestatic && !has_capability('moodle/course:viewhiddenactivities', $coursecontext)) {
                $this->content->text = '';
                return '';
            }

            // Default: set title to instance name.
            $this->title = format_string($this->moduleinstance->name);

            // Calling hook, set_instance, and passing $this by reference.

            $displayoptions = [];
            if (!empty($this->config->view)) {
                // This calls an alternate view of a CM.
                if ($debug && $CFG->debug = DEBUG_DEVELOPER) {
                    echo "Getting view {$this->config->view} for {$this->title} ";
                }
                block_page_module_hook($this->config->view, 'set_instance', [&$this]);
            } else {
                // This calls the "standard" view of a CM.
                if ($debug && $CFG->debug = DEBUG_DEVELOPER) {
                    echo "Getting default view for {$this->title} ";
                }
                block_page_module_hook($this->module->name.'/default', 'set_instance', [&$this]);
            }

            // No hook could make the content, probably not pageable module, so use the standard cm rendering.
            if (empty($this->content->text) && array_key_exists($this->config->cmid, $this->coursemodinfo->cms)) {
                if ($debug && $CFG->debug = DEBUG_DEVELOPER) {
                    echo "Printing cm in standard way as last chance for {$this->title} ";
                }
                $cm = $this->coursemodinfo->cms[$this->cm->id];
                $this->content->text .= $renderer->print_cm($COURSE, $cm, $displayoptions);
            }

            // Important : next instruction REPLACES content. Not appending.
            if (array_key_exists($this->cm->id, $this->coursemodinfo->cms)) {
                $cm = $this->coursemodinfo->cms[$this->cm->id];
                // M4 : completion is handled inside cm template.
                $this->content->text = $this->content->text;
            }
        }

        if (!$result && empty($this->content->text)) {
            $this->content->text = get_string('displayerror', 'block_page_module');
        }
        return $this->content;
    }

    /**
     * Modify id and class to suit this block
     *
     * @return array
     */
    public function html_attributes() {
        global $COURSE;

        $result = block_page_module_init($this->config->cmid);

        if ($result !== false && is_array($result)) {

            // Get all of the variables out.
            list($this->cm,
                 $this->module,
                 $this->moduleinstance,
                 $this->course,
                 $this->coursepage,
                 $this->baseurl) = $result;
        }

        $extraclasses = '';
        if ($COURSE->format == 'page' && $this->page->user_is_editing()) {
            $pageid = str_replace('page-', '', $this->instance->subpagepattern);
            if (!$pageid) {
                // This is a "all pages block".
                $extraclasses = ' allpages';
            }
        }

        return [
            'id' => 'inst'.$this->instance->id,
            'class' => 'block block_'. $this->name().' mod-'.($this->module->name ?? '').$extraclasses,
        ];
    }

    /**
     * Default return is false - header will be shown
     *
     * @return boolean
     */
    public function hide_header() {
        return $this->hideheader;
    }

    /**
     * Has instance config
     *
     * @return boolean
     **/
    public function instance_allow_config() {
        return true;
    }

    /**
     * Page module represents activities and resources,
     * use course module availability to hide.
     */
    public function instance_can_be_hidden() {
        return false;
    }

    /**
     * Allow multiple instances of each block
     *
     * @return boolean
     */
    public function instance_allow_multiple() {
        return true;
    }

    /**
     * checks if a user id has an individualisation mark for this module. the marker is a negative "hiding' mark.
     * @param int $userid the user ID
     * @param object $cm the course module. If not provided, takes the current Course Module ID in local configuration.
     */
    public function has_user_access($userid, $cm = null) {
        global $DB;

        if (is_null($cm)) {
            $cm = new StdClass;
            $cm->id = $this->config->cmid;
        }

        $hidden = $DB->get_field('block_page_module_access', 'hidden', ['userid' => $userid, 'pageitemid' => $cm->id]);
        return !$hidden;
    }

    /**
     * The cron handles time schedule switching from individualization settings
     * This first implementation scans for active switch times
     */
    public function cron() {
        global $DB;

        $now = time();
        $select = ' revealtime > ? AND revealtime != 0 ';
        if ($revealswitches = $DB->get_records_select('block_page_module_access', $select, [$now])) {
            foreach ($revealswitches as $sw) {
                $sw->revealtime = 0;
                $sw->hidden = 0;
                $DB->update_record('block_page_module_access', $sw);
            }
        }
        $select = ' hidetime > ? AND hidetime != 0 ';
        if ($hideswitches = $DB->get_records_select('block_page_module_access', $select, [$now])) {
            foreach ($hideswitches as $sw) {
                $sw->hidetime = 0;
                $sw->hidden = 1;
                $DB->update_record('block_page_module_access', $sw);
            }
        }
    }

    /**
     * Checks for available pageitem views. Views are located in the format page in the "plugins" directory,
     * or directly in moodle activity modiles implementation as a pageitem_<view>.php file.
     *
     * @return an array of viewname => viewcontent
     */
    public function get_views() {
        global $DB, $CFG;

        $viewlist = ['default' => get_string('linkview', 'block_page_module')];

        $moduleid = $DB->get_field('course_modules', 'module', ['id' => $this->config->cmid]);
        $modname = $DB->get_field('modules', 'name', ['id' => $moduleid]);

        if (file_exists($CFG->dirroot.'/course/format/page/plugins/'.$modname.'.php')) {
            $viewlist[$modname] = get_string('pluginname', $modname);
        }

        if ($views = glob($CFG->dirroot.'/course/format/page/plugins/'.$modname.'_*.php')) {
            foreach ($views as $view) {
                $parts = pathinfo($view);
                $filename = $parts['filename'];
                if ($filename == 'page_item_default') {
                    continue;
                }
                if ($filename == $modname) {
                    $viewlist[$modname] = get_string('defaultpageview', 'block_page_module');
                } else {
                    $viewname = str_replace($modname.'_', '', $filename);
                    $lastdbg = $CFG->debug;
                    $CFG->debug = false;
                    $str = get_string('view_'.$filename, 'format_page');
                    if (preg_match('/\[\[.*\]\]/', $str)) {
                        $str = get_string('view_'.$viewname, $modname);
                    }
                    $CFG->debug = $lastdbg;
                    $viewlist["$modname/$viewname"] = $str;
                }
            }
        } else {
            /*
             * Last try : for non standardly handled modules, check in plugin directory.
             * We seek for page_item.php file or page_item_wviewname>.php
             */
            if ($views = glob($CFG->dirroot.'/mod/'.$modname.'/pageitem*.php')) {
                foreach ($views as $view) {
                    $parts = pathinfo($view);
                    $filename = $parts['filename'];
                    if ($filename == 'pageitem') {
                        $viewlist[$modname.'/default'] = get_string('defaultpageview', 'block_page_module');
                    } else {
                        $viewname = str_replace('pageitem_', '', $filename);
                        $lastdbg = $CFG->debug;
                        $CFG->debug = false;
                        $str = get_string('view_'.$filename, $modname);
                        if (preg_match('/\[\[.*\]\]/', $str)) {
                            $str = get_string('view_'.$viewname, $modname);
                        }
                        $CFG->debug = $lastdbg;
                        $viewlist["$modname/$viewname"] = $str;
                    }
                }
            }
        }

        return $viewlist;
    }
}
