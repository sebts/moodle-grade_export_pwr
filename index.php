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

require_once '../../../config.php';
require_once $CFG->dirroot.'/grade/export/lib.php';
require_once 'grade_export_pwr.php';

$id = required_param('id', PARAM_INT); // course id

$PAGE->set_url('/grade/export/pwr/index.php', array('id'=>$id));

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/pwr:view', $context);

print_grade_page_head($COURSE->id, 'export', 'pwr', get_string('exportto', 'grades') . ' ' . get_string('pluginname', 'gradeexport_pwr'));
export_verify_grades($COURSE->id);

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/pwr:publish', $context);
}

$export = new grade_export_pwr($course);
if (isset($_GET["msg"]) && $_GET["msg"] == 'Update Successful') {
    $export->print_success($msg);
} else {
    $export->print_continue();
}
$export->display_preview();
echo $OUTPUT->footer();
