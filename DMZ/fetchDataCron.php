#!/usr/bin/env php
<?php
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ . '/config.php';

$searchTerms = DEFAULT_SEARCH_TERM;
$data=fetchBooks($searchTerms);
echo "[" . date('c'). "] Starting cron job. \n";
if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
}

//Debug
$clean_data = cleanData($data);
processPublishBooks($clean_data);
echo "[" . date('c') . "] Cron finsihed *dabs*\n";
?>
