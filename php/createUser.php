<?php

include "common.php";

function main(){
	requireHTTPS();
	//load settings
    $settings = parse_ini_file("../configs/config.ini",true);
    
    //get headers
    $headers = apache_request_headers();

    //check required headers or queries are sent
    $auth = $headers["Authorization"];
    
    if (is_null($auth)){
        $random = hash("sha256",rand());
        header('WWW-Authenticate: Basic realm="$random"');
        header('HTTP/1.0 401 Unauthorized');
        exit;
    };

    $auth = base64_decode(explode(" ",$auth)[1]);

    //get username and password hash
    $username = explode(":",$auth)[0];
    $passHash = base64_encode(hash("sha256",explode(":",$auth)[1],true));

    //connect to mysql server specified in config.ini
    $conn = new mysqli($settings["mysql"]["server"],
                       $settings["mysql"]["username"],
                       $settings["mysql"]["password"],
                       $settings["mysql"]["database"]
                      );



    $esc_username = mysqli_real_escape_string($conn,$username);
    $esc_passHash = mysqli_real_escape_string($conn,$passHash);
    $seed = base64_encode(hash("sha256",rand(),true));


    if (mysqli_query($conn,"SELECT iduser FROM users WHERE username = '$esc_username' LIMIT 1") -> num_rows > 0) {
    	error("username in use");
    };

    $query = "INSERT INTO users (username,passHash,apiKeySeed) VALUES ('$username', '$passHash', '$seed')";
    mysqli_query($conn,$query);
    echo $query;
};
main();
exit;	
?>

