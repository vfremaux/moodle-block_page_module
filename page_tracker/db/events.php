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
 * @package    block_page_tracker
 * @category   blocks
 * @author     Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright  2014 valery fremaux (valery.fremaux@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

$observers = array (
    array(
        'eventname'   => '\core\event\course_deleted',
        'callback'    => 'block_page_tracker_event_observer::on_course_deleted',
        'includefile' => '/blocks/page_tracker/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),

    array(
        'eventname'   => '\core\event\course_reset_started',
        'callback'    => 'block_page_tracker_event_observer::on_course_reset_started',
        'includefile' => '/blocks/page_tracker/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),

    array(
        'eventname'   => '\format_page\event\course_page_viewed',
        'callback'    => 'block_page_tracker_event_observer::on_course_page_viewed',
        'includefile' => '/blocks/page_tracker/observers.php',
        'internal'    => true,
        'priority'    => 9999,
    ),
);
