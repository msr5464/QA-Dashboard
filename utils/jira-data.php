<?php
header('Content-type: application/json');
require("config.php");

function getTableName($verticalName) {
    return str_replace(" ", "_", strtolower($verticalName)."_jira");
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

        if($totalTicketsTested<=0)
            $totalTicketsTested = 1;

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

            $perc = round(($increasedStgBugs*100) / $totalTicketsTested, 1);
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

        if($totalTicketsTested<=0)
            $totalTicketsTested = 1;

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

            $perc = round(($increasedProdBugs*100) / $totalTicketsTested,1);
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
    $sql = "SELECT sum(totalTicketsTested) as totalTicketsTested, sum(totalStgBugs) as totalStgBugs, sum(totalProdBugs) as totalProdBugs FROM(SELECT (a.totalTicketsTested-b.totalTicketsTested) as totalTicketsTested,(a.totalStgBugs-b.totalStgBugs) as totalStgBugs,(a.totalProdBugs-b.totalProdBugs) as totalProdBugs FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' and projectName in (" . $projectName . ") group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName in (" . $projectName . ") group by a.projectName order by a.projectName desc) temp;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['totalTicketsTested'] = $row['totalTicketsTested'];
        $jsonArrayItem['totalProdBugs'] = $row['totalProdBugs'];
        $jsonArrayItem['totalStgBugs'] = $row['totalStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugPriorityBreakdown_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $sql = "SELECT sum(p0ProdBugs) as p0ProdBugs,sum(p1ProdBugs) as p1ProdBugs,sum(p2ProdBugs) as p2ProdBugs,sum(pnProdBugs) as pnProdBugs, sum(p0StgBugs) as p0StgBugs,sum(p1StgBugs) as p1StgBugs,sum(p2StgBugs) as p2StgBugs,sum(pnStgBugs) as pnStgBugs from (SELECT (a.p0ProdBugs-b.p0ProdBugs) as p0ProdBugs,(a.p1ProdBugs-b.p1ProdBugs) as p1ProdBugs,(a.p2ProdBugs-b.p2ProdBugs) as p2ProdBugs,(a.pnProdBugs-b.pnProdBugs) as pnProdBugs, (a.p0StgBugs-b.p0StgBugs) as p0StgBugs,(a.p1StgBugs-b.p1StgBugs) as p1StgBugs,(a.p2StgBugs-b.p2StgBugs) as p2StgBugs,(a.pnStgBugs-b.pnStgBugs) as pnStgBugs FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName in (" . $projectName . ") group by a.projectName order by a.projectName desc) temp;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = "P0-Prod";
        $jsonArrayItem['value'] = $row['p0ProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "P1-Prod";
        $jsonArrayItem['value'] = $row['p1ProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "P2-Prod";
        $jsonArrayItem['value'] = $row['p2ProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Pn-Prod";
        $jsonArrayItem['value'] = $row['pnProdBugs'];
        array_push($jsonArray, $jsonArrayItem);

        $jsonArrayItem['label'] = "P0-Stg";
        $jsonArrayItem['value'] = $row['p0StgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "P1-Stg";
        $jsonArrayItem['value'] = $row['p1StgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "P2-Stg";
        $jsonArrayItem['value'] = $row['p2StgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Pn-Stg";
        $jsonArrayItem['value'] = $row['pnStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugCategoryBreakdown_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $sql = "SELECT sum(manualFoundProdBugs) as manualFoundProdBugs,sum(automationFoundProdBugs) as automationFoundProdBugs,sum(incidentFoundProdBugs) as incidentFoundProdBugs,sum(otherFoundProdBugs) as otherFoundProdBugs, sum(manualFoundStgBugs) as manualFoundStgBugs,sum(automationFoundStgBugs) as automationFoundStgBugs,sum(incidentFoundStgBugs) as incidentFoundStgBugs,sum(otherFoundStgBugs) as otherFoundStgBugs from (SELECT (a.manualFoundProdBugs-b.manualFoundProdBugs) as manualFoundProdBugs,(a.automationFoundProdBugs-b.automationFoundProdBugs) as automationFoundProdBugs,(a.incidentFoundProdBugs-b.incidentFoundProdBugs) as incidentFoundProdBugs,(a.otherFoundProdBugs-b.otherFoundProdBugs) as otherFoundProdBugs, (a.manualFoundStgBugs-b.manualFoundStgBugs) as manualFoundStgBugs,(a.automationFoundStgBugs-b.automationFoundStgBugs) as automationFoundStgBugs,(a.incidentFoundStgBugs-b.incidentFoundStgBugs) as incidentFoundStgBugs,(a.otherFoundStgBugs-b.otherFoundStgBugs) as otherFoundStgBugs FROM ".getTableName($verticalName)." a JOIN ".getTableName($verticalName)." b ON a.projectName = b.projectName AND a.id > b.id LEFT OUTER JOIN ".getTableName($verticalName)." c ON a.projectName = c.projectName AND a.id > c.id AND b.id < c.id WHERE a.id in (select max(id) from ".getTableName($verticalName)." where date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' group by projectName) and date(b.createdAt)>='" . $startDate . "' and date(b.createdAt)<='" . $endDate . "' and a.projectName in (" . $projectName . ") group by a.projectName order by a.projectName desc) temp;";

    foreach ($DB->query($sql) as $row)
    {
        $jsonArrayItem = array();
        $jsonArrayItem['label'] = "Manual-Prod";
        $jsonArrayItem['value'] = $row['manualFoundProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Automation-Prod";
        $jsonArrayItem['value'] = $row['automationFoundProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Incident-Prod";
        $jsonArrayItem['value'] = $row['incidentFoundProdBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Other-Prod";
        $jsonArrayItem['value'] = $row['otherFoundProdBugs'];
        array_push($jsonArray, $jsonArrayItem);

        $jsonArrayItem['label'] = "Manual-Stg";
        $jsonArrayItem['value'] = $row['manualFoundStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Automation-Stg";
        $jsonArrayItem['value'] = $row['automationFoundStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Incident-Stg";
        $jsonArrayItem['value'] = $row['incidentFoundStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
        $jsonArrayItem['label'] = "Other-Stg";
        $jsonArrayItem['value'] = $row['otherFoundStgBugs'];
        array_push($jsonArray, $jsonArrayItem);
    }
    return $jsonArray;
}

function getBugTrend_Project($verticalName, $projectName, $startDate, $endDate) {
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayDataSet2 = array();
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
    $totalTicketsTested = 1;
    
    $sql = "SELECT DATE(createdAt) as createdAt, sum(totalTicketsTested) as totalTicketsTested, IFNULL(round((sum(totalStgBugs)*100/sum(totalTicketsTested)),2),0) as stgBugPercentage, IFNULL(round((sum(totalProdBugs)*100/sum(totalTicketsTested)),2),0) as prodBugPercentage, sum(totalStgBugs) as totalStgBugs, sum(totalProdBugs) as totalProdBugs, sum(p0StgBugs) as p0StgBugs, sum(p0ProdBugs) as p0ProdBugs,sum(p1StgBugs) as p1StgBugs, sum(p1ProdBugs) as p1ProdBugs,sum(p2StgBugs) as p2StgBugs, sum(p2ProdBugs) as p2ProdBugs from (SELECT DATE(createdAt) as createdAt, totalTicketsTested, totalStgBugs, totalProdBugs, p0StgBugs, p0ProdBugs,p1StgBugs, p1ProdBugs,p2StgBugs, p2ProdBugs FROM `".getTableName($verticalName)."` WHERE projectName in (" . $projectName . ") and date(createdAt)>='" . $startDate . "' and date(createdAt)<='" . $endDate . "' GROUP BY DATE(createdAt),projectName) temp GROUP BY DATE(createdAt);";
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

        $jsonArrayItem = array();
        $jsonArrayItem['label'] = $row['createdAt'];
        array_push($jsonArraySubCategory, $jsonArrayItem);


        $jsonArrayItem1['value'] = $row['totalTicketsTested'];
        $jsonArrayItem2['value'] = $row['totalStgBugs'];
        $jsonArrayItem3['value'] = $row['totalProdBugs'];
        $jsonArrayItem4['value'] = $row['p0StgBugs'];
        $jsonArrayItem5['value'] = $row['p0ProdBugs'];
        $jsonArrayItem6['value'] = $row['p1StgBugs'];
        $jsonArrayItem7['value'] = $row['p1ProdBugs'];
        $jsonArrayItem8['value'] = $row['p2StgBugs'];
        $jsonArrayItem9['value'] = $row['p2ProdBugs'];
        $jsonArrayItem10['value'] = $row['stgBugPercentage'];
        $jsonArrayItem11['value'] = $row['prodBugPercentage'];
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
    }
    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Tickets Tested",
        "data" => $jsonArraySubSet1
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Total Stg Bugs",
        "data" => $jsonArraySubSet2
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Total Prod Bugs",
        "data" => $jsonArraySubSet3
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P0 Stg Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet4
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P0 Prod Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet5
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P1 Stg Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet6
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P1 Prod Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet7
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P2 Stg Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet8
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "P2 Prod Bugs",
        "visible" => "0",
        "data" => $jsonArraySubSet9
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Staging Bug Percentage",
        "data" => $jsonArraySubSet10
    ));
    array_push($jsonArrayDataSet2, array(
        "seriesname" => "Production Bug Percentage",
        "data" => $jsonArraySubSet11
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset1" => $jsonArrayDataSet1,
        "dataset2" => $jsonArrayDataSet2
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
        case 'getBugPriorityBreakdown_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getBugPriorityBreakdown_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getBugCategoryBreakdown_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getBugCategoryBreakdown_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
        case 'getBugTrend_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getBugTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
        break;
    }
    echo json_encode($jsonArray);
}
?>