<?php

include("config.php");

//$input = file_get_contents("php://input");
$json = json_decode($input);

$data['device_id'] = isset($json->device_id) ? $json->device_id : "";
$data['info_id'] = isset($json->info_id) ? $json->info_id : 0;
//$data['is_claimed'] = isset($json->is_claimed) ? $json->is_claimed : 0;

$connection = new PDO(
    "mysql:dbname=$mydatabase;host=$myhost;port=$myport",
    $myuser, $mypass
);
    
// create record if not exists
$sql1 = "INSERT INTO inbox (device_id, info_id, last_update)
    VALUES (:device_id, :info_id, NOW())
    ON DUPLICATE KEY UPDATE
    info_id = :info_id2, last_update = NOW()
";
$statement1 = $connection->prepare($sql1);
$statement1->bindParam(":device_id", $data['device_id']);
$statement1->bindParam(":info_id", $data['info_id']);
//$statement1->bindParam(":is_claimed", $data['is_claimed']);
$statement1->bindParam(":info_id2", $data['info_id']);
//$statement1->bindParam(":is_claimed2", $data['is_claimed']);
$statement1->execute();

$data['info_id'] = intval($data['info_id']);
//$data['is_claimed'] = intval($data['is_claimed']);
$data['affected_row'] = $statement1->rowCount();
$data['error'] = 0;
$data['message'] = 'Success';

//header('Content-Type: application/json');
//echo json_encode($data);   
return $data;
