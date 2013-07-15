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

$id                = required_param('id', PARAM_INT); // course id
$itemids           = required_param('itemids', PARAM_RAW);
$coursetotalid     = required_param('coursetotalid', PARAM_INT);


if (!$course = $DB->get_record('course', array('id'=>$id))) {
    print_error('nocourseid');
}

require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $id);

require_capability('moodle/grade:export', $context);
require_capability('gradeexport/pwr:view', $context);


// print all the exported data here
$export = new grade_export_pwr($course, $itemids, $coursetotalid);
$errmsg = $export->upload_PowerCampus();	//TSTAMP: Execute the upload to PowerCampus

if (empty($errmsg)) {
	header('Location: '.$_SERVER['HTTP_REFERER'].'&msg=Update Succesful');
}
