<?php  

/*
 This file is part of block_strathcourse_list.

    block_strathcourse_list is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    block_strathcourse_list is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with block_strathcourse_list.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * Parts of this file may be based on Moodle code
 * Copyright © 1999 onwards, Martin Dougiamas
 * and many other contributors.
 * GNU Public License
 */




$options = array('all'=>get_string('allcourses', 'block_course_list'), 'own'=>get_string('owncourses', 'block_course_list'));

$settings->add(new admin_setting_configselect('block_course_list_adminview', get_string('adminview', 'block_course_list'),
                   get_string('configadminview', 'block_course_list'), 'all', $options));

$settings->add(new admin_setting_configcheckbox('block_course_list_hideallcourseslink', get_string('hideallcourseslink', 'block_course_list'),
                   get_string('confighideallcourseslink', 'block_course_list'), 0));

$settings->add(new admin_setting_configtextarea('block_course_list_archiveservers','Archive Servers',get_string('archiveservers_help','block_strathcourse_list'),''));
$settings->add(new admin_setting_configtextarea('block_course_list_archiveserversnames','Archive Server Names',get_string('archiveservernames_help','block_strathcourse_list'),''));
?>
