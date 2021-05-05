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
 * @package block_page_module
 * @category blocks
 * @author Valery Fremaux (valery@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

$key = 'block_page_module/showactivityname';
$label = get_string('showactivitynamedefault', 'block_page_module');
$desc = get_string('configshowactivityname', 'block_page_module');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, false));

$key = 'block_page_module/pageindividualisationfeature';
$label = get_string('pageindividualisationfeature', 'block_page_module');
$desc = get_string('configpageindividualisationfeature', 'block_page_module');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, false));

$key = 'block_page_module/individualizewithtimes';
$label = get_string('individualizewithtimes', 'block_page_module');
$desc = get_string('configindividualizewithtimes', 'block_page_module');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, true));

