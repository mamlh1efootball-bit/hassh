<?php
include '../config.php';
//==========================// table creator //==========================//
#-- User --#
mysqli_multi_query($connect, "CREATE TABLE `user` (
  `id` BIGINT(32) PRIMARY KEY,
  `spam` VARCHAR(20) NOT NULL,
  `step` VARCHAR(50) DEFAULT NULL,
  `data` TEXT DEFAULT NULL,
  `daily_at` BIGINT DEFAULT NULL,
  `create_at` BIGINT DEFAULT NULL,
  `update_at` BIGINT DEFAULT NULL
  ) default charset = utf8mb4;");

#-- Channels --#
mysqli_multi_query($connect, "CREATE TABLE `channels` (
  `idoruser` VARCHAR(30) PRIMARY KEY,
  `link` VARCHAR(200) NOT NULL
  ) default charset = utf8mb4;");

#-- Admins --#
mysqli_multi_query($connect, "CREATE TABLE `admin` (
  `admin` BIGINT(40) PRIMARY KEY
  ) default charset = utf8mb4;
  INSERT INTO `admin` (`admin`) VALUES ('$admin')");

#-- Block List --#
mysqli_multi_query($connect, "CREATE TABLE `block` (
  `id` BIGINT(32) NOT NULL
  ) default charset = utf8mb4;");

#-- Groups --#
mysqli_multi_query($connect, "CREATE TABLE `group` (
  `id` TEXT DEFAULT NULL,
	`name` TEXT DEFAULT NULL,
	`member` BIGINT(32) DEFAULT '0',
  `time` TEXT DEFAULT NULL,
  `date` TEXT DEFAULT NULL,
  `user_id` TEXT DEFAULT NULL,
  `chat_id` TEXT DEFAULT NULL,
  `join_at` BIGINT DEFAULT NULL,
  `update_at` BIGINT DEFAULT NULL
  ) default charset = utf8mb4;");

#-- Send All --#
mysqli_multi_query($connect, "CREATE TABLE `sendall` (
  `step` VARCHAR(20) DEFAULT NULL,
  `admin` BIGINT(32) DEFAULT NULL,
  `messageid` BIGINT(32) DEFAULT NULL,
	`text` TEXT DEFAULT NULL,
	`chat` VARCHAR(100) DEFAULT NULL,
	`sended` BIGINT(32) DEFAULT '0'
  ) default charset = utf8mb4;
  INSERT INTO `sendall` () VALUES ();");
//========================== // Check connection // ==============================
if ($connect->connect_error) {
  die("خطا در ارتصال به خاطره :" . $connect->connect_error);
}
echo "دیتابیس متصل و نصب شد .";
