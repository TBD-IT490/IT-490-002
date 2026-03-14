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
	$book = [];
	for($i = 0; $i < count($data["items"] ?? []); $i++) {
	/// print array
    $book[$i]['api_book_id'] = $data["items"][$i]['id'] ?? null;
    $book[$i]['isbn'] = $data["items"][$i]['volumeInfo']['industryIdentifiers'][1]['identifier'] ?? null;
    $book[$i]['title'] = $data["items"][$i]['volumeInfo']['title'] ?? null;
    $book[$i]['subtitle'] = $data["items"][$i]['volumeInfo']['subtitle'] ?? null;
    $book[$i]['author'] = $data["items"][$i]['volumeInfo']['authors'][0] ?? null;
    $book[$i]['description'] = $data["items"][$i]['volumeInfo']['description'] ?? null;
    $book[$i]['cover_url'] = $data["items"][$i]['volumeInfo']['imageLinks']['thumbnail'] ?? null;
    $book[$i]['publisher'] = $data["items"][$i]['volumeInfo']['publisher'] ?? null;
    $published_date = $data["items"][$i]['volumeInfo']['publishedDate'] ?? null;
	if (strlen($published_date) != 4){ 
		$published_date = date("Y", strtotime($published_date));
	}
	$book[$i]['published_year'] = $published_date;
	
	$book[$i]['genre'] = $data["items"][$i]['volumeInfo']['categories'][0] ?? null;
    $book[$i]['maturity_rating'] = $data["items"][$i]['volumeInfo']['maturityRating'] ?? null;
    $book[$i]['content_version'] = $data["items"][$i]['volumeInfo']['contentVersion'] ?? null;
	$book[$i]['pages'] = $data["items"][$i]['volumeInfo']['pageCount'] ?? 0 ;
	}
	echo '' . print_r($book, true) .'';
    return $book;
}

//uses curl to get all the books from api
function fetchBooks(string $searchTerm): ?array{
	$url=buildURL($searchTerm);
	echo''.$url.'';
	$ch=curl_init($url);
	curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT=>10]);
	$response=curl_exec($ch);
	$httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);	

	//trying to fix that 429 error :c
	if($httpCode==429){
		echo"Google hates us, waiting 5 seconds...\n";
		sleep(5);
		return null;//fetchBooks($searchTerm);
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
function processPublishBooks(array $data){
	//$seenFile= __DIR__ . '/bookIDsInQueue.txt';
	//$seen = file_exists($seenFile) ? array_flip(file($seenFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)) : [];
	$count=count($data);
	$books = [];
	/*foreach($data as $book){

		//turns book entry into json string
		$json=json_encode($book);


		publishToRabbit($json);
		$count++;
		if($count > 10){ 
			break;
		}
	}*/
	$json=json_encode($data);
	return $json;
	//publishToRabbit($json);
	echo "Published $count books to RabbitMQ :D !!\n";
	//if($count > 0){return true;} else {return false;}
}
?>