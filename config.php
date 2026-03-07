#!/usr/bin/env php
<?php
define ('API_BASE', 'https://www.googleapis.com/books/v1/volumes');
define ('API_KEY', 'AIzaSyDYJbl3JpctqD5r3bn_qF4LkCzBvjOfdQI');
define ('DEFAULT_SEARCH_TERM', 'The Name Book');

//for rabbit
define ('RABBITMQ_HOST','100.101.27.73'); // change to matt's ip 100.127.138.110
define ('RABBITMQ_PORT', 5672);
define ('RABBITMQ_USER', 'broker'); //change to broker rabbit user
define ('RABBITMQ_PASS', 'test'); // change to test rabbit pass
define ('RABBITMQ_QUEUE', 'api_queue'); //change queue name to actual name

?>
