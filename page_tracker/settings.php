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
 * @package   block_page_tracker
 * @category  blocks
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$linkoptions = array();
$linkoptions['0'] = get_string('no');
$linkoptions['1'] = get_string('yesonvisited', 'block_page_tracker');
$linkoptions['2'] = get_string('yes');

$key = 'block_page_tracker/defaultallowlinks';
$label = get_string('configdefaultallowlinks', 'block_page_tracker');
$desc = get_string('configdefaultallowlinks_desc', 'block_page_tracker');
$settings->add(new admin_setting_configselect($key, $label, $desc, 2, $linkoptions));

$key = 'block_page_tracker/defaulthidedisabledlinks';
$label = get_string('configdefaulthidedisabledlinks', 'block_page_tracker');
$desc = get_string('configdefaulthidedisabledlinks_desc', 'block_page_tracker');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, true));

$leveloptions = array();
$leveloptions['100'] = get_string('alllevels', 'block_page_tracker');
for ($i = 1; $i <= 3; $i++) {
    $leveloptions[$i] = $i;
}
$key = 'block_page_tracker/defaultdepth';
$label = get_string('configdefaultdepth', 'block_page_tracker');
$desc = get_string('configdefaultdepth_desc', 'block_page_tracker');
$settings->add(new admin_setting_configselect($key, $label, $desc, 100, $leveloptions));

$key = 'block_page_tracker/defaultusemenulabels';
$label = get_string('configdefaultusemenulabels', 'block_page_tracker');
$desc = get_string('configdefaultusemenulabels_desc', 'block_page_tracker');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, true));

$key = 'block_page_tracker/defaulthideaccessbullets';
$label = get_string('configdefaulthideaccessbullets', 'block_page_tracker');
$desc = get_string('configdefaulthideaccessbullets_desc', 'block_page_tracker');
$settings->add(new admin_setting_configcheckbox($key, $label, $desc, false));
