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
    $sql = "Select sum(f.alreadyAutomated) as alreadyAutomated,sum(f.totalCases) as totalCases, FLOOR((sum(f.alreadyAutomated)/sum(f.totalCases))*100) as automationCoveragePerc, sum(f.p0AutomatedCases) as p0AutomatedCases, sum(f.p0Cases) as p0Cases, FLOOR((sum(f.p0AutomatedCases)/sum(f.p0Cases))*100) as p0CoveragePerc, sum(f.p1AutomatedCases) as p1AutomatedCases, sum(f.p1Cases) as p1Cases, FLOOR((sum(f.p1AutomatedCases)/sum(f.p1Cases))*100) as p1CoveragePerc, f.id from ( select projectName, max(id) as id from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName not like 'Vertical%' group by projectName) as x inner join ".getTableName($tableNamePrefix)." as f on f.projectName = x.projectName and f.id = x.id order by id desc;";
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
        $jsonArrayItem1['automatableCases'] = $row['totalCases'];
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

    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
    $sql = showVerticalLevelData($sql, $isVerticalDataActive);

    foreach ($DB->query($sql) as $row)
    {

        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['projectName'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Calculate Full Coverage Percentage
        $fullCoveragePerc = $row['totalCases'] > 0 ? floor(($row['alreadyAutomated'] / $row['totalCases']) * 100) : 0;
        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $fullCoveragePerc;
        array_push($jsonArrayForFullCoverage, $jsonArrayItem1);

        // Calculate P0 Coverage Percentage
        $p0CoveragePerc = $row['p0Cases'] > 0 ? floor(($row['p0AutomatedCases'] / $row['p0Cases']) * 100) : 0;
        $jsonArrayItem2 = array();
        $jsonArrayItem2['value'] = $p0CoveragePerc;
        array_push($jsonArrayForP0Coverage, $jsonArrayItem2);

        // Calculate P1 Coverage Percentage
        $p1CoveragePerc = $row['p1Cases'] > 0 ? floor(($row['p1AutomatedCases'] / $row['p1Cases']) * 100) : 0;
        $jsonArrayItem3 = array();
        $jsonArrayItem3['value'] = $p1CoveragePerc;
        array_push($jsonArrayForP1Coverage, $jsonArrayItem3);

        // Calculate P2 Coverage Percentage
        $p2CoveragePerc = $row['p2Cases'] > 0 ? floor(($row['p2AutomatedCases'] / $row['p2Cases']) * 100) : 0;
        $jsonArrayItem4 = array();
        $jsonArrayItem4['value'] = $p2CoveragePerc;
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
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalCases desc,p0Cases desc,p1Cases desc;";
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
        $jsonArrayItem1['value'] = $row['p0Cases'];
        $jsonArrayItem2['value'] = $row['p1Cases'];
        $jsonArrayItem3['value'] = $row['p2Cases'];
        $otherCases = $row['totalCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
        $jsonArrayItem4['value'] = $otherCases;
        array_push($jsonArraySubSet1, $jsonArrayItem1);
        array_push($jsonArraySubSet2, $jsonArrayItem2);
        array_push($jsonArraySubSet3, $jsonArrayItem3);
        array_push($jsonArraySubSet4, $jsonArrayItem4);

        $jsonArrayItem5['value'] = $row['totalAutomationCases'];
        $jsonArrayItem6['value'] = $row['totalCases'] - $row['totalAutomationCases'];
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
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalCases desc,p0Cases desc,p1Cases desc;";
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
        $jsonArrayItem1['value'] = $row['api_totalCases'];
        $jsonArrayItem2['value'] = $row['web_totalCases'];
        $jsonArrayItem3['value'] = $row['android_totalCases'];
        $jsonArrayItem4['value'] = $row['ios_totalCases'];
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
    $sql = "select * from ".getTableName($tableNamePrefix)." as a where id in(select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(a.createdAt)>='" . $startDate . "' and date(a.createdAt)<='" . $endDate . "' and a.projectName not like 'Vertical%' order by totalCases desc,p0CoveragePerc desc,p1CoveragePerc desc;";
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
        $jsonArrayItem1['value'] = $row['p0Cases'];
        $jsonArrayItem2['value'] = $row['p1Cases'];
        $jsonArrayItem3['value'] = $row['p2Cases'];
        $otherCases = $row['totalCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
        $jsonArrayItem4['value'] = $otherCases;
        $jsonArrayItem5['value'] = $row['totalCases'] - $row['totalCases'];
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
            $sql = "Select sum(alreadyAutomated) as alreadyAutomated,sum(totalCases) as totalCases, FLOOR((sum(alreadyAutomated)/sum(totalCases))*100) as automationCoveragePerc, sum(p0AutomatedCases) as p0AutomatedCases, sum(p0Cases) as p0Cases, FLOOR((sum(p0AutomatedCases)/sum(p0Cases))*100) as p0CoveragePerc, sum(p1AutomatedCases) as p1AutomatedCases, sum(p1Cases) as p1Cases, FLOOR((sum(p1AutomatedCases)/sum(p1Cases))*100) as p1CoveragePerc from ".getTableName($tableNamePrefix)." where id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

            foreach ($DB->query($sql) as $row)
            {
                $totalDataArray['alreadyAutomated'] += $row['alreadyAutomated'];
                $totalDataArray['automatableCases'] += $row['totalCases'];
                $p0DataArray['alreadyAutomated'] += $row['p0AutomatedCases'];
                $p0DataArray['automatableCases'] += $row['p0Cases'];
                $p1DataArray['alreadyAutomated'] += $row['p1AutomatedCases'];
                $p1DataArray['automatableCases'] += $row['p1Cases'];
            }
        }
        else 
        {
            $sql = "SELECT SUM(api_alreadyAutomated) AS api_alreadyAutomated, SUM(api_totalCases) AS api_totalCases, FLOOR((SUM(api_alreadyAutomated)/SUM(api_totalCases))*100) AS api_automationCoveragePerc, SUM(api_p0AutomatedCases) AS api_p0AutomatedCases, SUM(api_p0Cases) AS api_p0Cases, FLOOR((SUM(api_p0AutomatedCases)/SUM(api_p0Cases))*100) AS api_p0CoveragePerc, SUM(api_p1AutomatedCases) AS api_p1AutomatedCases, SUM(api_p1Cases) AS api_p1Cases, FLOOR((SUM(api_p1AutomatedCases)/SUM(api_p1Cases))*100) AS api_p1CoveragePerc, SUM(web_alreadyAutomated) AS web_alreadyAutomated, SUM(web_totalCases) AS web_totalCases, FLOOR((SUM(web_alreadyAutomated)/SUM(web_totalCases))*100) AS web_automationCoveragePerc, SUM(web_p0AutomatedCases) AS web_p0AutomatedCases, SUM(web_p0Cases) AS web_p0Cases, FLOOR((SUM(web_p0AutomatedCases)/SUM(web_p0Cases))*100) AS web_p0CoveragePerc, SUM(web_p1AutomatedCases) AS web_p1AutomatedCases, SUM(web_p1Cases) AS web_p1Cases, FLOOR((SUM(web_p1AutomatedCases)/SUM(web_p1Cases))*100) AS web_p1CoveragePerc, SUM(android_alreadyAutomated) AS android_alreadyAutomated, SUM(android_totalCases) AS android_totalCases, FLOOR((SUM(android_alreadyAutomated)/SUM(android_totalCases))*100) AS android_automationCoveragePerc, SUM(android_p0AutomatedCases) AS android_p0AutomatedCases, SUM(android_p0Cases) AS android_p0Cases, FLOOR((SUM(android_p0AutomatedCases)/SUM(android_p0Cases))*100) AS android_p0CoveragePerc, SUM(android_p1AutomatedCases) AS android_p1AutomatedCases, SUM(android_p1Cases) AS android_p1Cases, FLOOR((SUM(android_p1AutomatedCases)/SUM(android_p1Cases))*100) AS android_p1CoveragePerc, SUM(ios_alreadyAutomated) AS ios_alreadyAutomated, SUM(ios_totalCases) AS ios_totalCases, FLOOR((SUM(ios_alreadyAutomated)/SUM(ios_totalCases))*100) AS ios_automationCoveragePerc, SUM(ios_p0AutomatedCases) AS ios_p0AutomatedCases, SUM(ios_p0Cases) AS ios_p0Cases, FLOOR((SUM(ios_p0AutomatedCases)/SUM(ios_p0Cases))*100) AS ios_p0CoveragePerc, SUM(ios_p1AutomatedCases) AS ios_p1AutomatedCases, SUM(ios_p1Cases) AS ios_p1Cases, FLOOR((SUM(ios_p1AutomatedCases)/SUM(ios_p1Cases))*100) AS ios_p1CoveragePerc FROM ".getTableName($tableNamePrefix)." WHERE id IN (SELECT MAX(id) FROM ".getTableName($tableNamePrefix)." WHERE DATE(createdAt)>='" . $startDate . "' AND DATE(createdAt)<='" . $endDate . "' AND projectName in (" . $projectName . ") GROUP BY projectName);";

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
                            $totalDataArray['automatableCases'] += $row['api_totalCases'];
                            $p0DataArray['alreadyAutomated'] += $row['api_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['api_p0Cases'];
                            $p1DataArray['alreadyAutomated'] += $row['api_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['api_p1Cases'];
                            break;

                        case 'web':
                            // Calculation for 'web' platform
                            $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['web_totalCases'];
                            $p0DataArray['alreadyAutomated'] += $row['web_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['web_p0Cases'];
                            $p1DataArray['alreadyAutomated'] += $row['web_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['web_p1Cases'];
                            break;

                        case 'android':
                            // Calculation for 'android' platform
                            $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['android_totalCases'];
                            $p0DataArray['alreadyAutomated'] += $row['android_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['android_p0Cases'];
                            $p1DataArray['alreadyAutomated'] += $row['android_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['android_p1Cases'];
                            break;

                        case 'ios':
                            // Calculation for 'ios' platform
                            $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                            $totalDataArray['automatableCases'] += $row['ios_totalCases'];
                            $p0DataArray['alreadyAutomated'] += $row['ios_p0AutomatedCases'];
                            $p0DataArray['automatableCases'] += $row['ios_p0Cases'];
                            $p1DataArray['alreadyAutomated'] += $row['ios_p1AutomatedCases'];
                            $p1DataArray['automatableCases'] += $row['ios_p1Cases'];
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
    $jsonArrayItem['label'] = "Other Automation cases";
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
        $sql = "select sum(totalCases) as totalCases, sum(alreadyAutomated) as alreadyAutomated, sum(p0Cases) as p0Cases, sum(p0AutomatedCases) as p0AutomatedCases, sum(p1Cases) as p1Cases, sum(p1AutomatedCases) as p1AutomatedCases, sum(p2Cases) as p2Cases, sum(p2AutomatedCases) as p2AutomatedCases from ".getTableName($tableNamePrefix)." where id in (select max(id) from ".getTableName($tableNamePrefix)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName);";

        foreach ($DB->query($sql) as $row)
        {
            $totalDataArray['alreadyAutomated'] += $row['alreadyAutomated'];
            $totalDataArray['automatableCases'] += $row['totalCases'];
            $p0DataArray['alreadyAutomated'] += $row['p0AutomatedCases'];
            $p0DataArray['automatableCases'] += $row['p0Cases'];
            $p1DataArray['alreadyAutomated'] += $row['p1AutomatedCases'];
            $p1DataArray['automatableCases'] += $row['p1Cases'];
            $p2DataArray['alreadyAutomated'] += $row['p2AutomatedCases'];
            $p2DataArray['automatableCases'] += $row['p2Cases'];
            $pnDataArray['alreadyAutomated'] += $totalDataArray['alreadyAutomated'] - ($p0DataArray['automatableCases'] + $p1DataArray['automatableCases'] + $p2DataArray['automatableCases']);
            $pnDataArray['automatableCases'] += $totalDataArray['automatableCases'] - ($p0DataArray['automatableCases'] + $p1DataArray['automatableCases'] + $p2DataArray['automatableCases']);
        }
    }
    else 
    {
        $sql = "SELECT SUM(api_alreadyAutomated) AS api_alreadyAutomated, SUM(api_totalCases) AS api_totalCases, FLOOR((SUM(api_alreadyAutomated)/SUM(api_totalCases))*100) AS api_automationCoveragePerc, SUM(api_p0AutomatedCases) AS api_p0AutomatedCases, SUM(api_p0Cases) AS api_p0Cases, FLOOR((SUM(api_p0AutomatedCases)/SUM(api_p0Cases))*100) AS api_p0CoveragePerc, SUM(api_p1AutomatedCases) AS api_p1AutomatedCases, SUM(api_p1Cases) AS api_p1Cases, FLOOR((SUM(api_p1AutomatedCases)/SUM(api_p1Cases))*100) AS api_p1CoveragePerc, SUM(api_p2AutomatedCases) AS api_p2AutomatedCases, SUM(api_p2Cases) AS api_p2Cases, FLOOR((SUM(api_p2AutomatedCases)/SUM(api_p2Cases))*100) AS api_p2CoveragePerc, SUM(web_alreadyAutomated) AS web_alreadyAutomated, SUM(web_totalCases) AS web_totalCases, FLOOR((SUM(web_alreadyAutomated)/SUM(web_totalCases))*100) AS web_automationCoveragePerc, SUM(web_p0AutomatedCases) AS web_p0AutomatedCases, SUM(web_p0Cases) AS web_p0Cases, FLOOR((SUM(web_p0AutomatedCases)/SUM(web_p0Cases))*100) AS web_p0CoveragePerc, SUM(web_p1AutomatedCases) AS web_p1AutomatedCases, SUM(web_p1Cases) AS web_p1Cases, FLOOR((SUM(web_p1AutomatedCases)/SUM(web_p1Cases))*100) AS web_p1CoveragePerc, SUM(web_p2AutomatedCases) AS web_p2AutomatedCases, SUM(web_p2Cases) AS web_p2Cases, FLOOR((SUM(web_p2AutomatedCases)/SUM(web_p2Cases))*100) AS web_p2CoveragePerc, SUM(android_alreadyAutomated) AS android_alreadyAutomated, SUM(android_totalCases) AS android_totalCases, FLOOR((SUM(android_alreadyAutomated)/SUM(android_totalCases))*100) AS android_automationCoveragePerc, SUM(android_p0AutomatedCases) AS android_p0AutomatedCases, SUM(android_p0Cases) AS android_p0Cases, FLOOR((SUM(android_p0AutomatedCases)/SUM(android_p0Cases))*100) AS android_p0CoveragePerc, SUM(android_p1AutomatedCases) AS android_p1AutomatedCases, SUM(android_p1Cases) AS android_p1Cases, FLOOR((SUM(android_p1AutomatedCases)/SUM(android_p1Cases))*100) AS android_p1CoveragePerc, SUM(android_p2AutomatedCases) AS android_p2AutomatedCases, SUM(android_p2Cases) AS android_p2Cases, FLOOR((SUM(android_p2AutomatedCases)/SUM(android_p2Cases))*100) AS android_p2CoveragePerc, SUM(ios_alreadyAutomated) AS ios_alreadyAutomated, SUM(ios_totalCases) AS ios_totalCases, FLOOR((SUM(ios_alreadyAutomated)/SUM(ios_totalCases))*100) AS ios_automationCoveragePerc, SUM(ios_p0AutomatedCases) AS ios_p0AutomatedCases, SUM(ios_p0Cases) AS ios_p0Cases, FLOOR((SUM(ios_p0AutomatedCases)/SUM(ios_p0Cases))*100) AS ios_p0CoveragePerc, SUM(ios_p1AutomatedCases) AS ios_p1AutomatedCases, SUM(ios_p1Cases) AS ios_p1Cases, FLOOR((SUM(ios_p1AutomatedCases)/SUM(ios_p1Cases))*100) AS ios_p1CoveragePerc, SUM(ios_p2AutomatedCases) AS ios_p2AutomatedCases, SUM(ios_p2Cases) AS ios_p2Cases, FLOOR((SUM(ios_p2AutomatedCases)/SUM(ios_p2Cases))*100) AS ios_p2CoveragePerc FROM ".getTableName($tableNamePrefix)." WHERE id IN (SELECT MAX(id) FROM ".getTableName($tableNamePrefix)." WHERE DATE(createdAt)>='" . $startDate . "' AND DATE(createdAt)<='" . $endDate . "' AND projectName IN (" . $projectName . ") GROUP BY projectName);";

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
                        $totalDataArray['automatableCases'] += $row['api_totalCases'];
                        $p0DataArray['alreadyAutomated'] += $row['api_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['api_p0Cases'];
                        $p1DataArray['alreadyAutomated'] += $row['api_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['api_p1Cases'];
                        $p2DataArray['alreadyAutomated'] += $row['api_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['api_p2Cases'];
                        break;

                    case 'web':
                        // Calculation for 'web' platform
                        $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['web_totalCases'];
                        $p0DataArray['alreadyAutomated'] += $row['web_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['web_p0Cases'];
                        $p1DataArray['alreadyAutomated'] += $row['web_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['web_p1Cases'];
                        $p2DataArray['alreadyAutomated'] += $row['web_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['web_p2Cases'];
                        break;

                    case 'android':
                        // Calculation for 'android' platform
                        $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['android_totalCases'];
                        $p0DataArray['alreadyAutomated'] += $row['android_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['android_p0Cases'];
                        $p1DataArray['alreadyAutomated'] += $row['android_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['android_p1Cases'];
                        $p2DataArray['alreadyAutomated'] += $row['android_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['android_p2Cases'];
                        break;

                    case 'ios':
                        // Calculation for 'ios' platform
                        $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                        $totalDataArray['automatableCases'] += $row['ios_totalCases'];
                        $p0DataArray['alreadyAutomated'] += $row['ios_p0AutomatedCases'];
                        $p0DataArray['automatableCases'] += $row['ios_p0Cases'];
                        $p1DataArray['alreadyAutomated'] += $row['ios_p1AutomatedCases'];
                        $p1DataArray['automatableCases'] += $row['ios_p1Cases'];
                        $p2DataArray['alreadyAutomated'] += $row['ios_p2AutomatedCases'];
                        $p2DataArray['automatableCases'] += $row['ios_p2Cases'];
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
    $jsonArraySubSet13 = array();
    $jsonArraySubSetCoverage = array(); // New array for coverage percentage
    $jsonArraySubSetManualPerc = array(); // New array for Manual percentage

    $sql = "SELECT DATE(createdAt) AS createdAt,
    SUM(totalCases) AS totalCases,
    SUM(totalAutomationCases) AS totalAutomationCases,
    SUM(alreadyAutomated) AS alreadyAutomated,
    SUM(p0Cases) AS p0Cases,
    SUM(p1Cases) AS p1Cases,
    SUM(p2Cases) AS p2Cases,
    SUM(p0AutomationCases) AS p0AutomationCases,
    SUM(p0AutomatedCases) AS p0AutomatedCases,
    SUM(p1AutomationCases) AS p1AutomationCases,
    SUM(p1AutomatedCases) AS p1AutomatedCases,
    SUM(p2AutomationCases) AS p2AutomationCases,
    SUM(p2AutomatedCases) AS p2AutomatedCases,
    SUM(api_totalCases) AS api_totalCases,
    SUM(api_totalAutomationCases) AS api_totalAutomationCases,
    SUM(api_alreadyAutomated) AS api_alreadyAutomated,
    SUM(api_p0Cases) AS api_p0Cases,
    SUM(api_p1Cases) AS api_p1Cases,
    SUM(api_p2Cases) AS api_p2Cases,
    SUM(api_p0AutomationCases) AS api_p0AutomationCases,
    SUM(api_p0AutomatedCases) AS api_p0AutomatedCases,
    SUM(api_p1AutomationCases) AS api_p1AutomationCases,
    SUM(api_p1AutomatedCases) AS api_p1AutomatedCases,
    SUM(api_p2AutomationCases) AS api_p2AutomationCases,
    SUM(api_p2AutomatedCases) AS api_p2AutomatedCases,
    SUM(web_totalCases) AS web_totalCases,
    SUM(web_totalAutomationCases) AS web_totalAutomationCases,
    SUM(web_alreadyAutomated) AS web_alreadyAutomated,
    SUM(web_p0Cases) AS web_p0Cases,
    SUM(web_p1Cases) AS web_p1Cases,
    SUM(web_p2Cases) AS web_p2Cases,
    SUM(web_p0AutomationCases) AS web_p0AutomationCases,
    SUM(web_p0AutomatedCases) AS web_p0AutomatedCases,
    SUM(web_p1AutomationCases) AS web_p1AutomationCases,
    SUM(web_p1AutomatedCases) AS web_p1AutomatedCases,
    SUM(web_p2AutomationCases) AS web_p2AutomationCases,
    SUM(web_p2AutomatedCases) AS web_p2AutomatedCases,
    SUM(android_totalCases) AS android_totalCases,
    SUM(android_totalAutomationCases) AS android_totalAutomationCases,
    SUM(android_alreadyAutomated) AS android_alreadyAutomated,
    SUM(android_p0Cases) AS android_p0Cases,
    SUM(android_p1Cases) AS android_p1Cases,
    SUM(android_p2Cases) AS android_p2Cases,
    SUM(android_p0AutomationCases) AS android_p0AutomationCases,
    SUM(android_p0AutomatedCases) AS android_p0AutomatedCases,
    SUM(android_p1AutomationCases) AS android_p1AutomationCases,
    SUM(android_p1AutomatedCases) AS android_p1AutomatedCases,
    SUM(android_p2AutomationCases) AS android_p2AutomationCases,
    SUM(android_p2AutomatedCases) AS android_p2AutomatedCases,
    SUM(ios_totalCases) AS ios_totalCases,
    SUM(ios_totalAutomationCases) AS ios_totalAutomationCases,
    SUM(ios_alreadyAutomated) AS ios_alreadyAutomated,
    SUM(ios_p0Cases) AS ios_p0Cases,
    SUM(ios_p1Cases) AS ios_p1Cases,
    SUM(ios_p2Cases) AS ios_p2Cases,
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
        MAX(totalCases) AS totalCases,
        MAX(totalAutomationCases) AS totalAutomationCases,
        MAX(alreadyAutomated) AS alreadyAutomated,
        MAX(p0Cases) AS p0Cases,
        MAX(p1Cases) AS p1Cases,
        MAX(p2Cases) AS p2Cases,
        MAX(p0AutomationCases) AS p0AutomationCases,
        MAX(p0AutomatedCases) AS p0AutomatedCases,
        MAX(p1AutomationCases) AS p1AutomationCases,
        MAX(p1AutomatedCases) AS p1AutomatedCases,
        MAX(p2AutomationCases) AS p2AutomationCases,
        MAX(p2AutomatedCases) AS p2AutomatedCases,
        MAX(api_totalCases) AS api_totalCases,
        MAX(api_totalAutomationCases) AS api_totalAutomationCases,
        MAX(api_alreadyAutomated) AS api_alreadyAutomated,
        MAX(api_p0Cases) AS api_p0Cases,
        MAX(api_p1Cases) AS api_p1Cases,
        MAX(api_p2Cases) AS api_p2Cases,
        MAX(api_p0AutomationCases) AS api_p0AutomationCases,
        MAX(api_p0AutomatedCases) AS api_p0AutomatedCases,
        MAX(api_p1AutomationCases) AS api_p1AutomationCases,
        MAX(api_p1AutomatedCases) AS api_p1AutomatedCases,
        MAX(api_p2AutomationCases) AS api_p2AutomationCases,
        MAX(api_p2AutomatedCases) AS api_p2AutomatedCases,
        MAX(web_totalCases) AS web_totalCases,
        MAX(web_totalAutomationCases) AS web_totalAutomationCases,
        MAX(web_alreadyAutomated) AS web_alreadyAutomated,
        MAX(web_p0Cases) AS web_p0Cases,
        MAX(web_p1Cases) AS web_p1Cases,
        MAX(web_p2Cases) AS web_p2Cases,
        MAX(web_p0AutomationCases) AS web_p0AutomationCases,
        MAX(web_p0AutomatedCases) AS web_p0AutomatedCases,
        MAX(web_p1AutomationCases) AS web_p1AutomationCases,
        MAX(web_p1AutomatedCases) AS web_p1AutomatedCases,
        MAX(web_p2AutomationCases) AS web_p2AutomationCases,
        MAX(web_p2AutomatedCases) AS web_p2AutomatedCases,
        MAX(android_totalCases) AS android_totalCases,
        MAX(android_totalAutomationCases) AS android_totalAutomationCases,
        MAX(android_alreadyAutomated) AS android_alreadyAutomated,
        MAX(android_p0Cases) AS android_p0Cases,
        MAX(android_p1Cases) AS android_p1Cases,
        MAX(android_p2Cases) AS android_p2Cases,
        MAX(android_p0AutomationCases) AS android_p0AutomationCases,
        MAX(android_p0AutomatedCases) AS android_p0AutomatedCases,
        MAX(android_p1AutomationCases) AS android_p1AutomationCases,
        MAX(android_p1AutomatedCases) AS android_p1AutomatedCases,
        MAX(android_p2AutomationCases) AS android_p2AutomationCases,
        MAX(android_p2AutomatedCases) AS android_p2AutomatedCases,
        MAX(ios_totalCases) AS ios_totalCases,
        MAX(ios_totalAutomationCases) AS ios_totalAutomationCases,
        MAX(ios_alreadyAutomated) AS ios_alreadyAutomated,
        MAX(ios_p0Cases) AS ios_p0Cases,
        MAX(ios_p1Cases) AS ios_p1Cases,
        MAX(ios_p2Cases) AS ios_p2Cases,
        MAX(ios_p0AutomationCases) AS ios_p0AutomationCases,
        MAX(ios_p0AutomatedCases) AS ios_p0AutomatedCases,
        MAX(ios_p1AutomationCases) AS ios_p1AutomationCases,
        MAX(ios_p1AutomatedCases) AS ios_p1AutomatedCases,
        MAX(ios_p2AutomationCases) AS ios_p2AutomationCases,
        MAX(ios_p2AutomatedCases) AS ios_p2AutomatedCases
    FROM " . getTableName($tableNamePrefix) . "
    WHERE projectName IN (" . $projectName . ")
        AND DATE(createdAt) >= '" . $startDate . "' 
        AND DATE(createdAt) <= '" . $endDate . "'
    GROUP BY DATE(createdAt), projectName
    ) temp
    GROUP BY DATE(createdAt);";

    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row)
    {
        $totalDataArray = array(
        'totalCases' => 0,
        'totalManualCases' => 0,
        'totalAutomationCases' => 0,
        'alreadyAutomated' => 0,
        'p0Cases' => 0,
        'p0ManualCases' => 0,
        'p0AutomationCases' => 0,
        'p0AutomatedCases' => 0,
        'p1Cases' => 0,
        'p1ManualCases' => 0,
        'p1AutomationCases' => 0,
        'p1AutomatedCases' => 0,
        'p2Cases' => 0,
        'p2ManualCases' => 0,
        'p2AutomationCases' => 0,
        'p2AutomatedCases' => 0,
        'pnCases' => 0,
        'pnManualCases' => 0,
        'pnAutomationCases' => 0,
        'pnAutomatedCases' => 0
        );
        // Split the platform values into an array
        $platforms = explode(',', $platform);

        foreach ($platforms as $platformValue) 
        {
            $platformValue = strtolower($platformValue);
            switch ($platformValue) 
            {
                case 'api':
                    $totalDataArray['totalCases'] += $row['api_totalCases'];
                    $totalDataArray['totalAutomationCases'] += $row['api_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['api_alreadyAutomated'];
                    $totalDataArray['p0Cases'] += $row['api_p0Cases'];
                    $totalDataArray['p0AutomationCases'] += $row['api_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['api_p0AutomatedCases'];
                    $totalDataArray['p1Cases'] += $row['api_p1Cases'];
                    $totalDataArray['p1AutomationCases'] += $row['api_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['api_p1AutomatedCases'];
                    $totalDataArray['p2Cases'] += $row['api_p2Cases'];
                    $totalDataArray['p2AutomationCases'] += $row['api_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['api_p2AutomatedCases'];
                    break;

                case 'web':
                    // Calculation for 'web' platform
                    $totalDataArray['totalCases'] += $row['web_totalCases'];
                    $totalDataArray['totalAutomationCases'] += $row['web_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['web_alreadyAutomated'];
                    $totalDataArray['p0Cases'] += $row['web_p0Cases'];
                    $totalDataArray['p0AutomationCases'] += $row['web_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['web_p0AutomatedCases'];
                    $totalDataArray['p1Cases'] += $row['web_p1Cases'];
                    $totalDataArray['p1AutomationCases'] += $row['web_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['web_p1AutomatedCases'];
                    $totalDataArray['p2Cases'] += $row['web_p2Cases'];
                    $totalDataArray['p2AutomationCases'] += $row['web_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['web_p2AutomatedCases'];
                    break;

                case 'android':
                    // Calculation for 'android' platform
                    $totalDataArray['totalCases'] += $row['android_totalCases'];
                    $totalDataArray['totalAutomationCases'] += $row['android_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['android_alreadyAutomated'];
                    $totalDataArray['p0Cases'] += $row['android_p0Cases'];
                    $totalDataArray['p0AutomationCases'] += $row['android_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['android_p0AutomatedCases'];
                    $totalDataArray['p1Cases'] += $row['android_p1Cases'];
                    $totalDataArray['p1AutomationCases'] += $row['android_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['android_p1AutomatedCases'];
                    $totalDataArray['p2Cases'] += $row['android_p2Cases'];
                    $totalDataArray['p2AutomationCases'] += $row['android_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['android_p2AutomatedCases'];
                    break;

                case 'ios':
                    // Calculation for 'ios' platform
                    $totalDataArray['totalCases'] += $row['ios_totalCases'];
                    $totalDataArray['totalAutomationCases'] += $row['ios_totalAutomationCases'];
                    $totalDataArray['alreadyAutomated'] += $row['ios_alreadyAutomated'];
                    $totalDataArray['p0Cases'] += $row['ios_p0Cases'];
                    $totalDataArray['p0AutomationCases'] += $row['ios_p0AutomationCases'];
                    $totalDataArray['p0AutomatedCases'] += $row['ios_p0AutomatedCases'];
                    $totalDataArray['p1Cases'] += $row['ios_p1Cases'];
                    $totalDataArray['p1AutomationCases'] += $row['ios_p1AutomationCases'];
                    $totalDataArray['p1AutomatedCases'] += $row['ios_p1AutomatedCases'];
                    $totalDataArray['p2Cases'] += $row['ios_p2Cases'];
                    $totalDataArray['p2AutomationCases'] += $row['ios_p2AutomationCases'];
                    $totalDataArray['p2AutomatedCases'] += $row['ios_p2AutomatedCases'];
                    break;

                default:
                    showErrorMessage("Invalid Platform name - ".$platformValue);
                    break;
            }
        }
        $totalDataArray['totalManualCases'] = $totalDataArray['totalCases'] - $totalDataArray['totalAutomationCases'];
        $totalDataArray['p0ManualCases'] = $totalDataArray['p0Cases'] - $totalDataArray['p0AutomationCases'];
        $totalDataArray['p1ManualCases'] = $totalDataArray['p1Cases'] - $totalDataArray['p1AutomationCases'];
        $totalDataArray['p2ManualCases'] = $totalDataArray['p2Cases'] - $totalDataArray['p2AutomationCases'];

        $totalDataArray['pnCases'] = $totalDataArray['totalCases'] - ($totalDataArray['p0Cases'] + $totalDataArray['p1Cases'] + $totalDataArray['p2Cases']);
        $totalDataArray['pnAutomationCases'] = $totalDataArray['totalAutomationCases'] - ($totalDataArray['p0AutomationCases'] + $totalDataArray['p1AutomationCases'] + $totalDataArray['p2AutomationCases']);
        $totalDataArray['pnAutomatedCases'] = $totalDataArray['alreadyAutomated'] - ($totalDataArray['p0AutomatedCases'] + $totalDataArray['p1AutomatedCases'] + $totalDataArray['p2AutomatedCases']);
        $totalDataArray['pnManualCases'] = $totalDataArray['pnCases'] - $totalDataArray['pnAutomationCases'];

        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'];
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Calculate automation coverage percentage
        $coverageItem = array();
        $coverageItem['value'] = $totalDataArray['totalCases'] > 0 ? 
            round(($totalDataArray['alreadyAutomated'] / $totalDataArray['totalCases']) * 100, 2) : 0;
        array_push($jsonArraySubSetCoverage, $coverageItem);

        // Calculate manual percentage
        $manualPercItem = array();
        $manualPercItem['value'] = $totalDataArray['totalCases'] > 0 ? 
            round(($totalDataArray['totalManualCases'] / $totalDataArray['totalCases']) * 100, 2) : 0;
        array_push($jsonArraySubSetManualPerc, $manualPercItem);

        // Calculate P2+ combined values
        $p2PlusCases = $totalDataArray['p2Cases'] + $totalDataArray['pnCases'];
        $p2PlusAutomatable = $totalDataArray['p2AutomationCases'] + $totalDataArray['pnAutomationCases'];
        $p2PlusManual = $p2PlusCases - $p2PlusAutomatable;

        // Total Cases
        $jsonArrayItem1['value'] = $totalDataArray['totalCases'];
        // Only Automatable
        $jsonArrayItem2['value'] = $totalDataArray['totalAutomationCases'];
        // Already Automated
        $jsonArrayItem3['value'] = $totalDataArray['alreadyAutomated'];
        // Only Manual
        $jsonArrayItem4['value'] = $totalDataArray['totalManualCases'];
        // P0 Cases
        $jsonArrayItem5['value'] = $totalDataArray['p0Cases'];
        // P0 Automatable
        $jsonArrayItem6['value'] = $totalDataArray['p0AutomationCases'];
        // P0 Manual
        $jsonArrayItem7['value'] = $totalDataArray['p0ManualCases'];
        // P1 Cases
        $jsonArrayItem8['value'] = $totalDataArray['p1Cases'];
        // P1 Automatable
        $jsonArrayItem9['value'] = $totalDataArray['p1AutomationCases'];
        // P1 Manual
        $jsonArrayItem10['value'] = $totalDataArray['p1ManualCases'];
        // P2+ Cases
        $jsonArrayItem11['value'] = $p2PlusCases;
        // P2+ Automatable
        $jsonArrayItem12['value'] = $p2PlusAutomatable;
        // P2+ Manual
        $jsonArrayItem13['value'] = $p2PlusManual;

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
        array_push($jsonArraySubSet13, $jsonArrayItem13);
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
        "seriesname" => "Manual %",
        "parentyaxis" => "S",
        "renderas" => "line",
        "showvalues" => "1",
        "numberSuffix" => "%",
        "data" => $jsonArraySubSetManualPerc
    ));

    array_push($jsonArrayDataSet, array(
        "seriesname" => "Total Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Only Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Already Automated",
        "visible" => "0",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "Only Manual",
        "visible" => "0",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet5
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P0 Manual",
        "visible" => "0",
        "data" => $jsonArraySubSet7
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet8
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet9
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P1 Manual",
        "visible" => "0",
        "data" => $jsonArraySubSet10
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2+ Cases",
        "visible" => "0",
        "data" => $jsonArraySubSet11
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2+ Automatable",
        "visible" => "0",
        "data" => $jsonArraySubSet12
    ));
    array_push($jsonArrayDataSet, array(
        "seriesname" => "P2+ Manual",
        "visible" => "0",
        "data" => $jsonArraySubSet13
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
            iosAutomationStatus, 
            reference 
        FROM " . getTestcasesTableName($tableNamePrefix) . " 
        WHERE isDeleted = 0
            AND " . $projectNameReference . " IN (" . $projectName . ") 
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