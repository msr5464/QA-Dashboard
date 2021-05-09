<?php
    header('Content-type: application/json');
    require "db-config.php";

    $jsonArray = array();
    if (!isset($_GET['functionname']))
    {
        $jsonArray['error'] = 'No function name!';
    }

    if (!isset($jsonArray['error']))
    {
        switch ($_GET['functionname'])
        {
            case 'getActiveTabs':
                if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 1))
                {
                    $jsonArray['error'] = 'Error in passed arguments!';
                }
                $sql = "select * from vertical where verticalName='" . $_GET['arguments'][0] . "';";
                foreach ($dbo->query($sql) as $row)
                {
                    $jsonArrayItem = array();
                    $jsonArrayItem["isResultsActive"] = $row['isResultsActive'];
                    $jsonArrayItem["isTestrailActive"] = $row['isTestrailActive'];
                    $jsonArrayItem["isJiraActive"] = $row['isJiraActive'];
                    array_push($jsonArray, $jsonArrayItem);
                }
            break;
        }
        echo json_encode($jsonArray);
    }
?>
