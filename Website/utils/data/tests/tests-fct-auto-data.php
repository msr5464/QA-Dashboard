<?php
header('Content-type: application/json');
require("../../config.php");

function getTableName($tableNamePrefix) {
    return str_replace(" ", "_", strtolower($tableNamePrefix)."_tests_fct");
}

function getTestcasesTableName($tableNamePrefix) {
    return str_replace(" ", "_", strtolower($tableNamePrefix)."_tests_data");
}

function getProjectNames($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select projectName from ".getTableName($tableNamePrefix)." where YEAR(createdAt)>=YEAR('" . $startDate . "') OR YEAR(createdAt)=YEAR('" . $endDate . "') group by projectName order by projectName desc;";

    foreach ($DB->query($sql) as $row)
    {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getCoverageNumbers_All($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "Select sum(f.alreadyAutomated) as alreadyAutomated,sum(f.totalAutomationCases) as totalAutomationCases, FLOOR((sum(f.alreadyAutomated)/sum(f.totalAutomationCases))*100) as automationCoveragePerc, sum(f.p0AutomatedCases) as p0AutomatedCases, sum(f.p0AutomationCases) as p0AutomationCases, FLOOR((sum(f.p0AutomatedCases)/sum(f.p0AutomationCases))*100) as p0CoveragePerc, sum(f.p1AutomatedCases) as p1AutomatedCases, sum(f.p1AutomationCases) as p1AutomationCases, FLOOR((sum(f.p1AutomatedCases)/sum(f.p1AutomationCases))*100) as p1CoveragePerc, f.id from ( select projectName, max(id) as id from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Vertical%' group by projectName) as x inner join ".getTableName($tableNamePrefix)." as f on f.projectName = x.projectName and f.id = x.id order by id desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

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
        $jsonArrayItem1['automatableCases'] = $row['p0AutomationCases'];
        array_push($jsonArraySubSet5, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P0Automation-data" => $jsonArraySubSet5
        ));

        $jsonArrayItem1['alreadyAutomated'] = $row['p1AutomatedCases'];
        $jsonArrayItem1['automatableCases'] = $row['p1AutomationCases'];
        array_push($jsonArraySubSet6, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P1Automation-data" => $jsonArraySubSet6
        ));
    }
    return $jsonArray;
}

function getP0CoverageChange($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT a.projectName as projectName, a.p0CoveragePerc as newP0CoveragePerc,b.p0CoveragePerc as oldP0CoveragePerc FROM ".getTableName($tableNamePrefix)." a JOIN ".getTableName($tableNamePrefix)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($tableNamePrefix)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and (a.p0CoveragePerc != b.p0CoveragePerc) and a.projectName not like 'Vertical%' group by projectName order by (a.p0CoveragePerc - b.p0CoveragePerc) desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

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

function getP1CoverageChange($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT a.projectName as projectName, a.p1CoveragePerc as newP1CoveragePerc,b.p1CoveragePerc as oldP1CoveragePerc FROM ".getTableName($tableNamePrefix)." a JOIN ".getTableName($tableNamePrefix)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($tableNamePrefix)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and (a.p1CoveragePerc != b.p1CoveragePerc) and a.projectName not like 'Vertical%' group by projectName order by (a.p1CoveragePerc - b.p1CoveragePerc) desc";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

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

function getAutomatedCountChange($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $sql = "SELECT projectName,alreadyAutomated,SUM(positiveDelta) as positiveDelta,SUM(negativeDelta) as negativeDelta FROM ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Vertical%' group by projectName order by positiveDelta desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

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

function getFullCoverageData($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayDataSet = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayForFullCoverage = array();
    $jsonArrayForP0Coverage = array();
    $jsonArrayForP1Coverage = array();
    $jsonArrayForP2Coverage = array();

    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalAutomationCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

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

function getPriorityDistribution($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalAutomationCases desc,p0AutomationCases desc,p1AutomationCases desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive); 

    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSet5 = array();
    $jsonArraySubSet6 = array();
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
        $jsonArrayItem6 = array();
        $jsonArrayItem1['value'] = $row['p0AutomationCases'];
        $jsonArrayItem2['value'] = $row['p1AutomationCases'];
        $jsonArrayItem3['value'] = $row['p2AutomationCases'];
        $otherCases = $row['totalAutomationCases'] - ($row['p0AutomationCases'] + $row['p1AutomationCases'] + $row['p2AutomationCases']);
        $jsonArrayItem4['value'] = $otherCases;
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);

        $jsonArrayItem5['value'] = $row['totalAutomationCases'];
        $otherCases = $row['totalAutomationCases'] - $row['totalAutomationCases'];
        $jsonArrayItem6['value'] = $otherCases;
        array_push($jsonArraySubSet5, $jsonArrayItem5);
        array_push($jsonArraySubSet6, $jsonArrayItem6);
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Cases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Cases",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2 Cases",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "PN Cases",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Automation Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet5
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Manual Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getPlatformDistribution($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalAutomationCases desc,p0AutomationCases desc,p1AutomationCases desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive); 

    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $jsonArrayItem1['value'] = $row['api_totalAutomationCases'];
        $jsonArrayItem2['value'] = $row['web_totalAutomationCases'];
        $jsonArrayItem3['value'] = $row['android_totalAutomationCases'];
        $jsonArrayItem4['value'] = $row['ios_totalAutomationCases'];
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Api Cases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Web Cases",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Android Cases",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Ios Cases",
        "data" => $jsonArraySubSet4
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getTestcaseCountDistribution($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive) {
    global $DB;
    $jsonArray = array();
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalAutomationCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive); 

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
        $jsonArrayItem1['value'] = $row['p0AutomationCases'];
        $jsonArrayItem2['value'] = $row['p1AutomationCases'];
        $jsonArrayItem3['value'] = $row['p2AutomationCases'];
        $otherCases = $row['totalAutomationCases'] - ($row['p0AutomationCases'] + $row['p1AutomationCases'] + $row['p2AutomationCases']);
        $jsonArrayItem4['value'] = $otherCases;
        $jsonArrayItem5['value'] = $row['totalAutomationCases'] - $row['totalAutomationCases'];
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

function getCoverageNumbers_Project($tableNamePrefix, $projectName, $startDate, $endDate, $platform) {
    global $DB;
    $jsonArray = array();
    //Query should return only 1 row, otherwise this logic will not work properly
    $jsonArrayItem = array();
    $jsonArrayItem1 = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSet5 = array();
    $jsonArraySubSet6 = array();

    $automationCoveragePerc = 0;
    $p0CoveragePerc = 0;
    $p1CoveragePerc = 0;
    $totalDataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $p0DataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $p1DataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );

    if (!isset($platform) || trim($platform) === '') {
            $sql = "Select sum(alreadyAutomated) as alreadyAutomated,sum(totalAutomationCases) as totalAutomationCases, FLOOR((sum(alreadyAutomated)/sum(totalAutomationCases))*100) as automationCoveragePerc, sum(p0AutomatedCases) as p0AutomatedCases, sum(p0AutomationCases) as p0AutomationCases, FLOOR((sum(p0AutomatedCases)/sum(p0AutomationCases))*100) as p0CoveragePerc, sum(p1AutomatedCases) as p1AutomatedCases, sum(p1AutomationCases) as p1AutomationCases, FLOOR((sum(p1AutomatedCases)/sum(p1AutomationCases))*100) as p1CoveragePerc from ".getTableName($tableNamePrefix)." where id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

            foreach ($DB->query($sql) as $row)
            {
                $totalDataArray['alreadyAutomated'] += $row['alreadyAutomated'];
                $totalDataArray['automatableCases'] += $row['totalAutomationCases'];
                $p0DataArray['alreadyAutomated'] += $row['p0AutomatedCases'];
                $p0DataArray['automatableCases'] += $row['p0AutomationCases'];
                $p1DataArray['alreadyAutomated'] += $row['p1AutomatedCases'];
                $p1DataArray['automatableCases'] += $row['p1AutomationCases'];
            }
        }
        else 
        {
            $sql = "SELECT SUM(api_alreadyAutomated) AS api_alreadyAutomated, SUM(api_totalAutomationCases) AS api_totalAutomationCases, FLOOR((SUM(api_alreadyAutomated)/SUM(api_totalAutomationCases))*100) AS api_automationCoveragePerc, SUM(api_p0AutomatedCases) AS api_p0AutomatedCases, SUM(api_p0AutomationCases) AS api_p0AutomationCases, FLOOR((SUM(api_p0AutomatedCases)/SUM(api_p0AutomationCases))*100) AS api_p0CoveragePerc, SUM(api_p1AutomatedCases) AS api_p1AutomatedCases, SUM(api_p1AutomationCases) AS api_p1AutomationCases, FLOOR((SUM(api_p1AutomatedCases)/SUM(api_p1AutomationCases))*100) AS api_p1CoveragePerc, SUM(web_alreadyAutomated) AS web_alreadyAutomated, SUM(web_totalAutomationCases) AS web_totalAutomationCases, FLOOR((SUM(web_alreadyAutomated)/SUM(web_totalAutomationCases))*100) AS web_automationCoveragePerc, SUM(web_p0AutomatedCases) AS web_p0AutomatedCases, SUM(web_p0AutomationCases) AS web_p0AutomationCases, FLOOR((SUM(web_p0AutomatedCases)/SUM(web_p0AutomationCases))*100) AS web_p0CoveragePerc, SUM(web_p1AutomatedCases) AS web_p1AutomatedCases, SUM(web_p1AutomationCases) AS web_p1AutomationCases, FLOOR((SUM(web_p1AutomatedCases)/SUM(web_p1AutomationCases))*100) AS web_p1CoveragePerc, SUM(android_alreadyAutomated) AS android_alreadyAutomated, SUM(android_totalAutomationCases) AS android_totalAutomationCases, FLOOR((SUM(android_alreadyAutomated)/SUM(android_totalAutomationCases))*100) AS android_automationCoveragePerc, SUM(android_p0AutomatedCases) AS android_p0AutomatedCases, SUM(android_p0AutomationCases) AS android_p0AutomationCases, FLOOR((SUM(android_p0AutomatedCases)/SUM(android_p0AutomationCases))*100) AS android_p0CoveragePerc, SUM(android_p1AutomatedCases) AS android_p1AutomatedCases, SUM(android_p1AutomationCases) AS android_p1AutomationCases, FLOOR((SUM(android_p1AutomatedCases)/SUM(android_p1AutomationCases))*100) AS android_p1CoveragePerc, SUM(ios_alreadyAutomated) AS ios_alreadyAutomated, SUM(ios_totalAutomationCases) AS ios_totalAutomationCases, FLOOR((SUM(ios_alreadyAutomated)/SUM(ios_totalAutomationCases))*100) AS ios_automationCoveragePerc, SUM(ios_p0AutomatedCases) AS ios_p0AutomatedCases, SUM(ios_p0AutomationCases) AS ios_p0AutomationCases, FLOOR((SUM(ios_p0AutomatedCases)/SUM(ios_p0AutomationCases))*100) AS ios_p0CoveragePerc, SUM(ios_p1AutomatedCases) AS ios_p1AutomatedCases, SUM(ios_p1AutomationCases) AS ios_p1AutomationCases, FLOOR((SUM(ios_p1AutomatedCases)/SUM(ios_p1AutomationCases))*100) AS ios_p1CoveragePerc FROM ".getTableName($tableNamePrefix)." WHERE id IN (SELECT MAX(id) FROM ".getTableName($tableNamePrefix)." WHERE DATE(createdAt)>='" . $startDate . "' AND DATE(createdAt)<='" . $endDate . "' AND projectName in (" . $projectName . ") GROUP BY projectName);";

            foreach ($DB->query($sql) as $row)
            {
                // Split the platform values into an array
                $platforms = explode(',', $platform);

                foreach ($platforms as $platformValue) 
                {
                    $platformValue = strtolower($platformValue);
                    switch ($platformValue) 
                    {
                        case 'api':
                            $totalDataArray['alreadyAutomated'] += $row['api_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['api_totalAutomationCases'];
                            $p0DataArray['alreadyAutomated'] += $row['api_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['api_p0AutomationCases'];
                            $p1DataArray['alreadyAutomated'] += $row['api_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['api_p1AutomationCases'];
                            break;

                        case 'web':
                            // Calculation for 'web' platform
                            $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['web_totalAutomationCases'];
                            $p0DataArray['alreadyAutomated'] += $row['web_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['web_p0AutomationCases'];
                            $p1DataArray['alreadyAutomated'] += $row['web_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['web_p1AutomationCases'];
                            break;

                        case 'android':
                            // Calculation for 'android' platform
                            $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['android_totalAutomationCases'];
                            $p0DataArray['alreadyAutomated'] += $row['android_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['android_p0AutomationCases'];
                            $p1DataArray['alreadyAutomated'] += $row['android_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['android_p1AutomationCases'];
                            break;

                        case 'ios':
                            // Calculation for 'ios' platform
                            $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['ios_totalAutomationCases'];
                            $p0DataArray['alreadyAutomated'] += $row['ios_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['ios_p0AutomationCases'];
                            $p1DataArray['alreadyAutomated'] += $row['ios_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['ios_p1AutomationCases'];
                            break;

                        default:
                            showErrorMessage("Invalid Platform name - ".$platformValue);
                            break;
                    }
                }
            }
        }

        if($totalDataArray['automatableCases'] > 0)
            $automationCoveragePerc = round(($totalDataArray['alreadyAutomated']*100)/$totalDataArray['automatableCases'],1);
        else
            $automationCoveragePerc = 0;

        if($p0DataArray['automatableCases'] > 0)
            $p0CoveragePerc = round(($p0DataArray['alreadyAutomated']*100)/$p0DataArray['automatableCases'],1);
        else
            $p0CoveragePerc = 0;

        if($p1DataArray['automatableCases'] > 0)
            $p1CoveragePerc = round(($p1DataArray['alreadyAutomated']*100)/$p1DataArray['automatableCases'],1);
        else
            $p1CoveragePerc = 0;

        $jsonArrayItem['label'] = "Pending Full Coverage";
        $jsonArrayItem['value'] = 100 - $automationCoveragePerc;
        array_push($jsonArraySubSet1, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved Full Coverage";
        $jsonArrayItem['value'] = $automationCoveragePerc;
        array_push($jsonArraySubSet1, $jsonArrayItem);
        array_push($jsonArray, array(
            "FullCoverage-data" => $jsonArraySubSet1
        ));

        $jsonArrayItem['label'] = "Pending P0 Coverage";
        $jsonArrayItem['value'] = 100 - $p0CoveragePerc;
        array_push($jsonArraySubSet2, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P0 Coverage";
        $jsonArrayItem['value'] = $p0CoveragePerc;
        array_push($jsonArraySubSet2, $jsonArrayItem);
        array_push($jsonArray, array(
            "P0Coverage-data" => $jsonArraySubSet2
        ));

        $jsonArrayItem['label'] = "Pending P1 Coverage";
        $jsonArrayItem['value'] = 100 - $p1CoveragePerc;
        array_push($jsonArraySubSet3, $jsonArrayItem);
        $jsonArrayItem['label'] = "Achieved P1 Coverage";
        $jsonArrayItem['value'] = $p1CoveragePerc;
        array_push($jsonArraySubSet3, $jsonArrayItem);
        array_push($jsonArray, array(
            "P1Coverage-data" => $jsonArraySubSet3
        ));

        $jsonArrayItem1['alreadyAutomated'] = $totalDataArray['alreadyAutomated'];
        $jsonArrayItem1['automatableCases'] = $totalDataArray['automatableCases'];
        array_push($jsonArraySubSet4, $jsonArrayItem1);
        array_push($jsonArray, array(
            "FullAutomation-data" => $jsonArraySubSet4
        ));

        $jsonArrayItem1['alreadyAutomated'] = $p0DataArray['alreadyAutomated'];
        $jsonArrayItem1['automatableCases'] = $p0DataArray['automatableCases'];
        array_push($jsonArraySubSet5, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P0Automation-data" => $jsonArraySubSet5
        ));

        $jsonArrayItem1['alreadyAutomated'] = $p1DataArray['alreadyAutomated'];
        $jsonArrayItem1['automatableCases'] = $p1DataArray['automatableCases'];
        array_push($jsonArraySubSet6, $jsonArrayItem1);
        array_push($jsonArray, array(
            "P1Automation-data" => $jsonArraySubSet6
        ));
    return $jsonArray;
}


function getTotalvsAutomatedCount_Project($tableNamePrefix, $projectName, $startDate, $endDate, $platform) {
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
    $jsonArrayItem['label'] = "Pn Automation cases";
    array_push($jsonArraySubCategory, $jsonArrayItem);

    $totalDataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $p0DataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $p1DataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $p2DataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );
    $pnDataArray = array(
        'alreadyAutomated' => 0,
        'automatableCases' => 0
    );

    if (!isset($platform) || trim($platform) === '') 
    {
        $sql = "select sum(totalAutomationCases) as totalAutomationCases, sum(alreadyAutomated) as alreadyAutomated, sum(p0AutomationCases) as p0AutomationCases, sum(p0AutomatedCases) as p0AutomatedCases, sum(p1AutomationCases) as p1AutomationCases, sum(p1AutomatedCases) as p1AutomatedCases, sum(p2AutomationCases) as p2AutomationCases, sum(p2AutomatedCases) as p2AutomatedCases from ".getTableName($tableNamePrefix)." where id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

        foreach ($DB->query($sql) as $row)
        {
            $totalDataArray['alreadyAutomated'] += $row['alreadyAutomated'];
            $totalDataArray['automatableCases'] += $row['totalAutomationCases'];
            $p0DataArray['alreadyAutomated'] += $row['p0AutomatedCases'];
            $p0DataArray['automatableCases'] += $row['p0AutomationCases'];
            $p1DataArray['alreadyAutomated'] += $row['p1AutomatedCases'];
            $p1DataArray['automatableCases'] += $row['p1AutomationCases'];
            $p2DataArray['alreadyAutomated'] += $row['p2AutomatedCases'];
            $p2DataArray['automatableCases'] += $row['p2AutomationCases'];
            $pnDataArray['alreadyAutomated'] += $totalDataArray['alreadyAutomated'] - ($p0DataArray['automatableCases'] + $p1DataArray['automatableCases'] + $p2DataArray['automatableCases']);
            $pnDataArray['automatableCases'] += $totalDataArray['automatableCases'] - ($p0DataArray['automatableCases'] + $p1DataArray['automatableCases'] + $p2DataArray['automatableCases']);
        }
    }
    else 
    {
        $sql = "SELECT SUM(api_alreadyAutomated) AS api_alreadyAutomated, SUM(api_totalAutomationCases) AS api_totalAutomationCases, FLOOR((SUM(api_alreadyAutomated)/SUM(api_totalAutomationCases))*100) AS api_automationCoveragePerc, SUM(api_p0AutomatedCases) AS api_p0AutomatedCases, SUM(api_p0AutomationCases) AS api_p0AutomationCases, FLOOR((SUM(api_p0AutomatedCases)/SUM(api_p0AutomationCases))*100) AS api_p0CoveragePerc, SUM(api_p1AutomatedCases) AS api_p1AutomatedCases, SUM(api_p1AutomationCases) AS api_p1AutomationCases, FLOOR((SUM(api_p1AutomatedCases)/SUM(api_p1AutomationCases))*100) AS api_p1CoveragePerc, SUM(api_p2AutomatedCases) AS api_p2AutomatedCases, SUM(api_p2AutomationCases) AS api_p2AutomationCases, FLOOR((SUM(api_p2AutomatedCases)/SUM(api_p2AutomationCases))*100) AS api_p2CoveragePerc, SUM(web_alreadyAutomated) AS web_alreadyAutomated, SUM(web_totalAutomationCases) AS web_totalAutomationCases, FLOOR((SUM(web_alreadyAutomated)/SUM(web_totalAutomationCases))*100) AS web_automationCoveragePerc, SUM(web_p0AutomatedCases) AS web_p0AutomatedCases, SUM(web_p0AutomationCases) AS web_p0AutomationCases, FLOOR((SUM(web_p0AutomatedCases)/SUM(web_p0AutomationCases))*100) AS web_p0CoveragePerc, SUM(web_p1AutomatedCases) AS web_p1AutomatedCases, SUM(web_p1AutomationCases) AS web_p1AutomationCases, FLOOR((SUM(web_p1AutomatedCases)/SUM(web_p1AutomationCases))*100) AS web_p1CoveragePerc, SUM(web_p2AutomatedCases) AS web_p2AutomatedCases, SUM(web_p2AutomationCases) AS web_p2AutomationCases, FLOOR((SUM(web_p2AutomatedCases)/SUM(web_p2AutomationCases))*100) AS web_p2CoveragePerc, SUM(android_alreadyAutomated) AS android_alreadyAutomated, SUM(android_totalAutomationCases) AS android_totalAutomationCases, FLOOR((SUM(android_alreadyAutomated)/SUM(android_totalAutomationCases))*100) AS android_automationCoveragePerc, SUM(android_p0AutomatedCases) AS android_p0AutomatedCases, SUM(android_p0AutomationCases) AS android_p0AutomationCases, FLOOR((SUM(android_p0AutomatedCases)/SUM(android_p0AutomationCases))*100) AS android_p0CoveragePerc, SUM(android_p1AutomatedCases) AS android_p1AutomatedCases, SUM(android_p1AutomationCases) AS android_p1AutomationCases, FLOOR((SUM(android_p1AutomatedCases)/SUM(android_p1AutomationCases))*100) AS android_p1CoveragePerc, SUM(android_p2AutomatedCases) AS android_p2AutomatedCases, SUM(android_p2AutomationCases) AS android_p2AutomationCases, FLOOR((SUM(android_p2AutomatedCases)/SUM(android_p2AutomationCases))*100) AS android_p2CoveragePerc, SUM(ios_alreadyAutomated) AS ios_alreadyAutomated, SUM(ios_totalAutomationCases) AS ios_totalAutomationCases, FLOOR((SUM(ios_alreadyAutomated)/SUM(ios_totalAutomationCases))*100) AS ios_automationCoveragePerc, SUM(ios_p0AutomatedCases) AS ios_p0AutomatedCases, SUM(ios_p0AutomationCases) AS ios_p0AutomationCases, FLOOR((SUM(ios_p0AutomatedCases)/SUM(ios_p0AutomationCases))*100) AS ios_p0CoveragePerc, SUM(ios_p1AutomatedCases) AS ios_p1AutomatedCases, SUM(ios_p1AutomationCases) AS ios_p1AutomationCases, FLOOR((SUM(ios_p1AutomatedCases)/SUM(ios_p1AutomationCases))*100) AS ios_p1CoveragePerc, SUM(ios_p2AutomatedCases) AS ios_p2AutomatedCases, SUM(ios_p2AutomationCases) AS ios_p2AutomationCases, FLOOR((SUM(ios_p2AutomatedCases)/SUM(ios_p2AutomationCases))*100) AS ios_p2CoveragePerc FROM ".getTableName($tableNamePrefix)." WHERE id IN (SELECT MAX(id) FROM ".getTableName($tableNamePrefix)." WHERE DATE(createdAt)>='" . $startDate . "' AND DATE(createdAt)<='" . $endDate . "' AND projectName in (" . $projectName . ") GROUP BY projectName);";

        foreach ($DB->query($sql) as $row)
        {
            // Split the platform values into an array
            $platforms = explode(',', $platform);

            foreach ($platforms as $platformValue) 
            {
                $platformValue = strtolower($platformValue);
                switch ($platformValue) 
                {
                    case 'api':
                        $totalDataArray['alreadyAutomated'] += $row['api_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['api_totalAutomationCases'];
                        $p0DataArray['alreadyAutomated'] += $row['api_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['api_p0AutomationCases'];
                        $p1DataArray['alreadyAutomated'] += $row['api_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['api_p1AutomationCases'];
                        $p2DataArray['alreadyAutomated'] += $row['api_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['api_p2AutomationCases'];
                        break;

                    case 'web':
                        // Calculation for 'web' platform
                        $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['web_totalAutomationCases'];
                        $p0DataArray['alreadyAutomated'] += $row['web_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['web_p0AutomationCases'];
                        $p1DataArray['alreadyAutomated'] += $row['web_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['web_p1AutomationCases'];
                        $p2DataArray['alreadyAutomated'] += $row['web_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['web_p2AutomationCases'];
                        break;

                    case 'android':
                        // Calculation for 'android' platform
                        $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['android_totalAutomationCases'];
                        $p0DataArray['alreadyAutomated'] += $row['android_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['android_p0AutomationCases'];
                        $p1DataArray['alreadyAutomated'] += $row['android_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['android_p1AutomationCases'];
                        $p2DataArray['alreadyAutomated'] += $row['android_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['android_p2AutomationCases'];
                        break;

                    case 'ios':
                        // Calculation for 'ios' platform
                        $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['ios_totalAutomationCases'];
                        $p0DataArray['alreadyAutomated'] += $row['ios_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['ios_p0AutomationCases'];
                        $p1DataArray['alreadyAutomated'] += $row['ios_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['ios_p1AutomationCases'];
                        $p2DataArray['alreadyAutomated'] += $row['ios_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['ios_p2AutomationCases'];
                        break;

                    default:
                        showErrorMessage("Invalid Platform name - ".$platformValue);
                        break;
                }
            }
            $pnDataArray['alreadyAutomated'] += $totalDataArray['alreadyAutomated'] - ($p0DataArray['alreadyAutomated'] + $p1DataArray['alreadyAutomated'] + $p2DataArray['alreadyAutomated']);
            $pnDataArray['automatableCases'] += $totalDataArray['automatableCases'] - ($p0DataArray['automatableCases'] + $p1DataArray['automatableCases'] + $p2DataArray['automatableCases']);
        }
    }

    $jsonArrayItem1 = array();
    $jsonArrayItem2 = array();
    $jsonArrayItem3 = array();
    $jsonArrayItem4 = array();
    $jsonArrayItem1['value'] = $p0DataArray['automatableCases'];
    $jsonArrayItem2['value'] = $p1DataArray['automatableCases'];
    $jsonArrayItem3['value'] = $p2DataArray['automatableCases'];
    $jsonArrayItem4['value'] = $pnDataArray['automatableCases'];
    array_push($jsonArraySubSet1, $jsonArrayItem1);
    array_push($jsonArraySubSet1, $jsonArrayItem2);
    array_push($jsonArraySubSet1, $jsonArrayItem3);
    array_push($jsonArraySubSet1, $jsonArrayItem4);

    $jsonArrayItem5 = array();
    $jsonArrayItem6 = array();
    $jsonArrayItem7 = array();
    $jsonArrayItem8 = array();
    $jsonArrayItem5['value'] = $p0DataArray['alreadyAutomated'];
    $jsonArrayItem6['value'] = $p1DataArray['alreadyAutomated'];
    $jsonArrayItem7['value'] = $p2DataArray['alreadyAutomated'];
    $jsonArrayItem8['value'] = $pnDataArray['alreadyAutomated'];
    array_push($jsonArraySubSet2, $jsonArrayItem5);
    array_push($jsonArraySubSet2, $jsonArrayItem6);
    array_push($jsonArraySubSet2, $jsonArrayItem7);
    array_push($jsonArraySubSet2, $jsonArrayItem8);

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

function getTestcaseCountDelta_Project($tableNamePrefix, $projectName, $startDate, $endDate, $platform) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array(); // Delta - Total Cases
    $jsonArraySubSet2 = array(); // Delta - Automated Cases
    $jsonArraySubSet3 = array(); // Total Cases Added
    $jsonArraySubSet4 = array(); // Total Cases Removed
    $jsonArraySubSet5 = array(); // Automated Cases Added
    $jsonArraySubSet6 = array(); // Automated Cases Removed

    // Base query with proper date handling
    $sql = "SELECT DATE(createdAt) AS date,
            createdAt as original_date,
            SUM(totalAutomationCases) AS totalAutomationCases,
            SUM(alreadyAutomated) AS alreadyAutomated,
            SUM(api_totalAutomationCases) AS api_totalAutomationCases,
            SUM(api_alreadyAutomated) AS api_alreadyAutomated,
            SUM(web_totalAutomationCases) AS web_totalAutomationCases,
            SUM(web_alreadyAutomated) AS web_alreadyAutomated,
            SUM(android_totalAutomationCases) AS android_totalAutomationCases,
            SUM(android_alreadyAutomated) AS android_alreadyAutomated,
            SUM(ios_totalAutomationCases) AS ios_totalAutomationCases,
            SUM(ios_alreadyAutomated) AS ios_alreadyAutomated
    FROM (
        SELECT 
            createdAt,
            MAX(id) AS maxId,
            MAX(totalAutomationCases) AS totalAutomationCases,
            MAX(alreadyAutomated) AS alreadyAutomated,
            MAX(api_totalAutomationCases) AS api_totalAutomationCases,
            MAX(api_alreadyAutomated) AS api_alreadyAutomated,
            MAX(web_totalAutomationCases) AS web_totalAutomationCases,
            MAX(web_alreadyAutomated) AS web_alreadyAutomated,
            MAX(android_totalAutomationCases) AS android_totalAutomationCases,
            MAX(android_alreadyAutomated) AS android_alreadyAutomated,
            MAX(ios_totalAutomationCases) AS ios_totalAutomationCases,
            MAX(ios_alreadyAutomated) AS ios_alreadyAutomated
        FROM " . getTableName($tableNamePrefix) . "
        WHERE 
            projectName IN (" . $projectName . ")
            AND DATE(createdAt) >= '" . $startDate . "' 
            AND DATE(createdAt) <= '" . $endDate . "'
        GROUP BY DATE(createdAt), projectName
    ) temp
    GROUP BY DATE(createdAt)
    ORDER BY createdAt ASC";

    $sql = updateGroupBy($sql, $startDate, $endDate);
    
    $prevAutomated = null;
    $prevTotal = null;
    
    $totalDeltaCases = 0;
    $totalDeltaAutomated = 0;
    $totalCasesAdded = 0;
    $totalCasesRemoved = 0;
    $totalAutomatedAdded = 0;
    $totalAutomatedRemoved = 0;

    // Variables to track first and last values
    $firstDateValues = null;
    $lastDateValues = null;
    
    foreach ($DB->query($sql) as $row) {
        // Store first date values
        if ($firstDateValues === null) {
            $firstDateValues = array(
                'date' => $row['date'],
                'automated' => 0,
                'total' => 0
            );
            
            // Calculate platform-specific totals for first date
            $platforms = explode(',', $platform);
            foreach ($platforms as $platformValue) {
                $platformValue = strtolower($platformValue);
                switch ($platformValue) {
                    case 'api':
                        $firstDateValues['automated'] += $row['api_alreadyAutomated'];
                        $firstDateValues['total'] += $row['api_totalAutomationCases'];
                        break;
                    case 'web':
                        $firstDateValues['automated'] += $row['web_alreadyAutomated'];
                        $firstDateValues['total'] += $row['web_totalAutomationCases'];
                        break;
                    case 'android':
                        $firstDateValues['automated'] += $row['android_alreadyAutomated'];
                        $firstDateValues['total'] += $row['android_totalAutomationCases'];
                        break;
                    case 'ios':
                        $firstDateValues['automated'] += $row['ios_alreadyAutomated'];
                        $firstDateValues['total'] += $row['ios_totalAutomationCases'];
                        break;
                }
            }
        }
        
        // Update last date values
        $lastDateValues = array(
            'date' => $row['date'],
            'automated' => 0,
            'total' => 0
        );
        
        // Calculate platform-specific totals for last date
        $platforms = explode(',', $platform);
        foreach ($platforms as $platformValue) {
            $platformValue = strtolower($platformValue);
            switch ($platformValue) {
                case 'api':
                    $lastDateValues['automated'] += $row['api_alreadyAutomated'];
                    $lastDateValues['total'] += $row['api_totalAutomationCases'];
                    break;
                case 'web':
                    $lastDateValues['automated'] += $row['web_alreadyAutomated'];
                    $lastDateValues['total'] += $row['web_totalAutomationCases'];
                    break;
                case 'android':
                    $lastDateValues['automated'] += $row['android_alreadyAutomated'];
                    $lastDateValues['total'] += $row['android_totalAutomationCases'];
                    break;
                case 'ios':
                    $lastDateValues['automated'] += $row['ios_alreadyAutomated'];
                    $lastDateValues['total'] += $row['ios_totalAutomationCases'];
                    break;
            }
        }

        // Rest of your existing code for calculating deltas...
        $jsonArrayItem = array();
        $jsonArrayItem1 = array(); // Delta - Total Cases
        $jsonArrayItem2 = array(); // Delta - Automated Cases
        $jsonArrayItem3 = array(); // Total Cases Added
        $jsonArrayItem4 = array(); // Total Cases Removed
        $jsonArrayItem5 = array(); // Automated Cases Added
        $jsonArrayItem6 = array(); // Automated Cases Removed
        
        $jsonArrayItem['label'] = $row['date'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Calculate platform-specific totals based on selected platforms
        $currentAutomated = 0;
        $currentTotal = 0;
        
        $platforms = explode(',', $platform);
        foreach ($platforms as $platformValue) {
            $platformValue = strtolower($platformValue);
            switch ($platformValue) {
                case 'api':
                    $currentAutomated += $row['api_alreadyAutomated'];
                    $currentTotal += $row['api_totalAutomationCases'];
                    break;
                case 'web':
                    $currentAutomated += $row['web_alreadyAutomated'];
                    $currentTotal += $row['web_totalAutomationCases'];
                    break;
                case 'android':
                    $currentAutomated += $row['android_alreadyAutomated'];
                    $currentTotal += $row['android_totalAutomationCases'];
                    break;
                case 'ios':
                    $currentAutomated += $row['ios_alreadyAutomated'];
                    $currentTotal += $row['ios_totalAutomationCases'];
                    break;
            }
        }
        
        if ($prevAutomated !== null) {
            // Your existing delta calculations...
            $deltaAutomated = $currentAutomated - $prevAutomated;
            $deltaTotal = $currentTotal - $prevTotal;

            $totalDeltaCases += $deltaTotal;
            $totalDeltaAutomated += $deltaAutomated;

            if ($deltaTotal > 0) {
                $totalCasesAdded += $deltaTotal;
            } else {
                $totalCasesRemoved += abs($deltaTotal);
            }

            if ($deltaAutomated > 0) {
                $totalAutomatedAdded += $deltaAutomated;
            } else {
                $totalAutomatedRemoved += abs($deltaAutomated);
            }

            $jsonArrayItem1['value'] = $deltaTotal;
            $jsonArrayItem2['value'] = $deltaAutomated;

            if ($deltaTotal > 0) {
                $jsonArrayItem3['value'] = $deltaTotal;
                $jsonArrayItem4['value'] = 0;  // Use 0 instead of null
            } else {
                $jsonArrayItem3['value'] = 0;  // Use 0 instead of null
                $jsonArrayItem4['value'] = $deltaTotal;  // Keep the negative value
            }

            if ($deltaAutomated > 0) {
                $jsonArrayItem5['value'] = $deltaAutomated;
                $jsonArrayItem6['value'] = 0;  // Use 0 instead of null
            } else {
                $jsonArrayItem5['value'] = 0;  // Use 0 instead of null
                $jsonArrayItem6['value'] = $deltaAutomated;  // Keep the negative value
            }
        } else {
            $jsonArrayItem1['value'] = 0;
            $jsonArrayItem2['value'] = 0;
            $jsonArrayItem3['value'] = 0;
            $jsonArrayItem4['value'] = 0;
            $jsonArrayItem5['value'] = 0;
            $jsonArrayItem6['value'] = 0;
        }
        
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);
        array_push($jsonArraySubSet5, $jsonArrayItem5);
        array_push($jsonArraySubSet6, $jsonArrayItem6);
        
        $prevAutomated = $currentAutomated;
        $prevTotal = $currentTotal;
    }

    // Calculate net delta between first and last dates
    $netDeltaTotal = $lastDateValues['total'] - $firstDateValues['total'];
    $netDeltaAutomated = $lastDateValues['automated'] - $firstDateValues['automated'];
    
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Delta - Total Cases",
        "renderAs" => "line",
        "data" => $jsonArraySubSet1,
        "caption" => "Total Delta: " . $totalDeltaCases . " (+" . $totalCasesAdded . "/-" . $totalCasesRemoved . ")"
    ));
    
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Delta - Automated Cases",
        "renderAs" => "line",
        "data" => $jsonArraySubSet2,
        "caption" => "Total Delta: " . $totalDeltaAutomated . " (+" . $totalAutomatedAdded . "/-" . $totalAutomatedRemoved . ")"
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Cases - Added",
        "renderAs" => "line",
        "visible" => "0",
        "data" => $jsonArraySubSet3
    ));
    
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Cases - Removed",
        "renderAs" => "line",
        "visible" => "0",
        "data" => $jsonArraySubSet4
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Automated Cases - Added",
        "renderAs" => "line",
        "visible" => "0",
        "data" => $jsonArraySubSet5
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Automated Cases - Removed",
        "renderAs" => "line",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    
    // Add chart caption with total deltas
    $chartData = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );

    // Create delta info array
    $deltaInfo = array(
        "totalDelta" => $netDeltaTotal,
        "automatedDelta" => $netDeltaAutomated
    );

    // Return both chart data and delta info
    return array(
        "chartData" => $chartData,
        "deltaInfo" => $deltaInfo
    );
}


function getTestcaseCountTrend_Project($tableNamePrefix, $projectName, $startDate, $endDate, $platform) {
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
    $jsonArraySubSetCoverage = array(); // New array for coverage percentage

    $sql = "SELECT DATE(createdAt) AS createdAt,
        SUM(totalAutomationCases) AS totalAutomationCases,
        SUM(alreadyAutomated) AS alreadyAutomated,
        SUM(p0AutomationCases) AS p0AutomationCases,
        SUM(p0AutomatedCases) AS p0AutomatedCases,
        SUM(p1AutomationCases) AS p1AutomationCases,
        SUM(p1AutomatedCases) AS p1AutomatedCases,
        SUM(p2AutomationCases) AS p2AutomationCases,
        SUM(p2AutomatedCases) AS p2AutomatedCases,
        SUM(api_totalAutomationCases) AS api_totalAutomationCases,
        SUM(api_alreadyAutomated) AS api_alreadyAutomated,
        SUM(api_p0AutomationCases) AS api_p0AutomationCases,
        SUM(api_p0AutomatedCases) AS api_p0AutomatedCases,
        SUM(api_p1AutomationCases) AS api_p1AutomationCases,
        SUM(api_p1AutomatedCases) AS api_p1AutomatedCases,
        SUM(api_p2AutomationCases) AS api_p2AutomationCases,
        SUM(api_p2AutomatedCases) AS api_p2AutomatedCases,
        SUM(web_totalAutomationCases) AS web_totalAutomationCases,
        SUM(web_alreadyAutomated) AS web_alreadyAutomated,
        SUM(web_p0AutomationCases) AS web_p0AutomationCases,
        SUM(web_p0AutomatedCases) AS web_p0AutomatedCases,
        SUM(web_p1AutomationCases) AS web_p1AutomationCases,
        SUM(web_p1AutomatedCases) AS web_p1AutomatedCases,
        SUM(web_p2AutomationCases) AS web_p2AutomationCases,
        SUM(web_p2AutomatedCases) AS web_p2AutomatedCases,
        SUM(android_totalAutomationCases) AS android_totalAutomationCases,
        SUM(android_alreadyAutomated) AS android_alreadyAutomated,
        SUM(android_p0AutomationCases) AS android_p0AutomationCases,
        SUM(android_p0AutomatedCases) AS android_p0AutomatedCases,
        SUM(android_p1AutomationCases) AS android_p1AutomationCases,
        SUM(android_p1AutomatedCases) AS android_p1AutomatedCases,
        SUM(android_p2AutomationCases) AS android_p2AutomationCases,
        SUM(android_p2AutomatedCases) AS android_p2AutomatedCases,
        SUM(ios_totalAutomationCases) AS ios_totalAutomationCases,
        SUM(ios_alreadyAutomated) AS ios_alreadyAutomated,
        SUM(ios_p0AutomationCases) AS ios_p0AutomationCases,
        SUM(ios_p0AutomatedCases) AS ios_p0AutomatedCases,
        SUM(ios_p1AutomationCases) AS ios_p1AutomationCases,
        SUM(ios_p1AutomatedCases) AS ios_p1AutomatedCases,
        SUM(ios_p2AutomationCases) AS ios_p2AutomationCases,
        SUM(ios_p2AutomatedCases) AS ios_p2AutomatedCases
        FROM (
            SELECT 
                DATE(createdAt) AS createdAt,
                MAX(id) AS maxId,
                MAX(totalAutomationCases) AS totalAutomationCases,
                MAX(alreadyAutomated) AS alreadyAutomated,
                MAX(p0AutomationCases) AS p0AutomationCases,
                MAX(p0AutomatedCases) AS p0AutomatedCases,
                MAX(p1AutomationCases) AS p1AutomationCases,
                MAX(p1AutomatedCases) AS p1AutomatedCases,
                MAX(p2AutomationCases) AS p2AutomationCases,
                MAX(p2AutomatedCases) AS p2AutomatedCases,
                MAX(api_totalAutomationCases) AS api_totalAutomationCases,
                MAX(api_alreadyAutomated) AS api_alreadyAutomated,
                MAX(api_p0AutomationCases) AS api_p0AutomationCases,
                MAX(api_p0AutomatedCases) AS api_p0AutomatedCases,
                MAX(api_p1AutomationCases) AS api_p1AutomationCases,
                MAX(api_p1AutomatedCases) AS api_p1AutomatedCases,
                MAX(api_p2AutomationCases) AS api_p2AutomationCases,
                MAX(api_p2AutomatedCases) AS api_p2AutomatedCases,
                MAX(web_totalAutomationCases) AS web_totalAutomationCases,
                MAX(web_alreadyAutomated) AS web_alreadyAutomated,
                MAX(web_p0AutomationCases) AS web_p0AutomationCases,
                MAX(web_p0AutomatedCases) AS web_p0AutomatedCases,
                MAX(web_p1AutomationCases) AS web_p1AutomationCases,
                MAX(web_p1AutomatedCases) AS web_p1AutomatedCases,
                MAX(web_p2AutomationCases) AS web_p2AutomationCases,
                MAX(web_p2AutomatedCases) AS web_p2AutomatedCases,
                MAX(android_totalAutomationCases) AS android_totalAutomationCases,
                MAX(android_alreadyAutomated) AS android_alreadyAutomated,
                MAX(android_p0AutomationCases) AS android_p0AutomationCases,
                MAX(android_p0AutomatedCases) AS android_p0AutomatedCases,
                MAX(android_p1AutomationCases) AS android_p1AutomationCases,
                MAX(android_p1AutomatedCases) AS android_p1AutomatedCases,
                MAX(android_p2AutomationCases) AS android_p2AutomationCases,
                MAX(android_p2AutomatedCases) AS android_p2AutomatedCases,
                MAX(ios_totalAutomationCases) AS ios_totalAutomationCases,
                MAX(ios_alreadyAutomated) AS ios_alreadyAutomated,
                MAX(ios_p0AutomationCases) AS ios_p0AutomationCases,
                MAX(ios_p0AutomatedCases) AS ios_p0AutomatedCases,
                MAX(ios_p1AutomationCases) AS ios_p1AutomationCases,
                MAX(ios_p1AutomatedCases) AS ios_p1AutomatedCases,
                MAX(ios_p2AutomationCases) AS ios_p2AutomationCases,
                MAX(ios_p2AutomatedCases) AS ios_p2AutomatedCases
            FROM " . getTableName($tableNamePrefix) . "
            WHERE 
                projectName IN (" . $projectName . ")
                AND DATE(createdAt) >= '" . $startDate . "' 
                AND DATE(createdAt) <= '" . $endDate . "'
            GROUP BY DATE(createdAt), projectName
        ) temp
    GROUP BY DATE(createdAt);";

    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $totalDataArray = array(
        'totalAutomationCases' => 0,
        'alreadyAutomated' => 0,
        'p0AutomationCases' => 0,
        'p0AutomatedCases' => 0,
        'p1AutomationCases' => 0,
        'p1AutomatedCases' => 0,
        'p2AutomationCases' => 0,
        'p2AutomatedCases' => 0,
        'pnCases' => 0,
        'pnAutomatedCases' => 0,
        'pnAutomationCases' => 0
        );
        // Split the platform values into an array
        $platforms = explode(',', $platform);

        foreach ($platforms as $platformValue) 
        {
            $platformValue = strtolower($platformValue);
            switch ($platformValue) 
            {
                case 'api':
                    $totalDataArray['totalAutomationCases'] += $row['api_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['api_alreadyAutomated'];
                    $totalDataArray['p0AutomationCases'] += $row['api_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['api_p0AutomatedCases'];
                    $totalDataArray['p1AutomationCases'] += $row['api_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['api_p1AutomatedCases'];
                    $totalDataArray['p2AutomationCases'] += $row['api_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['api_p2AutomatedCases'];
                    break;

                case 'web':
                    // Calculation for 'web' platform
                    $totalDataArray['totalAutomationCases'] += $row['web_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                    $totalDataArray['p0AutomationCases'] += $row['web_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['web_p0AutomatedCases'];
                    $totalDataArray['p1AutomationCases'] += $row['web_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['web_p1AutomatedCases'];
                    $totalDataArray['p2AutomationCases'] += $row['web_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['web_p2AutomatedCases'];
                    break;

                case 'android':
                    // Calculation for 'android' platform
                    $totalDataArray['totalAutomationCases'] += $row['android_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                    $totalDataArray['p0AutomationCases'] += $row['android_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['android_p0AutomatedCases'];
                    $totalDataArray['p1AutomationCases'] += $row['android_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['android_p1AutomatedCases'];
                    $totalDataArray['p2AutomationCases'] += $row['android_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['android_p2AutomatedCases'];
                    break;

                case 'ios':
                    // Calculation for 'ios' platform
                    $totalDataArray['totalAutomationCases'] += $row['ios_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                    $totalDataArray['p0AutomationCases'] += $row['ios_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['ios_p0AutomatedCases'];
                    $totalDataArray['p1AutomationCases'] += $row['ios_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['ios_p1AutomatedCases'];
                    $totalDataArray['p2AutomationCases'] += $row['ios_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['ios_p2AutomatedCases'];
                    break;

                default:
                    showErrorMessage("Invalid Platform name - ".$platformValue);
                    break;
            }
        }

        $totalDataArray['pnAutomationCases'] = $totalDataArray['totalAutomationCases'] - ($totalDataArray['p0AutomationCases'] + $totalDataArray['p1AutomationCases'] + $totalDataArray['p2AutomationCases']);
        $totalDataArray['pnAutomatedCases'] = $totalDataArray['alreadyAutomated'] - ($totalDataArray['p0AutomatedCases'] + $totalDataArray['p1AutomatedCases'] + $totalDataArray['p2AutomatedCases']);


        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Calculate automation coverage percentage
        $coverageItem = array();
        $coverageItem['value'] = $totalDataArray['totalAutomationCases'] > 0 ? 
            round(($totalDataArray['alreadyAutomated'] / $totalDataArray['totalAutomationCases']) * 100, 2) : 0;
        array_push($jsonArraySubSetCoverage, $coverageItem);

        // Calculate P2+ combined values
        $p2PlusAutomatable = $totalDataArray['p2AutomationCases'] + $totalDataArray['pnAutomationCases'];
        $p2PlusAutomated = $totalDataArray['p2AutomatedCases'] + $totalDataArray['pnAutomatedCases'];

        // Automatable Cases
        $jsonArrayItem1['value'] = $totalDataArray['totalAutomationCases'];
        // Already Automated
        $jsonArrayItem2['value'] = $totalDataArray['alreadyAutomated'];
        // P0 Automatable
        $jsonArrayItem3['value'] = $totalDataArray['p0AutomationCases'];
        // P0 Automated
        $jsonArrayItem4['value'] = $totalDataArray['p0AutomatedCases'];
        // P1 Automatable
        $jsonArrayItem5['value'] = $totalDataArray['p1AutomationCases'];
        // P1 Automated
        $jsonArrayItem6['value'] = $totalDataArray['p1AutomatedCases'];
        // P2+ Automatable
        $jsonArrayItem7['value'] = $p2PlusAutomatable;
        // P2+ Automated
        $jsonArrayItem8['value'] = $p2PlusAutomated;

        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);
        array_push($jsonArraySubSet5, $jsonArrayItem5);
        array_push($jsonArraySubSet6, $jsonArrayItem6);
        array_push($jsonArraySubSet7, $jsonArrayItem7);
        array_push($jsonArraySubSet8, $jsonArrayItem8);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));

    // Add automation coverage percentage as first dataset
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Automation Coverage%",
        "parentyaxis" => "S",
        "renderas" => "line",
        "showvalues" => "1",
        "visible" => "1",
        "numberSuffix" => "%",
        "data" => $jsonArraySubSetCoverage
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Automatable Cases",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Already Automated",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Automated",
        "visible" => "0",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet5
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Automated",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2+ Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet7
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2+ Automated",
        "visible" => "0",
        "data" => $jsonArraySubSet8
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getTestcasesList_Project($tableNamePrefix, $projectName, $startDate, $endDate, $platform)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $firstReview_p0SlaInHours, $firstReview_p1SlaInHours, $firstReview_p2SlaInHours, $firstReview_p3SlaInHours, $firstReviewClosedStatuses, $developmentClosedStatuses, $overallClosedStatuses;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $sql = "SELECT 
            testrailId, 
            projectName, 
            title, 
            priority, 
            type, 
            platform, 
            apiAutomationStatus, 
            webAutomationStatus, 
            androidAutomationStatus, 
            CASE 
                WHEN iosAutomationStatus = 'NA' THEN 'NA'
                WHEN isMobileNative = 0 THEN 'Not Required'
                ELSE iosAutomationStatus
            END AS iosAutomationStatus, 
            reference 
        FROM " . getTestcasesTableName($tableNamePrefix) . " 
        WHERE isDeleted = 0
            AND " . $projectNameReference . " IN (" . $projectName . ") 
            AND executionMode = 'Automatable' 
            AND type IN ('FCT / Regression', 'Prod Sanity') 
            AND (" . implode(" OR ", array_map(function($p) {
                    return "LOWER(platform) LIKE '%" . strtolower(trim($p)) . "%'";
                }, explode(',', $platform))) . ")
            AND DATE(createdAt) <= '" . $endDate . "' 
        ORDER BY priority, type DESC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();
    return $rows;
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
        case 'getPriorityDistribution':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getPriorityDistribution($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getPlatformDistribution':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getPlatformDistribution($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getCoverageNumbers_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getCoverageNumbers_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getTotalvsAutomatedCount_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getTotalvsAutomatedCount_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getTestcaseCountDelta_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getTestcaseCountDelta_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getTestcaseCountTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getTestcaseCountTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
        break;
        case 'getTestcasesList_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getTestcasesList_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
    }
    echo json_encode($jsonArray);
}
?>