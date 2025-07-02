<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use ChatApp\User;

$user = new User();
$user->logout();

// Redirect to login page
header('Location: login.php');
exit; 