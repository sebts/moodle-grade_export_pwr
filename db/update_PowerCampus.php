<?php
/*
** Contains functions to connect to and execute queries on Campus6
*/


require_once 'config.php';

//Connection to the database
function open_mydb() {
    global $PWR;

    $connectionInfo = array("Database"=>$PWR->dbname, "UID"=>$PWR->dbuser, "PWD"=>$PWR->dbpass);
    $db = sqlsrv_connect($PWR->dbhost, $connectionInfo);
    if ($db) {
        return $db;
    } else {
        die('Unable to connect to the database.');
    }
}

function close_mydb($db) {
    return sqlsrv_close($db);
}

function update_transcript( $arr_peoplegrades, $ubound
                          , $acad_year, $acad_term, $event_id, $section
                          , $rev_date, $rev_time, $opid, $terminal
                          )
{
    $errmsg = '';

    $db = open_mydb();
    for($i= 0; $i <= $ubound; $i++){
        $people_id = $arr_peoplegrades[$i]['id'];
        $final_grade = $arr_peoplegrades[$i]['grade'];

        $sql = "UPDATE TranscriptDetail"
             . "   SET final_grade = Upper(Replace('$final_grade',' ',''))"
             . "     , revision_date = '$rev_date'"
             . "     , revision_time = '$rev_time'"
             . "     , revision_opid = Replace('$opid','000','')"
             . "     , revision_terminal = '$terminal'"
             . " WHERE add_drop_wait = 'A'"
             . "   AND academic_year = '$acad_year'"
             . "   AND academic_term = '$acad_term'"
             . "   AND event_id      = '$event_id'"
             . "   AND section       = '$section'"
             . "   AND FINAL_GRADE not in ('W','WP','WF')"
             . "   AND people_id     = (SELECT pe.People_ID"
             . "                          FROM People pe LEFT JOIN"
             . "                               UserDefinedInd ud ON pe.People_ID = ud.People_ID"
             . "                         WHERE pe.People_ID  = '$people_id'"
             . "                            OR ud.AD_User_ID = '$people_id'"
             . "                       )";

        if (!sqlsrv_query($db, $sql)) {
            $errmsg .= "<br>Error occured while updating $people_id with final grade '$final_grade' : ";
            $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
            foreach ($errors as $err)
            {
                $errmsg += "MsgRet: (".$err['code'].") ".$err['message'];
            }

        }
    }

    close_mydb($db);

    if (!empty($errmsg)) {
        $errmsg = '<br /><p style="color:red;font-size:20px;text-align:center">Update Error</p>'
                . '<p style="color:black">Please copy and notify IT of  the following error(s):</p>'
                . '<br>Academic Year = '.$acad_year.'<br>Academic Term = '.$acad_term
                . '<br>Event ID = '.$event_id.'<br>Section = '.$section
                . '<br>Revision Date = '.$rev_date.'<br>Revision Time = '.$rev_time
                . '<br>OPID = '.$opid.'<br>Terminal = '.$terminal.'<p />'
                . $errmsg;
    }
    return $errmsg;
}

?>
