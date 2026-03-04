<?php
	define('DB_NAME', 'tosnema22');
	define('DB_USER', 'tosnema22');
	define('DB_PASSWORD', 'jzzBnDYb');
	define('DB_HOST', '127.0.0.1');

	global $db;
    $db = new PDO(
   	"mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
    DB_USER,
    DB_PASSWORD,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

?>	