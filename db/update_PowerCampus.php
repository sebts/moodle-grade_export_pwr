<?php
/*
** Contains functions to connect to and execute queries on Campus6
*/


require_once 'config.php';

//Connection to the database
function open_mydb() {
	global $PWR;
	
    $db = mssql_connect($PWR->dbhost,$PWR->dbuser,$PWR->dbpass);
    if (!$db || !mssql_select_db($PWR->dbname,$db)) {
        die('Unable to connect to the database.');
    } else {
        return $db;
    }
}

function close_mydb($db) {
    return mssql_close($db);
}

// To pretend an error occurred
function ooops( $opid )
{
	global $PWR;

	close_mydb($db);
	mssql_select_db($PWR->dbname,$db);
    $errmsg = 'Ooops '.$opid.' no DB connection -- '.mssql_get_last_message();
    return $errmsg;
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
             . "   AND people_id     = (SELECT pe.People_ID"
             . "                          FROM People pe LEFT JOIN"
             . "                               UserDefinedInd ud ON pe.People_ID = ud.People_ID"
             . "                         WHERE pe.People_ID  = '$people_id'"
             . "                            OR ud.AD_User_ID = '$people_id'"
             . "                       )";

        if (!mssql_query($sql, $db)) {
            $errmsg .= "<br>Error occured while updating $people_id with final grade '$final_grade' : MsgRet: ".mssql_get_last_message();
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
