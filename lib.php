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
 * Page module block external library
 *
 * @package   block_page_module
 * @category  blocks
 * @author    Mark Nielsen
 * @author    Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * Our global cache variable
 */
global $BLOCK_PAGE_MODULE;

/**
 * External function for retrieving module data.
 *
 * Using external method so we can cache results
 * to improve performance for all page_module
 * instances.
 *
 * @param int $cmid Course Module ID
 * @return array
 **/
function block_page_module_init($cmid) {
    global $COURSE, $CFG, $PAGE, $BLOCK_PAGE_MODULE, $DB;

    static $page = false;
    $baseurl = '';

    if (!$page) {
        include_once($CFG->dirroot.'/course/format/page/page.class.php');

        if (!$page = course_page::get_current_page()) {
            $page = new stdClass;
            $page->id = 0;
        }

        // Then build our cache.
        if (!empty($page->id)) {
            // Since we know what page will be printed, lets get all of our records in bulk and cache the results.
            $sql = "
                SELECT
                    c.*
                FROM
                    {course_modules} c,
                    {format_page} p,
                    {format_page_items} i
                WHERE 
                    i.cmid = c.id AND
                    p.id = i.pageid AND
                    p.id = ?
            ";

            if ($cms = $DB->get_records_sql($sql, array($page->id))) {
                // Save for later.
                $BLOCK_PAGE_MODULE['cms'] = $cms;
    
                if ($modules = $DB->get_records('modules')) {
                    // Save for later.
                    $BLOCK_PAGE_MODULE['modules'] = $modules;
    
                    $mods = array();
                    foreach ($cms as $cm) {
                        $mods[$modules[$cm->module]->name][] = $cm->instance;
                    }
                    $instances = array();
                    foreach ($mods as $modname => $instanceids) {
                        if ($records = $DB->get_records_list($modname, 'id', implode(',', $instanceids))) {
                            $instances[$modname] = $records;
                        }
                    }
                    // Save for later.
                    $BLOCK_PAGE_MODULE['instances'] = $instances;
                }
            }
        } else {
            // OK, we cannot do anything cool, make sure we dont break rest of the script.
            $BLOCK_PAGE_MODULE = array('cms' => array(), 'modules' => array(), 'instances' => array());
        }
    }

    if ($COURSE->id == SITEID) {
        $baseurl = "$CFG->wwwroot/index.php?id=$COURSE->id&amp;page=$page->id";
    } else {
        $baseurl = "$CFG->wwwroot/course/view.php?id=$COURSE->id&amp;page=$page->id";
    }

    if (!$cm = block_page_module_get_cm($cmid, $page->id)) {
        return false;
    }
    if (!$module = block_page_module_get_module($cm->module)) {
        return false;
    }
    if (!$moduleinstance = block_page_module_get_instance($module->name, $cm->instance)) {
        return false;
    }

    return array($cm, $module, $moduleinstance, $COURSE, $page, $baseurl);
}

/**
 * Get the Course Module Record
 *
 * @param int $cmid Course Module ID
 * @return mixed
 **/
function block_page_module_get_cm($cmid) {
    global $BLOCK_PAGE_MODULE, $DB;

    $cms = &$BLOCK_PAGE_MODULE['cms'];

    if (empty($cms[$cmid])) {
        if (!$cm = $DB->get_record('course_modules', array('id' => $cmid))) {
            return false;
        }
        $cms[$cm->id] = $cm;
    }

    return $cms[$cmid];
}

/**
 * Get the Module Record
 *
 * @param int $moduleid Module ID
 * @return mixed
 **/
function block_page_module_get_module($moduleid) {
    global $BLOCK_PAGE_MODULE, $DB;

    $modules = &$BLOCK_PAGE_MODULE['modules'];

    if (empty($modules[$moduleid])) {
        if (!$module = $DB->get_record('modules', array('id' => $moduleid))) {
            return false;
        }
        $modules[$module->id] = $module;
    }

    return $modules[$moduleid];
}

/**
 * Get the Module Instance Record
 *
 * @param string $name Module name
 * @param int $id instance ID
 * @return mixed
 **/
function block_page_module_get_instance($name, $id) {
    global $BLOCK_PAGE_MODULE, $DB;

    $instances = &$BLOCK_PAGE_MODULE['instances'];

    if (empty($instances[$name]) or empty($instances[$name][$id])) {
        if (!$moduleinstance = $DB->get_record($name, array('id' => $id))) {
            return false;
        }
        $instances[$name][$id] = $moduleinstance;
    }

    return $instances[$name][$id];
}

/**
 * Call a page item hook.
 *
 * Locations where the hook can be located:
 *    mod/modname/pageitem.php
 *    course/format/page/plugins/pageitem.modname.php
 *
 * If above fail, will call default method in course/format/page/plugins/pageitem.php
 *
 * @param string $module Module name to call the hook for
 * @param string $method Function that will be called (A prefix will be added)
 * @param mixed $args This will be passed to the hook function
 * @return mixed
 **/
function block_page_module_hook($moduleview, $method, $args = array()) {
    global $CFG;

    $result = false;

    if (!is_array($args)) {
        $args = array($args);
    }

    if (strpos($moduleview, '/') === false) {
        $module = $moduleview;
        $view = '';
    } else {
        list($module, $view) = explode('/', $moduleview);
    }

    if ($view != 'default' && !empty($view)) {
        $view = '_'.$view;
    } else {
        $view = '';
    }

    // Path and function mappings.
    $paths = array("$CFG->dirroot/mod/{$module}/pageitem{$view}.php"
                        => "{$module}{$view}_$method",
                   "$CFG->dirroot/course/format/page/plugins/{$module}{$view}.php"
                        => "{$module}{$view}_$method",
                    );

    foreach ($paths as $path => $function) {
        if (file_exists($path)) {
            require_once($path);
            if (is_callable($function)) {
                $result = call_user_func_array($function, $args);
                break;
            }
        }
    }

    return $result;
}
