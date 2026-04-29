<?php
require_once __DIR__ .'/vendor/autoload.php'; /** rmq library */

$output = shell_exec("tailscale status --json");
$data = json_decode($output, true);
$Selfhost = $data['Self']['HostName'];
$Selfip = $data['Self']['TailscaleIPs'][0];
$result = [];


$result['Self'] = $data['Self']['TailscaleIPs'][0];

$host = gethostname();
$clust = explode('-', $host)[0];

foreach ($data['Peer'] as $peer){
    $host = $peer['HostName'];
    $ip = $peer['TailscaleIPs'][0];
    echo "" . $host . "|" . $ip;
    if (str_contains($host,$clust)) {
        $ser = explode('-',$host)[1];
        if ($ser == "frontend") {
            $result['frontend'] = $ip;
        } elseif ($ser == "backend") {
            $result['backend'] = $ip;
        } elseif ($ser == "dmz"){
            $result["dmz"] = $ip;
        }
    } elseif($host == "deployment") {
        $result['deploy'] = $ip;
    }
}
print_r($result);
$env = "";
foreach ($result as $key => $value) {
    $envKey = strtoupper(str_replace('-', '_', $key));

    $env .= "$envKey=$value\n";
}

// write to file
file_put_contents('.env', $env);


?>