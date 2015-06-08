<?php
include "common.php";

requireHTTPS();

$document = "./basehtml/signup.html";

function main() {
	global $document;

	$settings = parse_ini_file("../configs/config.ini",true);

	$username = postHeader("username",false);
	$password = postHeader("password",false);
	$recaptcha = postHeader("g-recaptcha-response",false);


	//return page if headers are missing
	if (is_null($username) and is_null($password)) {
		$filecontents = file_get_contents($document);
		$filecontents = str_replace("***SITE_KEY***", $settings["recaptcha"]["site"], $filecontents);
		echo $filecontents;
		exit;
	}
	


	//check inputs

	//check username
	if (is_null($username) or $username == ""){
		serverMessage("fail","No username");
		exit;
	}
	elseif (preg_match("/[^\x20-\x7E]+|[\<\>]+/", $username)) {
		serverMessage("fail","Username contains restricted characters.");
		exit;
	}
	elseif (strlen($username) > 30) {
		serverMessage("fail","Username is too long");
		exit;
	}
	elseif (strlen($username) < 2) {
		serverMessage("fail","Username is too short");
		exit;
	};

	//check password
	if (is_null($password) or $password == "") {
		serverMessage("fail","No username");
		exit;
	};

	//check recaptcha
	$res = verifyRecaptcha($recaptcha);
	if (!$res->success){
		serverMessage("fail","reCAPTCHA failed");
		exit;
	};

    $conn = mysqlConnect(); 



    //return error message if connection is broken.
    if ($conn->connect_error) {
        echo $conn->connect_error."\n";
        error("mysql connection error");
    };

    //check if username is unique
    $esc_username = mysqli_real_escape_string($conn,$username);
    $query = "SELECT iduser FROM users WHERE username = '$esc_username' LIMIT 1";
	if (mysqli_query($conn, $query) -> num_rows > 0){
		serverMessage("fail","Username is in use.");
	}

    $passHash = base64_encode(hash("sha256",$password,true));

    $esc_passHash = mysqli_real_escape_string($conn,$passHash);
    $seed = base64_encode(hash("sha256",rand(),true));


    if (mysqli_query($conn,"SELECT iduser FROM users WHERE username = '$esc_username' LIMIT 1") -> num_rows > 0) {
    	serverMessage("fail","The username has already been taken.");
    	exit;
    };

    $query = "INSERT INTO users (username,passHash,apiKeySeed) VALUES ('$username', '$passHash', '$seed')";
    mysqli_query($conn,$query);
    serverMessage("success","The user '$esc_username' has been created.");

};


main();
exit;
?>