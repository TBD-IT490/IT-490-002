#!/usr/bin/env php
<?php
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ . '/config.php';
//fixed window, 1 hr
$data=fetchBooks($searchTerms);
echo "[" . date('c'). "] Starting cron job. \n";
if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
}
processPublishBooks($data);
echo "[" . date('c') . "] Cron finsihed *dabs*\n";
?>
