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
    $tableName = str_replace(" ", "_", strtolower($_GET['arguments'][0])."_testrail");
    
    switch ($_GET['functionname'])
    {
        case 'getProjectNames':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 1))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select projectName from $tableName group by projectName order by projectName asc;";

            foreach ($dbo->query($sql) as $row)
            {
                array_push($jsonArray, $row['projectName']);
            }
        break;
        case 'getCoverageNumbers_All':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "Select FLOOR(avg(f.automationCoveragePerc)) as automationCoveragePerc, FLOOR(avg(f.p0CoveragePerc)) as p0CoveragePerc, FLOOR(avg(f.p1CoveragePerc)) as p1CoveragePerc, f.id from ( select projectName, max(id) as id from $tableName group by projectName) as x inner join $tableName as f on f.projectName = x.projectName and f.id = x.id order by id desc;";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem["totalCoverage"] = $row['automationCoveragePerc'];
                $jsonArrayItem["P0Coverage"] = $row['p0CoveragePerc'];
                $jsonArrayItem["P1Coverage"] = $row['p1CoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;
        case 'getP0CoverageChange':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT a.projectName as projectName, a.p0CoveragePerc as newP0CoveragePerc,b.p0CoveragePerc as oldP0CoveragePerc FROM $tableName a JOIN $tableName b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN $tableName c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from $tableName group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL " . $_GET['arguments'][1] . " DAY) and (a.p0CoveragePerc > b.p0CoveragePerc or a.p0CoveragePerc < b.p0CoveragePerc) group by projectName order by (a.p0CoveragePerc - b.p0CoveragePerc) desc;";

            foreach ($dbo->query($sql) as $row)
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
        break;

        case 'getP1CoverageChange':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT a.projectName as projectName, a.p1CoveragePerc as newP1CoveragePerc,b.p1CoveragePerc as oldP1CoveragePerc FROM $tableName a JOIN $tableName b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN $tableName c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from $tableName group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL " . $_GET['arguments'][1] . " DAY) and (a.p1CoveragePerc > b.p1CoveragePerc or a.p1CoveragePerc < b.p1CoveragePerc) group by projectName order by (a.p1CoveragePerc - b.p1CoveragePerc) desc";

            foreach ($dbo->query($sql) as $row)
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
        break;

        case 'getAutomatedCountChange':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT a.projectName as projectName, a.alreadyAutomated as newAlreadyAutomated,b.alreadyAutomated as oldAlreadyAutomated FROM $tableName a JOIN $tableName b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN $tableName c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from $tableName group by projectName) and b.createdAt>=DATE_SUB(a.createdAt, INTERVAL " . $_GET['arguments'][1] . " DAY) and (a.alreadyAutomated > b.alreadyAutomated or a.alreadyAutomated < b.alreadyAutomated) group by projectName order by (a.alreadyAutomated - b.alreadyAutomated) desc;";

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem1 = array();
                $jsonArrayItem2 = array();
                $jsonArrayItem3 = array();

                $jsonArrayItem['label'] = $row['projectName'];
                array_push($jsonArraySubCategory, $jsonArrayItem);
                $jsonArrayItem1['value'] = $row['oldAlreadyAutomated'];
                $increment = $row['newAlreadyAutomated'] - $row['oldAlreadyAutomated'];

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
                "seriesname" => "Previous Count",
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
        break;

        case 'getTotalP0Coverage':
            $sql = "select * from $tableName where id in(select max(id) from $tableName group by projectName) order by p0CoveragePerc desc;";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['projectName'];
                $jsonArrayItem['value'] = $row['p0CoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getTotalP1Coverage':
            $sql = "select * from $tableName where id in(select max(id) from $tableName group by projectName) order by p1CoveragePerc desc;";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['projectName'];
                $jsonArrayItem['value'] = $row['p1CoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getTotalP2Coverage':
            $sql = "select * from $tableName where id in(select max(id) from $tableName group by projectName) order by p2CoveragePerc desc;";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['projectName'];
                $jsonArrayItem['value'] = $row['p2CoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getTotalAutomationCoverage':
            $sql = "select * from $tableName where id in(select max(id) from $tableName group by projectName) order by automationCoveragePerc desc;";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['projectName'];
                $jsonArrayItem['value'] = $row['automationCoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getTestcaseCountDistribution':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select * from $tableName where id in(select max(id) from $tableName group by projectName) and createdAt>=DATE_SUB((select max(createdAt) from $tableName) , INTERVAL " . $_GET['arguments'][1] . " DAY) order by totalCases desc;";
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $jsonArraySubSet4 = array();
            $jsonArraySubSet5 = array();
            foreach ($dbo->query($sql) as $row)
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
        break;
        case 'getCoverageNumbers_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select * from $tableName where id = (select max(id) from $tableName where projectName='" . $_GET['arguments'][1] . "');";
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem["totalCoverage"] = $row['automationCoveragePerc'];
                $jsonArrayItem["P0Coverage"] = $row['p0CoveragePerc'];
                $jsonArrayItem["P1Coverage"] = $row['p1CoveragePerc'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getTestCasesBreakdown_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select * from $tableName where id = (select max(id) from $tableName where projectName='" . $_GET['arguments'][1] . "');";

            $totalCases = 0;
            $jsonArrayInternal = array();

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = "P0 Automation Cases";
                $jsonArrayItem['value'] = $row['p0Cases'];
                array_push($jsonArrayInternal, $jsonArrayItem);
                $jsonArrayItem['label'] = "P1 Automation Cases";
                $jsonArrayItem['value'] = $row['p1Cases'];
                array_push($jsonArrayInternal, $jsonArrayItem);
                $jsonArrayItem['label'] = "P2 Automation Cases";
                $jsonArrayItem['value'] = $row['p2Cases'];
                array_push($jsonArrayInternal, $jsonArrayItem);
                $otherCases = $row['totalAutomationCases'] - ($row['p0Cases'] + $row['p1Cases'] + $row['p2Cases']);
                $jsonArrayItem['label'] = "Low Priority Automation Cases";
                $jsonArrayItem['value'] = $otherCases;
                array_push($jsonArrayInternal, $jsonArrayItem);
                $onlyManualCases = $row['totalCases'] - $row['totalAutomationCases'];
                $jsonArrayItem['label'] = "Manual Cases";
                $jsonArrayItem['value'] = $onlyManualCases;
                array_push($jsonArrayInternal, $jsonArrayItem);
                $totalCases = $row['totalCases'];
            }
                array_push($jsonArray, array(
                "totalCases" => $totalCases,
                "fullResult" => $jsonArrayInternal
            ));
        break;

        case 'getTestcaseCountTrend_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 3))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
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
            $sql = "SELECT  DATE(createdAt) as createdAt, max(totalCases) as totalCases, max(totalAutomationCases) as totalAutomationCases, max(alreadyAutomated) as alreadyAutomated, max(p0Cases) as p0Cases, max(p1Cases) as p1Cases, max(p2Cases) as p2Cases FROM $tableName WHERE projectName='" . $_GET['arguments'][1] . "' and createdAt>=DATE_SUB((select max(createdAt) from $tableName) , INTERVAL " . $_GET['arguments'][2] . "+1 DAY) GROUP BY DATE(createdAt);";
            $sql = updateGroupBy($sql, $_GET['arguments'][2]);

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem1 = array();
                $jsonArrayItem2 = array();
                $jsonArrayItem3 = array();
                $jsonArrayItem4 = array();
                $jsonArrayItem5 = array();
                $jsonArrayItem6 = array();
                $jsonArrayItem7 = array();

                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['createdAt'];
                array_push($jsonArraySubCategory, $jsonArrayItem);

                $jsonArrayItem1['value'] = $row['totalCases'];
                $jsonArrayItem2['value'] = $row['totalAutomationCases'];
                $jsonArrayItem3['value'] = $row['p0Cases'];
                $jsonArrayItem4['value'] = $row['p1Cases'];
                $jsonArrayItem5['value'] = $row['alreadyAutomated'];
                $otherCases = $row['totalAutomationCases'] - ($row['p0Cases'] + $row['p1Cases']);
                $jsonArrayItem6['value'] = $otherCases;
                $manualCases = $row['totalCases'] - $row['totalAutomationCases'];
                $jsonArrayItem7['value'] = $manualCases;
                array_push($jsonArraySubSet1, $jsonArrayItem1);
                array_push($jsonArraySubSet2, $jsonArrayItem2);
                array_push($jsonArraySubSet3, $jsonArrayItem3);
                array_push($jsonArraySubSet4, $jsonArrayItem4);
                array_push($jsonArraySubSet5, $jsonArrayItem5);
                array_push($jsonArraySubSet6, $jsonArrayItem6);
                array_push($jsonArraySubSet7, $jsonArrayItem7);
            }
            array_push($jsonArrayCategory, array(
                "category" => $jsonArraySubCategory
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Total Testcases",
                "data" => $jsonArraySubSet1
            ));
     
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Only Manual",
                "data" => $jsonArraySubSet7
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
                "data" => $jsonArraySubSet3
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "P1 Cases",
                "data" => $jsonArraySubSet4
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Other Cases",
                "data" => $jsonArraySubSet6
            ));
      
            $jsonArray = array(
                "categories" => $jsonArrayCategory,
                "dataset" => $jsonArrayDataSet
            );
        break;

        case 'getTotalvsAutomatedCount_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 2))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
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

            $sql = "select * from $tableName where id = (select max(id) from $tableName where projectName='" . $_GET['arguments'][1] . "');";

            foreach ($dbo->query($sql) as $row)
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
        break;
    }
    echo json_encode($jsonArray);
}

function updateGroupBy($sql, $filter)
{
    $updatedSql = $sql;
    if($filter == 90)
        $updatedSql = str_replace("GROUP BY DATE", "GROUP BY WEEK", $sql);
    else if($filter == 365)
        $updatedSql = str_replace("GROUP BY DATE", "GROUP BY MONTH", $sql);
    return $updatedSql;
}
?>
