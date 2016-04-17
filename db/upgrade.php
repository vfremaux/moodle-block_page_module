<?php
// This file keeps track of upgrades to
// the email
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

/**
 * @package    block_page_module
 * @category   blocks
 * @copyright  2003 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_page_module_upgrade($oldversion = 0) {
    global $CFG, $DB;

    $result = true;
    
    $dbman = $DB->get_manager();

// And upgrade begins here. For each one, you'll need one
// block of code similar to the next one. Please, delete
// this comment lines once this file start handling proper
// upgrade code.

    if ($result && $oldversion < 2013020700) {
        // Define table block_page_module_access to be renamed to block_page_module_access.
        $table = new xmldb_table('page_module_access');

        // Launch rename table for block_page_module_access.
        $dbman->rename_table($table, 'block_page_module_access');

        // page_module savepoint reached.
        upgrade_block_savepoint(true, 2013020700, 'page_module');
    }
    
    return $result;
}