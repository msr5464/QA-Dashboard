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
    $tableName = str_replace(" ", "_", strtolower($_GET['arguments'][0])."_results");

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
        case 'getAvgPercentage':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 4))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select environment,groupName,projectName,round(AVG(percentage),0) as percentage from $tableName where environment=".getMyEnvironment($tableName, $_GET['arguments'][2])." and groupName='" . $_GET['arguments'][3] . "' and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][1] . " DAY) group by projectName order by projectName desc;";
            $environmentValue = "";
            $groupNameValue = "";
            $jsonArraySubSet1 = array();
            foreach ($dbo->query($sql) as $row)
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
        break;

        case 'getAvgExecutionTime':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 4))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select environment,groupName,projectName,round(AVG(TIME_TO_SEC(duration))/60,2) as duration from $tableName where environment=".getMyEnvironment($tableName, $_GET['arguments'][2])." and groupName='" . $_GET['arguments'][3] . "' and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][1] . " DAY) group by projectName order by projectName desc;";
            $environmentValue = "";
            $groupNameValue = "";
            $jsonArraySubSet1 = array();
            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['projectName'];
                $jsonArrayItem['value'] = $row['duration'];
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
        break;

        case 'getLast7Records':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 3))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $counter = 1;
            $sql = "select buildTag,resultsLink,percentage,Date(createdAt) as createdAt from $tableName where projectName='" . $_GET['arguments'][1] . "' order by id desc limit " . $_GET['arguments'][2];

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem['label'] = $row['createdAt'] . "\n" . $row['buildTag']. ", link- " .$row['resultsLink'];
                $jsonArrayItem['value'] = $row['percentage'];
                array_push($jsonArray, $jsonArrayItem);
            }
        break;

        case 'getAvgPercentage_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 4))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $sql = "select environment,round(AVG(percentage),0) as percentage from $tableName where projectName='" . $_GET['arguments'][1] . "' and groupName in (" . $_GET['arguments'][3] . ") and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][2] . " DAY) group by environment;";

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArraySubSet1 = array();
                $jsonArrayItem = array();

                $jsonArrayItem['label'] = "Fail Percentage";
                $jsonArrayItem['value'] = 100 - $row['percentage'];
                array_push($jsonArraySubSet1, $jsonArrayItem);

                $jsonArrayItem['label'] = "Pass Percentage";
                $jsonArrayItem['value'] = $row['percentage'];
                array_push($jsonArraySubSet1, $jsonArrayItem);

                array_push($jsonArray, array(
                    $row['environment'] . "-data" => $jsonArraySubSet1
                ));
            }
        break;

        case 'getDailyAvgPercentage_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 3))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $lastDate = '2010-01-01';
            $lastRegression = 0;
            $lastProduction = 0;
            $lastSanity = 0;
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT  DATE(createdAt) as createdAt, avg(percentage) as percentage, groupName FROM $tableName WHERE projectName='" . $_GET['arguments'][1] . "' and (environment=".getMyEnvironment($tableName, 'environment1')." or environment=".getMyEnvironment($tableName, 'environment2').") and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][2] . " DAY) GROUP BY DATE(createdAt),groupName;";
            $sql = updateGroupBy($sql, $_GET['arguments'][2]);

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem1 = array();
                $jsonArrayItem2 = array();
                $jsonArrayItem3 = array();

                if ($lastDate == $row['createdAt'])
                {
                    if ($row['groupName'] == "regression")
                    {
                        $jsonArrayItem1['value'] = $row['percentage'];
                        $lastRegression = $row['percentage'];
                        array_pop($jsonArraySubSet1);
                        array_push($jsonArraySubSet1, $jsonArrayItem1);
                    }
                    else if ($row['groupName'] == "production")
                    {
                        $jsonArrayItem2['value'] = $row['percentage'];
                        $lastProduction = $row['percentage'];
                        array_pop($jsonArraySubSet2);
                        array_push($jsonArraySubSet2, $jsonArrayItem2);
                    }
                    else if ($row['groupName'] == "sanity")
                    {
                        $jsonArrayItem3['value'] = $row['percentage'];
                        $lastSanity = $row['percentage'];
                        array_pop($jsonArraySubSet3);
                        array_push($jsonArraySubSet3, $jsonArrayItem3);
                    }
                }
                else
                {
                    $lastDate = $row['createdAt'];
                    $jsonArrayItem['label'] = $row['createdAt'];
                    array_push($jsonArraySubCategory, $jsonArrayItem);

                    if ($row['groupName'] == "regression")
                    {
                        $lastRegression = $row['percentage'];
                    }

                    if ($row['groupName'] == "production")
                    {
                        $lastProduction = $row['percentage'];
                    }

                    if ($row['groupName'] == "sanity")
                    {
                        $lastSanity = $row['percentage'];
                    }
                    $jsonArrayItem1['value'] = $lastRegression;
                    $jsonArrayItem2['value'] = $lastProduction;
                    $jsonArrayItem3['value'] = $lastSanity;
                    array_push($jsonArraySubSet1, $jsonArrayItem1);
                    array_push($jsonArraySubSet2, $jsonArrayItem2);
                    array_push($jsonArraySubSet3, $jsonArrayItem3);
                }
            }
            array_push($jsonArrayCategory, array(
                "category" => $jsonArraySubCategory
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Sanity",
                "data" => $jsonArraySubSet3
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Regression",
                "data" => $jsonArraySubSet1
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Production",
                "data" => $jsonArraySubSet2
            ));
            
            $jsonArray = array(
                "categories" => $jsonArrayCategory,
                "dataset" => $jsonArrayDataSet
            );
            break;
        case 'getDailyAvgExecutionTime_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 3))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $lastDate = '2010-01-01';
            $lastRegression = 0;
            $lastProduction = 0;
            $lastSanity = 0;
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT  DATE(createdAt) as createdAt, round(AVG(TIME_TO_SEC(duration))/60,2) as duration, groupName FROM $tableName WHERE projectName='" . $_GET['arguments'][1] . "' and (environment=".getMyEnvironment($tableName, 'environment1')." or environment=".getMyEnvironment($tableName, 'environment2').") and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][2] . " DAY) GROUP BY DATE(createdAt),groupName;";
            $sql = updateGroupBy($sql, $_GET['arguments'][2]);

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem1 = array();
                $jsonArrayItem2 = array();
                $jsonArrayItem3 = array();

                if ($lastDate == $row['createdAt'])
                {
                    if ($row['groupName'] == "regression")
                    {
                        $jsonArrayItem1['value'] = $row['duration'];
                        $lastRegression = $row['duration'];
                        array_pop($jsonArraySubSet1);
                        array_push($jsonArraySubSet1, $jsonArrayItem1);
                    }
                    else if ($row['groupName'] == "production")
                    {
                        $jsonArrayItem2['value'] = $row['duration'];
                        $lastProduction = $row['duration'];
                        array_pop($jsonArraySubSet2);
                        array_push($jsonArraySubSet2, $jsonArrayItem2);
                    }
                    else if ($row['groupName'] == "sanity")
                    {
                        $jsonArrayItem3['value'] = $row['duration'];
                        $lastSanity = $row['duration'];
                        array_pop($jsonArraySubSet3);
                        array_push($jsonArraySubSet3, $jsonArrayItem3);
                    }
                }
                else
                {
                    $lastDate = $row['createdAt'];
                    $jsonArrayItem['label'] = $row['createdAt'];
                    array_push($jsonArraySubCategory, $jsonArrayItem);

                    if ($row['groupName'] == "regression")
                    {
                        $lastRegression = $row['duration'];
                    }

                    if ($row['groupName'] == "production")
                    {
                        $lastProduction = $row['duration'];
                    }

                    if ($row['groupName'] == "sanity")
                    {
                        $lastSanity = $row['duration'];
                    }
                    $jsonArrayItem1['value'] = $lastRegression;
                    $jsonArrayItem2['value'] = $lastProduction;
                    $jsonArrayItem3['value'] = $lastSanity;
                    array_push($jsonArraySubSet1, $jsonArrayItem1);
                    array_push($jsonArraySubSet2, $jsonArrayItem2);
                    array_push($jsonArraySubSet3, $jsonArrayItem3);
                }
            }
            array_push($jsonArrayCategory, array(
                "category" => $jsonArraySubCategory
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Sanity",
                "data" => $jsonArraySubSet3
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Regression",
                "data" => $jsonArraySubSet1
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Production",
                "data" => $jsonArraySubSet2
            ));
            
            $jsonArray = array(
                "categories" => $jsonArrayCategory,
                "dataset" => $jsonArrayDataSet
            );
            break;

        case 'getTotalCasesGroupwise_Project':
            if (!is_array($_GET['arguments']) || (count($_GET['arguments']) < 3))
            {
                $jsonArray['error'] = 'Error in passed arguments!';
            }
            $lastDate = '2010-01-01';
            $lastRegression = 0;
            $lastProduction = 0;
            $lastSanity = 0;
            $jsonArrayCategory = array();
            $jsonArraySubCategory = array();
            $jsonArrayDataSet = array();
            $jsonArraySubSet1 = array();
            $jsonArraySubSet2 = array();
            $jsonArraySubSet3 = array();
            $sql = "SELECT  DATE(createdAt) as createdAt, max(totalCases) as totalCases, groupName FROM $tableName WHERE projectName='" . $_GET['arguments'][1] . "' and (environment=".getMyEnvironment($tableName, 'environment1')." or environment=".getMyEnvironment($tableName, 'environment2').") and createdAt>=DATE_SUB((select max(createdAt) from $tableName), INTERVAL " . $_GET['arguments'][2] . " DAY) GROUP BY DATE(createdAt),groupName;";
            $sql = updateGroupBy($sql, $_GET['arguments'][2]);

            foreach ($dbo->query($sql) as $row)
            {
                $jsonArrayItem = array();
                $jsonArrayItem1 = array();
                $jsonArrayItem2 = array();
                $jsonArrayItem3 = array();

                if ($lastDate == $row['createdAt'])
                {
                    if ($row['groupName'] == "regression")
                    {
                        $jsonArrayItem1['value'] = $row['totalCases'];
                        $lastRegression = $row['totalCases'];
                        array_pop($jsonArraySubSet1);
                        array_push($jsonArraySubSet1, $jsonArrayItem1);
                    }
                    else if ($row['groupName'] == "production")
                    {
                        $jsonArrayItem2['value'] = $row['totalCases'];
                        $lastProduction = $row['totalCases'];
                        array_pop($jsonArraySubSet2);
                        array_push($jsonArraySubSet2, $jsonArrayItem2);
                    }
                    else if ($row['groupName'] == "sanity")
                    {
                        $jsonArrayItem3['value'] = $row['totalCases'];
                        $lastSanity = $row['totalCases'];
                        array_pop($jsonArraySubSet3);
                        array_push($jsonArraySubSet3, $jsonArrayItem3);
                    }
                }
                else
                {
                    $lastDate = $row['createdAt'];
                    $jsonArrayItem['label'] = $row['createdAt'];
                    array_push($jsonArraySubCategory, $jsonArrayItem);

                    if ($row['groupName'] == "regression")
                    {
                        $lastRegression = $row['totalCases'];
                    }

                    if ($row['groupName'] == "production")
                    {
                        $lastProduction = $row['totalCases'];
                    }

                    if ($row['groupName'] == "sanity")
                    {
                        $lastSanity = $row['totalCases'];
                    }
                    $jsonArrayItem1['value'] = $lastRegression;
                    $jsonArrayItem2['value'] = $lastProduction;
                    $jsonArrayItem3['value'] = $lastSanity;
                    array_push($jsonArraySubSet1, $jsonArrayItem1);
                    array_push($jsonArraySubSet2, $jsonArrayItem2);
                    array_push($jsonArraySubSet3, $jsonArrayItem3);
                }
            }
            array_push($jsonArrayCategory, array(
                "category" => $jsonArraySubCategory
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname"=>"Sanity", 
                "data"=>$jsonArraySubSet3)
            );
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Regression",
                "data" => $jsonArraySubSet1
            ));
            array_push($jsonArrayDataSet, array(
                "seriesname" => "Production",
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

    function getMyEnvironment($tableName, $envCount)
    {
        $updatedSql = "(Select " . $envCount . " from vertical where tableName='".str_replace("_results", "", $tableName)."')";
        return $updatedSql;
    }

?>


