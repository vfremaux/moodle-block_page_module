<?php // $Id$
/**
 * Page Module block
 *
 * Warning: $this->instance->id is actually a
 * format_page_item record ID, so DO NOT USE
 * unless you know what your doing.
 *
 * @author Mark Nielsen
 * @version $Id$
 * @package block_page_module
 * @todo Could have external methods for caching cm, module, module instace records
 **/

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

    /**
     * Sets default title and version.
     *
     * @return void
     **/
    function init() {
        $this->title = get_string('blockname', 'block_page_module') ;
        $this->version = 2010020200;
    }

    /**
     * Given a course module ID, this block
     * will display the module's pageitem hook.
     *
     * @return object
     **/
    function get_content() {
        global $CFG, $USER;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->instance) or !$this->config->cmid) {
            return $this->content;
        }

        require_once($CFG->dirroot.'/blocks/page_module/lib.php');

        // Gets all of our variables and caches result
        $result = block_page_module_init($this->config->cmid);

        if ($result !== false and is_array($result)) {
            // Get all of the variables out
            list($this->cm,     $this->module, $this->moduleinstance,
                 $this->course, $this->page,   $this->baseurl) = $result;

            // Check module visibility
            $modulevisible = $this->cm->visible && $this->has_user_access($USER->id, $this->cm);
            if ($modulevisible or has_capability('moodle/course:viewhiddenactivities', get_context_instance(CONTEXT_COURSE, $this->course->id))) {
                // Default: set title to instance name
                $this->title = format_string($this->moduleinstance->name);

                // Calling hook, set_instance, and passing $this by reference
                $result = block_page_module_hook($this->module->name, 'set_instance', array(&$this));

                if (!empty($this->content->text) and !$this->cm->visible) {
                    $this->content->text = '<span class="dimmed_text">'.$this->content->text.'</span>';
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
        return array('id' => 'pageitem'.$this->instance->id, 'class' => 'block_'. $this->name()." mod-{$this->module->name}");
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
        return true;
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
        if (empty($this->config->cmid)) {
            $this->config->cmid = 0;
        }
    }

    /**
     * Remap config's course module ID
     *
     * @return void
     **/
    function after_restore($restore) {
        if (!empty($this->config->cmid)) {
            if ($newid = backup_getid($restore->backup_unique_code, 'course_modules', $this->config->cmid)) {
                $this->config->cmid = $newid->new_id;
            } else {
                $this->config->cmid = 0;
            }
            $this->instance_config_commit();
        }
    }

    /**
     * Only real Hack of this block, replace the
     * contextid of the block with the linked
     * module's ID.  If no ID, then hide the 
     * the button.
     *
     * @return void
     **/
    function _add_edit_controls($options) {
        parent::_add_edit_controls($options);

        if ($this->edit_controls !== NULL) {
            if (!empty($this->config->cmid)) {
                $blockcontext  = get_context_instance(CONTEXT_BLOCK, $this->instance->id);
                $modulecontext = get_context_instance(CONTEXT_MODULE, $this->config->cmid);

                $this->edit_controls = str_replace("contextid=$blockcontext->id", "contextid=$modulecontext->id", $this->edit_controls);
            } else if (empty($this->instance->pinned)) {
                // No linked module so hide the role assign widget for non-pinned instances
                $this->edit_controls = str_replace('<div class="commands"><a',
                                                   '<div class="commands"><a style="position: absolute; display: none;"',
                                                   $this->edit_controls);
            }
        }
    }

    // PATCH : This adds a possibility to check against individual course module mapping (individualized training)
    function has_user_access($userid, $cm){
        $hidden = get_field('page_module_access', 'hidden', 'userid', $userid, 'pageitemid', $cm->id);
        return !$hidden;
    }    
    // /PATCH    

}
?>