#!/usr/bin/php
<?php

define('source_ip', '100.112.153.128'); //source ip
define('proxy_user', 'haproxy_user');
define('proxy_pass', '');
define('db_name', 'noetic');

define('replica_ip', '127.0.0.1'); //replica ip
define('replica_user', 'root');
define('replica_pass', 'it490');


$check = 5; //every 5 seconds??

echo "[*] Starting Failover Script...\n";
//$conn = mysqli_connect(source_ip, proxy_user, proxy_pass, db_name);


//check connection
function connectDB() {
	$conn = mysqli_connect(source_ip, proxy_user, proxy_pass, db_name);
    echo "Connected to source!\n";

	if ($conn->connect_error) {
		echo "Connection Failed!! \n";

        echo "Switching to backup server... \n";
		return null;
	}
	return $conn;
}

function connectReplica() {
    $conn2 = mysqli_connect(replica_ip, replica_user, replica_pass, db_name);

    if ($conn2->connect_error) {
		echo "Connection to Replica Failed!! \n";
		return null;
	}
	return $conn2;
}

while (true) {
    try{
        $conn = connectDB();

        if ($conn) {
            echo "Connection to source successful! \n";
            $conn->close();
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "Connection to source FAILED! \n";
        echo "Switching to replica! \n";

        //this is for testing purposes only weeee
        $conn2 = connectReplica();
        if ($conn2) {
            echo "Connect to replica success! \n";
            echo "STOP REPLICA! \n";
            echo "RESET REPLICA! \n";
            echo "SET GLOBAL read_only = 0; \n";
            echo "SET GLOBAL super_read_only = 0; \n";
            $stmt = $conn2->query("STOP REPLICA;");
            $stmt2 = $conn2->query("RESET REPLICA ALL;");
            $stmt3 = $conn2->query("SET GLOBAL read_only = 0;");
            $stmt4 = $conn2->query("SET GLOBAL super_read_only = 0;");

            /*raahh
            $stmt = $conn2->query("STOP REPLICA;");
            $stmt2 = $conn2->query("RESET REPLICA ALL;");
            $stmt3 = $conn2->query("SET GLOBAL read_only = 0;");
            $stmt4 = $conn2->query("SET GLOBAL super_read_only = 0;");
            */
            /* test raahh
            echo "Showing users... \n";
            $rah = $conn2->query("SELECT * FROM users");
            foreach($rah as $row) {
                echo $row['username'] . "\n";
            }
            */
        }

        echo "Replica has been promoted to source...RAAHH! \n";
        $conn2->close();
        exit(0);
    }

    sleep($check);
}

?>