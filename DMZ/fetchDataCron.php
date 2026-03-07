#!/usr/bin/env php
<?php
require_once __DIR__ . '/fetchData.php';
//fixed window, 1 hr
$daysBack =DEFAULT_DAYS_BACK;
$data=fetchBooks($daysBack);
echo "[" . date('c'). "] Starting cron job. \n";
if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
}
processPublishBooks($data);
echo "[" . date('c') . "] Cron finsihed *dabs*\n";
?>
