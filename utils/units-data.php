<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_units");
}

function getProjectNames($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select projectName from ".getTableName($verticalName)." where YEAR(createdAt)>=YEAR('" . $startDate . "') OR YEAR(createdAt)=YEAR('" . $endDate . "') group by projectName order by projectName asc;";

    foreach ($DB->query($sql) as $row)
    {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getCoverageNumbers_All($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "Select FLOOR((sum(f.linesCurrentCount)/sum(f.linesTotalCount))*100) as linesPercentage, f.id from ( select projectName, max(id) as id from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by projectName) as x inner join ".getTableName($verticalName)." as f on f.projectName = x.projectName and f.id = x.id order by id desc;";

foreach ($DB->query($sql) as $row)
    {
        $jsonArraySubSet1 = array();
        $jsonArrayItem = array();

        $jsonArrayItem['label'] = "Pending Coverage";
        $jsonArrayItem['value'] = 100 - $row['linesPercentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

        $jsonArrayItem['label'] = "Current Coverage";
        $jsonArrayItem['value'] = $row['linesPercentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);
    }

    array_push($jsonArray, array(
            "coverage-data" => $jsonArraySubSet1
        ));
    return $jsonArray;
}

function getCoverageDelta($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT a.projectName as projectName, a.linesPercentage as newLinesPercentage,b.linesPercentage as oldLinesPercentage FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and (a.linesPercentage != b.linesPercentage) and a.projectName not like 'Pod%' group by projectName order by (a.linesPercentage - b.linesPercentage) desc";
    //$sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();

        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);
        $jsonArrayItem1['value'] = $row['oldLinesPercentage'];
        $increment = $row['newLinesPercentage'] - $row['oldLinesPercentage'];

        if ($increment > 0)
        {
            $jsonArrayItem2['value'] = $increment;

        }
        else
        {
            $jsonArrayItem3['value'] = $increment;

        }
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Previous Coverage",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Incremented",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Decremented",
        "data" => $jsonArraySubSet3
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}


function getFullCoverageData($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayForFullCoverage = array();
    $jsonArrayForP0Coverage = array();
    $jsonArrayForP1Coverage = array();
    $jsonArrayForP2Coverage = array();

    $sql = "select * from ".getTableName($verticalName)." as a where id in(select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Pod%' order by linesPercentage desc;";
    //$sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem1 = array();
        $jsonArrayItem1['label'] = $row['projectName'];
        $jsonArrayItem1['value'] = $row['linesPercentage'];
        array_push($jsonArrayForFullCoverage, $jsonArrayItem1);
    }
    $jsonArray = array(
        "FullCoverage" => $jsonArrayForFullCoverage,
    );
    return $jsonArray;
}



function getCoverageNumbers_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $sql = "select * from ".getTableName($verticalName)." where id = (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName=" . $projectName . ");";
    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem["totalCoverage"] = $row['linesPercentage'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getLast7Records($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $jsonArrayDataSet = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayForPercentage = array();
    $sql = "(select id,buildTag,resultLink,linesPercentage,Date(createdAt) as createdAt from ".getTableName($verticalName)." where projectName in (" . $projectName . ") and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' order by id desc) order by id desc";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'] . "\n" . $row['buildTag']. ", link- " .$row['resultLink'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $row['linesPercentage'];
        array_push($jsonArrayForPercentage, $jsonArrayItem1);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Code Coverage Builds",
        "plottooltext" => "\$label: \$dataValue",
        "visible" => "1",
        "data" => $jsonArrayForPercentage
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );

    return $jsonArray;
}

function getTestcaseCountTrend_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSet5 = array();
    $jsonArraySubSet6 = array();
    $jsonArraySubSet7 = array();
    $jsonArraySubSet8 = array();
    $jsonArraySubSet9 = array();
    $jsonArraySubSet10 = array();
    $jsonArraySubSet11 = array();
    $jsonArraySubSet12 = array();

    $sql = "SELECT DATE(createdAt) as createdAt, max(linesTotalCount) as linesTotalCount, max(linesCurrentCount) as linesCurrentCount, Floor(max(linesCurrentCount)/max(linesTotalCount)*100) as linesPercentage, max(statementsTotalCount) as statementsTotalCount, max(statementsCurrentCount) as statementsCurrentCount, Floor(max(statementsCurrentCount)/max(statementsTotalCount)*100) as statementsPercentage, max(branchesTotalCount) as branchesTotalCount, max(branchesCurrentCount) as branchesCurrentCount, Floor(max(branchesCurrentCount)/max(branchesTotalCount)*100) as branchesPercentage, max(functionsTotalCount) as functionsTotalCount, max(functionsCurrentCount) as functionsCurrentCount, Floor(max(functionsCurrentCount)/max(functionsTotalCount)*100) as functionsPercentage FROM ".getTableName($verticalName)." WHERE projectName=" . $projectName . " and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt);";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $jsonArrayItem5 = array();
        $jsonArrayItem6 = array();
        $jsonArrayItem7 = array();
        $jsonArrayItem8 = array();
        $jsonArrayItem9 = array();
        $jsonArrayItem10 = array();
        $jsonArrayItem11 = array();
        $jsonArrayItem12 = array();

        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem1['value'] = $row['linesTotalCount'];
        $jsonArrayItem2['value'] = $row['linesCurrentCount'];
        $jsonArrayItem3['value'] = $row['statementsTotalCount'];
        $jsonArrayItem4['value'] = $row['statementsCurrentCount'];
        $jsonArrayItem5['value'] = $row['branchesTotalCount'];
        $jsonArrayItem6['value'] = $row['branchesCurrentCount'];
        $jsonArrayItem7['value'] = $row['functionsTotalCount'];
        $jsonArrayItem8['value'] = $row['functionsCurrentCount'];
        $jsonArrayItem9['value'] = $row['linesPercentage'];
        $jsonArrayItem10['value'] = $row['statementsPercentage'];
        $jsonArrayItem11['value'] = $row['branchesPercentage'];
        $jsonArrayItem12['value'] = $row['functionsPercentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);
        array_push($jsonArraySubSet5, $jsonArrayItem5);
        array_push($jsonArraySubSet6, $jsonArrayItem6);
        array_push($jsonArraySubSet7, $jsonArrayItem7);
        array_push($jsonArraySubSet8, $jsonArrayItem8);
        array_push($jsonArraySubSet9, $jsonArrayItem9);
        array_push($jsonArraySubSet10, $jsonArrayItem10);
        array_push($jsonArraySubSet11, $jsonArrayItem11);
        array_push($jsonArraySubSet12, $jsonArrayItem12);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Lines %",
        "data" => $jsonArraySubSet9
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Lines",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Covered Lines",
        "data" => $jsonArraySubSet2
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Statements %",
        "data" => $jsonArraySubSet10,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Statements",
        "data" => $jsonArraySubSet3,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Covered Statements",
        "data" => $jsonArraySubSet4,
        "visible" => "0"
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Branches %",
        "data" => $jsonArraySubSet12,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Branches",
        "data" => $jsonArraySubSet5,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Covered Branches",
        "data" => $jsonArraySubSet6,
        "visible" => "0"
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Functions %",
        "data" => $jsonArraySubSet11,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Functions",
        "data" => $jsonArraySubSet7,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Covered Functions",
        "data" => $jsonArraySubSet8,
        "visible" => "0"
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

$jsonArray = array();
if (!isset($_GET['functionname']))
{
    $jsonArray['error'] = 'No function name!';
}
if (!isset($jsonArray['error']))
{   
    switch ($_GET['functionname'])
    {
        case 'getProjectNames':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getProjectNames($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getCoverageNumbers_All':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getCoverageNumbers_All($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getCoverageDelta':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getCoverageDelta($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getFullCoverageData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getFullCoverageData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getCoverageNumbers_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getCoverageNumbers_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getLast7Records':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getLast7Records($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getTestcaseCountTrend_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTestcaseCountTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
    }
    echo json_encode($jsonArray);
}
?>