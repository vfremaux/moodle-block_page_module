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
 * @package    block_page_module
 * @category   blocks
 * @author Mark Nielsen
 * @author Moodle 2 Valery Fremaux
 * @todo Could have external methods for caching cm, module, module instace records
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * This script allows user to choose a rendering view for the page module instance.
 */
require('../../config.php');
require_once($CFG->dirroot.'/blocks/page_module/chooseview_form.php');

$id = required_param('id', PARAM_INT); // Course ID.
$instanceid = required_param('instance', PARAM_INT); // Block instance ID.

if (!$course = $DB->get_record('course', array('id' => "$id"))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($course->id);

// Security.

require_login($course);
require_capability('moodle/course:manageactivities', $coursecontext);

if (!$instance = $DB->get_record('block_instances', array('id' => "$instanceid"))) {
    print_error('badblockinstance', 'block_page_module');
}

$theblock = block_instance('page_module', $instance);

$url = new moodle_url('/blocks/page_module/chooseview.php', array('id' => $id, 'instance' => $instanceid));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);
$PAGE->navbar->add(get_string('pluginname', 'format_page'));
$PAGE->navbar->add(get_string('chooseview', 'block_page_module'));
$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->shortname);

$mform = new ChooseView_Form($url, array('theblock' => $theblock));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
}

if ($data = $mform->get_data()) {
    $theblock->config->view = $data->chooseview;
    $theblock->instance_config_save($theblock->config);
    redirect(new moodle_url('/course/view.php', array('id' => $id)));
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('chooseview', 'block_page_module'));

$data = new StdClass;
$data->chooseview = ''.@$theblock->config->view;
$mform->set_data($data);
$mform->display();

echo $OUTPUT->footer();