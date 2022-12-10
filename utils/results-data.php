<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_results");
}

function getProjectNames($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select projectName from ".getTableName($verticalName)." where (YEAR(createdAt)>=YEAR('" . $startDate . "') OR YEAR(createdAt)=YEAR('" . $endDate . "')) group by projectName order by projectName asc;";

    foreach ($DB->query($sql) as $row)
    {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getAvgPercentage_All($verticalName, $startDate, $endDate, $environment1, $groupName1, $environment2, $groupName2, $environment3, $groupName3, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,Floor(sum(totalCases)) as totalCases,Floor(sum(passedCases)) as passedCases,Floor(sum(passedCases)*100/sum(totalCases)) as percentage from (select environment,groupName,avg(totalCases) as totalCases,avg(passedCases) as passedCases from ".getTableName($verticalName)." where ((groupName='".$groupName1."' and environment='".$environment1."') or (groupName='".$groupName2."' and environment='".$environment2."') or (groupName='".$groupName3."' and environment='".$environment3."')) and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by groupName,environment,projectName) as x group by groupName,environment;";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArraySubSet1 = array();
        $jsonArrayItem = array();

        $jsonArrayItem['passedCases'] = $row['passedCases'];
        $jsonArrayItem['totalCases'] = $row['totalCases'];
        $jsonArrayItem['percentage'] = $row['percentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

        array_push($jsonArray, array(
            $row['environment'] . "-" . $row['groupName'] => $jsonArraySubSet1
        ));
    }
    return $jsonArray;
}

function getAvgPercentage($verticalName, $startDate, $endDate, $environment, $groupName, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,projectName,Floor(sum(passedCases)*100/sum(totalCases)) as percentage from ".getTableName($verticalName)." where (groupName='".$groupName."' and environment='".$environment."')  and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by groupName,environment,projectName order by projectName;";
    $sql = showPodLevelData($sql, $isPodDataActive);

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

function getAvgExecutionTime($verticalName, $startDate, $endDate, $environment, $groupName, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select projectName,environment,groupName,Floor(avg(totalCases)) as totalCases,Floor(avg(passedCases)) as passedCases, round(avg(duration)/60,2) as duration from ".getTableName($verticalName)." where (groupName='".$groupName."' and environment='".$environment."')  and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by projectName order by projectName;";
    $sql = showPodLevelData($sql, $isPodDataActive);

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
    $sql = "(select id,buildTag,resultLink,percentage,Date(createdAt) as createdAt from ".getTableName($verticalName)." where projectName in (" . $projectName . ") and environment='".$environment."' and groupName='".$groupName."' and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' order by createdAt desc) order by createdAt desc";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'] . "\n" . $row['buildTag']. ", link- " .$row['resultLink'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $row['percentage'];
        array_push($jsonArrayForPercentage, $jsonArrayItem1);
    }

    if(sizeof($jsonArrayForPercentage) <= 0)
    {
        $jsonArrayItemL = array();
        $jsonArrayItemL['label'] = "No data to display.";
        array_push($jsonArraySubCategory, $jsonArrayItemL);

        $jsonArrayItemV = array();
        $jsonArrayItemV['value'] = "No data to display.";
        array_push($jsonArrayForPercentage, $jsonArrayItemV);
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

function getAvgPercentage_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $groupName1, $environment2, $groupName2, $environment3, $groupName3, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select environment,groupName,Floor(sum(totalCases)) as totalCases,Floor(sum(passedCases)) as passedCases,Floor(sum(passedCases)*100/sum(totalCases)) as percentage from (select environment,groupName,avg(totalCases) as totalCases,avg(passedCases) as passedCases from ".getTableName($verticalName)." where ((groupName='".$groupName1."' and environment='".$environment1."') or (groupName='".$groupName2."' and environment='".$environment2."') or (groupName='".$groupName3."' and environment='".$environment3."')) and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by groupName,environment,projectName) as x group by groupName,environment;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArraySubSet1 = array();
        $jsonArrayItem = array();

        $jsonArrayItem['passedCases'] = $row['passedCases'];
        $jsonArrayItem['totalCases'] = $row['totalCases'];
        $jsonArrayItem['percentage'] = $row['percentage'];
        array_push($jsonArraySubSet1, $jsonArrayItem);

        array_push($jsonArray, array(
            $row['environment'] . "-" . $row['groupName'] => $jsonArraySubSet1
        ));
    }
    return $jsonArray;
}

function getDailyAvgPercentage_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $groupName1, $environment2, $groupName2, $environment3, $groupName3) {
    global $DB;
    $jsonArray = array();
    $lastDate = '2010-01-01';
    $lastValueForEnv1 = 0;
    $lastValueForEnv2 = 0;
    $lastValueForEnv3 = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "select DATE(createdAt) as createdAt, sum(passedCases)*100/sum(totalCases) as percentage, environment,groupName from (select createdAt,environment,groupName,avg(passedCases) as passedCases, avg(totalCases) as totalCases from ".getTableName($verticalName)." where ((groupName='".$groupName1."' and environment='".$environment1."') or (groupName='".$groupName2."' and environment='".$environment2."') or (groupName='".$groupName3."' and environment='".$environment3."')) and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") GROUP BY DATE(createdAt),groupName,environment,projectName) as x GROUP BY DATE(createdAt),groupName,environment;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();

        if ($lastDate == $row['createdAt'])
        {
            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $jsonArrayItem1['value'] = $row['percentage'];
                $lastValueForEnv1 = $row['percentage'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $jsonArrayItem2['value'] = $row['percentage'];
                $lastValueForEnv2 = $row['percentage'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $jsonArrayItem3['value'] = $row['percentage'];
                $lastValueForEnv3 = $row['percentage'];
                array_pop($jsonArraySubSet3);
                array_push($jsonArraySubSet3, $jsonArrayItem3);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $lastValueForEnv1 = $row['percentage'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $lastValueForEnv2 = $row['percentage'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $lastValueForEnv3 = $row['percentage'];
            }

            $jsonArrayItem1['value'] = $lastValueForEnv1;
            $jsonArrayItem2['value'] = $lastValueForEnv2;
            $jsonArrayItem3['value'] = $lastValueForEnv3;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment1."-".$groupName1,
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment2."-".$groupName2,
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment3."-".$groupName3,
        "data" => $jsonArraySubSet3
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getDailyAvgExecutionTime_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $groupName1, $environment2, $groupName2, $environment3, $groupName3) {
    global $DB;
    $jsonArray = array();
    $lastDate = '2010-01-01';
    $lastValueForEnv1 = 0;
    $lastValueForEnv2 = 0;
    $lastValueForEnv3 = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "select DATE(createdAt) as createdAt, round(sum(duration)/60,2) as duration, environment,groupName from (select createdAt,environment,groupName,avg(duration) as duration from ".getTableName($verticalName)." where ((groupName='".$groupName1."' and environment='".$environment1."') or (groupName='".$groupName2."' and environment='".$environment2."') or (groupName='".$groupName3."' and environment='".$environment3."')) and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") GROUP BY DATE(createdAt),groupName,environment,projectName) as x GROUP BY DATE(createdAt),groupName,environment;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();

        if ($lastDate == $row['createdAt'])
        {
            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $jsonArrayItem1['value'] = $row['duration'];
                $lastValueForEnv1 = $row['duration'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $jsonArrayItem2['value'] = $row['duration'];
                $lastValueForEnv2 = $row['duration'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $jsonArrayItem3['value'] = $row['duration'];
                $lastValueForEnv3 = $row['duration'];
                array_pop($jsonArraySubSet3);
                array_push($jsonArraySubSet3, $jsonArrayItem3);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $lastValueForEnv1 = $row['duration'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $lastValueForEnv2 = $row['duration'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $lastValueForEnv3 = $row['duration'];
            }

            $jsonArrayItem1['value'] = $lastValueForEnv1;
            $jsonArrayItem2['value'] = $lastValueForEnv2;
            $jsonArrayItem3['value'] = $lastValueForEnv3;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment1."-".$groupName1,
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment2."-".$groupName2,
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment3."-".$groupName3,
        "data" => $jsonArraySubSet3
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getTotalCasesGroupwise_Project($verticalName, $projectName, $startDate, $endDate, $environment1, $groupName1, $environment2, $groupName2, $environment3, $groupName3) {
    global $DB;
    $jsonArray = array();
    $lastDate = '2010-01-01';
    $lastValue1ForEnv1 = 0;
    $lastValue1ForEnv2 = 0;
    $lastValue1ForEnv3 = 0;
    $lastValue2ForEnv1 = 0;
    $lastValue2ForEnv2 = 0;
    $lastValue2ForEnv3 = 0;
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSet5 = array();
    $jsonArraySubSet6 = array();
    $sql = "select DATE(createdAt) as createdAt, Floor(sum(totalCases)) as totalCases, Floor(sum(passedCases)) as passedCases, groupName, environment from (select createdAt,environment,groupName,avg(totalCases) as totalCases,avg(passedCases) as passedCases from ".getTableName($verticalName)." where ((groupName='".$groupName1."' and environment='".$environment1."') or (groupName='".$groupName2."' and environment='".$environment2."') or (groupName='".$groupName3."' and environment='".$environment3."')) and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") GROUP BY DATE(createdAt),groupName,environment,projectName) as x GROUP BY DATE(createdAt),groupName,environment;";
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

        if ($lastDate == $row['createdAt'])
        {
            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $jsonArrayItem1['value'] = $row['totalCases'];
                $lastValue1ForEnv1 = $row['totalCases'];
                array_pop($jsonArraySubSet1);
                array_push($jsonArraySubSet1, $jsonArrayItem1);

                $jsonArrayItem4['value'] = $row['passedCases'];
                $lastValue2ForEnv1 = $row['passedCases'];
                array_pop($jsonArraySubSet4);
                array_push($jsonArraySubSet4, $jsonArrayItem4);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $jsonArrayItem2['value'] = $row['totalCases'];
                $lastValue1ForEnv2 = $row['totalCases'];
                array_pop($jsonArraySubSet2);
                array_push($jsonArraySubSet2, $jsonArrayItem2);

                $jsonArrayItem5['value'] = $row['passedCases'];
                $lastValue2ForEnv2 = $row['passedCases'];
                array_pop($jsonArraySubSet5);
                array_push($jsonArraySubSet5, $jsonArrayItem5);
            }
            else if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $jsonArrayItem3['value'] = $row['totalCases'];
                $lastValue1ForEnv3 = $row['totalCases'];
                array_pop($jsonArraySubSet3);
                array_push($jsonArraySubSet3, $jsonArrayItem3);

                $jsonArrayItem6['value'] = $row['passedCases'];
                $lastValue2ForEnv3 = $row['passedCases'];
                array_pop($jsonArraySubSet6);
                array_push($jsonArraySubSet6, $jsonArrayItem6);
            }
        }
        else
        {
            $lastDate = $row['createdAt'];
            $jsonArrayItem['label'] = $row['createdAt'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if (strtoupper($row['environment']) == strtoupper($environment1) & strtoupper($row['groupName']) == strtoupper($groupName1))
            {
                $lastValue1ForEnv1 = $row['totalCases'];
                $lastValue2ForEnv1 = $row['passedCases'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment2) & strtoupper($row['groupName']) == strtoupper($groupName2))
            {
                $lastValue1ForEnv2 = $row['totalCases'];
                $lastValue2ForEnv2 = $row['passedCases'];
            }

            if (strtoupper($row['environment']) == strtoupper($environment3) & strtoupper($row['groupName']) == strtoupper($groupName3))
            {
                $lastValue1ForEnv3 = $row['totalCases'];
                $lastValue2ForEnv3 = $row['passedCases'];
            }

            $jsonArrayItem1['value'] = $lastValue1ForEnv1;
            $jsonArrayItem2['value'] = $lastValue1ForEnv2;
            $jsonArrayItem3['value'] = $lastValue1ForEnv3;
            $jsonArrayItem4['value'] = $lastValue2ForEnv1;
            $jsonArrayItem5['value'] = $lastValue2ForEnv2;
            $jsonArrayItem6['value'] = $lastValue2ForEnv3;
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
            array_push($jsonArraySubSet4, $jsonArrayItem4);
            array_push($jsonArraySubSet5, $jsonArrayItem5);
            array_push($jsonArraySubSet6, $jsonArrayItem6);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment1."-".$groupName1."-totalCases",
        "data" => $jsonArraySubSet1,
        "visible" => "1"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment1."-".$groupName1."-passedCases",
        "data" => $jsonArraySubSet4,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment2."-".$groupName2."-totalCases",
        "data" => $jsonArraySubSet2,
        "visible" => "1"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment2."-".$groupName2."-passedCases",
        "data" => $jsonArraySubSet5,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment3."-".$groupName3."-totalCases",
        "data" => $jsonArraySubSet3,
        "visible" => "1"
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => $environment3."-".$groupName3."-passedCases",
        "data" => $jsonArraySubSet6,
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
        case 'getAvgPercentage_All':
            validateParams(10, $_GET['arguments']);
            $jsonArray = getAvgPercentage_All($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7], $_GET['arguments'][8], $_GET['arguments'][9]);
        break;
        case 'getAvgPercentage':
            validateParams(6, $_GET['arguments']);
            $jsonArray = getAvgPercentage($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5]);
        break;
        case 'getAvgExecutionTime':
            validateParams(6, $_GET['arguments']);
            $jsonArray = getAvgExecutionTime($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5]);
        break;
        case 'getLast7Records':
            validateParams(6, $_GET['arguments']);
            $jsonArray = getLast7Records($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5]);
        break;
        case 'getAvgPercentage_Project':
            validateParams(11, $_GET['arguments']);
            $jsonArray = getAvgPercentage_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7], $_GET['arguments'][8], $_GET['arguments'][9], $_GET['arguments'][10]);
        break;
        case 'getDailyAvgPercentage_Project':
            validateParams(10, $_GET['arguments']);
            $jsonArray = getDailyAvgPercentage_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7], $_GET['arguments'][8], $_GET['arguments'][9]);
            break;
        case 'getDailyAvgExecutionTime_Project':
            validateParams(10, $_GET['arguments']);
            $jsonArray = getDailyAvgExecutionTime_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7], $_GET['arguments'][8], $_GET['arguments'][9]);
            break;
        case 'getTotalCasesGroupwise_Project':
            validateParams(10, $_GET['arguments']);
            $jsonArray = getTotalCasesGroupwise_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5], $_GET['arguments'][6], $_GET['arguments'][7], $_GET['arguments'][8], $_GET['arguments'][9]);
            break;
    }
    echo json_encode($jsonArray);
}
?>