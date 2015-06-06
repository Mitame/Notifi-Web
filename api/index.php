<?php
include "common.php";

function main(){
    //load settings
    $settings = parse_ini_file("config.ini",true);
    //get headers
    $headers = apache_request_headers();

    //check required headers or queries are sent
    $apikey = getHeader("apikey");
    $message = getHeader("message");
    

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
    echo apikeyIsValid($conn,$apikey);
};
main();
echo "END";
exit;
?>
