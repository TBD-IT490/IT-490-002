#!/usr/bin/env php
<?php
require_once __DIR__ . '/fetchData.php';
require_once __DIR__ . '/config.php';
//require_once __DIR__ . '/apiListener.php';

$searchTerms = '';
$data=fetchBooks($searchTerms);
echo "[" . date('c'). "] Starting cron job. \n";
if($data==null){
	echo"[" . date('c'). "] Cron failed to fetch data :c \n";
	exit(1);
}

//Debug
$clean_data = cleanData($data);
processPublishBooks($clean_data);
echo "[" . date('c') . "] Cron finsihed for now...*dabs*\n";

//logging stuff
$logFile = __DIR__ .'/cron.log';
file_put_contents($logFile, "[" . date('c') . "] The cron has begun...\n", FILE_APPEND);

for($i = 0; $i < count($data["items"]); $i++) {
	/// print array
    $book[$i]['api_book_id'] = $data["items"][$i]['id'] ?? null;
	file_put_contents($logFile, "Fetched book with api_book_id: " . $book[$i]['api_book_id'] . "\n", FILE_APPEND);
}
file_put_contents($logFile, "[" . date('c') . "] The cron has finished for now...\n", FILE_APPEND);
?>
