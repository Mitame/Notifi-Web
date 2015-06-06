<?php
include "common.php";

function main(){
    //load settings
    $settings = parse_ini_file("../configs/config.ini",true);
    //get headers
    $headers = apache_request_headers();

    //check required headers or queries are sent
    $apikey = getHeader("apikey");
    $limit = getHeader("limit",false);
    $unread_only = getHeader("unreadOnly",false);


    //connect to mysql server specified in config.ini
    $conn = new mysqli($settings["mysql"]["server"],
                       $settings["mysql"]["username"],
                       $settings["mysql"]["password"],
                       $settings["mysql"]["database"]
                      );

    //return error message if connection is broken.
    if ($conn->connect_error) {
        echo $conn->connect_error."\n";
        error("mysql connection error");
    };

    //check apikey and get userid
    $userID = apikeyIsValid($conn,$apikey)["iduser"];
    if (!$userID){error("Internal error");};

    //create query
    $query = "SELECT idmessage,idfromapikey,type,message,signature,sentAt,receivedAt,hasRead FROM messages WHERE idToUser = $userID";
    if (is_null($unread_only) or strtolower($unread_only) != "false") {
        $query .= " AND hasRead = 0";
    };

    if (is_null($limit)) {
        $query .= " LIMIT 50";
    } elseif ($limit > 50){
        $query .= " LIMIT 50";
    } else {
        $query .= " LIMIT ".mysqli_real_escape_string($limit);
    };

    $res = mysqli_query($conn,$query);

    
    $messages = array();
    $row = 0;

    //place all rows in an array
    while ($row != $res->num_rows){
       $messages[$row] = mysqli_fetch_array($res);
       $row += 1;
    };
    

    //mark messages as read and add their recieved time
    foreach ($messages as &$message){
        $messageID = $message["idmessage"];
        
        if ($message["receivedAt"] == ""){
            $query = "UPDATE messages SET receivedAt=NOW(), hasRead=1 WHERE idmessage = $messageID";
            mysqli_query($conn,$query);
        };
    };

    //set each message to correct format and add them to new array
    $messagesToSend = array();

    foreach ($messages as &$message) {
        array_push($messagesToSend,array(
            "msgFrom" => getFullNameFromIDApikey($conn,$message["idfromapikey"]),
            "msgType" => $message["type"],
            "message" => $message["message"],
            "sig" => $message["signature"]
            )
        );
    };

    sendAsJSON(array("messages" => $messagesToSend));
};

main();
exit;
?>