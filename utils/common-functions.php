<?php
include 'ChromePhp.php';

function errorMessage($str) {
    return '<div style="width:50%; margin:0 auto; border:2px solid #F00;padding:2px; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}

function simple_redirect($url) {

    echo "<script language=\"JavaScript\">\n";
    echo "<!-- hide from old browser\n\n";

    echo "window.location = \"" . $url . "\";\n";

    echo "-->\n";
    echo "</script>\n";

    return true;
}

function getHomeURL() {
    return HTTP_SERVER . SITE_DIR;
}

//Encryption function
function easy_crypt($string) {
    return base64_encode($string . "_@#!@");
}

//Decodes encryption
function easy_decrypt($str) {
    $str = base64_decode($str);
    return str_replace("_@#!@", "", $str);
}

function getActiveVerticalData() {
    global $DB;
    $sql = "select * from configurations where isActive='1' order by verticalName desc";
    try {
        $stmt = $DB->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        errorMessage($ex->getMessage());
    }
    return $results;
}

function getVerticalTableData() {
    global $DB;
    $sql = "select * from configurations order by verticalName desc";
    try {
        $stmt = $DB->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        errorMessage($ex->getMessage());
    }
    return $results;
}

function getLastUpdatedTime() {
    global $DB;
    global $verticalName;
    global $projectName;
    global $pageName;

    $vertical = $verticalName;
    if($vertical == null || $vertical == "")
        $vertical = $_COOKIE['entity'];

    $results = "";
    $tableName = "";
    $sql = "select * from configurations where verticalName='".$vertical."';";
    try {
        foreach ($DB->query($sql) as $row)
            $tableName = $row['tableNamePrefix'];
    } catch (Exception $ex) {
        errorMessage($ex->getMessage());
    }

    if($projectName != null && $projectName != "") {
        $sql = "select createdAt from ".$tableName."_".$pageName." where projectName in (" . $projectName . ") order by id desc limit 1";
    }
    else {
        $sql = "select createdAt from ".$tableName."_".$pageName." order by id desc limit 1";
    }
    try {
        $stmt = $DB->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
    } catch (Exception $ex) {
        errorMessage($ex->getMessage());
    }
    return $results;
}

function validateParams($counter, $params)
{
    $jsonArray = array();
    if (!is_array($params) || (count($params) < $counter)) {
        $jsonArray['error'] = "Error in passed arguments!";
        $jsonArray['failure_reason'] = "Expected parameters = ".$counter." but actul = ".count($params);
        echo json_encode($jsonArray);
    }
}

function showPodLevelData($sql, $isPodDataActive)
{
    $updatedSql = $sql;
    if($isPodDataActive == 1)
     {
        $updatedSql = str_replace("projectName not like 'Pod%'", "projectName like 'Pod%'", $sql);
     }
    return $updatedSql;
}

function updateGroupBy($sql, $startDate, $endDate)
{
    $updatedSql = $sql;
    logger("Sdate = ".$startDate);
    logger("Edate = ".$endDate);
    $earlier = new DateTime($startDate);
    $later = new DateTime($endDate);
    $diff = $later->diff($earlier)->format("%a");
    logger("Diff = ".$diff);
    if($diff > 185)
        $updatedSql = str_replace("GROUP BY DATE", "GROUP BY MONTH", $sql);
    else if($diff > 31)
        $updatedSql = str_replace("GROUP BY DATE", "GROUP BY WEEK", $sql);
    return $updatedSql;
}

function getMedian($arr) {
    sort($arr);
    $count = count($arr);
    $middleval = floor(($count-1)/2);
    if ($count % 2) {
        $median = $arr[$middleval];
    } else {
        $low = $arr[$middleval];
        $high = $arr[$middleval+1];
        $median = (($low+$high)/2);
    }
    if($median == null)
        $median = 0;
    return $median;
}

function logger($value)
{
    ChromePhp::log($value);
}

?>