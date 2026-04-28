<?php
$host = "sql100.infinityfree.com";
$user = "if0_41775045";
$pass = "S@deeq20562056";
$db   = "if0_41775045_student_system";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
