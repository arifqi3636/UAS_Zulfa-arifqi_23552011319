<?php

session_start();
require_once '../config/database.php';
require_once '../includes/Auth.php';

// Perform logout
$auth = new Auth();

// Redirect to login
header('Location: ../index.php?logout=success');
exit();
