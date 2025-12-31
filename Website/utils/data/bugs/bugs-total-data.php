<?php
header('Content-type: application/json');
require("../../config.php");

function getTableName($tableNamePrefix)
{
    return str_replace(" ", "_", strtolower($tableNamePrefix) . "_jira_bugs");
}

function getTotalBugsWhereClause()
{
    return "classification = 'PaymentGateway'";
}

function getJiraTableName($tableNamePrefix)
{
    return str_replace(" ", "_", strtolower($tableNamePrefix) . "_jira_tickets");
}

function getProjectNames($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();
    $sql = "Select distinct projectName from " . getTableName($tableNamePrefix) . " WHERE " . getTotalBugsWhereClause() . " order by projectName desc;";

    foreach ($DB->query($sql) as $row) {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getTotalTicketsTested($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();
    $whereClause = buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive, array(), 'createdAt');
    $sql = "SELECT count(*) as totalTicketsTested FROM " . getJiraTableName($tableNamePrefix) . " WHERE " . $whereClause . ";";

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem['totalTicketsTested'] = is_null($row['totalTicketsTested']) ? '0' : $row['totalTicketsTested'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getTotalBugsCount($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();

    $whereClause = buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive, array('isInvalid=0', getTotalBugsWhereClause()), 'createdAt');
    $sql = "select count(*) as bugs, bugCategory as category from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by bugCategory;";

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        if ($row['category'] == 'FCT')
            $jsonArrayItem['totalFctBugs'] = $row['bugs'];
        else if ($row['category'] == 'PRD')
            $jsonArrayItem['totalPrdBugs'] = $row['bugs'];
        else if ($row['category'] == 'STG')
            $jsonArrayItem['totalStgBugs'] = $row['bugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugCountAndPercentageData($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive, $category)
{
    global $DB;
    $projectNameReference = $isVerticalDataActive ? "verticalName" : "projectName";
    $whereClauseBugs = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', "bugCategory='" . $category . "'", getTotalBugsWhereClause()), 'createdAt');
    $whereClauseTickets = buildCommonWhereClause($startDate, $endDate, false, array(), 'createdAt');
    $sql = "SELECT " . $projectNameReference . ", priority, SUM(bugs) AS bugs, SUM(ticketsTested) AS ticketsTested FROM (SELECT " . $projectNameReference . ", priority, COUNT(*) AS bugs, 0 AS ticketsTested FROM " . getTableName($tableNamePrefix) . " WHERE " . $whereClauseBugs . " GROUP BY " . $projectNameReference . ", priority UNION ALL SELECT " . $projectNameReference . ", priority, 0 AS bugs, COUNT(*) AS ticketsTested FROM " . getJiraTableName($tableNamePrefix) . " WHERE " . $whereClauseTickets . " GROUP BY " . $projectNameReference . ", priority) AS combined GROUP BY " . $projectNameReference . ", priority ORDER BY " . $projectNameReference . " ASC;";

    foreach ($DB->query($sql) as $row) {
        $mysqlData[] = $row;
    }

    $data = array(
        "categories" => array(),
        "dataset1" => array(),
        "dataset2" => array(),
        "totalTicketsTested_sum" => 0,
        "trendLineCountAvg" => 0,
        "trendLinePercAvg" => 0
    );

    // Build the "categories" section (already sorted by SQL query)
    $categories = array();
    $projectNames = array();
    foreach ($mysqlData as $row) {
        $category = $row[$projectNameReference];
        if (!in_array($category, $categories)) {
            $categories[] = $category;
            array_push($projectNames, array("label" => $category));
        }
    }
    $data["categories"][] = array("category" => $projectNames);

    // Build the "dataset1" section
    $priorityBugs = array(
        "P0" => array("seriesname" => "P0 Bugs", "data" => array()),
        "P1" => array("seriesname" => "P1 Bugs", "data" => array()),
        "P2" => array("seriesname" => "P2 Bugs", "data" => array()),
        "P3" => array("seriesname" => "P3 Bugs", "data" => array())
    );

    $counter = 0;
    $trendLineCountAvg = 0;
    $trendLinePercAvg = 0;
    $totalBugsFound = array();
    $totalTicketsTested = array();
    $totalBugRatio = array();
    $bugsFound_sum = 0;
    $ticketsTested_sum = 0;
    foreach ($categories as $category) {

        $increasedP0Bugs = 0;
        $increasedP1Bugs = 0;
        $increasedP2Bugs = 0;
        $increasedP3Bugs = 0;
        $bugsFound = 0;
        $ticketsTested = 0;
        $bugsRatio = 0;
        foreach ($mysqlData as $singleRow) {
            if ($singleRow[$projectNameReference] == $category) {
                $bugsFound += $singleRow["bugs"];
                $ticketsTested += $singleRow["ticketsTested"];
                switch ($singleRow['priority']) {
                    case "P0 +":
                    case "P0-Critical":
                        $increasedP0Bugs = $increasedP0Bugs + $singleRow["bugs"];
                        break;
                    case "P1-High":
                        $increasedP1Bugs = $increasedP1Bugs + $singleRow["bugs"];
                        break;
                    case "P2-Medium":
                        $increasedP2Bugs = $increasedP2Bugs + $singleRow["bugs"];
                        break;
                    case "P3-Low":
                        $increasedP3Bugs = $increasedP3Bugs + $singleRow["bugs"];
                        break;
                }
            }
        }
        $priorityBugs["P0"]["data"][] = array("value" => $increasedP0Bugs);
        $priorityBugs["P1"]["data"][] = array("value" => $increasedP1Bugs);
        $priorityBugs["P2"]["data"][] = array("value" => $increasedP2Bugs);
        $priorityBugs["P3"]["data"][] = array("value" => $increasedP3Bugs);

        array_push($totalBugsFound, array("value" => $bugsFound));
        array_push($totalTicketsTested, array("value" => $ticketsTested));

        $bugsFound_sum += $bugsFound;
        $ticketsTested_sum += $ticketsTested;
        if ($ticketsTested > 0)
            $bugsRatio = round(($bugsFound / $ticketsTested), 2);
        else
            $bugsRatio = 0;
        array_push($totalBugRatio, array("value" => $bugsRatio));
        $counter++;

    }
    $data["dataset1"] = array_values($priorityBugs);

    // Build the "dataset2" section
    $data["dataset2"][] = array("seriesname" => "Bug Ratio", "data" => $totalBugRatio);
    $data["dataset2"][] = array("seriesname" => "Tickets Tested", "visible" => "0", "data" => $totalTicketsTested);
    $data["dataset2"][] = array("seriesname" => "Bugs Found", "visible" => "0", "data" => $totalBugsFound);

    // Calculate additional metrics
    if ($counter > 0) {
        $trendLineCountAvg = round($bugsFound_sum / $counter, 1);

        if ($ticketsTested_sum > 0)
            $trendLinePercAvg = round(($bugsFound_sum / $ticketsTested_sum), 2);
        else
            $trendLinePercAvg = 0;
    }
    $data["trendLineCountAvg"] = $trendLineCountAvg;
    $data["trendLinePercAvg"] = $trendLinePercAvg;
    $data["totalTicketsTested_sum"] = $ticketsTested_sum;
    return $data;
}

function getTotalTicketsTested_Project($tableNamePrefix, $projectName, $startDate, $endDate)
{
    global $DB;
    $jsonArray = array();
    $projectNameReference = getProjectNameReference($projectName);

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " in (" . $projectName . ")"), 'createdAt');
    $sql = "SELECT count(*) as totalTicketsTested FROM " . getJiraTableName($tableNamePrefix) . " WHERE " . $whereClause;

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem['totalTicketsTested'] = is_null($row['totalTicketsTested']) ? '0' : $row['totalTicketsTested'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getTotalBugsCount_Project($tableNamePrefix, $projectName, $startDate, $endDate)
{
    global $DB;
    $jsonArray = array();
    $projectNameReference = getProjectNameReference($projectName);

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " in (" . $projectName . ")", getTotalBugsWhereClause()), 'createdAt');
    $sql = "select count(*) as bugs, bugCategory as category from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by bugCategory;";

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        if ($row['category'] == 'FCT')
            $jsonArrayItem['totalFctBugs'] = $row['bugs'];
        else if ($row['category'] == 'PRD')
            $jsonArrayItem['totalPrdBugs'] = $row['bugs'];
        else if ($row['category'] == 'STG')
            $jsonArrayItem['totalStgBugs'] = $row['bugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugsData_Project($tableNamePrefix, $startDate, $endDate, $projectName, $filterName, $category)
{
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayP0BugCount = array();
    $jsonArrayP1BugCount = array();
    $jsonArrayP2BugCount = array();
    $jsonArrayP3BugCount = array();
    $increasedP0Bugs = 0;
    $increasedP1Bugs = 0;
    $increasedP2Bugs = 0;
    $increasedP3Bugs = 0;
    $previousValue = "";
    $projectNameReference = getProjectNameReference($projectName);

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', "bugCategory in ('" . str_replace(",", "','", $category) . "')", $projectNameReference . " in (" . $projectName . ")", getTotalBugsWhereClause()), 'createdAt');
    $sql = "select count(*) as bugs,priority," . $filterName . " from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by priority," . $filterName . " order by " . $filterName . ",priority;";
    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $increasedP0Bugs = 0;
        $increasedP1Bugs = 0;
        $increasedP2Bugs = 0;
        $increasedP3Bugs = 0;

        if ($previousValue != $row[$filterName]) {
            $previousValue = $row[$filterName];
            foreach ($rows as $singleRow) {
                if ($row[$filterName] == $singleRow[$filterName]) {
                    switch ($singleRow['priority']) {
                        case "P0 +":
                        case "P0-Critical":
                            $increasedP0Bugs = $increasedP0Bugs + $singleRow['bugs'];
                            break;
                        case "P1-High":
                            $increasedP1Bugs = $increasedP1Bugs + $singleRow['bugs'];
                            break;
                        case "P2-Medium":
                            $increasedP2Bugs = $increasedP2Bugs + $singleRow['bugs'];
                            break;
                        default:
                            $increasedP3Bugs = $increasedP3Bugs + $singleRow['bugs'];
                    }
                }
            }

            $jsonArrayItem['label'] = $row[$filterName];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if ($increasedP0Bugs == 0)
                $jsonArrayItem1['value'] = "";
            else
                $jsonArrayItem1['value'] = $increasedP0Bugs;
            array_push($jsonArrayP0BugCount, $jsonArrayItem1);

            if ($increasedP1Bugs == 0)
                $jsonArrayItem2['value'] = "";
            else
                $jsonArrayItem2['value'] = $increasedP1Bugs;
            array_push($jsonArrayP1BugCount, $jsonArrayItem2);

            if ($increasedP2Bugs == 0)
                $jsonArrayItem3['value'] = "";
            else
                $jsonArrayItem3['value'] = $increasedP2Bugs;
            array_push($jsonArrayP2BugCount, $jsonArrayItem3);

            if ($increasedP3Bugs == 0)
                $jsonArrayItem4['value'] = "";
            else
                $jsonArrayItem4['value'] = $increasedP3Bugs;
            array_push($jsonArrayP3BugCount, $jsonArrayItem4);
        }
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P0 Bugs",
        "data" => $jsonArrayP0BugCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P1 Bugs",
        "data" => $jsonArrayP1BugCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P2 Bugs",
        "data" => $jsonArrayP2BugCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P3 Bugs",
        "data" => $jsonArrayP3BugCount
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getIssuesList_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $jsonArray = array();
    $projectNameReference = getProjectNameReference($projectName);

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', "bugCategory in ('" . str_replace(",", "','", $category) . "')", $projectNameReference . " in (" . $projectName . ")", getTotalBugsWhereClause()), 'createdAt');
    $sql = "select issueId,DATE_FORMAT(createdAt,'%d %b %Y'),title,priority,teamName,productArea,bugType,rootCause,status from " . getTableName($tableNamePrefix) . " where " . $whereClause . " order by priority,issueId desc, bugCategory, Date(createdAt) desc;";
    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();
    return $rows;
}

function getProdBugLeakage_Project($tableNamePrefix, $projectName, $startDate, $endDate)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);
    $projData = $projectNameReference;
    if (strpos($projectName, "Vertical") !== false) {
        $projData = "projectName";
        if (substr_count($projectName, 'Vertical') >= 2)
            $projData = "verticalName";
    }

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " in (" . $projectName . ")"), 'createdAt');
    $sql = "select " . $projData . " as pname,count(*) as bugs,bugCategory as category,priority from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by " . $projData . ",priority,bugCategory order by " . $projData . ",bugCategory,priority;";
    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    // Find all unique project names
    $projectNames = array();
    foreach ($rows as $row) {
        if (!in_array($row['pname'], $projectNames)) {
            $projectNames[] = $row['pname'];
        }
    }
    if (empty($projectNames)) {
        $projectNames = $projectNamesArray;
    }

    // Aggregate bug counts by project, environment, and priority
    $priorityMap = [
        'P0' => ['P0 +', 'P0-Critical'],
        'P1' => ['P1-High'],
        'P2' => ['P2-Medium'],
        'P3' => ['P3-Low']
    ];
    $bugCounts = array();
    foreach ($projectNames as $pname) {
        foreach (['STG', 'FCT', 'PRD'] as $env) {
            foreach (array_keys($priorityMap) as $pKey) {
                $bugCounts[$pname][$env][$pKey] = 0;
            }
        }
    }
    foreach ($rows as $row) {
        $pname = $row['pname'];
        $env = $row['category'];
        $priority = $row['priority'];
        foreach ($priorityMap as $pKey => $labels) {
            if (in_array($priority, $labels)) {
                $bugCounts[$pname][$env][$pKey] += intval($row['bugs']);
            }
        }
    }

    // Build FusionCharts-compatible structure: categories = project names
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    foreach ($projectNames as $pname) {
        $jsonArraySubCategory[] = array('label' => $pname);
    }
    $jsonArrayCategory[] = array('category' => $jsonArraySubCategory);

    $jsonArrayDataSet1 = array();
    // 1. P0 Bug Leakage %
    $dataP0 = array();
    // 2. Upto P1 Bug Leakage %
    $dataUptoP1 = array();
    // 3. Upto P2 Bug Leakage %
    $dataUptoP2 = array();
    // 4. Upto P3 Bug Leakage %
    $dataUptoP3 = array();
    foreach ($projectNames as $pname) {
        // P0
        $prdBugsP0 = $bugCounts[$pname]['PRD']['P0'] ?? 0;
        $totalBugsP0 = $prdBugsP0
            + ($bugCounts[$pname]['FCT']['P0'] ?? 0)
            + ($bugCounts[$pname]['STG']['P0'] ?? 0);
        $leakageP0 = ($totalBugsP0 > 0) ? round(($prdBugsP0 / $totalBugsP0) * 100, 2) : 0;
        $dataP0[] = array('value' => $leakageP0);
        // Upto P1
        $prdBugsUptoP1 = $prdBugsP0 + ($bugCounts[$pname]['PRD']['P1'] ?? 0);
        $totalBugsUptoP1 = $totalBugsP0 + ($bugCounts[$pname]['PRD']['P1'] ?? 0)
            + ($bugCounts[$pname]['FCT']['P1'] ?? 0)
            + ($bugCounts[$pname]['STG']['P1'] ?? 0);
        $leakageUptoP1 = ($totalBugsUptoP1 > 0) ? round(($prdBugsUptoP1 / $totalBugsUptoP1) * 100, 2) : 0;
        $dataUptoP1[] = array('value' => $leakageUptoP1);
        // Upto P2
        $prdBugsUptoP2 = $prdBugsUptoP1 + ($bugCounts[$pname]['PRD']['P2'] ?? 0);
        $totalBugsUptoP2 = $totalBugsUptoP1 + ($bugCounts[$pname]['PRD']['P2'] ?? 0)
            + ($bugCounts[$pname]['FCT']['P2'] ?? 0)
            + ($bugCounts[$pname]['STG']['P2'] ?? 0);
        $leakageUptoP2 = ($totalBugsUptoP2 > 0) ? round(($prdBugsUptoP2 / $totalBugsUptoP2) * 100, 2) : 0;
        $dataUptoP2[] = array('value' => $leakageUptoP2);
        // Upto P3
        $prdBugsUptoP3 = $prdBugsUptoP2 + ($bugCounts[$pname]['PRD']['P3'] ?? 0);
        $totalBugsUptoP3 = $totalBugsUptoP2 + ($bugCounts[$pname]['PRD']['P3'] ?? 0)
            + ($bugCounts[$pname]['FCT']['P3'] ?? 0)
            + ($bugCounts[$pname]['STG']['P3'] ?? 0);
        $leakageUptoP3 = ($totalBugsUptoP3 > 0) ? round(($prdBugsUptoP3 / $totalBugsUptoP3) * 100, 2) : 0;
        $dataUptoP3[] = array('value' => $leakageUptoP3);
    }
    $jsonArrayDataSet1[] = array(
        'seriesname' => 'Upto P0',
        'data' => $dataP0,
        'visible' => '0'
    );
    $jsonArrayDataSet1[] = array(
        'seriesname' => 'Upto P1',
        'data' => $dataUptoP1,
        'visible' => '0'
    );
    $jsonArrayDataSet1[] = array(
        'seriesname' => 'Upto P2',
        'data' => $dataUptoP2,
        'visible' => '0'
    );
    $jsonArrayDataSet1[] = array(
        'seriesname' => 'Upto P3',
        'data' => $dataUptoP3,
        'visible' => '1'
    );
    $jsonArray = array(
        'categories' => $jsonArrayCategory,
        'dataset' => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getBugCountTrendByPriority_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();
    $jsonArraySubSetTotal = array();
    $jsonArraySubSetBugScore = array();

    // Get all data first and organize by date
    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " IN (" . $projectName . ")", "bugCategory IN ('" . str_replace(",", "','", $category) . "')", getTotalBugsWhereClause()), 'createdAt');
    $sql = "SELECT DATE(createdAt) AS createdAt, COUNT(*) AS bugCount, priority
            FROM " . getTableName($tableNamePrefix) . "
            WHERE " . $whereClause . "
            GROUP BY DATE(createdAt), priority 
            ORDER BY DATE(createdAt), priority;";

    $sql = updateGroupBy($sql, $startDate, $endDate);

    // Store data by date and priority
    $dateData = array();
    foreach ($DB->query($sql) as $row) {
        $date = $row['createdAt'];
        $priority = $row['priority'];
        $bugCount = $row['bugCount'];

        if (!isset($dateData[$date])) {
            $dateData[$date] = array(
                'P0-Critical' => 0,
                'P1-High' => 0,
                'P2-Medium' => 0,
                'P3-Low' => 0
            );
        }

        // Map priority names to standard format
        if ($priority == 'P0 +' || $priority == 'P0-Critical') {
            $dateData[$date]['P0-Critical'] = $bugCount;
        } elseif ($priority == 'P1-High') {
            $dateData[$date]['P1-High'] = $bugCount;
        } elseif ($priority == 'P2-Medium') {
            $dateData[$date]['P2-Medium'] = $bugCount;
        } elseif ($priority == 'P3-Low') {
            $dateData[$date]['P3-Low'] = $bugCount;
        }
    }

    // Process each date
    foreach ($dateData as $date => $priorities) {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $date;
        array_push($jsonArraySubCategory, $jsonArrayItem);

        $p0Count = $priorities['P0-Critical'];
        $p1Count = $priorities['P1-High'];
        $p2Count = $priorities['P2-Medium'];
        $p3Count = $priorities['P3-Low'];
        $totalCount = $p0Count + $p1Count + $p2Count + $p3Count;

        // Calculate Bug Score: (12 * P0) + (6 * P1) + (2 * P2) + (1 * P3)
        $bugScore = (12 * $p0Count) + (6 * $p1Count) + (2 * $p2Count) + (1 * $p3Count);

        // P0-Critical
        $jsonArrayItem1 = array();
        $jsonArrayItem1['value'] = $p0Count;
        array_push($jsonArraySubSet1, $jsonArrayItem1);

        // P1-High
        $jsonArrayItem2 = array();
        $jsonArrayItem2['value'] = $p1Count;
        array_push($jsonArraySubSet2, $jsonArrayItem2);

        // P2-Medium
        $jsonArrayItem3 = array();
        $jsonArrayItem3['value'] = $p2Count;
        array_push($jsonArraySubSet3, $jsonArrayItem3);

        // P3-Low
        $jsonArrayItem4 = array();
        $jsonArrayItem4['value'] = $p3Count;
        array_push($jsonArraySubSet4, $jsonArrayItem4);

        // Total
        $jsonArrayItemTotal = array();
        $jsonArrayItemTotal['value'] = $totalCount;
        array_push($jsonArraySubSetTotal, $jsonArrayItemTotal);

        // Bug Score
        $jsonArrayItemBugScore = array();
        $jsonArrayItemBugScore['value'] = $bugScore;
        array_push($jsonArraySubSetBugScore, $jsonArrayItemBugScore);
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Total",
            "data" => $jsonArraySubSetTotal
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Bug Score",
            "data" => $jsonArraySubSetBugScore,
            "visible" => "0"
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P0-Critical",
            "visible" => "0",
            "data" => $jsonArraySubSet1
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P1-High",
            "visible" => "0",
            "data" => $jsonArraySubSet2
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P2-Medium",
            "visible" => "0",
            "data" => $jsonArraySubSet3
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P3-Low",
            "visible" => "0",
            "data" => $jsonArraySubSet4
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getProdBugLeakageTrend_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $lastDate = '2010-01-01';
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArraySubSet1 = array();
    $jsonArraySubSet2 = array();
    $jsonArraySubSet3 = array();
    $jsonArraySubSet4 = array();

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " IN (" . $projectName . ")", getTotalBugsWhereClause()), 'createdAt');
    $sql = "SELECT DATE(createdAt) AS createdAt, priority, bugCategory as category, COUNT(*) AS bugCount
            FROM " . getTableName($tableNamePrefix) . "
            WHERE " . $whereClause . "
            GROUP BY DATE(createdAt), priority, bugCategory 
            ORDER BY DATE(createdAt), priority, category;";

    $sql = updateGroupBy($sql, $startDate, $endDate);

    // Get all data first
    $allData = array();
    foreach ($DB->query($sql) as $row) {
        $date = $row['createdAt'];
        $priority = $row['priority'];
        $category = $row['category'];
        $bugCount = $row['bugCount'];

        if (!isset($allData[$date])) {
            $allData[$date] = array();
        }
        if (!isset($allData[$date][$priority])) {
            $allData[$date][$priority] = array('STG' => 0, 'FCT' => 0, 'PRD' => 0);
        }
        $allData[$date][$priority][$category] = $bugCount;
    }

    // Calculate leakage percentages for each date
    $priorityMap = [
        'P0' => ['P0 +', 'P0-Critical'],
        'P1' => ['P1-High'],
        'P2' => ['P2-Medium'],
        'P3' => ['P3-Low']
    ];

    foreach ($allData as $date => $priorities) {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $date;
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Initialize values for this date
        $leakageP0 = 0;
        $leakageP1 = 0;
        $leakageP2 = 0;
        $leakageP3 = 0;

        // Calculate P0 leakage
        $prdBugsP0 = 0;
        $totalBugsP0 = 0;
        foreach ($priorityMap['P0'] as $pLabel) {
            if (isset($priorities[$pLabel])) {
                $prdBugsP0 += $priorities[$pLabel]['PRD'];
                $totalBugsP0 += $priorities[$pLabel]['PRD'] + $priorities[$pLabel]['FCT'] + $priorities[$pLabel]['STG'];
            }
        }
        $leakageP0 = ($totalBugsP0 > 0) ? round(($prdBugsP0 / $totalBugsP0) * 100, 2) : 0;

        // Calculate P1 leakage (upto P1)
        $prdBugsUptoP1 = $prdBugsP0;
        $totalBugsUptoP1 = $totalBugsP0;
        foreach ($priorityMap['P1'] as $pLabel) {
            if (isset($priorities[$pLabel])) {
                $prdBugsUptoP1 += $priorities[$pLabel]['PRD'];
                $totalBugsUptoP1 += $priorities[$pLabel]['PRD'] + $priorities[$pLabel]['FCT'] + $priorities[$pLabel]['STG'];
            }
        }
        $leakageP1 = ($totalBugsUptoP1 > 0) ? round(($prdBugsUptoP1 / $totalBugsUptoP1) * 100, 2) : 0;

        // Calculate P2 leakage (upto P2)
        $prdBugsUptoP2 = $prdBugsUptoP1;
        $totalBugsUptoP2 = $totalBugsUptoP1;
        foreach ($priorityMap['P2'] as $pLabel) {
            if (isset($priorities[$pLabel])) {
                $prdBugsUptoP2 += $priorities[$pLabel]['PRD'];
                $totalBugsUptoP2 += $priorities[$pLabel]['PRD'] + $priorities[$pLabel]['FCT'] + $priorities[$pLabel]['STG'];
            }
        }
        $leakageP2 = ($totalBugsUptoP2 > 0) ? round(($prdBugsUptoP2 / $totalBugsUptoP2) * 100, 2) : 0;

        // Calculate P3 leakage (upto P3)
        $prdBugsUptoP3 = $prdBugsUptoP2;
        $totalBugsUptoP3 = $totalBugsUptoP2;
        foreach ($priorityMap['P3'] as $pLabel) {
            if (isset($priorities[$pLabel])) {
                $prdBugsUptoP3 += $priorities[$pLabel]['PRD'];
                $totalBugsUptoP3 += $priorities[$pLabel]['PRD'] + $priorities[$pLabel]['FCT'] + $priorities[$pLabel]['STG'];
            }
        }
        $leakageP3 = ($totalBugsUptoP3 > 0) ? round(($prdBugsUptoP3 / $totalBugsUptoP3) * 100, 2) : 0;

        array_push($jsonArraySubSet1, array('value' => $leakageP0));
        array_push($jsonArraySubSet2, array('value' => $leakageP1));
        array_push($jsonArraySubSet3, array('value' => $leakageP2));
        array_push($jsonArraySubSet4, array('value' => $leakageP3));
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Upto P0",
            "visible" => "0",
            "data" => $jsonArraySubSet1
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Upto P1",
            "visible" => "0",
            "data" => $jsonArraySubSet2
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Upto P2",
            "visible" => "0",
            "data" => $jsonArraySubSet3
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Upto P3",
            "visible" => "1",
            "data" => $jsonArraySubSet4
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

function getBugRatioTrend_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $lastDate = '2010-01-01';
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet = array();
    $jsonArrayBugRatio = array();
    $jsonArrayTicketsTested = array();
    $jsonArrayBugsFound = array();

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " IN (" . $projectName . ")", "bugCategory IN ('" . str_replace(",", "','", $category) . "')"), 'createdAt');
    $sql = "SELECT DATE(createdAt) AS createdAt, priority, bugCategory as category, COUNT(*) AS bugCount
            FROM " . getTableName($tableNamePrefix) . "
            WHERE " . $whereClause . "
            GROUP BY DATE(createdAt), priority, bugCategory 
            ORDER BY DATE(createdAt), priority, category;";
    $sql = updateGroupBy($sql, $startDate, $endDate);
    // Get all data first
    $allData = array();
    foreach ($DB->query($sql) as $row) {
        $date = $row['createdAt'];
        $priority = $row['priority'];
        $category = $row['category'];
        $bugCount = $row['bugCount'];

        if (!isset($allData[$date])) {
            $allData[$date] = array();
        }
        if (!isset($allData[$date][$priority])) {
            $allData[$date][$priority] = array('STG' => 0, 'FCT' => 0, 'PRD' => 0);
        }
        $allData[$date][$priority][$category] = $bugCount;
    }

    // Calculate bug ratios for each date

    foreach ($allData as $date => $priorities) {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $date;
        array_push($jsonArraySubCategory, $jsonArrayItem);

        // Calculate total bugs found for this date
        $totalBugsFound = 0;
        foreach ($priorities as $priority => $categories) {
            foreach ($categories as $catType => $count) {
                if ($catType == 'STG' || $catType == 'FCT' || $catType == 'PRD') {
                    $totalBugsFound += $count;
                }
            }
        }

        // Get tickets tested for this date
        $totalTicketsTested = 0;

        // For tickets tested, we need to get from jira table
        // Get all tickets data first and find the matching date
        $whereClause = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " IN (" . $projectName . ")"), 'createdAt');
        $ticketsSql = "SELECT DATE(createdAt) AS createdAt, COUNT(*) as ticketsTested 
                       FROM " . getJiraTableName($tableNamePrefix) . " 
                       WHERE " . $whereClause . "
                       GROUP BY DATE(createdAt)";
        $ticketsSql = updateGroupBy($ticketsSql, $startDate, $endDate);

        // Get all tickets data and store in array
        $ticketsData = array();
        $ticketsResult = $DB->query($ticketsSql);
        foreach ($ticketsResult as $ticketRow) {
            $ticketsData[$ticketRow['createdAt']] = $ticketRow['ticketsTested'];
        }

        // Get tickets for the specific date
        $totalTicketsTested = isset($ticketsData[$date]) ? $ticketsData[$date] : 0;

        // Calculate bug ratio
        $bugRatio = ($totalTicketsTested > 0) ? round(($totalBugsFound / $totalTicketsTested), 2) : 0;

        array_push($jsonArrayBugRatio, array('value' => $bugRatio));
        array_push($jsonArrayTicketsTested, array('value' => $totalTicketsTested));
        array_push($jsonArrayBugsFound, array('value' => $totalBugsFound));
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Bug Ratio",
            "data" => $jsonArrayBugRatio
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Tickets Tested",
            "visible" => "0",
            "data" => $jsonArrayTicketsTested
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "Bugs Found",
            "visible" => "0",
            "data" => $jsonArrayBugsFound
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet
    );
    return $jsonArray;
}

/**
 * Calculate Quality Score from bug counts and story points
 * Quality Score = (Bug Score * 100) / Story Points
 * Bug Score = P0*12 + P1*6 + P2*2 + P3*1
 */
function calculateQualityScore($priorities, $storyPoints)
{
    $bugScore = ($priorities['P0'] * 12) + 
                    ($priorities['P1'] * 6) + 
                    ($priorities['P2'] * 2) + 
                    ($priorities['P3'] * 1);
    
    $qualityScore = 0;
    if ($storyPoints > 0) {
        $qualityScore = round(($bugScore * 100) / $storyPoints, 2);
    }
    
    return array(
        'qualityScore' => $qualityScore,
        'bugScore' => $bugScore
    );
}

/**
 * Helper to normalize priority names
 */
function normalizePriority($priority)
{
    switch ($priority) {
        case 'P0 +':
        case 'P0-Critical':
            return 'P0';
        case 'P1-High':
            return 'P1';
        case 'P2-Medium':
            return 'P2';
        case 'P3-Low':
        default:
            return 'P3';
    }
}

/**
 * Get Quality Score for all projects with category filter
 */
function getQualityScore_All($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive, $category)
{
    global $DB;
    $projectNameReference = $isVerticalDataActive ? "verticalName" : "projectName";
    
    $whereClauseBugs = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', "bugCategory='" . $category . "'", getTotalBugsWhereClause()), 'createdAt');
    $whereClauseTickets = buildCommonWhereClause($startDate, $endDate, false, array(), 'createdAt');
    
    $sql = "SELECT " . $projectNameReference . ", priority, 
                   SUM(bugs) AS bugs, 
                   SUM(storyPointsTested) AS storyPointsTested 
            FROM (
                SELECT " . $projectNameReference . ", priority, 
                       COUNT(*) AS bugs, 
                       0 AS storyPointsTested 
                FROM " . getTableName($tableNamePrefix) . " 
                WHERE " . $whereClauseBugs . "
                GROUP BY " . $projectNameReference . ", priority 
                
                UNION ALL 
                
                SELECT " . $projectNameReference . ", priority, 
                       0 AS bugs, 
                       SUM(storyPoints) AS storyPointsTested 
                FROM " . getJiraTableName($tableNamePrefix) . " 
                WHERE " . $whereClauseTickets . "
                GROUP BY " . $projectNameReference . ", priority
            ) AS combined 
            GROUP BY " . $projectNameReference . ", priority 
            ORDER BY " . $projectNameReference . " ASC;";

    $mysqlData = array();
    foreach ($DB->query($sql) as $row) {
        $mysqlData[] = $row;
    }

    // Build categories (already sorted by SQL query)
    $categories = array();
    $projectNames = array();
    foreach ($mysqlData as $row) {
        $category = $row[$projectNameReference];
        if (!in_array($category, $categories)) {
            $categories[] = $category;
            array_push($projectNames, array("label" => $category));
        }
    }

    $jsonArrayQualityScore = array();
    $jsonArrayBugWeightage = array();
    $jsonArrayStoryPoints = array();
    
    // Track totals for overall Quality Score calculation
    $totalBugScore = 0;
    $totalStoryPoints = 0;

    foreach ($categories as $project) {
        $priorities = array('P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0);
        $storyPoints = 0;

        foreach ($mysqlData as $row) {
            if ($row[$projectNameReference] == $project) {
                $normalizedPriority = normalizePriority($row['priority']);
                $priorities[$normalizedPriority] += $row['bugs'];
                $storyPoints += $row['storyPointsTested'];
            }
        }

        $result = calculateQualityScore($priorities, $storyPoints);
        
        array_push($jsonArrayQualityScore, array('value' => $result['qualityScore']));
        array_push($jsonArrayBugWeightage, array('value' => $result['bugScore']));
        array_push($jsonArrayStoryPoints, array('value' => $storyPoints));
        
        // Accumulate totals for overall calculation
        $totalBugScore += $result['bugScore'];
        $totalStoryPoints += $storyPoints;
    }
    
    // Calculate trendline average as: Total Bug Score * 100 / Total Story Points
    $trendLineQualityScoreAvg = 0;
    if ($totalStoryPoints > 0) {
        $trendLineQualityScoreAvg = round(($totalBugScore * 100) / $totalStoryPoints, 1);
    }

    return array(
        'categories' => array(array('category' => $projectNames)),
        'dataset' => array(
            array('seriesname' => 'Bug Score', 'visible' => '0', 'data' => $jsonArrayBugWeightage),
            array('seriesname' => 'Quality Score', 'data' => $jsonArrayQualityScore),
            array('seriesname' => 'Story Points', 'visible' => '0', 'data' => $jsonArrayStoryPoints)
        ),
        'trendLineQualityScoreAvg' => $trendLineQualityScoreAvg
    );
}

/**
 * Get Quality Score for specific project
 */
function getQualityScore_Project($tableNamePrefix, $projectName, $startDate, $endDate, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);
    
    $whereClauseBugs = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " IN (" . $projectName . ")", "bugCategory IN ('" . str_replace(",", "','", $category) . "')", getTotalBugsWhereClause()), 'createdAt');
    $whereClauseTickets = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " IN (" . $projectName . ")"), 'createdAt');
    
    $bugsSql = "SELECT priority, COUNT(*) as bugCount
                FROM " . getTableName($tableNamePrefix) . "
                WHERE " . $whereClauseBugs . "
                GROUP BY priority";
    
    $spSql = "SELECT SUM(storyPoints) as totalStoryPoints
              FROM " . getJiraTableName($tableNamePrefix) . "
              WHERE " . $whereClauseTickets;
    
    $priorities = array('P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0);
    foreach ($DB->query($bugsSql) as $row) {
        $normalizedPriority = normalizePriority($row['priority']);
        $priorities[$normalizedPriority] += $row['bugCount'];
    }
    
    $storyPoints = 0;
    foreach ($DB->query($spSql) as $row) {
        $storyPoints = intval($row['totalStoryPoints']);
    }
    
    $result = calculateQualityScore($priorities, $storyPoints);
    
    return array(
        'qualityScore' => $result['qualityScore'],
        'bugScore' => $result['bugScore'],
        'storyPoints' => $storyPoints,
        'p0Bugs' => $priorities['P0'],
        'p1Bugs' => $priorities['P1'],
        'p2Bugs' => $priorities['P2'],
        'p3Bugs' => $priorities['P3']
    );
}

/**
 * Get Quality Score Trend over time
 */
function getQualityScoreTrend_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);
    
    $whereClauseBugs = buildCommonWhereClause($startDate, $endDate, false, array('isInvalid=0', $projectNameReference . " IN (" . $projectName . ")", "bugCategory IN ('" . str_replace(",", "','", $category) . "')", getTotalBugsWhereClause()), 'createdAt');
    $whereClauseTickets = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " IN (" . $projectName . ")"), 'createdAt');
    
    $bugsSql = "SELECT DATE(createdAt) AS createdAt, priority, COUNT(*) AS bugCount
                FROM " . getTableName($tableNamePrefix) . "
                WHERE " . $whereClauseBugs . "
                GROUP BY DATE(createdAt), priority
                ORDER BY DATE(createdAt), priority";
    $bugsSql = updateGroupBy($bugsSql, $startDate, $endDate);
    
    $spSql = "SELECT DATE(createdAt) AS createdAt, SUM(storyPoints) as storyPoints
              FROM " . getJiraTableName($tableNamePrefix) . "
              WHERE " . $whereClauseTickets . "
              GROUP BY DATE(createdAt)";
    $spSql = updateGroupBy($spSql, $startDate, $endDate);
    
    $bugsData = array();
    foreach ($DB->query($bugsSql) as $row) {
        $date = $row['createdAt'];
        if (!isset($bugsData[$date])) {
            $bugsData[$date] = array('P0' => 0, 'P1' => 0, 'P2' => 0, 'P3' => 0);
        }
        $normalizedPriority = normalizePriority($row['priority']);
        $bugsData[$date][$normalizedPriority] += $row['bugCount'];
    }
    
    $storyPointsData = array();
    foreach ($DB->query($spSql) as $row) {
        $storyPointsData[$row['createdAt']] = intval($row['storyPoints']);
    }
    
    $jsonArraySubCategory = array();
    $jsonArrayQualityScore = array();
    $jsonArrayBugWeightage = array();
    $jsonArrayStoryPoints = array();
    
    foreach ($bugsData as $date => $priorities) {
        $storyPoints = isset($storyPointsData[$date]) ? $storyPointsData[$date] : 0;
        $result = calculateQualityScore($priorities, $storyPoints);
        
        array_push($jsonArraySubCategory, array('label' => $date));
        array_push($jsonArrayQualityScore, array('value' => $result['qualityScore']));
        array_push($jsonArrayBugWeightage, array('value' => $result['bugScore']));
        array_push($jsonArrayStoryPoints, array('value' => $storyPoints));
    }
    
    return array(
        'categories' => array(array('category' => $jsonArraySubCategory)),
        'dataset' => array(
            array('seriesname' => 'Quality Score', 'data' => $jsonArrayQualityScore),
            array('seriesname' => 'Bug Score', 'visible' => '0', 'data' => $jsonArrayBugWeightage),
            array('seriesname' => 'Story Points', 'visible' => '0', 'data' => $jsonArrayStoryPoints)
        )
    );
}

$jsonArray = array();
if (!isset($_GET['functionname'])) {
    $jsonArray['error'] = 'No function name!';
}

if (!isset($jsonArray['error'])) {
    switch ($_GET['functionname']) {
        case 'getProjectNames':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getProjectNames($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getTotalTicketsTested':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalTicketsTested($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getTotalBugsCount':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalBugsCount($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getBugCountAndPercentageData':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugCountAndPercentageData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getQualityScore_All':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getQualityScore_All($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getTotalTicketsTested_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalTicketsTested_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getTotalBugsCount_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalBugsCount_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getBugsData_Project':
            validateParams(6, $_GET['arguments']);
            $jsonArray = getBugsData_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4], $_GET['arguments'][5]);
            break;
        case 'getIssuesList_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getIssuesList_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getProdBugLeakage_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getProdBugLeakage_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getBugCountTrendByPriority_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugCountTrendByPriority_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getProdBugLeakageTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getProdBugLeakageTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getBugRatioTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugRatioTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getQualityScore_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getQualityScore_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getQualityScoreTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getQualityScoreTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
    }
    echo json_encode($jsonArray);
}
?>