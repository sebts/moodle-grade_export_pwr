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

$PAGE->set_url('/grade/export/pwr/index.php', array('id'=>$id));	//TSTAMP: Should this be deleted?

if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = context_course::instance($id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/pwr:view', $context);

print_grade_page_head($COURSE->id, 'export', 'pwr', get_string('exportto', 'grades') . ' PowerCampus ' );

if (!empty($CFG->gradepublishing)) {
    $CFG->gradepublishing = has_capability('gradeexport/pwr:publish', $context);
}

$mform = new grade_export_form(null, array('includeseparator'=>true, 'publishing' => true));

$groupmode    = groups_get_course_groupmode($course);   // Groups are being used
$currentgroup = groups_get_course_group($course, true);
if ($groupmode == SEPARATEGROUPS and !$currentgroup and !has_capability('moodle/site:accessallgroups', $context)) {
    echo $OUTPUT->heading(get_string("notingroup"));
    echo $OUTPUT->footer();
    die;
}

///This excutes the form to set all the display defaults of the Export Options form
/// it doesn't show the form because the $mform->display() call has been removed.
/// This way, users are immediately presented with the Export Preview page.
$data = $mform->get_data();
//Probaly overkill but just to be safe:
$data->display = GRADE_DISPLAY_TYPE_LETTER;
$data->previewrows = '100000';

    $export = new grade_export_pwr($course, '', '');

    // print the grades on screen for review
    $export->process_form($data);
    $msg = $_GET["msg"];
    if ($msg == 'Update Successful') {
        $export->print_success($msg);
    } else {
        $export->print_continue();
    }
    $export->display_preview();

    echo '<p />';
    print_footer($course);

	exit;

groups_print_course_menu($course, 'index.php?id='.$id);
echo '<div class="clearer"></div>';

