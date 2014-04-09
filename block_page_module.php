<?php // $Id: block_page_module.php,v 1.9 2012-07-10 16:01:36 vf Exp $
/**
 * Page Module block
 *
 * Warning: $this->instance->id is actually a
 * format_page_item record ID, so DO NOT USE
 * unless you know what your doing.
 *
 * @author Mark Nielsen
 * @version $Id: block_page_module.php,v 1.9 2012-07-10 16:01:36 vf Exp $
 * @package block_page_module
 * @todo Could have external methods for caching cm, module, module instace records
 **/

require_once($CFG->dirroot.'/blocks/page_module/lib.php');

/**
 * Block class definition
 *
 * @package block_page_module
 **/
class block_page_module extends block_base {
    /**
     * Hide block header or not
     *
     * @var boolean
     **/
    var $hideheader = true;
    
    var $modinfo;

    var $coursemodinfo;

    var $completioninfo;

    /**
     * Sets default title and version.
     *
     * @return void
     **/
    function init() {
    	global $COURSE;
        
        $this->completioninfo = new completion_info($COURSE);

        $this->title = get_string('blockname', 'block_page_module');

        $this->coursemodinfo = get_fast_modinfo($COURSE);
    }

	/**
	*
	*
	*/	
    function applicable_formats() {
        // Default case: the block can be used in page format courses only
        return array('all' => false, 'course-view-page' => true);
    }    

    function has_config(){
        return true;
    }

	/**
	* overrides core one to add completion data in content structures
	*
	*
	*/
    public function get_content_for_output($output) {
    	global $COURSE;

        $result = block_page_module_init($this->config->cmid);
        
        if ($result !== false and is_array($result)) {        	
            // Get all of the variables out
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->coursepage,   $this->baseurl) = $result;
        }

    	$bc = parent::get_content_for_output($output);
    	
        if (array_key_exists($this->cm->id, $this->coursemodinfo)){
	        $this->modinfo = $this->coursemodinfo->cms[$this->cm->id];
	    	$bc->completion = new StdClass();
	    	$bc->completion->mod = $this->modinfo;
	    	$bc->completion->completioninfo = $this->completioninfo;
	    }
    	
    	return $bc;
    }

    /**
     * Given a course module ID, this block
     * will display the module's pageitem hook.
     *
     * @return object
     **/
    function get_content() {
        global $CFG, $USER, $PAGE, $COURSE;

        $renderer = $PAGE->get_renderer('format_page');
        
        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        
        if (empty($this->instance) or !$this->config->cmid) {
            return $this->content;
        }

        // Gets all of our variables and caches result
        $result = block_page_module_init($this->config->cmid);
        
        if ($result !== false and is_array($result)) {
        	
            // Get all of the variables out
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->coursepage,   $this->baseurl) = $result;
                 
            // Check module visibility
            $modulevisible = $this->instance->visible && $this->cm->visible && $this->has_user_access($USER->id, $this->cm);
            if ($modulevisible or has_capability('moodle/course:viewhiddenactivities', context_course::instance($this->course->id))) {
                // Default: set title to instance name
                $this->title = format_string($this->moduleinstance->name);
                
                // Calling hook, set_instance, and passing $this by reference
                $displayoptions = array();
                block_page_module_hook($this->module->name, 'set_instance', array(&$this));
                if (empty($this->content->text) && array_key_exists($this->config->cmid, $this->coursemodinfo->cms)){
	            	$this->content->text = $renderer->print_cm($COURSE, $this->coursemodinfo->cms[$this->cm->id], $displayoptions);
	            }

                if (!empty($this->content->text) and !$modulevisible) {
                    $this->content->text = '<div class="dimmed">'.$this->content->text.'</div>';
                }
            }
        }
        if (!$result and empty($this->content->text)) {
            $this->content->text = get_string('displayerror', 'block_page_module');
        }
        return $this->content;
    }

    /**
     * Modify id and class to suite this block
     *
     * @return array
     */
    function html_attributes() {
        $result = block_page_module_init($this->config->cmid);
        
        if ($result !== false and is_array($result)) {
        	
            // Get all of the variables out
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->coursepage,   $this->baseurl) = $result;
		}        

        return array('id' => 'pageitem'.$this->instance->id, 'class' => 'block_'. $this->name().' mod-'.@$this->module->name);
    }

    /**
     * Default return is false - header will be shown
     *
     * @return boolean
     */
    function hide_header() {
        return $this->hideheader;
    }

    /**
     * Has instance config
     *
     * @return boolean
     **/
    function instance_allow_config() {
        return false;
    }

    /**
     * Allow multiple instances of each block
     *
     * @return boolean
     */
    function instance_allow_multiple() {
        return true;
    }

    /**
     * Make sure config is set to something
     *
     */
    function specialization() {
    	global $DB;
    	
        if (empty($this->config->cmid) or !$DB->record_exists('course_modules', array('id' => $this->config->cmid))) {
        	if (!isset($this->config)) $this->config = new StdClass();        	
            $this->config->cmid = 0;
        }
    }

    /**
     * Only real Hack of this block, replace the
     * contextid of the block with the linked
     * module's ID.  If no ID, then hide the 
     * the button.
     * DEPRECATED
     * @return void
     **/
    function _add_edit_controls() {
        parent::_add_edit_controls();

        if ($this->edit_controls !== NULL) {
            if (!empty($this->config->cmid)) {
                $blockcontext  = context_block::instance($this->instance->id);
                $modulecontext = context_module::instance($this->config->cmid);

                $this->edit_controls = str_replace("contextid=$blockcontext->id", "contextid=$modulecontext->id", $this->edit_controls);
            } else {
                // No linked module so hide the role assign widget
                $this->edit_controls = str_replace('<div class="commands"><a',
                                                   '<div class="commands"><a style="position: absolute; display: none;"',
                                                   $this->edit_controls);
            }
        }
    }

    function has_user_access($userid, $cm){
    	global $DB;

        $hidden = $DB->get_field('block_page_module_access', 'hidden', array('userid' => $userid, 'pageitemid' => $cm->id));
        return !$hidden;
    }    

    /**
    * the cron handles time schedule switching from individualization settings
    * This first implementation scans for active switch times
    */
    function cron(){
    	global $DB;
    	
        $now = time();
        if ($revealswitches = $DB->get_records_select('block_page_module_access', ' revealtime > ? AND revealtime != 0 ', array($now))){
            foreach($revealswitches as $sw){
                $sw->revealtime = 0;
                $sw->hidden = 0;
                $DB->update_record('block_page_module_access', $sw);
            }
        }
        if ($hideswitches = $DB->get_records_select('block_page_module_access', ' hidetime > ? AND hidetime != 0 ', array($now))){
            foreach($revealswitches as $sw){
                $sw->hidetime = 0;
                $sw->hidden = 1;
                $DB->update_record('block_page_module_access', $sw);
            }
        }
    }    
}
