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


	if (is_null($username) and is_null($password)) {
		$filecontents = file_get_contents($document);
		$filecontents = str_replace("***SITE_KEY***", $settings["recaptcha"]["site"], $filecontents);
		echo $filecontents;
		exit;
	}
	elseif (is_null($username) or $username == "") {
		serverMessage("fail","No username");
		exit;
	}
	elseif (is_null($password) or $password == "") {
		serverMessage("fail","No password");
		exit;
	}

	if (is_null($recaptcha) or $recaptcha == ""){
		serverMessage("fail","No reCAPTCHA");
		exit;
	}


	//check recaptcha
	$data = array("secret" => $settings["recaptcha"]["secret"],
				  "response" => $recaptcha,
				  "remoteip" => $_SERVER["REMOTE_ADDR"]
				  );

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/recaptcha/api/siteverify');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1); // On dev server only!
	$result = curl_exec($ch);
	$resjson = json_decode($result);
	if (!$resjson->success){
		$detail = $resjson -> error-codes;
		serverMessage("fail","reCAPTCHA failed. $detail");
		exit;
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

    $passHash = base64_encode(hash("sha256",$password,true));

    $esc_username = mysqli_real_escape_string($conn,$username);
    $esc_passHash = mysqli_real_escape_string($conn,$passHash);
    $seed = base64_encode(hash("sha256",rand(),true));


    if (mysqli_query($conn,"SELECT iduser FROM users WHERE username = '$esc_username' LIMIT 1") -> num_rows > 0) {
    	serverMessage("fail","The username has already been taken.");
    	exit;
    };

    $query = "INSERT INTO users (username,passHash,apiKeySeed) VALUES ('$username', '$passHash', '$seed')";
    mysqli_query($conn,$query);
    serverMessage("success","The user '$username' has been created.");

};


main();
exit;
?>