<?php
$dbhost_name = "127.0.0.1";
$database = "thanos";
$username = "root";
$password = "Thanos@121";

try {
$dbo = new PDO('mysql:host='.$dbhost_name.';dbname='.$database, $username, $password);
} catch (PDOException $e) {
print "Error!: " . $e->getMessage() . "<br/>";
die();
}
?> 