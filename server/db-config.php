<?php
$dbhost_name = "127.0.0.1";
$username = "root";
$password = "Thanos@121";

try
{
	$selectedYear = $_COOKIE['selectedYear'];
	if(empty($selectedYear))
	{
		$database = "thanos";
	}
	else
	{
		//$database = $selectedYear; - Uncomment this once you have multiple dbs
		$database = "thanos";
	}
    $dbo = new PDO('mysql:host=' . $dbhost_name . ';dbname=' . $database, $username, $password);
}
catch(PDOException $e)
{
    print "Error!: " . $e->getMessage() . "<br/>";
    die();
}
?>
