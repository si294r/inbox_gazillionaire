<?php

include("config.php");

//$input = file_get_contents("php://input");
$json = json_decode($input);

$data['device_id'] = isset($json->device_id) ? $json->device_id : "";
$data['facebook_id'] = isset($json->facebook_id) ? $json->facebook_id : "";
$data['os'] = isset($json->os) ? $json->os : "";

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport",
    $myuser, $mypass
);
    
if ($data['facebook_id'] != "") {
    
    $sql1 = "
        SELECT master_inbox.*,
            COALESCE(inbox.facebook_id, :facebook_id) facebook_id
        FROM master_inbox 
        LEFT JOIN inbox_fb 
            ON master_inbox.info_id = inbox_fb.info_id
            AND inbox_fb.facebook_id = :facebook_id
        WHERE COALESCE(master_inbox.target_fb, :facebook_id) = :facebook_id
            AND master_inbox.os IN ('All', :os)
            AND master_inbox.status = 1
            AND inbox_fb.facebook_id IS NULL
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
        WHERE COALESCE(master_inbox.target_device, :device_id) = :device_id
            AND master_inbox.os IN ('All', :os)
            AND master_inbox.status = 1
            AND inbox.device_id IS NULL
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


