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
 * @package block-dashboard
 * @category blocks
 * @author Valery Fremaux (valery@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @version Moodle 2.2
 */

require_once $CFG->libdir.'/formslib.php';

class ChooseView_Form extends moodleform {

    function definition() {
        global $PAGE, $COURSE, $CFG;

        $mform = $this->_form;

        $theblock = $this->_customdata['theblock'];
        $renderer = $PAGE->get_renderer('format_page');

        $result = block_page_module_init($theblock->config->cmid);

        if ($result !== false and is_array($result)) {
            // Get all of the variables out.
            list($theblock->cm,     $theblock->module, $theblock->moduleinstance,
                 $theblock->course, $theblock->coursepage,   $theblock->baseurl) = $result;
        }

        $views = $theblock->get_views();

        foreach ($views as $view => $viewname) {
            $mform->addElement('radio', 'chooseview', '', $viewname, $view);

            if ($view == 'default') {
                $coursemodinfo = get_fast_modinfo($COURSE);
                $viewcontent = '<div class="block-page-module-view section">'.$renderer->print_cm($COURSE, $coursemodinfo->cms[$theblock->config->cmid], array()).'</div>';
            } else {
                $viewfile = str_replace('/', '_', $view);

                if (file_exists($CFG->dirroot.'/course/format/page/plugins/'.$viewfile.'.php')) {
                    include_once($CFG->dirroot.'/course/format/page/plugins/'.$viewfile.'.php');
                    $modname = $viewfile;
                    $viewname = '';
                } else {
                    list($modname, $viewname) = explode('/', $view);
                    if ($viewname != 'default') {
                        $viewname = '_'.$viewname;
                    } else {
                        $viewname = '';
                    }
                    include_once($CFG->dirroot.'/mod/'.$modname.'/pageitem'.$viewname.'.php');
                }
                $fakeblock = clone($theblock);
                $fakeblock->content = new StdClass;
                $func = $modname.$viewname.'_set_instance';
                $func($fakeblock);
                if (empty($fakeblock->content->text)) {
                    $fakeblock->content->text = '<div class="block-page-module-emptyview">'.get_string('emptyview', 'block_page_module').'</div>';
                }
                $viewcontent = '<div class="block-page-module-view section">'.$fakeblock->content->text.'</div/>';
                $viewcontent = preg_replace('/<form[^>]*>/', '', $viewcontent);
                $viewcontent = preg_replace('/<\/form>/', '', $viewcontent);
            }

            $mform->addElement('static', 'view_'.$view, $viewname, $viewcontent);
        }

        $this->add_action_buttons();
    }
}
