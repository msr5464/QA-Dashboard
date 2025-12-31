<?php
header('Content-type: application/json');
require("../../config.php");

function getTableName($tableNamePrefix)
{
    return str_replace(" ", "_", strtolower($tableNamePrefix) . "_jira_bugs");
}

function getStagingBugsWhereClause()
{
    return "bugCategory = 'STG'";
}

$p0SlaInHours = 24;
$p1SlaInHours = 24;
$p2SlaInHours = 48;
$p3SlaInHours = 96;
$closedStatuses = "'QA DONE','QA PASSED','Closed/Deployed','Deployed to Production'";

function getProjectNames($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();
    $sql = "Select distinct projectName from " . getTableName($tableNamePrefix) . " order by projectName desc;";

    foreach ($DB->query($sql) as $row) {
        array_push($jsonArray, $row['projectName']);
    }
    return $jsonArray;
}

function getTotalBugs($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();
    $sql = "SELECT IFNULL(c.category, 'TotalBugs') AS category, COUNT(a.classification) AS bugs FROM (SELECT 'PaymentGateway' AS category UNION ALL SELECT 'Invalid' UNION ALL SELECT 'Partner' UNION ALL SELECT 'TotalBugs') c LEFT JOIN " . getTableName($tableNamePrefix) . " a ON c.category = a.classification AND date(a.createdAt) >= '" . $startDate . "' AND date(a.createdAt) <= '" . $endDate . "' AND a.isDeleted=0 AND " . getStagingBugsWhereClause() . " GROUP BY c.category WITH ROLLUP;";

    foreach ($DB->query($sql) as $row) {
        $category = $row['category'];
        $bugs = $row['bugs'];
        // Map category names to desired key names
        $key = '';
        switch ($category) {
            case 'PaymentGateway':
                $key = 'paymentGatewayBugs';
                break;
            case 'Invalid':
                $key = 'invalidBugs';
                break;
            case 'Partner':
                $key = 'partnerBugs';
                break;
            case 'TotalBugs':
                $key = 'totalBugs';
                break;
            default:
                break;
        }
        $jsonArray[$key] = $bugs;
    }
    return $jsonArray;
}

function getBugCountData($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $whereClause = buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive, array(getStagingBugsWhereClause()), 'createdAt');
    $sql = "select priority, classification as category, count(*) as bugs from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by classification,priority order by priority, classification asc";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;
        $increasedPnBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['bugs'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['bugs'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['bugs'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "PaymentGateway Bugs",
        "data" => $jsonArrayBugs1
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Partner Bugs",
        "data" => $jsonArrayBugs2,
        "visible" => "0"
    ));
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Invalid Bugs",
        "data" => $jsonArrayBugs3,
        "visible" => "0"
    ));

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getOverallBugSlaData($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $whereClause = buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive, array(getStagingBugsWhereClause()), 'createdAt');
    $sql = "SELECT priority, category, COUNT(*) AS totalRows, ROUND((SUM(isWithinSLA = 'Yes') / (SUM(isWithinSLA = 'Yes') + SUM(isWithinSLA = 'No'))) * 100, 0) AS slaPercentage 
            FROM (
                SELECT 
                    classification as category, priority, 
                    CASE 
                        WHEN priority = 'P0-Critical' THEN IF(overallTime > (" . $p0SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P1-High' THEN IF(overallTime > (" . $p1SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P2-Medium' THEN IF(overallTime > (" . $p2SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P3-Low' THEN IF(overallTime > (" . $p3SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        ELSE 'TBD' 
                    END AS isWithinSLA 
                FROM " . getTableName($tableNamePrefix) . " 
                WHERE " . $whereClause . "
            ) AS subquery 
            GROUP BY category, priority 
            ORDER BY priority, category ASC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['slaPercentage'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['slaPercentage'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['slaPercentage'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "PaymentGateway Bugs",
            "data" => $jsonArrayBugs1
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Partner Bugs",
            "data" => $jsonArrayBugs2,
            "visible" => "0"
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Invalid Bugs",
            "data" => $jsonArrayBugs3,
            "visible" => "0"
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getDevelopmentBugSlaData($tableNamePrefix, $startDate, $endDate, $isVerticalDataActive)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $whereClause = buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive, array(getStagingBugsWhereClause()), 'createdAt');
    $sql = "SELECT priority, category, COUNT(*) AS totalRows, ROUND((SUM(isWithinSLA = 'Yes') / (SUM(isWithinSLA = 'Yes') + SUM(isWithinSLA = 'No'))) * 100, 0) AS slaPercentage 
            FROM (
                SELECT 
                    classification as category, priority, 
                    CASE 
                        WHEN priority = 'P0-Critical' THEN IF(devTime > (" . $p0SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P1-High' THEN IF(devTime > (" . $p1SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P2-Medium' THEN IF(devTime > (" . $p2SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P3-Low' THEN IF(devTime > (" . $p3SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        ELSE 'TBD' 
                    END AS isWithinSLA 
                FROM " . getTableName($tableNamePrefix) . " 
                WHERE " . $whereClause . "
            ) AS subquery 
            GROUP BY category, priority 
            ORDER BY priority, category ASC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['slaPercentage'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['slaPercentage'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['slaPercentage'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "PaymentGateway Bugs",
            "data" => $jsonArrayBugs1
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Partner Bugs",
            "data" => $jsonArrayBugs2,
            "visible" => "0"
        )
    );
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Invalid Bugs",
            "data" => $jsonArrayBugs3,
            "visible" => "0"
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function setBugFoundByValue($row)
{
    $jsonArrayItem = array();
    switch ($row['bugFoundBy']) {
        case "manual":
            $jsonArrayItem['label'] = "Manual Testing";
            $jsonArrayItem['value'] = $row['bugs'];
            break;
        case "automation":
            $jsonArrayItem['label'] = "Automation";
            $jsonArrayItem['value'] = $row['bugs'];
            break;
        case "crashes/leaks":
            $jsonArrayItem['label'] = "Crashes/Leaks";
            $jsonArrayItem['value'] = $row['bugs'];
            break;
        case "real prod users":
            $jsonArrayItem['label'] = "Real Users";
            $jsonArrayItem['value'] = $row['bugs'];
            break;
        case "incident":
            $jsonArrayItem['label'] = "Incidents";
            $jsonArrayItem['value'] = $row['bugs'];
            break;
        default:
            $jsonArrayItem['label'] = "Others";
            $jsonArrayItem['value'] = $row['bugs'];
    }
    return $jsonArrayItem;
}

function getTotalBugs_Project($tableNamePrefix, $startDate, $endDate, $projectName)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " in (" . $projectName . ")", getStagingBugsWhereClause()), 'createdAt');
    $sql = "SELECT IFNULL(c.category, 'TotalBugs') AS category, COUNT(a.classification) AS bugs FROM (SELECT 'PaymentGateway' AS category UNION ALL SELECT 'Invalid' UNION ALL SELECT 'Partner' UNION ALL SELECT 'TotalBugs') c LEFT JOIN " . getTableName($tableNamePrefix) . " a ON c.category = a.classification AND a." . $whereClause . " GROUP BY c.category WITH ROLLUP;";

    foreach ($DB->query($sql) as $row) {
        $category = $row['category'];
        $bugs = $row['bugs'];
        // Map category names to desired key names
        $key = '';
        switch ($category) {
            case 'PaymentGateway':
                $key = 'paymentGatewayBugs';
                break;
            case 'Invalid':
                $key = 'invalidBugs';
                break;
            case 'Partner':
                $key = 'partnerBugs';
                break;
            case 'TotalBugs':
                $key = 'totalBugs';
                break;
            default:
                break;
        }
        $jsonArray[$key] = $bugs;
    }
    return $jsonArray;
}

function getBugCountData_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " in (" . $projectName . ")", getStagingBugsWhereClause()), 'createdAt');
    $sql = "select priority, classification as category, count(*) as bugs from " . getTableName($tableNamePrefix) . " where " . $whereClause . " group by classification,priority order by priority, classification asc";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;
        $increasedPnBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['bugs'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['bugs'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['bugs'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push($jsonArrayCategory, array(
        "category" => $jsonArraySubCategory
    ));
    $visible = (strpos($category, "PaymentGateway") !== false) ? "1" : "0";
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "PaymentGateway Bugs",
        "data" => $jsonArrayBugs1,
        "visible" => $visible
    ));
    $visible = (strpos($category, "Partner") !== false) ? "1" : "0";
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Partner Bugs",
        "data" => $jsonArrayBugs2,
        "visible" => $visible
    ));
    $visible = (strpos($category, "Invalid") !== false) ? "1" : "0";
    array_push($jsonArrayDataSet1, array(
        "seriesname" => "Invalid Bugs",
        "data" => $jsonArrayBugs3,
        "visible" => $visible
    ));
    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getOverallBugSlaData_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $sql = "SELECT priority, category, COUNT(*) AS totalRows, ROUND((SUM(isWithinSLA = 'Yes') / (SUM(isWithinSLA = 'Yes') + SUM(isWithinSLA = 'No'))) * 100, 0) AS slaPercentage 
            FROM (
                SELECT 
                    classification as category, priority, 
                    CASE 
                        WHEN priority = 'P0-Critical' THEN IF(overallTime > (" . $p0SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P1-High' THEN IF(overallTime > (" . $p1SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P2-Medium' THEN IF(overallTime > (" . $p2SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P3-Low' THEN IF(overallTime > (" . $p3SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        ELSE 'TBD' 
                    END AS isWithinSLA 
                FROM " . getTableName($tableNamePrefix) . " 
                WHERE isDeleted=0 AND " . $projectNameReference . " in (" . $projectName . ") AND date(createdAt) >= '" . $startDate . "' AND date(createdAt) <= '" . $endDate . "'
            ) AS subquery 
            GROUP BY category, priority 
            ORDER BY priority, category ASC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['slaPercentage'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['slaPercentage'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['slaPercentage'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    $visible = (strpos($category, "PaymentGateway") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "PaymentGateway Bugs",
            "data" => $jsonArrayBugs1,
            "visible" => $visible
        )
    );
    $visible = (strpos($category, "Partner") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Partner Bugs",
            "data" => $jsonArrayBugs2,
            "visible" => $visible
        )
    );
    $visible = (strpos($category, "Invalid") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Invalid Bugs",
            "data" => $jsonArrayBugs3,
            "visible" => $visible
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getDevelopmentBugSlaData_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $jsonArrayCategory = array();
    $jsonArraySubCategory = array();
    $jsonArrayDataSet1 = array();
    $jsonArrayBugs1 = array();
    $jsonArrayBugs2 = array();
    $jsonArrayBugs3 = array();
    $previousPriority = "";

    $sql = "SELECT priority, category, COUNT(*) AS totalRows, ROUND((SUM(isWithinSLA = 'Yes') / (SUM(isWithinSLA = 'Yes') + SUM(isWithinSLA = 'No'))) * 100, 0) AS slaPercentage 
            FROM (
                SELECT 
                    classification as category, priority, 
                    CASE 
                        WHEN priority = 'P0-Critical' THEN IF(devTime > (" . $p0SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P1-High' THEN IF(devTime > (" . $p1SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P2-Medium' THEN IF(devTime > (" . $p2SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P3-Low' THEN IF(devTime > (" . $p3SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        ELSE 'TBD' 
                    END AS isWithinSLA 
                FROM " . getTableName($tableNamePrefix) . " 
                WHERE isDeleted=0 AND " . $projectNameReference . " in (" . $projectName . ") AND date(createdAt) >= '" . $startDate . "' AND date(createdAt) <= '" . $endDate . "'
            ) AS subquery 
            GROUP BY category, priority 
            ORDER BY priority, category ASC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    foreach ($rows as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $increasedPaymentGatewayBugs = 0;
        $increasedPartnerBugs = 0;
        $increasedInvalidBugs = 0;

        if ($previousPriority != $row["priority"]) {
            $previousPriority = $row["priority"];
            foreach ($rows as $singleRow) {
                if ($row["priority"] == $singleRow["priority"]) {
                    switch ($singleRow['category']) {
                        case "PaymentGateway":
                            $increasedPaymentGatewayBugs = $increasedPaymentGatewayBugs + $singleRow['slaPercentage'];
                            break;
                        case "Partner":
                            $increasedPartnerBugs = $increasedPartnerBugs + $singleRow['slaPercentage'];
                            break;
                        case "Invalid":
                            $increasedInvalidBugs = $increasedInvalidBugs + $singleRow['slaPercentage'];
                            break;
                    }
                }
            }

            $jsonArrayItem['label'] = $row["priority"];
            array_push($jsonArraySubCategory, $jsonArrayItem);

            $jsonArrayItem1['value'] = $increasedPaymentGatewayBugs;
            array_push($jsonArrayBugs1, $jsonArrayItem1);

            $jsonArrayItem2['value'] = $increasedPartnerBugs;
            array_push($jsonArrayBugs2, $jsonArrayItem2);

            $jsonArrayItem3['value'] = $increasedInvalidBugs;
            array_push($jsonArrayBugs3, $jsonArrayItem3);
        }
    }

    array_push(
        $jsonArrayCategory,
        array(
            "category" => $jsonArraySubCategory
        )
    );
    $visible = (strpos($category, "PaymentGateway") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "PaymentGateway Bugs",
            "data" => $jsonArrayBugs1,
            "visible" => $visible
        )
    );
    $visible = (strpos($category, "Partner") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Partner Bugs",
            "data" => $jsonArrayBugs2,
            "visible" => $visible
        )
    );
    $visible = (strpos($category, "Invalid") !== false) ? "1" : "0";
    array_push(
        $jsonArrayDataSet1,
        array(
            "seriesname" => "Invalid Bugs",
            "data" => $jsonArrayBugs3,
            "visible" => $visible
        )
    );

    $jsonArray = array(
        "categories" => $jsonArrayCategory,
        "dataset" => $jsonArrayDataSet1
    );
    return $jsonArray;
}

function getIssuesList_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $sql = "SELECT issueId, createdAt, title, priority, teamName, bugType, rootCause, status, 
                CONCAT(CASE WHEN devTime = 0 THEN '0' ELSE CONCAT(ROUND(devTime/3600, 1), 'H') END) AS devTimeHours,
                CONCAT(CASE WHEN qaTime = 0 THEN '0' ELSE CONCAT(ROUND(qaTime/3600, 1), 'H') END) AS qaTimeHours, 
                CASE 
                    WHEN priority = 'P0-Critical' THEN IF(devTime > (" . $p0SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P1-High' THEN IF(devTime > (" . $p1SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P2-Medium' THEN IF(devTime > (" . $p2SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P3-Low' THEN IF(devTime > (" . $p3SlaInHours . " * 3600), 'No', IF(devTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    ELSE 'TBD' 
                END AS isWithinDevelopmentSLA,  
                CONCAT(CASE WHEN overallTime = 0 THEN '0' ELSE CONCAT(ROUND(overallTime/3600, 1), 'H') END) AS overallTimeHours, 
                CASE 
                    WHEN priority = 'P0-Critical' THEN IF(overallTime > (" . $p0SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P1-High' THEN IF(overallTime > (" . $p1SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P2-Medium' THEN IF(overallTime > (" . $p2SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    WHEN priority = 'P3-Low' THEN IF(overallTime > (" . $p3SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD')) 
                    ELSE 'TBD' 
                END AS isWithinOverallSLA
                
            FROM " . getTableName($tableNamePrefix) . " 
            WHERE isDeleted=0 
                AND " . $projectNameReference . " IN (" . $projectName . ") 
                AND classification IN ('" . str_replace(",", "','", $category) . "') 
                AND date(createdAt) >= '" . $startDate . "' 
                AND date(createdAt) <= '" . $endDate . "' 
            ORDER BY priority,createdAt DESC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();
    return $rows;
}

function getBugCountTrend_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
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
    $sql = "SELECT DATE(createdAt) AS createdAt,sum(bugCount) AS bugCount,priority
            FROM (
                SELECT createdAt,priority,COUNT(*) AS bugCount
                FROM 
                    " . getTableName($tableNamePrefix) . "
                WHERE isDeleted=0 
                AND " . $projectNameReference . " IN (" . $projectName . ") 
                AND classification IN ('" . str_replace(",", "','", $category) . "') 
                AND date(createdAt) >= '" . $startDate . "' 
                AND date(createdAt) <= '" . $endDate . "' 
                GROUP BY DATE(createdAt), priority
            ) AS x
            GROUP BY DATE(createdAt), priority 
            ORDER BY DATE(createdAt), priority;";
    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();

        $createdAtValue = $row['createdAt'];
        $bugCount = $row['bugCount'];
        if ($lastDate == $createdAtValue) {
            switch ($row['priority']) {
                case "P0-Critical":
                    $jsonArrayItem1['value'] = $bugCount;
                    array_pop($jsonArraySubSet1);
                    array_push($jsonArraySubSet1, $jsonArrayItem1);
                    break;
                case "P1-High":
                    $jsonArrayItem2['value'] = $bugCount;
                    array_pop($jsonArraySubSet2);
                    array_push($jsonArraySubSet2, $jsonArrayItem2);
                    break;
                case "P2-Medium":
                    $jsonArrayItem3['value'] = $bugCount;
                    array_pop($jsonArraySubSet3);
                    array_push($jsonArraySubSet3, $jsonArrayItem3);
                    break;
                case "P3-Low":
                    $jsonArrayItem4['value'] = $bugCount;
                    array_pop($jsonArraySubSet4);
                    array_push($jsonArraySubSet4, $jsonArrayItem4);
                    break;
            }
        } else {
            $lastDate = $createdAtValue;
            $jsonArrayItem['label'] = $createdAtValue;
            array_push($jsonArraySubCategory, $jsonArrayItem);

            switch ($row['priority']) {
                case "P0-Critical":
                    $jsonArrayItem1['value'] = $bugCount;
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P1-High":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = $bugCount;
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P2-Medium":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = $bugCount;
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P3-Low":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = $bugCount;
                    break;
            }
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
            array_push($jsonArraySubSet4, $jsonArrayItem4);
        }
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
            "seriesname" => "P0-Count",
            "data" => $jsonArraySubSet1
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P1-Count",
            "data" => $jsonArraySubSet2
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P2-Count",
            "data" => $jsonArraySubSet3
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P3-Count",
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

function getBugSlaTrend_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
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
    $sql = "SELECT DATE(createdAt) AS createdAt,ROUND((SUM(isWithinSLA = 'Yes') / (SUM(isWithinSLA = 'Yes') + SUM(isWithinSLA = 'No'))) * 100, 0) AS bugSla,priority
            FROM (
                SELECT createdAt,priority,
                CASE 
                        WHEN priority = 'P0-Critical' THEN IF(overallTime > (" . $p0SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p0SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P1-High' THEN IF(overallTime > (" . $p1SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p1SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P2-Medium' THEN IF(overallTime > (" . $p2SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p2SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        WHEN priority = 'P3-Low' THEN IF(overallTime > (" . $p3SlaInHours . " * 3600), 'No', IF(overallTime <= (" . $p3SlaInHours . " * 3600) AND status IN ($closedStatuses), 'Yes', 'TBD'))
                        ELSE 'TBD' 
                    END AS isWithinSLA
                FROM 
                    " . getTableName($tableNamePrefix) . "
                WHERE isDeleted=0 
                AND " . $projectNameReference . " IN (" . $projectName . ") 
                AND classification IN ('" . str_replace(",", "','", $category) . "') 
                AND date(createdAt) >= '" . $startDate . "' 
                AND date(createdAt) <= '" . $endDate . "' 
            ) AS x
            GROUP BY DATE(createdAt), priority 
            ORDER BY DATE(createdAt), priority;";

    $sql = updateGroupBy($sql, $startDate, $endDate);

    foreach ($DB->query($sql) as $row) {
        $jsonArrayItem = array();
        $jsonArrayItem1 = array();
        $jsonArrayItem2 = array();
        $jsonArrayItem3 = array();
        $jsonArrayItem4 = array();

        $createdAtValue = $row['createdAt'];
        $bugSla = $row['bugSla'];

        if ($lastDate == $createdAtValue) {
            switch ($row['priority']) {
                case "P0-Critical":
                    $jsonArrayItem1['value'] = $bugSla;
                    array_pop($jsonArraySubSet1);
                    array_push($jsonArraySubSet1, $jsonArrayItem1);
                    break;
                case "P1-High":
                    $jsonArrayItem2['value'] = $bugSla;
                    array_pop($jsonArraySubSet2);
                    array_push($jsonArraySubSet2, $jsonArrayItem2);
                    break;
                case "P2-Medium":
                    $jsonArrayItem3['value'] = $bugSla;
                    array_pop($jsonArraySubSet3);
                    array_push($jsonArraySubSet3, $jsonArrayItem3);
                    break;
                case "P3-Low":
                    $jsonArrayItem4['value'] = $bugSla;
                    array_pop($jsonArraySubSet4);
                    array_push($jsonArraySubSet4, $jsonArrayItem4);
                    break;
            }
        } else {
            $lastDate = $createdAtValue;
            $jsonArrayItem['label'] = $createdAtValue;
            array_push($jsonArraySubCategory, $jsonArrayItem);

            switch ($row['priority']) {
                case "P0-Critical":
                    $jsonArrayItem1['value'] = $bugSla;
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P1-High":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = $bugSla;
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P2-Medium":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = $bugSla;
                    $jsonArrayItem4['value'] = '0';
                    break;
                case "P3-Low":
                    $jsonArrayItem1['value'] = '0';
                    $jsonArrayItem2['value'] = '0';
                    $jsonArrayItem3['value'] = '0';
                    $jsonArrayItem4['value'] = $bugSla;
                    break;
            }
            array_push($jsonArraySubSet1, $jsonArrayItem1);
            array_push($jsonArraySubSet2, $jsonArrayItem2);
            array_push($jsonArraySubSet3, $jsonArrayItem3);
            array_push($jsonArraySubSet4, $jsonArrayItem4);
        }
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
            "seriesname" => "P0-Sla",
            "data" => $jsonArraySubSet1
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P1-Sla",
            "data" => $jsonArraySubSet2
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P2-Sla",
            "data" => $jsonArraySubSet3
        )
    );
    array_push(
        $jsonArrayDataSet,
        array(
            "seriesname" => "P3-Sla",
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
    $whereClause = buildCommonWhereClause($startDate, $endDate, false, array($projectNameReference . " IN (" . $projectName . ")", "classification IN ('" . str_replace(",", "','", $category) . "')", getStagingBugsWhereClause()), 'createdAt');
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
        $dateData[$date][$priority] = $bugCount;
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

function getBugsReviewTable_Project($tableNamePrefix, $startDate, $endDate, $projectName, $category)
{
    global $DB, $p0SlaInHours, $p1SlaInHours, $p2SlaInHours, $p3SlaInHours, $closedStatuses;
    $projectNameReference = getProjectNameReference($projectName);

    $jsonArray = array();
    $sql = "SELECT rootCause,
            SUM(CASE WHEN priority = 'P0-Critical' THEN 1 ELSE 0 END) AS P0,
            SUM(CASE WHEN priority = 'P1-High' THEN 1 ELSE 0 END) AS P1,
            SUM(CASE WHEN priority = 'P2-Medium' THEN 1 ELSE 0 END) AS P2,
            SUM(CASE WHEN priority = 'P3-Low' THEN 1 ELSE 0 END) AS P3,
            SUM(1) AS totalCount,
            (12 * SUM(CASE WHEN priority = 'P0-Critical' THEN 1 ELSE 0 END)) +
            (6 * SUM(CASE WHEN priority = 'P1-High' THEN 1 ELSE 0 END)) +
            (2 * SUM(CASE WHEN priority = 'P2-Medium' THEN 1 ELSE 0 END)) +
            (1 * SUM(CASE WHEN priority = 'P3-Low' THEN 1 ELSE 0 END)) AS bugScore
            FROM " . getTableName($tableNamePrefix) . " 
            WHERE isDeleted=0 
                AND " . $projectNameReference . " IN (" . $projectName . ") 
                AND classification IN ('" . str_replace(",", "','", $category) . "') 
                AND date(createdAt) >= '" . $startDate . "' 
                AND date(createdAt) <= '" . $endDate . "' 
            GROUP BY rootCause
            ORDER BY rootCause ASC;";

    $dbResults = $DB->query($sql);
    $rows = $dbResults->fetchAll();

    // Calculate totals
    $totalP0 = 0;
    $totalP1 = 0;
    $totalP2 = 0;
    $totalP3 = 0;
    $totalBugs = 0;
    $totalQs = 0;

    foreach ($rows as $row) {
        $totalP0 += $row['P0'];
        $totalP1 += $row['P1'];
        $totalP2 += $row['P2'];
        $totalP3 += $row['P3'];
        $totalBugs += $row['totalCount'];
        $totalQs += $row['bugScore'];
    }

    // Add the total row
    $totalRow = array(
        'rootCause' => 'TOTAL',
        'P0' => $totalP0,
        'P1' => $totalP1,
        'P2' => $totalP2,
        'P3' => $totalP3,
        'totalCount' => $totalBugs,
        'bugScore' => $totalQs
    );
    $rows[] = $totalRow;
    return $rows;
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
        case 'getTotalBugs':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalBugs($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getBugCountData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getBugCountData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getOverallBugSlaData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getOverallBugSlaData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getDevelopmentBugSlaData':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getDevelopmentBugSlaData($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getTotalBugs_Project':
            validateParams(4, $_GET['arguments']);
            $jsonArray = getTotalBugs_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3]);
            break;
        case 'getBugCountData_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugCountData_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getOverallBugSlaData_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getOverallBugSlaData_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getDevelopmentBugSlaData_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getDevelopmentBugSlaData_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getBugCountTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugCountTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getBugSlaTrend_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugSlaTrend_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getIssuesList_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getIssuesList_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getBugCountTrendByPriority_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugCountTrendByPriority_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
        case 'getBugsReviewTable_Project':
            validateParams(5, $_GET['arguments']);
            $jsonArray = getBugsReviewTable_Project($_GET['arguments'][0], $_GET['arguments'][1], $_GET['arguments'][2], $_GET['arguments'][3], $_GET['arguments'][4]);
            break;
    }
    echo json_encode($jsonArray);
}
?>