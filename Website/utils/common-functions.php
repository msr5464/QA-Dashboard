<?php
include 'ChromePhp.php';

function errorMessage($str) {
    return '<div style="width:50%; margin:0 auto; border:2px solid #F00;padding:2px; color:#000; margin-top:10px; text-align:center;">' . $str . '</div>';
}

function simple_redirect($url) {

    echo "<script language=\"JavaScript\">\n";
    echo "<!-- hide from old browser\n\n";

    echo "window.location = \"" . $url . "\";\n";

    echo "-->\n";
    echo "</script>\n";

    return true;
}

function getHomeURL() {
    return HTTP_SERVER . SITE_DIR;
}

//Encryption function
function easy_crypt($string) {
    return base64_encode($string . "_@#!@");
}

//Decodes encryption
function easy_decrypt($str) {
    $str = base64_decode($str);
    return str_replace("_@#!@", "", $str);
}

/**
 * Get all active entity configurations
 * @return array Array of active entity configurations (converted to database-like format for backward compatibility)
 */
function getActiveEntityData() {
    global $ENTITY_CONFIGURATIONS;
    static $cachedActiveEntities = null;
    
    if ($cachedActiveEntities === null) {
        $results = array();
        
        // Filter active entities and convert to database-like format
        foreach ($ENTITY_CONFIGURATIONS as $config) {
            if (isset($config['isActive']) && $config['isActive']) {
                $results[] = convertConfigToDbFormat($config);
            }
        }
        
        // Sort by entityName descending (matching original SQL behavior)
        usort($results, function($a, $b) {
            return strcmp($b['entityName'], $a['entityName']);
        });
        
        $cachedActiveEntities = $results;
    }
    
    return $cachedActiveEntities;
}

/**
 * Get all entity configurations (both active and inactive)
 * @return array Array of all entity configurations (converted to database-like format for backward compatibility)
 */
function getEntityTableData() {
    global $ENTITY_CONFIGURATIONS;
    static $cachedAllEntities = null;
    
    if ($cachedAllEntities === null) {
        $results = array();
        
        // Convert all configurations to database-like format
        foreach ($ENTITY_CONFIGURATIONS as $config) {
            $results[] = convertConfigToDbFormat($config);
        }
        
        // Sort by entityName descending (matching original SQL behavior)
        usort($results, function($a, $b) {
            return strcmp($b['entityName'], $a['entityName']);
        });
        
        $cachedAllEntities = $results;
    }
    
    return $cachedAllEntities;
}

/**
 * Convert simplified config format to database-like format for backward compatibility
 * @param array $config Simplified config array
 * @return array Database-like format array
 */
function convertConfigToDbFormat($config) {
    $dbFormat = array(
        'entityName' => $config['entityName'],
        'tableNamePrefix' => $config['tableNamePrefix'],
        'isActive' => $config['isActive'] ? 1 : 0,
        'showFctTests' => $config['showFctTests'] ? 1 : 0,
        'showFctTestsAuto' => $config['showFctTestsAuto'] ? 1 : 0,
        'showAllTests' => $config['showAllTests'] ? 1 : 0,
        'showAllTestsAuto' => $config['showAllTestsAuto'] ? 1 : 0,
        'showAutomationStability' => $config['showAutomationStability'] ? 1 : 0,
        'showProdBugs' => $config['showProdBugs'] ? 1 : 0,
        'showFctBugs' => $config['showFctBugs'] ? 1 : 0,
        'showStagingBugs' => $config['showStagingBugs'] ? 1 : 0,
        'showTotalBugs' => $config['showTotalBugs'] ? 1 : 0
    );
    
    // Convert environment pairs array to individual fields
    $pairs = isset($config['environmentAndGroupNamePairs']) ? $config['environmentAndGroupNamePairs'] : array();
    $dbFormat['environmentAndGroupNamePair1'] = isset($pairs[0]) ? $pairs[0] : '';
    $dbFormat['environmentAndGroupNamePair2'] = isset($pairs[1]) ? $pairs[1] : '';
    $dbFormat['environmentAndGroupNamePair3'] = isset($pairs[2]) ? $pairs[2] : '';
    
    return $dbFormat;
}

function getLastUpdatedTime() {
    global $DB;
    global $entityName;
    global $projectName;
    global $dbTableName;
    global $bugCategory; // For bug pages: 'FCT', 'STG', 'PRD', or 'total'

    $entity = $entityName;
    if($entity == null || $entity == "")
        $entity = isset($_COOKIE['entity']) ? $_COOKIE['entity'] : '';

    // Initialize as array to prevent foreach errors
    $results = array();
    $tableNamePrefix = "";
    
    // Get tableNamePrefix from entity configurations
    global $ENTITY_CONFIGURATIONS;
    foreach ($ENTITY_CONFIGURATIONS as $config) {
        if (isset($config['entityName']) && $config['entityName'] == $entity) {
            $tableNamePrefix = $config['tableNamePrefix'];
            break;
        }
    }

    // If tableNamePrefix is empty, entity not found - return empty array
    if (empty($tableNamePrefix) || empty($dbTableName)) {
        return $results;
    }

    // List of tables where "updatedAt" should be used instead of "createdAt"
    $tablesWithUpdatedAt = ['jira_bugs'];

    // Determine which timestamp field to use
    $timestampField = in_array($dbTableName, $tablesWithUpdatedAt) ? 'updatedAt' : 'createdAt';

    // Determine which project name reference to use
    if (in_array($dbTableName, $tablesWithUpdatedAt) && $projectName != null && $projectName != "") {
        $projectNameReference = getProjectNameReference($projectName);
    } else {
        $projectNameReference = "projectName";
    }

    // Build WHERE clause
    $whereClause = "";
    if ($dbTableName == 'jira_bugs' && isset($bugCategory)) {
        // For bug tables, use bugCategory to filter
        if ($bugCategory == 'total') {
            $whereClause = "classification = 'PaymentGateway'";
        } else {
            $whereClause = "bugCategory = '" . $bugCategory . "'";
        }
        
        if (!empty($whereClause)) {
            $whereClause = " WHERE " . $whereClause;
            if ($projectName != null && $projectName != "") {
                $whereClause .= " AND $projectNameReference IN (" . $projectName . ")";
            }
        } else {
            if ($projectName != null && $projectName != "") {
                $whereClause = " WHERE $projectNameReference IN (" . $projectName . ")";
            }
        }
    } else {
        // For non-bug tables, use standard WHERE clause
        if ($projectName != null && $projectName != "") {
            $whereClause = " WHERE $projectNameReference IN (" . $projectName . ")";
        }
    }

    // For results table, order by createdAt DESC, for others use id DESC
    $orderByField = ($dbTableName == 'results') ? 'createdAt' : 'id';
    $sql = "SELECT $timestampField as createdAt FROM ".$tableNamePrefix."_".$dbTableName.$whereClause." ORDER BY $orderByField DESC LIMIT 1";

    try {
        $stmt = $DB->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        // If no results found, return empty array (don't show error, just no data)
        if (empty($results)) {
            return array();
        }
    } catch (Exception $ex) {
        // For debugging: uncomment the line below to see errors
        // error_log("getLastUpdatedTime error: " . $ex->getMessage() . " SQL: " . $sql);
        return array();
    }
    return $results;
}

function validateParams($counter, $params)
{
    $jsonArray = array();
    if (!is_array($params) || (count($params) < $counter)) {
        $jsonArray['error'] = "Error in passed arguments!";
        $jsonArray['failure_reason'] = "Expected parameters = ".$counter." but actul = ".count($params);
        echo json_encode($jsonArray);
    }
}

function showErrorMessage($message)
{
    $jsonArray = array();
    $jsonArray['failure_reason'] = $message;
    echo json_encode($jsonArray);
}

function showVerticalLevelData($sql, $isVerticalDataActive)
{
    $updatedSql = $sql;
    if($isVerticalDataActive == 1)
     {
        $updatedSql = str_replace("projectName not like 'Vertical%'", "projectName like 'Vertical%'", $sql);
     }
    return $updatedSql;
}

function updateGroupBy($sql, $startDate, $endDate)
{
    $updatedSql = $sql;
    $earlier = new DateTime($startDate);
    $later = new DateTime($endDate);
    $diff = $later->diff($earlier)->format("%a");
    if($diff > 269)
    {
        $updatedSql = str_replace("SELECT DATE(createdAt)", "SELECT CONCAT('Q', QUARTER(createdAt), '-', YEAR(createdAt))", $sql);
        $updatedSql = str_replace("GROUP BY DATE(createdAt)", "GROUP BY QUARTER(createdAt), YEAR(createdAt)", $updatedSql);
    }
    else if($diff > 89)
    {
        $updatedSql = str_replace("SELECT DATE(createdAt)", "SELECT DATE_FORMAT(createdAt, '%M %Y')", $sql);
        $updatedSql = str_replace("GROUP BY DATE(createdAt)", "GROUP BY DATE_FORMAT(createdAt, '%M %Y')", $updatedSql);
    }
    else if($diff > 29)
    {
        $updatedSql = str_replace("SELECT DATE(createdAt)", "SELECT CONCAT('Week ', WEEK(createdAt), ' - ', LEFT(MONTHNAME(createdAt), 3), ' ', YEAR(createdAt))", $sql);
        $updatedSql = str_replace("GROUP BY DATE", "GROUP BY WEEK", $updatedSql);
    }
    return $updatedSql;
}

function getMedian($arr) {
    sort($arr);
    $count = count($arr);
    $middleval = floor(($count-1)/2);
    if ($count % 2) {
        $median = $arr[$middleval];
    } else {
        $low = $arr[$middleval];
        $high = $arr[$middleval+1];
        $median = (($low+$high)/2);
    }
    if($median == null)
        $median = 0;
    return $median;
}

function logger($value)
{
    ChromePhp::log($value);
}

/**
 * Get the appropriate project name reference field based on project name
 * Returns "verticalName" if project name contains "Vertical", otherwise "projectName"
 * 
 * @param string $projectName The project name to check
 * @return string Either "verticalName" or "projectName"
 */
function getProjectNameReference($projectName) {
    return (strpos($projectName, "Vertical") !== false) ? "verticalName" : "projectName";
}

/**
 * Build common WHERE clause for bug/ticket queries
 * Handles date filtering, deletion flags, and vertical filtering
 * 
 * @param string $startDate Start date in YYYY-MM-DD format
 * @param string $endDate End date in YYYY-MM-DD format
 * @param bool $isVerticalDataActive If true, include only vertical projects; if false, exclude them
 * @param array $additionalConditions Additional WHERE conditions (e.g., ['isInvalid=0', "category='PRD'"])
 * @param string $dateColumn Column name for date filtering (default: 'createdAt')
 * @return string WHERE clause string
 */
function buildCommonWhereClause($startDate, $endDate, $isVerticalDataActive = false, $additionalConditions = array(), $dateColumn = 'createdAt') {
    $conditions = array();
    
    // Date filtering - use direct column comparison for better index usage
    // Convert date strings to datetime format for proper comparison
    $conditions[] = "$dateColumn >= '$startDate 00:00:00'";
    $conditions[] = "$dateColumn <= '$endDate 23:59:59'";
    
    // Deletion flag
    $conditions[] = "isDeleted=0";
    
    // Vertical filtering: only exclude vertical entries when NOT in vertical view mode
    // When isVerticalDataActive=1, show all data (no vertical filter)
    // When isVerticalDataActive=0, exclude vertical placeholder entries
    if (!$isVerticalDataActive) {
        $conditions[] = "projectName not like 'Vertical%'";
    }
    
    // Add any additional conditions
    foreach ($additionalConditions as $condition) {
        if (!empty($condition)) {
            $conditions[] = $condition;
        }
    }
    
    return implode(" AND ", $conditions);
}

?>