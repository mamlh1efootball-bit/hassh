<?php
error_reporting(0);
//==========================// token //==========================//
define('API_KEY', '8535592623:AAEQNB4Yg6umfHQtp4fNS4vEf0VnhXYjNXw');
define('API_URL', 'https://api.telegram.org/bot' . API_KEY . '/');
//==========================// config //==========================//
$admin    = "7668129326";
$group_log    = "-1005061061409";

$usernamebot  = "CryptoPISHRO_Bot";
$channel = "TEAM_PISHROO";
$bug_report = "OwnerPISHRO";

$web = "https://domain.ir/folder";
$api = "https://api.spcloud.online";
//==========================// database //==========================//
$dbname = "novinpa1_pishro";
$dbuser = "novinpa1_pishro";
$dbpass = "8535592623:AAEQNB4Yg6umfHQtp4fNS4vEf0VnhXYjNXw";

$connect = new mysqli('localhost', $dbuser, $dbpass, $dbname);
$connect->query("SET NAMES 'utf8'");
$connect->set_charset('utf8mb4');
