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
    protected $coursetotalid = 0;

    /**
     * Constructor should set up all the private variables ready to be pulled
     * @param object $course
     * @param string $itemlist comma separated list of item ids, empty means all
     */
    public function __construct($course, $itemlist='-1') {
        parent::__construct($course, 0, $itemlist, false, false, GRADE_DISPLAY_TYPE_LETTER, NULL, true, false);
        
        foreach ($this->grade_items as $itemid=>$item) {
            if ($item->itemtype == 'course') {
                $this->coursetotalid = $itemid;
        
                if (empty($this->columns)) {
                    $this->columns = array();
                    $this->columns[$itemid] =& $item;
                    break;
                }
            }
        }
    }
    
    //changed button from 'Download' to 'Export'. Added non-empty grades warning
    public function print_continue() {
        global $CFG, $OUTPUT;

        $params = $this->get_export_params();

        echo $OUTPUT->heading(get_string('export', 'grades'));

        echo $OUTPUT->container_start('gradeexportlink');

        if (!$this->userkey) {      // this button should trigger a download prompt
            echo $OUTPUT->single_button(new moodle_url('/grade/export/'.$this->plugin.'/export.php', $params), get_string('export', 'gradeexport_pwr'));

        } else {
            $paramstr = '';
            $sep = '?';
            foreach($params as $name=>$value) {
                $paramstr .= $sep.$name.'='.$value;
                $sep = '&';
            }

            $link = $CFG->wwwroot.'/grade/export/'.$this->plugin.'/dump.php'.$paramstr.'&key='.$this->userkey;

            echo get_string('export', 'gradeexport_pwr').': ' . html_writer::link($link, $link);
        }
        
        echo '<p>
			  <font color=red>
				<br />
				<b>NOTE:</b> The PowerCampus Grade Export function is only for exporting your <b>FINAL</b> gradebook at the end of the semester.<br />
				<b>Using this function prior to setting up the Gradebook will result in wrong grades in PowerCampus.</b><br />
				For more information, click Help and search on gradebook.
              </font>
			  <font color=blue>
				<br /><br />
                The letter grade show below for each student will be written to PowerCampus/Self-Service except for students who have already been given a withdrawal grade (WP/WF).<br />
                Students who have a withdrawal grade for this class will be unaffected by this export.
              </font>
			  <br />
			  </p>';
                
        echo $OUTPUT->container_end();
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
    public function print_grades() {
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
        
        //Array to hold the student IDs and their grade
        $arr_peoplegrades = array('id' => array(), 'grade'=> array());
        $i = 0; //Array element subscript incrementor

        
        //Loop through the list of students for the course and retrieve their Final Grades
        $gui = new graded_users_iterator($this->course, $this->columns, $this->groupid);
        $gui->require_active_enrolment($this->onlyactive);
        $gui->init();

        while ($userdata = $gui->next_user()) {
            $people_id = $userdata->user->idnumber;
            $final_grade = $this->format_grade($userdata->grades[$this->coursetotalid]);

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
