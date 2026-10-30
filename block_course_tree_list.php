<?php
// This file is part of course-tree-access plugin for Moodle
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Course-Tree-Access block main file.
 *
 * @package    block_course_tree_list
 * @copyright  2013 Jay Huber <jhuber@colum.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/**
 * Displays enrolled courses in a collapsible category tree.
 */
class block_course_tree_list extends block_base {
    /**
     * Assign initial plugin properties.
     */
    public function init() {
        $this->title = get_string('plugindisplayname', 'block_course_tree_list');
        $this->content_type = BLOCK_TYPE_TEXT;
    }

    /**
     * This method returns a boolean value that denotes whether the block
     * wants to present a configuration interface to site admins or not.
     *
     * @return bool Returns true
     */
    public function has_config() {
        return true;
    }

    /**
     * This method returns a boolean value, indicating whether the block is
     * allowed to have multiple instances in the same page or not.
     *
     * @return bool Returns false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     * Set the applicable formats for this plugin.
     *
     * @return array Array of booleans for each format that is allowed or not
     */
    public function applicable_formats() {
        return ['all' => true];
    }

    /**
     * Determines whether the plugin can be docked to the sidebar.
     *
     * @return boolean Returns true
     */
    public function instance_can_be_docked() {
        return true;
    }

    /**
     * Populates the course tree, returning cached content when available.
     *
     * @return stdClass|null Cached content, or null on the initial call.
     */
    public function get_content() {
        global $CFG, $USER;
        global $DB;

        $showweeksbefore = get_config('course_tree_list', 'Weeks_Before');
        $showweeksafter = get_config('course_tree_list', 'Weeks_After');

        // Set a default value if one is not found in the tables - 2 weeks.
        if ($showweeksbefore == '') {
            $showweeksbefore = 2;
        }
        if ($showweeksafter == '') {
            $showweeksafter = 2;
        }

        // Determine the IE version.
        preg_match('/MSIE (.*?);/', $_SERVER['HTTP_USER_AGENT'], $matches);
        if (count($matches) > 1) {
            // Then we're using IE.
            $version = $matches[1];
            switch (true) {
                case ($version <= 8):
                    // IE 8 or under!
                    $ie = 8;
                    break;
                case ($version == 9):
                    // IE9!
                    $ie = 0;
                    break;
                default:
                    $ie = 0;
            }
        } else {
            $ie = 0;
        }

        $out = '';
        if ($ie == 8) {
            $out = '<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>';
            $this->page->requires->js(new moodle_url($CFG->wwwroot . '/blocks/course_tree_list/javascript/selectivizr-min.js'));
        }

        // Content has been computed before -> return content.
        if ($this->content !== null) {
            return $this->content;
        }

        if (isloggedin() && !empty($USER->id) && $USER->id != 1) {
            if ($CFG->version < 2012120300) {
                // The numsections field was moved in 2.4.
                $courses = enrol_get_my_courses('numsections', 'visible DESC, fullname ASC');
            } else {
                $courses = enrol_get_my_courses('', 'visible DESC, fullname ASC');
            }

            if (!$courses) {
                $out .= get_string('noenrollments', 'block_course_tree_list');
            } else {
                $coursecategories = $DB->get_records('course_categories', null, 'sortorder');

                foreach ($coursecategories as $cc) {
                    // Add the sub_ids element to all objects.
                    $cc->sub_ids = [];
                    $cc->open = "";
                    $cc->sub_ids[$cc->id] = $cc->id;

                    if ($cc->parent != 0) {
                        $rec = $cc->parent;
                        $allowexit = 0;
                        do {
                            $coursecategories[$rec]->sub_ids[$cc->id] = $cc->id;
                            if ($coursecategories[$rec]->parent != 0) {
                                $rec = $coursecategories[$rec]->parent;
                            } else {
                                $allowexit = 1;
                            }
                        } while ($allowexit == 0);
                    }
                }

                // Open categories containing a course within the configured before/after window.
                $oneweek = 7 * 24 * 60 * 60;
                foreach ($courses as $course) {
                    if ($CFG->version < 2012120300) {
                        $numsections = $course->numsections;
                    } else {
                        $numsections = $DB->get_field(
                            'course_format_options',
                            'value',
                            ['courseid' => $course->id, 'name' => 'numsections']
                        );
                    }

                    $startdate = $course->startdate - ($oneweek * $showweeksbefore);
                    $enddate = $course->startdate + ($oneweek * ($numsections + $showweeksafter));

                    if ((time() >= $startdate) && (time() <= $enddate)) {
                        // Now update all the course categories to be displayed.
                        foreach ($coursecategories as $cc) {
                            if (array_key_exists($course->category, $cc->sub_ids)) {
                                $cc->open = " checked";
                            }
                        }
                    }
                }

                $lastcourseid = 0;
                $lastcoursedepth = 0;
                $out .= PHP_EOL . PHP_EOL . '<ol class="tree">' . PHP_EOL;
                foreach ($coursecategories as $cc) {
                    $displayed = 0;
                    foreach ($courses as $course) {
                        if (array_key_exists($course->category, $cc->sub_ids)) {
                            if ($displayed == 0) {
                                $displayed = 1;
                                $depth = $cc->depth - 1;

                                if ($lastcoursedepth >= $cc->depth) {
                                    do {
                                        $out .= '</ol>' . PHP_EOL;
                                        $out .= '</li>' . PHP_EOL;
                                        $lastcoursedepth -= 1;
                                    } while ($lastcoursedepth != ($cc->depth - 1));
                                }

                                $out .= '<li>' . PHP_EOL;
                                $out .= '<label title="' . $cc->name . '" for="category' . $cc->id . '">' . $cc->name
                                    . '</label><input type="checkbox"' . $cc->open . ' id="category' . $cc->id . '" />' . PHP_EOL;
                                $out .= '<ol>' . PHP_EOL;
                                $lastcoursedepth = $cc->depth;
                            }
                        }
                        if ($course->category == $cc->id) {
                            $url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;
                            $out .= '<li class="course"><a href="' . $url . '" title="' . $course->shortname . '">'
                                . $course->fullname . '</a></li>' . PHP_EOL;
                        }
                    }
                }

                $out .= '</ol>' . PHP_EOL . PHP_EOL;
            }
        }

        if ($CFG->branch > 23) {
            // For 2.4 or higher only.
            $this->content = new stdClass();
        }
        $this->content->text = $out;
    }
}
