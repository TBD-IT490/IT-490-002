#!/usr/bin/env php
<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/rabbitmqPublish.php';

//builds url for the search term
function buildURL(string $searchTerm): string
{
	
	$query = http_build_query(['q'=>$searchTerm, 'maxResults'=>10, 'key'=>API_KEY]);
	return API_BASE . '?' . $query;
}

function cleanData($data){

	/// print array
$book = [];

    $book['book_id'] = $data['id'] ?? null;
    $book['api_book_id'] = $data['id'] ?? null;

    $book['isbn'] = $data['volumeInfo']['industryIdentifiers'][1]['identifier'] ?? null;

    $book['title'] = $data['volumeInfo']['title'] ?? null;
    $book['subtitle'] = $data['volumeInfo']['subtitle'] ?? null;

    $book['author'] = $data['volumeInfo']['authors'][0] ?? null;

    $book['description'] = $data['volumeInfo']['description'] ?? null;

    $book['cover_url'] = $data['volumeInfo']['imageLinks']['thumbnail'] ?? null;

    $book['created_at'] = date("Y-m-d H:i:s");

    $book['publisher'] = $data['volumeInfo']['publisher'] ?? null;

    $book['published_year'] = $data['volumeInfo']['publishedDate'] ?? null;

    $book['genre'] = $data['volumeInfo']['categories'][0] ?? null;

    $book['maturity_rating'] = $data['volumeInfo']['maturityRating'] ?? null;

    $book['content_version'] = $data['volumeInfo']['contentVersion'] ?? null;

    $book['country'] = $data['saleInfo']['country'] ?? null;

    return $book;

}

//uses curl to get all the books from api
function fetchBooks(string $searchTerm): ?array{
	$url=buildURL($searchTerm);
	$ch=curl_init($url);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT=>10]);
	$response=curl_exec($ch);
	$httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);	

	//trying to fix that 429 error :c
	if($httpCode==429){
		echo"Google hates us, waiting 5 seconds...\n";
		sleep(5);
		return fetchBooks($searchTerm);
	}
	//deals with other errors	
	if($response==false || $httpCode!==200){
		fwrite(STDERR, "Error fetching book api :( \nHTTP code: $httpCode\n");
		curl_close($ch);
		return null;
	}
	curl_close($ch);
	$data=json_decode($response,true);
	if($data==null){
		fwrite(STDERR, "Error decoding JSON from book api :| \n");
		return null;
	} return $data;
}

//gets each book to send to rabbit
function processPublishBooks(array $data):void{
	if(!isset($data['items']) || !is_array($data['items'])){
		echo "No items field in response. \n";
	}
	$seenFile= __DIR__ . '/bookIDsInQueue.txt';
	$seen = file_exists($seenFile) ? array_flip(file($seenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
	$count=0;
	foreach($data['items'] as $item){
		if(!isset($item['id'])){
			continue;
		}
		$bookID=$item['id'];
		//skips duplicates
		if(isset($seen[$bookID])){
			continue;
		}
		//turns book entry into json string
		$json=json_encode($item);
		if($json == false){
			continue;
		}

		publishToRabbit($json);
		$count++;
		
		//puts seen books in a separate file
		file_put_contents($seenFile,$bookID . "\n", FILE_APPEND);
		$seen[$bookID] = true;

		//slows down publishing to not overload queue
		usleep(50000); //50ms delay
	}

	echo "Published $count books to RabbitMQ :D !!\n";
}

$searchTerm=DEFAULT_SEARCH_TERM;
if($argc > 1 && is_numeric($argv[1])){
	$searchTerm=(int)$argv[1];
}

echo "url: " . buildUrl($searchTerm) . "\n";
//echo "apikey: ".  API_KEY . "\n";

echo "Fetching books for search term:'$searchTerm'... \n";
$data=fetchBooks($searchTerm);
if($data==null){
	echo "Failed to fetch data D:\n";
	exit(1);
}
$clean_data = cleanData($data);
processPublishBooks($clean_data);
echo "Done!! :D\n";
?>
