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
 * @category   blocks
 * @author Mark Nielsen
 * @author Moodle 2 Valery Fremaux
 * @todo Could have external methods for caching cm, module, module instace records
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * Warning: $this->instance->id is actually a
 * format_page_item record ID, so DO NOT USE
 * unless you know what your doing.
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/blocks/page_module/lib.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

/**
 * Block class definition
 *
 */
class block_page_module extends block_base {

    /**
     * Hide block header or not
     *
     * @var boolean
     */
    public $hideheader = true;

    /**
     * a cache for course modinfo
     */
    protected $modinfo;

    /**
     * a cache for coursemodinfo
     */
    protected $coursemodinfo;

    /**
     * a local cache for completion info
     */
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

    public function specialization() {
        global $DB;

        if (empty($this->config->cmid) ||
                !$DB->record_exists('course_modules', array('id' => $this->config->cmid))) {
            if (!isset($this->config)) {
                $this->config = new StdClass();
            }
            $this->config->cmid = 0;
        } else {
            $result = block_page_module_init($this->config->cmid);

            if ($result !== false and is_array($result)) {

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
        return array('all' => false, 'course-view-page' => true);
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
     */
    public function get_content_for_output($output) {
        global $COURSE;

        $coursecontext = context_course::instance($COURSE->id);

        $result = block_page_module_init($this->config->cmid);

        if ($result !== false and is_array($result)) {
            // Get all of the variables out.
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->coursepage,   $this->baseurl) = $result;
        }

        if (empty($this->cm)) {
            // Lost module.
            return;
        }

        $bc = parent::get_content_for_output($output);

        if (empty($bc)) {
            return;
        }

        if (array_key_exists($this->cm->id, $this->coursemodinfo)) {
            $this->modinfo = $this->coursemodinfo->cms[$this->cm->id];
            $bc->completion = new StdClass();
            $bc->completion->mod = $this->modinfo;
            $bc->completion->completioninfo = $this->completioninfo;
        }

        $bc->add_class('yui3-dd-drop');

        /*
         * In this case, $subpagepattern is mandatory and holds the pageid
         * Bloc protected pages for page module editing extensions here
         */
        if ($COURSE->format == 'page') {
            $pageid = str_replace('page-', '', $this->instance->subpagepattern);
            $page = course_page::get($pageid);
            if (empty($page)) {
                return '';
            }
            $context = context::instance_by_id($this->instance->parentcontextid);
            if ($page->protected && !has_capability('format/page:editprotectedpages', $context)) {
                return $bc;
            }
        }

        if ($this->page->user_is_editing() && has_capability('moodle/course:manageactivities', $coursecontext)) {
            $str = get_string('editmodule', 'block_page_module');
            $url = new moodle_url('/course/modedit.php', array('update' => $this->config->cmid));
            $icon = new pix_icon('t/edit', $str, 'moodle', array('class' => 'iconsmall', 'title' => ''));
            $attributes = array('class' => 'editing_edit');
            $bc->controls[] = new action_menu_link_secondary($url, $icon, $str, $attributes);

            $views = $this->get_views();
            if (count($views) > 1) {
                $str = get_string('changeview', 'block_page_module');
                $params = array('id' => $COURSE->id, 'instance' => $this->instance->id);
                $url = new moodle_url('/blocks/page_module/chooseview.php', $params);
                $icon = new pix_icon('chooseview', $str, 'block_page_module', array('class' => 'iconsmall', 'title' => ''));
                $attributes = array('class' => 'editing_changeview');
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
        global $USER, $PAGE, $COURSE;

        // This contains an alterated course renderer embedded.
        $renderer = $PAGE->get_renderer('format_page');
        $courserenderer = $PAGE->get_renderer('core', 'course');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance) or !$this->config->cmid) {
            return $this->content;
        }

        // Gets all of our variables and caches result.
        $result = block_page_module_init($this->config->cmid);

        if ($result !== false and is_array($result)) {

            // Get all of the variables out.
            list($this->cm,
                 $this->module,
                 $this->moduleinstance,
                 $this->course,
                 $this->coursepage,
                 $this->baseurl) = $result;

            // Check module visibility.
            $modulevisible = $this->instance->visible && $this->cm->visible && $this->has_user_access($USER->id, $this->cm);
            $coursecontext = context_course::instance($this->course->id);
            if ($modulevisible or has_capability('moodle/course:viewhiddenactivities', $coursecontext)) {
                // Default: set title to instance name.
                $this->title = format_string($this->moduleinstance->name);

                // Calling hook, set_instance, and passing $this by reference.

                $displayoptions = array();
                if (!empty($this->config->view)) {
                    block_page_module_hook($this->config->view, 'set_instance', array(&$this));
                } else {
                    block_page_module_hook($this->module->name.'/default', 'set_instance', array(&$this));
                }
                if (empty($this->content->text) && array_key_exists($this->config->cmid, $this->coursemodinfo->cms)) {
                    $cm = $this->coursemodinfo->cms[$this->cm->id];
                    $this->content->text .= $renderer->print_cm($COURSE, $cm, $displayoptions);
                }

                if (!empty($this->content->text) and !$modulevisible) {
                    $this->content->text .= '<div class="dimmed">'.$this->content->text.'</div>';
                }

                // Important : next instruction REPLACES content. Not appending.
                if (!empty($this->coursemodinfo->cms[$this->cm->id])) {
                    $cm = $this->coursemodinfo->cms[$this->cm->id];
                    $comp = $courserenderer->course_section_cm_completion($COURSE, $foocompletion, $cm);
                    $this->content->text = '<div class="mod-completion" style="float:right">'.$comp.'</div>'.$this->content->text;
                }
            }
        }
        if (!$result and empty($this->content->text)) {
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
        $result = block_page_module_init($this->config->cmid);

        if ($result !== false and is_array($result)) {

            // Get all of the variables out.
            list($this->cm,
                 $this->module,
                 $this->moduleinstance,
                 $this->course,
                 $this->coursepage,
                 $this->baseurl) = $result;
        }

        return array('id' => 'inst'.$this->instance->id, 'class' => 'block block_'. $this->name().' mod-'.@$this->module->name);
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

        $hidden = $DB->get_field('block_page_module_access', 'hidden', array('userid' => $userid, 'pageitemid' => $cm->id));
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
        if ($revealswitches = $DB->get_records_select('block_page_module_access', $select, array($now))) {
            foreach ($revealswitches as $sw) {
                $sw->revealtime = 0;
                $sw->hidden = 0;
                $DB->update_record('block_page_module_access', $sw);
            }
        }
        $select = ' hidetime > ? AND hidetime != 0 ';
        if ($hideswitches = $DB->get_records_select('block_page_module_access', $select, array($now))) {
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

        $viewlist = array('default' => get_string('linkview', 'block_page_module'));

        $moduleid = $DB->get_field('course_modules', 'module', array('id' => $this->config->cmid));
        $modname = $DB->get_field('modules', 'name', array('id' => $moduleid));

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
