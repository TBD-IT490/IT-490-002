#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rabbitmqPublish.php';

//builds url with start and end date and api key
function buildURL(int $daysBack): string
{
	$end = new DateTime('now', new DateTimeZone('UTC'));
	$start = (clone $end)->modify("-{$daysBack} days");
	$pubStartDate=$start->format('Y-m-d\TH:i:s\Z');
	$pubEndDate=$end->format('Y-m-d\TH:i:s\Z');
	$query=http_build_query(['pubStartDate'=>$pubStartDate, 'pubEndDate'=>$pubEndDate, 'resultsPerPage'=>100]);
	return API_BASE . '?' . $query;
}

//uses curl to call the api, checks the http status andd ecodes the json
function fetchBooks(int $daysBack): ?array{
	$url=buildURL($daysBack);
	$ch=curl_init($url);
//	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER =>['apiKey: ' . API_KEY]]);
	$response=curl_exec($ch);
	$httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);
	if($response==false || $httpCode!==200){
		fwrite(STDERR, "Error fetching book api :( HTTP code: $httpCode\n");
		curl_close($ch);
		return null;
	}
	curl_close($ch);
	$data=json_decode($response,true);
	if($data==null){
		fwrite(STDERR, "Error decoding JSON from book list :| \n");
		return null;
	} return $data;
}

//gets each book and converts to json and sends to rabbit
function processPublishBooks(array $data):void{
	if(!isset($data['vulnerabilities']) || !is_array($data['vulnerabilities'])){
		echo "No vulnerabilities field in response. \n";
	}
	$seenFile= __DIR__ . '/seen_books.txt';
	$seen = file_exists($seenFile) ? array_flip(file($seenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
	$count=0;
	foreach($data['vulnerabilities'] as $item){
		if(!isset($item['book']['id'])){
			continue;
		}
		$bookID=$item['book']['id'];
		//skips duplicates
		if(isset($seen[$bookID])){
			continue;
		}
		//turns book entry into json string
		$json=json_encode($item['book']);
		if($json == false){
			continue;
		}

		publishToRabbit($json);
		$count++;

		file_put_contents($seenFile,$bookID . "\n", FILE_APPEND);
		$seen[$bookID] = true;

		//slows down publishing to not overload queue
		usleep(50000); //50ms delay
	}

	echo "Published $count books to RabbitMQ :D !!\n";
}

$daysBack=DEFAULT_DAYS_BACK;
if($argc > 1 && is_numeric($argv[1])){
	$daysBack=(int)$argv[1];
}

echo "url: " . buildUrl($daysBack) . "\n";
//echo "apikey: ".  API_KEY . "\n";

echo "Fetching books from last $daysBack day(s) ... \n";
$data=fetchBooks($daysBack);
if($data==null){
	echo "Failed to fetch data D:\n";
	exit(1);
}
processPublishBooks($data);
echo "Done!! :D\n";
?>
