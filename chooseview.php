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

require('../../config.php');
require_once($CFG->dirroot.'/blocks/page_module/chooseview_form.php');
require_once($CFG->dirroot.'/course/format/page/renderers.php');

$id = required_param('id', PARAM_INT); // Course ID.
$instanceid = required_param('instance', PARAM_INT); // Block instance ID.

if (!$course = $DB->get_record('course', array('id' => "$id"))) {
    print_error('invalidcourseid');
}
$coursecontext = context_course::instance($course->id);

require_login($course);

if (!$instance = $DB->get_record('block_instances', array('id' => "$instanceid"))) {
    print_error('badblockinstance', 'block_page_module');
}

$theblock = block_instance('page_module', $instance);

$url = new moodle_url('/blocks/page_module/chooseview.php', array('id' => $id, 'instance' => $instanceid));
$PAGE->set_url($url);
$PAGE->set_context($coursecontext);

$PAGE->set_title($SITE->shortname);
$PAGE->set_heading($SITE->shortname);

$mform = new ChooseView_Form($url, array('theblock' => $theblock));

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot.'/course/view.php?id='.$id);
}

if ($data = $mform->get_data()) {
    $theblock->config->view = $data->chooseview;
    $theblock->instance_config_save($theblock->config);
    redirect($CFG->wwwroot.'/course/view.php?id='.$id);
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('chooseview', 'block_page_module'));

$data = new StdClass;
$data->chooseview = ''.@$theblock->config->view;
$mform->set_data($data);
$mform->display();

echo $OUTPUT->footer();