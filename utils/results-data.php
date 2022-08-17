<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_results");
}

function getProjectNames($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select projectName from ".getTableName($verticalName)." where YEAR(createdAt)=YEAR('" . $startDate . "') OR YEAR(createdAt)=YEAR('" . $endDate . "') group by projectName order by projectName asc;";

    foreach ($DB->query($sql) as $row)
    {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getAvgPercentage_All($verticalName, $startDate, $endDate, $groupName) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,Floor(sum(passedCases)) as passedCases,Floor(sum(totalCases)) as totalCases,Floor(sum(passedCases)*100/sum(totalCases)) as percentage from (select environment,groupName,avg(totalCases) as totalCases,avg(passedCases) as passedCases from ".getTableName($verticalName)." where groupName in (".$groupName.") and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by environment,projectName) as temp group by environment;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArraySubSet1 = array();
        $jsonArrayItem = array();

        $jsonArrayItem['passedCases'] = $row['passedCases'];
        $jsonArrayItem['totalCases'] = $row['totalCases'];
        $jsonArrayItem['percentage'] = $row['percentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

        array_push($jsonArray, array(
            $row['environment'] . "-data" => $jsonArraySubSet1
        ));
    }
    return $jsonArray;
}

function getAvgPercentage($verticalName, $startDate, $endDate, $environment, $groupName) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,projectName,round(AVG(percentage),0) as percentage from ".getTableName($verticalName)." where environment='".$environment."' and groupName='".$groupName."' and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName order by projectName desc;";
    
    $environmentValue = "";
    $groupNameValue = "";
    $jsonArraySubSet1 = array();
    foreach ($DB->query($sql) as $row)
    {   
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['projectName'];
        $jsonArrayItem['value'] = $row['percentage'];
        $environmentValue = $row['environment'];
        $groupNameValue = $row['groupName'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

    }
    array_push($jsonArray, array(
            "resultsData" => $jsonArraySubSet1
        ));
    array_push($jsonArray, array(
            "environmentValue" => $environmentValue
        ));
    array_push($jsonArray, array(
            "groupNameValue" => $groupNameValue
        ));
    return $jsonArray;
}

function getAvgExecutionTime($verticalName, $startDate, $endDate, $environment, $groupName) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,projectName,totalCases, round(AVG(TIME_TO_SEC(duration))/60,2) as duration from ".getTableName($verticalName)." where environment='".$environment."' and groupName='".$groupName."' and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName order by projectName desc;";
    $environmentValue = "";
    $groupNameValue = "";
    $jsonArraySubSet1 = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $combinedData = array();
    $counter = 0;
    $trendLineAvg = 0;
    $totalDuration = 0;
    $jsonArrayItem = array();
    $jsonArrayItem2 = array();
    $jsonArrayItem3 = array();
    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem2['value'] = $row['duration'];
        array_push($jsonArraySubSet1, $jsonArrayItem2);
        
        $jsonArrayItem3['value'] = $row['totalCases'];
        array_push($jsonArraySubSet2, $jsonArrayItem3);

        $environmentValue = $row['environment'];
        $groupNameValue = $row['groupName'];

        $totalDuration = $totalDuration + $row['duration'];
        $counter++;
    }
    if($counter > 0)
    {
        $trendLineAvg = round($totalDuration/$counter,1);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Time Taken",
        "plottooltext" => "Time Taken by \$label: \$dataValue min",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Number of Tests Executed",
        "renderas" => "line",
        "visible" => "0",
        "plottooltext" => "No. of Tests Executed: \$dataValue",
        "data" => $jsonArraySubSet2
    ));

array_push($jsonArray, array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet,
        "environmentValue" => $environmentValue,
        "groupNameValue" => $groupNameValue,
        "trendLineAvg" => $trendLineAvg
        ));

    return $jsonArray;
}

function getLast7Records($verticalName, $projectName, $startDate, $endDate, $environment, $groupName) {
    global $DB;
    $jsonArray = array();
    $jsonArrayDataSet = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayForPercentage = array();
    $sql = "(select id,buildTag,resultLink,percentage,Date(createdAt) as createdAt from ".getTableName($verticalName)." where projectName in (" . $projectName . ") and environment='".$environment."' and groupName='".$groupName."' and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' order by id desc) order by id desc";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'] . "\n" . $row['buildTag']. ", link- " .$row['resultLink'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $row['percentage'];
        array_push($jsonArrayForPercentage, $jsonArrayItem1);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Execution Builds",
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

function getAvgPercentage_Project($verticalName, $projectName, $startDate, $endDate, $groupName) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,Floor(avg(passedCases)) as passedCases,Floor(avg(totalCases)) as totalCases,Floor(sum(passedCases)*100/sum(totalCases)) as percentage from (select environment,groupName,avg(totalCases) as totalCases,avg(passedCases) as passedCases from ".getTableName($verticalName)." where projectName in (" . $projectName . ") and groupName in (".$groupName.") and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by environment,projectName) as temp group by environment;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArraySubSet1 = array();
        $jsonArrayItem = array();

        $jsonArrayItem['passedCases'] = $row['passedCases'];
        $jsonArrayItem['totalCases'] = $row['totalCases'];
        $jsonArrayItem['percentage'] = $row['percentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

        array_push($jsonArray, array(
            $row['environment'] . "-data" => $jsonArraySubSet1
        ));
    }
    return $jsonArray;
}

function getDailyAvgPercentage_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $environment2, $groupName1, $groupName2) {
    global $DB;
    $jsonArray = array();
    $lastDate = '2010-01-01';
    $lastRegression = 0;
    $lastProduction = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $sql = "Select DATE(createdAt) as createdAt, sum(passedCases)*100/sum(totalCases) as percentage, groupName FROM (SELECT DATE(createdAt) as createdAt, avg(passedCases) as passedCases, avg(totalCases) as totalCases, groupName FROM ".getTableName($verticalName)." WHERE projectName in (" . $projectName . ") and environment in('".$environment1."','".$environment2."') and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt),groupName,projectName) as temp GROUP BY DATE(createdAt),groupName;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();

        if ($lastDate == $row['createdAt'])
        {
            if ($row['groupName'] == $groupName1)
            {
                $jsonArrayItem1['value'] = $row['percentage'];
                $lastRegression = $row['percentage'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if ($row['groupName'] == $groupName2)
            {
                $jsonArrayItem2['value'] = $row['percentage'];
                $lastProduction = $row['percentage'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if ($row['groupName'] == $groupName1)
            {
                $lastRegression = $row['percentage'];
            }

            if ($row['groupName'] == $groupName2)
            {
                $lastProduction = $row['percentage'];
            }

            $jsonArrayItem1['value'] = $lastRegression;
            $jsonArrayItem2['value'] = $lastProduction;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName1,
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName2,
        "data" => $jsonArraySubSet2
    ));
    
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getDailyAvgExecutionTime_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $environment2, $groupName1, $groupName2) {
    global $DB;
    $jsonArray = array();    
    $lastDate = '2010-01-01';
    $lastRegression = 0;
    $lastProduction = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $sql = "Select DATE(createdAt) as createdAt, sum(duration) as duration, groupName FROM (SELECT DATE(createdAt) as createdAt, round(AVG(TIME_TO_SEC(duration))/60,2) as duration, groupName FROM ".getTableName($verticalName)." WHERE projectName in (" . $projectName . ") and environment in('".$environment1."','".$environment2."') and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt),groupName,projectName) as temp GROUP BY DATE(createdAt),groupName;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();

        if ($lastDate == $row['createdAt'])
        {
            if ($row['groupName'] == $groupName1)
            {
                $jsonArrayItem1['value'] = $row['duration'];
                $lastRegression = $row['duration'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if ($row['groupName'] == $groupName2)
            {
                $jsonArrayItem2['value'] = $row['duration'];
                $lastProduction = $row['duration'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if ($row['groupName'] == $groupName1)
            {
                $lastRegression = $row['duration'];
            }

            if ($row['groupName'] == $groupName2)
            {
                $lastProduction = $row['duration'];
            }

            $jsonArrayItem1['value'] = $lastRegression;
            $jsonArrayItem2['value'] = $lastProduction;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName1,
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName2,
        "data" => $jsonArraySubSet2
    ));
    
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getTotalCasesGroupwise_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $environment2, $groupName1, $groupName2) {
    global $DB;
    $jsonArray = array();
    $lastDate = '2010-01-01';
    $lastRegression = 0;
    $lastProduction = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $sql = "Select DATE(createdAt) as createdAt, sum(totalCases) as totalCases, groupName from (SELECT DATE(createdAt) as createdAt, Floor(avg(totalCases)) as totalCases, groupName FROM ".getTableName($verticalName)." WHERE projectName in (" . $projectName . ") and environment in('".$environment1."','".$environment2."') and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt),groupName,projectName) as temp GROUP BY DATE(createdAt),groupName;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();

        if ($lastDate == $row['createdAt'])
        {
            if ($row['groupName'] == $groupName1)
            {
                $jsonArrayItem1['value'] = $row['totalCases'];
                $lastRegression = $row['totalCases'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if ($row['groupName'] == $groupName2)
            {
                $jsonArrayItem2['value'] = $row['totalCases'];
                $lastProduction = $row['totalCases'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if ($row['groupName'] == $groupName1)
            {
                $lastRegression = $row['totalCases'];
            }

            if ($row['groupName'] == $groupName2)
            {
                $lastProduction = $row['totalCases'];
            }

            $jsonArrayItem1['value'] = $lastRegression;
            $jsonArrayItem2['value'] = $lastProduction;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName1,
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $groupName2,
        "data" => $jsonArraySubSet2
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
        case 'getAvgPercentage_All':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getAvgPercentage_All($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getAvgPercentage':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getAvgPercentage($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getAvgExecutionTime':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getAvgExecutionTime($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getLast7Records':
            validateParams(6, $_GET['arguments']);
            $jsonArray = getLast7Records($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5]);
        break;
        case 'getAvgPercentage_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getAvgPercentage_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getDailyAvgPercentage_Project':
            validateParams(8, $_GET['arguments']);
            $jsonArray = getDailyAvgPercentage_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7]);
            break;
        case 'getDailyAvgExecutionTime_Project':
            validateParams(8, $_GET['arguments']);
            $jsonArray = getDailyAvgExecutionTime_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7]);
            break;
        case 'getTotalCasesGroupwise_Project':
            validateParams(8, $_GET['arguments']);
            $jsonArray = getTotalCasesGroupwise_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7]);
            break;
    }
    echo json_encode($jsonArray);
}
?>