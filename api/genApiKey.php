<?php
include "common.php";

function main(){
    //force HTTPS
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

    //get application name
    $appID = getHeader("appID");

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

    //return error message if connection is broken.
    if ($conn->connect_error) {
        echo $conn->connect_error."\n";
        error("mysql connection error");
    };

    $username = mysqli_real_escape_string($conn,$username);
    $passHash = mysqli_real_escape_string($conn,$passHash);
    
    $res = mysqli_query($conn,"SELECT * FROM users WHERE username = '$username' AND passHash = '$passHash' LIMIT 1");
    if ($res->num_rows == 0){
        error("Incorrect login");
    };

    $user = mysqli_fetch_array($res);
    $seed = mysqli_fetch_array($res)["apiKeySeed"];
    $key = generate($seed);


    //escape EVERYTHING
    $escKey = mysqli_real_escape_string($conn,$key);
    $escIduser = mysqli_real_escape_string($conn,$user["iduser"]);
    $escAppID = mysqli_real_escape_string($conn,$appID);
    
    //create query
    $query = "INSERT INTO apiKeys (apikey,iduser,validFrom,validTo,appName) VALUES ('$escKey',$escIduser,NOW(),DATE_ADD(NOW(), INTERVAL 1 YEAR),'$escAppID')";
    
    if (mysqli_query($conn,$query)){
        echo $key;
    } else {
        error("error adding key to database");
    };
};


function generate($seed) {
    // combine the seed with the hash of a random number > hash > return
    $full = hash("sha256",base64_encode(base64_decode($seed).hash("sha256",rand())));
    return substr($full, 32);
};




main();
?>