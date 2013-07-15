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

require_once($CFG->dirroot.'/grade/export/lib.php');
require_once('db/update_PowerCampus.php');

class grade_export_pwr extends grade_export {

    public $plugin = 'pwr';
    public $separator; // default separator

	var $coursetotalid;     //Hold the itemid of the Course Total column
    public function __construct($course, $itemlist='', $coursetotalid='') {
		$this->grade_export($course, $itemlist, $coursetotalid, $displaytype);
        $this->separator = $separator;
    }

//// TSTAMP Funtion lifted from /grade/export/lib.php and modified to show minimal number
//			of columns but maximum number of rows, and to show letter grades only
    function grade_export($course, $itemlist='', $coursetotalid='',$displaytype = 0) {
        $this->course = $course;
        $this->grade_items = grade_item::fetch_all(array('courseid'=>$this->course->id));

        $this->columns = array();
        if (!empty($itemlist)) {
            $itemids = explode(',', $itemlist);
            // remove items that are not requested
            foreach ($itemids as $itemid) {
                if (array_key_exists($itemid, $this->grade_items)) {
                    $this->columns[$itemid] =& $this->grade_items[$itemid];
                }
            }
        } else {
            foreach ($this->grade_items as $itemid=>$unused) {
                $this->columns[$itemid] =& $this->grade_items[$itemid];
            }
        }

        $this->coursetotalid = $coursetotalid;              //The itemid of the Course Total column
        $this->displaytype = GRADE_DISPLAY_TYPE_LETTER;     //PowerCampus only takes letter grades

    }


//// TSTAMP Funtion lifted from /grade/export/lib.php and modified to show minimal number
//			of columns but maximum number of rows, and to show letter grades only
    function process_form($formdata) {
        global $USER;

        $this->columns = array();
        foreach ($this->grade_items as $itemid=>$unused) {
            // Only keep the Course Total column
            if ($this->grade_items["$itemid"]->itemtype == 'course') {
                $this->columns[$itemid] =& $this->grade_items[$itemid];
                $this->coursetotalid = $itemid;
            }
        }

		//The $this->export_letters & $this->previewrows lines refer
		// to the following lines that was set in index.php file:
		//		$data->display = GRADE_DISPLAY_TYPE_LETTER;
		//		$data->previewrows = '100000';
        $this->export_letters = $formdata->export_letters;
        $this->previewrows = $formdata->previewrows;
    }


//// TSTAMP Funtion lifted from /grade/export/lib.php and modified to re-label
//			the columns and space it out better on the Preview Screen
    function display_preview() {
        echo '<table>';
        echo '<tr>';
        echo '<th>First Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>'.
             '<th>Last Name&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>'.
             '<th>Student ID&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>'.
             '<th>Final Grade</th>';
        echo '</tr>';
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->init();
        while ($userdata = $gui->next_user()) {
            $user = $userdata->user;
            $rowstr = '';
            foreach ($this->columns as $itemid=>$unused) {
                $gradetxt = $this->format_grade($userdata->grades[$itemid]);
                $rowstr .= "<td>$gradetxt</td>";
            }

            echo '<tr>';
            echo "<td>$user->firstname</td><td>$user->lastname</td><td>$user->username</td>";
            echo $rowstr;
            echo "</tr>";
        }
        echo '</table>';
        $gui->close();
    }

//// TSTAMP Funtion lifted from /grade/export/lib.php and modified to just
//			provide the page title and the 'Export Now!' button
    function print_continue() {
        global $CFG;

        $params = $this->get_export_params();

        //Add the course total column (itemid) to the list of parms to be passed.
        $params['coursetotalid'] = $this->coursetotalid;

        echo '<div class="gradeexportlink">';
        print_single_button($CFG->wwwroot.'/grade/export/'.$this->plugin.'/export.php',
                            $params, 'Export Now!');

        echo '<br><font color=red>Check whether you have disabled the "Aggregate only non-empty grades" setting under Categories and Items, Full View.</font><br>
				To understand why this setting is important to your grade calculations, <a href="http://youtu.be/9d2Kvf1JEAE" target=_new>see the tutorial</a>. </div>';
    }

    public function get_export_params() {
        $params = parent::get_export_params();
        $params['separator'] = $this->separator;
        return $params;
    }

	//TSTAMP: print_grades can probably be deleted.
    public function print_grades() {
        global $CFG;

        $export_tracking = $this->track_exports();

        $strgrades = get_string('grades');

        switch ($this->separator) {
            case 'comma':
                $separator = ",";
                break;
            case 'tab':
            default:
                $separator = "\t";
        }

        /// Print header to force download
        if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
            @header('Cache-Control: max-age=10');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: ');
        } else { //normal http - prevent caching at all cost
            @header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            @header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
            @header('Pragma: no-cache');
        }
        header("Content-Type: application/download\n");
        $shortname = format_string($this->course->shortname, true, array('context' => get_context_instance(CONTEXT_COURSE, $this->course->id)));
        $downloadfilename = clean_filename("$shortname $strgrades");
        header("Content-Disposition: attachment; filename=\"$downloadfilename.pwr\"");

/// Print names of all the fields
        echo get_string("firstname").$separator.
             get_string("lastname").$separator.
             get_string("idnumber").$separator.
             get_string("institution").$separator.
             get_string("department").$separator.
             get_string("email");

        foreach ($this->columns as $grade_item) {
            echo $separator.$this->format_column_name($grade_item);

            /// add a feedback column
            if ($this->export_feedback) {
                echo $separator.$this->format_column_name($grade_item, true);
            }
        }
        echo "\n";

/// Print all the lines of data.
        $geub = new grade_export_update_buffer();
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->init();
        while ($userdata = $gui->next_user()) {

            $user = $userdata->user;

            echo $user->firstname.$separator.$user->lastname.$separator.$user->idnumber.$separator.$user->institution.$separator.$user->department.$separator.$user->email;

            foreach ($userdata->grades as $itemid => $grade) {
                if ($export_tracking) {
                    $status = $geub->track($grade);
                }

                echo $separator.$this->format_grade($grade);

                if ($this->export_feedback) {
                    echo $separator.$this->format_feedback($userdata->feedbacks[$itemid]);
                }
            }
            echo "\n";
        }
        $gui->close();
        $geub->close();

        exit;
    }
	
    //Print error message to the screen
    function print_foul($msg) {
        global $CFG;

        $params = $this->get_export_params();

        echo $msg;
        echo '<div class="gradeexportlink">';
        print_single_button($CFG->wwwroot.'/grade/report/grader/index.php',
                            $params, 'Return to Grader Report');
        echo '</div>';
    }

    //Get rid of the Export button and print a success message.
    function print_success($msg) {
        echo '<br /><p style="color:red;font-size:20px;text-align:center">'.$msg.'</p>';
    }

    function upload_PowerCampus() {
        global $USER;

        //Get the userID of the one making the update to PowerCampus & set other variables that will
        //be used to the update to PowerCampus as an audit trail
        //NOTE: Max len for OPID in PowerCampus is only 8 chars, however, $USER->username (Moodle logon id)
        //      can be 100 chars. Hence, the substr($USER->username,0,8).
        $opid = strtoupper(substr($USER->username,0,8));
        $terminal = 'MDL1';
        $rev_date = date('m/d/Y');
        $rev_time = '1/1/1900 '.date('h:i:s A');   //MS SQL epoch is Jan. 1, 1900

        // Get the course 'shortname' & parse it to get to the equivalent field values for PowerCampus
        $shortname = $this->course->shortname;      //ex. 'THE3120.ONL-SP2010'
        $event_id = strtok($shortname,'.');         // return up to the '.' (ie 'THE3210')
        $section = strtok('-');                     // return between previous position & '-' (ie 'ONL')
        $acad_year = substr($shortname,-4,4);       // return last 4 characters (ie '2010')
        switch (substr($shortname,-6,2))            // return 2 chars just before last 4 chars (ie 'SP')
        {                                  //Convert academic term from 2-char code to full spelling
            case 'JA':
                $acad_term = 'JANUARY';
                break;
            case 'SP':
                $acad_term = 'SPRING';
                break;
            case 'SU':
                $acad_term = 'SUMMER';
                break;
            case 'FA':
                $acad_term = 'FALL';
                break;
            default:
                $acad_term = 'X';
        }

        //Get the value of itemid for the Course Total column -- 
        $coursetotalid = $this->coursetotalid;
		
        //Array to hold the student IDs and their grade
        $arr_peoplegrades = array('id' => array(), 'grade'=> array());
        $i = 0; //Array element subscript incrementor

		
        //Loop through the list of students for the course and retrieve their Final Grades
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->init();

        while ($userdata = $gui->next_user()) {
            $people_id = $userdata->user->username;
            $final_grade = $this->format_grade($userdata->grades[$coursetotalid]);

            //Convert an empty grade in Moodle ('-') to an empty string for PowerCampus
            if($final_grade == '-'){
                $final_grade = '';
            }

            $arr_peoplegrades[$i]['id'] = $people_id;
            $arr_peoplegrades[$i]['grade'] = $final_grade;

            $i++;
        }
        $gui->close();
		
        $ubound = --$i;     //Subscript of the last element of the array
		
/* FOR DEBUGGING ONLY
var_dump($arr_peoplegrades);
echo '<br />*****<br />';
for($i= 0; $i <= $ubound; $i++){
    $people_id = $arr_peoplegrades[$i]['id'];
    $final_grade = $arr_peoplegrades[$i]['grade'];
	echo $i. ') ' . $people_id . ' - ' . $final_grade . '<br />';
}
// die;
$msg = ooops($opid);
*/

        $msg = update_transcript( $arr_peoplegrades, $ubound
                                , $acad_year, $acad_term, $event_id, $section
                                , $rev_date, $rev_time, $opid, $terminal
                                );
        if (!empty($msg)) {
            $this->print_foul($msg);
        }
		return $msg;
    }
	
}


