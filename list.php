<?php

include("config.php");

//$input = file_get_contents("php://input");
$json = json_decode($input);

$data['device_id'] = isset($json->device_id) ? $json->device_id : "";
$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['os'] = isset($json->os) ? $json->os : "";
$data['limit'] = isset($json->limit) ? $json->limit : 100;

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport",
    $myuser, $mypass
);
    
if ($IS_DEVELOPMENT == false) {
    $filter_time = "NOW() BETWEEN COALESCE(valid_from, NOW()) AND COALESCE(valid_to, NOW())"; 
} else {
    $iservice = "gettime-dev";
    $result_gettime = file_get_contents('http://alegrium5.alegrium.com/gazillionaire/cloudsave/?'.$iservice, null, stream_context_create(
            array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: application/json'. "\r\n"
                    . 'x-api-key: ' . X_API_KEY_TOKEN . "\r\n"
                    . 'Content-Length: ' . strlen('{}') . "\r\n",
                    'content' => '{}'
                )
            )
        )
    );
    $result_gettime = json_decode($result_gettime, true);
    $timestamp = $result_gettime['timestamp'];
    
    $filter_time = "$timestamp BETWEEN COALESCE(UNIX_TIMESTAMP(valid_from), $timestamp) AND COALESCE(UNIX_TIMESTAMP(valid_to), $timestamp)";
}

if ($data['facebook_id'] != "") {
    
    $sql1 = "
        SELECT master_inbox.*,
            COALESCE(inbox_fb.facebook_id, :facebook_id) facebook_id
        FROM master_inbox 
        LEFT JOIN inbox_fb 
            ON master_inbox.info_id = inbox_fb.info_id
            AND inbox_fb.facebook_id = :facebook_id
        WHERE 
            (
                master_inbox.target_fb is null
                OR master_inbox.target_fb IN (
                    SELECT ''
                    UNION ALL
                    SELECT :facebook_id
                )
            )
            AND ( master_inbox.target_device is null OR master_inbox.target_device = '' )
            AND master_inbox.os IN ('All', :os)
            AND master_inbox.status = 1
            AND $filter_time
            AND inbox_fb.facebook_id IS NULL
        LIMIT {$data['limit']}
    ";
    $statement1 = $connection->prepare($sql1);
    $statement1->bindParam(":facebook_id", $data['facebook_id']);
    $statement1->bindParam(":os", $data['os']);
    $statement1->execute();
    $row_inbox = $statement1->fetchAll(PDO::FETCH_ASSOC);

    $data['inbox'] = $row_inbox;
    
} elseif ($data['device_id'] != "") {
    
    $sql1 = "
        SELECT master_inbox.*,
            COALESCE(inbox.device_id, :device_id) device_id
        FROM master_inbox 
        LEFT JOIN inbox 
            ON master_inbox.info_id = inbox.info_id
            AND inbox.device_id = :device_id
        WHERE 
            (
		master_inbox.target_device is null 
                OR master_inbox.target_device IN (
                    SELECT '' 
                    UNION ALL
                    SELECT :device_id
                    UNION ALL
                    SELECT cast(user_id as char(100)) FROM device_user WHERE device_id = :device_id
                )
            )
            AND ( master_inbox.target_fb is null OR master_inbox.target_fb = '' )
            AND master_inbox.os IN ('All', :os)
            AND master_inbox.status = 1
            AND $filter_time
            AND inbox.device_id IS NULL
        LIMIT {$data['limit']}
    ";
    $statement1 = $connection->prepare($sql1);
    $statement1->bindParam(":device_id", $data['device_id']);
    $statement1->bindParam(":os", $data['os']);
    $statement1->execute();
    $row_inbox = $statement1->fetchAll(PDO::FETCH_ASSOC);

    $data['inbox'] = $row_inbox;

} else {
    
    $data['error'] = 1;
    $data['message'] = 'Error: Facebook ID or Device ID is required';
    
}

//header('Content-Type: application/json');
//echo json_encode($data);   
return $data;


