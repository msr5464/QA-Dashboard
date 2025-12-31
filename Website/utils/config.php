<?php
$version = "1035";

require_once("constants.php");
require_once("common-functions.php");

/**
 * Entity Configurations
 * 
 * Defines which entities are available and their settings.
 * Each entity controls:
 * - Which pages/tabs are visible (show* flags)
 * - Database table prefix (tableNamePrefix)
 * - Environment/group pairs for results page
 * - Active status (isActive)
 */
$ENTITY_CONFIGURATIONS = [
    [
        'entityName' => 'PaymentGateway',
        'tableNamePrefix' => 'paymentgateway',
        'isActive' => true,
        'showFctTests' => true,
        'showFctTestsAuto' => true,
        'showAllTests' => false,
        'showAllTestsAuto' => false,
        'showAutomationStability' => true,
        'showProdBugs' => true,
        'showFctBugs' => true,
        'showStagingBugs' => true,
        'showTotalBugs' => true,
        'environmentAndGroupNamePairs' => [
            'staging,regression',
            'staging,androidCases',
            'staging,iosCases'
        ]
    ],
    [
        'entityName' => 'All Entities',
        'tableNamePrefix' => 'entities',
        'isActive' => false,
        'showFctTests' => false,
        'showFctTestsAuto' => false,
        'showAllTests' => true,
        'showAllTestsAuto' => true,
        'showAutomationStability' => true,
        'showProdBugs' => false,
        'showFctBugs' => false,
        'showStagingBugs' => false,
        'showTotalBugs' => false,
        'environmentAndGroupNamePairs' => [
            'staging,regression',
            'staging,androidCases',
            'staging,Production'
        ]
    ]
];

// display all error except deprecated and notice  
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

// turn on output buffering 
ob_start();

// turn off magic-quotes support, for runtime e, as it will cause problems if enabled
if (version_compare(PHP_VERSION, 5.3, '<') && function_exists('set_magic_quotes_runtime'))
    set_magic_quotes_runtime(0);

// set currentPage in the local scope
$currentPage = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);


// basic options for PDO 
$dboptions = array(
    PDO::ATTR_PERSISTENT => FALSE,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

//connect with the server
try {
    $DB = new PDO(DB_DRIVER . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_HOST_USERNAME, DB_HOST_PASSWORD, $dboptions);
} catch (Exception $ex) {
    echo errorMessage($ex->getMessage());
    die;
}
?>