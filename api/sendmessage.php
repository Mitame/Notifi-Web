<?php
include "common.php";

function main(){
    //load settings
    $settings = parse_ini_file("config.ini",true);
    //get headers
    $headers = apache_request_headers();

    //check required headers or queries are sent
    $apikey = postHeader("apikey");
    $message = json_decode(postHeader("message"),true);

    //check correct parameters exist in JSON
    if (!array_key_exists("to", $message) or !array_key_exists("message", $message)){
        error("correct parameters not given");
    };


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

    $user = apikeyIsValid($conn,$apikey);
    if (!$user){error("Internal error");};

    $toUserID = getUserFromUsername($conn,$message["to"])["iduser"];
    $body = $message["message"];
    //$signigture = $message["sig"];

    $type = $message["type"];
    if (is_null($type)){$type = "message";};

    $query = "SELECT idapikey FROM apiKeys WHERE apikey = '".mysqli_real_escape_string($conn,$apikey)."' LIMIT 1";
    echo $query."\n";
    $idfromapikey = mysqli_fetch_array(mysqli_query($conn,$query))[0];
    $fromIP = $_SERVER["REMOTE_ADDR"];

    $esc_type = mysqli_real_escape_string($conn,$type);
    $esc_body = mysqli_real_escape_string($conn,$body);
    $esc_sig = mysqli_real_escape_string($conn,$signigture);

    $query = "INSERT INTO messages (idfromapikey,idToUser,type,message,signigture,fromIP) VALUES ('$idfromapikey','$toUserID','$esc_type','$esc_body','$esc_sig','$fromIP')";
    echo $query."\n";
    $res = mysqli_query($conn,$query);
    if ($res){
        serverMessage("success","Your message was sucessfully sent.");
    }else{
        error("unexpected error");
    };
};

main();
echo "END";
exit;
?>
