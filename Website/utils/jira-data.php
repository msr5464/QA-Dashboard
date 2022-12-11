<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_jira");
}

function getBugsTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_bugs");
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

function getStgBugsData($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayDataSet2 = array();
    $jsonArrayP0StgCount = array();
    $jsonArrayP1StgCount = array();
    $jsonArrayP2StgCount = array();
    $jsonArrayPnStgCount = array();
    $jsonArrayTotalStgCount = array();
    $jsonArrayTotalStgPerc = array();
    $ticketsTestedSubSet = array();
    $ticketsTestedDataSet = array();
    $totalTicketsTested_all = 0;
    $increasedStgBugs_all = 0;
    $increasedProdBugs_all = 0;
    $counter = 0;
    $trendLineCountAvg = 0;
    $trendLinePercAvg = 0;
    $sql = "SELECT a.projectName,a.totalTicketsTested as newTotalTicketsTested,b.totalTicketsTested as oldTotalTicketsTested, a.totalStgBugs as newTotalStgBugs,b.totalStgBugs as oldTotalStgBugs, a.p0StgBugs as newP0StgBugs,b.p0StgBugs as oldP0StgBugs, a.p1StgBugs as newP1StgBugs,b.p1StgBugs as oldP1StgBugs, a.p2StgBugs as newP2StgBugs,b.p2StgBugs as oldP2StgBugs, a.pnStgBugs as newPnStgBugs,b.pnStgBugs as oldPnStgBugs, a.totalProdBugs as newTotalProdBugs,b.totalProdBugs as oldTotalProdBugs FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName not like 'Pod%' group by projectName order by (a.totalStgBugs - b.totalStgBugs) desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

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

        $totalTicketsTested = $row['newTotalTicketsTested'] - $row['oldTotalTicketsTested'];
        $increasedProdBugs = $row['newTotalProdBugs'] - $row['oldTotalProdBugs'];
        $increasedStgBugs = $row['newTotalStgBugs'] - $row['oldTotalStgBugs'];

        $increasedP0StgBugs = $row['newP0StgBugs'] - $row['oldP0StgBugs'];
        $increasedP1StgBugs = $row['newP1StgBugs'] - $row['oldP1StgBugs'];
        $increasedP2StgBugs = $row['newP2StgBugs'] - $row['oldP2StgBugs'];
        $increasedPnStgBugs = $row['newPnStgBugs'] - $row['oldPnStgBugs'];

        if($increasedStgBugs != 0)
        {
            $jsonArrayItem['label'] = $row['projectName'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if($increasedP0StgBugs == 0)
                $jsonArrayItem1['value'] = "";
            else
                $jsonArrayItem1['value'] = $increasedP0StgBugs;
            array_push($jsonArrayP0StgCount, $jsonArrayItem1);

            if($increasedP1StgBugs == 0)
                $jsonArrayItem2['value'] = "";
            else
                $jsonArrayItem2['value'] = $increasedP1StgBugs;
            array_push($jsonArrayP1StgCount, $jsonArrayItem2);

            if($increasedP2StgBugs == 0)
                $jsonArrayItem3['value'] = "";
            else
                $jsonArrayItem3['value'] = $increasedP2StgBugs;
            array_push($jsonArrayP2StgCount, $jsonArrayItem3);

            if($increasedPnStgBugs == 0)
                $jsonArrayItem4['value'] = "";
            else
                $jsonArrayItem4['value'] = $increasedPnStgBugs;
            array_push($jsonArrayPnStgCount, $jsonArrayItem4);

            if($totalTicketsTested<=0)
            {
                $perc = round(($increasedStgBugs*100) / 1, 1);
            }
            else
            {
                $perc = round(($increasedStgBugs*100) / $totalTicketsTested, 1);
            }

            $jsonArrayItem5['value'] = $perc;
            array_push($jsonArrayTotalStgPerc, $jsonArrayItem5);

            $jsonArrayItem6['value'] = $totalTicketsTested;
            array_push($ticketsTestedSubSet, $jsonArrayItem6);

            $jsonArrayItem7['value'] = $increasedStgBugs;
            array_push($jsonArrayTotalStgCount, $jsonArrayItem7);
        }
        $counter++;
        $totalTicketsTested_all = $totalTicketsTested_all + $totalTicketsTested;
        $increasedStgBugs_all = $increasedStgBugs_all + $increasedStgBugs;
        $increasedProdBugs_all = $increasedProdBugs_all + $increasedProdBugs;
    }
    if($counter > 0)
    {
        $trendLineCountAvg = round($increasedStgBugs_all/$counter,1); 
        $trendLinePercAvg = round(($increasedStgBugs_all*100)/$totalTicketsTested_all,1);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P0 Bugs",
        "data" => $jsonArrayP0StgCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P1 Bugs",
        "data" => $jsonArrayP1StgCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P2 Bugs",
        "data" => $jsonArrayP2StgCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Pn Bugs",
        "data" => $jsonArrayPnStgCount
    ));

    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Bug Percentage",
        "data" => $jsonArrayTotalStgPerc
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Tickets Tested",
        "visible" => "0",
        "data" => $ticketsTestedSubSet
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Bugs Found",
        "visible" => "0",
        "data" => $jsonArrayTotalStgCount
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset1" => $jsonArrayDataSet1,
        "dataset2" => $jsonArrayDataSet2,
        "totalTicketsTested_sum" => $totalTicketsTested_all,
        "totalStgBugs_sum" => $increasedStgBugs_all,
        "totalProdBugs_sum" => $increasedProdBugs_all,
        "trendLineCountAvg" => $trendLineCountAvg,
        "trendLinePercAvg" => $trendLinePercAvg
    );
    return $jsonArray;
}

function getProdBugsData($verticalName, $startDate, $endDate, $isPodDataActive) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayDataSet2 = array();
    $jsonArrayP0ProdCount = array();
    $jsonArrayP1ProdCount = array();
    $jsonArrayP2ProdCount = array();
    $jsonArrayPnProdCount = array();
    $jsonArrayTotalProdCount = array();
    $jsonArrayTotalProdPerc = array();
    $ticketsTestedSubSet = array();
    $ticketsTestedDataSet = array();
    $totalTicketsTested_all = 0;
    $increasedProdBugs_all = 0;
    $counter = 0;
    $trendLineCountAvg = 0;
    $trendLinePercAvg = 0;
    
    $sql = "SELECT a.projectName,a.totalTicketsTested as newTotalTicketsTested,b.totalTicketsTested as oldTotalTicketsTested, a.totalProdBugs as newTotalProdBugs,b.totalProdBugs as oldTotalProdBugs, a.p0ProdBugs as newP0ProdBugs,b.p0ProdBugs as oldP0ProdBugs, a.p1ProdBugs as newP1ProdBugs,b.p1ProdBugs as oldP1ProdBugs, a.p2ProdBugs as newP2ProdBugs,b.p2ProdBugs as oldP2ProdBugs, a.pnProdBugs as newPnProdBugs,b.pnProdBugs as oldPnProdBugs FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName not like 'Pod%' group by projectName order by (a.totalProdBugs - b.totalProdBugs) desc;";
    $sql = showPodLevelData($sql, $isPodDataActive);

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

        $totalTicketsTested = $row['newTotalTicketsTested'] - $row['oldTotalTicketsTested'];
        $increasedProdBugs = $row['newTotalProdBugs'] - $row['oldTotalProdBugs'];
        $increasedP0ProdBugs = $row['newP0ProdBugs'] - $row['oldP0ProdBugs'];
        $increasedP1ProdBugs = $row['newP1ProdBugs'] - $row['oldP1ProdBugs'];
        $increasedP2ProdBugs = $row['newP2ProdBugs'] - $row['oldP2ProdBugs'];
        $increasedPnProdBugs = $row['newPnProdBugs'] - $row['oldPnProdBugs'];

        if($increasedProdBugs != 0)
        {
            $jsonArrayItem['label'] = $row['projectName'];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if($increasedP0ProdBugs == 0)
                $jsonArrayItem1['value'] = "";
            else
                $jsonArrayItem1['value'] = $increasedP0ProdBugs;
            array_push($jsonArrayP0ProdCount, $jsonArrayItem1);

            if($increasedP1ProdBugs == 0)
                $jsonArrayItem2['value'] = "";
            else
                $jsonArrayItem2['value'] = $increasedP1ProdBugs;
            array_push($jsonArrayP1ProdCount, $jsonArrayItem2);

            if($increasedP2ProdBugs == 0)
                $jsonArrayItem3['value'] = "";
            else
                $jsonArrayItem3['value'] = $increasedP2ProdBugs;
            array_push($jsonArrayP2ProdCount, $jsonArrayItem3);

            if($increasedPnProdBugs == 0)
                $jsonArrayItem4['value'] = "";
            else
                $jsonArrayItem4['value'] = $increasedPnProdBugs;
            array_push($jsonArrayPnProdCount, $jsonArrayItem4);

            if($totalTicketsTested<=0)
            {
                $perc = round(($increasedProdBugs*100) / 1, 1);
            }
            else
            {
                $perc = round(($increasedProdBugs*100) / $totalTicketsTested, 1);
            }

            $jsonArrayItem5['value'] = $perc;
            array_push($jsonArrayTotalProdPerc, $jsonArrayItem5);

            $jsonArrayItem6['value'] = $totalTicketsTested;
            array_push($ticketsTestedSubSet, $jsonArrayItem6);

            $jsonArrayItem7['value'] = $increasedProdBugs;
            array_push($jsonArrayTotalProdCount, $jsonArrayItem7);
        }
        $counter++;
        $totalTicketsTested_all = $totalTicketsTested_all + $totalTicketsTested;
        $increasedProdBugs_all = $increasedProdBugs_all + $increasedProdBugs;
    }
    if($counter > 0)
    {
        $trendLineCountAvg = round($increasedProdBugs_all/$counter,1); 
        $trendLinePercAvg = round(($increasedProdBugs_all*100)/$totalTicketsTested_all,1);
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P0 Bugs",
        "data" => $jsonArrayP0ProdCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P1 Bugs",
        "data" => $jsonArrayP1ProdCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P2 Bugs",
        "data" => $jsonArrayP2ProdCount
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Pn Bugs",
        "data" => $jsonArrayPnProdCount
    ));

    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Bug Percentage",
        "data" => $jsonArrayTotalProdPerc
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Tickets Tested",
        "visible" => "0",
        "data" => $ticketsTestedSubSet
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Bugs Found",
        "visible" => "0",
        "data" => $jsonArrayTotalProdCount
    ));
    
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset1" => $jsonArrayDataSet1,
        "dataset2" => $jsonArrayDataSet2,
        "trendLineCountAvg" => $trendLineCountAvg,
        "trendLinePercAvg" => $trendLinePercAvg
    );
    return $jsonArray;
}

function getTotalTicketsTested_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $sql = "SELECT sum(totalTicketsTested) as totalTicketsTested FROM(SELECT (a.totalTicketsTested-b.totalTicketsTested) as totalTicketsTested FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName in (" . $projectName . ") group by a.projectName order by a.projectName desc) temp;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['totalTicketsTested'] = is_null($row['totalTicketsTested']) ? '0' : $row['totalTicketsTested'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getTotalBugsCount_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $projectNameReference = "projectName";
    if( strpos( $projectName, "Pod" ) !== false)
        $projectNameReference = "podName";

    $sql = "select count(*) as bugs, environment from ".getBugsTableName($verticalName)." where ".$projectNameReference." in (" . $projectName . ") and isDeleted=0 and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by environment;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        if($row['environment'] == 'Staging')
            $jsonArrayItem['totalStgBugs'] = $row['bugs'];
        else if($row['environment'] == 'Production')
            $jsonArrayItem['totalProdBugs'] = $row['bugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugsData_Project($verticalName, $startDate, $endDate, $projectName, $filterName, $environment) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayP0BugCount = array();
    $jsonArrayP1BugCount = array();
    $jsonArrayP2BugCount = array();
    $jsonArrayPnBugCount = array();
    $increasedP0Bugs = 0;
    $increasedP1Bugs = 0;
    $increasedP2Bugs = 0;
    $increasedPnBugs = 0;
    $previousValue="";
    $projectNameReference = "projectName";
    if( strpos( $projectName, "Pod" ) !== false)
        $projectNameReference = "podName";

    $sql = "select count(*) as bugs,priority,".$filterName." from ".getBugsTableName($verticalName)." where environment in ('".str_replace(",","','",$environment)."') and ".$projectNameReference." in (" . $projectName . ") and isDeleted=0 and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by priority,".$filterName." order by ".$filterName.",priority;";
    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $increasedP0Bugs = 0;
        $increasedP1Bugs = 0;
        $increasedP2Bugs = 0;
        $increasedPnBugs = 0;

        if($previousValue != $row[$filterName])
        {
            $previousValue = $row[$filterName];
            foreach ($rows as $singleRow)
            {
                if($row[$filterName] == $singleRow[$filterName])
                {
                    switch ($singleRow['priority']) {
                      case "Blocker":
                      case "P0":
                        $increasedP0Bugs = $increasedP0Bugs + $singleRow['bugs'];
                        break;
                      case "P1":
                        $increasedP1Bugs = $increasedP1Bugs + $singleRow['bugs'];
                        break;
                      case "P2":
                        $increasedP2Bugs = $increasedP2Bugs + $singleRow['bugs'];
                        break;
                      default:
                        $increasedPnBugs = $increasedPnBugs + $singleRow['bugs'];
                    }
                } 
            }

            $jsonArrayItem['label'] = $row[$filterName];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            if($increasedP0Bugs == 0)
                $jsonArrayItem1['value'] = "";
            else
                $jsonArrayItem1['value'] = $increasedP0Bugs;
            array_push($jsonArrayP0BugCount, $jsonArrayItem1);

            if($increasedP1Bugs == 0)
                $jsonArrayItem2['value'] = "";
            else
                $jsonArrayItem2['value'] = $increasedP1Bugs;
            array_push($jsonArrayP1BugCount, $jsonArrayItem2);

            if($increasedP2Bugs == 0)
                $jsonArrayItem3['value'] = "";
            else
                $jsonArrayItem3['value'] = $increasedP2Bugs;
            array_push($jsonArrayP2BugCount, $jsonArrayItem3);

            if($increasedPnBugs == 0)
                $jsonArrayItem4['value'] = "";
            else
                $jsonArrayItem4['value'] = $increasedPnBugs;
            array_push($jsonArrayPnBugCount, $jsonArrayItem4);
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
        "seriesname" => "Pn Bugs",
        "data" => $jsonArrayPnBugCount
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getIssuesList_Project($verticalName, $startDate, $endDate, $projectName, $environment) {
    global $DB;
    $jsonArray = array();
    $projectNameReference = "projectName";
    if( strpos( $projectName, "Pod" ) !== false)
        $projectNameReference = "podName";

    $sql = "select issueId,DATE_FORMAT(createdAt,'%d %b %Y'),title,priority,environment,bugFoundBy,bugCategory,status from ".getBugsTableName($verticalName)." where environment in ('".str_replace(",","','",$environment)."') and ".$projectNameReference." in (" . $projectName . ") and isDeleted=0 and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' order by environment, Date(createdAt) desc, priority;";
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
        case 'getStgBugsData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getStgBugsData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getProdBugsData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getProdBugsData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
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
    }
    echo json_encode($jsonArray);
}
?>