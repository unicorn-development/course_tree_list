<?php
// This file is part of course-quick-access plugin for Moodle
// http://moodle.org/
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
 * Plugin versioning.
 *
 * @package    block_course_tree_list
 * @copyright  2013 Jay Huber <jhuber@colum.edu>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2024111200;
$plugin->requires = 2022112800.00;
$plugin->cron      = 0;
$plugin->component = 'block_course_tree_list';
$plugin->maturity = MATURITY_STABLE;
$plugin->release   = '2.0.0 for Moodle 4.1+ (Build: 2024111200)';
