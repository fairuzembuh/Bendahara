<?php

session_start();
require_once __DIR__ . '/classes.php';

$auth = new Auth();
$auth->logout();

header("Location: index.php");
exit;
?>
