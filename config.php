<?php
$host = "mysql.railway.internal";
$user = "root";
$pass = "MCEEbZJQpLSoHyZnvakumJNVmUXQteDN";
$db   = "railway";
$port = 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
