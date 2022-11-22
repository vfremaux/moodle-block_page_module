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
 * Version details.
 *
 * @package     block_page_module
 * @category    blocks
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2010 onwards Valery Fremaux (http://www.mylearningfactory.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2019091600;        // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2022041200;        // Requires this Moodle version.
$plugin->component = 'block_page_module'; // Full name of the plugin (used for diagnostics).
$plugin->release = '4.0.0 (Build 2019091600)';
$plugin->maturity = MATURITY_STABLE;
$plugin->supported = [40,40];

// Non moodle attributes.
$plugin->codeincrement = '4.0.0002';