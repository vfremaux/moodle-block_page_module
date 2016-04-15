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

defined('MOODLE_INTERNAL') || die();

/**
 * Form for editing profile block settings
 *
 * @package    block_page_module
 * @category   blocks
 * @author     Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_page_module_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        $config = get_config('block_page_module');

        $mform->addElement('header', 'configheader', get_string('page_module_settings', 'block_page_module'));

        $mform->addElement('checkbox', 'config_showactivityname', get_string('showactivityname', 'block_page_module'), @$config->showactivityname);

    }
}