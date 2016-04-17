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
 * @package block_page_module
 * @category blocks
 * @author Valery Fremaux (valery@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

$settings->add(new admin_setting_configcheckbox('showactivityname', get_string('showactivitynamedefault', 'block_page_module'),
                   get_string('configshowactivityname', 'block_page_module'), false));

$settings->add(new admin_setting_configcheckbox('pageindividualisationfeature', get_string('pageindividualisationfeature', 'block_page_module'),
                   get_string('configpageindividualisationfeature', 'block_page_module'), false));

$settings->add(new admin_setting_configcheckbox('individualizewithtimes', get_string('individualizewithtimes', 'block_page_module'),
                   get_string('configindividualizewithtimes', 'block_page_module'), true));

