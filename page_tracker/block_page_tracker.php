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
 * Form for editing page_tracker block instances.
 *
 * @package   block_page_tracker
 * @category  blocks
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/*
 * generates a menu list of child pages ("stations") for a paged format course
 */

require_once($CFG->dirroot.'/blocks/page_tracker/locallib.php');
require_once($CFG->dirroot.'/course/format/page/lib.php');

class block_page_tracker extends block_list {

    protected $tracks;

    public function init() {
        $this->title = get_string('blockname', 'block_page_tracker');
    }

    public function specialization() {
        if (!empty($this->config) && !empty($this->config->title)) {
            $this->title = format_string($this->config->title);
        }
    }

    public function has_config() {
        return true;
    }

    public function instance_allow_config() {
        return true;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function applicable_formats() {
        return array('all' => false, 'course' => true, 'mod-*' => true);
    }

    public function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isset($this->config->depth)) {
            @$this->config->depth = 100;
        }

        $filteropt = new stdClass;
        $filteropt->noclean = true;

        $this->content = new stdClass;
        $this->content->text = $this->generate_summary();
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Generates the bloc's full page summary
     */
    public function generate_summary() {
        global $CFG, $USER, $COURSE, $DB, $OUTPUT;

        $context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        if (!$courseid = $COURSE->id) {
            $courseid = $this->instance->pageid;
        }

        if (!isset($this->config->startpage)) {
            @$this->config->startpage = 0;
        }

        $reldepth = 0;
        if ($this->config->startpage > 0) {
            if ($startpage = course_page::get($this->config->startpage, $COURSE->id)) {
                $pages = $startpage->get_children();
            } else {
                $this->content->footer = get_string('errormissingpage', 'block_page_tracker');
                return $this->content;
            }
        } else if ($this->config->startpage == -1) {
            $startpage = course_page::get_current_page($courseid);
            $pages = $startpage->get_children();
        } else if ($this->config->startpage == -2) {
            $startpage = course_page::get_current_page($courseid);
            $parent = $startpage->get_parent();
            if (!empty($parent)) {
                $pages = $parent->get_children();
                $startpage = $parent;
            } else {
                $pages = course_page::get_all_pages($courseid, 'nested');
            }
        } else if ($this->config->startpage == -3) {
            // Get all upper nav.
            $current = course_page::get_current_page($courseid);
            $reldepth = $current->get_page_depth();
            $pages = course_page::get_all_pages($courseid, 'nested', true, 0, $reldepth);
            $flat = course_page::get_all_pages($courseid, 'flat'); // No cost.

            // Find current's parent and plug current into tree.
            if ($current->parent) {
                $flat[$current->parent]->childs = array($current->id => $current);
            }
            $flat[$current->id] = $current;

            // block_page_tracker_debug_print_tree($pages);

            /*
            $depth = (!empty($this->config->depth)) ? $this->config->depth : 99;
            $flat[$current->id]->get_children($depth); // Load subtree in the startpage instance (wich is current).
            */
        } else {
            $pages = course_page::get_all_pages($courseid, 'nested');
        }

        $current = course_page::get_current_page($courseid);

        if (!empty($startpage)) {
            $tmp = $startpage;
            // Remove childs to only have this page.
            if (!empty($parent)) {
                $tmp->childs = null;
                array_unshift($pages, $tmp);
            }
            while ($tmp = $tmp->get_parent()) {
                $tmp->childs = null;
                if (!empty($pages)) {
                    array_unshift($pages, $tmp);
                }
            }
        }

        if (empty($pages)) {
            return '';
        }

        // Resolve tickimage locations.
        $ticks = new StdClass();
        $ticks->image = $OUTPUT->pix_url('tick_green_big', 'block_page_tracker');
        $ticks->imagepartial = $OUTPUT->pix_url('tick_green_big_partial', 'block_page_tracker');
        $ticks->imageempty = $OUTPUT->pix_url('tick_green_big_empty', 'block_page_tracker');

        $this->content->items = array();
        $this->content->icons = array();

        // TODO : if in my learning paths check completion for tick display.

        $this->get_tracks();

        // Pre scans page for completion compilation.
        foreach ($pages as $pid => $page) {
            if (!empty($this->tracks) && in_array($pid, $this->tracks)) {
                $pages[$pid]->accessed = 1;
            } else {
                $pages[$pid]->accessed = 0;
            }

            if ($page->has_children()) {
                $pages[$pid]->complete = ($pages[$pid]->accessed && $this->check_childs_access($pages[$pid]));
            } else {
                $pages[$pid]->complete = $page->accessed;
            }
        }

        foreach ($pages as $page) {
            if (!$page->is_visible(false)) {
                if (!has_capability('format/page:editpages', $coursecontext)) {
                    continue;
                }
            }

            $realvisible = $page->is_visible(true);
            $class = ($realvisible) ? '' : 'shadow';
            $class .= ($current->id == $page->id) ? 'current' : '';
            $isenabled = $page->check_activity_lock();
            if ($page->accessed) {
                if ($page->complete) {
                    $image = $ticks->image;
                } else {
                    $image = $ticks->imagepartial;
                }
            } else {
                $image = $ticks->imageempty;
            }

            if (!empty($this->config->usemenulabels)) {
                $pagename = format_string($page->nametwo);
                if (empty($pagename)) {
                    $pagename = format_string($page->nameone);
                }
            } else {
                $pagename = format_string($page->nameone);
            }

            $depthclass = 'pagedepth'.@$page->get_page_depth();
            if (((@$this->config->allowlinks == 2 ||
                    (@$this->config->allowlinks == 1 && $page->accessed)) && $isenabled) ||
                            has_capability('block/page_tracker:accessallpages', $context)) {
                $str = '<div class="block-pagetracker '.$class.' '.$depthclass.'">';
                $pageurl = new moodle_url('/course/view.php', array('id' => $courseid, 'page' => $page->id));
                $str .= '<a href="'.$pageurl.'" class="block-pagetracker '.$class.'">'.$pagename.'</a>';
                $str .= '</div>';
                $this->content->items[] = $str;
                if (empty($this->config->hideaccessbullets)) {
                    $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" class="img'.$depthclass.'" />';
                }
            } else {
                if (empty($this->config->hidedisabledlinks)) {
                    $classes = 'block-pagetracker '.$class.' '.$depthclass;
                    $str = '<div class="'.$classes.'">'.$pagename.'</div>';
                    $this->content->items[] = $str;
                    if (empty($this->config->hideaccessbullets)) {
                        $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                    }
                }
            }

            if ($page->has_children() && (($reldepth + $this->config->depth - 1) > 0)) {
                $this->print_sub_stations($page, $ticks, $current, $reldepth + $this->config->depth - 2);
            }
        }

        return $this->content;
    }

    /**
     * Recursive down scann into children to check if some
     * have been accessed already.
     * @param objectref &$page the parent course page
     */
    public function check_childs_access(&$page) {
        global $USER, $COURSE, $DB;

        $complete = true;
        $children = $page->get_children();
        foreach ($children as &$child) {

            if (!empty($this->tracks) && in_array($child->id, $this->tracks)) {
                $child->accessed = 1;
            } else {
                $child->accessed = 0;
            }

            if ($child->has_children()) {
                $child->complete = $child->accessed && $this->check_childs_access($child);
            } else {
                $child->complete = $child->accessed;
            }
            $complete = $complete && $child->accessed;
        }

        return $complete;
    }

    /**
     * Recursive printing of children pages.
     * @param objectref &$page the parent station
     * @param &$ticks
     * @param $current
     * @param int $currentdepth the depth in hierarchy of the current page.
     */
    public function print_sub_stations(&$page, &$ticks, $current, $currentdepth) {
        global $CFG, $COURSE, $OUTPUT;

        $context = context_block::instance($this->instance->id);
        $coursecontext = context_course::instance($COURSE->id);

        $children = $page->get_children();
        foreach ($children as &$child) {
            if (!$child->is_visible(false)) {
                if (!has_capability('format/page:editpages', $coursecontext)) {
                    continue;
                }
            }
            $realvisible = $child->is_visible(false);
            $class = ($realvisible) ? '' : 'shadow ';
            $class .= ($current->id == $child->id) ? 'current' : '';
            $isenabled = $child->check_activity_lock();
            if (@$child->accessed) {
                if ($child->complete) {
                    $image = $ticks->image;
                } else {
                    $image = $ticks->imagepartial;
                }
            } else {
                $image = $ticks->imageempty;
            }

            if (!empty($this->config->usemenulabels)) {
                $childname = format_string($child->nametwo);
                if (empty($childname)) {
                    $childname = format_string($child->nameone);
                }
            } else {
                $childname = format_string($child->nameone);
            }

            if (((@$this->config->allowlinks == 2 ||
                    (@$this->config->allowlinks == 1 && $child->accessed)) && $isenabled) ||
                            has_capability('block/page_tracker:accessallpages', $context)) {
                $pageurl = new moodle_url('/course/view.php', array('id' => $COURSE->id, 'page' => $child->id));
                $str = '<div class="block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth().'">';
                $str .= '<a href="'.$pageurl.'" class="block-pagetracker '.$class.'">'.$childname.'</a>';
                $str .= '</div>';
                $this->content->items[] = $str;
                if (empty($this->config->hideaccessbullets)) {
                    $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                }
            } else {
                if (empty($this->config->hidedisabledlinks)) {
                    $classes = 'block-pagetracker '.$class.' pagedepth'.@$child->get_page_depth();
                    $str = '<div class="'.$classes.'">'.$childname.'</div>';
                    $this->content->items[] = $str;
                    if (empty($this->config->hideaccessbullets)) {
                        $this->content->icons[] = '<img border="0" align="left" src="'.$image.'" width="15" />';
                    }
                }
            }

            if ($child->has_children() && ($currentdepth > 0)) {
                $this->print_sub_stations($child, $ticks, $current, $currentdepth - 1);
            }
        }
    }

    /**
     * Get distinct pages that have been viewed by the current user
     * @return an array of page ids or null if empty.
     */
    protected function get_tracks() {
        global $DB, $COURSE, $USER;

        $params = array('courseid' => $COURSE->id, 'userid' => $USER->id);
        if ($tracks = $DB->get_records('block_page_tracker', $params, 'id', 'DISTINCT pageid,pageid')) {
            $this->tracks = array_keys($tracks);
        }
    }
}
