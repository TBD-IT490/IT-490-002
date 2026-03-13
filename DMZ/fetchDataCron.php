#!/usr/bin/env php
<?php
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ . '/config.php';
//require_once __DIR__ . '/apiListener.php';

$searchTerms = 'cat in the hat';
$data=fetchBooks($searchTerms);
echo "[" . date('c'). "] Starting cron job. \n";
if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
}

//Debug
try {
	$clean_data = cleanData($data);
} catch (Exception $e) {
	echo "". $e->getMessage() ."";
}

return processPublishBooks($clean_data);
?>
