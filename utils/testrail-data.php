<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_testrail");
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
    $sql = "Select sum(f.alreadyAutomated) as alreadyAutomated,sum(f.totalAutomationCases) as totalAutomationCases, FLOOR((sum(f.alreadyAutomated)/sum(f.totalAutomationCases))*100) as automationCoveragePerc, sum(f.p0AutomatedCases) as p0AutomatedCases, sum(f.p0Cases) as p0Cases, FLOOR((sum(f.p0AutomatedCases)/sum(f.p0Cases))*100) as p0CoveragePerc, sum(f.p1AutomatedCases) as p1AutomatedCases, sum(f.p1Cases) as p1Cases, FLOOR((sum(f.p1AutomatedCases)/sum(f.p1Cases))*100) as p1CoveragePerc, f.id from ( select projectName, max(id) as id from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by projectName) as x inner join ".getTableName($verticalName)." as f on f.projectName = x.projectName and f.id = x.id order by id desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        //Query should return only 1 row, otherwise this logic will not work properly
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        $jsonArraySubSet5 = array();
        $jsonArraySubSet6 = array();

        $jsonArrayItem['label'] = "Pending Full Coverage";
        $jsonArrayItem['value'] = 100 - $row['automationCoveragePerc'];
        array_push($jsonArraySubSet1, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved Full Coverage";
        $jsonArrayItem['value'] = $row['automationCoveragePerc'];
        array_push($jsonArraySubSet1, $jsonArrayItem);
        array_push($jsonArray, array(
            "FullCoverage-data" => $jsonArraySubSet1
        ));

        $jsonArrayItem['label'] = "Pending P0 Coverage";
        $jsonArrayItem['value'] = 100 - $row['p0CoveragePerc'];
        array_push($jsonArraySubSet2, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P0 Coverage";
        $jsonArrayItem['value'] = $row['p0CoveragePerc'];
        array_push($jsonArraySubSet2, $jsonArrayItem);
        array_push($jsonArray, array(
            "P0Coverage-data" => $jsonArraySubSet2
        ));

        $jsonArrayItem['label'] = "Pending P1 Coverage";
        $jsonArrayItem['value'] = 100 - $row['p1CoveragePerc'];
        array_push($jsonArraySubSet3, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P1 Coverage";
        $jsonArrayItem['value'] = $row['p1CoveragePerc'];
        array_push($jsonArraySubSet3, $jsonArrayItem);
        array_push($jsonArray, array(
            "P1Coverage-data" => $jsonArraySubSet3
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['alreadyAutomated'];
        $jsonArrayItem1['automatableCases'] = $row['totalAutomationCases'];
        array_push($jsonArraySubSet4, $jsonArrayItem1);
        array_push($jsonArray, array(
            "FullAutomation-data" => $jsonArraySubSet4
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['p0AutomatedCases'];
        $jsonArrayItem1['automatableCases'] = $row['p0Cases'];
        array_push($jsonArraySubSet5, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P0Automation-data" => $jsonArraySubSet5
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['p1AutomatedCases'];
        $jsonArrayItem1['automatableCases'] = $row['p1Cases'];
        array_push($jsonArraySubSet6, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P1Automation-data" => $jsonArraySubSet6
        ));
    }
    return $jsonArray;
}

function getP0CoverageChange($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT a.projectName as projectName, a.p0CoveragePerc as newP0CoveragePerc,b.p0CoveragePerc as oldP0CoveragePerc FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and (a.p0CoveragePerc != b.p0CoveragePerc) and a.projectName not like 'Pod%' group by projectName order by (a.p0CoveragePerc - b.p0CoveragePerc) desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();

        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);
        $jsonArrayItem1['value'] = $row['oldP0CoveragePerc'];
        $increment = $row['newP0CoveragePerc'] - $row['oldP0CoveragePerc'];

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
        "seriesname" => "Previous P0 Coverage",
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

function getP1CoverageChange($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT a.projectName as projectName, a.p1CoveragePerc as newP1CoveragePerc,b.p1CoveragePerc as oldP1CoveragePerc FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and (a.p1CoveragePerc != b.p1CoveragePerc) and a.projectName not like 'Pod%' group by projectName order by (a.p1CoveragePerc - b.p1CoveragePerc) desc";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();

        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);
        $jsonArrayItem1['value'] = $row['oldP1CoveragePerc'];
        $increment = $row['newP1CoveragePerc'] - $row['oldP1CoveragePerc'];

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
        "seriesname" => "Previous P1 Coverage",
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

function getAutomatedCountChange($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT projectName,alreadyAutomated,SUM(positiveDelta) as positiveDelta,SUM(negativeDelta) as negativeDelta FROM ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Pod%' group by projectName order by positiveDelta desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        
        if ($row['positiveDelta'] > 0)
        {
            $jsonArrayItem2['value'] = $row['positiveDelta'];
        }

        if ($row['negativeDelta'] < 0)
        {
            $jsonArrayItem3['value'] = $row['negativeDelta'];
        }
        
        if ($row['positiveDelta'] > 0 || $row['negativeDelta'] < 0)
        {
            $jsonArrayItem['label'] = $row['projectName'];
            array_push($jsonArraySubCategory, $jsonArrayItem);
            $jsonArrayItem1['value'] = $row['alreadyAutomated'];
            array_push($jsonArraySubSet1, $jsonArrayItem1);

            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
        }
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Previous Count",
        "visible" => "0",
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
    $jsonArrayDataSet = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayForFullCoverage = array();
    $jsonArrayForP0Coverage = array();
    $jsonArrayForP1Coverage = array();
    $jsonArrayForP2Coverage = array();

    $sql = "select * from ".getTableName($verticalName)." as a where id in(select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Pod%' order by totalAutomationCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

    foreach ($DB->query($sql) as $row)
    {

        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $row['automationCoveragePerc'];
        array_push($jsonArrayForFullCoverage, $jsonArrayItem1);

        $jsonArrayItem2 = array();
        $jsonArrayItem2['value'] = $row['p0CoveragePerc'];
        array_push($jsonArrayForP0Coverage, $jsonArrayItem2);

        $jsonArrayItem3 = array();
        $jsonArrayItem3['value'] = $row['p1CoveragePerc'];
        array_push($jsonArrayForP1Coverage, $jsonArrayItem3);

        $jsonArrayItem4 = array();
        $jsonArrayItem4['value'] = $row['p2CoveragePerc'];
        array_push($jsonArrayForP2Coverage, $jsonArrayItem4);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Coverage%",
        "plottooltext" => "P0 Coverage for \$label: \$dataValue",
        "visible" => "1",
        "data" => $jsonArrayForP0Coverage
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Coverage%",
        "plottooltext" => "P1 Coverage for \$label: \$dataValue",
        "visible" => "1",
        "data" => $jsonArrayForP1Coverage
    ));

    // array_push($jsonArrayDataSet, array(
    //     "seriesname" => "P2 Coverage%",
    //     "plottooltext" => "P2 Coverage for \$label: \$dataValue",
    //     "visible" => "0",
    //     "data" => $jsonArrayForP2Coverage
    // ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Full Coverage%",
        "plottooltext" => "Full Coverage for \$label: \$dataValue",
        "visible" => "0",
        "data" => $jsonArrayForFullCoverage
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}


function getTestcaseCountDistribution($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select * from ".getTableName($verticalName)." as a where id in(select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Pod%' order by totalAutomationCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
    $sql = showPodLevelData($sql, $isPodDataActive); 

    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSet5 = array();
    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $jsonArrayItem5 = array();
        $jsonArrayItem1['value'] = $row['p0Cases'];
        $jsonArrayItem2['value'] = $row['p1Cases'];
        $jsonArrayItem3['value'] = $row['p2Cases'];
        $otherCases = $row['totalAutomationCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
        $jsonArrayItem4['value'] = $otherCases;
        $jsonArrayItem5['value'] = $row['totalCases'] - $row['totalAutomationCases'];
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);
        array_push($jsonArraySubSet5, $jsonArrayItem5);
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Automation Cases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Automation Cases",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2 Automation Cases",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Low Priority Automation Cases",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Manual Cases",
        "data" => $jsonArraySubSet5
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getCoverageNumbers_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $sql = "Select sum(alreadyAutomated) as alreadyAutomated,sum(totalAutomationCases) as totalAutomationCases, FLOOR((sum(alreadyAutomated)/sum(totalAutomationCases))*100) as automationCoveragePerc, sum(p0AutomatedCases) as p0AutomatedCases, sum(p0Cases) as p0Cases, FLOOR((sum(p0AutomatedCases)/sum(p0Cases))*100) as p0CoveragePerc, sum(p1AutomatedCases) as p1AutomatedCases, sum(p1Cases) as p1Cases, FLOOR((sum(p1AutomatedCases)/sum(p1Cases))*100) as p1CoveragePerc from ".getTableName($verticalName)." where id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

    foreach ($DB->query($sql) as $row)
    {
        //Query should return only 1 row, otherwise this logic will not work properly
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArraySubSet1 = array();
        $jsonArraySubSet2 = array();
        $jsonArraySubSet3 = array();
        $jsonArraySubSet4 = array();
        $jsonArraySubSet5 = array();
        $jsonArraySubSet6 = array();

        $jsonArrayItem['label'] = "Pending Full Coverage";
        $jsonArrayItem['value'] = 100 - $row['automationCoveragePerc'];
        array_push($jsonArraySubSet1, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved Full Coverage";
        $jsonArrayItem['value'] = $row['automationCoveragePerc'];
        array_push($jsonArraySubSet1, $jsonArrayItem);
        array_push($jsonArray, array(
            "FullCoverage-data" => $jsonArraySubSet1
        ));

        $jsonArrayItem['label'] = "Pending P0 Coverage";
        $jsonArrayItem['value'] = 100 - $row['p0CoveragePerc'];
        array_push($jsonArraySubSet2, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P0 Coverage";
        $jsonArrayItem['value'] = $row['p0CoveragePerc'];
        array_push($jsonArraySubSet2, $jsonArrayItem);
        array_push($jsonArray, array(
            "P0Coverage-data" => $jsonArraySubSet2
        ));

        $jsonArrayItem['label'] = "Pending P1 Coverage";
        $jsonArrayItem['value'] = 100 - $row['p1CoveragePerc'];
        array_push($jsonArraySubSet3, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P1 Coverage";
        $jsonArrayItem['value'] = $row['p1CoveragePerc'];
        array_push($jsonArraySubSet3, $jsonArrayItem);
        array_push($jsonArray, array(
            "P1Coverage-data" => $jsonArraySubSet3
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['alreadyAutomated'];
        $jsonArrayItem1['automatableCases'] = $row['totalAutomationCases'];
        array_push($jsonArraySubSet4, $jsonArrayItem1);
        array_push($jsonArray, array(
            "FullAutomation-data" => $jsonArraySubSet4
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['p0AutomatedCases'];
        $jsonArrayItem1['automatableCases'] = $row['p0Cases'];
        array_push($jsonArraySubSet5, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P0Automation-data" => $jsonArraySubSet5
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['p1AutomatedCases'];
        $jsonArrayItem1['automatableCases'] = $row['p1Cases'];
        array_push($jsonArraySubSet6, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P1Automation-data" => $jsonArraySubSet6
        ));
    }
    return $jsonArray;
}


function getTotalvsAutomatedCount_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArrayItem = array();
    $jsonArrayItem['label'] = "P0 Automation cases";
    array_push($jsonArraySubCategory, $jsonArrayItem);
    $jsonArrayItem['label'] = "P1 Automation cases";
    array_push($jsonArraySubCategory, $jsonArrayItem);
    $jsonArrayItem['label'] = "P2 Automation cases";
    array_push($jsonArraySubCategory, $jsonArrayItem);
    $jsonArrayItem['label'] = "Other Automation cases";
    array_push($jsonArraySubCategory, $jsonArrayItem);

    $sql = "select sum(totalAutomationCases) as totalAutomationCases, sum(alreadyAutomated) as alreadyAutomated, sum(p0Cases) as p0Cases, sum(p0AutomatedCases) as p0AutomatedCases, sum(p1Cases) as p1Cases, sum(p1AutomatedCases) as p1AutomatedCases, sum(p2Cases) as p2Cases, sum(p2AutomatedCases) as p2AutomatedCases from ".getTableName($verticalName)." where id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $jsonArrayItem1['value'] = $row['p0Cases'];
        $jsonArrayItem2['value'] = $row['p1Cases'];
        $jsonArrayItem3['value'] = $row['p2Cases'];
        $otherCases = $row['totalAutomationCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
        $jsonArrayItem4['value'] = $otherCases;
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet1, $jsonArrayItem2);
        array_push($jsonArraySubSet1, $jsonArrayItem3);
        array_push($jsonArraySubSet1, $jsonArrayItem4);

        $jsonArrayItem5 = array();
        $jsonArrayItem6 = array();
        $jsonArrayItem7 = array();
        $jsonArrayItem8 = array();
        $jsonArrayItem5['value'] = $row['p0AutomatedCases'];
        $jsonArrayItem6['value'] = $row['p1AutomatedCases'];
        $jsonArrayItem7['value'] = $row['p2AutomatedCases'];
        $otherCases = $row['alreadyAutomated'] - ($row['p0AutomatedCases'] + $row['p1AutomatedCases'] + $row['p2AutomatedCases']);
        $jsonArrayItem8['value'] = $otherCases;
        array_push($jsonArraySubSet2, $jsonArrayItem5);
        array_push($jsonArraySubSet2, $jsonArrayItem6);
        array_push($jsonArraySubSet2, $jsonArrayItem7);
        array_push($jsonArraySubSet2, $jsonArrayItem8);
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Automation Cases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Already Automated Count",
        "data" => $jsonArraySubSet2
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

    $sql = "SELECT DATE(createdAt) as createdAt, sum(totalCases) as totalCases, sum(totalAutomationCases) as totalAutomationCases, sum(alreadyAutomated) as alreadyAutomated, sum(p0Cases) as p0Cases, sum(p0AutomatedCases) as p0AutomatedCases, sum(p1Cases) as p1Cases, sum(p1AutomatedCases) as p1AutomatedCases, sum(p2Cases) as p2Cases, sum(p2AutomatedCases) as p2AutomatedCases from (SELECT DATE(createdAt) as createdAt, max(id), max(totalCases) as totalCases, max(totalAutomationCases) as totalAutomationCases, max(alreadyAutomated) as alreadyAutomated, max(p0Cases) as p0Cases, max(p0AutomatedCases) as p0AutomatedCases, max(p1Cases) as p1Cases, max(p1AutomatedCases) as p1AutomatedCases, max(p2Cases) as p2Cases, max(p2AutomatedCases) as p2AutomatedCases FROM ".getTableName($verticalName)." WHERE projectName in (" . $projectName . ") and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt),projectName) temp GROUP BY DATE(createdAt);";
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

        $jsonArrayItem1['value'] = $row['totalCases'];
        $jsonArrayItem2['value'] = $row['totalAutomationCases'];
        $jsonArrayItem3['value'] = $row['p0Cases'];
        $jsonArrayItem4['value'] = $row['p1Cases'];
        $jsonArrayItem5['value'] = $row['alreadyAutomated'];
        $otherCases = $row['totalAutomationCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
        $jsonArrayItem6['value'] = $otherCases;
        $manualCases = $row['totalCases'] - $row['totalAutomationCases'];
        $jsonArrayItem7['value'] = $manualCases;

        $jsonArrayItem8['value'] = $row['p2Cases'];
        $jsonArrayItem9['value'] = $row['p0AutomatedCases'];
        $jsonArrayItem10['value'] = $row['p1AutomatedCases'];
        $jsonArrayItem11['value'] = $row['p2AutomatedCases'];
        $otherAutomatedCases = $row['alreadyAutomated'] - ($row['p0AutomatedCases'] + $row['p1AutomatedCases'] + $row['p2AutomatedCases']);
        $jsonArrayItem12['value'] = $otherAutomatedCases;

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
        "seriesname" => "Total Testcases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Only Automation",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Already Automated",
        "data" => $jsonArraySubSet5
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Automated Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet9
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Automated Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet10
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2 Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet8
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2 Automated Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet11
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Other Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Other Automated Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet12
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Only Manual",
        "visible" => "0",
        "data" => $jsonArraySubSet7
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
        case 'getAutomatedCountChange':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getAutomatedCountChange($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getP0CoverageChange':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getP0CoverageChange($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getP1CoverageChange':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getP1CoverageChange($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getFullCoverageData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getFullCoverageData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getTestcaseCountDistribution':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTestcaseCountDistribution($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getCoverageNumbers_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getCoverageNumbers_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getTestCasesBreakdown_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTestCasesBreakdown_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getTotalvsAutomatedCount_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalvsAutomatedCount_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getTestcaseCountTrend_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTestcaseCountTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
    }
    echo json_encode($jsonArray);
}
?>